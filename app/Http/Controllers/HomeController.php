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

        $bookings = Booking::with(['venuePackage', 'eventType', 'paymentOption', 'feedback'])
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_ref' => $booking->booking_ref,
                'package' => $booking->venuePackage?->title ?? 'Butal Ship Hauz Deck',
                'event_type' => $booking->eventType?->type ?? $booking->eventType?->title ?? 'Special Event',
                'status' => $booking->status,
                'date' => $booking->date ? (is_string($booking->date) ? $booking->date : $booking->date->toDateString()) : null,
                'time_slot' => $booking->time_slot,
                'booking_mode' => $booking->booking_mode ?? 'exclusive',
                'guests' => $booking->guest_count,
                'total_amount' => (float) $booking->total_payment,
                'downpayment_paid' => round((float) $booking->total_payment * 0.5, 2),
                'remaining_balance' => round((float) $booking->total_payment * 0.5, 2),
                'payment_method' => $booking->paymentOption?->payment ?? 'GCash',
                'payment_transaction_ref' => $booking->payment_transaction_ref,
                'payment_account_number' => $booking->payment_account_number,
                'feedback' => $booking->feedback ? [
                    'id' => $booking->feedback->id,
                    'rating' => $booking->feedback->rating,
                    'cleanliness_rating' => $booking->feedback->cleanliness_rating,
                    'staff_rating' => $booking->feedback->staff_rating,
                    'facilities_rating' => $booking->feedback->facilities_rating,
                    'comment' => $booking->feedback->comment,
                    'created_at' => $booking->feedback->created_at?->format('M j, Y'),
                ] : null,
                'created_at' => $booking->created_at?->toIso8601String(),
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