<script setup>
import { ref, computed } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import ChatBot from "@/Components/ChatBot.vue";

const page = usePage();

const unreadMessagesCount = computed(
    () => page.props.unreadClientMessagesCount ?? 0,
);

const user = computed(() => page.props.auth.user);

// Mobile drawer state
const isMobileMenuOpen = ref(false);

const navMenus = [
    {
        menuName: "Deck Feeds",
        route: route("client.home"),
        icon: "fa-solid fa-compass",
        desc: "Browse venue decks & packages",
    },
    {
        menuName: "Reservations",
        route: route("client.booking.index"),
        icon: "fa-solid fa-calendar-check",
        desc: "Bookings & deck availability",
    },
    {
        menuName: "Shipboard Alerts",
        route: route("client.notifications.index"),
        icon: "fa-solid fa-bell",
        desc: "Messages & booking updates",
        hasBadge: true,
    },
];

const isActive = (href) => {
    try {
        if (!href) return false;
        const target = new URL(href, window.location.origin).pathname;
        return page.url === target || page.url.startsWith(target + "/");
    } catch {
        return false;
    }
};

const footerCols = [
    {
        heading: "Passenger Services",
        links: [
            { label: "Available Decks & Feeds", href: route("client.home") },
            { label: "Book a Deck Reservation", href: route("client.booking.index") },
            { label: "Notifications & Signals", href: route("client.notifications.index") },
            { label: "Passenger Profile & Stateroom", href: route("client.profile.index") },
        ],
    },
    {
        heading: "Event Shifts & Hours",
        links: [
            { label: "Morning Shift: 8:00 AM – 12:00 PM", href: route("client.booking.index") },
            { label: "Afternoon Shift: 1:00 PM – 5:00 PM", href: route("client.booking.index") },
            { label: "Night Gala Shift: 6:00 PM – 10:00 PM", href: route("client.booking.index") },
            { label: "Full Day Exclusive: 8:00 AM – 10:00 PM", href: route("client.booking.index") },
        ],
    },
    {
        heading: "Ship Hauz Info",
        links: [
            { label: "Public Website & Virtual Tour", href: route("landing-page") },
            { label: "Location: Talibon, Bohol, Philippines", href: route("landing-page") },
            { label: "Open-Air Natural Coastal Breeze", href: route("landing-page") },
            { label: "Commercial Standby Generator Ready", href: route("landing-page") },
        ],
    },
];
</script>

<template>
    <div class="flex flex-col min-h-screen bg-slate-50 font-body text-slate-800">
        <!-- ── TOP MARITIME TICKER RIBBON ── -->
        <aside class="bg-gradient-to-r from-sky-950 via-slate-900 to-sky-950 text-white text-[11px] font-mono border-b border-sky-900/60 shrink-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-1.5 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2 sm:gap-3 truncate">
                    <span class="inline-flex items-center gap-1 text-orange-400 font-bold tracking-wider uppercase text-[10px]">
                        <font-awesome-icon icon="fa-solid fa-anchor" class="text-xs" />
                        Passenger Portal
                    </span>
                    <span class="hidden md:inline text-slate-600">•</span>
                    <span class="hidden md:inline text-slate-300">Port of Talibon, Bohol</span>
                    <span class="hidden sm:inline text-slate-600">•</span>
                    <span class="hidden sm:inline text-slate-400">Natural Ocean Breeze & Bay Views</span>
                </div>

                <div class="flex items-center gap-4 text-slate-300 shrink-0 text-[10px] sm:text-[11px]">
                    <span class="hidden lg:inline text-sky-300">
                        <font-awesome-icon icon="fa-solid fa-clock" class="mr-1 text-orange-400" />
                        Shifts: Morning (8–12) • Afternoon (1–5) • Night (6–10)
                    </span>
                    <Link
                        :href="route('landing-page')"
                        class="hover:text-orange-400 transition-colors flex items-center gap-1 text-slate-300 font-bold"
                    >
                        <font-awesome-icon icon="fa-solid fa-globe" class="text-xs text-orange-400" />
                        <span class="hidden sm:inline">Explore</span> Public Site
                    </Link>
                </div>
            </div>
        </aside>

        <!-- ── MAIN TOP NAVIGATION BAR ── -->
        <header class="sticky top-0 z-30 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-[74px] flex items-center justify-between gap-4">
                <!-- Brand / Logo -->
                <div class="flex items-center gap-6 lg:gap-8 h-full">
                    <Link :href="route('client.home')" class="flex items-center gap-3 group no-underline shrink-0">
                        <div class="w-10 h-10 sm:w-11 sm:h-11 rounded-xl bg-gradient-to-br from-sky-900 to-sky-700 flex items-center justify-center shadow-md shadow-sky-900/20 border border-sky-600/30 group-hover:scale-105 transition-transform">
                            <svg class="w-6 h-6 fill-orange-400 drop-shadow-sm" viewBox="0 0 48 48">
                                <path d="M24 6a6 6 0 1 0 0 12A6 6 0 0 0 24 6zm0 8a2 2 0 1 1 0-4 2 2 0 0 1 0 4zm0 6c-1.1 0-2 .9-2 2v16.5C15 37 8.5 31 8.5 24H12c.83 0 1.5-.67 1.5-1.5S12.83 21 12 21H6c-.83 0-1.5.67-1.5 1.5S5.17 24 6 24c0 8.84 7.16 16 16 16s16-7.16 16-16h2.5a1.5 1.5 0 0 0 0-3H36c-.83 0-1.5.67-1.5 1.5S35.17 24 36 24c0 7-6.5 13-13.5 13.5V22c0-1.1-.9-2-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <span class="font-display font-black text-sky-950 text-lg sm:text-xl tracking-tight leading-tight block">
                                BUTAL SHIP HAUZ
                            </span>
                            <span class="font-mono text-[10px] font-bold text-slate-400 tracking-[0.16em] uppercase block">
                                Passenger Lounge
                            </span>
                        </div>
                    </Link>

                    <!-- Desktop Nav Links -->
                    <nav class="hidden md:flex items-center gap-1.5 h-full">
                        <Link
                            v-for="menu in navMenus"
                            :key="menu.menuName"
                            :href="menu.route"
                            :class="[
                                'relative flex items-center gap-2.5 px-4 py-2 rounded-xl transition-all duration-200 text-sm font-bold',
                                isActive(menu.route)
                                    ? 'bg-sky-900 text-white shadow-md shadow-sky-900/20'
                                    : 'text-slate-600 hover:text-sky-900 hover:bg-slate-100/80'
                            ]"
                        >
                            <div class="relative flex items-center justify-center">
                                <font-awesome-icon
                                    :icon="menu.icon"
                                    class="text-sm"
                                    :class="isActive(menu.route) ? 'text-orange-400' : 'text-slate-400 group-hover:text-sky-700'"
                                />
                                <span
                                    v-if="menu.hasBadge && unreadMessagesCount > 0"
                                    class="absolute -top-2 -right-2.5 min-w-[18px] h-[18px] px-1 bg-orange-500 text-white text-[10px] font-mono font-bold rounded-full flex items-center justify-center ring-2 ring-white"
                                >
                                    {{ unreadMessagesCount }}
                                </span>
                            </div>
                            <span>{{ menu.menuName }}</span>
                        </Link>
                    </nav>
                </div>

                <!-- Right Actions: Booking CTA, Passenger Profile Pill, Logout -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Quick Reserve Button (Desktop) -->
                    <Link
                        :href="route('client.booking.index')"
                        class="hidden sm:inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold bg-gradient-to-r from-orange-500 to-amber-500 text-white hover:from-orange-600 hover:to-amber-600 shadow-sm shadow-orange-500/25 transition-all hover:scale-[1.02]"
                    >
                        <font-awesome-icon icon="fa-solid fa-plus-circle" />
                        <span>Reserve Deck</span>
                    </Link>

                    <!-- User Profile Pill -->
                    <Link
                        v-if="user"
                        :href="route('client.profile.index')"
                        class="flex items-center gap-2.5 p-1.5 sm:px-3 sm:py-1.5 rounded-xl border border-slate-200 hover:border-sky-300 hover:bg-sky-50/50 transition-all group"
                        title="View Passenger Profile"
                    >
                        <div class="relative">
                            <img
                                v-if="user.profile_photo"
                                :src="'/storage/' + user.profile_photo"
                                alt="Profile"
                                class="w-8 h-8 rounded-lg object-cover border border-orange-400 shadow-xs"
                            />
                            <div
                                v-else
                                class="w-8 h-8 rounded-lg bg-gradient-to-br from-sky-800 to-sky-600 text-white flex items-center justify-center font-bold text-xs shadow-xs"
                            >
                                {{ user.name ? user.name.charAt(0).toUpperCase() : 'P' }}
                            </div>
                            <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 bg-emerald-500 border-2 border-white rounded-full"></span>
                        </div>

                        <div class="hidden lg:block text-left min-w-0 max-w-[120px]">
                            <p class="text-xs font-bold text-slate-800 truncate leading-tight group-hover:text-sky-900">
                                {{ user.name }}
                            </p>
                            <p class="font-mono text-[9px] font-semibold text-slate-400 uppercase tracking-wider leading-none mt-0.5">
                                Passenger
                            </p>
                        </div>
                    </Link>

                    <!-- Logout Button -->
                    <Link
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="w-9 h-9 sm:w-10 sm:h-10 text-slate-500 hover:text-red-600 hover:bg-red-50 rounded-xl border border-slate-200 hover:border-red-200 transition-colors flex items-center justify-center shadow-xs"
                        title="Sign Out of Portal"
                    >
                        <font-awesome-icon icon="fa-solid fa-right-from-bracket" class="text-sm" />
                    </Link>

                    <!-- Mobile Hamburger Toggle -->
                    <button
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        class="md:hidden w-9 h-9 sm:w-10 sm:h-10 text-slate-600 hover:text-sky-900 hover:bg-slate-100 rounded-xl border border-slate-200 flex items-center justify-center transition-colors"
                        aria-label="Toggle Navigation Drawer"
                    >
                        <font-awesome-icon :icon="isMobileMenuOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'" class="text-base" />
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation Drawer -->
            <div
                v-if="isMobileMenuOpen"
                class="md:hidden border-t border-slate-200 bg-white px-4 py-3 space-y-1.5 shadow-xl animate-in slide-in-from-top-2 duration-200"
            >
                <Link
                    v-for="menu in navMenus"
                    :key="menu.menuName"
                    :href="menu.route"
                    @click="isMobileMenuOpen = false"
                    :class="[
                        'flex items-center justify-between px-4 py-3 rounded-xl text-sm font-bold transition-all',
                        isActive(menu.route)
                            ? 'bg-sky-900 text-white shadow-md'
                            : 'text-slate-600 hover:bg-slate-100'
                    ]"
                >
                    <div class="flex items-center gap-3">
                        <font-awesome-icon :icon="menu.icon" class="w-4 text-base" :class="isActive(menu.route) ? 'text-orange-400' : 'text-slate-400'" />
                        <div>
                            <span>{{ menu.menuName }}</span>
                            <span class="block text-[11px] font-normal opacity-80">{{ menu.desc }}</span>
                        </div>
                    </div>
                    <span
                        v-if="menu.hasBadge && unreadMessagesCount > 0"
                        class="px-2 py-0.5 rounded-full bg-orange-500 text-white font-mono text-xs font-bold"
                    >
                        {{ unreadMessagesCount }} new
                    </span>
                </Link>

                <div class="pt-2 border-t border-slate-100 flex items-center gap-2">
                    <Link
                        :href="route('client.profile.index')"
                        @click="isMobileMenuOpen = false"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold"
                    >
                        <font-awesome-icon icon="fa-solid fa-user-gear" />
                        <span>My Profile</span>
                    </Link>
                    <Link
                        :href="route('client.booking.index')"
                        @click="isMobileMenuOpen = false"
                        class="flex-1 flex items-center justify-center gap-2 py-2.5 px-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold"
                    >
                        <font-awesome-icon icon="fa-solid fa-plus-circle" />
                        <span>Reserve Deck</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- ── MAIN CONTENT CONTAINER WITH NAUTICAL WATERMARK ── -->
        <main class="flex-1 relative z-10">
            <!-- Background Compass Watermark -->
            <div class="pointer-events-none fixed inset-0 flex items-center justify-center z-[-1] overflow-hidden opacity-30 select-none">
                <svg class="w-[380px] h-[380px] sm:w-[580px] sm:h-[580px] text-sky-900/[0.04]" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v3h-2zm0 10h2v3h-2zm-6-6h3v2H5zm11 0h3v2h-3zm-2.71-3.71l1.41-1.41 2.12 2.12-1.41 1.41zm-6.58 6.58l1.41-1.41 2.12 2.12-1.41 1.41zm6.58 0l1.41 1.41-2.12 2.12-1.41-1.41zm-6.58-6.58l1.41 1.41-2.12 2.12-1.41-1.41z" />
                </svg>
            </div>

            <!-- Page Content Slot -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
                <slot />
            </div>
        </main>

        <!-- ── RICH MARITIME LUXURY FOOTER ── -->
        <footer class="bg-slate-900 text-slate-300 border-t border-slate-800 shrink-0 mt-auto">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                    <!-- Brand info -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-sky-800 to-sky-600 flex items-center justify-center border border-sky-400/30">
                                <font-awesome-icon icon="fa-solid fa-anchor" class="text-orange-400 text-lg" />
                            </div>
                            <div>
                                <span class="font-display font-bold text-white text-lg tracking-tight block">
                                    Butal Ship Hauz
                                </span>
                                <span class="font-mono text-[10px] text-sky-400 tracking-widest uppercase block">
                                    Port of Talibon, Bohol
                                </span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            A premier maritime-styled event landmark in Bohol. Featuring open-air sea breeze panoramic decks, professional stage & lighting systems, and commercial backup generator support.
                        </p>
                        <div class="pt-2 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-sky-950 border border-sky-800 text-[10px] font-mono text-sky-300">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                Passenger Portal Live
                            </span>
                        </div>
                    </div>

                    <!-- Dynamic Navigation Columns -->
                    <div v-for="(col, idx) in footerCols" :key="idx" class="space-y-3">
                        <h4 class="font-mono text-xs font-bold text-orange-400 tracking-wider uppercase">
                            {{ col.heading }}
                        </h4>
                        <ul class="space-y-2">
                            <li v-for="(item, i) in col.links" :key="i">
                                <Link
                                    :href="item.href"
                                    class="text-xs text-slate-400 hover:text-white transition-colors block leading-relaxed"
                                >
                                    {{ item.label }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Bottom Copyright Strip -->
                <div class="mt-10 pt-6 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-[11px] font-mono text-slate-500">
                    <p>© {{ new Date().getFullYear() }} Butal Ship Hauz. All rights reserved. Talibon, Bohol, Philippines.</p>
                    <div class="flex items-center gap-4">
                        <span>Natural Coastal Sea Breeze Ambiance</span>
                        <span>•</span>
                        <Link :href="route('landing-page')" class="hover:text-slate-300">Public Portal</Link>
                    </div>
                </div>
            </div>
        </footer>

        <!-- Interactive AI Chatbot Concierge -->
        <ChatBot />
    </div>
</template>
