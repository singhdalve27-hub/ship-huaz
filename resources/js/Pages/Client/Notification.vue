<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, router } from "@inertiajs/vue3";
import { ref, computed, onMounted, onUnmounted } from "vue";

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
    auth: {
        type: Object,
        default: () => ({}),
    },
});

const activeTab = ref("all");
const replyText = ref("");
const replyingTo = ref(null);
const expandedThread = ref(null);

const notifications = computed(() => props.notifications);

const filtered = computed(() => {
    if (activeTab.value === "booking")
        return notifications.value.filter((n) =>
            ["booking_reminder", "booking_confirmed"].includes(n.type),
        );
    if (activeTab.value === "messages")
        return notifications.value.filter((n) => n.type === "message");
    return notifications.value;
});

const unreadCount = computed(
    () => notifications.value.filter((n) => !n.read).length,
);

function formatDate(ts) {
    return new Date(ts).toLocaleDateString("en-PH", {
        month: "short",
        day: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
}

function typeLabel(type) {
    return (
        {
            booking_reminder: "Reminder",
            booking_confirmed: "Confirmed",
            message: "Message",
        }[type] ?? "Notice"
    );
}

function iconBgClass(type) {
    return (
        {
            booking_reminder: "bg-orange-100 text-orange-500 border-orange-200",
            booking_confirmed: "bg-lime-100 text-lime-600 border-lime-200",
            message: "bg-sky-100 text-sky-500 border-sky-200",
        }[type] ?? "bg-slate-100 text-slate-500 border-slate-200"
    );
}

function badgeClass(type) {
    return (
        {
            booking_reminder: "bg-orange-100 text-orange-800",
            booking_confirmed: "bg-lime-100 text-lime-800",
            message: "bg-sky-100 text-sky-800",
        }[type] ?? "bg-slate-100 text-slate-700"
    );
}

function markRead(notif) {
    if (notif.read) {
        expandedThread.value =
            expandedThread.value === notif.id ? null : notif.id;
        return;
    }
    notif.read = true;
    expandedThread.value = expandedThread.value === notif.id ? null : notif.id;
    axios.patch(route("client.notifications.markRead", notif.id));
}

function toggleThread(notif) {
    markRead(notif);
}

function openReply(notif) {
    replyingTo.value = notif.id;
    replyText.value = "";
}

function sendReply(notif) {
    if (!replyText.value.trim()) return;

    router.post(
        route("client.notifications.reply", notif.id),
        {
            body: replyText.value.trim(),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                replyText.value = "";
                replyingTo.value = null;
            },
        },
    );
}

function markAllRead() {
    router.patch(
        route("client.notifications.markAllRead"),
        {},
        { preserveScroll: true },
    );
}

let pollTimer = null;

onMounted(() => {
    pollTimer = setInterval(() => {
        router.reload({
            only: ["notifications"],
            preserveScroll: true,
            preserveState: true,
        });
    }, 5000);
});

onUnmounted(() => {
    if (pollTimer) clearInterval(pollTimer);
});
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <div class="min-h-screen bg-slate-50 pb-16">
            <!-- ── Sticky Header: Maritime Telegraph ── -->
            <div class="sticky top-0 z-20 bg-white/95 backdrop-blur-md border-b border-slate-200 shadow-sm">
                <div class="mx-auto max-w-4xl px-3 sm:px-8 pt-4 sm:pt-6 pb-3">
                    <!-- Title row -->
                    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 sm:gap-4 pb-3 sm:pb-4">
                        <div class="flex items-center gap-3 sm:gap-3.5">
                            <div class="w-10 h-10 sm:w-11 sm:h-11 bg-gradient-to-br from-sky-900 to-sky-700 rounded-xl flex items-center justify-center border border-sky-600/30 text-orange-400 shadow-sm shrink-0">
                                <font-awesome-icon icon="fa-solid fa-tower-broadcast" class="text-sm sm:text-base" />
                            </div>
                            <div>
                                <h1 class="font-display text-xl sm:text-2xl font-black leading-tight text-sky-950">
                                    Shipboard Telegraph & Signals
                                </h1>
                                <p class="font-mono text-[9px] sm:text-[10px] font-bold uppercase tracking-widest text-slate-400 mt-0.5">
                                    Passenger Communications • Butal Ship Hauz
                                </p>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                            <button
                                v-if="unreadCount > 0"
                                class="w-full sm:w-auto rounded-xl border border-sky-200 bg-sky-50 px-4 py-2 text-xs font-bold text-sky-800 transition-all hover:bg-sky-100 hover:text-sky-900 shadow-xs flex items-center justify-center gap-1.5"
                                @click="markAllRead"
                            >
                                <font-awesome-icon icon="fa-solid fa-check-double" class="text-orange-500" />
                                <span>Mark All Read</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Segmented Tabs -->
                    <div class="flex overflow-x-auto no-scrollbar p-1 rounded-xl bg-slate-100 border border-slate-200 gap-1 w-full sm:w-auto">
                        <button
                            v-for="tab in [
                                { key: 'all', label: 'All Signals' },
                                { key: 'booking', label: 'Reservations' },
                                { key: 'messages', label: 'Direct Messages' },
                            ]"
                            :key="tab.key"
                            class="whitespace-nowrap flex-1 sm:flex-initial flex items-center justify-center gap-1.5 sm:gap-2 px-3 sm:px-4 py-2 rounded-lg text-xs font-bold transition-all"
                            :class="
                                activeTab === tab.key
                                    ? 'bg-white text-sky-950 shadow-xs'
                                    : 'text-slate-500 hover:text-sky-900'
                            "
                            @click="activeTab = tab.key"
                        >
                            <span>{{ tab.label }}</span>
                            <span
                                v-if="tab.key === 'all' && unreadCount"
                                class="rounded-full bg-orange-500 px-1.5 py-0.2 font-mono text-[10px] font-bold text-white shadow-xs"
                            >
                                {{ unreadCount }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ── Notification List ── -->
            <div class="mx-auto mt-4 sm:mt-8 max-w-4xl space-y-3 sm:space-y-4 px-3 sm:px-8">
                <!-- Empty state -->
                <div
                    v-if="filtered.length === 0"
                    class="flex flex-col items-center gap-3 rounded-2xl border border-slate-200 bg-white py-16 sm:py-20 px-4 text-center shadow-sm"
                >
                    <div class="w-16 h-16 sm:w-20 sm:h-20 bg-sky-50 rounded-full flex items-center justify-center border border-sky-100 mb-2">
                        <font-awesome-icon icon="fa-solid fa-bell-slash" class="text-3xl sm:text-4xl text-sky-300" />
                    </div>
                    <p class="font-display text-lg sm:text-xl font-bold text-sky-900">
                        No notifications yet
                    </p>
                    <p class="text-xs sm:text-sm font-medium text-slate-500">
                        We'll let you know when something comes in.
                    </p>
                </div>

                <!-- Card -->
                <div
                    v-for="notif in filtered"
                    :key="notif.id"
                    class="overflow-hidden rounded-xl bg-white shadow-sm transition-shadow hover:shadow-md border"
                    :class="
                        !notif.read
                            ? 'border-orange-200 border-l-4 border-l-orange-500 bg-orange-50/20'
                            : 'border-slate-200'
                    "
                >
                    <!-- Top row (clickable) -->
                    <div
                        class="flex cursor-pointer items-start gap-3 sm:gap-4 p-4 sm:p-6"
                        @click="markRead(notif)"
                    >
                        <!-- Icon -->
                        <div
                            class="flex h-10 w-10 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-full border shadow-sm"
                            :class="iconBgClass(notif.type)"
                        >
                            <font-awesome-icon v-if="notif.type === 'booking_reminder'" icon="fa-solid fa-clock" class="text-base sm:text-lg" />
                            <font-awesome-icon v-else-if="notif.type === 'booking_confirmed'" icon="fa-solid fa-check-circle" class="text-base sm:text-lg" />
                            <font-awesome-icon v-else icon="fa-solid fa-envelope" class="text-base sm:text-lg" />
                        </div>

                        <!-- Content -->
                        <div class="min-w-0 flex-1">
                            <!-- Meta row -->
                            <div
                                class="mb-1.5 sm:mb-2 flex flex-wrap items-center gap-1.5 sm:gap-2"
                            >
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider shadow-sm"
                                    :class="badgeClass(notif.type)"
                                >
                                    {{ typeLabel(notif.type) }}
                                </span>
                                <span
                                    v-if="notif.booking_ref"
                                    class="rounded bg-slate-100 border border-slate-200 px-2 py-0.5 font-mono text-[10px] font-bold text-slate-500 tracking-wider"
                                >
                                    REF: {{ notif.booking_ref }}
                                </span>
                                <span
                                    class="ml-auto hidden text-[11px] font-semibold text-slate-400 sm:inline"
                                >
                                    {{ formatDate(notif.timestamp) }}
                                </span>
                            </div>

                            <!-- Title -->
                            <h3 class="text-sm sm:text-base font-bold text-sky-900 mb-1" :class="{ 'opacity-80': notif.read }">
                                {{ notif.title }}
                            </h3>

                            <!-- Body -->
                            <p
                                class="text-xs sm:text-sm font-medium leading-relaxed text-slate-600"
                                :class="{ 'opacity-80': notif.read }"
                            >
                                {{ notif.body }}
                            </p>

                            <!-- Sender -->
                            <p
                                v-if="notif.sender === 'admin'"
                                class="mt-2 font-mono text-[10px] font-semibold uppercase tracking-wider text-slate-400"
                            >
                                — {{ notif.sender_name }}
                            </p>

                            <!-- Mobile time -->
                            <p class="mt-2 font-mono text-[10px] font-semibold text-slate-400 sm:hidden">
                                {{ formatDate(notif.timestamp) }}
                            </p>
                        </div>

                        <!-- Unread dot -->
                        <span
                            v-if="!notif.read"
                            class="mt-1 sm:mt-2 h-2.5 w-2.5 sm:h-3 sm:w-3 shrink-0 rounded-full bg-orange-500 shadow-sm"
                        />
                    </div>

                    <!-- Thread section -->
                    <div
                        v-if="
                            expandedThread === notif.id &&
                            (notif.thread.length || notif.canReply)
                        "
                        class="border-t border-slate-100 bg-slate-50 px-4 sm:px-6 py-4 sm:py-5"
                    >
                        <!-- Messages -->
                        <div class="space-y-3 sm:space-y-4">
                            <div
                                v-for="msg in notif.thread"
                                :key="msg.id"
                                class="flex"
                                :class="
                                    msg.from === 'client'
                                        ? 'justify-end'
                                        : 'justify-start'
                                "
                            >
                                <div
                                    class="max-w-[90%] sm:max-w-[70%] rounded-2xl px-3.5 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm shadow-sm"
                                    :class="
                                        msg.from === 'client'
                                            ? 'rounded-br-sm bg-sky-600 text-white shadow-sky-600/20'
                                            : 'rounded-bl-sm border border-slate-200 bg-white text-slate-700'
                                    "
                                >
                                    <div class="flex items-baseline justify-between gap-3 mb-1">
                                        <p
                                            class="text-[10px] font-bold uppercase tracking-wider"
                                            :class="msg.from === 'client' ? 'text-sky-200' : 'text-sky-800'"
                                        >
                                            {{ msg.name }}
                                        </p>
                                        <p
                                            class="text-[9px] sm:text-[10px] font-medium"
                                            :class="msg.from === 'client' ? 'text-sky-300' : 'text-slate-400'"
                                        >
                                            {{ formatDate(msg.timestamp) }}
                                        </p>
                                    </div>
                                    <p class="leading-relaxed font-medium whitespace-pre-wrap">{{ msg.body }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Reply -->
                        <div v-if="notif.canReply" class="mt-4 sm:mt-5 border-t border-slate-200 pt-4 sm:pt-5">
                            <!-- Trigger -->
                            <div
                                v-if="replyingTo !== notif.id"
                                class="flex justify-end"
                            >
                                <button
                                    class="rounded-full border border-sky-200 bg-white px-5 py-2 text-xs font-bold text-sky-700 transition-colors hover:bg-sky-50 hover:text-sky-900 shadow-sm"
                                    @click.stop="openReply(notif)"
                                >
                                    <font-awesome-icon
                                        icon="fa-solid fa-reply"
                                        class="mr-1"
                                    />
                                    Reply
                                </button>
                            </div>

                            <!-- Reply box -->
                            <div v-else class="space-y-2.5">
                                <textarea
                                    v-model="replyText"
                                    rows="3"
                                    placeholder="Type your reply here… (Ctrl + Enter to send)"
                                    class="w-full resize-none rounded-xl border border-slate-300 px-3.5 sm:px-4 py-2.5 sm:py-3 text-xs sm:text-sm font-medium text-slate-700 outline-none transition-shadow focus:border-orange-500 focus:ring-1 focus:ring-orange-500 shadow-sm"
                                    @keydown.ctrl.enter="sendReply(notif)"
                                />
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        class="rounded-lg px-3.5 py-1.5 text-xs font-bold text-slate-500 transition-colors hover:text-red-500 hover:bg-red-50"
                                        @click.stop="replyingTo = null"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        class="flex items-center justify-center gap-1.5 rounded-lg bg-orange-500 px-4 py-1.5 text-xs font-bold text-white transition-colors hover:bg-orange-600 disabled:cursor-not-allowed disabled:opacity-50 shadow-md shadow-orange-500/20"
                                        :disabled="!replyText.trim()"
                                        @click.stop="sendReply(notif)"
                                    >
                                        Send
                                        <font-awesome-icon
                                            icon="fa-solid fa-paper-plane"
                                        />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expand toggle -->
                    <button
                        v-if="notif.thread.length || notif.canReply"
                        class="w-full border-t border-slate-100 bg-slate-50 py-2.5 sm:py-3 text-xs font-bold uppercase tracking-widest text-sky-700 transition-colors hover:bg-slate-100"
                        @click="toggleThread(notif)"
                    >
                        <font-awesome-icon :icon="expandedThread === notif.id ? 'fa-solid fa-chevron-up' : 'fa-solid fa-chevron-down'" class="mr-1" />
                        {{
                            expandedThread === notif.id
                                ? "Hide thread"
                                : notif.thread.length
                                  ? `View thread (${notif.thread.length})`
                                  : "Reply to message"
                        }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>