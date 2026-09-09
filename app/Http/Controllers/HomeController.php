<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\MessageThread;
use App\Models\VenuePackage; // <-- Idinagdag ang VenuePackage Model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $bookings = Booking::with(['venuePackage', 'eventType'])
            ->where('user_id', $user->id)
            ->orderByDesc('date')
            ->take(10)
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'package' => $booking->venuePackage->title ?? null,
                'event_type' => $booking->eventType->title ?? $booking->eventType->name ?? null,
                'status' => $booking->status,
                'date' => $booking->date->toDateString(),
                'guests' => $booking->guest_count,
                'total_amount' => (float) $booking->total_payment,
            ]);

        $notifications = MessageThread::with('latestMessage')
            ->where('user_id', $user->id)
            ->whereIn('type', ['booking_reminder', 'booking_confirmed'])
            ->orderByDesc('updated_at')
            ->take(10)
            ->get()
            ->map(fn (MessageThread $thread) => [
                'id' => $thread->id,
                'read_at' => $thread->read_by_client ? $thread->updated_at?->toIso8601String() : null,
                'title' => $this->notificationTitle($thread),
                'message' => optional($thread->latestMessage)->body ?? $thread->subject,
                'created_at' => $thread->created_at->toIso8601String(),
            ]);

        // Venues with event types for the Feeds tab
        $venues = VenuePackage::with('eventType')
            ->where('status', 'active')
            ->get();

        return Inertia::render('Client/Home', [
            'bookings' => $bookings,
            'notifications' => $notifications,
            'venues' => $venues, // <-- Ipapasa natin dito papunta sa frontend
        ]);
    }

    /**
     * Map a message thread's type to the notification heading shown to the client.
     */
    private function notificationTitle(MessageThread $thread): string
    {
        return match ($thread->type) {
            'booking_confirmed' => 'Booking Confirmed!',
            'booking_reminder' => 'Upcoming Reservation Reminder',
            default => $thread->subject,
        };
    }
}