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

    /**
     * Smart Knowledge Base QA Engine: Answers any question about Butal Ship Hauz.
     */
    public function ask(Request $request): JsonResponse
    {
        $raw = trim($request->input('question', $request->query('question', '')));
        if (empty($raw)) {
            return response()->json([
                'answered' => false,
                'message'  => "Please type a question so I can assist you!",
            ]);
        }

        $query = strtolower($raw);
        $words = preg_split('/[\s,\.\?\!\;\:\-\_]+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        // Filter out very short common filler words
        $stopWords = ['the', 'is', 'at', 'which', 'on', 'a', 'an', 'and', 'or', 'in', 'to', 'for', 'of', 'ba', 'ang', 'mga', 'ng', 'sa', 'kay', 'po', 'na', 'mo', 'ko', 'ug', 'ni', 'og'];
        $significantWords = array_values(array_diff($words, $stopWords));

        // ── 1. Structured Venue Knowledge Base ──
        $knowledge = [
            [
                'id'       => 'generator',
                'title'    => '100% Commercial Backup Power Generator',
                'patterns' => ['generator', 'brownout', 'blackout', 'power', 'outage', 'kuryente', 'koryente', 'genset', 'backup', 'electric', 'electricity'],
                'answer'   => "Yes! Butal Ship Hauz is equipped with a 100% heavy-duty commercial automatic backup generator. In the event of a municipal brownout, the generator seamlessly powers all venue lighting, pro sound systems, and full stage equipment with zero interruption.",
                'buttons'  => [
                    ['label' => '👉 Book a Reservation', 'href' => '/client/booking?mode=exclusive']
                ],
            ],
            [
                'id'       => 'parking',
                'title'    => 'Spacious Free Guest Parking',
                'patterns' => ['parking', 'park', 'cars', 'van', 'bus', 'vehicles', 'kotse', 'sasakyan', 'parkingan', 'garage', 'space'],
                'answer'   => "We provide spacious, well-lit, and completely free on-site parking that easily accommodates up to 50+ private vehicles, vans, and tour buses, with security marshals on standby to assist your guests.",
            ],
            [
                'id'       => 'hours',
                'title'    => 'Operating & Visiting Hours',
                'patterns' => ['hours', 'open', 'close', 'opening', 'closing', 'time', 'oras', 'bukas', 'sira', 'schedule', 'daily', 'visiting'],
                'answer'   => "Butal Ship Hauz is open daily from 8:00 AM to 6:00 PM for walk-in visitors, ocular tours, and sightseeing. For private evening banquets and exclusive celebrations, the venue operates until 10:00 PM (or overnight for room accommodation guests).",
                'buttons'  => [
                    ['label' => '🎫 Book Tour Pass (₱150)', 'href' => '/client/booking?mode=visitor'],
                    ['label' => '📞 Call Hotline', 'href' => 'tel:09207139299']
                ],
            ],
            [
                'id'       => 'entrance',
                'title'    => 'Visitor Tour Pass & Entrance Fee',
                'patterns' => ['entrance', 'fee', 'ticket', 'tour pass', 'visitor', 'walk in', 'ocular', 'sightseeing', 'pasyal', 'magkano entrance', 'pila entrance', 'per head', 'pax'],
                'answer'   => "Our Visitor Tour Pass is only ₱150 per person! This grants you full access to explore the ship decks, enjoy the panoramic ocean trade winds, and take unlimited photos across our unique maritime landmarks.",
                'buttons'  => [
                    ['label' => '🎫 Book Tour Pass (₱150)', 'href' => '/client/booking?mode=visitor'],
                    ['label' => '📅 Check Date Availability', 'custom_action' => 'date_checker']
                ],
            ],
            [
                'id'       => 'corkage',
                'title'    => 'Catering & Corkage Guidelines',
                'patterns' => ['corkage', 'catering', 'food', 'lechon', 'drinks', 'outside food', 'beverage', 'pagkain', 'kaon', 'ulam', 'beer', 'liquor', 'cook', 'potluck'],
                'answer'   => "Outside caterers and styling suppliers are welcomed for exclusive reservations! We provide a dedicated catering prep kitchen with zero or minimal corkage fees. Lechon, celebration cakes, and outside drinks are fully permitted for your private celebration.",
                'buttons'  => [
                    ['label' => '📞 Inquire with Crew', 'href' => 'tel:09207139299']
                ],
            ],
            [
                'id'       => 'location',
                'title'    => 'Venue Location & Directions',
                'patterns' => ['location', 'address', 'where', 'saan', 'asa', 'directions', 'how to get', 'commute', 'bus', 'van', 'tubigon', 'tagbilaran', 'ubay', 'map', 'gps', 'capawan', 'talibon'],
                'answer'   => "Butal Ship Hauz is located in Sitio Capawan, Poblacion, Talibon, Bohol, Philippines. From Tagbilaran City, Tubigon Port, or Ubay Port, take a public bus or van bound for Talibon (approx. 2 hours travel time). Ask the driver to drop you off near Butal Ship Hauz in Capawan!",
                'buttons'  => [
                    ['label' => '🗺️ Open in Google Maps (GPS)', 'href' => 'https://www.google.com/maps/search/?api=1&query=Butal+Ship+Hauz,+Capawan,+Talibon,+Bohol', 'external' => true],
                    ['label' => '📞 Call Hotline (0920 713 9299)', 'href' => 'tel:09207139299']
                ],
            ],
            [
                'id'       => 'payments',
                'title'    => 'Payment Terms & Downpayment',
                'patterns' => ['payment', 'downpayment', 'deposit', 'gcash', 'bank', 'bdo', 'bpi', 'cash', 'terms', 'how to pay', 'bayad', 'balance', '50%'],
                'answer'   => "A 50% reservation downpayment confirms and locks your date on our official calendar. We accept GCash, Bank Transfer (BDO, BPI), and Cash on site at the venue office. The remaining balance can be settled on or before the day of your event.",
                'buttons'  => [
                    ['label' => '👉 Book a Reservation', 'href' => '/client/booking?mode=exclusive']
                ],
            ],
            [
                'id'       => 'photography',
                'title'    => 'Photography & Prenup Sessions',
                'patterns' => ['photo', 'prenup', 'shoot', 'photographer', 'pictorial', 'debut pictorial', 'camera', 'video', 'pictures', 'instagram', 'pose'],
                'answer'   => "Photography and prenuptial sessions start at ₱2,500. Featuring authentic ship architecture, captain's wheel, grand staircase, and coastal sunset horizon, Butal Ship Hauz is Bohol's most distinctive backdrop for prenups and pictorials!",
                'buttons'  => [
                    ['label' => '👉 Book Photo Session', 'href' => '/client/booking?mode=exclusive'],
                    ['label' => '📞 Inquire via Hotline', 'href' => 'tel:09207139299']
                ],
            ],
            [
                'id'       => 'ingress',
                'title'    => 'Ingress & Egress Setup Hours',
                'patterns' => ['ingress', 'egress', 'setup', 'set up', 'styling', 'preparation', 'advance', 'hours before', 'decorator'],
                'answer'   => "Standard packages include 2 complimentary hours before the event for ingress (decor styling, stage prep, sound testing) and 1 hour after the event for egress. Extended setup hours can be coordinated with our reservation manager.",
            ],
            [
                'id'       => 'sound_lights',
                'title'    => 'Professional Sound & Stage Lighting',
                'patterns' => ['sound', 'speaker', 'lights', 'lighting', 'mic', 'microphone', 'dj', 'stage', 'audio', 'acoustic', 'band'],
                'answer'   => "All exclusive packages include professional high-clarity speaker arrays, wireless handheld microphones, theatrical ambient LED mood wash lights, and an elevated center stage ready for your host, DJ, or live acoustic band.",
            ],
            [
                'id'       => 'bridal_suite',
                'title'    => 'Private Dressing & Holding Suite',
                'patterns' => ['dressing room', 'bridal suite', 'holding room', 'makeup', 'bihisan', 'bride suite', 'aircon room', 'suite'],
                'answer'   => "We provide an exclusive, private airconditioned dressing suite equipped with lighted vanity mirrors, comfortable seating, and clothing racks for the bride, debutante, or event host.",
            ],
            [
                'id'       => 'rooms',
                'title'    => 'Overnight Ship Accommodations',
                'patterns' => ['room', 'stay', 'sleep', 'hotel', 'overnight', 'bed', 'accommodation', 'tulog', 'matulog', 'cabin', 'overnight stay'],
                'answer'   => "Spend the night aboard the Ship Hauz! Our nautical-themed cabin rooms start at ₱1,200 per night, featuring scenic coastal morning breezes and full access to venue grounds.",
                'buttons'  => [
                    ['label' => '📞 Reserve a Room (0920 713 9299)', 'href' => 'tel:09207139299']
                ],
            ],
            [
                'id'       => 'island_tours',
                'title'    => 'Northern Bohol Guided Island Tours',
                'patterns' => ['island', 'island tour', 'tour routes', 'hopping', 'beach', 'waterfall', 'snorkeling', 'routes', 'islands'],
                'answer'   => "Explore Northern Bohol's hidden gems! We offer 12 guided tour routes starting at ₱800 per person, covering secluded sandbars, pristine beaches, waterfall adventures, and coral reef snorkeling departing from Butal Ship Hauz.",
                'buttons'  => [
                    ['label' => '📞 Inquire about Island Tours', 'href' => 'tel:09207139299']
                ],
            ],
            [
                'id'       => 'history',
                'title'    => 'History & Ownership (Captain Marcelo Butal)',
                'patterns' => ['owner', 'captain', 'marcelo', 'butal', 'who owns', 'history', 'built', 'story', 'kumpas', 'tag-iya', 'creator', 'may-ari'],
                'answer'   => "Butal Ship Hauz was founded by Captain Marcelo Butal, a seasoned ship captain and native of Barangay Zamora, Talibon, who spent 20 years captaining merchant vessels worldwide. Construction began in March 2024 and officially opened to visitors in January 2025.",
            ],
            [
                'id'       => 'admiral_deck',
                'title'    => "The Admiral's Grand Deck",
                'patterns' => ['admiral', 'grand deck', 'ballroom', 'capacity', 'seating', 'pax', 'how many guests', 'largest deck', 'main hall'],
                'answer'   => "The Admiral's Grand Deck is our flagship ballroom hall accommodating up to 250 guests for banquets and 350 for theater/cocktails. It features panoramic ocean vistas, theatrical lighting, elevated center stage, and a VIP holding suite.",
                'buttons'  => [
                    ['label' => '👉 Book the Grand Deck', 'href' => '/client/booking?mode=exclusive']
                ],
            ],
            [
                'id'       => 'sunset_terrace',
                'title'    => 'Sunset Promenade & Skyline Terrace',
                'patterns' => ['sunset', 'promenade', 'skyline', 'terrace', 'open air', 'outdoor', 'breeze', 'stars'],
                'answer'   => "Sunset Promenade & Skyline Terrace is an open-air upper deck accommodating 120–180 guests. Offering refreshing coastal breezes, fairy string lighting, and an acoustic stage—ideal for sunset wedding vows, cocktail dinner receptions, and reunions.",
                'buttons'  => [
                    ['label' => '👉 Book Skyline Terrace', 'href' => '/client/booking?mode=exclusive']
                ],
            ],
            [
                'id'       => 'function_hall',
                'title'    => "Captain's Executive Function Hall",
                'patterns' => ['executive', 'function hall', 'seminar', 'meeting', 'conference', 'workshop', 'classroom'],
                'answer'   => "The Captain's Executive Function Hall accommodates 50–80 guests for business conferences, corporate seminars, and private dinners. Equipped with a laser projector, 120-inch motorized screen, high-speed Wi-Fi, and conference microphones.",
                'buttons'  => [
                    ['label' => '👉 Book Function Hall', 'href' => '/client/booking?mode=exclusive']
                ],
            ],
            [
                'id'       => 'wedding',
                'title'    => 'Weddings & Receptions',
                'patterns' => ['wedding', 'kasal', 'bride', 'groom', 'reception', 'vows', 'matrimony'],
                'answer'   => "Exchange vows with the Bohol sea as your backdrop! Our wedding packages include exclusive deck access, full sound & ambient lighting, banquet tables and chairs, private bridal dressing suite, and backup generator assurance.",
                'buttons'  => [
                    ['label' => '👉 Book a Wedding Reservation', 'href' => '/client/booking?mode=exclusive'],
                    ['label' => '📅 Check Wedding Date', 'custom_action' => 'date_checker']
                ],
            ],
            [
                'id'       => 'debut',
                'title'    => 'Debuts & Birthday Celebrations',
                'patterns' => ['debut', 'birthday', 'debutante', '18th', 'sweet 16', 'kaarawan', 'party', 'celebration', 'reunion'],
                'answer'   => "Make your grand entrance down the ship's illuminated staircase! We host 18th debuts, Sweet 16s, and milestone birthday celebrations with dynamic dance floor lighting, stage setup, and spacious deck seating.",
                'buttons'  => [
                    ['label' => '👉 Book Birthday / Debut', 'href' => '/client/booking?mode=exclusive']
                ],
            ],
            [
                'id'       => 'pool',
                'title'    => 'Swimming & Pool Amenities',
                'patterns' => ['pool', 'swimming', 'swimming pool', 'langoy', 'water'],
                'answer'   => "Butal Ship Hauz is a dryland architectural ship landmark and hospitality venue featuring ocean-view promenade decks, function halls, and accommodations. While there is no full-size swimming pool on the ship, guests enjoy panoramic sea trade winds, rooftop sunset lounges, and guided island-hopping beach tours!",
            ],
            [
                'id'       => 'shifts',
                'title'    => 'Shifts: Morning vs Afternoon vs Full Day',
                'patterns' => ['morning', 'afternoon', 'night', 'shift', 'half day', 'whole day', 'fullday', 'rates', 'pricing'],
                'answer'   => "We offer flexible shifts! Morning Shift (8:00 AM – 12:00 NN) and Afternoon Shift (1:00 PM – 5:00 PM) are available at ~60% of the full package price. Full Day Exclusive (8:00 AM – 10:00 PM) grants you total access throughout the day!",
                'buttons'  => [
                    ['label' => '📅 Check Slot Availability', 'custom_action' => 'date_checker']
                ],
            ],
            [
                'id'       => 'capacity',
                'title'    => 'Venue Guest Capacities',
                'patterns' => ['capacity', 'guests', 'kasya', 'ilan', 'ilang tao', 'seating', 'maximum', 'how many', 'pax', 'attendees', 'tao'],
                'answer'   => "Our venue accommodates gatherings from intimate 50-guest banquets up to 350-guest grand celebrations!\n\n• The Admiral's Grand Deck: Up to 250 guests banquet / 350 theater\n• Sunset Promenade Terrace: 120–180 guests open-air\n• Captain's Function Hall: 50–80 guests air-conditioned",
                'buttons'  => [
                    ['label' => '👉 Book a Reservation', 'href' => '/client/booking?mode=exclusive'],
                    ['label' => '📅 Check Date', 'custom_action' => 'date_checker']
                ],
            ],
            [
                'id'       => 'wifi',
                'title'    => 'Free High-Speed Wi-Fi',
                'patterns' => ['wifi', 'wi-fi', 'internet', 'connection', 'signal', 'data'],
                'answer'   => "Yes! Free high-speed Wi-Fi is available across our deck lounges and air-conditioned function halls for both event hosts and visiting guests.",
            ],
            [
                'id'       => 'lead_time',
                'title'    => 'Reservation Lead Time & Rush Bookings',
                'patterns' => ['advance', 'lead time', 'kailan', 'earliest', 'rush', 'how early', 'days before', 'months before'],
                'answer'   => "We recommend reserving at least 2 to 4 weeks in advance to secure your preferred date and time slot. Rush bookings are also accepted depending on real-time deck availability!",
                'buttons'  => [
                    ['label' => '📅 Check Date Availability', 'custom_action' => 'date_checker']
                ],
            ],
            [
                'id'       => 'pets',
                'title'    => 'Pet Policy',
                'patterns' => ['pet', 'pets', 'dog', 'dogs', 'cat', 'cats', 'aso', 'pusa', 'hayop', 'animals'],
                'answer'   => "Pets are welcome on our outdoor scenic promenade decks provided they are leashed, diapered, and attended by their owners.",
            ],
            [
                'id'       => 'hotlines',
                'title'    => 'Official Contact Hotlines',
                'patterns' => ['contact', 'hotline', 'phone', 'number', 'cell', 'call', 'telepono', 'tawag'],
                'answer'   => "You can reach our reservation team directly at:\n• Smart / TNT: 0920 713 9299\n• Globe / TM: 0930 903 6834\n• Email: reservations@butalshiphauz.com.ph\n• Office hours: 8:00 AM – 6:00 PM daily",
                'buttons'  => [
                    ['label' => '📞 Call Now (0920 713 9299)', 'href' => 'tel:09207139299'],
                    ['label' => '💬 Send SMS Text', 'href' => 'sms:09207139299']
                ],
            ],
        ];

        // ── 2. Dynamic scoring across Knowledge Base ──
        $bestMatch = null;
        $highestScore = 0;

        foreach ($knowledge as $item) {
            $score = 0;

            // Pattern check
            foreach ($item['patterns'] as $pat) {
                if (str_contains($query, strtolower($pat))) {
                    $score += 15;
                }
            }

            // Word-level check
            foreach ($significantWords as $word) {
                if (str_contains(strtolower($item['title']), $word)) {
                    $score += 8;
                }
                foreach ($item['patterns'] as $pat) {
                    if (str_contains(strtolower($pat), $word)) {
                        $score += 5;
                    }
                }
                if (str_contains(strtolower($item['answer']), $word)) {
                    $score += 2;
                }
            }

            if ($score > $highestScore) {
                $highestScore = $score;
                $bestMatch = $item;
            }
        }

        // ── 3. Check Dynamic Venue Packages and ChatBot Nodes in DB if needed ──
        if ($highestScore < 15) {
            try {
                $packages = VenuePackage::where('status', 'active')->get();
                foreach ($packages as $pkg) {
                    $pkgTitle = strtolower($pkg->title);
                    $pkgScore = 0;

                    if (str_contains($query, $pkgTitle)) {
                        $pkgScore += 25;
                    }

                    foreach ($significantWords as $word) {
                        if (str_contains($pkgTitle, $word)) {
                            $pkgScore += 10;
                        }
                    }

                    if ($pkgScore > $highestScore && $pkgScore >= 10) {
                        $highestScore = $pkgScore;
                        $bestMatch = [
                            'id'      => 'package_' . $pkg->id,
                            'title'   => $pkg->title,
                            'answer'  => "Package: {$pkg->title}\nCapacity: Up to {$pkg->guests} guests\nStarting Rate: ₱" . number_format($pkg->price, 2) . "\nDescription: {$pkg->description}",
                            'buttons' => [
                                ['label' => '👉 Book this Package', 'href' => "/client/booking?package_id={$pkg->id}&mode=exclusive"],
                                ['label' => '📅 Check Date', 'custom_action' => 'date_checker']
                            ]
                        ];
                    }
                }

                // ── 4. Check ChatBot Nodes in DB ──
                $dbNodes = ChatBotNode::where('status', 'active')->get();
                foreach ($dbNodes as $node) {
                    $nodeKey = strtolower($node->node_key);
                    $nodeScore = 0;

                    if (str_contains($query, $nodeKey)) {
                        $nodeScore += 20;
                    }
                    foreach ($significantWords as $word) {
                        if (str_contains($nodeKey, $word)) {
                            $nodeScore += 6;
                        }
                    }

                    if ($nodeScore > $highestScore && $nodeScore >= 15) {
                        $highestScore = $nodeScore;
                        $bestMatch = [
                            'id'      => 'node_' . $node->id,
                            'title'   => $node->node_key,
                            'answer'  => $node->message,
                            'buttons' => [
                                ['label' => '👉 Book a Reservation', 'href' => '/client/booking?mode=exclusive']
                            ]
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Graceful fallback to static knowledge base if DB connection is unavailable
            }
        }

        // Return answer if confident enough (score >= 8)
        if ($bestMatch && $highestScore >= 8) {
            return response()->json([
                'answered'       => true,
                'score'          => $highestScore,
                'title'          => $bestMatch['title'],
                'answer'         => $bestMatch['answer'],
                'action_buttons' => $bestMatch['buttons'] ?? [],
            ]);
        }

        // Friendly fallback
        return response()->json([
            'answered' => false,
            'message'  => "I'd be glad to help! You can ask me about:\n\n• Venue Rates & ₱150 Visitor Passes\n• Decks & Seating Capacity (up to 350 pax)\n• 100% Automatic Backup Generator\n• Free 50+ Car Parking & Security\n• Outside Catering & Corkage Guidelines\n• Location & Turn-by-Turn Directions\n• Overnight Rooms (₱1,200/night) & Island Tours\n• Booking Downpayment (50% deposit)\n\nOr feel free to contact our crew directly at 0920 713 9299!",
            'action_buttons' => [
                ['label' => '📅 Check Date Availability', 'custom_action' => 'date_checker'],
                ['label' => '🔍 Track Booking Status', 'custom_action' => 'booking_tracker'],
                ['label' => '📞 Call Hotline (0920 713 9299)', 'href' => 'tel:09207139299'],
            ],
        ]);
    }
}