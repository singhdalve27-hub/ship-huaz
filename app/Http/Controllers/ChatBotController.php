<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ChatBotNode;
use App\Models\EventType;
use App\Models\VenuePackage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatBotController extends Controller
{
    public function index(): JsonResponse
    {
        $nodes = ChatBotNode::with('options')
            ->where('status', 'active')
            ->get();

        $eventTypes = EventType::where('status', 'active')->get(['id', 'type']);
        
        // BAGO: Isinama natin ang 'image' sa kukunin mula sa database
        $venuePackages = VenuePackage::where('status', 'active')->get([
            'id', 'title', 'description', 'guests', 'price', 
            'price_morning', 'price_afternoon', 'price_fullday', 'price_visitor',
            'event_type_id', 'image' 
        ]);

        $mapped = [];
        $mainNodeId = null;

        foreach ($nodes as $node) {
            $images = $node->images ? json_decode($node->images) : [];

            $dynamicData = null;
            if ($node->dynamic_content === 'event_types') {
                $dynamicData = [
                    'type' => 'event_types',
                    'items' => $eventTypes
                ];
            } elseif (str_starts_with($node->dynamic_content ?? '', 'venue_packages_')) {
                $evtId = str_replace('venue_packages_', '', $node->dynamic_content);
                $filteredPackages = $venuePackages->where('event_type_id', $evtId)->values();

                $dynamicData = [
                    'type' => 'venue_packages',
                    'items' => $filteredPackages
                ];
            }

            $mapped[$node->id] = [
                'id'           => $node->id,
                'node_key'     => $node->node_key,
                'message'      => $node->message,
                'images'       => $images,
                'dynamic_data' => $dynamicData, 
                'options'      => $node->options->map(fn($opt) => [
                    'label'        => $opt->option['label'] ?? '',
                    'next_node_id' => $opt->option['next_node_id'] ?? 0,
                ])->values()->toArray(),
            ];

            if (strtolower($node->node_key) === 'main') {
                $mainNodeId = $node->id;
            }
        }

        return response()->json([
            'nodes'        => $mapped,
            'main_node_id' => $mainNodeId,
        ]);
    }

    public function nodes(): JsonResponse
    {
        $nodes = ChatBotNode::where('status', 'active')
            ->orderBy('node_key')
            ->get(['id', 'node_key']);

        return response()->json($nodes);
    }

    public function checkDate(Request $request): JsonResponse
    {
        $date = $request->query('date');
        if (!$date || !strtotime($date)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or missing date parameter.',
            ], 400);
        }

        $carbonDate = Carbon::parse($date);
        $today = Carbon::today();
        $isPast = $carbonDate->lt($today);

        if ($isPast) {
            return response()->json([
                'success'        => true,
                'date'           => $date,
                'formatted_date' => $carbonDate->format('F j, Y (l)'),
                'is_past'        => true,
                'available'      => false,
                'message'        => 'This date has already passed. Please choose a future date.',
            ]);
        }

        // Fetch non-cancelled bookings on this date
        $bookings = Booking::with(['eventType', 'venuePackage'])
            ->whereDate('date', $date)
            ->whereNotIn('status', ['cancelled'])
            ->get();

        $exclusiveBookings = $bookings->filter(fn($b) => empty($b->booking_mode) || $b->booking_mode === 'exclusive');
        
        $morningTaken = false;
        $afternoonTaken = false;
        $fullDayTaken = false;

        foreach ($exclusiveBookings as $b) {
            $slot = $b->time_slot;
            if (str_contains($slot, 'Full Day') || str_contains($slot, '8:00 AM – 5:00 PM') || str_contains($slot, '8:00 AM – 10:00 PM') || str_contains($slot, 'Whole Day')) {
                $fullDayTaken = true;
                $morningTaken = true;
                $afternoonTaken = true;
            } elseif (str_contains($slot, 'Morning') || str_contains($slot, '8:00 AM – 12:00 NN')) {
                $morningTaken = true;
            } elseif (str_contains($slot, 'Afternoon') || str_contains($slot, '1:00 PM – 5:00 PM') || str_contains($slot, '6:00 PM')) {
                $afternoonTaken = true;
            }
        }

        $exclusiveAvailable = !$fullDayTaken && (!$morningTaken || !$afternoonTaken);
        
        $message = '';
        if ($fullDayTaken) {
            $message = "The venue is fully reserved for an exclusive event on this day. However, Visitor Tour Passes (₱150/pax) are still welcomed!";
        } elseif ($morningTaken && !$afternoonTaken) {
            $message = "Morning slot is reserved. Afternoon (1:00 PM onwards) and Visitor Tour Passes are available!";
        } elseif (!$morningTaken && $afternoonTaken) {
            $message = "Afternoon slot is reserved. Morning (8:00 AM – 12:00 NN) and Visitor Tour Passes are available!";
        } else {
            $message = "Great news! This date is completely open for both Exclusive Events and Visitor Tour Passes!";
        }

        return response()->json([
            'success'             => true,
            'date'                => $date,
            'formatted_date'      => $carbonDate->format('F j, Y (l)'),
            'is_past'             => false,
            'available'           => true,
            'exclusive_available' => $exclusiveAvailable,
            'visitor_available'   => true,
            'morning_taken'       => $morningTaken,
            'afternoon_taken'     => $afternoonTaken,
            'fullday_taken'       => $fullDayTaken,
            'message'             => $message,
        ]);
    }

    public function trackBooking(Request $request): JsonResponse
    {
        $query = trim($request->query('query', ''));
        if (strlen($query) < 3) {
            return response()->json([
                'found'   => false,
                'message' => 'Please enter a valid Booking Reference (e.g., BSH-...) or contact phone number.',
            ]);
        }

        $booking = Booking::with(['venuePackage', 'eventType'])
            ->where(function($q) use ($query) {
                $q->where('booking_ref', 'LIKE', "%{$query}%")
                  ->orWhere('guest_phone', 'LIKE', "%{$query}%");
            })
            ->orderByDesc('id')
            ->first();

        if (!$booking) {
            return response()->json([
                'found'   => false,
                'message' => "No reservation found matching '{$query}'. Please check your Booking Reference code (e.g., BSH-...) or registered phone number.",
            ]);
        }

        return response()->json([
            'found'          => true,
            'booking_ref'    => $booking->booking_ref,
            'guest_name'     => $booking->guest_first_name . ' ' . (substr($booking->guest_last_name ?? '', 0, 1) ? substr($booking->guest_last_name ?? '', 0, 1) . '.' : ''),
            'event_type'     => $booking->eventType?->type ?? 'General Event',
            'package_title'  => $booking->venuePackage?->title ?? 'Venue Reservation',
            'date'           => Carbon::parse($booking->date)->format('F j, Y (l)'),
            'time_slot'      => $booking->time_slot,
            'guest_count'    => $booking->guest_count,
            'booking_mode'   => $booking->booking_mode ?? 'exclusive',
            'status'         => $booking->status,
            'total_payment'  => $booking->total_payment,
        ]);
    }
}