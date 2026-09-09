<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link, usePage } from "@inertiajs/vue3";

const page = usePage();

const unreadMessagesCount = computed(
    () => page.props.unreadAdminMessagesCount ?? 0,
);

const user = computed(() => page.props.auth?.user ?? {});

const userInitials = computed(() => {
    const u = user.value;
    const first = u.user_info?.first_name?.[0] || u.name?.[0] || "A";
    const last = u.user_info?.last_name?.[0] || "";
    return (first + last).toUpperCase();
});

const mobileMenuOpen = ref(false);
const activeDropdown = ref(null);

const toggleDropdown = (name) => {
    activeDropdown.value = activeDropdown.value === name ? null : name;
};

const closeDropdowns = (e) => {
    if (!e.target.closest(".nav-dropdown-trigger")) {
        activeDropdown.value = null;
    }
};

onMounted(() => {
    window.addEventListener("click", closeDropdowns);
});

onUnmounted(() => {
    window.removeEventListener("click", closeDropdowns);
});

const isActive = (href) => {
    try {
        if (!href) return false;
        const target = new URL(href, window.location.origin).pathname;
        return page.url === target || page.url.startsWith(target + "/");
    } catch {
        return false;
    }
};

const isGroupActive = (items) => {
    return items && items.some(item => isActive(item.route));
};

const reservationLinks = [
    { label: "Bookings Manifest", route: route("admin.bookings.index"), icon: "fa-solid fa-calendar-check", desc: "View & confirm bookings" },
    { label: "Daily Sales Report", route: route("admin.sales.index"), icon: "fa-solid fa-file-invoice-dollar", desc: "Auditing & financial metrics" },
    { label: "Venue Packages", route: route("admin.venue-packages.index"), icon: "fa-solid fa-hotel", desc: "Manage deck halls & rates" },
    { label: "Event Types", route: route("admin.event-types.index"), icon: "fa-solid fa-cake-candles", desc: "Weddings, debuts, corporate" },
    { label: "Package Add-Ons", route: route("admin.package-add-ons.index"), icon: "fa-solid fa-puzzle-piece", desc: "Sound, lights & catering perks" },
    { label: "Payment Gateways", route: route("admin.payment-options.index"), icon: "fa-solid fa-qrcode", desc: "GCash, Maya & Bank transfers" },
];

const dispatchLinks = [
    { label: "Guest Messages", route: route("admin.messages.index"), icon: "fa-solid fa-comments", desc: "Client chat & direct dispatch", hasBadge: true },
    { label: "Client Directory", route: route("admin.clients.index"), icon: "fa-solid fa-users", desc: "Registered passengers & guests" },
    { label: "News & Announcements", route: route("admin.posts.index"), icon: "fa-solid fa-newspaper", desc: "Public stories & event updates" },
];

const botLinks = [
    { label: "Bot Dialogue Nodes", route: route("admin.chat-nodes.index"), icon: "fa-solid fa-diagram-project", desc: "Conversational replies & promos" },
    { label: "Response Options", route: route("admin.chat-node-options.index"), icon: "fa-solid fa-list-check", desc: "Decision tree button links" },
];

// Footer links for Admin
const footerCols = [
    {
        heading: "Venue & Operations",
        links: [
            { label: "Venue Packages Catalog", href: route("admin.venue-packages.index") },
            { label: "Package Add-Ons", href: route("admin.package-add-ons.index") },
            { label: "Event Types Catalog", href: route("admin.event-types.index") },
            { label: "Payment Channels", href: route("admin.payment-options.index") },
        ],
    },
    {
        heading: "Reservations & Reports",
        links: [
            { label: "Master Bookings Manifest", href: route("admin.bookings.index") },
            { label: "Daily Revenue & Sales", href: route("admin.sales.index") },
            { label: "Passenger & Client Directory", href: route("admin.clients.index") },
            { label: "Dispatch Messenger", href: route("admin.messages.index") },
        ],
    },
    {
        heading: "Automation & System",
        links: [
            { label: "Virtual Concierge Nodes", href: route("admin.chat-nodes.index") },
            { label: "Concierge Decision Trees", href: route("admin.chat-node-options.index") },
            { label: "Officer Profile & Security", href: route("admin.profile") },
            { label: "View Public Website", href: "/" },
        ],
    },
];

const socials = [
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
];
</script>

<template>
    <div class="min-h-screen bg-slate-50/70 text-slate-900 antialiased overflow-x-hidden font-body flex flex-col selection:bg-orange-500 selection:text-white">
        
        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 1. TOP ANNOUNCEMENT BAR (Identical to Welcome.vue)     -->
        <!-- ══════════════════════════════════════════════════════ -->
        <header class="sticky top-0 left-0 right-0 z-40">
            <div class="bg-sky-900 text-sky-100 text-xs py-2 px-4 text-center border-b border-sky-800 hidden sm:block">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <span class="inline-flex items-center gap-2 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <strong class="text-white font-bold">Butal Ship Hauz Command Console</strong> — Talibon, Bohol HQ • Standby Power 100% • Official Operations
                    </span>
                    <a href="/" target="_blank" class="text-amber-300 hover:text-white font-bold transition-colors inline-flex items-center gap-1.5">
                        <span>View Live Website</span>
                        <font-awesome-icon icon="fa-solid fa-arrow-up-right-from-square" class="text-[10px]" />
                    </a>
                </div>
            </div>

            <!-- Main Navbar (Identical clean white style to Welcome.vue) -->
            <nav class="bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm transition-all duration-300">
                <div class="max-w-7xl mx-auto flex items-center justify-between px-4 sm:px-6 lg:px-8 h-20">
                    <!-- Brand Logo -->
                    <Link :href="route('admin.dashboard')" class="flex items-center gap-3.5 no-underline flex-shrink-0 group">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center shadow-md shadow-orange-500/20 group-hover:scale-105 transition-transform duration-300">
                            <font-awesome-icon icon="fa-solid fa-ship" class="text-white text-xl" />
                        </div>
                        <div>
                            <div class="font-display text-slate-900 text-xl font-bold leading-tight tracking-tight flex items-center gap-2">
                                Butal Ship Hauz
                                <span class="bg-orange-100 text-orange-700 text-[10px] font-mono px-2 py-0.5 rounded-full uppercase tracking-wider font-bold">Admin</span>
                            </div>
                            <div class="font-mono text-orange-600 text-[11px] font-bold tracking-[.18em] uppercase leading-none mt-1 flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-location-dot" class="text-[9px]" /> Talibon, Bohol
                            </div>
                        </div>
                    </Link>

                    <!-- Desktop Nav Menu -->
                    <ul class="hidden xl:flex items-center gap-1 list-none m-0 p-0">
                        <!-- Dashboard Link -->
                        <li>
                            <Link
                                :href="route('admin.dashboard')"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 inline-flex items-center gap-2',
                                    isActive(route('admin.dashboard'))
                                        ? 'bg-orange-50 text-orange-600 border border-orange-200 shadow-xs'
                                        : 'text-slate-700 hover:text-orange-600 hover:bg-slate-50'
                                ]"
                            >
                                <font-awesome-icon icon="fa-solid fa-chart-line" />
                                <span>Dashboard</span>
                            </Link>
                        </li>

                        <!-- Reservations & Sales Dropdown -->
                        <li class="relative nav-dropdown-trigger">
                            <button
                                @click.stop="toggleDropdown('reservations')"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 inline-flex items-center gap-2',
                                    isGroupActive(reservationLinks) || activeDropdown === 'reservations'
                                        ? 'bg-orange-50 text-orange-600 border border-orange-200 shadow-xs'
                                        : 'text-slate-700 hover:text-orange-600 hover:bg-slate-50'
                                ]"
                            >
                                <font-awesome-icon icon="fa-solid fa-calendar-check" />
                                <span>Reservations & Sales</span>
                                <font-awesome-icon icon="fa-solid fa-chevron-down" class="text-[10px] transition-transform duration-200" :class="{ 'rotate-180 text-orange-500': activeDropdown === 'reservations' }" />
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-show="activeDropdown === 'reservations'"
                                class="absolute top-full left-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-slate-200/80 p-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                            >
                                <div class="px-3 py-1.5 text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400">
                                    Venue & Transactions
                                </div>
                                <div class="space-y-1">
                                    <Link
                                        v-for="item in reservationLinks"
                                        :key="item.label"
                                        :href="item.route"
                                        :class="[
                                            'flex items-start gap-3 p-2.5 rounded-xl transition-colors',
                                            isActive(item.route)
                                                ? 'bg-orange-50 text-orange-600'
                                                : 'text-slate-700 hover:bg-slate-50 hover:text-orange-600'
                                        ]"
                                    >
                                        <div :class="['w-7 h-7 rounded-lg flex items-center justify-center text-xs flex-shrink-0 mt-0.5', isActive(item.route) ? 'bg-orange-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600']">
                                            <font-awesome-icon :icon="item.icon" />
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold leading-snug">{{ item.label }}</div>
                                            <div class="text-[11px] text-slate-400 font-normal leading-tight">{{ item.desc }}</div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </li>

                        <!-- Operations & Dispatch Dropdown -->
                        <li class="relative nav-dropdown-trigger">
                            <button
                                @click.stop="toggleDropdown('dispatch')"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 inline-flex items-center gap-2 relative',
                                    isGroupActive(dispatchLinks) || activeDropdown === 'dispatch'
                                        ? 'bg-orange-50 text-orange-600 border border-orange-200 shadow-xs'
                                        : 'text-slate-700 hover:text-orange-600 hover:bg-slate-50'
                                ]"
                            >
                                <font-awesome-icon icon="fa-solid fa-tower-broadcast" />
                                <span>Operations & CMS</span>
                                <span
                                    v-if="unreadMessagesCount > 0"
                                    class="px-1.5 py-0.5 text-[10px] font-black rounded-full bg-rose-500 text-white leading-none shadow-sm"
                                >
                                    {{ unreadMessagesCount }}
                                </span>
                                <font-awesome-icon icon="fa-solid fa-chevron-down" class="text-[10px] transition-transform duration-200" :class="{ 'rotate-180 text-orange-500': activeDropdown === 'dispatch' }" />
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-show="activeDropdown === 'dispatch'"
                                class="absolute top-full left-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-slate-200/80 p-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                            >
                                <div class="px-3 py-1.5 text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400">
                                    Communications & Content
                                </div>
                                <div class="space-y-1">
                                    <Link
                                        v-for="item in dispatchLinks"
                                        :key="item.label"
                                        :href="item.route"
                                        :class="[
                                            'flex items-start gap-3 p-2.5 rounded-xl transition-colors',
                                            isActive(item.route)
                                                ? 'bg-orange-50 text-orange-600'
                                                : 'text-slate-700 hover:bg-slate-50 hover:text-orange-600'
                                        ]"
                                    >
                                        <div :class="['w-7 h-7 rounded-lg flex items-center justify-center text-xs flex-shrink-0 mt-0.5', isActive(item.route) ? 'bg-orange-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600']">
                                            <font-awesome-icon :icon="item.icon" />
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-xs font-bold leading-snug flex items-center justify-between">
                                                <span>{{ item.label }}</span>
                                                <span
                                                    v-if="item.hasBadge && unreadMessagesCount > 0"
                                                    class="px-1.5 py-0.5 text-[9px] font-bold rounded-full bg-rose-500 text-white"
                                                >
                                                    {{ unreadMessagesCount }}
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-400 font-normal leading-tight">{{ item.desc }}</div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </li>

                        <!-- Virtual Concierge / Bot Dropdown -->
                        <li class="relative nav-dropdown-trigger">
                            <button
                                @click.stop="toggleDropdown('chatbot')"
                                :class="[
                                    'px-3.5 py-2 rounded-xl text-xs font-bold transition-all duration-150 inline-flex items-center gap-2',
                                    isGroupActive(botLinks) || activeDropdown === 'chatbot'
                                        ? 'bg-orange-50 text-orange-600 border border-orange-200 shadow-xs'
                                        : 'text-slate-700 hover:text-orange-600 hover:bg-slate-50'
                                ]"
                            >
                                <font-awesome-icon icon="fa-solid fa-robot" />
                                <span>Virtual Concierge</span>
                                <font-awesome-icon icon="fa-solid fa-chevron-down" class="text-[10px] transition-transform duration-200" :class="{ 'rotate-180 text-orange-500': activeDropdown === 'chatbot' }" />
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-show="activeDropdown === 'chatbot'"
                                class="absolute top-full left-0 mt-2 w-72 bg-white rounded-2xl shadow-xl border border-slate-200/80 p-2 z-50 animate-in fade-in slide-in-from-top-2 duration-150"
                            >
                                <div class="px-3 py-1.5 text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400">
                                    Chatbot Knowledge Base
                                </div>
                                <div class="space-y-1">
                                    <Link
                                        v-for="item in botLinks"
                                        :key="item.label"
                                        :href="item.route"
                                        :class="[
                                            'flex items-start gap-3 p-2.5 rounded-xl transition-colors',
                                            isActive(item.route)
                                                ? 'bg-orange-50 text-orange-600'
                                                : 'text-slate-700 hover:bg-slate-50 hover:text-orange-600'
                                        ]"
                                    >
                                        <div :class="['w-7 h-7 rounded-lg flex items-center justify-center text-xs flex-shrink-0 mt-0.5', isActive(item.route) ? 'bg-orange-500 text-white shadow-sm' : 'bg-slate-100 text-slate-600']">
                                            <font-awesome-icon :icon="item.icon" />
                                        </div>
                                        <div>
                                            <div class="text-xs font-bold leading-snug">{{ item.label }}</div>
                                            <div class="text-[11px] text-slate-400 font-normal leading-tight">{{ item.desc }}</div>
                                        </div>
                                    </Link>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <!-- Right Controls: Profile & Sign Out -->
                    <div class="hidden sm:flex items-center gap-3">
                        <!-- Profile Card Link -->
                        <Link
                            :href="route('admin.profile')"
                            :class="[
                                'flex items-center gap-2.5 px-3.5 py-1.5 rounded-full border transition-all duration-200',
                                isActive(route('admin.profile'))
                                    ? 'bg-orange-50 border-orange-300 shadow-xs'
                                    : 'bg-slate-50 hover:bg-slate-100 border-slate-200'
                            ]"
                        >
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 text-white font-bold text-xs flex items-center justify-center shadow-xs">
                                {{ userInitials }}
                            </div>
                            <div class="text-left leading-none">
                                <div class="text-xs font-bold text-slate-800 line-clamp-1 max-w-[110px]">
                                    {{ user.name || 'Ship Admin' }}
                                </div>
                                <div class="text-[10px] font-mono text-orange-600 font-semibold tracking-wider uppercase mt-0.5">
                                    Duty Officer
                                </div>
                            </div>
                        </Link>

                        <!-- Sign Out Button -->
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-rose-600 bg-slate-100 hover:bg-rose-50 px-3.5 py-2 rounded-xl transition-all duration-200 border border-slate-200 hover:border-rose-200 cursor-pointer"
                            title="Sign out of Admin Console"
                        >
                            <font-awesome-icon icon="fa-solid fa-arrow-right-from-bracket" />
                            <span class="hidden md:inline">Sign Out</span>
                        </Link>
                    </div>

                    <!-- Mobile Hamburger Button -->
                    <div class="xl:hidden flex items-center gap-2">
                        <Link
                            v-if="unreadMessagesCount > 0"
                            :href="route('admin.messages.index')"
                            class="p-2 text-rose-600 relative"
                        >
                            <font-awesome-icon icon="fa-solid fa-envelope" class="text-lg" />
                            <span class="absolute top-1 right-1 w-2.5 h-2.5 rounded-full bg-rose-500 ring-2 ring-white"></span>
                        </Link>
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="p-2.5 rounded-xl text-slate-700 hover:text-orange-600 hover:bg-slate-100 transition-colors"
                            aria-label="Toggle Navigation Menu"
                        >
                            <font-awesome-icon :icon="mobileMenuOpen ? 'fa-solid fa-xmark' : 'fa-solid fa-bars'" class="text-xl" />
                        </button>
                    </div>
                </div>

                <!-- Mobile Slide-Out / Dropdown Drawer -->
                <div
                    v-show="mobileMenuOpen"
                    class="xl:hidden border-t border-slate-200 bg-white px-4 py-4 space-y-4 max-h-[calc(100vh-5rem)] overflow-y-auto"
                >
                    <!-- Officer Profile Banner -->
                    <div class="flex items-center justify-between p-3 rounded-2xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 text-white font-bold flex items-center justify-center">
                                {{ userInitials }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-900 text-sm">{{ user.name || 'Ship Admin' }}</div>
                                <div class="text-xs text-orange-600 font-mono font-semibold">{{ user.email || 'Admin Console' }}</div>
                            </div>
                        </div>
                        <Link
                            :href="route('admin.profile')"
                            @click="mobileMenuOpen = false"
                            class="text-xs font-bold text-slate-600 bg-white border border-slate-200 px-3 py-1.5 rounded-lg"
                        >
                            Profile
                        </Link>
                    </div>

                    <!-- Navigation Links Accordion -->
                    <div class="space-y-4">
                        <!-- Dashboard -->
                        <Link
                            :href="route('admin.dashboard')"
                            @click="mobileMenuOpen = false"
                            :class="[
                                'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-bold',
                                isActive(route('admin.dashboard')) ? 'bg-orange-500 text-white' : 'text-slate-700 bg-slate-50'
                            ]"
                        >
                            <font-awesome-icon icon="fa-solid fa-chart-line" />
                            <span>Dashboard Overview</span>
                        </Link>

                        <!-- Reservations & Sales -->
                        <div>
                            <div class="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-400 px-3 mb-1.5">
                                Reservations & Sales
                            </div>
                            <div class="space-y-1">
                                <Link
                                    v-for="item in reservationLinks"
                                    :key="item.label"
                                    :href="item.route"
                                    @click="mobileMenuOpen = false"
                                    :class="[
                                        'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold',
                                        isActive(item.route) ? 'bg-orange-50 text-orange-600 font-bold' : 'text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    <font-awesome-icon :icon="item.icon" class="w-4 text-center text-slate-400" />
                                    <span>{{ item.label }}</span>
                                </Link>
                            </div>
                        </div>

                        <!-- Operations & CMS -->
                        <div>
                            <div class="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-400 px-3 mb-1.5">
                                Operations & CMS
                            </div>
                            <div class="space-y-1">
                                <Link
                                    v-for="item in dispatchLinks"
                                    :key="item.label"
                                    :href="item.route"
                                    @click="mobileMenuOpen = false"
                                    :class="[
                                        'flex items-center justify-between px-3 py-2 rounded-xl text-xs font-semibold',
                                        isActive(item.route) ? 'bg-orange-50 text-orange-600 font-bold' : 'text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    <div class="flex items-center gap-3">
                                        <font-awesome-icon :icon="item.icon" class="w-4 text-center text-slate-400" />
                                        <span>{{ item.label }}</span>
                                    </div>
                                    <span
                                        v-if="item.hasBadge && unreadMessagesCount > 0"
                                        class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-500 text-white"
                                    >
                                        {{ unreadMessagesCount }}
                                    </span>
                                </Link>
                            </div>
                        </div>

                        <!-- Virtual Concierge -->
                        <div>
                            <div class="text-[11px] font-mono font-bold uppercase tracking-wider text-slate-400 px-3 mb-1.5">
                                Virtual Concierge
                            </div>
                            <div class="space-y-1">
                                <Link
                                    v-for="item in botLinks"
                                    :key="item.label"
                                    :href="item.route"
                                    @click="mobileMenuOpen = false"
                                    :class="[
                                        'flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold',
                                        isActive(item.route) ? 'bg-orange-50 text-orange-600 font-bold' : 'text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    <font-awesome-icon :icon="item.icon" class="w-4 text-center text-slate-400" />
                                    <span>{{ item.label }}</span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Sign Out Mobile -->
                    <div class="pt-2 border-t border-slate-200">
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs transition-colors"
                        >
                            <font-awesome-icon icon="fa-solid fa-arrow-right-from-bracket" />
                            <span>Sign Out of Console</span>
                        </Link>
                    </div>
                </div>
            </nav>
        </header>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 2. MAIN ADMIN CONTENT CONTAINER                        -->
        <!-- ══════════════════════════════════════════════════════ -->
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 relative">
            <!-- Subtle Nautical Background Accent Pattern -->
            <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden opacity-[0.03]">
                <div class="absolute -top-40 right-0 w-[600px] h-[600px] rounded-full border-[60px] border-slate-900"></div>
                <div class="absolute top-1/2 left-0 w-[450px] h-[450px] rounded-full border-[40px] border-slate-900"></div>
            </div>

            <div class="relative z-10">
                <slot />
            </div>
        </main>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 3. MARITIME LUXURY FOOTER (Identical to Welcome.vue)   -->
        <!-- ══════════════════════════════════════════════════════ -->
        <footer class="bg-slate-900 text-slate-400 pt-16 pb-12 border-t-2 border-orange-500 mt-auto">
            <div class="max-w-7xl mx-auto px-6 md:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 pb-12 border-b border-slate-800">
                    
                    <!-- Col 1: Brand & Identity -->
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center shadow-lg shadow-orange-500/30">
                                <font-awesome-icon icon="fa-solid fa-ship" class="text-white text-lg" />
                            </div>
                            <div>
                                <span class="font-display text-white text-xl font-bold tracking-tight">Butal Ship Hauz</span>
                                <div class="font-mono text-orange-400 text-xs font-semibold tracking-wider uppercase">Talibon, Bohol</div>
                            </div>
                        </div>
                        <p class="text-slate-400 text-sm leading-relaxed">
                            A one-of-a-kind ship-themed landmark in Bohol offering grand ballrooms, open-air promenade decks, and premium amenities for life's greatest celebrations.
                        </p>
                        <!-- Socials -->
                        <div class="flex items-center gap-3 pt-2">
                            <a
                                v-for="s in socials"
                                :key="s.label"
                                :href="s.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                :aria-label="s.label"
                                class="w-9 h-9 rounded-xl bg-slate-800 hover:bg-orange-500 hover:text-white text-slate-300 flex items-center justify-center transition-all duration-200 border border-slate-700"
                            >
                                <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" v-html="s.icon" />
                            </a>
                        </div>
                    </div>

                    <!-- Col 2, 3, 4: Categorized Links -->
                    <div v-for="col in footerCols" :key="col.heading" class="space-y-4">
                        <h4 class="font-serif text-white font-bold text-sm tracking-wider uppercase border-b border-slate-800 pb-2">
                            {{ col.heading }}
                        </h4>
                        <ul class="space-y-2.5 list-none p-0 m-0">
                            <li v-for="link in col.links" :key="link.label">
                                <Link
                                    :href="link.href"
                                    class="text-slate-400 hover:text-orange-400 text-sm transition-colors duration-200 inline-flex items-center gap-2 group"
                                >
                                    <span class="text-orange-500 text-xs group-hover:translate-x-0.5 transition-transform">›</span>
                                    <span>{{ link.label }}</span>
                                </Link>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Footer Bottom Bar -->
                <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-slate-400">
                    <p class="m-0">
                        &copy; 2026 Butal Ship Hauz. All rights reserved. • Primary Vessel Operations & Booking Console
                    </p>
                    <div class="flex items-center gap-6">
                        <span class="text-slate-400 font-mono text-[11px]">Bohol, Philippines</span>
                        <a href="/" target="_blank" class="text-orange-400 hover:underline font-bold">Public Portal ↗</a>
                    </div>
                </div>
            </div>
        </footer>

    </div>
</template>
