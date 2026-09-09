<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import ChatBot from "@/Components/ChatBot.vue";

const props = defineProps({
    maxWidth: {
        type: String,
        default: "xl",
    },
});

const mobileMenuOpen = ref(false);

const maxWidthClass = computed(() => {
    switch (props.maxWidth) {
        case "sm":
            return "sm:max-w-sm";
        case "md":
            return "sm:max-w-md";
        case "lg":
            return "sm:max-w-lg";
        case "xl":
            return "sm:max-w-xl";
        case "2xl":
            return "sm:max-w-2xl";
        case "3xl":
            return "sm:max-w-3xl";
        case "4xl":
            return "sm:max-w-4xl";
        default:
            return "sm:max-w-xl";
    }
});

const isRegisterPage = computed(() => {
    try {
        if (typeof route === "function") {
            return route().current("register");
        }
    } catch {
        // Fallback
    }
    return typeof window !== "undefined" && window.location.pathname.includes("register");
});

// ── Footer Column Links ──
const footerCols = [
    {
        heading: "Venue & Spaces",
        links: [
            { label: "Grand Ballroom Deck", href: "/#venues" },
            { label: "Sunset Promenade", href: "/#venues" },
            { label: "Captain's Function Hall", href: "/#venues" },
            { label: "Venue Pricing & Rates", href: "/#venues" },
            { label: "Technical Inclusions", href: "/#amenities" },
        ],
    },
    {
        heading: "Planning Your Event",
        links: [
            { label: "How to Book", href: "/#how-it-works" },
            { label: "Occasions & Celebrations", href: "/#occasions" },
            { label: "Frequently Asked Questions", href: "/#faq" },
            { label: "Schedule an Ocular Visit", href: "/#contact" },
            { label: "News & Stories", href: "/#blog" },
        ],
    },
    {
        heading: "Policies & Terms",
        links: [
            { label: "Reservation & Deposit Policy", href: "/#faq" },
            { label: "Ingress & Egress Guidelines", href: "/#faq" },
            { label: "Catering & Sound Guidelines", href: "/#faq" },
            { label: "Terms of Service", href: "/#" },
            { label: "Privacy Policy", href: "/#" },
        ],
    },
];

// ── Social Media Channels ──
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
    <div class="min-h-screen bg-white text-slate-900 antialiased overflow-x-hidden font-body flex flex-col selection:bg-orange-500 selection:text-white">
        
        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 1. TOP ANNOUNCEMENT BAR (Identical to Welcome.vue)     -->
        <!-- ══════════════════════════════════════════════════════ -->
        <header class="sticky top-0 left-0 right-0 z-40">
            <!-- Top Announcement Bar -->
            <div class="bg-sky-900 text-sky-100 text-xs py-2 px-4 text-center border-b border-sky-800 hidden sm:block">
                <div class="max-w-7xl mx-auto flex items-center justify-between">
                    <span class="inline-flex items-center gap-2 font-medium">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <strong class="text-white font-bold">Accepting Bookings for 2026 & 2027</strong> — Reserve your event date with our flexible 50% downpayment!
                    </span>
                    <a href="/#contact" class="text-amber-300 hover:text-white underline font-bold transition-colors">
                        Book an Ocular Visit &rarr;
                    </a>
                </div>
            </div>

            <!-- Main Navbar (Clean White & Slate with Maritime Accents) -->
            <nav class="bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm transition-all duration-300">
                <div class="max-w-7xl mx-auto flex items-center justify-between px-6 md:px-8 h-20">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-3.5 no-underline flex-shrink-0 group">
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
                    </Link>

                    <!-- Desktop Nav Links (pointing to Welcome page sections) -->
                    <ul class="hidden lg:flex items-center gap-7 list-none m-0 p-0">
                        <li>
                            <a href="/#venues" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                Venue Spaces
                            </a>
                        </li>
                        <li>
                            <a href="/#how-it-works" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                How to Book
                            </a>
                        </li>
                        <li>
                            <a href="/#occasions" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                Occasions
                            </a>
                        </li>
                        <li>
                            <a href="/#amenities" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                Inclusions & Perks
                            </a>
                        </li>
                        <li>
                            <a href="/#faq" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                FAQs
                            </a>
                        </li>
                        <li>
                            <a href="/#contact" class="nav-link text-sm font-bold text-slate-700 hover:text-orange-600 transition-colors duration-200">
                                Contact & Map
                            </a>
                        </li>
                    </ul>

                    <!-- Action Buttons -->
                    <div class="hidden sm:flex items-center gap-3">
                        <Link 
                            href="/" 
                            class="inline-flex items-center gap-2 text-sm font-bold text-slate-700 hover:text-orange-600 px-3 py-2 transition-colors"
                        >
                            <font-awesome-icon icon="fa-solid fa-arrow-left" class="text-orange-500 text-xs" />
                            <span>Return to Home</span>
                        </Link>

                        <Link 
                            v-if="!isRegisterPage" 
                            :href="route('register')" 
                            class="inline-flex items-center gap-2 text-sm font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 px-5 py-2.5 rounded-full transition-all duration-200 shadow-md shadow-orange-500/25 hover:-translate-y-0.5"
                        >
                            <font-awesome-icon icon="fa-solid fa-calendar-check" /> Reserve Venue
                        </Link>
                        <Link 
                            v-else 
                            :href="route('login')" 
                            class="inline-flex items-center gap-2 text-sm font-bold text-white bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 px-5 py-2.5 rounded-full transition-all duration-200 shadow-md shadow-orange-500/25 hover:-translate-y-0.5"
                        >
                            <font-awesome-icon icon="fa-solid fa-right-to-bracket" /> Sign In
                        </Link>
                    </div>

                    <!-- Mobile Hamburger Toggle -->
                    <div class="flex lg:hidden items-center gap-2">
                        <button
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            class="w-11 h-11 flex flex-col items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-slate-100 text-slate-800 hover:bg-slate-200 transition-colors"
                            aria-label="Toggle navigation menu"
                        >
                            <span :class="['w-5 h-0.5 bg-slate-800 transition-transform duration-300', mobileMenuOpen ? 'rotate-45 translate-y-2' : '']"></span>
                            <span :class="['w-5 h-0.5 bg-slate-800 transition-opacity duration-300', mobileMenuOpen ? 'opacity-0' : '']"></span>
                            <span :class="['w-5 h-0.5 bg-slate-800 transition-transform duration-300', mobileMenuOpen ? '-rotate-45 -translate-y-2' : '']"></span>
                        </button>
                    </div>
                </div>

                <!-- Mobile Menu Drawer -->
                <transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-2"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-2"
                >
                    <div v-if="mobileMenuOpen" class="lg:hidden bg-white border-b border-slate-200 px-6 py-5 shadow-lg">
                        <ul class="flex flex-col gap-4 list-none m-0 p-0 text-sm font-bold text-slate-800">
                            <li><a href="/#venues" @click="mobileMenuOpen = false" class="hover:text-orange-600 block py-1">Venue Spaces</a></li>
                            <li><a href="/#how-it-works" @click="mobileMenuOpen = false" class="hover:text-orange-600 block py-1">How to Book</a></li>
                            <li><a href="/#occasions" @click="mobileMenuOpen = false" class="hover:text-orange-600 block py-1">Occasions</a></li>
                            <li><a href="/#amenities" @click="mobileMenuOpen = false" class="hover:text-orange-600 block py-1">Inclusions & Perks</a></li>
                            <li><a href="/#faq" @click="mobileMenuOpen = false" class="hover:text-orange-600 block py-1">FAQs</a></li>
                            <li><a href="/#contact" @click="mobileMenuOpen = false" class="hover:text-orange-600 block py-1">Contact & Map</a></li>
                            <li class="pt-3 border-t border-slate-200 flex flex-col gap-2.5">
                                <Link href="/" class="text-center py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold hover:bg-slate-50">
                                    Return to Home
                                </Link>
                                <Link v-if="!isRegisterPage" :href="route('register')" class="text-center py-2.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 text-white font-bold shadow-md">
                                    Reserve Venue
                                </Link>
                                <Link v-else :href="route('login')" class="text-center py-2.5 rounded-xl bg-gradient-to-r from-orange-500 to-amber-500 text-white font-bold shadow-md">
                                    Sign In
                                </Link>
                            </li>
                        </ul>
                    </div>
                </transition>
            </nav>
        </header>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 2. MAIN BODY SECTION (Coastal Ambience & Card)          -->
        <!-- ══════════════════════════════════════════════════════ -->
        <main class="flex-1 relative flex flex-col items-center justify-center py-12 sm:py-16 px-4 sm:px-6 lg:px-8 overflow-hidden bg-gradient-to-br from-slate-100 via-sky-50 to-orange-50/40">
            <!-- Landmark watermark in background -->
            <div 
                class="absolute inset-0 bg-cover bg-center bg-no-repeat opacity-[0.05] pointer-events-none z-0" 
                style="background-image: url('/images/landmark.jpg');"
            ></div>

            <!-- Gentle ambient glow orbs -->
            <div class="absolute -top-32 right-1/4 w-[32rem] h-[32rem] bg-sky-200/40 rounded-full blur-3xl pointer-events-none z-0"></div>
            <div class="absolute -bottom-32 left-1/4 w-[32rem] h-[32rem] bg-orange-200/30 rounded-full blur-3xl pointer-events-none z-0"></div>

            <div class="relative z-10 w-full flex flex-col items-center">
                <!-- Top Pill Badge -->
                <div class="inline-flex items-center gap-2 text-xs font-mono font-bold uppercase tracking-wider text-orange-700 bg-orange-50 border border-orange-200 px-4 py-1.5 rounded-full shadow-sm mb-6">
                    <font-awesome-icon icon="fa-solid fa-anchor" class="text-orange-500" />
                    <span>Talibon, Bohol • Official Venue Booking Portal</span>
                </div>

                <!-- Content Card: High-Contrast, Rounded, Shadowed -->
                <div 
                    :class="[
                        'w-full bg-white px-6 py-8 sm:px-10 sm:py-10 shadow-xl shadow-slate-300/40 rounded-3xl border border-slate-200 transition-all relative z-10',
                        maxWidthClass
                    ]"
                >
                    <slot />
                </div>

                <!-- Trust Badges below Card -->
                <div class="mt-8 flex flex-wrap items-center justify-center gap-6 sm:gap-10 text-xs font-semibold text-slate-600">
                    <div class="flex items-center gap-2">
                        <font-awesome-icon icon="fa-solid fa-shield-halved" class="text-emerald-600" />
                        <span>SSL Encrypted & Secure</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <font-awesome-icon icon="fa-solid fa-bolt" class="text-amber-500" />
                        <span>100% Standby Power Generator</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <font-awesome-icon icon="fa-solid fa-wind" class="text-sky-500" />
                        <span>Refreshing Coastal Sea Breeze</span>
                    </div>
                </div>
            </div>
        </main>

        <!-- ══════════════════════════════════════════════════════ -->
        <!-- 3. FOOTER (Exact 4-column Maritime Design from Welcome) -->
        <!-- ══════════════════════════════════════════════════════ -->
        <footer class="bg-slate-900 text-slate-300 pt-16 pb-12 border-t border-slate-800 mt-auto">
            <div class="max-w-7xl mx-auto px-6 md:px-8 space-y-12">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10">
                    
                    <!-- Brand Column (2 cols wide) -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center shadow-md">
                                <font-awesome-icon icon="fa-solid fa-ship" class="text-white text-lg" />
                            </div>
                            <div>
                                <div class="font-display text-white text-lg font-bold leading-tight">Butal Ship Hauz</div>
                                <div class="font-mono text-amber-400 text-[10px] font-bold tracking-widest uppercase">Talibon, Bohol</div>
                            </div>
                        </div>

                        <p class="text-slate-300 text-xs leading-relaxed font-medium max-w-sm">
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

                    <!-- Dynamic Navigation Links (3 cols) -->
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
        <!-- 4. CHATBOT WIDGET                                      -->
        <!-- ══════════════════════════════════════════════════════ -->
        <ChatBot />

    </div>
</template>

<style scoped>
.nav-link {
    position: relative;
}
.nav-link::after {
    content: '';
    position: absolute;
    bottom: -4px;
    left: 0;
    width: 100%;
    height: 2px;
    background: #f97316;
    transform: scaleX(0);
    transition: transform 0.2s ease;
}
.nav-link:hover::after {
    transform: scaleX(1);
}
</style>