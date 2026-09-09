<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * Store or update client feedback for a completed booking.
     */
    public function store(Request $request, Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'rating'              => ['required', 'integer', 'min:1', 'max:5'],
            'cleanliness_rating'  => ['nullable', 'integer', 'min:1', 'max:5'],
            'staff_rating'        => ['nullable', 'integer', 'min:1', 'max:5'],
            'facilities_rating'   => ['nullable', 'integer', 'min:1', 'max:5'],
            'value_rating'        => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment'             => ['nullable', 'string', 'max:1000'],
        ]);

        Feedback::updateOrCreate(
            ['booking_id' => $booking->id],
            [
                'user_id'            => Auth::id(),
                'venue_package_id'   => $booking->venue_package_id,
                'rating'             => $validated['rating'],
                'cleanliness_rating' => $validated['cleanliness_rating'] ?? null,
                'staff_rating'       => $validated['staff_rating'] ?? null,
                'facilities_rating'  => $validated['facilities_rating'] ?? null,
                'value_rating'       => $validated['value_rating'] ?? null,
                'comment'            => $validated['comment'] ?? null,
                'status'             => 'approved',
            ]
        );

        return redirect()->back()->with('success', 'Thank you! Your feedback helps us continuously improve the Butal Ship Hauz experience.');
    }
}
