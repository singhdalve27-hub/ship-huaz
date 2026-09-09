<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\EventType;
use App\Models\PackageAddOn;
use App\Models\PaymentOption;
use App\Models\VenuePackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingMail;
use App\Models\MessageThread;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $allAddOns = PackageAddOn::pluck('title', 'id');

        $bookings = Booking::with('eventType', 'venuePackage', 'paymentOption')
            ->latest()
            ->where('user_id', auth()->user()->id)
            ->whereBetween('created_at', [Carbon::now()->subWeek(), Carbon::now()])
            ->get()
            ->map(function ($booking) use ($allAddOns) {
                $addonIds = $booking->package_add_ons ?? [];
                $booking->package_add_ons = collect($addonIds)
                    ->map(fn($id) => $allAddOns[$id] ?? null)
                    ->filter()
                    ->values();
                return $booking;
            });

        // Reserved schedules for availability calendar and client transparency
        $reservedSchedules = Booking::with('eventType', 'venuePackage')
            ->whereNotIn('status', ['cancelled'])
            ->whereDate('date', '>=', Carbon::today()->subDays(1))
            ->orderBy('date')
            ->get()
            ->map(function ($b) {
                return [
                    'id'               => $b->id,
                    'date'             => $b->date->toDateString(),
                    'time_slot'        => $b->time_slot,
                    'exact_time'       => $b->exact_time,
                    'venue_package_id' => $b->venue_package_id,
                    'venue_title'      => $b->venuePackage->title ?? 'Venue Deck',
                    'event_type'       => $b->eventType->type ?? $b->eventType->name ?? 'Private Celebration',
                    'booking_mode'     => $b->booking_mode ?: 'exclusive',
                    'booker_name'      => $b->guest_first_name . ' ' . (substr($b->guest_last_name ?? '', 0, 1) ? substr($b->guest_last_name ?? '', 0, 1) . '.' : ''),
                    'status'           => $b->status,
                ];
            });

        $data = [
            'bookings'          => $bookings,
            'reservedSchedules' => $reservedSchedules,
            'eventTypes'        => EventType::where('status', 'active')->get(),
            'venuePackages'     => VenuePackage::where('status', 'active')->get(),
            'packageAddOns'     => PackageAddOn::where('status', 'active')->get(),
            'paymentOptions'    => PaymentOption::where('status', 'active')->get(),
        ];

        return Inertia::render('Client/Booking', $data);
    }

    /**
     * Display a listing of the resource.
     */
    public function indexAdmin()
    {
        $bookings = Booking::with('user', 'user.userInfo', 'eventType', 'venuePackage', 'paymentOption')
            ->orderByRaw('FIELD(status, "pending", "confirmed", "cancelled", "completed")')
            ->latest()
            ->get()
            ->map(function ($booking) {
                $addonIds = $booking->package_add_ons ?? [];
                $booking->package_add_ons = PackageAddOn::whereIn('id', $addonIds)
                    ->get(['id', 'title', 'price'])
                    ->toArray();

                return $booking;
            });

        return Inertia::render('Admin/Bookings', [
            'bookings' => $bookings,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'date'                      => ['required', 'date', 'after_or_equal:today'],
            'time_slot'                 => ['required', 'string'],
            'exact_time'                => ['required', 'string'],
            'booking_mode'              => ['required', 'string', 'in:exclusive,visitor'], 
            'event_type_id'             => ['required', 'integer', 'exists:event_types,id'],
            'venue_package_id'          => ['required', 'integer', 'exists:venue_packages,id'],
            'package_add_ons'           => ['nullable', 'array'],
            'package_add_ons.*'         => ['integer', 'exists:package_add_ons,id'],
            'guest_first_name'          => ['required', 'string', 'max:100'],
            'guest_last_name'           => ['required', 'string', 'max:100'],
            'guest_email'               => ['required', 'email', 'max:255'],
            'guest_phone'               => ['required', 'regex:/^9\d{9}$/'],
            'guest_count'               => ['required', 'integer', 'min:1'],
            'guest_request_notes'       => ['nullable', 'string', 'max:1000'],
            'payment_option_id'         => ['required'],
            'payment_account_number'    => ['nullable', 'required_unless:payment_option_id,property', 'string', 'max:20'],
            'payment_transaction_ref'   => ['nullable', 'required_unless:payment_option_id,property', 'string', 'max:100'],
            'total_payment'             => ['required', 'numeric'], 
        ]);

        // Conflict prevention check for exclusive booking:
        if ($validated['booking_mode'] === 'exclusive') {
            $targetPackage = VenuePackage::find($validated['venue_package_id']);
            $sameVenueIds = $targetPackage 
                ? VenuePackage::where('title', $targetPackage->title)->pluck('id')->toArray()
                : [$validated['venue_package_id']];

            $existingExclusive = Booking::where('date', $validated['date'])
                ->whereIn('venue_package_id', $sameVenueIds)
                ->whereNotIn('status', ['cancelled'])
                ->where(function ($q) {
                    $q->where('booking_mode', 'exclusive')
                      ->orWhereNull('booking_mode');
                })
                ->get();

            foreach ($existingExclusive as $existing) {
                if ($this->isTimeSlotConflicting($existing->time_slot, $validated['time_slot'])) {
                    return back()->withErrors([
                        'time_slot' => "This venue deck is already exclusively reserved on {$validated['date']} ({$existing->time_slot}). You may only book as a Visitor for this slot.",
                    ]);
                }
            }
        }

        $isPayAtVenue = $validated['payment_option_id'] === 'property';

        $booking = Booking::create([
            'user_id'                   => auth()->id(),
            'booking_ref'               => 'BSH-' . strtoupper(substr(uniqid(), -8)),
            'event_type_id'             => $validated['event_type_id'],
            'venue_package_id'          => $validated['venue_package_id'],
            'package_add_ons'           => $validated['package_add_ons'] ?? [],
            'payment_option_id'         => $isPayAtVenue ? null : $validated['payment_option_id'],
            'payment_account_number'    => $validated['payment_account_number'] ?? null,
            'payment_transaction_ref'   => $validated['payment_transaction_ref'] ?? null,
            'guest_first_name'          => $validated['guest_first_name'],
            'guest_last_name'           => $validated['guest_last_name'],
            'guest_email'               => $validated['guest_email'],
            'guest_phone'               => $validated['guest_phone'],
            'guest_count'               => $validated['guest_count'],
            'guest_request_notes'       => $validated['guest_request_notes'] ?? null,
            'time_slot'                 => $validated['time_slot'],
            'exact_time'                => $validated['exact_time'],
            'booking_mode'              => $validated['booking_mode'], 
            'total_payment'             => $validated['total_payment'],
            'date'                      => $validated['date'],
            'status'                    => 'pending',
        ]);

        $this->sendBookingStatusEmail($booking, 'pending');

        return redirect()->back()->with([
            'booking_ref' => $booking->booking_ref,
            'success'     => 'Booking confirmed successfully.',
        ]);
    }

    public function checkAvailability(Request $request)
    {
        $request->validate([
            'date'              => ['required', 'date'],
            'time_slot'         => ['required', 'string'],
            'venue_package_id'  => ['nullable', 'integer'],
        ]);

        $requestedTime = $request->time_slot;
        $date = $request->date;
        $venuePackageId = $request->venue_package_id;

        $sameVenueIds = [];
        if ($venuePackageId) {
            $targetPackage = VenuePackage::find($venuePackageId);
            if ($targetPackage) {
                $sameVenueIds = VenuePackage::where('title', $targetPackage->title)->pluck('id')->toArray();
            }
        }

        $query = Booking::with('eventType', 'venuePackage')
            ->where('date', $date)
            ->whereNotIn('status', ['cancelled']);

        if (!empty($sameVenueIds)) {
            $query->whereIn('venue_package_id', $sameVenueIds);
        }

        $existingBookings = $query->get();
        $exclusiveConflict = null;

        foreach ($existingBookings as $booking) {
            if ($this->isTimeSlotConflicting($booking->time_slot, $requestedTime)) {
                if ($booking->booking_mode === 'exclusive' || empty($booking->booking_mode)) {
                    $exclusiveConflict = $booking;
                    break;
                }
            }
        }

        if ($exclusiveConflict) {
            // An exclusive event already exists on this venue & time slot
            // Exclusive booking is blocked; only VISITOR mode is allowed!
            return response()->json([
                'available'    => true,
                'mode'         => 'visitor',
                'conflict'     => true,
                'message'      => "This venue is already exclusively reserved for this date and time slot. You can still proceed by booking as a Visitor.",
                'booked_by'    => $exclusiveConflict->guest_first_name . ' ' . (substr($exclusiveConflict->guest_last_name ?? '', 0, 1) ? substr($exclusiveConflict->guest_last_name ?? '', 0, 1) . '.' : ''),
                'booked_event' => $exclusiveConflict->eventType->type ?? $exclusiveConflict->eventType->name ?? 'Private Event',
                'booked_time'  => $exclusiveConflict->time_slot,
            ]);
        }

        return response()->json([
            'available' => true,
            'mode'      => 'exclusive',
            'conflict'  => false,
            'message'   => 'Venue is available for exclusive event reservation.',
        ]);
    }

    private function isTimeSlotConflicting(string $bookedTime, string $requestedTime): bool
    {
        if ($bookedTime === $requestedTime) {
            return true;
        }

        $isFullDayBooked = str_contains($bookedTime, 'Full Day') || str_contains($bookedTime, '8:00 AM – 5:00 PM') || str_contains($bookedTime, '8:00 AM – 10:00 PM');
        $isFullDayReq = str_contains($requestedTime, 'Full Day') || str_contains($requestedTime, '8:00 AM – 5:00 PM') || str_contains($requestedTime, '8:00 AM – 10:00 PM');

        if ($isFullDayBooked || $isFullDayReq) {
            return true;
        }

        return false;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'required|in:confirmed,cancelled,completed',
        ]);

        if ($booking->status === 'completed' && $request->status === 'cancelled') {
            return back()->with([
                'error' => 'This booking is already completed and cannot be cancelled.',
            ]);
        }

        if ($booking->status === 'cancelled' && in_array($request->status, ['confirmed', 'completed'])) {
            return back()->with([
                'error' => 'This booking is already cancelled and cannot be confirmed or completed.',
            ]);
        }

        $booking->load('user', 'eventType', 'venuePackage');

        $booking->update([
            'status' => $request->status,
            'cancelled_by' => $request->status === 'cancelled' ? 'admin' : null
        ]);

        if ($request->status === 'completed') {
            MessageThread::where('booking_id', $booking->id)
                ->update([
                    'read_by_client' => true,
                    'read_by_admin' => true
                ]);
        }

        $this->sendBookingStatusEmail($booking, $request->status);

        return back()->with([
            'success' => 'Booking status updated successfully.',
        ]);
    }

    public function cancel(Booking $booking)
    {
        if ($booking->status === 'completed') {
            return back()->with([
                'error' => 'This booking is already completed and cannot be cancelled.',
            ]);
        }

        if ($booking->status === 'cancelled') {
            return back()->with([
                'error' => 'This booking is already cancelled.',
            ]);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_by' => 'client'
        ]);

        $this->sendBookingStatusEmail($booking, 'cancelled');

        return back()->with([
            'success' => 'Booking status updated successfully.',
        ]);
    }

    private function sendBookingStatusEmail(Booking $booking, string $status): void
    {
        $booking->load('eventType', 'venuePackage', 'user', 'user.userInfo');

        $guestName = $booking->guest_first_name . ' ' . $booking->guest_last_name;
        $userEmail  = $booking->user->email ?? null;
        $userName   = $booking->user->userInfo->first_name . ' ' . $booking->user->userInfo->last_name;

        Mail::to($booking->guest_email)->send(new BookingMail($booking, $status, $guestName));

        if ($userEmail && $userEmail !== $booking->guest_email) {
            Mail::to($userEmail)->send(new BookingMail($booking, $status, $userName));
        }
    }
}