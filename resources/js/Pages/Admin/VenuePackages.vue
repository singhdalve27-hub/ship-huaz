<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Table from "@/Components/Table.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import Modal from "@/Components/Modal.vue";
import TextInput from "@/Components/TextInput.vue";
import TextAreaInput from "@/Components/TextAreaInput.vue";
import SelectInput from "@/Components/SelectInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref, computed, watch } from "vue";
import { useFormatter } from "@/Composables/useFormatter";
import { useModal } from "@/Composables/useModal";

const props = defineProps({
    venuePackages: {
        type: Array,
        default: () => [],
    },
    eventTypes: {
        type: Array,
        default: () => [],
    },
});

const { formatAmount } = useFormatter();
const modal = useModal();
const viewMode = ref("table"); // 'table' or 'cards'

// Image Preview & Landmark Deck Presets
const imagePreview = ref(null);
const standardVenues = [
    {
        title: "The Admiral's Grand Deck",
        tag: "Grand Ballroom",
        defaultGuests: 250,
        image: "/images/venue.jpg",
        description: "Premier main deck ballroom with natural sea breeze ventilation, theater stage, VIP mezzanine, and panoramic bay vistas.",
    },
    {
        title: "Sunset Promenade & Skyline Terrace",
        tag: "Open-Air Deck",
        defaultGuests: 150,
        image: "/images/venue2.jpg",
        description: "Open-air upper ship deck offering breath-taking coastal sunset breezes, fairy festoon lighting, and acoustic stage sets.",
    },
    {
        title: "Captain's Executive Function Hall",
        tag: "Conference & Dining",
        defaultGuests: 80,
        image: "/images/dining.jpg",
        description: "Executive maritime-styled hall with high-lumen laser projector, conference audio, fiber Wi-Fi, and private dining setup.",
    },
];

const applyVenuePreset = (preset) => {
    form.title = preset.title;
    form.guests = preset.defaultGuests;
    form.description = preset.description;
    imagePreview.value = preset.image;
    form.image = null;
    form.preset_image = preset.image;
};

const handleImageChange = (e) => {
    const file = e.target.files[0];
    form.image = file;
    form.preset_image = null;
    if (file) {
        imagePreview.value = URL.createObjectURL(file);
    } else {
        imagePreview.value = null;
    }
};

const resolveImageUrl = (img) => {
    if (!img) return "/images/venue.jpg";
    if (typeof img === "string") {
        if (img.includes("XmTOpOEzc8Q71BziIlsEPiIcOn36151otlKZ9b5I")) return "/images/venue.jpg";
        if (img.includes("6pdOrmIILX1jfPKuzGK7Wes1hNF0L0hzLfIWkjBF")) return "/images/venue2.jpg";
        if (img.includes("4wdCZSeyN6xugrJkZucQ8gY32U519AtZoR7gGyOJ")) return "/images/dining.jpg";
        if (img.startsWith("http://") || img.startsWith("https://") || img.startsWith("/")) return img;
    }
    return "/" + img;
};

// Event Types dropdown options
const eventTypeOptions = computed(() => [
    { value: "", label: "-- General / All Occasions --" },
    ...props.eventTypes.map((et) => ({
        value: et.id,
        label: et.type || et.name || `Occasion #${et.id}`,
    })),
]);

// Filters for Admin view: by Venue Deck and by Event Type
const selectedVenueFilter = ref("all");
const selectedEventFilter = ref("all");

// Unique venue titles present in database
const uniqueVenueTitles = computed(() => {
    const titles = new Set();
    (props.venuePackages || []).forEach((p) => {
        if (p.title) titles.add(p.title);
    });
    return Array.from(titles);
});

// Table Columns
const tableColumns = [
    { key: "image", label: "Deck Cover", slot: "image" },
    { key: "details", label: "Venue Deck & Inclusions", slot: "details" },
    { key: "event_type", label: "Event Occasion", slot: "event_type" },
    { key: "guests", label: "Capacity", slot: "guests" },
    { key: "rates", label: "Morning / Afternoon / Night / Full Day Rates", slot: "rates" },
    { key: "status", label: "Status", slot: "status" },
    { key: "actions", label: "Actions", slot: "actions" },
];

const formatTableData = (packages) => {
    return (packages || []).map((item, index) => ({
        id: index + 1,
        venue_package_id: item.id,
        title: item.title,
        description: item.description,
        image: item.image,
        event_type_id: item.event_type_id,
        event_type_name: item.event_type?.type || item.event_type?.name || "All Occasions",
        guests: item.guests,
        price: item.price,
        price_morning: item.price_morning,
        price_afternoon: item.price_afternoon,
        price_night: item.price_night,
        price_fullday: item.price_fullday,
        price_visitor: item.price_visitor,
        status: item.status,
    }));
};

const rawTableData = ref(formatTableData(props.venuePackages));

watch(
    () => props.venuePackages,
    (newPackages) => {
        rawTableData.value = formatTableData(newPackages);
    },
);

const filteredTableData = computed(() => {
    return rawTableData.value.filter((item) => {
        const matchesVenue =
            selectedVenueFilter.value === "all" || item.title === selectedVenueFilter.value;
        const matchesEvent =
            selectedEventFilter.value === "all" ||
            String(item.event_type_id) === String(selectedEventFilter.value);
        return matchesVenue && matchesEvent;
    });
});

const tableActions = {
    isDateFilterShow: false,
    isPerPageShow: true,
    isSearchShow: true,
};

const statusConfig = {
    active: {
        label: "Live & Active",
        classes: "bg-emerald-50 text-emerald-700 border border-emerald-200/80 ring-1 ring-emerald-500/20",
    },
    inactive: {
        label: "Draft / Archived",
        classes: "bg-rose-50 text-rose-700 border border-rose-200/80 ring-1 ring-rose-500/20",
    },
};

const statusFormat = ref([
    { value: "active", label: "Active (Bookable on System)" },
    { value: "inactive", label: "Inactive (Hidden)" },
]);

const form = useForm({
    id: "",
    event_type_id: "",
    title: "",
    description: "",
    guests: "",
    price: "",
    price_morning: "",
    price_afternoon: "",
    price_night: "",
    price_fullday: "",
    price_visitor: "",
    status: "active",
    image: null,
    preset_image: null,
});

const addModalOpen = () => {
    form.reset();
    form.clearErrors();
    imagePreview.value = "/images/venue.jpg";
    modal.title.value = "Create Venue Package for Event";
    modal.type.value = "Add";
    modal.icon.value = "fa-solid fa-plus-circle";
    modal.openModal();
};

const editModalOpen = (item) => {
    form.clearErrors();
    form.id = item.venue_package_id;
    form.event_type_id = item.event_type_id || "";
    form.title = item.title;
    form.description = item.description;
    form.guests = item.guests;
    form.price = item.price;
    form.price_morning = item.price_morning || "";
    form.price_afternoon = item.price_afternoon || "";
    form.price_night = item.price_night || "";
    form.price_fullday = item.price_fullday || "";
    form.price_visitor = item.price_visitor || "";
    form.status = item.status;
    form.image = null;
    form.preset_image = null;
    imagePreview.value = resolveImageUrl(item.image);

    modal.title.value = "Edit Venue Package Rates";
    modal.type.value = "Edit";
    modal.icon.value = "fa-solid fa-pen-to-square";
    modal.openModal();
};

// Quick Duplicate for another event
const cloneForAnotherEvent = (item) => {
    form.clearErrors();
    form.id = "";
    form.event_type_id = "";
    form.title = item.title;
    form.description = item.description;
    form.guests = item.guests;
    form.price = item.price;
    form.price_morning = item.price_morning || "";
    form.price_afternoon = item.price_afternoon || "";
    form.price_night = item.price_night || "";
    form.price_fullday = item.price_fullday || "";
    form.price_visitor = item.price_visitor || "";
    form.status = "active";
    form.image = null;
    form.preset_image = item.image;
    imagePreview.value = resolveImageUrl(item.image);

    modal.title.value = `Set Rates for New Event (${item.title})`;
    modal.type.value = "Add";
    modal.icon.value = "fa-solid fa-copy";
    modal.openModal();
};

const deleteModalOpen = (item) => {
    form.id = item.venue_package_id;
    modal.title.value = "Delete Venue Package";
    modal.type.value = "Delete";
    modal.icon.value = "fa-solid fa-trash";
    modal.openModal();
};

const modalClose = () => {
    form.reset();
    form.clearErrors();
    imagePreview.value = null;
    modal.closeModal();
};

const submit = () => {
    if (modal.type.value === "Add") {
        form.transform((data) => {
            const payload = { ...data };
            delete payload.id;
            return payload;
        }).post(route("admin.venue-packages.store"), {
            onSuccess: () => modalClose(),
            forceFormData: true,
        });
    } else if (modal.type.value === "Edit") {
        form.transform((data) => ({
            ...data,
            _method: "put",
        })).post(route("admin.venue-packages.update", form.id), {
            onSuccess: () => modalClose(),
            forceFormData: true,
        });
    } else if (modal.type.value === "Delete") {
        form.delete(route("admin.venue-packages.destroy", form.id), {
            onSuccess: () => modalClose(),
        });
    }
};

const getDeckTag = (guests) => {
    const g = Number(guests);
    if (g >= 200) return "Grand Ballroom";
    if (g >= 100) return "Open-Air Deck";
    return "Executive Hall";
};
</script>

<template>
    <Head title="Venue Packages & Event Rates — Admin Console" />

    <AdminLayout>
        <div class="space-y-6">
            <!-- ══════════════════════════════════════════════════════ -->
            <!-- 1. EXECUTIVE BANNER                                    -->
            <!-- ══════════════════════════════════════════════════════ -->
            <div class="bg-gradient-to-r from-slate-900 via-sky-950 to-slate-900 rounded-2xl p-6 lg:p-8 text-white shadow-xl shadow-slate-900/10 border border-slate-800 relative overflow-hidden">
                <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-orange-500/10 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute right-10 top-6 opacity-10 text-white pointer-events-none">
                    <font-awesome-icon icon="fa-solid fa-ship" class="text-9xl" />
                </div>

                <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/20 border border-orange-400/30 text-orange-300 text-xs font-bold tracking-wider uppercase mb-2.5">
                            <font-awesome-icon icon="fa-solid fa-hotel" />
                            Multi-Event Venue Matrix
                        </div>
                        <h1 class="text-2xl lg:text-3xl font-black tracking-tight text-white">
                            Venue Decks & Event Pricing Rates
                        </h1>
                        <p class="text-slate-300 text-sm mt-1 max-w-2xl leading-relaxed">
                            Each venue deck (Grand Deck, Promenade, Function Hall) can host any event type (Wedding, Debut, Corporate, Reunion) with customized rates for <strong>Morning</strong>, <strong>Afternoon</strong>, <strong>Night</strong>, and <strong>Full Day</strong>.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0 flex-wrap">
                        <!-- Toggle View Mode Button -->
                        <div class="inline-flex p-1 bg-slate-800/90 rounded-xl border border-slate-700 shadow-inner">
                            <button
                                type="button"
                                @click="viewMode = 'table'"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                                    viewMode === 'table' ? 'bg-orange-500 text-white shadow-sm' : 'text-slate-300 hover:text-white'
                                ]"
                            >
                                <font-awesome-icon icon="fa-solid fa-table-list" />
                                <span>Table View</span>
                            </button>
                            <button
                                type="button"
                                @click="viewMode = 'cards'"
                                :class="[
                                    'px-3 py-1.5 rounded-lg text-xs font-bold transition-all flex items-center gap-1.5 cursor-pointer',
                                    viewMode === 'cards' ? 'bg-orange-500 text-white shadow-sm' : 'text-slate-300 hover:text-white'
                                ]"
                            >
                                <font-awesome-icon icon="fa-solid fa-grip" />
                                <span>Cards Showcase</span>
                            </button>
                        </div>

                        <!-- Add Button -->
                        <button
                            class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm px-6 py-3 rounded-xl shadow-lg shadow-orange-500/25 transition-all duration-200 cursor-pointer active:scale-95"
                            @click="addModalOpen"
                        >
                            <font-awesome-icon icon="fa-solid fa-plus-circle" />
                            <span>Set Venue & Event Rate</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════ -->
            <!-- 2. VENUE & EVENT FILTERS BAR                           -->
            <!-- ══════════════════════════════════════════════════════ -->
            <div class="bg-white p-4 lg:p-5 rounded-2xl border border-slate-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Filter by Venue -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold font-mono uppercase text-slate-500">Filter Venue:</span>
                        <select
                            v-model="selectedVenueFilter"
                            class="text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:ring-orange-500 focus:border-orange-500 cursor-pointer"
                        >
                            <option value="all">All Venue Decks ({{ rawTableData.length }})</option>
                            <option v-for="title in uniqueVenueTitles" :key="title" :value="title">
                                {{ title }}
                            </option>
                        </select>
                    </div>

                    <!-- Filter by Event Occasion -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold font-mono uppercase text-slate-500">Event Occasion:</span>
                        <select
                            v-model="selectedEventFilter"
                            class="text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-slate-800 focus:ring-orange-500 focus:border-orange-500 cursor-pointer"
                        >
                            <option value="all">All Event Types ({{ eventTypes.length }})</option>
                            <option v-for="et in eventTypes" :key="et.id" :value="et.id">
                                {{ et.type || et.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Reset Filter -->
                    <button
                        v-if="selectedVenueFilter !== 'all' || selectedEventFilter !== 'all'"
                        @click="selectedVenueFilter = 'all'; selectedEventFilter = 'all'"
                        class="text-xs font-bold text-rose-600 hover:text-rose-700 bg-rose-50 px-3 py-2 rounded-xl border border-rose-200 transition-colors"
                    >
                        ✕ Clear Filter
                    </button>
                </div>

                <div class="text-xs text-slate-500 font-medium">
                    Showing <strong class="text-slate-900">{{ filteredTableData.length }}</strong> of {{ rawTableData.length }} total configured event rates
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════ -->
            <!-- 3A. TABLE VIEW (Default Matrix)                        -->
            <!-- ══════════════════════════════════════════════════════ -->
            <div v-show="viewMode === 'table'" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 lg:p-6 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">
                            Venue Decks & Shift Rates Manifest
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Shift Rates: Morning (8:00 AM – 12:00 PM), Afternoon (1:00 PM – 5:00 PM), Night (6:00 PM – 10:00 PM), and Full Day Exclusive.
                        </p>
                    </div>
                </div>

                <div class="p-4 lg:p-6">
                    <Table :data="filteredTableData" :columns="tableColumns" :actions="tableActions">
                        <!-- Cover Image Thumbnail -->
                        <template #image="{ row }">
                            <div class="w-24 h-16 rounded-xl overflow-hidden bg-slate-100 border border-slate-200 shadow-xs shrink-0 relative group">
                                <img
                                    :src="resolveImageUrl(row.image)"
                                    :alt="row.title"
                                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                                />
                                <span class="absolute bottom-1 right-1 bg-slate-900/85 text-[9px] font-mono font-bold text-white px-1.5 py-0.5 rounded">
                                    {{ getDeckTag(row.guests) }}
                                </span>
                            </div>
                        </template>

                        <!-- Details & Description -->
                        <template #details="{ row }">
                            <div class="space-y-1 max-w-sm">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-black text-slate-900 hover:text-orange-600 transition-colors">
                                        {{ row.title }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed">
                                    {{ row.description }}
                                </p>
                            </div>
                        </template>

                        <!-- Event Type / Occasion -->
                        <template #event_type="{ value }">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-orange-50 border border-orange-200 text-orange-800 text-xs font-bold shadow-2xs">
                                <span>🎉</span>
                                <span>{{ value }}</span>
                            </span>
                        </template>

                        <!-- Capacity -->
                        <template #guests="{ value }">
                            <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">
                                <font-awesome-icon icon="fa-solid fa-users" class="text-orange-500" />
                                <span>{{ value }} Pax</span>
                            </div>
                        </template>

                        <!-- Rates: Morning, Afternoon, Night, Full Day -->
                        <template #rates="{ row }">
                            <div class="space-y-1.5 text-left min-w-[240px]">
                                <div class="text-sm font-black text-orange-600">
                                    {{ formatAmount(row.price) }}
                                    <span class="text-[10px] font-semibold text-slate-400">/ base rate</span>
                                </div>
                                <div class="grid grid-cols-2 gap-1 text-[11px] font-mono">
                                    <!-- Morning -->
                                    <div class="px-2 py-0.5 rounded bg-sky-50 text-sky-900 border border-sky-100 flex items-center justify-between">
                                        <span class="font-sans text-[10px] text-sky-700 font-semibold">🌅 Morning:</span>
                                        <strong>{{ row.price_morning ? formatAmount(row.price_morning) : '—' }}</strong>
                                    </div>
                                    <!-- Afternoon -->
                                    <div class="px-2 py-0.5 rounded bg-amber-50 text-amber-900 border border-amber-100 flex items-center justify-between">
                                        <span class="font-sans text-[10px] text-amber-700 font-semibold">☀️ Afternoon:</span>
                                        <strong>{{ row.price_afternoon ? formatAmount(row.price_afternoon) : '—' }}</strong>
                                    </div>
                                    <!-- Night -->
                                    <div class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-900 border border-indigo-100 flex items-center justify-between">
                                        <span class="font-sans text-[10px] text-indigo-700 font-semibold">🌙 Night:</span>
                                        <strong>{{ row.price_night ? formatAmount(row.price_night) : '—' }}</strong>
                                    </div>
                                    <!-- Full Day -->
                                    <div class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-900 border border-emerald-100 flex items-center justify-between">
                                        <span class="font-sans text-[10px] text-emerald-700 font-semibold">🚢 Full Day:</span>
                                        <strong>{{ row.price_fullday ? formatAmount(row.price_fullday) : '—' }}</strong>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Status Badge -->
                        <template #status="{ value }">
                            <span
                                class="inline-flex items-center gap-1.5 text-xs font-bold px-3 py-1 rounded-full whitespace-nowrap"
                                :class="statusConfig[value]?.classes ?? 'bg-slate-100 text-slate-600'"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="value === 'active' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                {{ statusConfig[value]?.label ?? value }}
                            </span>
                        </template>

                        <!-- Action Buttons -->
                        <template #actions="{ row }">
                            <div class="flex items-center justify-center gap-1.5">
                                <button
                                    class="inline-flex items-center gap-1 bg-sky-50 hover:bg-sky-100 text-sky-700 font-bold px-2.5 py-1.5 rounded-lg border border-sky-200 transition-colors text-xs active:scale-95 cursor-pointer"
                                    @click="cloneForAnotherEvent(row)"
                                    title="Clone rates for another event type"
                                >
                                    <font-awesome-icon icon="fa-solid fa-copy" />
                                    <span>Clone</span>
                                </button>
                                <button
                                    class="inline-flex items-center gap-1 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold px-2.5 py-1.5 rounded-lg border border-slate-200 transition-colors text-xs active:scale-95 cursor-pointer"
                                    @click="editModalOpen(row)"
                                    title="Edit Package Rates"
                                >
                                    <font-awesome-icon icon="fa-solid fa-pen-to-square" class="text-orange-500" />
                                    <span>Edit</span>
                                </button>
                                <button
                                    class="inline-flex items-center gap-1 bg-rose-50 hover:bg-rose-100 text-rose-700 font-bold px-2.5 py-1.5 rounded-lg border border-rose-200 transition-colors text-xs active:scale-95 cursor-pointer"
                                    @click="deleteModalOpen(row)"
                                    title="Delete Package"
                                >
                                    <font-awesome-icon icon="fa-solid fa-trash" />
                                </button>
                            </div>
                        </template>
                    </Table>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════ -->
            <!-- 3B. CARDS SHOWCASE VIEW                                -->
            <!-- ══════════════════════════════════════════════════════ -->
            <div v-show="viewMode === 'cards'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div
                    v-for="pkg in filteredTableData"
                    :key="pkg.venue_package_id"
                    class="group bg-white rounded-3xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col"
                >
                    <!-- Cover Image with Badges -->
                    <div class="relative h-56 overflow-hidden bg-slate-100">
                        <img
                            :src="resolveImageUrl(pkg.image)"
                            :alt="pkg.title"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                        />
                        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-black/20"></div>

                        <div class="absolute top-4 left-4 flex flex-col gap-1.5 items-start">
                            <span class="bg-white text-slate-900 font-mono text-[11px] font-bold px-3 py-1 rounded-lg shadow uppercase tracking-wider">
                                {{ getDeckTag(pkg.guests) }}
                            </span>
                            <span class="bg-orange-500 text-white font-body text-xs font-black px-3 py-0.5 rounded-md shadow flex items-center gap-1">
                                🎉 {{ pkg.event_type_name }}
                            </span>
                        </div>

                        <div class="absolute top-4 right-4">
                            <span class="bg-sky-900 text-white text-xs font-bold px-3 py-1.5 rounded-lg shadow flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-users" class="text-amber-300" />
                                Up to {{ pkg.guests }} Pax
                            </span>
                        </div>

                        <div class="absolute bottom-3 left-4 right-4 flex items-end justify-between text-white">
                            <div>
                                <span class="text-[10px] font-mono uppercase tracking-wider text-amber-300 font-bold block drop-shadow">
                                    Base Package Rate
                                </span>
                                <div class="text-2xl font-black text-white drop-shadow-md">
                                    {{ formatAmount(pkg.price) }}
                                    <span class="text-xs font-bold text-slate-200">/ slot</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Content -->
                    <div class="p-6 flex flex-col flex-1">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <h3 class="text-xl font-black text-slate-900 group-hover:text-orange-600 transition-colors">
                                {{ pkg.title }}
                            </h3>
                            <span
                                class="text-[10px] font-bold px-2 py-0.5 rounded-full"
                                :class="pkg.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                            >
                                {{ pkg.status }}
                            </span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed mb-4 line-clamp-3">
                            {{ pkg.description }}
                        </p>

                        <!-- All Shift Rates: Morning, Afternoon, Night, Full Day -->
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 mb-5 space-y-2 text-xs">
                            <div class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400">
                                Shift Rates Breakdown:
                            </div>
                            <div class="flex items-center justify-between text-slate-700">
                                <span class="flex items-center gap-1 font-medium">🌅 Morning (8 AM – 12 PM)</span>
                                <strong class="text-sky-900 font-mono font-bold">{{ pkg.price_morning ? formatAmount(pkg.price_morning) : 'Base' }}</strong>
                            </div>
                            <div class="flex items-center justify-between text-slate-700">
                                <span class="flex items-center gap-1 font-medium">☀️ Afternoon (1 PM – 5 PM)</span>
                                <strong class="text-amber-900 font-mono font-bold">{{ pkg.price_afternoon ? formatAmount(pkg.price_afternoon) : 'Base' }}</strong>
                            </div>
                            <div class="flex items-center justify-between text-slate-700">
                                <span class="flex items-center gap-1 font-medium">🌙 Night (6 PM – 10 PM)</span>
                                <strong class="text-indigo-900 font-mono font-bold">{{ pkg.price_night ? formatAmount(pkg.price_night) : 'Base' }}</strong>
                            </div>
                            <div class="flex items-center justify-between text-slate-700 pt-1 border-t border-slate-200">
                                <span class="flex items-center gap-1 font-bold text-slate-900">🚢 Full Day Exclusive</span>
                                <strong class="text-orange-600 font-mono font-black text-sm">{{ pkg.price_fullday ? formatAmount(pkg.price_fullday) : 'Custom' }}</strong>
                            </div>
                        </div>

                        <!-- Card Buttons -->
                        <div class="mt-auto pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
                            <button
                                type="button"
                                @click="cloneForAnotherEvent(pkg)"
                                class="px-3 py-2 rounded-xl bg-sky-50 hover:bg-sky-100 text-sky-700 font-bold text-xs transition-colors flex items-center gap-1.5 cursor-pointer border border-sky-200"
                                title="Duplicate for another event"
                            >
                                <font-awesome-icon icon="fa-solid fa-copy" />
                                <span>Clone</span>
                            </button>
                            <button
                                type="button"
                                @click="editModalOpen(pkg)"
                                class="flex-1 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer shadow-sm"
                            >
                                <font-awesome-icon icon="fa-solid fa-pen-to-square" />
                                <span>Edit Rates</span>
                            </button>
                            <button
                                type="button"
                                @click="deleteModalOpen(pkg)"
                                class="px-3 py-2 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs transition-colors cursor-pointer border border-rose-200"
                                title="Delete"
                            >
                                <font-awesome-icon icon="fa-solid fa-trash" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ══════════════════════════════════════════════════════ -->
            <!-- 4. ADD / EDIT VENUE & EVENT RATES MODAL                -->
            <!-- ══════════════════════════════════════════════════════ -->
            <Modal
                :show="modal.type.value === 'Add' || modal.type.value === 'Edit'"
                @close="!form.processing && modalClose()"
                :maxWidth="'2xl'"
            >
                <!-- Modal Top Header -->
                <div class="bg-gradient-to-r from-slate-900 to-sky-950 px-6 py-5 text-white flex items-center justify-between rounded-t-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-orange-500/20 text-orange-400 flex items-center justify-center text-lg border border-orange-500/30">
                            <font-awesome-icon :icon="modal.icon.value" />
                        </div>
                        <div>
                            <h2 class="font-bold text-base text-white">
                                {{ modal.title.value }}
                            </h2>
                            <p class="text-xs text-slate-300">
                                Configure pricing rates for this venue deck based on the selected event occasion and time shift.
                            </p>
                        </div>
                    </div>
                    <button
                        @click="modalClose"
                        class="text-slate-400 hover:text-white transition-colors p-1 cursor-pointer"
                        :disabled="form.processing"
                    >
                        ✕
                    </button>
                </div>

                <!-- Modal Body Form -->
                <div class="p-6 max-h-[75vh] overflow-y-auto space-y-5 bg-white">
                    <!-- Standard Landmark Presets for 1-Click Fill -->
                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-xl">
                        <span class="text-[11px] font-mono font-bold uppercase text-slate-500 block mb-2">
                            ⚡ Quick Pick Landmark Deck (Auto-fill Name, Pax & Photo):
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="preset in standardVenues"
                                :key="preset.title"
                                type="button"
                                @click="applyVenuePreset(preset)"
                                class="px-2.5 py-1 rounded-lg text-xs font-bold bg-white hover:bg-orange-50 hover:text-orange-600 border border-slate-200 shadow-2xs transition-all cursor-pointer flex items-center gap-1.5"
                            >
                                <font-awesome-icon icon="fa-solid fa-ship" class="text-orange-500 text-[10px]" />
                                <span>{{ preset.title }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Venue Deck Name & Event Occasion Selector -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="title" value="Venue Deck Name *" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                            <TextInput
                                id="title"
                                type="text"
                                class="mt-1.5 block w-full border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm"
                                v-model="form.title"
                                required
                                placeholder="e.g. The Admiral's Grand Deck"
                            />
                            <InputError :message="form.errors.title" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="event_type_id" value="Event Occasion (Wedding, Debut, Corporate, etc.) *" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                            <SelectInput
                                id="event_type_id"
                                :options="eventTypeOptions"
                                class="mt-1.5 block w-full border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm"
                                v-model="form.event_type_id"
                            />
                            <p class="text-[10px] text-slate-500 mt-1">
                                Select the event occasion that corresponds to these rates for this venue deck.
                            </p>
                            <InputError :message="form.errors.event_type_id" class="mt-1" />
                        </div>
                    </div>

                    <!-- Pax Capacity & Base Starting Price -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="guests" value="Guest Capacity (Max Pax) *" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                            <div class="relative mt-1.5">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                                    <font-awesome-icon icon="fa-solid fa-users" class="text-xs" />
                                </span>
                                <TextInput
                                    id="guests"
                                    type="number"
                                    min="1"
                                    class="block w-full pl-10 border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm"
                                    v-model="form.guests"
                                    required
                                    placeholder="e.g. 250"
                                />
                            </div>
                            <InputError :message="form.errors.guests" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="price" value="Base / Default Price (₱) *" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                            <div class="relative mt-1.5">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none font-bold text-xs">
                                    ₱
                                </span>
                                <TextInput
                                    id="price"
                                    type="number"
                                    min="0"
                                    class="block w-full pl-10 border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm"
                                    v-model="form.price"
                                    required
                                    placeholder="e.g. 28000"
                                />
                            </div>
                            <InputError :message="form.errors.price" class="mt-1" />
                        </div>
                    </div>

                    <!-- Featured Photo Upload -->
                    <div>
                        <InputLabel for="image" value="Featured Deck Photograph" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                        <div class="mt-2 flex flex-col sm:flex-row items-start sm:items-center gap-4">
                            <div class="w-28 h-20 rounded-xl overflow-hidden border-2 border-slate-200 bg-slate-100 shrink-0 shadow-xs relative">
                                <img
                                    v-if="imagePreview"
                                    :src="imagePreview"
                                    alt="Preview"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-slate-400 text-xs">
                                    No Image
                                </div>
                            </div>
                            <div class="flex-1 w-full space-y-1">
                                <input
                                    type="file"
                                    id="image"
                                    accept="image/*"
                                    @change="handleImageChange"
                                    class="block w-full text-xs text-slate-600
                                           file:mr-4 file:py-2 file:px-4
                                           file:rounded-lg file:border-0
                                           file:text-xs file:font-bold
                                           file:bg-orange-50 file:text-orange-600
                                           hover:file:bg-orange-100 transition-colors cursor-pointer"
                                />
                                <p class="text-[11px] text-slate-400">
                                    Upload a custom photo or choose from the landmark deck presets above.
                                </p>
                            </div>
                        </div>
                        <InputError :message="form.errors.image" class="mt-1" />
                    </div>

                    <!-- Description -->
                    <div>
                        <InputLabel for="description" value="Deck Description & Inclusions *" class="text-slate-900 font-bold text-xs uppercase tracking-wider" />
                        <TextAreaInput
                            id="description"
                            class="mt-1.5 block w-full border-slate-300 focus:border-orange-500 focus:ring-orange-500 rounded-xl shadow-sm text-sm resize-none"
                            v-model="form.description"
                            rows="2"
                            required
                            placeholder="Describe the stage, sound setup, seaside ambiance, and package amenities for this event..."
                        />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <!-- Shift Rates: Morning, Afternoon, Night, Full Day -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-mono font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                                <font-awesome-icon icon="fa-solid fa-clock" class="text-orange-500" />
                                Exact Shift Rates for this Event & Venue
                            </span>
                            <span class="text-[10px] text-slate-400">Customize rates for each shift session</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <!-- Morning -->
                            <div>
                                <InputLabel for="price_morning" value="🌅 Morning (8 AM – 12 PM)" class="text-[11px] font-bold text-sky-800" />
                                <TextInput
                                    id="price_morning"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full text-xs rounded-lg font-mono font-bold"
                                    v-model="form.price_morning"
                                    placeholder="₱ Morning rate"
                                />
                            </div>
                            <!-- Afternoon -->
                            <div>
                                <InputLabel for="price_afternoon" value="☀️ Afternoon (1 PM – 5 PM)" class="text-[11px] font-bold text-amber-800" />
                                <TextInput
                                    id="price_afternoon"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full text-xs rounded-lg font-mono font-bold"
                                    v-model="form.price_afternoon"
                                    placeholder="₱ Afternoon rate"
                                />
                            </div>
                            <!-- Night -->
                            <div>
                                <InputLabel for="price_night" value="🌙 Night (6 PM – 10 PM)" class="text-[11px] font-bold text-indigo-800" />
                                <TextInput
                                    id="price_night"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full text-xs rounded-lg font-mono font-bold"
                                    v-model="form.price_night"
                                    placeholder="₱ Night rate"
                                />
                            </div>
                        </div>

                        <!-- Full Day & Visitor Mode -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-200">
                            <div>
                                <InputLabel for="price_fullday" value="🚢 Full Day Exclusive (8 AM – 10 PM)" class="text-[11px] font-bold text-emerald-800" />
                                <TextInput
                                    id="price_fullday"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full text-xs rounded-lg font-mono font-bold text-emerald-700"
                                    v-model="form.price_fullday"
                                    placeholder="₱ Full day exclusive rate"
                                />
                            </div>
                            <div>
                                <InputLabel for="price_visitor" value="🎟️ Visitor Mode Rate (Per Head, optional)" class="text-[11px] font-bold text-slate-700" />
                                <TextInput
                                    id="price_visitor"
                                    type="number"
                                    min="0"
                                    class="mt-1 block w-full text-xs rounded-lg font-mono font-bold"
                                    v-model="form.price_visitor"
                                    placeholder="₱ rate per visitor"
                                />
                            </div>
                        </div>

                        <div v-if="modal.type.value === 'Edit'" class="pt-2 border-t border-slate-200">
                            <InputLabel for="status" value="Visibility Status" class="text-[11px] font-bold text-slate-700" />
                            <SelectInput
                                id="status"
                                :options="statusFormat"
                                class="mt-1 block w-full text-xs rounded-lg"
                                v-model="form.status"
                            />
                        </div>
                    </div>
                </div>

                <!-- Modal Bottom Actions -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3 rounded-b-xl">
                    <button
                        type="button"
                        class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold text-sm rounded-xl transition-colors shadow-sm cursor-pointer"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        @click="modalClose"
                        :disabled="form.processing"
                    >
                        Cancel
                    </button>

                    <button
                        type="button"
                        class="px-6 py-2.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white font-bold text-sm rounded-xl shadow-md shadow-orange-500/25 transition-all flex items-center gap-2 cursor-pointer active:scale-95"
                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                        @click="submit"
                        :disabled="form.processing"
                    >
                        <font-awesome-icon v-if="form.processing" icon="fa-solid fa-spinner" spin />
                        <span v-else>{{ modal.type.value === 'Add' ? 'Save Venue Event Rate' : 'Update Rates' }}</span>
                        <font-awesome-icon v-if="!form.processing" icon="fa-solid fa-check" />
                    </button>
                </div>
            </Modal>

            <!-- ══════════════════════════════════════════════════════ -->
            <!-- 5. DELETE MODAL                                        -->
            <!-- ══════════════════════════════════════════════════════ -->
            <Modal
                :show="modal.type.value === 'Delete'"
                @close="!form.processing && modalClose()"
                :maxWidth="'sm'"
            >
                <div class="p-6 bg-white rounded-xl">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 flex items-center justify-center text-xl mb-4 border border-rose-200">
                        <font-awesome-icon icon="fa-solid fa-triangle-exclamation" />
                    </div>
                    <h2 class="font-bold text-lg text-slate-900">
                        Delete Venue Event Rate?
                    </h2>
                    <p class="text-slate-600 text-sm mt-2 leading-relaxed">
                        Are you sure you want to delete this venue package rate for this event? Existing bookings associated with this package will remain safely preserved in the master manifest.
                    </p>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button
                            type="button"
                            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-sm rounded-xl transition-colors cursor-pointer"
                            :disabled="form.processing"
                            @click="modalClose"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            class="px-5 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm rounded-xl shadow-md shadow-rose-600/20 transition-colors flex items-center gap-2 cursor-pointer"
                            :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                            @click="submit"
                            :disabled="form.processing"
                        >
                            <font-awesome-icon v-if="form.processing" icon="fa-solid fa-spinner" spin />
                            <span v-else>Confirm Delete</span>
                        </button>
                    </div>
                </div>
            </Modal>
        </div>
    </AdminLayout>
</template>
