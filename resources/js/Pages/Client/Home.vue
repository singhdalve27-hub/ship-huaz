<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import Modal from "@/Components/Modal.vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import { ref, computed } from "vue";

const page = usePage();
const user = computed(() => page.props.auth.user);

const props = defineProps({
    bookings: {
        type: Array,
        default: () => [],
    },
    notifications: {
        type: Array,
        default: () => [],
    },
    venues: {
        type: Array,
        default: () => [],
    },
});

const bookings = computed(() => props.bookings || []);
const notifications = computed(() => props.notifications || []);
const venues = computed(() => props.venues || []);

const unreadCount = computed(
    () => notifications.value.filter((n) => !n.read_at).length,
);

// Active Tab: feeds (venues), bookings, notifications
const activeTab = ref("feeds");

// ── Image Helper & Fallback ──
const resolveImageUrl = (img, fallback = "/images/venue.jpg") => {
    if (!img) return fallback;
    if (typeof img === "string") {
        if (img.includes("XmTOpOEzc8Q71BziIlsEPiIcOn36151otlKZ9b5I")) return "/images/venue.jpg";
        if (img.includes("6pdOrmIILX1jfPKuzGK7Wes1hNF0L0hzLfIWkjBF")) return "/images/venue2.jpg";
        if (img.includes("4wdCZSeyN6xugrJkZucQ8gY32U519AtZoR7gGyOJ")) return "/images/dining.jpg";
        if (img.startsWith("http://") || img.startsWith("https://") || img.startsWith("/")) return img;
    }
    return "/" + img;
};

// ── Venue Deduplication & Available Events Aggregator ──
// Same reliable pattern used on Welcome.vue: Groups packages by physical venue title
const curatedFallbackTemplates = [
    {
        title: "The Admiral's Grand Deck",
        tag: "Grand Ballroom",
        description: "Premier main deck ballroom with natural ocean breeze ventilation, theater stage, VIP mezzanine, and panoramic bay vistas.",
        image: "/images/venue.jpg",
        inclusions: [
            "Exclusive access to the Grand Main Deck",
            "Elevated performance stage with LED mood lighting",
            "Professional multi-channel sound system with wireless mics",
            "Round banquet dining tables with cushioned seating",
            "Standby commercial automatic backup generator",
            "Natural coastal sea breeze ventilation & bay vistas",
        ],
    },
    {
        title: "Sunset Promenade & Skyline Terrace",
        tag: "Open-Air Deck",
        description: "Open-air upper ship deck offering breath-taking coastal sunset breezes, fairy festoon lighting, and acoustic stage sets.",
        image: "/images/venue2.jpg",
        inclusions: [
            "Open-air upper deck with 360-degree sunset ocean views",
            "Romantic festoon string & ambient fairy lighting",
            "Dedicated acoustic live music stage & mobile speakers",
            "Cocktail bar counter and buffet food staging area",
            "Tent and weather canopy standby provisions",
            "Natural ocean sea breeze throughout the event",
        ],
    },
    {
        title: "Captain's Executive Function Hall",
        tag: "Conference & Dining",
        description: "Enclosed maritime-styled hall engineered for business seminars, private birthday dinners, and intimate baptisms.",
        image: "/images/dining.jpg",
        inclusions: [
            "Executive maritime conference hall with projector setup",
            "High-speed presentation Wi-Fi connectivity",
            "Conference microphones & clear audio monitors",
            "Ergonomic seating with flexible classroom or banquet layouts",
            "Coffee station and dedicated buffet staging area",
            "Refreshing coastal sea breeze ventilation",
        ],
    },
];

const deduplicatedVenues = computed(() => {
    if (!venues.value || venues.value.length === 0) {
        return curatedFallbackTemplates.map((item, idx) => ({
            id: `v_${idx}`,
            title: item.title,
            tag: item.tag,
            price: 15000 + idx * 5000,
            guests: 150 - idx * 30,
            image: item.image,
            description: item.description,
            inclusions: item.inclusions,
            availableEvents: [
                { id: 1, name: "Wedding & Reception" },
                { id: 2, name: "Birthday & Debut" },
                { id: 3, name: "Corporate Gathering" },
            ],
            packages: [],
        }));
    }

    const groups = new Map();

    venues.value.forEach((v) => {
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

    const result = [];
    let index = 0;

    for (const [key, group] of groups.entries()) {
        const primary = group.primary;
        const items = group.items;
        const fallback = curatedFallbackTemplates[index % curatedFallbackTemplates.length];

        // Gather unique event types configured for this deck
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

        // Price calculation: lowest starting price
        const prices = items
            .map((p) => Number(p.price))
            .filter((pr) => !isNaN(pr) && pr > 0);
        const lowestPrice = prices.length > 0 ? Math.min(...prices) : Number(primary.price || 15000);

        // Guest capacity: max capacity
        const guestCaps = items
            .map((p) => Number(p.guests || p.capacity))
            .filter((g) => !isNaN(g) && g > 0);
        const maxGuests = guestCaps.length > 0 ? Math.max(...guestCaps) : (primary.guests || primary.capacity || 150);

        const tag = maxGuests > 180 ? "Grand Ballroom" : maxGuests > 90 ? "Open-Air Deck" : "Conference & Dining";

        result.push({
            id: primary.id,
            title: group.title,
            tag: tag,
            price: lowestPrice,
            guests: maxGuests,
            image: resolveImageUrl(primary.image, fallback.image),
            description: primary.description || fallback.description,
            inclusions: fallback.inclusions,
            availableEvents: availableEvents.length > 0 ? availableEvents : [
                { id: 1, name: "Wedding & Reception" },
                { id: 2, name: "Debut Gala" },
                { id: 3, name: "Corporate Gathering" },
            ],
            packages: items,
        });

        index++;
    }

    return result;
});

// ── Inclusions & Details Modal ──
const showDeckModal = ref(false);
const activeDeck = ref(null);

const openDeckModal = (deck) => {
    activeDeck.value = deck;
    showDeckModal.value = true;
};

const closeDeckModal = () => {
    showDeckModal.value = false;
    setTimeout(() => {
        activeDeck.value = null;
    }, 200);
};

// ── Status Configurations ──
const statusConfig = {
    pending: { label: "Pending Review", badge: "bg-amber-50 text-amber-800 border-amber-200", icon: "fa-solid fa-hourglass-half" },
    confirmed: { label: "Confirmed", badge: "bg-sky-50 text-sky-800 border-sky-200", icon: "fa-solid fa-circle-check" },
    cancelled: { label: "Cancelled", badge: "bg-rose-50 text-rose-800 border-rose-200", icon: "fa-solid fa-circle-xmark" },
    completed: { label: "Completed", badge: "bg-emerald-50 text-emerald-800 border-emerald-200", icon: "fa-solid fa-flag-checkered" },
};

function getStatus(status) {
    return statusConfig[status] ?? {
        label: status,
        badge: "bg-slate-100 text-slate-700 border-slate-200",
        icon: "fa-solid fa-circle",
    };
}

function formatDate(dateStr) {
    if (!dateStr) return "—";
    return new Date(dateStr).toLocaleDateString("en-PH", {
        year: "numeric",
        month: "short",
        day: "numeric",
    });
}

function formatTime(dateStr) {
    if (!dateStr) return "";
    return new Date(dateStr).toLocaleTimeString("en-PH", {
        hour: "2-digit",
        minute: "2-digit",
    });
}

const pendingCount = computed(
    () => bookings.value.filter((b) => b.status === "pending").length,
);
const confirmedCount = computed(
    () => bookings.value.filter((b) => b.status === "confirmed" || b.status === "completed").length,
);
</script>

<template>
    <Head title="Passenger Dashboard | Butal Ship Hauz" />

    <AuthenticatedLayout>
        <div class="space-y-8">
            <!-- ── HERO BANNER: LUXURY CRUISE SHIPBOARD LOUNGE ── -->
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-sky-950 via-sky-900 to-slate-900 border border-sky-800/60 shadow-xl text-white p-6 sm:p-8 lg:p-10">
                <!-- Nautical Wave & Starburst Background -->
                <div class="pointer-events-none absolute inset-0 opacity-10">
                    <svg class="w-full h-full object-cover" viewBox="0 0 800 400" preserveAspectRatio="none">
                        <path d="M0,200 C300,300 500,100 800,200 L800,400 L0,400 Z" fill="currentColor" />
                    </svg>
                </div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="max-w-2xl space-y-3">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/10 backdrop-blur-md border border-white/15 text-[11px] font-mono font-bold tracking-widest uppercase text-orange-400">
                            <font-awesome-icon icon="fa-solid fa-anchor" class="text-xs" />
                            <span>Passenger Deck • Talibon, Bohol</span>
                        </div>

                        <h1 class="font-display text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight text-white leading-tight drop-shadow-sm">
                            Welcome Aboard, <span class="text-transparent bg-clip-text bg-gradient-to-r from-orange-400 to-amber-300">{{ user?.name || "Sailor" }}</span>!
                        </h1>

                        <p class="text-sm sm:text-base text-slate-300 font-medium leading-relaxed">
                            Your personal voyage command center at Butal Ship Hauz. Browse all configured event decks with natural sea breeze ambiance, check shift availability, and review your reservations.
                        </p>

                        <!-- Quick Shortcuts -->
                        <div class="pt-2 flex flex-wrap items-center gap-3">
                            <Link
                                :href="route('client.booking.index')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white shadow-lg shadow-orange-500/25 transition-all hover:scale-[1.02]"
                            >
                                <font-awesome-icon icon="fa-solid fa-calendar-plus" />
                                <span>Book an Event Deck</span>
                            </Link>

                            <button
                                @click="activeTab = 'feeds'"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold bg-white/10 hover:bg-white/20 border border-white/20 text-white backdrop-blur-md transition-all"
                            >
                                <font-awesome-icon icon="fa-solid fa-compass" class="text-orange-400" />
                                <span>Browse Decks</span>
                            </button>

                            <Link
                                :href="route('landing-page')"
                                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold text-slate-300 hover:text-white transition-colors"
                            >
                                <font-awesome-icon icon="fa-solid fa-globe" />
                                <span>Public Virtual Tour</span>
                            </Link>
                        </div>
                    </div>

                    <!-- Quick Shift Status Card -->
                    <div class="lg:w-80 p-5 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 shadow-xl space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-white/10 text-xs font-mono">
                            <span class="text-slate-300 font-bold uppercase tracking-wider">Shift Schedule</span>
                            <span class="text-emerald-400 font-bold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                Open for Booking
                            </span>
                        </div>
                        <ul class="space-y-2 text-xs text-slate-200">
                            <li class="flex items-center justify-between">
                                <span class="font-medium text-slate-300">🌅 Morning Shift</span>
                                <span class="font-mono font-bold text-orange-400">8:00 AM – 12:00 PM</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span class="font-medium text-slate-300">☀️ Afternoon Shift</span>
                                <span class="font-mono font-bold text-orange-400">1:00 PM – 5:00 PM</span>
                            </li>
                            <li class="flex items-center justify-between">
                                <span class="font-medium text-slate-300">🌙 Night Gala Shift</span>
                                <span class="font-mono font-bold text-orange-400">6:00 PM – 10:00 PM</span>
                            </li>
                            <li class="flex items-center justify-between pt-1 border-t border-white/10">
                                <span class="font-medium text-amber-300">⭐ Full Day Exclusive</span>
                                <span class="font-mono font-bold text-amber-300">8:00 AM – 10:00 PM</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- ── MARITIME STAT METRICS CARDS ── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- Total Bookings -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition-all group flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                        <font-awesome-icon icon="fa-solid fa-calendar-check" />
                    </div>
                    <div>
                        <p class="font-mono text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Bookings</p>
                        <p class="font-display text-2xl sm:text-3xl font-black text-sky-950 leading-tight">{{ bookings.length }}</p>
                    </div>
                </div>

                <!-- Pending Review -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition-all group flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                        <font-awesome-icon icon="fa-solid fa-hourglass-half" />
                    </div>
                    <div>
                        <p class="font-mono text-[10px] font-bold uppercase tracking-wider text-slate-400">Pending Review</p>
                        <p class="font-display text-2xl sm:text-3xl font-black text-amber-600 leading-tight">{{ pendingCount }}</p>
                    </div>
                </div>

                <!-- Confirmed / Completed -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition-all group flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                        <font-awesome-icon icon="fa-solid fa-flag-checkered" />
                    </div>
                    <div>
                        <p class="font-mono text-[10px] font-bold uppercase tracking-wider text-slate-400">Confirmed / Done</p>
                        <p class="font-display text-2xl sm:text-3xl font-black text-emerald-700 leading-tight">{{ confirmedCount }}</p>
                    </div>
                </div>

                <!-- Unread Alerts -->
                <div class="p-5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:shadow-md transition-all group flex items-center gap-4">
                    <div class="w-12 h-12 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center text-xl group-hover:scale-110 transition-transform">
                        <font-awesome-icon icon="fa-solid fa-bell" />
                    </div>
                    <div>
                        <p class="font-mono text-[10px] font-bold uppercase tracking-wider text-slate-400">Shipboard Alerts</p>
                        <p class="font-display text-2xl sm:text-3xl font-black text-orange-600 leading-tight">{{ unreadCount }}</p>
                    </div>
                </div>
            </div>

            <!-- ── SEGMENTED CONTROL TABS ── -->
            <div class="flex items-center justify-between flex-wrap gap-4 border-b border-slate-200 pb-4">
                <div class="inline-flex p-1 rounded-2xl bg-slate-100 border border-slate-200">
                    <button
                        @click="activeTab = 'feeds'"
                        :class="[
                            'flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all',
                            activeTab === 'feeds'
                                ? 'bg-white text-sky-950 shadow-sm'
                                : 'text-slate-600 hover:text-sky-900'
                        ]"
                    >
                        <font-awesome-icon icon="fa-solid fa-compass" class="text-orange-500" />
                        <span>Available Ship Decks</span>
                        <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[10px] font-mono">
                            {{ deduplicatedVenues.length }}
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'bookings'"
                        :class="[
                            'flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all',
                            activeTab === 'bookings'
                                ? 'bg-white text-sky-950 shadow-sm'
                                : 'text-slate-600 hover:text-sky-900'
                        ]"
                    >
                        <font-awesome-icon icon="fa-solid fa-book-bookmark" class="text-sky-600" />
                        <span>My Bookings</span>
                        <span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 text-[10px] font-mono">
                            {{ bookings.length }}
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'notifications'"
                        :class="[
                            'flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all',
                            activeTab === 'notifications'
                                ? 'bg-white text-sky-950 shadow-sm'
                                : 'text-slate-600 hover:text-sky-900'
                        ]"
                    >
                        <font-awesome-icon icon="fa-solid fa-bell" class="text-amber-500" />
                        <span>Shipboard Alerts</span>
                        <span
                            v-if="unreadCount > 0"
                            class="px-2 py-0.5 rounded-full bg-orange-500 text-white text-[10px] font-mono font-bold animate-pulse"
                        >
                            {{ unreadCount }}
                        </span>
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <span class="hidden sm:inline text-xs font-mono text-slate-400">
                        Ambiance: Open-Air Natural Sea Breeze
                    </span>
                    <Link
                        :href="route('client.booking.index')"
                        class="inline-flex items-center gap-1.5 text-xs font-bold text-sky-800 hover:text-orange-500 transition-colors"
                    >
                        <span>Check Deck Schedule</span>
                        <font-awesome-icon icon="fa-solid fa-arrow-right" class="text-[10px]" />
                    </Link>
                </div>
            </div>

            <!-- ── TAB 1: DEDUPLICATED VENUES & AVAILABLE EVENTS ── -->
            <section v-if="activeTab === 'feeds'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                    <article
                        v-for="deck in deduplicatedVenues"
                        :key="deck.id"
                        class="rounded-3xl bg-white border border-slate-200/90 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group"
                    >
                        <!-- Deck Cover Image with Badges -->
                        <div class="relative h-56 w-full overflow-hidden bg-slate-900 shrink-0">
                            <img
                                :src="deck.image"
                                :alt="deck.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                @error="$event.target.onerror = null; $event.target.src = '/images/venue.jpg'"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-black/30"></div>

                            <!-- Tag / Category badge -->
                            <div class="absolute top-4 left-4 flex items-center gap-2">
                                <span class="px-3 py-1 rounded-full bg-white/95 backdrop-blur-md text-[10px] font-mono font-bold uppercase tracking-wider text-sky-950 shadow-sm">
                                    {{ deck.tag }}
                                </span>
                            </div>

                            <!-- Capacity badge -->
                            <div class="absolute top-4 right-4">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-sky-950/85 backdrop-blur-md text-[10px] font-mono font-bold text-white border border-white/20 shadow-sm">
                                    <font-awesome-icon icon="fa-solid fa-users" class="text-orange-400 text-xs" />
                                    Up to {{ deck.guests }} pax
                                </span>
                            </div>

                            <!-- Starting Rate Tag -->
                            <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between text-white">
                                <div>
                                    <span class="text-[10px] font-mono uppercase text-slate-300 block">Starting From</span>
                                    <span class="font-display font-black text-2xl text-amber-400 leading-none">
                                        ₱{{ Number(deck.price).toLocaleString("en-PH") }}
                                    </span>
                                </div>
                                <span class="text-[11px] font-medium text-slate-200 bg-black/40 px-2.5 py-1 rounded-md backdrop-blur-xs">
                                    per shift
                                </span>
                            </div>
                        </div>

                        <!-- Card Content -->
                        <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                            <div class="space-y-3">
                                <h3 class="font-display font-black text-xl text-sky-950 group-hover:text-orange-500 transition-colors">
                                    {{ deck.title }}
                                </h3>

                                <p class="text-xs text-slate-500 leading-relaxed line-clamp-2">
                                    {{ deck.description }}
                                </p>

                                <!-- Available Events Badges (User requirement: show available events) -->
                                <div class="pt-1">
                                    <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 block mb-2">
                                        Configured for Events:
                                    </span>
                                    <div class="flex flex-wrap gap-1.5">
                                        <span
                                            v-for="(ev, idx) in deck.availableEvents"
                                            :key="idx"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-sky-50 border border-sky-100 text-sky-900 text-[11px] font-semibold"
                                        >
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                                            {{ ev.name }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="pt-4 border-t border-slate-100 flex items-center gap-2">
                                <button
                                    @click="openDeckModal(deck)"
                                    class="flex-1 py-2.5 px-3 rounded-xl border border-slate-200 hover:border-sky-300 hover:bg-sky-50/50 text-slate-700 text-xs font-bold transition-all text-center"
                                >
                                    Inclusions & Rates
                                </button>
                                <Link
                                    :href="route('client.booking.index', { venue_id: deck.id })"
                                    class="flex-1 py-2.5 px-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold shadow-sm shadow-orange-500/25 transition-all text-center hover:scale-[1.02]"
                                >
                                    Reserve Deck
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- ── TAB 2: MY BOOKINGS MANIFEST ── -->
            <section v-if="activeTab === 'bookings'" class="space-y-6">
                <!-- Empty state -->
                <div v-if="bookings.length === 0" class="rounded-3xl bg-white border border-slate-200/80 p-12 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-sky-50 text-sky-700 flex items-center justify-center text-2xl mx-auto">
                        <font-awesome-icon icon="fa-solid fa-anchor" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-display font-bold text-xl text-sky-950">No Booked Voyages Yet</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            You haven't reserved an event deck yet. Check out our available decks or reserve a date today.
                        </p>
                    </div>
                    <div class="pt-2">
                        <Link
                            :href="route('client.booking.index')"
                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold shadow-sm transition-all"
                        >
                            <font-awesome-icon icon="fa-solid fa-plus-circle" />
                            <span>Create Your First Reservation</span>
                        </Link>
                    </div>
                </div>

                <!-- Bookings Cards -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div
                        v-for="b in bookings"
                        :key="b.id"
                        class="rounded-3xl bg-white border border-slate-200/90 shadow-sm hover:shadow-md transition-all p-6 flex flex-col justify-between space-y-4 border-l-4"
                        :class="{
                            'border-l-amber-400': b.status === 'pending',
                            'border-l-sky-600': b.status === 'confirmed',
                            'border-l-emerald-500': b.status === 'completed',
                            'border-l-rose-500': b.status === 'cancelled',
                        }"
                    >
                        <div class="space-y-3">
                            <!-- Ref & Status -->
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-mono text-xs font-bold text-sky-800 bg-sky-50 px-2.5 py-1 rounded-md border border-sky-100">
                                    REF #{{ b.booking_ref || b.id }}
                                </span>
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border',
                                        getStatus(b.status).badge
                                    ]"
                                >
                                    <font-awesome-icon :icon="getStatus(b.status).icon" />
                                    {{ getStatus(b.status).label }}
                                </span>
                            </div>

                            <!-- Event & Package -->
                            <div>
                                <h4 class="font-display font-black text-lg text-sky-950">
                                    {{ b.event_type || b.package || "Special Event" }}
                                </h4>
                                <p class="text-xs font-semibold text-orange-500 mt-0.5">
                                    {{ b.package || "Butal Ship Hauz Deck" }}
                                </p>
                            </div>

                            <!-- Details Grid -->
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-2 text-xs">
                                <div class="flex items-center justify-between text-slate-600">
                                    <span class="flex items-center gap-1.5">
                                        <font-awesome-icon icon="fa-solid fa-calendar-day" class="text-slate-400" />
                                        <span>Event Date:</span>
                                    </span>
                                    <span class="font-bold text-slate-800">{{ formatDate(b.date) }}</span>
                                </div>

                                <div v-if="b.guests" class="flex items-center justify-between text-slate-600">
                                    <span class="flex items-center gap-1.5">
                                        <font-awesome-icon icon="fa-solid fa-users" class="text-slate-400" />
                                        <span>Expected Guests:</span>
                                    </span>
                                    <span class="font-bold text-slate-800">{{ b.guests }} pax</span>
                                </div>
                            </div>
                        </div>

                        <!-- Amount & Action -->
                        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] font-mono uppercase text-slate-400 block">Total Amount</span>
                                <span class="font-display font-black text-lg text-sky-950">
                                    ₱{{ Number(b.total_amount || b.total_payment || 0).toLocaleString("en-PH", { minimumFractionDigits: 2 }) }}
                                </span>
                            </div>
                            <Link
                                :href="route('client.booking.index')"
                                class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-sky-50 hover:text-sky-900 text-slate-700 text-xs font-bold transition-all"
                            >
                                View Details
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── TAB 3: SHIPBOARD NOTIFICATIONS & ALERTS ── -->
            <section v-if="activeTab === 'notifications'" class="space-y-6">
                <div v-if="notifications.length === 0" class="rounded-3xl bg-white border border-slate-200/80 p-12 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-sky-50 text-sky-700 flex items-center justify-center text-2xl mx-auto">
                        <font-awesome-icon icon="fa-solid fa-bell-slash" />
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-display font-bold text-xl text-sky-950">No Signals on Deck</h3>
                        <p class="text-xs text-slate-500 max-w-md mx-auto">
                            All quiet on the maritime telegraph. We will alert you whenever your booking status updates.
                        </p>
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <Link
                        v-for="n in notifications"
                        :key="n.id"
                        :href="route('client.notifications.index')"
                        class="p-5 rounded-2xl bg-white border border-slate-200/80 hover:border-sky-300 hover:shadow-md transition-all flex items-start gap-4 group"
                        :class="{ 'bg-orange-50/20 border-orange-200': !n.read_at }"
                    >
                        <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <font-awesome-icon icon="fa-solid fa-bell" class="text-orange-500" />
                        </div>
                        <div class="flex-1 min-w-0 space-y-1">
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="font-display font-bold text-sm text-sky-950 truncate">
                                    {{ n.title }}
                                </h4>
                                <span class="font-mono text-[10px] text-slate-400 shrink-0">
                                    {{ formatDate(n.created_at) }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed">
                                {{ n.message }}
                            </p>
                        </div>
                        <span v-if="!n.read_at" class="w-2.5 h-2.5 rounded-full bg-orange-500 shrink-0 mt-1.5"></span>
                    </Link>
                </div>
            </section>
        </div>

        <!-- ── DECK INCLUSIONS & RATES MODAL ── -->
        <Modal :show="showDeckModal" @close="closeDeckModal" max-width="2xl">
            <div v-if="activeDeck" class="p-6 sm:p-8 space-y-6">
                <!-- Modal Header -->
                <div class="flex items-start justify-between gap-4 pb-4 border-b border-slate-100">
                    <div class="space-y-1">
                        <span class="px-2.5 py-0.5 rounded-md bg-sky-50 text-sky-800 font-mono text-[10px] font-bold uppercase tracking-wider border border-sky-100">
                            {{ activeDeck.tag }}
                        </span>
                        <h3 class="font-display font-black text-2xl text-sky-950">
                            {{ activeDeck.title }}
                        </h3>
                        <p class="text-xs text-slate-500">
                            Natural Coastal Sea Breeze Ventilation • Capacity: Up to {{ activeDeck.guests }} Guests
                        </p>
                    </div>
                    <button
                        @click="closeDeckModal"
                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition-colors"
                    >
                        <font-awesome-icon icon="fa-solid fa-xmark" />
                    </button>
                </div>

                <!-- Available Events Breakdown -->
                <div class="space-y-3">
                    <h4 class="font-mono text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                        <font-awesome-icon icon="fa-solid fa-cake-candles" class="text-orange-500" />
                        Available Event Packages & Shift Rates
                    </h4>
                    <div class="space-y-2">
                        <div
                            v-for="(ev, idx) in activeDeck.availableEvents"
                            :key="idx"
                            class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                        >
                            <div>
                                <p class="font-bold text-sm text-sky-950">{{ ev.name }}</p>
                                <p class="text-[11px] text-slate-500 font-medium">Standard reservation rate</p>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="font-display font-black text-base text-orange-600">
                                    ₱{{ Number(ev.price || activeDeck.price).toLocaleString("en-PH") }}
                                </span>
                                <Link
                                    :href="route('client.booking.index', { venue_id: ev.id || activeDeck.id })"
                                    class="px-3 py-1 rounded-lg bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold transition-colors"
                                >
                                    Select
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Deck Inclusions -->
                <div class="space-y-3">
                    <h4 class="font-mono text-xs font-bold uppercase tracking-wider text-slate-700 flex items-center gap-1.5">
                        <font-awesome-icon icon="fa-solid fa-circle-check" class="text-emerald-500" />
                        Deck Amenities & Inclusions
                    </h4>
                    <ul class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs text-slate-600">
                        <li v-for="(inc, idx) in activeDeck.inclusions" :key="idx" class="flex items-start gap-2">
                            <font-awesome-icon icon="fa-solid fa-check" class="text-emerald-500 mt-0.5 text-[10px]" />
                            <span>{{ inc }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Modal Footer -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button
                        @click="closeDeckModal"
                        class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-700 text-xs font-bold hover:bg-slate-50 transition-colors"
                    >
                        Close
                    </button>
                    <Link
                        :href="route('client.booking.index', { venue_id: activeDeck.id })"
                        class="px-6 py-2.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold shadow-sm transition-all"
                    >
                        Proceed to Reservation
                    </Link>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
