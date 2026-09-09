<script setup>
import { ref, onMounted } from "vue";

const chatOpen = ref(false);
const chatMessages = ref([]);
const chatCurrentNodeId = ref(null);
const chatTyping = ref(false);
const nodes = ref({});       // keyed by node id
const mainNodeId = ref(null);
const loading = ref(true);
const error = ref(false);

// ── Multi-language support (English as primary/default) ──
const currentLang = ref('en'); // 'en' | 'tl' | 'ceb'

const langGreetings = {
    en: "Ahoy! Welcome to Butal Ship Hauz in Capawan, Talibon, Bohol! 🚢 How may our crew assist you today?",
    tl: "Ahoy! Maligayang pagdating sa Butal Ship Hauz (Capawan, Talibon, Bohol)! 🚢 Paano ka namin matutulungan ngayong araw?",
    ceb: "Ahoy! Maayong pag-abot sa Butal Ship Hauz (Capawan, Talibon, Bohol)! 🚢 Unsay among ikatabang kanimo karong adlawa?",
};

const cycleLanguage = () => {
    if (currentLang.value === 'en') currentLang.value = 'tl';
    else if (currentLang.value === 'tl') currentLang.value = 'ceb';
    else currentLang.value = 'en';

    chatMessages.value.push({
        from: 'bot',
        text: langGreetings[currentLang.value]
    });
    scrollChat();
};

// ── Price Formatter ──
const fmtPrice = (val) => "₱" + Number(val || 0).toLocaleString("en-PH");

// ── Interactive Widgets State ──
const userInputText = ref("");

// Date Checker State
const dateInputVal = ref(new Date().toISOString().split("T")[0]);
const dateCheckLoading = ref(false);
const dateCheckResult = ref(null);

// Booking Tracker State
const trackInputVal = ref("");
const trackLoading = ref(false);
const trackResult = ref(null);

// ── Reusable function to fetch LIVE data from Backend ──
const fetchChatbotData = async (isInitial = false) => {
    if (isInitial) loading.value = true;
    try {
        const apiUrl = (import.meta.env.VITE_APP_URL ? import.meta.env.VITE_APP_URL : "") + "/api/chatbot";
        const res = await fetch(apiUrl);
        if (!res.ok) throw new Error("Failed to fetch chatbot data");
        const data = await res.json();
        
        nodes.value = data.nodes;
        mainNodeId.value = data.main_node_id;
        error.value = false;

        if (isInitial) {
            loading.value = false;
            startConversation();
        }
    } catch (e) {
        if (isInitial) {
            loading.value = false;
            error.value = true;
            chatMessages.value = [{
                from: "bot",
                text: "Sorry, I am having trouble connecting to the crew server. Please try again shortly or call our hotline at 0920 713 9299.",
            }];
        }
    }
};

// ── Start or restart conversation ──
const startConversation = () => {
    chatMessages.value = [];
    const main = nodes.value[mainNodeId.value];
    
    // Greeting in active language (Default English)
    const greetingText = langGreetings[currentLang.value] || (main ? main.message : "Welcome to Butal Ship Hauz!");

    chatMessages.value.push({ 
        from: "bot", 
        text: greetingText,
        images: main ? (main.images || []) : [],
        dynamic_data: main ? (main.dynamic_data || null) : null
    });
    
    // Top interactive actions + database options
    const interactiveOptions = [
        { label: "📅 Check Date Availability", custom_action: "date_checker" },
        { label: "🔍 Track My Booking Status", custom_action: "booking_tracker" },
    ];

    const dbOptions = main && main.options ? main.options : [];
    
    chatMessages.value.push({
        from: "options",
        label: "Main Menu",
        options: [...interactiveOptions, ...dbOptions],
    });
};

// ── Restart button handler ──
const resetChat = async () => {
    chatMessages.value = [];
    await fetchChatbotData(true);
};

// Fetch on mount
onMounted(() => {
    fetchChatbotData(true);
});

// ── Option Click Handler ──
async function handleOption(option) {
    if (option.custom_action) {
        handleCustomAction(option.custom_action);
        return;
    }

    chatMessages.value.push({ from: "user", text: option.label });
    chatTyping.value = true;
    scrollChat();

    // Fetch latest live data before replying
    await fetchChatbotData(false);

    setTimeout(() => {
        chatTyping.value = false;
        const nextNodeId = option.next_node_id;

        // 0 or null means "back to main"
        const targetId = (!nextNodeId || nextNodeId === 0)
            ? mainNodeId.value
            : nextNodeId;

        const targetNode = nodes.value[targetId];

        if (targetNode) {
            chatMessages.value.push({ 
                from: "bot", 
                text: targetNode.message,
                images: targetNode.images || [],
                dynamic_data: targetNode.dynamic_data || null
            });
            chatCurrentNodeId.value = targetId;

            if (targetNode.options && targetNode.options.length > 0) {
                let opts = targetNode.options;
                if (targetId === mainNodeId.value) {
                    opts = [
                        { label: "📅 Check Date Availability", custom_action: "date_checker" },
                        { label: "🔍 Track My Booking Status", custom_action: "booking_tracker" },
                        ...opts
                    ];
                }
                chatMessages.value.push({
                    from: "options",
                    label: targetNode.node_key,
                    options: opts,
                });
            }
        } else {
            chatMessages.value.push({ 
                from: "bot", 
                text: "This option seems to have been updated by our crew. Let's return to the main menu!" 
            });
            setTimeout(startConversation, 1500);
        }

        scrollChat();
    }, 450);
}

// ── Custom Actions Handler ──
function handleCustomAction(action) {
    if (action === "date_checker") {
        chatMessages.value.push({ from: "user", text: "📅 Check Date Availability" });
        chatMessages.value.push({
            from: "bot",
            text: "Please select a date below to check if the venue has an open slot:",
            custom_widget: "date_checker"
        });
        scrollChat();
    } else if (action === "booking_tracker") {
        chatMessages.value.push({ from: "user", text: "🔍 Track My Booking Status" });
        chatMessages.value.push({
            from: "bot",
            text: "Please enter your Booking Reference Number (e.g. BSH-XXXXXXXX) or registered phone number:",
            custom_widget: "booking_tracker"
        });
        scrollChat();
    } else if (action === "main_menu") {
        startConversation();
        scrollChat();
    }
}

// ── Date Checker Execution ──
const executeDateCheck = async () => {
    if (!dateInputVal.value) return;
    dateCheckLoading.value = true;
    dateCheckResult.value = null;

    try {
        const apiUrl = (import.meta.env.VITE_APP_URL ? import.meta.env.VITE_APP_URL : "") + `/api/chatbot/check-date?date=${dateInputVal.value}`;
        const res = await fetch(apiUrl);
        const data = await res.json();
        dateCheckResult.value = data;
    } catch (e) {
        dateCheckResult.value = {
            success: false,
            message: "Unable to access the calendar service right now. Please try again or call 0920 713 9299."
        };
    } finally {
        dateCheckLoading.value = false;
        scrollChat();
    }
};

// ── Booking Tracking Execution ──
const executeTrackBooking = async () => {
    if (!trackInputVal.value || trackInputVal.value.trim().length < 3) return;
    trackLoading.value = true;
    trackResult.value = null;

    try {
        const apiUrl = (import.meta.env.VITE_APP_URL ? import.meta.env.VITE_APP_URL : "") + `/api/chatbot/track-booking?query=${encodeURIComponent(trackInputVal.value.trim())}`;
        const res = await fetch(apiUrl);
        const data = await res.json();
        trackResult.value = data;
    } catch (e) {
        trackResult.value = {
            found: false,
            message: "Unable to connect to the booking database. Please try again shortly."
        };
    } finally {
        trackLoading.value = false;
        scrollChat();
    }
};

// ── Smart Text Input & Keyword Matcher ──
const handleUserTextSubmit = () => {
    const text = userInputText.value.trim();
    if (!text) return;

    chatMessages.value.push({ from: "user", text: text });
    userInputText.value = "";
    chatTyping.value = true;
    scrollChat();

    setTimeout(() => {
        chatTyping.value = false;
        processKeywordIntent(text);
        scrollChat();
    }, 450);
};

const processKeywordIntent = (text) => {
    const lower = text.toLowerCase();

    // 1. Date Check Intent
    if (lower.includes("date") || lower.includes("petsa") || lower.includes("available") || lower.includes("bakante") || lower.includes("adlaw") || lower.includes("open") || lower.includes("schedule") || lower.includes("slot")) {
        chatMessages.value.push({
            from: "bot",
            text: "Sure! Please pick a date below to check availability for exclusive events or visitor passes:",
            custom_widget: "date_checker"
        });
        return;
    }

    // 2. Track Booking Intent
    if (lower.includes("track") || lower.includes("status") || lower.includes("reserba") || lower.includes("booking") || lower.includes("bsh-") || lower.includes("reference") || lower.includes("lookup")) {
        chatMessages.value.push({
            from: "bot",
            text: "Please enter your Booking Reference (e.g., BSH-XXXXXXXX) or your contact phone number below:",
            custom_widget: "booking_tracker"
        });
        return;
    }

    // 3. Pricing / Rates / Entrance Fee
    if (lower.includes("price") || lower.includes("rate") || lower.includes("cost") || lower.includes("entrance") || lower.includes("fee") || lower.includes("magkano") || lower.includes("pila") || lower.includes("presyo") || lower.includes("bayad") || lower.includes("pax")) {
        chatMessages.value.push({
            from: "bot",
            text: "Here are the venue rates at Butal Ship Hauz:\n\n🎫 Tour / Visitor Pass: ₱150 per person (Walk-in Ocular Tour)\n🚢 Exclusive Venue Packages: Starting at ₱5,000 for private occasions (Weddings, Debuts, Birthdays, Reunions, Conferences).\n\nWe provide flexible Morning, Afternoon, and Full Day exclusive deck reservations!",
            action_buttons: [
                { label: "👉 Book a Reservation", href: "/client/booking?mode=exclusive" },
                { label: "🎫 Get a Tour Pass (₱150)", href: "/client/booking?mode=visitor" },
            ]
        });
        return;
    }

    // 4. Location / Directions / Address
    if (lower.includes("location") || lower.includes("where") || lower.includes("address") || lower.includes("directions") || lower.includes("saan") || lower.includes("asa") || lower.includes("capawan") || lower.includes("talibon") || lower.includes("map") || lower.includes("gps")) {
        chatMessages.value.push({
            from: "bot",
            text: "📍 Venue Location:\nButal Ship Hauz, Sitio Capawan, Poblacion, Talibon, Bohol, Philippines\n\n🚗 How to Get Here:\nFrom Tagbilaran City, Tubigon Port, or Ubay Port, take a bus or van bound for Talibon (approx. 2 hours). Ask the driver to drop you off near Butal Ship Hauz in Capawan!\n\nClick below to open turn-by-turn Google Maps GPS navigation:",
            action_buttons: [
                { label: "🗺️ Open in Google Maps (GPS)", href: "https://www.google.com/maps/search/?api=1&query=Butal+Ship+Hauz,+Capawan,+Talibon,+Bohol", external: true },
                { label: "📞 Call Hotline (0920 713 9299)", href: "tel:09207139299" },
            ]
        });
        return;
    }

    // 5. Corkage / Catering / Food Policy
    if (lower.includes("corkage") || lower.includes("food") || lower.includes("catering") || lower.includes("lechon") || lower.includes("drinks") || lower.includes("pagkain") || lower.includes("kaon")) {
        chatMessages.value.push({
            from: "bot",
            text: "🍽️ Corkage & Catering Guidelines:\n\n• Outside Catering: Allowed for Exclusive Events (minimal utility fee may apply based on high-wattage warming equipment).\n• Lechon & Packed Food: Permitted for private gatherings.\n• Drinks & Refreshments: Welcome for private reservations.\n\nFor custom catering recommendations or package inquiries, feel free to call 0920 713 9299.",
            action_buttons: [
                { label: "📞 Call Crew Hotline", href: "tel:09207139299" }
            ]
        });
        return;
    }

    // 6. Contact / Phone / Hotline
    if (lower.includes("contact") || lower.includes("phone") || lower.includes("hotline") || lower.includes("number") || lower.includes("call") || lower.includes("cell") || lower.includes("tawag")) {
        chatMessages.value.push({
            from: "bot",
            text: "📞 Official Contact Hotlines:\n\n• Smart/TNT: 0920 713 9299\n• Globe/TM: 0930 903 6834\n• Email: reservations@butalshiphauz.com.ph\n• Office & Ocular Hours: 8:00 AM – 6:00 PM Daily",
            action_buttons: [
                { label: "📞 Call Now (0920 713 9299)", href: "tel:09207139299" },
                { label: "💬 Send SMS Text", href: "sms:09207139299" }
            ]
        });
        return;
    }

    // 7. Payment / Downpayment
    if (lower.includes("payment") || lower.includes("downpayment") || lower.includes("deposit") || lower.includes("gcash") || lower.includes("bank") || lower.includes("bayad")) {
        chatMessages.value.push({
            from: "bot",
            text: "💳 Payment & Reservation Policy:\n\n• A 50% Downpayment is required to officially confirm and lock your date on our booking calendar.\n• We accept GCash, Bank Transfer, and Cash on site at the venue office.\n• The remaining balance can be settled on or before the day of the event.",
            action_buttons: [
                { label: "👉 Book a Reservation", href: "/client/booking?mode=exclusive" }
            ]
        });
        return;
    }

    // 8. Language switch triggers
    if (lower.includes("tagalog") || lower.includes("filipino")) {
        currentLang.value = "tl";
        chatMessages.value.push({
            from: "bot",
            text: "Naka-set na po ang wika sa Tagalog. Paano ka namin matutulungan sa Butal Ship Hauz?"
        });
        return;
    } else if (lower.includes("bisaya") || lower.includes("cebuano")) {
        currentLang.value = "ceb";
        chatMessages.value.push({
            from: "bot",
            text: "Gi-set na ang pinulongan sa Bisaya. Unsay ikatabang namo kanimo sa Butal Ship Hauz?"
        });
        return;
    }

    // Fallback: Check if user typed something that matches database node_keys
    let matched = null;
    for (const id in nodes.value) {
        if (nodes.value[id].node_key.toLowerCase().includes(lower) || lower.includes(nodes.value[id].node_key.toLowerCase())) {
            matched = nodes.value[id];
            break;
        }
    }

    if (matched) {
        chatMessages.value.push({
            from: "bot",
            text: matched.message,
            images: matched.images || [],
            dynamic_data: matched.dynamic_data || null
        });
        if (matched.options && matched.options.length > 0) {
            chatMessages.value.push({
                from: "options",
                label: matched.node_key,
                options: matched.options,
            });
        }
    } else {
        chatMessages.value.push({
            from: "bot",
            text: "I'm sorry, I didn't quite catch that. You may select one of the options below or contact our crew directly:",
            action_buttons: [
                { label: "📅 Check Date Availability", custom_action: "date_checker" },
                { label: "🔍 Track Booking Status", custom_action: "booking_tracker" },
                { label: "🏠 Back to Main Menu", custom_action: "main_menu" },
                { label: "📞 Call Hotline (0920 713 9299)", href: "tel:09207139299" },
            ]
        });
    }
};

function scrollChat() {
    setTimeout(() => {
        const el = document.getElementById("chat-body");
        if (el) el.scrollTop = el.scrollHeight;
    }, 60);
}

function openChat() {
    chatOpen.value = true;
    fetchChatbotData(false);
    scrollChat();
}
</script>

<template>
    <!-- ══════════ CHATBOT FAB ══════════ -->
    <div class="fixed bottom-6 right-6 z-50">
        <!-- Chat modal -->
        <Transition name="chat-pop">
            <div
                v-if="chatOpen"
                class="chat-modal mb-4 w-[360px] max-w-[calc(100vw-36px)] rounded-2xl overflow-hidden shadow-2xl flex flex-col font-body border border-sky-200"
                style="
                    background: #f8fafc;
                    height: 590px;
                    max-height: calc(100vh - 110px);
                "
            >
                <!-- 1. Header -->
                <div class="flex items-center justify-between px-4 py-3 bg-sky-900 border-b border-sky-800 shrink-0">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 bg-white rounded-full flex items-center justify-center flex-shrink-0 shadow-sm border border-sky-800">
                            <svg class="w-5 h-5 fill-orange-500" viewBox="0 0 24 24">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-display text-white text-sm font-black leading-tight tracking-wide flex items-center gap-1.5">
                                Ship Hauz Crew
                                <span class="bg-amber-400/20 text-amber-300 text-[9px] px-1.5 py-0.5 rounded font-mono uppercase font-bold">Talibon</span>
                            </div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span
                                    class="w-2 h-2 rounded-full"
                                    :class="loading ? 'bg-amber-400' : error ? 'bg-red-400' : 'bg-lime-400'"
                                ></span>
                                <span class="font-mono text-sky-200 font-semibold text-[10px] tracking-wider uppercase">
                                    {{ loading ? 'Connecting...' : error ? 'Offline' : 'Online Assistant' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Header Actions -->
                    <div class="flex items-center gap-1.5">
                        <!-- Restart Chat Button -->
                        <button
                            @click="resetChat"
                            title="Restart Conversation"
                            class="w-7 h-7 rounded-full hover:bg-white/20 flex items-center justify-center transition-colors text-sky-200 hover:text-white"
                        >
                            <font-awesome-icon icon="fa-solid fa-rotate-right" class="text-xs" />
                        </button>
                        <!-- Close Button -->
                        <button
                            @click="chatOpen = false"
                            title="Close Chat"
                            class="w-7 h-7 rounded-full hover:bg-red-500/80 flex items-center justify-center transition-colors text-sky-200 hover:text-white"
                        >
                            <font-awesome-icon icon="fa-solid fa-xmark" class="text-sm" />
                        </button>
                    </div>
                </div>

                <!-- 2. Quick Actions & Language Subheader -->
                <div class="bg-sky-950 px-3.5 py-1.5 flex items-center justify-between border-b border-sky-800 text-[11px] font-bold text-white shrink-0">
                    <a href="tel:09207139299" class="text-orange-400 hover:text-orange-300 flex items-center gap-1 transition-colors">
                        <font-awesome-icon icon="fa-solid fa-phone" class="text-[10px]" /> 0920 713 9299
                    </a>
                    <div class="flex items-center gap-2">
                        <a 
                            href="https://www.google.com/maps/search/?api=1&query=Butal+Ship+Hauz,+Capawan,+Talibon,+Bohol" 
                            target="_blank" 
                            rel="noopener noreferrer" 
                            class="text-sky-300 hover:text-white flex items-center gap-1 transition-colors text-[10px]"
                            title="Turn-by-Turn GPS to Capawan, Talibon"
                        >
                            <font-awesome-icon icon="fa-solid fa-map-location-dot" /> Maps GPS
                        </a>
                        <!-- Language toggle button -->
                        <button 
                            @click="cycleLanguage" 
                            class="bg-sky-800 hover:bg-sky-700 text-amber-300 px-1.5 py-0.5 rounded text-[9px] font-mono uppercase tracking-wider transition-colors"
                            :title="'Language: ' + currentLang.toUpperCase() + ' (click to switch)'"
                        >
                            {{ currentLang === 'en' ? '🌐 EN' : currentLang === 'tl' ? '🇵🇭 TL' : '🏝️ CEB' }}
                        </button>
                    </div>
                </div>

                <!-- 3. Messages Body -->
                <div
                    id="chat-body"
                    class="flex-1 overflow-y-auto px-4 py-4 space-y-3.5 relative"
                    style="min-height: 0"
                >
                    <!-- Loading skeleton -->
                    <div v-if="loading" class="flex items-center justify-center h-full">
                        <div class="text-slate-400 text-xs font-bold animate-pulse">Connecting to crew...</div>
                    </div>

                    <template v-else v-for="(msg, i) in chatMessages" :key="i">
                        <!-- Bot message -->
                        <div
                            v-if="msg.from === 'bot'"
                            class="flex items-end gap-2"
                        >
                            <div class="w-6 h-6 bg-sky-100 rounded-full flex items-center justify-center flex-shrink-0 border border-sky-200">
                                <svg class="w-3.5 h-3.5 fill-orange-500" viewBox="0 0 24 24">
                                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                                </svg>
                            </div>
                            
                            <div class="flex flex-col gap-2 max-w-[88%] w-full">
                                <!-- Text Bubble -->
                                <div
                                    class="chat-bubble-bot w-fit font-body text-slate-800 text-[13px] font-medium leading-relaxed px-3.5 py-2.5 rounded-2xl rounded-bl-sm border border-slate-200 bg-white shadow-sm"
                                    style="white-space: pre-line;"
                                >
                                    {{ msg.text }}
                                </div>

                                <!-- Node Images -->
                                <template v-if="msg.images && msg.images.length > 0">
                                    <img 
                                        v-for="(img, idx) in msg.images" 
                                        :key="idx" 
                                        :src="img" 
                                        class="w-full rounded-xl border border-slate-200 shadow-sm object-cover max-h-44" 
                                        alt="Butal Ship Hauz"
                                    />
                                </template>

                                <!-- Action Buttons attached to Bot Message -->
                                <div v-if="msg.action_buttons && msg.action_buttons.length > 0" class="flex flex-col gap-1.5 pt-1">
                                    <template v-for="(btn, bIdx) in msg.action_buttons" :key="bIdx">
                                        <a 
                                            v-if="btn.href"
                                            :href="btn.href"
                                            :target="btn.external ? '_blank' : '_self'"
                                            class="w-full text-center font-body font-bold text-[12px] bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white py-2 px-3 rounded-xl shadow-sm transition-all"
                                        >
                                            {{ btn.label }}
                                        </a>
                                        <button 
                                            v-else-if="btn.custom_action"
                                            @click="handleCustomAction(btn.custom_action)"
                                            class="w-full text-left font-body font-bold text-[12px] text-sky-800 bg-sky-50 border border-sky-200 hover:bg-orange-500 hover:text-white px-3.5 py-2 rounded-xl shadow-sm transition-all"
                                        >
                                            {{ btn.label }}
                                        </button>
                                    </template>
                                </div>

                                <!-- Dynamic Data: Event Types -->
                                <div v-if="msg.dynamic_data && msg.dynamic_data.type === 'event_types'" class="bg-sky-50 border border-sky-100 rounded-xl p-3 shadow-sm w-full">
                                    <p class="text-[10px] font-bold text-sky-800 uppercase tracking-wider mb-2">🎟️ Available Events</p>
                                    <ul class="space-y-1.5">
                                        <li v-for="evt in msg.dynamic_data.items" :key="evt.id" class="text-[12px] font-bold text-slate-700 flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> {{ evt.type }}
                                        </li>
                                    </ul>
                                </div>

                                <!-- Dynamic Data: Venue Packages & Direct Booking Links -->
                                <div v-if="msg.dynamic_data && msg.dynamic_data.type === 'venue_packages'" class="space-y-3 w-full">
                                    <div v-for="pkg in msg.dynamic_data.items" :key="pkg.id" class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm w-full overflow-hidden">
                                        <div v-if="pkg.image" class="mb-2.5 -mx-3 -mt-3 border-b border-slate-100">
                                            <img :src="pkg.image" :alt="pkg.title" @error="$event.target.onerror = null; $event.target.src = '/images/venue.jpg'" class="w-full h-28 object-cover" />
                                        </div>

                                        <p class="text-[13px] font-black text-sky-900 leading-tight mb-0.5">{{ pkg.title }}</p>
                                        <p class="text-[11px] font-medium text-slate-500 mb-2 flex items-center gap-1">
                                            <font-awesome-icon icon="fa-solid fa-users" class="text-slate-400 text-[10px]" />
                                            Up to {{ pkg.guests }} guests
                                        </p>

                                        <div class="grid grid-cols-2 gap-1.5 text-[11px] mb-3">
                                            <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100 flex flex-col justify-center">
                                                <span class="text-slate-400 font-bold uppercase text-[8.5px] tracking-wider">Morning</span>
                                                <span class="font-bold text-sky-700">{{ fmtPrice(pkg.price_morning || pkg.price * 0.6) }}</span>
                                            </div>
                                            <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100 flex flex-col justify-center">
                                                <span class="text-slate-400 font-bold uppercase text-[8.5px] tracking-wider">Afternoon</span>
                                                <span class="font-bold text-sky-700">{{ fmtPrice(pkg.price_afternoon || pkg.price * 0.6) }}</span>
                                            </div>
                                            <div class="bg-sky-50 p-1.5 rounded-lg border border-sky-100 flex flex-col justify-center">
                                                <span class="text-sky-600/70 font-bold uppercase text-[8.5px] tracking-wider">Full Day</span>
                                                <span class="font-bold text-sky-900">{{ fmtPrice(pkg.price_fullday || pkg.price) }}</span>
                                            </div>
                                            <div class="bg-amber-50 p-1.5 rounded-lg border border-amber-100 flex flex-col justify-center">
                                                <span class="text-amber-600/70 font-bold uppercase text-[8.5px] tracking-wider">Visitor Pass</span>
                                                <span class="font-bold text-amber-700">{{ fmtPrice(pkg.price_visitor || 150) }}<span class="text-[9px] font-medium text-amber-600/70">/pax</span></span>
                                            </div>
                                        </div>

                                        <!-- DIRECT BOOKING BUTTONS (Links to client booking) -->
                                        <div class="flex items-center gap-1.5">
                                            <a 
                                                :href="`/client/booking?package_id=${pkg.id}&mode=exclusive`"
                                                class="flex-1 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-[11px] py-2 px-2.5 rounded-lg text-center transition-all shadow-sm flex items-center justify-center gap-1"
                                            >
                                                <font-awesome-icon icon="fa-solid fa-calendar-check" class="text-[10px]" /> Book Package
                                            </a>
                                            <a 
                                                href="/client/booking?mode=visitor"
                                                class="bg-amber-100 hover:bg-amber-200 text-amber-900 font-bold text-[11px] py-2 px-2.5 rounded-lg text-center transition-colors border border-amber-200 shrink-0"
                                                title="Tour / Visitor Pass"
                                            >
                                                Tour Pass
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- WIDGET: Live Date Availability Checker -->
                                <div v-if="msg.custom_widget === 'date_checker'" class="bg-white border border-sky-200 rounded-2xl p-3.5 shadow-md w-full space-y-3">
                                    <div class="flex items-center gap-2 text-sky-900 font-black text-xs">
                                        <font-awesome-icon icon="fa-solid fa-calendar-days" class="text-orange-500" />
                                        <span>Check Date Availability</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="date" 
                                            v-model="dateInputVal"
                                            class="flex-1 border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:ring-1 focus:ring-orange-500 focus:outline-none"
                                        />
                                        <button 
                                            @click="executeDateCheck"
                                            :disabled="dateCheckLoading"
                                            class="bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white font-bold text-xs px-3 py-2 rounded-xl transition-colors shrink-0 shadow-sm"
                                        >
                                            {{ dateCheckLoading ? 'Checking...' : 'Check' }}
                                        </button>
                                    </div>

                                    <!-- Date Check Result Card -->
                                    <div v-if="dateCheckResult" class="pt-2 border-t border-slate-100 space-y-2 text-xs">
                                        <div class="font-bold text-slate-800">{{ dateCheckResult.formatted_date }}</div>
                                        
                                        <!-- Availability status banner -->
                                        <div 
                                            v-if="dateCheckResult.is_past"
                                            class="bg-red-50 text-red-700 border border-red-200 rounded-xl p-2.5 font-medium leading-tight"
                                        >
                                            ⚠️ {{ dateCheckResult.message }}
                                        </div>
                                        <div 
                                            v-else-if="dateCheckResult.fullday_taken"
                                            class="bg-amber-50 text-amber-900 border border-amber-200 rounded-xl p-2.5 font-medium leading-tight"
                                        >
                                            🔒 Exclusive reserved for whole day. <strong>Walk-in Visitor Passes (₱150/pax) are still welcome!</strong>
                                        </div>
                                        <div 
                                            v-else-if="dateCheckResult.morning_taken || dateCheckResult.afternoon_taken"
                                            class="bg-amber-50 text-amber-800 border border-amber-200 rounded-xl p-2.5 font-medium leading-tight"
                                        >
                                            ⚡ {{ dateCheckResult.message }}
                                        </div>
                                        <div 
                                            v-else
                                            class="bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl p-2.5 font-medium leading-tight"
                                        >
                                            ✅ <strong>Fully Available!</strong> You can reserve an Exclusive Event or a Visitor Tour Pass for this date.
                                        </div>

                                        <a 
                                            v-if="!dateCheckResult.is_past"
                                            :href="`/client/booking?date=${dateCheckResult.date}`"
                                            class="block w-full text-center bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-black py-2 rounded-xl shadow-sm transition-all"
                                        >
                                            Book for this Date
                                        </a>
                                    </div>
                                </div>

                                <!-- WIDGET: Booking Reference Tracker -->
                                <div v-if="msg.custom_widget === 'booking_tracker'" class="bg-white border border-sky-200 rounded-2xl p-3.5 shadow-md w-full space-y-3">
                                    <div class="flex items-center gap-2 text-sky-900 font-black text-xs">
                                        <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="text-orange-500" />
                                        <span>Track Booking Status</span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <input 
                                            type="text" 
                                            v-model="trackInputVal"
                                            placeholder="e.g. BSH-8A1C5F2B or Phone #"
                                            class="flex-1 border border-slate-200 rounded-xl px-2.5 py-1.5 text-xs font-semibold text-slate-800 focus:ring-1 focus:ring-orange-500 focus:outline-none"
                                            @keyup.enter="executeTrackBooking"
                                        />
                                        <button 
                                            @click="executeTrackBooking"
                                            :disabled="trackLoading"
                                            class="bg-orange-500 hover:bg-orange-600 disabled:opacity-50 text-white font-bold text-xs px-3 py-2 rounded-xl transition-colors shrink-0 shadow-sm"
                                        >
                                            {{ trackLoading ? '...' : 'Track' }}
                                        </button>
                                    </div>

                                    <!-- Tracking Result Card -->
                                    <div v-if="trackResult" class="pt-2 border-t border-slate-100 text-xs">
                                        <div v-if="!trackResult.found" class="bg-red-50 text-red-700 border border-red-200 rounded-xl p-2.5 font-medium leading-tight">
                                            {{ trackResult.message }}
                                        </div>
                                        <div v-else class="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="font-mono text-[10px] font-bold text-slate-400 uppercase">Booking Ref #</span>
                                                <span class="font-mono text-xs font-black text-sky-900">{{ trackResult.booking_ref }}</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-500">Customer:</span>
                                                <span class="font-bold text-slate-800">{{ trackResult.guest_name }}</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-500">Event / Package:</span>
                                                <span class="font-bold text-slate-800">{{ trackResult.event_type }} ({{ trackResult.package_title }})</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span class="text-slate-500">Date & Time:</span>
                                                <span class="font-bold text-slate-800">{{ trackResult.date }} ({{ trackResult.time_slot }})</span>
                                            </div>
                                            <div class="flex items-center justify-between pt-1 border-t border-slate-200">
                                                <span class="text-slate-500 font-bold">Status:</span>
                                                <span 
                                                    class="font-black uppercase text-[10px] px-2.5 py-0.5 rounded-full"
                                                    :class="{
                                                        'bg-emerald-100 text-emerald-800': trackResult.status === 'confirmed',
                                                        'bg-amber-100 text-amber-800': trackResult.status === 'pending',
                                                        'bg-blue-100 text-blue-800': trackResult.status === 'completed',
                                                        'bg-red-100 text-red-800': trackResult.status === 'cancelled',
                                                    }"
                                                >
                                                    {{ trackResult.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- User message -->
                        <div v-else-if="msg.from === 'user'" class="flex justify-end">
                            <div class="font-body text-white font-semibold text-[13px] leading-relaxed px-3.5 py-2 rounded-2xl rounded-br-sm max-w-[85%] bg-sky-600 shadow-sm shadow-sky-600/20">
                                {{ msg.text }}
                            </div>
                        </div>

                        <!-- Options Menu -->
                        <div v-else-if="msg.from === 'options'" class="pl-8 pr-1">
                            <p
                                v-if="i === chatMessages.length - 1"
                                class="font-mono text-slate-400 font-bold text-[9.5px] tracking-wider uppercase mb-1.5 ml-1"
                            >
                                {{ msg.label }}
                            </p>
                            <div
                                v-if="i === chatMessages.length - 1"
                                class="flex flex-col gap-1.5"
                            >
                                <button
                                    v-for="opt in msg.options"
                                    :key="opt.label"
                                    @click="handleOption(opt)"
                                    class="chat-option-btn text-left font-body font-bold text-[12px] text-sky-800 bg-sky-50 border border-sky-200 hover:bg-orange-500 hover:text-white hover:border-orange-500 px-3.5 py-2 rounded-xl transition-colors duration-150 shadow-sm"
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Typing indicator -->
                    <div v-if="chatTyping" class="flex items-end gap-2">
                        <div class="w-6 h-6 bg-sky-100 rounded-full flex items-center justify-center flex-shrink-0 border border-sky-200">
                            <svg class="w-3.5 h-3.5 fill-orange-500" viewBox="0 0 24 24">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                            </svg>
                        </div>
                        <div class="px-3.5 py-3 rounded-2xl rounded-bl-sm border border-slate-200 bg-white shadow-sm">
                            <div class="typing-dots flex gap-1.5 items-center">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Interactive Smart Text Input Bar -->
                <div class="p-2.5 bg-white border-t border-slate-200 shrink-0">
                    <form @submit.prevent="handleUserTextSubmit" class="flex items-center gap-1.5">
                        <input
                            v-model="userInputText"
                            type="text"
                            placeholder="Ask a question or type here..."
                            class="flex-1 bg-slate-50 border border-slate-200 focus:bg-white focus:border-orange-500 focus:ring-1 focus:ring-orange-500 rounded-xl px-3 py-2 text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none transition-all"
                        />
                        <button
                            type="submit"
                            :disabled="!userInputText.trim()"
                            class="w-8 h-8 rounded-xl bg-orange-500 hover:bg-orange-600 disabled:opacity-40 disabled:hover:bg-orange-500 text-white flex items-center justify-center transition-colors shadow-sm shrink-0"
                            title="Send message"
                        >
                            <font-awesome-icon icon="fa-solid fa-paper-plane" class="text-xs" />
                        </button>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- FAB Button -->
        <button
            @click="chatOpen ? (chatOpen = false) : openChat()"
            class="chat-fab w-14 h-14 rounded-full flex items-center justify-center transition-all duration-300"
            :class="
                chatOpen
                    ? 'bg-sky-950 text-white shadow-lg border border-sky-800 hover:bg-red-500 hover:border-red-500'
                    : 'bg-orange-500 text-white shadow-lg shadow-orange-500/40 hover:bg-orange-600 hover:scale-105'
            "
            aria-label="Open chat assistant"
        >
            <Transition name="icon-swap" mode="out-in">
                <svg
                    v-if="!chatOpen"
                    key="chat"
                    class="w-7 h-7 fill-current"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M12 2a1 1 0 0 1 1 1v1h3a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3h-1v2a1 1 0 1 1-2 0v-2h-2v2a1 1 0 1 1-2 0v-2H8a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h3V3a1 1 0 0 1 1-1zm4 5H8a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1zM10 10a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm4 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm-5 5h6a1 1 0 1 1 0 2H9a1 1 0 1 1 0-2z"
                    />
                </svg>
                <font-awesome-icon v-else key="close" icon="fa-solid fa-xmark" class="text-xl" />
            </Transition>
        </button>

        <!-- Notification dot -->
        <span
            v-if="!chatOpen"
            class="absolute top-0 right-0 w-3.5 h-3.5 bg-lime-500 border-2 border-white rounded-full animate-pulse"
        ></span>
    </div>
</template>

<style scoped>
.chat-pop-enter-active {
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.chat-pop-leave-active {
    transition: all 0.18s ease-in;
}
.chat-pop-enter-from {
    opacity: 0;
    transform: scale(0.85) translateY(12px);
    transform-origin: bottom right;
}
.chat-pop-leave-to {
    opacity: 0;
    transform: scale(0.9) translateY(8px);
    transform-origin: bottom right;
}
.icon-swap-enter-active,
.icon-swap-leave-active {
    transition: all 0.15s ease;
}
.icon-swap-enter-from {
    opacity: 0;
    transform: scale(0.7) rotate(-20deg);
}
.icon-swap-leave-to {
    opacity: 0;
    transform: scale(0.7) rotate(20deg);
}
.typing-dots span {
    display: inline-block;
    width: 6px;
    height: 6px;
    background: #0ea5e9;
    border-radius: 50%;
    animation: typingbounce 1.2s ease-in-out infinite;
}
.typing-dots span:nth-child(2) { animation-delay: 0.2s; background: #0284c7; }
.typing-dots span:nth-child(3) { animation-delay: 0.4s; background: #075985; }
@keyframes typingbounce {
    0%, 60%, 100% { transform: translateY(0); }
    30% { transform: translateY(-5px); }
}
</style>