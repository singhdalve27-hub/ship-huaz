<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import ChatBot from "@/Components/ChatBot.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    canLogin: { type: Boolean, default: true },
    canRegister: { type: Boolean, default: true },
    posts: { type: Array, default: () => [] },
    venues: { type: Array, default: () => [] },
});

// ── Image Helper & Fallback Handler ──
const resolveImageUrl = (img, fallback = "/images/venue.jpg") => {
    if (!img) return fallback;
    if (typeof img === "string") {
        if (img.includes("XmTOpOEzc8Q71BziIlsEPiIcOn36151otlKZ9b5I")) {
            return "/images/venue.jpg";
        }
        if (img.includes("6pdOrmIILX1jfPKuzGK7Wes1hNF0L0hzLfIWkjBF")) {
            return "/images/venue2.jpg";
        }
        if (img.includes("4wdCZSeyN6xugrJkZucQ8gY32U519AtZoR7gGyOJ")) {
            return "/images/dining.jpg";
        }
        if (img.startsWith("http://") || img.startsWith("https://") || img.startsWith("/")) {
            return img;
        }
    }
    // If it's a relative filename without slash, resolve from root
    return "/" + img;
};

const handleImageError = (event, fallback = "/images/venue.jpg") => {
    if (event && event.target && event.target.src !== fallback) {
        event.target.onerror = null;
        event.target.src = fallback;
    }
};

// ── Mobile Navigation Drawer ──
const mobileMenuOpen = ref(false);

// ── Quick Availability Search Widget ──
const todayStr = new Date().toISOString().split("T")[0];
const searchForm = ref({
    eventType: "",
    date: "",
    guests: "50-100",
    timeSlot: "evening",
});

const eventTypesList = [
    { value: "wedding", label: "Wedding & Reception", icon: "fa-solid fa-ring" },
    { value: "debut", label: "Debut & Birthday Gala", icon: "fa-solid fa-cake-candles" },
    { value: "corporate", label: "Corporate Event / Seminar", icon: "fa-solid fa-briefcase" },
    { value: "reunion", label: "Grand Reunion / Anniversary", icon: "fa-solid fa-champagne-glasses" },
    { value: "party", label: "Private Social Gathering", icon: "fa-solid fa-music" },
    { value: "photoshoot", label: "Prenup & Video Shoot", icon: "fa-solid fa-camera" },
];

const guestRanges = [
    { value: "20-50", label: "20 – 50 Guests (Intimate)" },
    { value: "50-100", label: "50 – 100 Guests (Standard)" },
    { value: "100-250", label: "100 – 250 Guests (Grand Hall)" },
    { value: "250+", label: "250+ Guests (Full Deck Banquet)" },
];

const timeSlots = [
    { value: "morning", label: "Morning Session (8:00 AM – 12:00 PM)" },
    { value: "afternoon", label: "Afternoon Session (1:00 PM – 5:00 PM)" },
    { value: "night", label: "Night Gala (6:00 PM – 10:00 PM)" },
    { value: "fullday", label: "Full Day Exclusive (8:00 AM – 10:00 PM)" },
];

// Quick check handler
const searchedHighlight = ref(false);
const handleQuickSearch = () => {
    searchedHighlight.value = true;
    const target = document.getElementById("venues");
    if (target) {
        target.scrollIntoView({ behavior: "smooth" });
    }
    setTimeout(() => {
        searchedHighlight.value = false;
    }, 3000);
};

// ── Venue Inclusions & Details Modal ──
const showVenueModal = ref(false);
const selectedVenue = ref(null);

const openVenueModal = (venue) => {
    selectedVenue.value = venue;
    showVenueModal.value = true;
};

const closeVenueModal = () => {
    showVenueModal.value = false;
    setTimeout(() => {
        selectedVenue.value = null;
    }, 250);
};

// ── Filtered & Enhanced Venues List ──
const activeCategoryFilter = ref("all");

// Default curated venues if database has not populated yet, or as fallback enhancements
const defaultVenueTemplates = [
    {
        id: "v1",
        title: "The Admiral's Grand Deck",
        category: "grand-deck",
        tag: "Grand Ballroom",
        price: "28,000",
        guests: 250,
        image: "/images/venue.jpg",
        description: "Our premier main deck ballroom featuring panoramic vistas of Talibon bay, theatrical mood lighting, natural ocean breeze ventilation, elevated center stage, and VIP mezzanine lounges.",
        features: ["Panoramic Ocean Views", "Full Audio & Lights", "Center Stage & Podium", "VIP Holding Room", "Generator Backed"],
        seating: "Banquet: 250 pax | Theater: 350 pax | Cocktail: 400 pax",
        availableEvents: [
            { id: 1, name: "Wedding & Reception" },
            { id: 2, name: "Grand Debut Gala" },
            { id: 3, name: "Corporate Gathering" },
            { id: 4, name: "Anniversary Celebration" },
        ],
        inclusions: [
            "Up to 8 hours exclusive venue access",
            "Full professional sound system with 4 wireless mics",
            "Theatrical stage & ambient LED mood wash lights",
            "Round banquet tables with floor-length linens & cushioned chairs",
            "Dedicated standby technical crew & electrical engineer",
            "Bridal / Host private dressing suite with vanity mirror",
            "Complimentary 2 hours rehearsal / ingress setup",
        ],
    },
    {
        id: "v2",
        title: "Sunset Promenade & Skyline Terrace",
        category: "outdoor-terrace",
        tag: "Open-Air Deck",
        price: "18,500",
        guests: 150,
        image: "/images/venue2.jpg",
        description: "An open-air upper ship deck offering breath-taking coastal sunset breezes. The perfect romantic setting for sunset wedding vows, cocktail dinner receptions, and milestone reunions under the stars.",
        features: ["Open-Air Ocean View", "Fairy & String Lighting", "Acoustic Stage Set", "Bar & Buffet Counter", "Tent Provision Ready"],
        seating: "Banquet: 120 pax | Cocktail: 180 pax",
        availableEvents: [
            { id: 1, name: "Sunset Wedding Vows" },
            { id: 2, name: "Grand Reunion & Gala" },
            { id: 3, name: "Cocktail Dinner Party" },
            { id: 4, name: "Milestone Celebration" },
        ],
        inclusions: [
            "Up to 6 hours exclusive terrace use",
            "Warm festoon & romantic fairy lighting setup",
            "Mobile acoustic audio setup with dual mics",
            "Dedicated beverage bar station and buffet staging line",
            "Covered canopy protection on standby for unexpected showers",
            "Complete tables, rustic wooden chairs, and table centerpieces",
        ],
    },
    {
        id: "v3",
        title: "Captain's Executive Function Hall",
        category: "intimate",
        tag: "Conference & Dining",
        price: "12,000",
        guests: 80,
        image: "/images/dining.jpg",
        description: "An executive, enclosed maritime-styled hall engineered for business conferences, corporate seminars, private birthday dinners, and intimate baptisms requiring privacy and premium audio-visual equipment.",
        features: ["High-Lumen Projector", "Conference Microphones", "High-Speed Wi-Fi", "Dedicated Buffet Area", "Refreshing Sea Breeze"],
        seating: "Classroom: 60 pax | Banquet: 80 pax | Theater: 100 pax",
        availableEvents: [
            { id: 1, name: "Corporate Conference & Seminar" },
            { id: 2, name: "Executive Meeting & Workshop" },
            { id: 3, name: "Intimate Birthday & Baptism" },
            { id: 4, name: "Private Social Dinner" },
        ],
        inclusions: [
            "Up to 5 hours exclusive hall use",
            "Laser projector with motorized 120-inch wide projection screen",
            "High-speed fiber Wi-Fi access for presenters and attendees",
            "Individual conference table layout with ergonomic chairs",
            "Refreshing coastal sea breeze and natural ventilation",
            "Coffee and water dispenser station on standby",
        ],
    },
];

// Display venues: Deduplicate by physical venue title & aggregate available events
const displayVenues = computed(() => {
    if (props.venues && props.venues.length > 0) {
        const groups = new Map();

        props.venues.forEach((v) => {
            const rawTitle = (v.title || v.name || "").trim();
            if (!rawTitle) return;
            const key = rawTitle.toLowerCase();

            if (!groups.has(key)) {
                groups.set(key, {
                    title: rawTitle,
                    primary: v,
                    items: [],
                });
            }
            groups.get(key).items.push(v);
        });

        const list = [];
        let index = 0;

        for (const [key, group] of groups.entries()) {
            const primary = group.primary;
            const items = group.items;
            const fallback = defaultVenueTemplates[index % defaultVenueTemplates.length];

            // Extract all unique events available for this venue deck
            const availableEvents = [];
            const seenEvents = new Set();

            items.forEach((p) => {
                const eventName = p.eventType?.type || p.eventType?.name || p.event_type?.type || p.event_type?.name;
                if (eventName && !seenEvents.has(eventName.toLowerCase())) {
                    seenEvents.add(eventName.toLowerCase());
                    availableEvents.push({
                        id: p.id,
                        name: eventName,
                        price: p.price,
                        price_morning: p.price_morning,
                        price_afternoon: p.price_afternoon,
                        price_night: p.price_night,
                        price_fullday: p.price_fullday,
                    });
                }
            });

            // If no specific event in DB, fallback to curated events
            const finalEvents = availableEvents.length > 0 ? availableEvents : (fallback.availableEvents || []);

            // Lowest base starting price across all configurations of this venue
            const prices = items
                .map((p) => Number(p.price))
                .filter((pr) => !isNaN(pr) && pr > 0);
            const lowestPrice = prices.length > 0 ? Math.min(...prices) : Number(primary.price || fallback.price);

            // Max capacity across configurations
            const guestCaps = items
                .map((p) => Number(p.guests))
                .filter((g) => !isNaN(g) && g > 0);
            const maxGuests = guestCaps.length > 0 ? Math.max(...guestCaps) : (primary.guests || fallback.guests);

            // Categorize by capacity & deck keyword
            let category = "grand-deck";
            const titleLower = group.title.toLowerCase();
            if (maxGuests <= 80 || titleLower.includes("executive") || titleLower.includes("function") || titleLower.includes("hall")) {
                category = "intimate";
            } else if (titleLower.includes("promenade") || titleLower.includes("terrace") || titleLower.includes("sunset") || titleLower.includes("open")) {
                category = "outdoor-terrace";
            }

            const tag = maxGuests > 180 ? "Grand Ballroom" : maxGuests > 90 ? "Open-Air Deck" : "Conference & Dining";

            list.push({
                id: primary.id,
                title: group.title,
                category: category,
                tag: tag,
                price: lowestPrice,
                guests: maxGuests,
                image: resolveImageUrl(primary.image, fallback.image),
                description: primary.description || fallback.description,
                features: fallback.features,
                seating: fallback.seating,
                inclusions: fallback.inclusions,
                availableEvents: finalEvents,
                packages: items,
            });

            index++;
        }

        return list.length > 0 ? list : defaultVenueTemplates;
    }

    return defaultVenueTemplates;
});

const filteredVenues = computed(() => {
    if (activeCategoryFilter.value === "all") return displayVenues.value;
    return displayVenues.value.filter((v) => v.category === activeCategoryFilter.value);
});

// ── FAQ Accordion ──
const faqs = ref([
    {
        q: "How early in advance should we reserve our event date?",
        a: "We recommend reserving at least 2 to 6 months in advance for peak wedding and holiday seasons (October to February, and May to June). However, we accommodate short-notice bookings as long as your preferred date and time slot are open.",
        open: true,
    },
    {
        q: "Do you allow outside catering and styling suppliers?",
        a: "Yes! While we offer in-house banquet catering packages, we welcome your chosen outside caterers and event stylists. We provide a designated catering prep kitchen area and clear ingress guidelines with zero or minimal corkage fees depending on your package.",
        open: false,
    },
    {
        q: "How many hours of setup / ingress are granted prior to the event?",
        a: "Standard packages include 2 complimentary hours before the event for ingress (styling, cake delivery, sound testing) and 1 hour after the event for egress. Extended setup hours can be coordinated with our reservation manager.",
        open: false,
    },
    {
        q: "What are the payment and reservation terms?",
        a: "A 50% reservation downpayment secures your date on our official booking calendar. The remaining balance can be settled on or before the event date via Cash, GCash, or Bank Transfer. Digital receipts are automatically generated.",
        open: false,
    },
    {
        q: "Do you have a power generator in case of utility outages?",
        a: "Yes! Butal Ship Hauz is equipped with a heavy-duty commercial automatic backup generator capable of powering all venue lighting, pro sound systems, and full stage equipment seamlessly without event interruption.",
        open: false,
    },
    {
        q: "Can we schedule an on-site ocular inspection before booking?",
        a: "Definitely! Our event coordinators are available daily from 8:00 AM to 5:00 PM to give you a guided walk-through of our ship decks, dressing suites, and technical facilities.",
        open: false,
    },
]);

const toggleFaq = (index) => {
    faqs.value[index].open = !faqs.value[index].open;
};

// ── Occasions Catered Showcase ──
const occasions = [
    {
        title: "Weddings & Receptions",
        tag: "Romance at Sea",
        desc: "Exchange vows with the Bohol sea horizon as your backdrop, followed by a grand banquet on the Admiral Deck.",
        image: "/images/venue.jpg",
        icon: "fa-solid fa-ring",
        pax: "Up to 350 Pax",
    },
    {
        title: "Debuts & Sweet 16s",
        tag: "Grand Celebrations",
        desc: "A magical red-carpet entrance down the ship's grand staircase with dynamic mood lighting and spacious dance floor.",
        image: "/images/venue2.jpg",
        icon: "fa-solid fa-cake-candles",
        pax: "100 – 250 Pax",
    },
    {
        title: "Corporate Seminars & Galas",
        tag: "Executive Functions",
        desc: "State-of-the-art projection, high-fidelity microphones, and comfortable table layouts for corporate success.",
        image: "/images/dining.jpg",
        icon: "fa-solid fa-briefcase",
        pax: "50 – 150 Pax",
    },
    {
        title: "Grand Reunions & Anniversaries",
        tag: "Unforgettable Milestones",
        desc: "Celebrate family milestones, alumni homecomings, and golden anniversaries with delicious catering and photo spots.",
        image: "/images/stay.jpg",
        icon: "fa-solid fa-champagne-glasses",
        pax: "80 – 300 Pax",
    },
];

// ── Booking Steps ──
const bookingSteps = [
    {
        step: "01",
        title: "Explore Decks & Packages",
        desc: "Browse our signature ship halls, check seating capacities, and preview complete venue inclusions.",
        icon: "fa-solid fa-magnifying-glass",
    },
    {
        step: "02",
        title: "Select Date & Customize",
        desc: "Lock in your preferred date and time slot. Choose add-ons like sound systems, projectors, or catering space.",
        icon: "fa-solid fa-calendar-check",
    },
    {
        step: "03",
        title: "Instant Confirmation",
        desc: "Secure your reservation with flexible payment channels (GCash, Bank, Cash) and get an automated receipt.",
        icon: "fa-solid fa-shield-halved",
    },
];

// ── Key Venue Perks ──
const venuePerks = [
    {
        icon: "fa-solid fa-ship",
        title: "One-of-a-Kind Ship Architecture",
        desc: "An architectural landmark in Talibon, Bohol offering iconic photo backdrops that guests will talk about for years.",
    },
    {
        icon: "fa-solid fa-wind",
        title: "Refreshing Ocean Breeze & Bay Views",
        desc: "Experience scenic coastal trade winds and panoramic sea vistas, creating a naturally cool and breath-taking maritime ambience.",
    },
    {
        icon: "fa-solid fa-bolt",
        title: "100% Backup Power Generator",
        desc: "Zero worries about local brownouts. Our heavy-duty standby generator keeps lights, sound, and stage equipment running uninterrupted.",
    },
    {
        icon: "fa-solid fa-square-parking",
        title: "Spacious Free Guest Parking",
        desc: "Ample, well-lit parking spaces accommodating up to 50+ vehicles, vans, and buses with security assistance.",
    },
    {
        icon: "fa-solid fa-music",
        title: "Pro Audio & Stage Equipment",
        desc: "High-clarity speaker arrays, wireless microphones, and versatile LED wash lighting ready for your band, DJ, or host.",
    },
    {
        icon: "fa-solid fa-handshake",
        title: "Dedicated On-Site Crew",
        desc: "Friendly, experienced venue marshals, electrical technicians, and coordinators on standby throughout your event.",
    },
];

// ── Official Facebook & Article Direct Navigation Helper ──
const OFFICIAL_FACEBOOK_URL = "https://www.facebook.com/share/1KawkEhDiH/";

const getArticleLink = (post) => {
    if (!post) return OFFICIAL_FACEBOOK_URL;
    const raw = (post.link || "").trim();
    if (raw) {
        if (/^https?:\/\//i.test(raw)) {
            return raw;
        }
        return "https://" + raw;
    }
    return OFFICIAL_FACEBOOK_URL;
};

// ── Sticky Booking Pill on Scroll ──
const isScrolledPastHero = ref(false);
const handleScroll = () => {
    isScrolledPastHero.value = window.scrollY > 550;
};

onMounted(() => {
    window.addEventListener("scroll", handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener("scroll", handleScroll);
});

// ── Contact Information ──
const contactInfo = ref([
    {
        label: "Venue Location",
        value: "Butal Ship Hauz, San Roque, Talibon, Bohol, Philippines",
        icon: "fa-solid fa-location-dot",
    },
    {
        label: "Reservations Hotline",
        value: "+63 912 345 6789 / (038) 510 1234",
        icon: "fa-solid fa-phone",
    },
    {
        label: "Events & Bookings Email",
        value: "reservations@butalshiphauz.com.ph",
        icon: "fa-solid fa-envelope",
    },
    {
        label: "Ocular & Office Hours",
        value: "Daily: 8:00 AM – 6:00 PM (Ocular visits welcome)",
        icon: "fa-solid fa-clock",
    },
]);

const footerCols = ref([
    {
        heading: "Venue & Spaces",
        links: [
            { label: "Grand Ballroom Deck", href: "#venues" },
            { label: "Sunset Promenade", href: "#venues" },
            { label: "Captain's Function Hall", href: "#venues" },
            { label: "Venue Pricing & Rates", href: "#venues" },
            { label: "Technical Inclusions", href: "#amenities" },
        ],
    },
    {
        heading: "Planning Your Event",
        links: [
            { label: "How to Book", href: "#how-it-works" },
            { label: "Occasions & Celebrations", href: "#occasions" },
            { label: "Frequently Asked Questions", href: "#faq" },
            { label: "Schedule an Ocular Visit", href: "#contact" },
            { label: "News & Stories", href: "#blog" },
        ],
    },
    {
        heading: "Policies & Terms",
        links: [
            { label: "Reservation & Deposit Policy", href: "#faq" },
            { label: "Ingress & Egress Guidelines", href: "#faq" },
            { label: "Catering & Sound Guidelines", href: "#faq" },
            { label: "Terms of Service", href: "#" },
            { label: "Privacy Policy", href: "#" },
        ],
    },
]);

const socials = ref([
    {
        label: "Facebook",
        url: "https://www.facebook.com/share/1KawkEhDiH/",
        icon: '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
    },
    {
        label: "Instagram",
        url: "https://www.instagram.com/",
        icon: '<rect x="2" y="2" width="20" height="20" rx="5" ry="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1"/>',
    },
    {
        label: "TikTok",
        url: "https://www.tiktok.com/",
        icon: '<path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.18 8.18 0 0 0 4.79 1.53V6.77a4.85 4.85 0 0 1-1.02-.08z"/>',
    },
]);
</script>

<template>
    <Head title="Butal Ship Hauz — Premier Venue Booking in Talibon, Bohol" />

    <div class="min-h-screen bg-white text-slate-900 antialiased overflow-x-hidden font-body flex flex-col selection:bg-orange-500 selection:text-white">
        
        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 1. TOP ANNOUNCEMENT & CLEAN CRISP NAVIGATION           -->
        <!-- ══════════════════════════════════════════════════════ -->
        <header class="fixed top-0 left-0 right-0 z-40">
            <!-- Top Announcement Bar (High contrast) -->
            <div class="bg-sky-900 text-sky-100 text-xs py-2 px-4 text-center border-b border-sky-800 hidden sm:block">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <span class="inline-flex items-center gap-2 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <strong class="text-white font-bold">Accepting Bookings for 2026 & 2027</strong> — Reserve your event date with our flexible 50% downpayment!
                    </span>
                    <a href="#contact" class="text-amber-300 hover:text-white underline font-bold transition-colors">
                        Book an Ocular Visit &rarr;
                    </a>
                </div>
            </div>

            <!-- Main Navbar (Clean White & Slate) -->
            <nav class="bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm transition-all duration-300">
                <div class="max-w-7xl mx-auto flex items-center justify-between px-6 md:px-8 h-20">
                    <!-- Logo -->
                    <a href="#home" class="flex items-center gap-3.5 no-underline flex-shrink-0 group">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center shadow-md shadow-orange-500/20 group-hover:scale-105 transition-transform duration-300">
                            <font-awesome-icon icon="fa-solid fa-ship" class="text-white text-xl" />
                        </div>
                        <div>
                            <div class="font-display text-slate-900 text-xl font-bold leading-tight tracking-tight flex items-center gap-2">
                                Butal Ship Hauz
                                <span class="bg-orange-100 text-orange-700 text-[10px] font-mono px-2 py-0.5 rounded-full uppercase tracking-wider font-bold">Venue</span>
                            </div>
                            <div class="font-mono text-orange-600 text-[11px] font-bold tracking-[.18em] uppercase leading-none mt-1 flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-location-dot" class="text-[9px]" /> Talibon, Bohol
                            </div>
                        </div>
                    </a>

                    <!-- Desktop Nav Links -->
                    <ul class="hidden lg:flex items-center gap-7 list-none m-0 p-0">
                        <li>
                            <a href="#venues" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                Venue Spaces
                            </a>
                        </li>
                        <li>
                            <a href="#how-it-works" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                How to Book
                            </a>
                        </li>
                        <li>
                            <a href="#occasions" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                Occasions
                            </a>
                        </li>
                        <li>
                            <a href="#amenities" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                Inclusions & Perks
                            </a>
                        </li>
                        <li>
                            <a href="#faq" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                FAQs
                            </a>
                        </li>
                        <li>
                            <a href="#contact" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                Contact & Map
                            </a>
                        </li>
                    </ul>

                    <!-- Auth / Action Buttons -->
                    <div class="hidden sm:flex items-center gap-3">
                        <template v-if="$page.props.auth.user">
                            <Link 
                                :href="$page.props.auth.user.role === 'admin' ? route('admin.dashboard') : route('client.home')" 
                                class="inline-flex items-center gap-2 text-sm font-bold text-slate-800 bg-slate-100 hover:bg-slate-200 px-5 py-2.5 rounded-full transition-all duration-200 border border-slate-300"
                            >
                                <font-awesome-icon icon="fa-solid fa-gauge-high" class="text-orange-500" />
                                {{ $page.props.auth.user.role === 'admin' ? 'Admin Portal' : 'My Dashboard' }}
                            </Link>
                            <Link 
                                v-if="$page.props.auth.user.role === 'client'"
                                :href="route('client.booking.index')" 
                                class="inline-flex items-center gap-2 text-sm font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 px-5 py-2.5 rounded-full transition-all duration-200 shadow-md shadow-orange-500/20 hover:-translate-y-0.5"
                            >
                                <font-awesome-icon icon="fa-solid fa-calendar-check" /> Book Now
                            </Link>
                        </template>
                        <template v-else-if="canLogin">
                            <Link :href="route('login')" class="text-sm font-bold text-slate-700 hover:text-orange-600 px-3 py-2 transition-colors">
                                Log in
                            </Link>
                            <Link 
                                v-if="canRegister" 
                                :href="route('register')" 
                                class="inline-flex items-center gap-2 text-sm font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 px-5 py-2.5 rounded-full transition-all duration-200 shadow-md shadow-orange-500/25 hover:-translate-y-0.5"
                            >
                                <font-awesome-icon icon="fa-solid fa-calendar-check" /> Reserve Venue
                            </Link>
                        </template>
                    </div>

                    <!-- Mobile Hamburger Toggle -->
                    <div class="flex lg:hidden items-center gap-2">
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="w-11 h-11 flex flex-col items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-slate-100 text-slate-800 hover:bg-slate-200 transition-colors"
                            aria-label="Toggle navigation menu"
                        >
                            <span class="w-5 h-0.5 bg-slate-800 transition-all" :class="{ 'rotate-45 translate-y-2': mobileMenuOpen }"></span>
                            <span class="w-5 h-0.5 bg-slate-800 transition-all" :class="{ 'opacity-0': mobileMenuOpen }"></span>
                            <span class="w-5 h-0.5 bg-slate-800 transition-all" :class="{ '-rotate-45 -translate-y-2': mobileMenuOpen }"></span>
                        </button>
                    </div>
                </div>

                <!-- Mobile Navigation Drawer -->
                <div 
                    class="lg:hidden overflow-hidden transition-all duration-300 ease-in-out bg-white border-t border-slate-200"
                    :style="mobileMenuOpen ? 'max-height: 480px;' : 'max-height: 0px;'"
                >
                    <div class="px-6 py-6 space-y-3">
                        <a 
                            v-for="nav in [
                                { label: 'Venue Spaces & Rates', href: '#venues', icon: 'fa-solid fa-ship' },
                                { label: 'How to Book', href: '#how-it-works', icon: 'fa-solid fa-calendar-check' },
                                { label: 'Occasions Catered', href: '#occasions', icon: 'fa-solid fa-champagne-glasses' },
                                { label: 'Inclusions & Perks', href: '#amenities', icon: 'fa-solid fa-star' },
                                { label: 'Frequently Asked Questions', href: '#faq', icon: 'fa-solid fa-circle-question' },
                                { label: 'Contact & Ocular Location', href: '#contact', icon: 'fa-solid fa-location-dot' },
                            ]"
                            :key="nav.label"
                            :href="nav.href"
                            @click="mobileMenuOpen = false"
                            class="flex items-center gap-3 text-slate-800 hover:text-orange-600 font-bold text-sm py-2.5 px-3 rounded-lg hover:bg-slate-50 transition-colors"
                        >
                            <font-awesome-icon :icon="nav.icon" class="text-orange-500 w-4" />
                            {{ nav.label }}
                        </a>

                        <div class="pt-4 border-t border-slate-200 flex flex-col gap-2.5">
                            <template v-if="$page.props.auth.user">
                                <Link 
                                    :href="$page.props.auth.user.role === 'admin' ? route('admin.dashboard') : route('client.booking.index')"
                                    class="w-full text-center bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition-colors shadow-md"
                                >
                                    Proceed to Bookings
                                </Link>
                            </template>
                            <template v-else>
                                <Link :href="route('login')" class="w-full text-center bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold py-3 rounded-xl border border-slate-300 transition-colors">
                                    Log in
                                </Link>
                                <Link :href="route('register')" class="w-full text-center bg-orange-500 hover:bg-orange-600 text-white font-bold py-3 rounded-xl transition-colors shadow-md">
                                    Create Account to Book
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>
            </nav>
        </header>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 2. HERO SECTION WITH CLEAR, COMFORTABLE CONTRAST       -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section id="home" class="relative pt-36 pb-20 lg:pt-44 lg:pb-32 overflow-hidden bg-sky-900 bg-cover bg-center bg-no-repeat" style="background-image: url('/images/landmark.jpg');">
            <!-- Clear & balanced overlay: not too dark, rich color depth -->
            <div class="absolute inset-0 bg-gradient-to-b from-sky-950/75 via-sky-900/65 to-sky-950/85 z-0"></div>

            <div class="relative z-10 max-w-7xl mx-auto px-6 md:px-8">
                <!-- Hero Heading & Trust Badge -->
                <div class="text-center max-w-4xl mx-auto mb-10">
                    <div class="inline-flex items-center gap-2.5 px-4 py-1.5 rounded-full bg-white/20 border border-white/30 backdrop-blur-md mb-6 shadow-sm">
                        <span class="w-2.5 h-2.5 rounded-full bg-lime-400 animate-ping"></span>
                        <span class="font-mono text-xs font-bold tracking-widest uppercase text-white drop-shadow-sm">Bohol's Premier Ship Landmark</span>
                    </div>

                    <h1 class="font-display text-white font-black leading-[1.08] text-4xl sm:text-6xl md:text-7xl mb-6 tracking-tight drop-shadow-md">
                        Celebrate Milestones Aboard Bohol's Most <span class="text-amber-300 italic">Iconic Landmark</span>
                    </h1>

                    <p class="font-body text-white font-medium text-lg sm:text-xl leading-relaxed max-w-2xl mx-auto drop-shadow">
                        From romantic sunset deck weddings to grand debutante balls and corporate galas. Reserve your dates with all-inclusive sound, stage, and coastal views in Talibon, Bohol.
                    </p>
                </div>

                <!-- ─── BRIGHT, CRISP QUICK-RESERVE / AVAILABILITY FINDER ─── -->
                <div class="max-w-5xl mx-auto bg-white rounded-3xl p-6 sm:p-8 shadow-2xl border border-slate-200">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-5 border-b border-slate-200">
                        <div class="flex items-center gap-2.5 text-slate-900 font-black text-base sm:text-lg">
                            <div class="w-9 h-9 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center">
                                <font-awesome-icon icon="fa-solid fa-calendar-days" />
                            </div>
                            <span>Check Available Spaces & Calculate Rates</span>
                        </div>
                        <div class="flex items-center gap-4 text-xs font-bold text-slate-600">
                            <span class="flex items-center gap-1.5 text-emerald-700">
                                <font-awesome-icon icon="fa-solid fa-circle-check" /> Instant Date Check
                            </span>
                            <span class="flex items-center gap-1.5 text-sky-800">
                                <font-awesome-icon icon="fa-solid fa-shield-halved" /> 50% Downpayment
                            </span>
                        </div>
                    </div>

                    <!-- Quick Search Form Fields (Pure white inputs, dark crisp text) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- 1. Occasion / Event Type -->
                        <div class="flex flex-col text-left">
                            <label class="font-mono text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5 flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-ring" class="text-orange-500 text-xs" /> Event Occasion
                            </label>
                            <select 
                                v-model="searchForm.eventType"
                                class="w-full bg-white border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-3 text-slate-900 text-sm font-bold focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all cursor-pointer shadow-sm"
                            >
                                <option value="">Any Occasion / General</option>
                                <option v-for="type in eventTypesList" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>

                        <!-- 2. Target Date -->
                        <div class="flex flex-col text-left">
                            <label class="font-mono text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5 flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-calendar-day" class="text-orange-500 text-xs" /> Preferred Date
                            </label>
                            <input 
                                type="date" 
                                v-model="searchForm.date"
                                :min="todayStr"
                                class="w-full bg-white border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-2.5 text-slate-900 text-sm font-bold focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all cursor-pointer shadow-sm"
                            />
                        </div>

                        <!-- 3. Estimated Guests -->
                        <div class="flex flex-col text-left">
                            <label class="font-mono text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5 flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-users" class="text-orange-500 text-xs" /> Guest Count
                            </label>
                            <select 
                                v-model="searchForm.guests"
                                class="w-full bg-white border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-3 text-slate-900 text-sm font-bold focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all cursor-pointer shadow-sm"
                            >
                                <option v-for="range in guestRanges" :key="range.value" :value="range.value">
                                    {{ range.label }}
                                </option>
                            </select>
                        </div>

                        <!-- 4. Time Slot -->
                        <div class="flex flex-col text-left">
                            <label class="font-mono text-xs font-bold uppercase tracking-wider text-slate-800 mb-1.5 flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-clock" class="text-orange-500 text-xs" /> Time Session
                            </label>
                            <select 
                                v-model="searchForm.timeSlot"
                                class="w-full bg-white border-2 border-slate-200 hover:border-slate-300 rounded-xl px-3.5 py-3 text-slate-900 text-sm font-bold focus:ring-2 focus:ring-orange-500 focus:border-orange-500 transition-all cursor-pointer shadow-sm"
                            >
                                <option v-for="slot in timeSlots" :key="slot.value" :value="slot.value">
                                    {{ slot.label }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Search CTA Button & Quick Action -->
                    <div class="mt-6 pt-5 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="text-xs text-slate-600 font-medium text-center sm:text-left">
                            💡 Need an ocular tour or customized setup? Call reservations at <strong class="text-slate-900 font-bold">+63 912 345 6789</strong>
                        </div>
                        <button 
                            @click="handleQuickSearch"
                            type="button"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black text-sm px-8 py-3.5 rounded-xl transition-all duration-200 shadow-md shadow-orange-500/25 hover:-translate-y-0.5 active:translate-y-0"
                        >
                            <font-awesome-icon icon="fa-solid fa-magnifying-glass" />
                            <span>Check Available Spaces</span>
                        </button>
                    </div>
                </div>

                <!-- ─── Trust Metrics Strip (Crisp White & Amber) ─── -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-4xl mx-auto mt-14 pt-8 border-t border-white/20 text-white">
                    <div class="flex flex-col items-center text-center">
                        <span class="font-display font-black text-3xl sm:text-4xl text-amber-300 drop-shadow">350+</span>
                        <span class="text-xs text-white font-bold uppercase tracking-wider mt-1 drop-shadow">Max Guest Capacity</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <span class="font-display font-black text-3xl sm:text-4xl text-amber-300 drop-shadow">500+</span>
                        <span class="text-xs text-white font-bold uppercase tracking-wider mt-1 drop-shadow">Events Hosted</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <span class="font-display font-black text-3xl sm:text-4xl text-amber-300 drop-shadow">100%</span>
                        <span class="text-xs text-white font-bold uppercase tracking-wider mt-1 drop-shadow">Generator Backed</span>
                    </div>
                    <div class="flex flex-col items-center text-center">
                        <span class="font-display font-black text-3xl sm:text-4xl text-amber-300 drop-shadow">4.9 ★</span>
                        <span class="text-xs text-white font-bold uppercase tracking-wider mt-1 drop-shadow">Client Satisfaction</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 3. FEATURED VENUE SPACES & PACKAGES                    -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section id="venues" class="py-24 sm:py-32 bg-slate-50 scroll-mt-20 relative">
            <div class="max-w-7xl mx-auto px-6 md:px-8">
                
                <!-- Section Header -->
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-14">
                    <div>
                        <div class="inline-flex items-center gap-2 mb-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                            <span class="font-mono text-xs font-bold uppercase tracking-[.2em] text-orange-600">Event Spaces & Packages</span>
                        </div>
                        <h2 class="font-display text-slate-900 font-black text-3xl sm:text-5xl tracking-tight leading-tight">
                            Choose Your Signature Deck
                        </h2>
                        <p class="font-body text-slate-600 text-base sm:text-lg mt-3 max-w-xl font-medium">
                            Each deck and function hall offers a distinct ambiance, complete lighting setup, stage, and dedicated service crew.
                        </p>
                    </div>

                    <!-- Category Filter Tabs -->
                    <div class="flex flex-wrap gap-2 bg-white p-1.5 rounded-2xl border border-slate-200 shadow-sm">
                        <button
                            v-for="tab in [
                                { key: 'all', label: 'All Spaces' },
                                { key: 'grand-deck', label: 'Grand Ballrooms' },
                                { key: 'outdoor-terrace', label: 'Sunset Terraces' },
                                { key: 'intimate', label: 'Function Halls' },
                            ]"
                            :key="tab.key"
                            @click="activeCategoryFilter = tab.key"
                            :class="[
                                activeCategoryFilter === tab.key
                                    ? 'bg-slate-900 text-white shadow-sm'
                                    : 'text-slate-700 hover:text-slate-900 hover:bg-slate-100',
                                'px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition-all'
                            ]"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </div>

                <!-- Venue Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div 
                        v-for="venue in filteredVenues" 
                        :key="venue.id" 
                        :class="[
                            'group bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col',
                            searchedHighlight ? 'ring-2 ring-orange-500 ring-offset-4' : ''
                        ]"
                    >
                        <!-- Venue Image Banner with Fallback Error Handler -->
                        <div class="relative h-64 overflow-hidden bg-slate-100 flex-shrink-0">
                            <img 
                                :src="resolveImageUrl(venue.image, '/images/venue.jpg')" 
                                :alt="venue.title" 
                                @error="handleImageError($event, '/images/venue.jpg')"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105" 
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-black/20"></div>

                            <!-- Venue Tag -->
                            <div class="absolute top-4 left-4">
                                <span class="bg-white text-slate-900 font-mono text-[11px] font-bold px-3 py-1.5 rounded-lg shadow uppercase tracking-wider">
                                    {{ venue.tag }}
                                </span>
                            </div>

                            <!-- Capacity Badge -->
                            <div class="absolute top-4 right-4">
                                <span class="bg-sky-900 text-white font-body text-xs font-bold px-3 py-1.5 rounded-lg shadow flex items-center gap-1.5">
                                    <font-awesome-icon icon="fa-solid fa-users" class="text-amber-300" />
                                    Up to {{ venue.guests }} Pax
                                </span>
                            </div>

                            <!-- Price Tag on Image -->
                            <div class="absolute bottom-4 left-4 right-4 flex items-end justify-between text-white">
                                <div>
                                    <span class="text-[11px] font-mono uppercase tracking-wider text-amber-300 font-bold block drop-shadow">Package Rate Starts At</span>
                                    <div class="text-2xl sm:text-3xl font-black font-display text-white drop-shadow-md">
                                        ₱{{ Number(venue.price).toLocaleString() }}
                                        <span class="text-xs font-bold text-slate-200 font-body">/ slot</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-6 sm:p-7 flex flex-col flex-1">
                            <h3 class="font-display text-slate-900 font-black text-2xl mb-2.5 group-hover:text-orange-600 transition-colors">
                                {{ venue.title }}
                            </h3>

                            <p class="font-body text-slate-600 text-sm leading-relaxed mb-4 line-clamp-2 font-medium">
                                {{ venue.description }}
                            </p>

                            <!-- Available Events Badges -->
                            <div v-if="venue.availableEvents && venue.availableEvents.length" class="mb-4 pt-3 border-t border-slate-100">
                                <span class="font-mono text-[11px] uppercase tracking-wider text-slate-700 font-bold block mb-2">
                                    Available for Events:
                                </span>
                                <div class="flex flex-wrap gap-1.5">
                                    <span 
                                        v-for="evt in venue.availableEvents" 
                                        :key="evt.id"
                                        class="inline-flex items-center gap-1.5 text-xs font-bold bg-orange-50 text-orange-950 border border-orange-200/90 px-2.5 py-1 rounded-lg shadow-2xs"
                                    >
                                        <span class="text-orange-500 text-[11px]">🎉</span>
                                        <span>{{ evt.name }}</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Key Amenities Pills -->
                            <div class="space-y-2 mb-6 pt-3 border-t border-slate-100 flex-1">
                                <span class="font-mono text-[11px] uppercase tracking-wider text-slate-700 font-bold block">Included Highlights:</span>
                                <div class="flex flex-wrap gap-1.5">
                                    <span 
                                        v-for="feat in (venue.features || ['Scenic Ocean View', 'Stage Ready', 'Pro Sound'])" 
                                        :key="feat"
                                        class="inline-flex items-center gap-1 text-xs font-semibold bg-sky-50 text-sky-900 border border-sky-100 px-2.5 py-1 rounded-md"
                                    >
                                        <font-awesome-icon icon="fa-solid fa-check" class="text-emerald-600 text-[10px]" />
                                        {{ feat }}
                                    </span>
                                </div>
                            </div>

                            <!-- Dual Action Buttons -->
                            <div class="pt-4 border-t border-slate-100 flex flex-col gap-2.5">
                                <!-- Book Button -->
                                <Link 
                                    v-if="$page.props.auth.user && $page.props.auth.user.role === 'client'"
                                    :href="route('client.booking.index', { venue_id: venue.id })"
                                    class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black text-sm px-4 py-3 rounded-xl transition-all shadow-md shadow-orange-500/20 hover:-translate-y-0.5"
                                >
                                    <font-awesome-icon icon="fa-solid fa-calendar-check" />
                                    Book This Space
                                </Link>
                                
                                <Link 
                                    v-else-if="$page.props.auth.user && $page.props.auth.user.role === 'admin'"
                                    :href="route('admin.venue-packages.index')"
                                    class="w-full inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-sm px-4 py-3 rounded-xl transition-colors border border-slate-200"
                                >
                                    <font-awesome-icon icon="fa-solid fa-pen-to-square" />
                                    Edit Space in Admin
                                </Link>

                                <Link 
                                    v-else
                                    :href="route('register')"
                                    class="w-full inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-sm px-4 py-3 rounded-xl transition-all shadow hover:-translate-y-0.5"
                                >
                                    <font-awesome-icon icon="fa-solid fa-calendar-check" />
                                    Reserve / Book Date
                                </Link>

                                <!-- View Details & Inclusions Modal Trigger -->
                                <button 
                                    @click="openVenueModal(venue)"
                                    type="button"
                                    class="w-full inline-flex items-center justify-center gap-1.5 text-slate-700 hover:text-orange-600 font-bold text-xs py-2 transition-colors"
                                >
                                    <font-awesome-icon icon="fa-solid fa-circle-info" />
                                    View Full Inclusions & Layout
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Banquet Inquiries Box -->
                <div class="mt-16 bg-gradient-to-r from-sky-900 via-sky-950 to-indigo-950 rounded-3xl p-8 sm:p-12 text-white shadow-xl flex flex-col md:flex-row items-center justify-between gap-8">
                    <div class="space-y-2 text-center md:text-left">
                        <span class="font-mono text-xs font-bold uppercase tracking-widest text-amber-300">Custom Event Layouts</span>
                        <h3 class="font-display text-2xl sm:text-3xl font-black">Planning a Grand Wedding or Multi-Deck Festival?</h3>
                        <p class="text-sky-100 text-sm max-w-xl font-medium">
                            We can combine indoor banquet halls and upper open-air sunset terraces to accommodate up to 500+ guests with whole-day exclusive access.
                        </p>
                    </div>
                    <a 
                        href="#contact" 
                        class="inline-flex items-center justify-center gap-2 bg-white hover:bg-slate-100 text-slate-900 font-black text-sm px-8 py-4 rounded-xl transition-all shadow-lg hover:-translate-y-0.5 whitespace-nowrap"
                    >
                        Inquire Custom Package <font-awesome-icon icon="fa-solid fa-arrow-right" class="text-xs text-orange-500" />
                    </a>
                </div>

            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 4. HOW BOOKING WORKS                                   -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section id="how-it-works" class="py-24 bg-white border-y border-slate-200 scroll-mt-20">
            <div class="max-w-7xl mx-auto px-6 md:px-8">
                
                <div class="text-center max-w-2xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                        <span class="font-mono text-xs font-bold uppercase tracking-[.2em] text-orange-600">Simple Reservation</span>
                    </div>
                    <h2 class="font-display text-slate-900 font-black text-3xl sm:text-5xl tracking-tight leading-tight">
                        How Booking Your Venue Works
                    </h2>
                    <p class="font-body text-slate-600 text-base mt-3 font-medium">
                        We made securing your special date completely hassle-free with transparent rates and automated digital confirmations.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                    <div 
                        v-for="(step, idx) in bookingSteps" 
                        :key="step.step" 
                        class="bg-slate-50 rounded-3xl p-8 border border-slate-200 shadow-sm relative flex flex-col group hover:bg-white hover:shadow-xl transition-all duration-300"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <span class="font-display font-black text-5xl text-orange-500 group-hover:scale-110 transition-transform">
                                {{ step.step }}
                            </span>
                            <div class="w-14 h-14 rounded-2xl bg-slate-900 text-white flex items-center justify-center text-xl shadow-md">
                                <font-awesome-icon :icon="step.icon" />
                            </div>
                        </div>

                        <h3 class="font-display font-black text-xl text-slate-900 mb-3">
                            {{ step.title }}
                        </h3>

                        <p class="font-body text-slate-600 text-sm leading-relaxed flex-1 font-medium">
                            {{ step.desc }}
                        </p>
                    </div>
                </div>

            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 5. OCCASIONS CATERED SHOWCASE                         -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section id="occasions" class="py-24 sm:py-32 bg-slate-50 scroll-mt-20">
            <div class="max-w-7xl mx-auto px-6 md:px-8">
                
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-16">
                    <div>
                        <div class="inline-flex items-center gap-2 mb-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                            <span class="font-mono text-xs font-bold uppercase tracking-[.2em] text-orange-600">Events We Host</span>
                        </div>
                        <h2 class="font-display text-slate-900 font-black text-3xl sm:text-5xl tracking-tight leading-tight">
                            Tailored for Every Life Milestone
                        </h2>
                    </div>
                    <p class="font-body text-slate-600 text-base max-w-md font-medium">
                        Whether celebrating a fairy-tale wedding, an executive workshop, or an intimate birthday party, our spaces elevate every moment.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div 
                        v-for="occ in occasions" 
                        :key="occ.title" 
                        class="group relative rounded-3xl overflow-hidden bg-slate-900 shadow-md hover:shadow-2xl transition-all duration-300 flex flex-col h-96"
                    >
                        <img 
                            :src="occ.image" 
                            :alt="occ.title" 
                            class="absolute inset-0 w-full h-full object-cover opacity-70 group-hover:opacity-50 group-hover:scale-110 transition-all duration-700" 
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/40 to-transparent"></div>

                        <div class="relative z-10 p-6 flex flex-col h-full justify-between text-white">
                            <div>
                                <span class="inline-flex items-center gap-1.5 font-mono text-[11px] uppercase font-black tracking-widest bg-orange-500 text-white px-3 py-1 rounded-full mb-3 shadow">
                                    <font-awesome-icon :icon="occ.icon" /> {{ occ.tag }}
                                </span>
                            </div>

                            <div>
                                <div class="font-mono text-xs font-bold text-amber-300 uppercase tracking-wider mb-1 drop-shadow">
                                    {{ occ.pax }}
                                </div>
                                <h3 class="font-display text-2xl font-black leading-tight mb-2 drop-shadow">
                                    {{ occ.title }}
                                </h3>
                                <p class="font-body text-xs text-white leading-relaxed line-clamp-3 font-medium">
                                    {{ occ.desc }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 6. VENUE AMENITIES & TECHNICAL PERKS (BRIGHT & CRISP)  -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section id="amenities" class="py-24 sm:py-32 bg-white scroll-mt-20 relative border-t border-slate-200">
            <div class="max-w-7xl mx-auto px-6 md:px-8 relative z-10">
                
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <div class="inline-flex items-center gap-2 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                        <span class="font-mono text-xs font-bold uppercase tracking-[.2em] text-orange-600">Why Choose Butal Ship Hauz</span>
                    </div>
                    <h2 class="font-display font-black text-3xl sm:text-5xl text-slate-900 tracking-tight leading-tight">
                        Built for Flawless Event Execution
                    </h2>
                    <p class="font-body text-slate-600 text-base sm:text-lg mt-3 font-medium">
                        We provide the technical reliability, comfort, and ambiance essential for making your celebration completely stress-free.
                    </p>
                </div>

                <!-- Clean, Light-Themed Amenity Cards (Zero eye strain) -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div 
                        v-for="perk in venuePerks" 
                        :key="perk.title"
                        class="p-8 rounded-3xl bg-slate-50 border border-slate-200 hover:border-orange-300 hover:bg-white hover:shadow-xl transition-all duration-300 flex gap-5 items-start"
                    >
                        <div class="w-12 h-12 rounded-2xl bg-orange-500 text-white flex items-center justify-center text-xl flex-shrink-0 shadow-md shadow-orange-500/25">
                            <font-awesome-icon :icon="perk.icon" />
                        </div>
                        <div>
                            <h3 class="font-display font-black text-xl text-slate-900 mb-2">
                                {{ perk.title }}
                            </h3>
                            <p class="font-body text-slate-600 text-sm leading-relaxed font-medium">
                                {{ perk.desc }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Video Tour Showcase -->
                <div class="mt-20 rounded-3xl overflow-hidden border border-slate-200 bg-slate-50 p-4 sm:p-6 shadow-lg">
                    <div class="aspect-video rounded-2xl overflow-hidden relative shadow-inner bg-black">
                        <video 
                            src="/images/about-video.mp4" 
                            class="w-full h-full object-cover" 
                            autoplay 
                            loop 
                            muted 
                            playsinline
                            preload="auto"
                            :muted="true"
                        ></video>
                    </div>
                    <div class="pt-4 px-2 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs font-bold text-slate-700">
                        <span class="flex items-center gap-2">
                            <font-awesome-icon icon="fa-solid fa-play" class="text-orange-500" />
                            Virtual Ocular Tour — Experience the Ship Hauz Ambience
                        </span>
                        <a href="#contact" class="text-orange-600 hover:text-orange-700 underline transition-colors">
                            Schedule a Personal On-Site Walkthrough &rarr;
                        </a>
                    </div>
                </div>

            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 7. FREQUENTLY ASKED QUESTIONS                         -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section id="faq" class="py-24 sm:py-32 bg-slate-50 border-t border-slate-200 scroll-mt-20">
            <div class="max-w-4xl mx-auto px-6 md:px-8">
                
                <div class="text-center mb-16">
                    <div class="inline-flex items-center gap-2 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                        <span class="font-mono text-xs font-bold uppercase tracking-[.2em] text-orange-600">Event Planner FAQ</span>
                    </div>
                    <h2 class="font-display text-slate-900 font-black text-3xl sm:text-5xl tracking-tight leading-tight">
                        Got Questions About Booking?
                    </h2>
                    <p class="font-body text-slate-600 text-base mt-3 font-medium">
                        Everything you need to know about reserving dates, outside suppliers, ingress setup hours, and policies.
                    </p>
                </div>

                <div class="space-y-4">
                    <div 
                        v-for="(faq, index) in faqs" 
                        :key="faq.q"
                        class="border border-slate-200 rounded-2xl overflow-hidden transition-all duration-200 shadow-sm"
                        :class="faq.open ? 'bg-white border-orange-300 ring-1 ring-orange-200' : 'bg-white hover:border-slate-300'"
                    >
                        <button 
                            @click="toggleFaq(index)"
                            type="button"
                            class="w-full px-6 py-5 text-left flex items-center justify-between gap-4 font-display font-black text-lg sm:text-xl text-slate-900"
                        >
                            <span>{{ faq.q }}</span>
                            <font-awesome-icon 
                                icon="fa-solid fa-chevron-down" 
                                class="text-slate-500 text-sm transition-transform duration-300 flex-shrink-0"
                                :class="{ 'rotate-180 text-orange-500': faq.open }"
                            />
                        </button>

                        <div 
                            v-show="faq.open"
                            class="px-6 pb-6 pt-1 font-body text-slate-700 text-base leading-relaxed border-t border-slate-100 font-medium"
                        >
                            {{ faq.a }}
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 8. STORIES & RECENT EVENTS FEED                        -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section id="blog" class="py-24 bg-white border-t border-slate-200 scroll-mt-20">
            <div class="max-w-7xl mx-auto px-6 md:px-8">
                
                <div class="flex flex-col md:flex-row md:items-end justify-between gap-6 mb-14">
                    <div>
                        <div class="inline-flex items-center gap-2 mb-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                            <span class="font-mono text-xs font-bold uppercase tracking-[.2em] text-orange-600">Event Highlights & News</span>
                        </div>
                        <h2 class="font-display text-slate-900 font-black text-3xl sm:text-5xl tracking-tight leading-tight">
                            Stories from the Ship
                        </h2>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-if="posts.length === 0" class="col-span-1 md:col-span-3 flex flex-col items-center justify-center py-16 bg-slate-50 border border-dashed border-slate-300 rounded-3xl text-center">
                        <font-awesome-icon icon="fa-solid fa-newspaper" class="text-3xl text-slate-400 mb-3" />
                        <p class="text-slate-600 font-bold">New event stories and updates will be featured here soon.</p>
                    </div>

                    <article 
                        v-for="post in posts" 
                        :key="post.id" 
                        class="group bg-slate-50 hover:bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col"
                    >
                        <!-- Cover Image with Direct Link -->
                        <a
                            :href="getArticleLink(post)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="h-52 relative bg-slate-100 overflow-hidden block group/img cursor-pointer"
                            :title="'Read article: ' + post.title"
                        >
                            <img 
                                v-if="post.image" 
                                :src="resolveImageUrl(post.image, '/images/blog1.jpg')" 
                                :alt="post.title" 
                                @error="handleImageError($event, '/images/blog1.jpg')"
                                class="w-full h-full object-cover transition-transform duration-700 group-hover/img:scale-105" 
                            />
                            <div v-else class="w-full h-full bg-sky-100 flex items-center justify-center text-sky-400">
                                <font-awesome-icon icon="fa-solid fa-newspaper" class="text-3xl" />
                            </div>
                            <div class="absolute inset-0 bg-slate-900/0 group-hover/img:bg-slate-900/30 transition-colors flex items-center justify-center opacity-0 group-hover/img:opacity-100">
                                <span class="bg-white/95 text-slate-900 text-xs font-bold px-3 py-1.5 rounded-full shadow-md flex items-center gap-1.5">
                                    <span>Read Article</span>
                                    <font-awesome-icon icon="fa-solid fa-arrow-up-right-from-square" class="text-[10px] text-orange-600" />
                                </span>
                            </div>
                        </a>

                        <div class="p-6 sm:p-8 flex flex-col flex-1">
                            <span class="font-mono text-[10px] font-bold uppercase tracking-wider text-orange-700 bg-orange-100 px-2.5 py-1 rounded-md w-max mb-3">
                                {{ post.category || 'Celebration' }}
                            </span>

                            <!-- Title: Direct Link -->
                            <a
                                :href="getArticleLink(post)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="block group/title cursor-pointer"
                                :title="'Read article: ' + post.title"
                            >
                                <h3 class="font-display text-slate-900 font-black text-xl leading-snug mb-3 group-hover/title:text-orange-600 transition-colors line-clamp-2">
                                    {{ post.title }}
                                </h3>
                            </a>

                            <p class="font-body text-slate-600 text-sm leading-relaxed mb-6 flex-1 line-clamp-3 font-medium">
                                {{ post.excerpt }}
                            </p>

                            <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                                <span class="font-mono text-xs text-slate-500 font-semibold flex items-center gap-1.5">
                                    <font-awesome-icon icon="fa-regular fa-calendar" />
                                    {{ post.post_date ? new Date(post.post_date).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' }) : '' }}
                                </span>

                                <!-- Read Article Button: ALWAYS Direct Link, Never Modal -->
                                <a
                                    :href="getArticleLink(post)"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-orange-600 hover:text-orange-700 font-bold text-xs inline-flex items-center gap-1.5 transition-colors group/link px-3 py-1.5 rounded-lg hover:bg-orange-50 cursor-pointer"
                                    :title="'Read article: ' + post.title"
                                >
                                    <span>Read Article</span>
                                    <font-awesome-icon icon="fa-solid fa-arrow-up-right-from-square" class="text-[10px] group-hover/link:translate-x-0.5 group-hover/link:-translate-y-0.5 transition-transform" />
                                </a>
                            </div>
                        </div>
                    </article>
                </div>

            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 9. CONTACT, MAP & OCULAR SCHEDULE (HIGH CONTRAST)     -->
        <!-- ══════════════════════════════════════════════════════ -->
        <section id="contact" class="py-24 sm:py-32 bg-sky-900 text-white scroll-mt-20 relative">
            <div class="max-w-7xl mx-auto px-6 md:px-8 relative z-10">
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-20 items-start">
                    
                    <!-- Left: Contact Details & Ocular CTA -->
                    <div>
                        <div class="inline-flex items-center gap-2 mb-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-amber-400"></span>
                            <span class="font-mono text-xs font-bold uppercase tracking-[.2em] text-amber-300">Ocular Inspection & Inquiries</span>
                        </div>
                        <h2 class="font-display font-black text-3xl sm:text-5xl tracking-tight leading-tight mb-6 text-white">
                            Schedule Your Visit or Inquire Today
                        </h2>
                        <p class="font-body text-sky-100 text-base sm:text-lg mb-10 leading-relaxed font-medium">
                            We encourage couples and event planners to walk the decks in person. Feel free to contact our events desk to arrange your complimentary ocular tour.
                        </p>

                        <!-- White Contact Cards for 100% Crisp Legibility -->
                        <div class="space-y-4">
                            <div 
                                v-for="info in contactInfo" 
                                :key="info.label"
                                class="flex items-start gap-4 p-4.5 rounded-2xl bg-white text-slate-800 shadow-md border border-slate-100"
                            >
                                <div class="w-11 h-11 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-lg flex-shrink-0">
                                    <font-awesome-icon :icon="info.icon" />
                                </div>
                                <div>
                                    <div class="font-mono text-[10px] uppercase font-black tracking-wider text-orange-600 mb-1">
                                        {{ info.label }}
                                    </div>
                                    <div class="font-body text-sm sm:text-base font-bold text-slate-900">
                                        {{ info.value }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Google Map -->
                    <div class="rounded-3xl overflow-hidden border border-slate-200 bg-white shadow-2xl flex flex-col h-[520px]">
                        <div class="p-6 border-b border-slate-200 bg-slate-50 flex items-center justify-between text-slate-900">
                            <div>
                                <h3 class="font-display font-black text-xl text-slate-900">Interactive Map Location</h3>
                                <p class="text-slate-600 text-xs font-medium">San Roque, Talibon, Bohol, Philippines</p>
                            </div>
                            <span class="bg-emerald-100 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full border border-emerald-200">
                                Open for Ocular
                            </span>
                        </div>

                        <div class="flex-1 relative">
                            <iframe
                                class="absolute inset-0 w-full h-full hover:opacity-100 transition-opacity"
                                frameborder="0"
                                style="border: 0"
                                referrerpolicy="no-referrer-when-downgrade"
                                src="https://www.google.com/maps?q=10.150360879723529,124.32322880265386&z=17&output=embed"
                                allowfullscreen
                            ></iframe>
                        </div>

                        <div class="p-4 bg-slate-50 border-t border-slate-200">
                            <a 
                                href="https://www.google.com/maps?q=10.150360879723529,124.32322880265386"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="w-full inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black text-sm py-3.5 rounded-xl transition-all shadow-md"
                            >
                                <font-awesome-icon icon="fa-solid fa-location-dot" />
                                Get Driving Directions
                            </a>
                        </div>
                    </div>

                </div>

            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 10. FOOTER                                             -->
        <!-- ══════════════════════════════════════════════════════ -->
        <footer class="bg-slate-900 text-slate-300 border-t border-slate-800 pt-20 pb-12">
            <div class="max-w-7xl mx-auto px-6 md:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-16">
                    <!-- Brand Col -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center shadow-md">
                                <font-awesome-icon icon="fa-solid fa-ship" class="text-white text-lg" />
                            </div>
                            <div>
                                <div class="font-display text-white text-lg font-bold leading-tight">Butal Ship Hauz</div>
                                <div class="font-mono text-amber-400 text-[10px] font-bold tracking-widest uppercase">Talibon, Bohol</div>
                            </div>
                        </div>

                        <p class="text-slate-300 text-xs leading-relaxed font-medium">
                            Bohol's distinctive maritime hospitality landmark offering indoor and open-air decks for weddings, debutante balls, conferences, and celebrations.
                        </p>

                        <!-- Social Icons -->
                        <div class="flex items-center gap-3 pt-2">
                            <a 
                                v-for="social in socials" 
                                :key="social.label" 
                                :href="social.url" 
                                target="_blank" 
                                rel="noopener noreferrer"
                                :aria-label="social.label"
                                class="w-9 h-9 rounded-full bg-slate-800 hover:bg-orange-500 text-slate-300 hover:text-white flex items-center justify-center transition-colors"
                            >
                                <svg class="w-4 fill-current" viewBox="0 0 24 24" v-html="social.icon"></svg>
                            </a>
                        </div>
                    </div>

                    <!-- Dynamic Navigation Links -->
                    <div v-for="col in footerCols" :key="col.heading" class="space-y-3">
                        <h4 class="font-mono text-xs font-bold text-white uppercase tracking-wider">
                            {{ col.heading }}
                        </h4>
                        <ul class="space-y-2 text-xs">
                            <li v-for="link in col.links" :key="link.label">
                                <a :href="link.href" class="text-slate-300 hover:text-orange-400 font-medium transition-colors">
                                    {{ link.label }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="pt-8 border-t border-slate-800 text-xs text-slate-400 flex flex-col sm:flex-row items-center justify-between gap-4 font-medium">
                    <p>© {{ new Date().getFullYear() }} Butal Ship Hauz Venue & Resort. All rights reserved.</p>
                    <p class="font-mono text-[11px]">Crafted with pride in Talibon, Bohol, Philippines.</p>
                </div>

            </div>
        </footer>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 11. VENUE INCLUSIONS & DETAILS MODAL                   -->
        <!-- ══════════════════════════════════════════════════════ -->
        <Modal :show="showVenueModal" max-width="3xl" @close="closeVenueModal">
            <div v-if="selectedVenue" class="bg-white overflow-hidden rounded-3xl">
                <!-- Modal Top Banner -->
                <div class="relative h-64 sm:h-80 w-full bg-slate-900">
                    <img 
                        :src="resolveImageUrl(selectedVenue.image, '/images/venue.jpg')" 
                        :alt="selectedVenue.title" 
                        @error="handleImageError($event, '/images/venue.jpg')"
                        class="w-full h-full object-cover" 
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent"></div>

                    <button 
                        @click="closeVenueModal" 
                        class="absolute top-4 right-4 w-10 h-10 rounded-full bg-black/60 hover:bg-black/80 text-white flex items-center justify-center transition-colors shadow-md backdrop-blur-sm"
                        aria-label="Close modal"
                    >
                        <font-awesome-icon icon="fa-solid fa-xmark" class="text-lg" />
                    </button>

                    <div class="absolute bottom-6 left-6 right-6 text-white">
                        <span class="bg-orange-500 text-white font-mono text-[10px] font-bold px-3 py-1 rounded-md uppercase tracking-wider mb-2 inline-block shadow">
                            {{ selectedVenue.tag }}
                        </span>
                        <h2 class="font-display font-black text-2xl sm:text-4xl text-white drop-shadow-md">
                            {{ selectedVenue.title }}
                        </h2>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="p-6 sm:p-8 space-y-6">
                    <!-- Quick Info Bar -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs">
                        <div>
                            <span class="font-mono text-[10px] uppercase font-bold text-slate-500 block">Starting Rate</span>
                            <strong class="font-display text-xl font-black text-orange-600">₱{{ Number(selectedVenue.price).toLocaleString() }}</strong>
                        </div>
                        <div>
                            <span class="font-mono text-[10px] uppercase font-bold text-slate-500 block">Capacity</span>
                            <strong class="font-display text-xl font-black text-slate-900">Up to {{ selectedVenue.guests }} Guests</strong>
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <span class="font-mono text-[10px] uppercase font-bold text-slate-500 block">Seating Config</span>
                            <strong class="text-slate-800 font-bold text-xs block leading-tight mt-1">{{ selectedVenue.seating || 'Banquet & Theater' }}</strong>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <h3 class="font-mono text-xs uppercase tracking-wider font-bold text-slate-700 mb-2">Space Overview</h3>
                        <p class="font-body text-slate-700 text-sm leading-relaxed font-medium">
                            {{ selectedVenue.description }}
                        </p>
                    </div>

                    <!-- Available Events & Rates Breakdown -->
                    <div v-if="selectedVenue.availableEvents && selectedVenue.availableEvents.length" class="space-y-2.5">
                        <h3 class="font-mono text-xs uppercase tracking-wider font-bold text-slate-700">Available Event Occasions for this Deck</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <div 
                                v-for="evt in selectedVenue.availableEvents" 
                                :key="evt.id"
                                class="flex items-center justify-between bg-orange-50/70 border border-orange-200/80 px-3.5 py-2.5 rounded-xl text-xs font-bold text-slate-900 shadow-2xs"
                            >
                                <span class="flex items-center gap-2">
                                    <span class="text-base">🎉</span>
                                    <span>{{ evt.name }}</span>
                                </span>
                                <span v-if="evt.price" class="text-orange-600 font-mono">
                                    Starts at ₱{{ Number(evt.price).toLocaleString() }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Inclusions List -->
                    <div>
                        <h3 class="font-mono text-xs uppercase tracking-wider font-bold text-slate-700 mb-3">Package Inclusions</h3>
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 text-xs text-slate-800">
                            <li 
                                v-for="inc in (selectedVenue.inclusions || ['Full Sound System', 'Tables & Chairs', 'Backup Power', 'Dressing Room'])" 
                                :key="inc"
                                class="flex items-center gap-2 bg-emerald-50 text-emerald-900 border border-emerald-200 px-3 py-2 rounded-xl font-semibold"
                            >
                                <font-awesome-icon icon="fa-solid fa-check" class="text-emerald-600 flex-shrink-0" />
                                <span>{{ inc }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="p-6 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <button 
                        @click="closeVenueModal" 
                        class="w-full sm:w-auto px-6 py-3 border border-slate-300 hover:bg-slate-100 text-slate-800 font-bold rounded-xl text-sm transition-colors"
                    >
                        Close Preview
                    </button>

                    <Link 
                        v-if="$page.props.auth.user && $page.props.auth.user.role === 'client'"
                        :href="route('client.booking.index', { venue_id: selectedVenue.id })"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black text-sm px-8 py-3 rounded-xl transition-all shadow-md shadow-orange-500/20"
                    >
                        <font-awesome-icon icon="fa-solid fa-calendar-check" /> Proceed to Book This Deck
                    </Link>
                    <Link 
                        v-else
                        :href="route('register')"
                        class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black text-sm px-8 py-3 rounded-xl transition-all shadow-md shadow-orange-500/20"
                    >
                        <font-awesome-icon icon="fa-solid fa-user-plus" /> Sign Up & Reserve Date
                    </Link>
                </div>
            </div>
        </Modal>



        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 13. STICKY FLOATING QUICK-RESERVE PILL                -->
        <!-- ══════════════════════════════════════════════════════ -->
        <transition
            enter-active-class="transition duration-300 ease-out transform"
            enter-from-class="opacity-0 translate-y-6"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in transform"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-6"
        >
            <div 
                v-if="isScrolledPastHero" 
                class="fixed bottom-6 left-6 z-30 hidden md:flex items-center gap-4 bg-white text-slate-900 border border-slate-200 px-5 py-3 rounded-2xl shadow-xl"
            >
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-orange-500 flex items-center justify-center text-white shadow-sm">
                        <font-awesome-icon icon="fa-solid fa-calendar-check" />
                    </div>
                    <div class="text-xs">
                        <div class="font-bold text-slate-900 leading-tight">Ready to lock in your event date?</div>
                        <div class="text-orange-600 font-mono text-[10px] font-bold">Admiral Grand Deck & Terrace Packages</div>
                    </div>
                </div>

                <Link 
                    :href="$page.props.auth.user ? route('client.booking.index') : route('register')"
                    class="bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black text-xs px-4 py-2 rounded-xl transition-all shadow-md shadow-orange-500/20 whitespace-nowrap"
                >
                    Book Venue Now
                </Link>
            </div>
        </transition>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 14. CHATBOT FAB WIDGET                                 -->
        <!-- ══════════════════════════════════════════════════════ -->
        <ChatBot />

    </div>
</template>

<style scoped>
/* ── Nav Link Hover Accent ── */
.nav-link {
    position: relative;
    text-decoration: none;
}
.nav-link::after {
    content: "";
    position: absolute;
    bottom: -6px;
    left: 0;
    right: 0;
    height: 2px;
    background: #f97316;
    transform: scaleX(0);
    transition: transform 0.2s ease;
}
.nav-link:hover::after {
    transform: scaleX(1);
}

/* ── Custom Scrollbar for Modal ── */
::-webkit-scrollbar {
    width: 7px;
}
::-webkit-scrollbar-track {
    background: #f1f5f9; 
}
::-webkit-scrollbar-thumb {
    background: #cbd5e1; 
    border-radius: 4px;
}
::-webkit-scrollbar-thumb:hover {
    background: #94a3b8; 
}
</style>