<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Table from "@/Components/Table.vue";
import DangerButton from "@/Components/DangerButton.vue";
import Modal from "@/Components/Modal.vue";
import SecondaryButton from "@/Components/SecondaryButton.vue";
import { Link, Head, useForm, usePage } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import { useFormatter } from "@/Composables/useFormatter";
import { useModal } from "@/Composables/useModal";

const props = defineProps({
    bookings: Array,
});

const localBookings = ref([...(props.bookings ?? [])]);

watch(
    () => props.bookings,
    (newVal) => {
        localBookings.value = [...(newVal ?? [])];
    }
);

const page = usePage();
const flash = computed(() => page.props.flash);

watch(flash, (newFlash) => {
    if (newFlash?.success) {
        console.log(newFlash.success);
    }
});

// ─── View Toggle & Filters ────────────────────────────────────────────────
const viewMode = ref("table"); // 'table' or 'schedule'
const scheduleDateFilter = ref("");
const scheduleVenueFilter = ref("all");
const scheduleModeFilter = ref("all");
const scheduleSearch = ref("");

// Stats counters
const stats = computed(() => {
    const list = localBookings.value;
    return {
        total: list.length,
        exclusive: list.filter((b) => (b.booking_mode || 'exclusive') === 'exclusive').length,
        visitor: list.filter((b) => b.booking_mode === 'visitor').length,
        pending: list.filter((b) => b.status === 'pending').length,
        confirmed: list.filter((b) => b.status === 'confirmed').length,
    };
});

// Venue options for filter dropdown
const venueOptions = computed(() => {
    const names = localBookings.value
        .map((b) => b.venue_package?.title)
        .filter(Boolean);
    return [...new Set(names)];
});

// Grouped schedule by date
const bookingsByDate = computed(() => {
    const map = {};
    const sorted = [...localBookings.value].sort(
        (a, b) => new Date(a.date || 0) - new Date(b.date || 0)
    );

    sorted.forEach((b) => {
        if (!b.date) return;
        if (scheduleDateFilter.value && b.date !== scheduleDateFilter.value) return;
        if (scheduleVenueFilter.value !== "all" && b.venue_package?.title !== scheduleVenueFilter.value) return;
        if (scheduleModeFilter.value !== "all" && (b.booking_mode || 'exclusive') !== scheduleModeFilter.value) return;

        if (scheduleSearch.value.trim()) {
            const q = scheduleSearch.value.toLowerCase().trim();
            const matches =
                (b.booking_ref && b.booking_ref.toLowerCase().includes(q)) ||
                (b.venue_package?.title && b.venue_package.title.toLowerCase().includes(q)) ||
                (b.event_type?.type && b.event_type.type.toLowerCase().includes(q)) ||
                (b.guest_first_name && b.guest_first_name.toLowerCase().includes(q)) ||
                (b.guest_last_name && b.guest_last_name.toLowerCase().includes(q)) ||
                (b.user?.user_info?.first_name && b.user.user_info.first_name.toLowerCase().includes(q));
            if (!matches) return;
        }

        if (!map[b.date]) {
            map[b.date] = [];
        }
        map[b.date].push(b);
    });

    return Object.entries(map).map(([date, list]) => ({
        date,
        bookings: list,
    }));
});

const tableColumns = [
    { key: "booking_ref", label: "Booking Ref", slot: "ref" },
    { key: "event_details", label: "Event Details", slot: "event_details" },
    { key: "mode", label: "Mode", slot: "mode" },
    { key: "package", label: "Package", slot: "package" },
    { key: "contact", label: "Contact", slot: "contact" },
    { key: "payment", label: "Payment", slot: "payment" },
    { key: "date", label: "Date", slot: "date" },
    { key: "status", label: "Status", slot: "status" },
    { key: "actions", label: "Actions", slot: "actions" },
];

const tableActions = {
    isDateFilterShow: true,
    isPerPageShow: true,
    isSearchShow: true,
};

const statusConfig = {
    pending: {
        label: "Pending",
        classes: "bg-amber-100 text-amber-800 border border-amber-200",
    },
    confirmed: {
        label: "Confirmed",
        classes: "bg-sky-100 text-sky-800 border border-sky-200",
    },
    cancelled: {
        label: "Cancelled",
        classes: "bg-red-100 text-red-800 border border-red-200",
    },
    completed: {
        label: "Completed",
        classes: "bg-emerald-100 text-emerald-800 border border-emerald-200",
    },
};

const actionConfig = {
    confirm: {
        label: "Confirm Booking",
        icon: "fa-solid fa-check",
        description: "Are you sure you want to confirm this booking?",
        status: "confirmed",
    },
    cancel: {
        label: "Cancel Booking",
        icon: "fa-solid fa-xmark",
        description: "Are you sure you want to cancel this booking?",
        status: "cancelled",
    },
    complete: {
        label: "Complete Booking",
        icon: "fa-solid fa-check-double",
        description: "Are you sure you want to mark this booking as completed?",
        status: "completed",
    },
};

const { formatAmount, formatDate, formatPhone } = useFormatter();
const modal = useModal();
const currentAction = ref(null);

const openStatusModal = (item, action) => {
    form.id = item.id;
    form.status = actionConfig[action].status;
    currentAction.value = action;

    modal.title.value = actionConfig[action].label;
    modal.type.value = "Status";
    modal.icon.value = actionConfig[action].icon;
    modal.openModal();
};

const modalClose = () => {
    form.reset();
    currentAction.value = null;
    modal.closeModal();
};

const form = useForm({
    id: "",
    status: "",
});

const submit = () => {
    form.put(route("admin.bookings.update", form.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            const booking = localBookings.value.find((b) => b.id === form.id);
            if (booking) booking.status = form.status;
            modalClose();
        },
    });
};

const formatExactTime = (time24) => {
    if (!time24) return "";
    const [hours, minutes] = time24.split(":");
    const h = parseInt(hours);
    const ampm = h >= 12 ? "PM" : "AM";
    const h12 = h % 12 || 12;
    return `${h12}:${minutes} ${ampm}`;
};
</script>

<template>
    <Head title="Bookings & Deck Manifest | Admin" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- Top Header & View Mode Switcher -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-sky-100 shadow-sm">
                <div>
                    <h1 class="text-2xl font-display font-bold text-sky-950">
                        Ship Deck Bookings & Schedule Manifest
                    </h1>
                    <p class="text-xs font-medium text-slate-500 mt-1">
                        Monitor active deck reservations, detect exclusive conflicts, and review visitor attendance.
                    </p>
                </div>

                <!-- View Switcher Tabs -->
                <div class="inline-flex p-1 rounded-xl bg-slate-100 border border-slate-200">
                    <button
                        @click="viewMode = 'table'"
                        :class="[
                            'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all',
                            viewMode === 'table'
                                ? 'bg-white text-sky-950 shadow-sm'
                                : 'text-slate-600 hover:text-sky-950'
                        ]"
                    >
                        <font-awesome-icon icon="fa-solid fa-table-list" />
                        Table View
                    </button>
                    <button
                        @click="viewMode = 'schedule'"
                        :class="[
                            'flex items-center gap-2 px-4 py-2 rounded-lg text-xs font-bold transition-all',
                            viewMode === 'schedule'
                                ? 'bg-sky-900 text-white shadow-sm'
                                : 'text-slate-600 hover:text-sky-950'
                        ]"
                    >
                        <font-awesome-icon icon="fa-solid fa-calendar-days" />
                        Deck Schedule & Timeline
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white p-4 rounded-xl border border-sky-100 shadow-sm">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block mb-1">Total Bookings</span>
                    <p class="text-2xl font-bold font-display text-sky-950">{{ stats.total }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-sky-100 shadow-sm">
                    <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider block mb-1">Exclusive Events</span>
                    <p class="text-2xl font-bold font-display text-indigo-900">{{ stats.exclusive }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-sky-100 shadow-sm">
                    <span class="text-xs font-bold text-amber-500 uppercase tracking-wider block mb-1">Visitor Passes</span>
                    <p class="text-2xl font-bold font-display text-amber-900">{{ stats.visitor }}</p>
                </div>
                <div class="bg-white p-4 rounded-xl border border-sky-100 shadow-sm">
                    <span class="text-xs font-bold text-orange-500 uppercase tracking-wider block mb-1">Pending Review</span>
                    <p class="text-2xl font-bold font-display text-orange-600">{{ stats.pending }}</p>
                </div>
            </div>

            <!-- ── TABLE VIEW ─────────────────────────────────────────── -->
            <div
                v-if="viewMode === 'table'"
                class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden px-6 py-6"
            >
                <Table
                    :data="localBookings"
                    :columns="tableColumns"
                    :actions="tableActions"
                >
                    <!-- Ref -->
                    <template #ref="{ row }">
                        <p class="text-sm font-mono font-bold text-sky-900">
                            {{ row.booking_ref }}
                        </p>
                    </template>

                    <!-- Event Details -->
                    <template #event_details="{ row }">
                        <p class="text-sm font-bold text-slate-800">
                            {{ row.event_type?.type ?? "—" }}
                        </p>
                        <p class="text-xs text-slate-500 mt-0.5">
                            {{ row.date ? formatDate(row.date) : "—" }} &bull; {{ row.time_slot ?? "—" }}
                        </p>
                        <p v-if="row.exact_time" class="text-xs text-orange-600 font-bold">
                            Arrival: {{ formatExactTime(row.exact_time) }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ row.guest_count }} {{ row.booking_mode === 'visitor' ? 'visitors' : 'guests' }}
                        </p>
                        <p
                            v-if="row.guest_request_notes"
                            class="text-xs text-slate-400 italic mt-1"
                        >
                            "{{ row.guest_request_notes }}"
                        </p>
                    </template>

                    <!-- Mode -->
                    <template #mode="{ row }">
                        <span
                            :class="[
                                'inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider',
                                row.booking_mode === 'visitor'
                                    ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                    : 'bg-indigo-100 text-indigo-900 border border-indigo-300'
                            ]"
                        >
                            <font-awesome-icon
                                :icon="row.booking_mode === 'visitor' ? 'fa-solid fa-users' : 'fa-solid fa-lock'"
                                class="text-[10px]"
                            />
                            {{ row.booking_mode === 'visitor' ? 'Visitor' : 'Exclusive' }}
                        </span>
                    </template>

                    <!-- Package -->
                    <template #package="{ row }">
                        <p class="text-sm font-bold text-sky-900">
                            {{ row.venue_package?.title ?? "—" }}
                        </p>
                        <p class="text-xs text-slate-500">
                            Base: {{
                                row.venue_package?.price
                                    ? formatAmount(row.venue_package.price)
                                    : "—"
                            }}
                        </p>
                        <template v-if="row.package_add_ons?.length">
                            <hr class="my-1.5 border-slate-100" />
                            <p class="text-[10px] uppercase font-bold text-slate-400 mb-0.5">
                                Add-ons:
                            </p>
                            <p
                                v-for="(addon, index) in row.package_add_ons"
                                :key="index"
                                class="text-xs text-slate-600"
                            >
                                • {{ addon.title }} ({{
                                    formatAmount(addon.price)
                                }})
                            </p>
                        </template>
                        <hr class="my-1.5 border-slate-100" />
                        <p class="text-sm font-bold text-orange-600">
                            Total: {{ formatAmount(row.total_payment) }}
                        </p>
                    </template>

                    <!-- Contact -->
                    <template #contact="{ row }">
                        <p class="text-sm font-bold text-slate-800">
                            {{ row.guest_first_name }} {{ row.guest_last_name }}
                        </p>
                        <p class="text-xs text-slate-500">
                            Email: {{ row.guest_email }}
                        </p>
                        <p class="text-xs text-slate-500">
                            Phone: {{ formatPhone(row.guest_phone) }}
                        </p>
                        <template v-if="row.user">
                            <hr class="my-1 border-slate-100" />
                            <p class="text-[10px] text-slate-400">
                                Account: {{ row.user?.user_info?.first_name }} {{ row.user?.user_info?.last_name }}
                            </p>
                        </template>
                    </template>

                    <!-- Payment -->
                    <template #payment="{ row }">
                        <p class="text-sm font-bold text-slate-800">
                            {{ row.payment_option?.payment ?? "Walk-in" }}
                        </p>
                        <template v-if="row.payment_option">
                            <p class="text-xs text-slate-500">
                                {{ row.payment_option.account }} ({{ formatPhone(row.payment_option.number) }})
                            </p>
                        </template>
                        <template v-if="row.payment_transaction_ref">
                            <hr class="my-1 border-slate-100" />
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Ref #</p>
                            <p class="text-xs font-mono text-slate-600">
                                {{ row.payment_transaction_ref }}
                            </p>
                        </template>
                    </template>

                    <!-- Date -->
                    <template #date="{ row }">
                        <p class="text-sm font-medium text-slate-700">
                            {{ row.date ? formatDate(row.date) : "—" }}
                        </p>
                    </template>

                    <!-- Status -->
                    <template #status="{ value }">
                        <span
                            class="inline-block text-xs font-bold px-3 py-1 rounded-full capitalize shadow-sm"
                            :class="
                                statusConfig[value]?.classes ??
                                'bg-slate-100 text-slate-600'
                            "
                        >
                            {{ statusConfig[value]?.label ?? value }}
                        </span>
                    </template>

                    <!-- Actions -->
                    <template #actions="{ row }">
                        <div class="flex flex-col gap-1.5">
                            <button
                                v-if="row.status === 'pending'"
                                class="bg-sky-600 hover:bg-sky-700 text-white font-bold px-3 py-1.5 rounded-md transition-colors text-xs flex items-center justify-center gap-1 shadow-sm"
                                @click="openStatusModal(row, 'confirm')"
                            >
                                <font-awesome-icon icon="fa-solid fa-check" />
                                Confirm
                            </button>

                            <button
                                v-if="['pending', 'confirmed'].includes(row.status)"
                                class="bg-red-500 hover:bg-red-600 text-white font-bold px-3 py-1.5 rounded-md transition-colors text-xs flex items-center justify-center gap-1 shadow-sm"
                                @click="openStatusModal(row, 'cancel')"
                            >
                                <font-awesome-icon icon="fa-solid fa-xmark" />
                                Cancel
                            </button>

                            <button
                                v-if="row.status === 'confirmed'"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-md transition-colors text-xs flex items-center justify-center gap-1 shadow-sm"
                                @click="openStatusModal(row, 'complete')"
                            >
                                <font-awesome-icon icon="fa-solid fa-check-double" />
                                Complete
                            </button>
                        </div>
                    </template>
                </Table>
            </div>

            <!-- ── DECK SCHEDULE & TIMELINE VIEW ───────────────────────── -->
            <div v-if="viewMode === 'schedule'" class="space-y-6">
                <!-- Filters Bar -->
                <div class="bg-white p-6 rounded-2xl border border-sky-100 shadow-sm flex flex-wrap items-center gap-4">
                    <div class="flex-1 min-w-[220px]">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Search Booking</label>
                        <input
                            v-model="scheduleSearch"
                            type="text"
                            placeholder="Search by ref, client, deck, or event…"
                            class="w-full text-xs font-medium border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500"
                        />
                    </div>

                    <div class="w-auto">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Filter by Specific Date</label>
                        <input
                            v-model="scheduleDateFilter"
                            type="date"
                            class="text-xs font-medium border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500"
                        />
                    </div>

                    <div class="w-auto">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Venue Deck</label>
                        <select
                            v-model="scheduleVenueFilter"
                            class="text-xs font-medium border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500"
                        >
                            <option value="all">All Decks</option>
                            <option v-for="name in venueOptions" :key="name" :value="name">{{ name }}</option>
                        </select>
                    </div>

                    <div class="w-auto">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Reservation Mode</label>
                        <select
                            v-model="scheduleModeFilter"
                            class="text-xs font-medium border border-slate-300 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500"
                        >
                            <option value="all">All Modes</option>
                            <option value="exclusive">Exclusive Only</option>
                            <option value="visitor">Visitor Only</option>
                        </select>
                    </div>

                    <button
                        v-if="scheduleDateFilter || scheduleVenueFilter !== 'all' || scheduleModeFilter !== 'all' || scheduleSearch"
                        @click="scheduleDateFilter = ''; scheduleVenueFilter = 'all'; scheduleModeFilter = 'all'; scheduleSearch = '';"
                        class="mt-5 text-xs font-bold text-orange-600 hover:text-orange-700 underline"
                    >
                        Reset Filters
                    </button>
                </div>

                <!-- Schedule Grouped by Date -->
                <div v-if="bookingsByDate.length === 0" class="bg-white p-12 rounded-2xl border border-slate-200 text-center">
                    <div class="w-16 h-16 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3 text-slate-400">
                        <font-awesome-icon icon="fa-solid fa-calendar-xmark" class="text-2xl" />
                    </div>
                    <h3 class="text-base font-bold text-slate-800 mb-1">No bookings match the schedule filters</h3>
                    <p class="text-xs text-slate-500">Try changing the date, deck filter, or search keyword.</p>
                </div>

                <div v-for="day in bookingsByDate" :key="day.date" class="bg-white rounded-2xl border border-sky-100 shadow-sm overflow-hidden">
                    <!-- Date Group Header -->
                    <div class="bg-sky-950 text-white px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">📅</span>
                            <div>
                                <h3 class="font-display font-bold text-base sm:text-lg">
                                    {{ formatDate(day.date) }}
                                </h3>
                                <p class="text-xs text-sky-200 font-medium">
                                    {{ day.bookings.length }} booking(s) scheduled on this day
                                </p>
                            </div>
                        </div>
                        <span class="text-xs font-bold bg-white/10 px-3 py-1 rounded-full border border-white/20">
                            {{ day.date }}
                        </span>
                    </div>

                    <!-- Bookings on this date -->
                    <div class="p-6 divide-y divide-slate-100 space-y-4">
                        <div
                            v-for="booking in day.bookings"
                            :key="booking.id"
                            class="pt-4 first:pt-0 flex flex-col lg:flex-row lg:items-center justify-between gap-6"
                        >
                            <!-- Venue & Shift Info -->
                            <div class="flex items-start gap-4 min-w-[280px]">
                                <div class="w-12 h-12 rounded-xl bg-sky-50 border border-sky-200 flex flex-col items-center justify-center text-sky-900 flex-shrink-0">
                                    <font-awesome-icon icon="fa-solid fa-ship" class="text-base text-sky-700" />
                                    <span class="text-[9px] font-bold uppercase tracking-wider">Deck</span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <h4 class="font-bold text-sky-950 text-base">
                                            {{ booking.venue_package?.title ?? 'Venue Deck' }}
                                        </h4>
                                        <span
                                            :class="[
                                                'text-[10px] font-bold px-2 py-0.5 rounded-full uppercase tracking-wider',
                                                booking.booking_mode === 'visitor'
                                                    ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                                    : 'bg-indigo-100 text-indigo-900 border border-indigo-300'
                                            ]"
                                        >
                                            {{ booking.booking_mode === 'visitor' ? '👥 Visitor' : '🔒 Exclusive' }}
                                        </span>
                                    </div>
                                    <p class="text-xs font-bold text-orange-600 mb-1">
                                        {{ booking.event_type?.type ?? 'Event' }}
                                    </p>
                                    <div class="text-xs text-slate-500 flex flex-wrap items-center gap-2">
                                        <span>🕒 {{ booking.time_slot }}</span>
                                        <span v-if="booking.exact_time" class="font-bold text-sky-900">
                                            (Arrival: {{ formatExactTime(booking.exact_time) }})
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Booker & Guests Info -->
                            <div class="text-xs text-slate-600 space-y-1 min-w-[220px]">
                                <p class="font-bold text-slate-800 text-sm">
                                    {{ booking.guest_first_name }} {{ booking.guest_last_name }}
                                </p>
                                <p>Email: {{ booking.guest_email }}</p>
                                <p>Phone: {{ formatPhone(booking.guest_phone) }}</p>
                                <p class="font-bold text-sky-900">
                                    👥 {{ booking.guest_count }} {{ booking.booking_mode === 'visitor' ? 'visitors' : 'expected guests' }}
                                </p>
                            </div>

                            <!-- Payment & Status -->
                            <div class="min-w-[160px]">
                                <span
                                    class="inline-block text-xs font-bold px-3 py-1 rounded-full capitalize mb-1"
                                    :class="statusConfig[booking.status]?.classes ?? 'bg-slate-100 text-slate-600'"
                                >
                                    {{ statusConfig[booking.status]?.label ?? booking.status }}
                                </span>
                                <p class="text-sm font-bold text-orange-600">
                                    {{ formatAmount(booking.total_payment) }}
                                </p>
                                <p class="text-[11px] text-slate-400 font-mono">
                                    {{ booking.booking_ref }}
                                </p>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="booking.status === 'pending'"
                                    @click="openStatusModal(booking, 'confirm')"
                                    class="bg-sky-600 hover:bg-sky-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition-colors shadow-sm"
                                >
                                    Confirm
                                </button>
                                <button
                                    v-if="booking.status === 'confirmed'"
                                    @click="openStatusModal(booking, 'complete')"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition-colors shadow-sm"
                                >
                                    Complete
                                </button>
                                <button
                                    v-if="['pending', 'confirmed'].includes(booking.status)"
                                    @click="openStatusModal(booking, 'cancel')"
                                    class="bg-red-500 hover:bg-red-600 text-white font-bold px-3 py-1.5 rounded-lg text-xs transition-colors shadow-sm"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Modal -->
            <Modal
                :show="modal.type.value === 'Status'"
                @close="!form.processing && modalClose()"
                :maxWidth="'sm'"
            >
                <div class="px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h2 class="text-lg font-bold text-sky-950">
                        <font-awesome-icon :icon="modal.icon.value" class="text-orange-500 mr-1" />
                        {{ modal.title.value }}
                    </h2>

                    <div class="mt-4">
                        <p class="text-sm text-slate-600 leading-relaxed">
                            {{
                                currentAction
                                    ? actionConfig[currentAction].description
                                    : "Are you sure you want to proceed?"
                            }}
                        </p>
                    </div>

                    <div class="mt-6 flex justify-between">
                        <SecondaryButton
                            :class="{ 'opacity-25': form.processing }"
                            @click="modalClose"
                            :disabled="form.processing"
                        >
                            Cancel
                        </SecondaryButton>

                        <DangerButton
                            class="flex items-center gap-1"
                            :class="{ 'opacity-25': form.processing }"
                            @click="submit"
                            :disabled="form.processing"
                        >
                            <div class="text-sm" v-if="form.processing">
                                <font-awesome-icon
                                    icon="fa-solid fa-spinner"
                                    spin
                                />
                            </div>
                            Confirm
                            <font-awesome-icon icon="fa-solid fa-thumbs-up" />
                        </DangerButton>
                    </div>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>
