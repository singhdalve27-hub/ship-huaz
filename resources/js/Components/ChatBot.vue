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

// ── Formatter para sa mga presyo ──
const fmtPrice = (val) => "₱" + Number(val || 0).toLocaleString("en-PH");

// ── Reusable function para kumuha ng LIVE data mula sa Backend ──
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
                text: "Sorry, I'm having trouble connecting. Please try again later.",
            }];
        }
    }
};

// ── Magsimula o mag-reset ng chat ──
const startConversation = () => {
    chatMessages.value = [];
    const main = nodes.value[mainNodeId.value];
    if (main) {
        chatMessages.value.push({ 
            from: "bot", 
            text: main.message,
            images: main.images || [],
            dynamic_data: main.dynamic_data || null
        });
        
        if (main.options && main.options.length > 0) {
            chatMessages.value.push({
                from: "options",
                label: main.node_key,
                options: main.options,
            });
        }
    }
};

// ── Restart button handler ──
const resetChat = async () => {
    chatMessages.value = [];
    await fetchChatbotData(true);
};

// Fetch sa simula
onMounted(() => {
    fetchChatbotData(true);
});

// ── Option Click Handler (Naging Async para mag-refresh muna bago sumagot) ──
async function handleOption(option) {
    // 1. I-post ang pinili ng user
    chatMessages.value.push({ from: "user", text: option.label });
    chatTyping.value = true;
    scrollChat();

    // 2. Kumuha ng pinaka-latest na data (LIVE UPDATE) nang patago bago mag-reply!
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
            // Reply ng Bot (Laging fresh galing sa database)
            chatMessages.value.push({ 
                from: "bot", 
                text: targetNode.message,
                images: targetNode.images || [],
                dynamic_data: targetNode.dynamic_data || null
            });
            chatCurrentNodeId.value = targetId;

            // Ipakita ang options ng bagong node
            if (targetNode.options && targetNode.options.length > 0) {
                chatMessages.value.push({
                    from: "options",
                    label: targetNode.node_key,
                    options: targetNode.options,
                });
            }
        } else {
            // Fallback kapag biglang na-delete ng admin yung node na dapat pupuntahan
            chatMessages.value.push({ 
                from: "bot", 
                text: "Oops! This option seems to have been updated or removed by our crew. Let's start over!" 
            });
            setTimeout(startConversation, 2000);
        }

        scrollChat();
    }, 600);
}

function scrollChat() {
    setTimeout(() => {
        const el = document.getElementById("chat-body");
        if (el) el.scrollTop = el.scrollHeight;
    }, 50);
}

function openChat() {
    chatOpen.value = true;

    // Refresh data tuwing bubuksan ang chat para siguradong latest
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
                class="chat-modal mb-4 w-[340px] max-w-[calc(100vw-48px)] rounded-xl overflow-hidden shadow-2xl flex flex-col font-body border border-sky-200"
                style="
                    background: #f8fafc;
                    height: 560px;
                    max-height: calc(100vh - 130px);
                "
            >
                <!-- Header -->
                <div class="flex items-center justify-between px-5 py-4 bg-sky-900 border-b border-sky-800 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center flex-shrink-0 shadow-sm border border-sky-800">
                            <svg class="w-5 h-5 fill-orange-500" viewBox="0 0 24 24">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <div class="font-display text-white text-base font-bold leading-tight tracking-wide">
                                Ship Hauz Crew
                            </div>
                            <div class="flex items-center gap-1.5 mt-0.5">
                                <span
                                    class="w-2 h-2 rounded-full"
                                    :class="loading ? 'bg-amber-400' : error ? 'bg-red-400' : 'bg-lime-400'"
                                ></span>
                                <span class="font-mono text-sky-200 font-semibold text-[10px] tracking-wider uppercase">
                                    {{ loading ? 'Connecting...' : error ? 'Offline' : 'Online now' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Header Actions -->
                    <div class="flex items-center gap-2">
                        <!-- Restart Chat Button -->
                        <button
                            @click="resetChat"
                            title="Restart Conversation"
                            class="w-8 h-8 rounded-full hover:bg-white/20 flex items-center justify-center transition-colors duration-150 text-sky-200 hover:text-white"
                        >
                            <font-awesome-icon icon="fa-solid fa-rotate-right" />
                        </button>
                        <!-- Close Button -->
                        <button
                            @click="chatOpen = false"
                            title="Close Chat"
                            class="w-8 h-8 rounded-full hover:bg-red-500/80 flex items-center justify-center transition-colors duration-150 text-sky-200 hover:text-white"
                        >
                            <font-awesome-icon icon="fa-solid fa-xmark" />
                        </button>
                    </div>
                </div>

                <!-- Messages -->
                <div
                    id="chat-body"
                    class="flex-1 overflow-y-auto px-5 py-5 space-y-4 relative"
                    style="min-height: 0"
                >
                    <!-- Loading skeleton -->
                    <div v-if="loading" class="flex items-center justify-center h-full">
                        <div class="text-slate-400 text-sm font-bold animate-pulse">Connecting to crew...</div>
                    </div>

                    <template v-else v-for="(msg, i) in chatMessages" :key="i">
                        <!-- Bot message -->
                        <div
                            v-if="msg.from === 'bot'"
                            class="flex items-end gap-2"
                        >
                            <!-- Bot Avatar -->
                            <div class="w-7 h-7 bg-sky-100 rounded-full flex items-center justify-center flex-shrink-0 border border-sky-200">
                                <svg class="w-4 h-4 fill-orange-500" viewBox="0 0 24 24">
                                    <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                                </svg>
                            </div>
                            
                            <!-- Bot Content Wrapper -->
                            <div class="flex flex-col gap-2 max-w-[85%] w-full">
                                <!-- Text Bubble -->
                                <div
                                    class="chat-bubble-bot w-fit font-body text-slate-700 text-[13.5px] font-medium leading-relaxed px-4 py-2.5 rounded-2xl rounded-bl-sm border border-slate-200 bg-white shadow-sm"
                                    style="white-space: pre-line;"
                                >
                                    {{ msg.text }}
                                </div>

                                <!-- Node Images (Admin configured images for the node) -->
                                <template v-if="msg.images && msg.images.length > 0">
                                    <img 
                                        v-for="(img, idx) in msg.images" 
                                        :key="idx" 
                                        :src="img" 
                                        class="w-full rounded-xl border border-slate-200 shadow-sm object-cover max-h-48 cursor-pointer hover:opacity-90 transition-opacity" 
                                        alt="Chatbot Node Image"
                                    />
                                </template>

                                <!-- Dynamic Data: Event Types -->
                                <div v-if="msg.dynamic_data && msg.dynamic_data.type === 'event_types'" class="bg-sky-50 border border-sky-100 rounded-xl p-3 shadow-sm w-full">
                                    <p class="text-[10px] font-bold text-sky-800 uppercase tracking-wider mb-2">🎟️ Available Events</p>
                                    <ul class="space-y-1.5">
                                        <li v-for="evt in msg.dynamic_data.items" :key="evt.id" class="text-[12px] font-bold text-slate-700 flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-500"></span> {{ evt.type }}
                                        </li>
                                    </ul>
                                </div>

                                <!-- Dynamic Data: Venue Packages & Pricing -->
                                <div v-if="msg.dynamic_data && msg.dynamic_data.type === 'venue_packages'" class="space-y-3 w-full">
                                    <div v-for="pkg in msg.dynamic_data.items" :key="pkg.id" class="bg-white border border-slate-200 rounded-xl p-3 shadow-sm w-full overflow-hidden">
                                        
                                        <!-- Package Image -->
                                        <div v-if="pkg.image" class="mb-3 -mx-3 -mt-3 border-b border-slate-100">
                                            <img :src="pkg.image" :alt="pkg.title" @error="$event.target.onerror = null; $event.target.src = '/images/venue.jpg'" class="w-full h-28 object-cover" />
                                        </div>

                                        <p class="text-[13px] font-bold text-sky-900 leading-tight mb-0.5">{{ pkg.title }}</p>
                                        <p class="text-[11px] font-medium text-slate-500 mb-2.5 flex items-center gap-1">
                                            <font-awesome-icon icon="fa-solid fa-users" class="text-slate-400" />
                                            Up to {{ pkg.guests }} guests
                                        </p>

                                        <div class="grid grid-cols-2 gap-1.5 text-[11px]">
                                            <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100 flex flex-col justify-center">
                                                <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider mb-0.5">Morning</span>
                                                <span class="font-bold text-sky-700">{{ fmtPrice(pkg.price_morning || pkg.price * 0.6) }}</span>
                                            </div>
                                            <div class="bg-slate-50 p-1.5 rounded-lg border border-slate-100 flex flex-col justify-center">
                                                <span class="text-slate-400 font-bold uppercase text-[9px] tracking-wider mb-0.5">Afternoon</span>
                                                <span class="font-bold text-sky-700">{{ fmtPrice(pkg.price_afternoon || pkg.price * 0.6) }}</span>
                                            </div>
                                            <div class="bg-sky-50 p-1.5 rounded-lg border border-sky-100 flex flex-col justify-center">
                                                <span class="text-sky-600/70 font-bold uppercase text-[9px] tracking-wider mb-0.5">Full Day</span>
                                                <span class="font-bold text-sky-900">{{ fmtPrice(pkg.price_fullday || pkg.price) }}</span>
                                            </div>
                                            <div class="bg-amber-50 p-1.5 rounded-lg border border-amber-100 flex flex-col justify-center relative overflow-hidden">
                                                <div class="absolute top-0 right-0 bg-amber-200 text-amber-800 text-[8px] font-bold px-1 rounded-bl">walk-in</div>
                                                <span class="text-amber-600/70 font-bold uppercase text-[9px] tracking-wider mb-0.5">Visitor</span>
                                                <span class="font-bold text-amber-700">{{ fmtPrice(pkg.price_visitor || 150) }}<span class="text-[9px] font-medium text-amber-600/70 ml-0.5">/pax</span></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User message -->
                        <div v-else-if="msg.from === 'user'" class="flex justify-end">
                            <div class="font-body text-white font-medium text-[13.5px] leading-relaxed px-4 py-2.5 rounded-2xl rounded-br-sm max-w-[85%] bg-sky-600 shadow-sm shadow-sky-600/20">
                                {{ msg.text }}
                            </div>
                        </div>

                        <!-- Options -->
                        <div v-else-if="msg.from === 'options'" class="pl-9 pr-2">
                            <p
                                v-if="i === chatMessages.length - 1"
                                class="font-mono text-slate-400 font-bold text-[10px] tracking-wider uppercase mb-2 ml-1"
                            >
                                {{ msg.label }}
                            </p>
                            <div
                                v-if="i === chatMessages.length - 1"
                                class="flex flex-col gap-2"
                            >
                                <button
                                    v-for="opt in msg.options"
                                    :key="opt.label"
                                    @click="handleOption(opt)"
                                    class="chat-option-btn text-left font-body font-bold text-[12.5px] text-sky-800 bg-sky-50 border border-sky-200 hover:bg-orange-500 hover:text-white hover:border-orange-500 px-4 py-2.5 rounded-xl transition-colors duration-200 shadow-sm"
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Typing indicator -->
                    <div v-if="chatTyping" class="flex items-end gap-2">
                        <div class="w-7 h-7 bg-sky-100 rounded-full flex items-center justify-center flex-shrink-0 border border-sky-200">
                            <svg class="w-4 h-4 fill-orange-500" viewBox="0 0 24 24">
                                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
                            </svg>
                        </div>
                        <div class="px-4 py-3.5 rounded-2xl rounded-bl-sm border border-slate-200 bg-white shadow-sm">
                            <div class="typing-dots flex gap-1.5 items-center">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer hint -->
                <div class="px-5 py-3 border-t bg-slate-100 border-slate-200 shrink-0">
                    <p class="font-mono text-slate-400 font-semibold text-[10px] tracking-wider uppercase text-center">
                        Select an option above to continue
                    </p>
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

        <!-- Notification dot (shows when closed) -->
        <span
            v-if="!chatOpen"
            class="absolute top-0 right-0 w-3.5 h-3.5 bg-lime-500 border-2 border-white rounded-full"
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
    background: #0ea5e9; /* sky-500 */
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