<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import CalendarPicker from "@/Components/CalendarPicker.vue";
import Table from "@/Components/Table.vue";
import Modal from "@/Components/Modal.vue";
import axios from "axios";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { ref, computed, watch, onMounted } from "vue";
import { useFormatter } from "@/Composables/useFormatter";
import { useModal } from "@/Composables/useModal";

const props = defineProps({
    bookings: Array,
    reservedSchedules: {
        type: Array,
        default: () => [],
    },
    eventTypes: Array,
    venuePackages: Array,
    packageAddOns: Array,
    paymentOptions: Array,
});

const localBookings = ref([...(props.bookings ?? [])]);

watch(
    () => props.bookings,
    (newVal) => {
        localBookings.value = [...(newVal ?? [])];
    },
);

const page = usePage();

const errors = computed(() => page.props.errors ?? {});

// ─── View Mode ─────────────────────────────────────────────────────────────
const showForm = ref(false);

// ─── Schedule Viewer Modal ────────────────────────────────────────────────
const showScheduleModal = ref(false);
const scheduleSearch = ref("");
const scheduleDeckFilter = ref("all");
const scheduleSlotFilter = ref("all");

const openScheduleModal = () => {
    showScheduleModal.value = true;
};
const closeScheduleModal = () => {
    showScheduleModal.value = false;
};

// Reserved dates for calendar indicators
const reservedDates = computed(() => {
    return [...new Set((props.reservedSchedules || []).map((s) => s.date))];
});

// Reservations on currently selected date
const selectedDateReservations = computed(() => {
    if (!eventDate.value) return [];
    return (props.reservedSchedules || []).filter(
        (s) => s.date === eventDate.value && s.status !== "cancelled"
    );
});

// Filter options for schedule modal
const deckFilterOptions = computed(() => {
    const titles = (props.reservedSchedules || [])
        .map((s) => s.venue_title)
        .filter(Boolean);
    return [...new Set(titles)];
});

// Filtered upcoming schedules
const filteredScheduleList = computed(() => {
    return (props.reservedSchedules || []).filter((s) => {
        if (s.status === "cancelled") return false;

        if (scheduleDeckFilter.value !== "all" && s.venue_title !== scheduleDeckFilter.value) {
            return false;
        }

        if (scheduleSlotFilter.value !== "all") {
            if (scheduleSlotFilter.value === "morning" && !s.time_slot.includes("Morning") && !s.time_slot.includes("8:00 AM – 12:00 PM")) return false;
            if (scheduleSlotFilter.value === "afternoon" && !s.time_slot.includes("Afternoon") && !s.time_slot.includes("1:00 PM – 5:00 PM")) return false;
            if (scheduleSlotFilter.value === "night" && !s.time_slot.includes("Night") && !s.time_slot.includes("6:00 PM – 10:00 PM")) return false;
            if (scheduleSlotFilter.value === "fullday" && !s.time_slot.includes("Full Day")) return false;
        }

        if (scheduleSearch.value.trim()) {
            const q = scheduleSearch.value.toLowerCase().trim();
            const matches =
                (s.venue_title && s.venue_title.toLowerCase().includes(q)) ||
                (s.event_type && s.event_type.toLowerCase().includes(q)) ||
                (s.booker_name && s.booker_name.toLowerCase().includes(q)) ||
                (s.date && s.date.includes(q)) ||
                (s.time_slot && s.time_slot.toLowerCase().includes(q));
            if (!matches) return false;
        }
        return true;
    });
});

const tableColumns = [
    { key: "ref", label: "Booking Ref", slot: "ref" },
    { key: "event", label: "Event" },
    { key: "date", label: "Date", slot: "date" },
    { key: "time", label: "Time", slot: "time" },
    { key: "package", label: "Package", slot: "package" },
    { key: "mode", label: "Mode", slot: "mode" },
    { key: "amount", label: "Amount", slot: "amount" },
    { key: "payment_method", label: "Payment", slot: "payment" },
    { key: "status", label: "Status", slot: "status" },
    { key: "actions", label: "Actions", slot: "actions" },
];

// ─── Table: Default Data ───────────────────────────────────────────────────
const tableData = computed(() =>
    localBookings.value.map((b) => {
        if (b.ref !== undefined) return b;
        return {
            id: b.id,
            ref: b.booking_ref,
            event: b.event_type?.type ?? "—",
            date: b.date,
            time: b.time_slot,
            exact_time: b.exact_time,
            package: b.venue_package?.title ?? "—",
            booking_mode: b.booking_mode ?? "exclusive",
            addons:
                Array.isArray(b.package_add_ons) && b.package_add_ons.length
                    ? b.package_add_ons.map((a) => a.title ?? a).join(", ")
                    : "",
            amount: b.total_payment,
            payment_method: b.payment_option?.payment ?? "—",
            payment_ref: b.payment_transaction_ref ?? "—",
            status: b.status,
        };
    }),
);

const tableActions = {
    isDateFilterShow: false,
    isPerPageShow: false,
    isSearchShow: true,
};

const statusConfig = {
    pending: {
        label: "Pending",
        classes: "bg-orange-100 text-orange-800 border border-orange-200",
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
        classes: "bg-lime-100 text-lime-800 border border-lime-200",
    },
};

// ─── Cancel Booking ─────────────────────────────────────────────────────────
const {
    open: showCancelModal,
    openModal: openCancelModalRaw,
    closeModal: closeCancelModal,
} = useModal();

const bookingToCancel = ref(null);
const isCancelling = ref(false);

const openCancelModal = (row) => {
    if (row.status === "cancelled" || row.status === "completed") return;
    bookingToCancel.value = row;
    openCancelModalRaw();
};

const confirmCancel = () => {
    if (!bookingToCancel.value) return;

    isCancelling.value = true;

    router.put(
        route("client.booking.cancel", {
            booking: bookingToCancel.value.id,
        }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                closeCancelModal();
                bookingToCancel.value = null;
            },
            onError: () => {
                alert("Could not cancel this booking. Please try again.");
            },
            onFinish: () => {
                isCancelling.value = false;
            },
        },
    );
};

// ─── Step Management ───────────────────────────────────────────────────────
const currentStep = ref(1);
const totalSteps = 6;

const steps = [
    { id: 1, label: "Event Date", icon: "📅" },
    { id: 2, label: "Packages", icon: "⚓" },
    { id: 3, label: "Contact", icon: "👤" },
    { id: 4, label: "Review", icon: "📋" },
    { id: 5, label: "Payment", icon: "💳" },
    { id: 6, label: "Confirmed", icon: "✅" },
];

const goToStep = (step) => {
    if (step >= 1 && step <= totalSteps) currentStep.value = step;
};
const nextStep = () => goToStep(currentStep.value + 1);
const prevStep = () => goToStep(currentStep.value - 1);

// ─── Terms & Conditions Modal ───────────────────────────────────────────────
const {
    open: showTermsModal,
    openModal: openTermsModal,
    closeModal: closeTermsModal,
} = useModal();

const acceptTerms = () => {
    closeTermsModal();
    nextStep();
};

// ─── Step 1: Event Date & Time ─────────────────────────────────────────────
const eventDate = ref("");
const timeSlotId = ref(""); 
const exactTime = ref(""); 
const today = new Date().toISOString().split("T")[0];

const timeSlots = [
    { id: "morning", label: "Morning", time: "8:00 AM – 12:00 PM" },
    { id: "afternoon", label: "Afternoon", time: "1:00 PM – 5:00 PM" },
    { id: "night", label: "Night", time: "6:00 PM – 10:00 PM" }, 
    { id: "fullday", label: "Full Day", time: "8:00 AM – 5:00 PM" },
];

const eventType = ref("");
const bookingMode = ref("exclusive"); 

const selectedEventTypeLabel = computed(
    () =>
        activeEventTypes.value.find((e) => e.id === eventType.value)?.type ??
        "",
);

const selectedTimeSlotData = computed(() =>
    timeSlots.find((t) => t.id === timeSlotId.value),
);

// Helper for checking slot overlaps
const checkTimeSlotOverlap = (bookedTime, requestedTime) => {
    if (!bookedTime || !requestedTime) return false;
    if (bookedTime === requestedTime) return true;
    const isFullDayBooked =
        bookedTime.includes("Full Day") ||
        bookedTime.includes("8:00 AM – 5:00 PM") ||
        bookedTime.includes("8:00 AM – 10:00 PM");
    const isFullDayReq =
        requestedTime.includes("Full Day") ||
        requestedTime.includes("8:00 AM – 5:00 PM") ||
        requestedTime.includes("8:00 AM – 10:00 PM");
    return isFullDayBooked || isFullDayReq;
};

// Venue conflict detector for a given package
const getVenueConflict = (pkg) => {
    if (!eventDate.value || !selectedTimeSlotData.value) return null;
    const requestedTime = selectedTimeSlotData.value.time;

    return (props.reservedSchedules || []).find((s) => {
        if (s.date !== eventDate.value) return false;
        if (s.status === "cancelled") return false;
        if (s.booking_mode !== "exclusive") return false;

        const matchesVenue =
            s.venue_package_id === pkg.id ||
            (s.venue_title &&
                pkg.name &&
                s.venue_title.toLowerCase().trim() ===
                    pkg.name.toLowerCase().trim());

        if (!matchesVenue) return false;

        return checkTimeSlotOverlap(s.time_slot, requestedTime);
    });
};



// ─── Step 1: Availability Check ───────────────────────────────────────────
const availabilityChecked = ref(false);
const isAvailable = ref(true);
const isCheckingAvailability = ref(false);
const availabilityError = ref("");

const checkAvailability = async () => {
    if (!eventDate.value || !timeSlotId.value) return;

    availabilityChecked.value = false;
    isAvailable.value = true;
    availabilityError.value = "";
    isCheckingAvailability.value = true;

    try {
        const { data } = await axios.post(
            route("client.booking.check-availability"),
            {
                date: eventDate.value,
                time_slot: selectedTimeSlotData.value.time,
                venue_package_id: selectedPackage.value || null,
            },
        );

        isAvailable.value = data.available;
        availabilityChecked.value = true;

        if (data.conflict) {
            bookingMode.value = "visitor";
        }
        nextStep();
    } catch (error) {
        console.error("Availability check:", error);
        nextStep();
    } finally {
        isCheckingAvailability.value = false;
    }
};

watch([eventDate, timeSlotId], () => {
    availabilityChecked.value = false;
    isAvailable.value = true;
    availabilityError.value = "";
});

watch(eventType, (newVal, oldVal) => {
    if (oldVal !== "") {
        selectedPackage.value = null;
        selectedAddons.value = [];
        addonQuantities.value = {};
    }
});

const activeEventTypes = computed(() =>
    (props.eventTypes ?? []).filter((e) => e.status === "active"),
);

const step1Valid = computed(
    () => eventDate.value && timeSlotId.value && exactTime.value && eventType.value,
);

// ─── Step 3: Contact Information ───────────────────────────────────────────
const contact = ref({
    firstName: "",
    lastName: "",
    email: "",
    phone: "",
    guestCount: 20,
    requests: "",
});

const step3Valid = computed(
    () =>
        contact.value.firstName &&
        contact.value.lastName &&
        contact.value.email &&
        contact.value.phone,
);

// Sync guestCount with per_head addons
watch(() => contact.value.guestCount, (newCount) => {
    selectedAddons.value.forEach(id => {
        const addon = addons.value.find(a => a.id === id);
        if (addon && addon.pricing_type === 'per_head') {
            addonQuantities.value[id] = newCount;
        }
    });
});

// ─── Step 2: Venue Packages & Add-ons ─────────────────────────────────────
const packages = computed(() =>
    (props.venuePackages ?? [])
        .filter((p) => p.status === "active" && p.event_type_id == eventType.value)
        .map((p) => ({
            id: p.id,
            name: p.title,
            desc: p.description,
            price: Number(p.price),
            price_morning: p.price_morning ? Number(p.price_morning) : Number(p.price) * 0.6,
            price_afternoon: p.price_afternoon ? Number(p.price_afternoon) : Number(p.price) * 0.6,
            price_night: p.price_night ? Number(p.price_night) : Number(p.price) * 0.6, 
            price_fullday: p.price_fullday ? Number(p.price_fullday) : Number(p.price),
            price_visitor: p.price_visitor ? Number(p.price_visitor) : 150, 
            capacity: `Up to ${p.guests} guests`,
        }))
);

const addons = computed(() =>
    (props.packageAddOns ?? [])
        .filter((a) => a.status === "active")
        .map((a) => ({
            id: a.id,
            name: a.title,
            desc: a.description,
            price: Number(a.price),
            pricing_type: a.pricing_type || 'fixed',
        })),
);

const selectedPackage = ref(null);
const selectedAddons = ref([]);
const addonQuantities = ref({});

const toggleAddon = (id) => {
    const idx = selectedAddons.value.indexOf(id);
    if (idx === -1) {
        selectedAddons.value.push(id);
        const addOnData = addons.value.find(a => a.id === id);
        if (addOnData?.pricing_type === 'per_head') {
            addonQuantities.value[id] = contact.value.guestCount || 1; 
        } else {
            addonQuantities.value[id] = 1;
        }
    } else {
        selectedAddons.value.splice(idx, 1);
        delete addonQuantities.value[id];
    }
};

const selectPackageCard = (pkg) => {
    selectedPackage.value = pkg.id;
    const conflict = getVenueConflict(pkg);
    if (conflict) {
        bookingMode.value = "visitor";
    }
};

const selectedPackageData = computed(() =>
    packages.value.find((p) => p.id === selectedPackage.value),
);

// Conflict on currently selected package
const currentVenueConflict = computed(() => {
    if (!selectedPackageData.value) return null;
    return getVenueConflict(selectedPackageData.value);
});

// Auto-switch to visitor mode when conflict is detected
watch([selectedPackage, eventDate, timeSlotId], () => {
    if (currentVenueConflict.value) {
        bookingMode.value = "visitor";
    }
});

const packageTotal = computed(() => {
    if (!selectedPackageData.value) return 0;
    const pkg = selectedPackageData.value;
    
    if (bookingMode.value === 'visitor') {
        return pkg.price_visitor * contact.value.guestCount;
    }

    if (timeSlotId.value === 'morning') return pkg.price_morning;
    if (timeSlotId.value === 'afternoon') return pkg.price_afternoon;
    if (timeSlotId.value === 'night') return pkg.price_night; 
    
    return pkg.price_fullday;
});

const addonTotal = computed(() => {
    return selectedAddons.value.reduce((sum, id) => {
        const addOnData = addons.value.find(a => a.id === id);
        if (!addOnData) return sum;
        const qty = addonQuantities.value[id] || 1;
        return sum + (addOnData.price * qty); 
    }, 0);
});

const grandTotal = computed(() => packageTotal.value + addonTotal.value);

const step2Valid = computed(() => !!selectedPackage.value);

// ─── Step 5: Payment ───────────────────────────────────────────────────────
const activePaymentOptions = computed(() =>
    (props.paymentOptions ?? [])
        .filter((o) => o.status === "active")
        .map((o) => ({
            id: o.id,
            label: o.payment,
            number: o.number,
            account: o.account,
            description: o.description,
            isOnline: true,
        })),
);

const payment = ref({
    method: "",
    accountNumber: "",
    transactionNumber: "",
});

const initPaymentMethod = () => {
    if (!payment.value.method && activePaymentOptions.value.length) {
        payment.value.method = activePaymentOptions.value[0].id;
    }
};

initPaymentMethod();

const selectedPaymentOption = computed(() =>
    activePaymentOptions.value.find((o) => o.id === payment.value.method),
);

const downpaymentAmount = computed(() => {
    return Math.round((grandTotal.value * 0.5) * 100) / 100;
});

const copiedNumber = ref(false);
const copyAccountNumber = (num) => {
    if (!num) return;
    navigator.clipboard.writeText(num);
    copiedNumber.value = true;
    setTimeout(() => {
        copiedNumber.value = false;
    }, 2000);
};

const step5Valid = computed(() => {
    if (selectedPaymentOption.value?.isOnline) {
        return (
            payment.value.accountNumber.trim() !== "" &&
            payment.value.transactionNumber.trim() !== ""
        );
    }
    return true;
});

// ─── Confirmation ──────────────────────────────────────────────────────────
const reservationCode = ref("");

const confirmReservation = () => {
    form.date = eventDate.value;
    form.time_slot = selectedTimeSlotData.value.time;
    form.exact_time = exactTime.value; 
    form.booking_mode = bookingMode.value;
    form.event_type_id = eventType.value;
    form.venue_package_id = selectedPackage.value;
    form.package_add_ons = selectedAddons.value; 
    form.guest_first_name = contact.value.firstName;
    form.guest_last_name = contact.value.lastName;
    form.guest_email = contact.value.email;
    form.guest_phone = contact.value.phone;
    form.guest_count = contact.value.guestCount;
    form.guest_request_notes = contact.value.requests;
    form.payment_option_id = payment.value.method;
    form.payment_account_number = payment.value.accountNumber;
    form.payment_transaction_ref = payment.value.transactionNumber;
    form.total_payment = grandTotal.value; 

    form.post(route("client.booking.store"), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
            reservationCode.value = page.props.flash?.booking_ref;
            nextStep();
        },
    });
};

const resetForm = () => {
    currentStep.value = 1;
    eventDate.value = "";
    timeSlotId.value = "";
    exactTime.value = ""; 
    eventType.value = "";
    bookingMode.value = "exclusive";
    selectedPackage.value = null;
    selectedAddons.value = [];
    addonQuantities.value = {};
    contact.value = {
        firstName: "",
        lastName: "",
        email: "",
        phone: "",
        guestCount: 20,
        requests: "",
    };
    payment.value = {
        method: activePaymentOptions.value[0]?.id ?? "",
        accountNumber: "",
        transactionNumber: "",
    };
    reservationCode.value = "";
    showForm.value = false;
};

onMounted(() => {
    if (typeof window !== "undefined") {
        const urlParams = new URLSearchParams(window.location.search);
        const qDate = urlParams.get("date");
        const qPkg = urlParams.get("package_id");
        const qMode = urlParams.get("mode");

        if (qDate || qPkg || qMode) {
            showForm.value = true;
            if (qDate) {
                eventDate.value = qDate;
            }
            if (qMode && (qMode === "exclusive" || qMode === "visitor")) {
                bookingMode.value = qMode;
            }
            if (qPkg && props.venuePackages) {
                const pkg = props.venuePackages.find((p) => p.id == qPkg);
                if (pkg) {
                    eventType.value = pkg.event_type_id;
                    selectedPackage.value = pkg.id;
                }
            }
        }
    }
});

const form = useForm({
    date: "",
    time_slot: "",
    exact_time: "", 
    booking_mode: "",
    event_type_id: "",
    venue_package_id: null,
    package_add_ons: [],
    guest_first_name: "",
    guest_last_name: "",
    guest_email: "",
    guest_phone: "",
    guest_count: 20,
    guest_request_notes: "",
    payment_option_id: "",
    payment_account_number: "",
    payment_transaction_ref: "",
    total_payment: 0,
});

const fmt = (n) => "₱" + Number(n).toLocaleString("en-PH");

const fmtDate = (d) =>
    d
        ? new Date(d + "T00:00:00").toLocaleDateString("en-PH", {
              weekday: "long",
              month: "long",
              day: "numeric",
              year: "numeric",
          })
        : "—";

const formatExactTime = (time24) => {
    if (!time24) return "";
    const [hours, minutes] = time24.split(":");
    const h = parseInt(hours);
    const ampm = h >= 12 ? "PM" : "AM";
    const h12 = h % 12 || 12;
    return `${h12}:${minutes} ${ampm}`;
};

const { formatDate, formatAmount } = useFormatter();

const getPackagePriceDisplay = (pkg) => {
    if (bookingMode.value === 'visitor') return fmt(pkg.price_visitor) + ' /head (Visitor)';
    if (timeSlotId.value === 'morning') return fmt(pkg.price_morning) + ' /morning';
    if (timeSlotId.value === 'afternoon') return fmt(pkg.price_afternoon) + ' /afternoon';
    if (timeSlotId.value === 'night') return fmt(pkg.price_night) + ' /night'; 
    return fmt(pkg.price_fullday) + ' /fullday';
};

onMounted(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const venueIdParam = urlParams.get('venue_id');

    if (venueIdParam && props.venuePackages) {
        const targetPackage = props.venuePackages.find(p => p.id == venueIdParam);
        
        if (targetPackage) {
            showForm.value = true;
            currentStep.value = 1;
            eventType.value = targetPackage.event_type_id;
            selectedPackage.value = Number(venueIdParam);
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    }
});
</script>

<template>
    <Head title="Event Booking | Butal Ship Hauz" />

    <AuthenticatedLayout>
        <div class="min-h-screen">
            <!-- BOOKINGS TABLE VIEW -->
            <div v-if="!showForm">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-50 border border-sky-100 text-[10px] font-mono font-bold tracking-widest uppercase text-sky-800 mb-1.5">
                            <font-awesome-icon icon="fa-solid fa-anchor" class="text-orange-500" />
                            <span>Passenger Reservations • Talibon, Bohol</span>
                        </div>
                        <h1 class="font-display text-2xl sm:text-3xl font-black text-sky-950">
                            Event Bookings Manifest
                        </h1>
                        <p class="text-xs sm:text-sm font-medium text-slate-500 mt-1">
                            Manage your shipboard event reservations and inspect live deck shift availability.
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            @click="openScheduleModal"
                            class="flex items-center justify-center gap-2 bg-sky-900 hover:bg-sky-800 text-white text-xs sm:text-sm font-bold tracking-wide px-5 py-2.5 rounded-xl shadow-sm transition-all"
                        >
                            <font-awesome-icon icon="fa-solid fa-calendar-days" class="text-orange-400" />
                            View Deck Schedules
                        </button>
                        <button
                            @click="
                                showForm = true;
                                currentStep = 1;
                            "
                            class="flex items-center justify-center gap-2 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 text-white text-xs sm:text-sm font-bold tracking-wide px-6 py-2.5 rounded-xl shadow-md shadow-orange-500/25 transition-all hover:scale-[1.02]"
                        >
                            <font-awesome-icon icon="fa-solid fa-plus-circle" />
                            Reserve a Deck
                        </button>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-sky-100 overflow-hidden px-6 py-6">
                    <Table
                        :data="tableData"
                        :columns="tableColumns"
                        :actions="tableActions"
                    >
                        <template #ref="{ value }">
                            <span class="font-mono text-sm text-sky-800 font-bold">{{ value }}</span>
                        </template>
                        <template #event="{ row }">
                            <span class="font-bold text-slate-700">{{ row.event }}</span>
                        </template>
                        <template #date="{ value }">
                            <span class="font-medium text-slate-600">{{ formatDate(value) }}</span>
                        </template>
                        <template #time="{ row }">
                            <span class="font-medium text-slate-700 capitalize flex flex-col">
                                <span>{{ row.time }}</span>
                                <span v-if="row.exact_time" class="text-xs text-orange-600 font-bold mt-0.5">{{ formatExactTime(row.exact_time) }}</span>
                            </span>
                        </template>
                        <template #package="{ row }">
                            <span class="font-bold text-sky-900">{{ row.package }}</span>
                            <span v-if="row.addons" class="block text-wrap text-slate-500 font-medium text-xs mt-1">
                                + {{ row.addons }}
                            </span>
                        </template>
                        <template #mode="{ row }">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1 text-[11px] font-bold px-2.5 py-1 rounded-full uppercase tracking-wider',
                                    row.booking_mode === 'visitor'
                                        ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                        : 'bg-sky-100 text-sky-900 border border-sky-300'
                                ]"
                            >
                                <font-awesome-icon :icon="row.booking_mode === 'visitor' ? 'fa-solid fa-users' : 'fa-solid fa-lock'" class="text-[10px]" />
                                {{ row.booking_mode === 'visitor' ? 'Visitor' : 'Exclusive' }}
                            </span>
                        </template>
                        <template #amount="{ value }">
                            <span class="font-bold text-orange-600">{{ formatAmount(value) }}</span>
                        </template>
                        <template #payment="{ row }">
                            <span class="font-bold text-slate-700">{{ row.payment_method }}</span>
                            <span class="block text-slate-400 font-medium text-xs mt-0.5">Ref: {{ row.payment_ref }}</span>
                        </template>
                        <template #status="{ value }">
                            <span
                                class="inline-block text-xs font-bold px-3 py-1 rounded-full capitalize tracking-wide shadow-sm"
                                :class="statusConfig[value]?.classes ?? 'bg-slate-100 text-slate-600'"
                            >
                                {{ statusConfig[value]?.label ?? value }}
                            </span>
                        </template>
                        <template #actions="{ row }">
                            <button
                                @click="row.status !== 'completed' && row.status !== 'cancelled' ? openCancelModal(row) : null"
                                :disabled="row.status === 'completed' || row.status === 'cancelled' || (isCancelling && bookingToCancel?.ref === row.ref)"
                                :class="[
                                    'font-bold px-4 py-2 rounded-md transition-colors text-xs tracking-wide shadow-sm flex items-center justify-center gap-1.5 w-full sm:w-auto min-w-[90px]',
                                    row.status === 'completed' || row.status === 'cancelled'
                                        ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed shadow-none'
                                        : 'bg-red-500 hover:bg-red-600 text-white shadow-red-500/20'
                                ]"
                            >
                                <template v-if="row.status === 'completed'">
                                    <font-awesome-icon icon="fa-solid fa-lock" />
                                    Completed
                                </template>
                                <template v-else-if="row.status === 'cancelled'">
                                    <font-awesome-icon icon="fa-solid fa-ban" />
                                    Cancelled
                                </template>
                                <template v-else>
                                    <font-awesome-icon icon="fa-solid fa-xmark" />
                                    Cancel
                                </template>
                            </button>
                        </template>
                    </Table>
                </div>
            </div>

            <!-- BOOKING FORM VIEW -->
            <div v-if="showForm" class="max-w-4xl mx-auto">
                <div v-if="currentStep < 6" class="mb-5 flex items-center justify-between">
                    <button
                        @click="showForm = false; currentStep = 1;"
                        class="flex items-center gap-2 text-sm text-sky-700 hover:text-orange-500 font-bold tracking-wide transition-colors"
                    >
                        <font-awesome-icon icon="fa-solid fa-angles-left" />
                        Back to Bookings
                    </button>
                    <button
                        @click="openScheduleModal"
                        class="flex items-center gap-1.5 text-xs font-bold text-sky-900 bg-sky-100 hover:bg-sky-200 px-3.5 py-1.5 rounded-lg border border-sky-200 transition-colors"
                    >
                        <font-awesome-icon icon="fa-solid fa-calendar-days" />
                        Check Deck Schedules
                    </button>
                </div>

                <!-- Step Indicator -->
                <div class="bg-white py-6 px-4 rounded-xl border border-sky-100 shadow-sm mb-6" v-if="currentStep < 6">
                    <div class="max-w-3xl mx-auto">
                        <div class="flex items-center justify-between px-2" v-if="currentStep < 6">
                            <template v-for="(step, i) in steps.slice(0, 5)" :key="step.id">
                                <button
                                    @click="step.id < currentStep && goToStep(step.id)"
                                    :class="['flex flex-col items-center gap-2 focus:outline-none transition-all duration-200', step.id < currentStep ? 'cursor-pointer hover:scale-105' : 'cursor-default']"
                                >
                                    <div :class="['w-10 h-10 rounded-full flex items-center justify-center text-sm font-bold border-2 transition-all duration-300', step.id === currentStep ? 'bg-orange-500 border-orange-500 text-white shadow-md shadow-orange-500/30 scale-110' : step.id < currentStep ? 'bg-lime-500 border-lime-500 text-sky-950 shadow-sm' : 'bg-white border-slate-200 text-slate-400']">
                                        <span v-if="step.id < currentStep">✓</span>
                                        <span v-else>{{ step.id }}</span>
                                    </div>
                                    <span :class="['text-xs font-bold tracking-wide hidden sm:block transition-colors', step.id === currentStep ? 'text-orange-600' : step.id < currentStep ? 'text-sky-900' : 'text-slate-400']">{{ step.label }}</span>
                                </button>
                                <div v-if="i < 4" :class="['flex-1 h-1 mx-2 rounded-full transition-all duration-300', step.id < currentStep ? 'bg-lime-400' : 'bg-slate-100']" />
                            </template>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md border border-sky-100 overflow-hidden">
                    <div v-if="Object.keys(errors).length" class="mx-8 mt-6 bg-red-50 border border-red-200 rounded-lg px-5 py-4">
                        <p class="text-sm font-bold text-red-700 mb-2">Please fix the following errors:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li v-for="(error, field) in errors" :key="field" class="text-xs font-medium text-red-600">{{ error }}</li>
                        </ul>
                    </div>

                    <!-- ── STEP 1: EVENT DATE & TIME ──────────────────────── -->
                    <div v-if="currentStep === 1" class="p-6 md:p-10">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <h2 class="font-display text-2xl font-bold text-sky-900">Event Date & Time</h2>
                            <button
                                @click="openScheduleModal"
                                type="button"
                                class="text-xs font-bold text-sky-700 hover:text-orange-600 bg-sky-50 border border-sky-200 px-3 py-1.5 rounded-lg inline-flex items-center gap-1.5 transition-colors"
                            >
                                <font-awesome-icon icon="fa-solid fa-calendar-week" />
                                View Reserved Dates
                            </button>
                        </div>
                        <p class="text-sm font-medium text-slate-500 mb-8">Select the date, time slot, and exact start time for your event at Butal Ship Hauz.</p>

                        <div class="mb-8">
                            <label class="block text-sm font-bold text-sky-900 mb-3">
                                Event Date
                                <span v-if="reservedDates.length" class="text-xs font-normal text-amber-700 ml-2">
                                    (Dates with dots • indicate existing deck reservations)
                                </span>
                            </label>
                            <div class="max-w-md">
                                <CalendarPicker v-model="eventDate" :min="today" :reservedDates="reservedDates" />
                            </div>

                            <!-- Live Date Notice if existing bookings on selected date -->
                            <div v-if="eventDate && selectedDateReservations.length > 0" class="mt-4 max-w-md bg-amber-50/90 border border-amber-300 rounded-xl p-4 shadow-sm">
                                <div class="flex items-center gap-2 text-amber-900 font-bold text-xs mb-2">
                                    <font-awesome-icon icon="fa-solid fa-circle-info" class="text-amber-600" />
                                    Existing Reservations on {{ fmtDate(eventDate) }}:
                                </div>
                                <div class="space-y-2">
                                    <div
                                        v-for="res in selectedDateReservations"
                                        :key="res.id"
                                        class="text-xs bg-white p-2.5 rounded-lg border border-amber-200 flex flex-col gap-1 text-slate-700"
                                    >
                                        <div class="flex items-center justify-between">
                                            <span class="font-bold text-sky-950">🚢 {{ res.venue_title }}</span>
                                            <span
                                                :class="[
                                                    'text-[10px] font-bold px-2 py-0.5 rounded uppercase',
                                                    res.booking_mode === 'exclusive'
                                                        ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                                        : 'bg-sky-100 text-sky-800'
                                                ]"
                                            >
                                                {{ res.booking_mode === 'exclusive' ? 'Exclusive Booked' : 'Visitor Group' }}
                                            </span>
                                        </div>
                                        <span class="text-slate-500">
                                            {{ res.time_slot }} &bull; {{ res.event_type }} (Booked by {{ res.booker_name }})
                                        </span>
                                    </div>
                                </div>
                                <p class="mt-2.5 text-[11px] text-amber-800 leading-relaxed font-medium">
                                    💡 <strong>Notice:</strong> Exclusive private booking is locked on occupied decks for that time slot. However, you can still reserve those decks as a <strong>Visitor Pass</strong>, or choose a free deck/slot!
                                </p>
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-sm font-bold text-sky-900 mb-3">Time Slot</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                <button
                                    v-for="slot in timeSlots" :key="slot.id" @click="timeSlotId = slot.id"
                                    :class="['text-left border-2 rounded-xl p-5 transition-all duration-200 outline-none', timeSlotId === slot.id ? 'border-orange-500 bg-orange-50 shadow-sm shadow-orange-500/10' : 'border-slate-200 hover:border-orange-300 hover:bg-slate-50']"
                                >
                                    <p class="font-bold text-sky-900 text-base mb-1">{{ slot.label }}</p>
                                    <p class="text-xs font-medium text-slate-500">{{ slot.time }}</p>
                                </button>
                            </div>
                        </div>

                        <div class="mb-8">
                            <label class="block text-sm font-bold text-sky-900 mb-2">Exact Start / Arrival Time <span class="text-orange-500">*</span></label>
                            <p class="text-xs text-slate-500 mb-2">Please indicate the exact time you or your guests will arrive or start.</p>
                            <input type="time" v-model="exactTime" class="w-full max-w-xs border border-slate-300 rounded-md px-4 py-2.5 text-sm font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 shadow-sm" required />
                        </div>

                        <div class="mb-8">
                            <label class="block text-sm font-bold text-sky-900 mb-3">Event Type</label>
                            <select v-model="eventType" class="w-full max-w-md border border-slate-300 rounded-md px-4 py-3 text-sm font-medium text-slate-700 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 bg-white shadow-sm">
                                <option value="" disabled>Select event type…</option>
                                <option v-for="type in activeEventTypes" :key="type.id" :value="type.id">{{ type.type }}</option>
                            </select>
                        </div>

                        <div v-if="eventDate && timeSlotId && exactTime && eventType" class="mt-8 bg-sky-50 border border-sky-200 rounded-lg px-6 py-5 flex items-center gap-4 shadow-inner">
                            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shadow-sm border border-sky-100 flex-shrink-0">
                                <span class="text-xl">⚓</span>
                            </div>
                            <div>
                                <p class="text-base font-bold text-sky-900 mb-1">{{ selectedEventTypeLabel }}</p>
                                <p class="text-sm font-medium text-sky-700">{{ fmtDate(eventDate) }} &bull; {{ selectedTimeSlotData?.label }} ({{ formatExactTime(exactTime) }})</p>
                            </div>
                        </div>
                    </div>

                    <!-- ── STEP 2: PACKAGES ────────────────────────────────── -->
                    <div v-if="currentStep === 2" class="p-6 md:p-10">
                        <div class="flex items-center justify-between gap-3 mb-2">
                            <h2 class="font-display text-2xl font-bold text-sky-900">Venue Package & Mode</h2>
                            <button
                                @click="openScheduleModal"
                                type="button"
                                class="text-xs font-bold text-sky-700 hover:text-orange-600 bg-sky-50 border border-sky-200 px-3 py-1.5 rounded-lg inline-flex items-center gap-1.5 transition-colors"
                            >
                                <font-awesome-icon icon="fa-solid fa-calendar-week" />
                                Check Deck Manifest
                            </button>
                        </div>
                        <p class="text-sm font-medium text-slate-500 mb-6">
                            Choose a venue deck for {{ selectedEventTypeLabel }} on {{ fmtDate(eventDate) }} ({{ selectedTimeSlotData?.label }}).
                        </p>

                        <!-- Banner if the currently selected package has an exclusive booking conflict -->
                        <div v-if="currentVenueConflict" class="mb-8 bg-amber-50 border-2 border-amber-300 rounded-xl p-5 shadow-sm">
                            <div class="flex items-start gap-3">
                                <div class="w-9 h-9 rounded-full bg-amber-500 text-white flex items-center justify-center flex-shrink-0 mt-0.5 font-bold">
                                    <font-awesome-icon icon="fa-solid fa-lock" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="text-base font-bold text-amber-950 mb-1">
                                        Exclusive Reservation is Locked for this Venue Deck
                                    </h4>
                                    <p class="text-sm font-medium text-amber-900 leading-relaxed mb-3">
                                        <strong>{{ selectedPackageData?.name }}</strong> is already booked for <strong>{{ currentVenueConflict.event_type }}</strong> by <strong>{{ currentVenueConflict.booker_name }}</strong> on {{ fmtDate(eventDate) }} ({{ currentVenueConflict.time_slot }}).
                                        To maintain safety and fairness, duplicate exclusive buyouts are restricted.
                                    </p>
                                    <div class="inline-flex items-center gap-2 bg-white px-3.5 py-2 rounded-lg border border-amber-200 text-xs font-bold text-sky-950">
                                        <font-awesome-icon icon="fa-solid fa-circle-check" class="text-emerald-500" />
                                        <span><strong>Visitor Mode Activated</strong>: ₱{{ selectedPackageData?.price_visitor }} per head. You can proceed with visitor admission!</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Booking Mode Selector (Only when no conflict) -->
                        <div v-if="selectedPackage && !currentVenueConflict" class="mb-8 bg-slate-50 border border-slate-200 rounded-xl p-5">
                            <label class="block text-sm font-bold text-sky-900 mb-3">Select Reservation Mode</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <button
                                    type="button"
                                    @click="bookingMode = 'exclusive'"
                                    :class="[
                                        'text-left border-2 rounded-xl p-4 transition-all',
                                        bookingMode === 'exclusive'
                                            ? 'border-sky-600 bg-white shadow-sm'
                                            : 'border-slate-200 hover:border-sky-300 bg-white'
                                    ]"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-bold text-sky-950 text-sm flex items-center gap-2">
                                            🚢 Exclusive Deck Reservation
                                        </span>
                                        <span v-if="bookingMode === 'exclusive'" class="text-sky-600 font-bold text-xs">Selected ✓</span>
                                    </div>
                                    <p class="text-xs text-slate-500">Full private deck buyout for your private event celebration.</p>
                                </button>

                                <button
                                    type="button"
                                    @click="bookingMode = 'visitor'"
                                    :class="[
                                        'text-left border-2 rounded-xl p-4 transition-all',
                                        bookingMode === 'visitor'
                                            ? 'border-amber-500 bg-white shadow-sm'
                                            : 'border-slate-200 hover:border-amber-300 bg-white'
                                    ]"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="font-bold text-sky-950 text-sm flex items-center gap-2">
                                            👥 Visitor Pass Mode
                                        </span>
                                        <span v-if="bookingMode === 'visitor'" class="text-amber-600 font-bold text-xs">Selected ✓</span>
                                    </div>
                                    <p class="text-xs text-slate-500">Per-head admission (₱{{ selectedPackageData?.price_visitor }}/head) with deck access.</p>
                                </button>
                            </div>
                        </div>

                        <!-- Venue Package Cards -->
                        <div v-if="packages.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-10">
                            <button
                                v-for="pkg in packages"
                                :key="pkg.id"
                                @click="selectPackageCard(pkg)"
                                :class="[
                                    'text-left border-2 rounded-xl p-6 transition-all duration-200 relative',
                                    selectedPackage === pkg.id
                                        ? 'border-orange-500 bg-orange-50 shadow-md shadow-orange-500/10'
                                        : 'border-slate-200 hover:border-orange-300 hover:bg-slate-50'
                                ]"
                            >
                                <div class="absolute top-4 right-4" v-if="selectedPackage === pkg.id">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-500 text-white">
                                        <font-awesome-icon icon="fa-solid fa-check" class="text-xs" />
                                    </span>
                                </div>

                                <!-- Conflict / Status Tag -->
                                <div v-if="getVenueConflict(pkg)" class="mb-3">
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full bg-amber-100 text-amber-900 border border-amber-300">
                                        <font-awesome-icon icon="fa-solid fa-lock" class="text-[10px]" />
                                        Exclusive Booked: {{ getVenueConflict(pkg).booker_name }}
                                    </span>
                                    <span class="ml-2 inline-flex items-center text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Visitors Allowed
                                    </span>
                                </div>
                                <div v-else class="mb-3">
                                    <span class="inline-flex items-center gap-1.5 text-[11px] font-bold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        <font-awesome-icon icon="fa-solid fa-check" class="text-[10px]" />
                                        Available for Exclusive Booking
                                    </span>
                                </div>

                                <p class="font-display font-bold text-sky-900 text-xl mb-2 pr-8">{{ pkg.name }}</p>
                                <p class="text-sm font-medium text-slate-600 mb-4 leading-relaxed">{{ pkg.desc }}</p>
                                
                                <div class="flex items-center gap-2 mb-4">
                                    <font-awesome-icon icon="fa-solid fa-users" class="text-slate-400" />
                                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wide">{{ pkg.capacity }}</p>
                                </div>

                                <div>
                                    <p class="text-orange-600 font-bold text-2xl">{{ getPackagePriceDisplay(pkg) }}</p>
                                    <p v-if="getVenueConflict(pkg)" class="text-xs font-semibold text-amber-800 mt-1">
                                        Exclusive buyout unavailable for this slot. Visitor rate applies.
                                    </p>
                                </div>
                            </button>
                        </div>
                        
                        <div v-else class="text-center py-12 bg-white border border-slate-200 rounded-xl mb-10 shadow-sm">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <span class="text-2xl">📦</span>
                            </div>
                            <p class="text-slate-500 font-medium text-base">No venue packages available for this event type yet.</p>
                            <button @click="prevStep" class="mt-4 text-sm font-bold text-sky-600 hover:text-orange-500 transition-colors">&larr; Go back and choose another event</button>
                        </div>

                        <!-- Optional Add-ons (Only for exclusive mode) -->
                        <div class="border-t-2 border-slate-100 pt-8" v-if="bookingMode === 'exclusive'">
                            <p class="text-sm font-bold text-sky-900 mb-4 flex items-center gap-2">
                                Optional Add-ons
                                <span class="font-medium text-slate-400 text-xs bg-slate-100 px-2 py-0.5 rounded-full">Select multiple</span>
                            </p>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div
                                    v-for="addon in addons" :key="addon.id"
                                    :class="['text-left border-2 rounded-xl p-5 transition-all duration-200 flex flex-col', selectedAddons.includes(addon.id) ? 'border-sky-500 bg-sky-50 shadow-md shadow-sky-500/10' : 'border-slate-200 hover:border-sky-300']"
                                >
                                    <div class="flex items-start gap-4 cursor-pointer" @click="toggleAddon(addon.id)">
                                        <div class="mt-1 flex-shrink-0">
                                            <div class="w-5 h-5 rounded border flex items-center justify-center transition-colors" :class="selectedAddons.includes(addon.id) ? 'bg-sky-500 border-sky-500' : 'border-slate-300 bg-white'">
                                                <font-awesome-icon v-if="selectedAddons.includes(addon.id)" icon="fa-solid fa-check" class="text-white text-[10px]" />
                                            </div>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-bold text-sky-900 mb-1">{{ addon.name }}</p>
                                            <p class="text-xs font-medium text-slate-500 leading-relaxed mb-2">{{ addon.desc }}</p>
                                            <p class="text-sm font-bold text-sky-700">
                                                + {{ fmt(addon.price) }}
                                                <span v-if="addon.pricing_type === 'per_head'" class="text-xs text-slate-500 font-medium ml-1">/ head</span>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div v-if="selectedAddons.includes(addon.id) && addon.pricing_type === 'per_head'" class="ml-9 mt-4 pt-4 border-t border-sky-200/60 flex items-center justify-between">
                                        <span class="text-xs font-bold text-sky-900 uppercase tracking-wide">Number of Pax:</span>
                                        <div class="flex items-center gap-3">
                                            <button type="button" @click.stop="addonQuantities[addon.id] = Math.max(1, addonQuantities[addon.id] - 1)" class="w-7 h-7 rounded-md bg-white border border-sky-200 text-sky-700 hover:bg-orange-500 hover:text-white font-bold flex items-center justify-center shadow-sm">−</button>
                                            <span class="text-sm font-bold w-6 text-center text-sky-900">{{ addonQuantities[addon.id] }}</span>
                                            <button type="button" @click.stop="addonQuantities[addon.id] += 1" class="w-7 h-7 rounded-md bg-white border border-sky-200 text-sky-700 hover:bg-orange-500 hover:text-white font-bold flex items-center justify-center shadow-sm">+</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── STEP 3: CONTACT INFORMATION ────────────────────── -->
                    <div v-if="currentStep === 3" class="p-6 md:p-10">
                        <div class="flex items-center gap-3 mb-2">
                            <h2 class="font-display text-2xl font-bold text-sky-900">Contact Information</h2>
                        </div>
                        <p class="text-sm font-medium text-slate-500 mb-8">Who should we coordinate with for this reservation?</p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-sky-900 mb-2">First Name <span class="text-orange-500">*</span></label>
                                <input v-model="contact.firstName" type="text" placeholder="e.g. Juan" required class="w-full border border-slate-300 rounded-md px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-sky-900 mb-2">Last Name <span class="text-orange-500">*</span></label>
                                <input v-model="contact.lastName" type="text" placeholder="e.g. Dela Cruz" required class="w-full border border-slate-300 rounded-md px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-sky-900 mb-2">Email Address <span class="text-orange-500">*</span></label>
                                <input v-model="contact.email" type="email" placeholder="e.g. juan@example.com" required class="w-full border border-slate-300 rounded-md px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 shadow-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-sky-900 mb-2">Phone Number <span class="text-orange-500">*</span></label>
                                <input v-model="contact.phone" type="tel" placeholder="e.g. 9123456789" pattern="^9d{9}$" maxlength="10" required class="w-full border border-slate-300 rounded-md px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 shadow-sm" />
                            </div>
                            
                            <div class="sm:col-span-2 pt-4 border-t border-slate-100">
                                <label class="block text-sm font-bold text-sky-900 mb-3">
                                    Expected Number of {{ bookingMode === 'visitor' ? 'Visitors' : 'Guests' }}
                                </label>
                                <div class="flex items-center gap-4 bg-slate-50 w-max p-2 rounded-lg border border-slate-200">
                                    <button @click="contact.guestCount = Math.max(1, contact.guestCount - 1)" class="w-10 h-10 rounded-md bg-white border border-slate-300 text-slate-600 hover:bg-slate-100 hover:text-orange-500 font-bold transition-colors shadow-sm">−</button>
                                    <span class="text-sky-900 font-bold text-lg w-12 text-center">{{ contact.guestCount }}</span>
                                    <button @click="contact.guestCount += 1" class="w-10 h-10 rounded-md bg-white border border-slate-300 text-slate-600 hover:bg-slate-100 hover:text-orange-500 font-bold transition-colors shadow-sm">+</button>
                                </div>
                                <p v-if="bookingMode === 'visitor'" class="text-xs text-amber-700 font-medium mt-2">
                                    * Visitor Pass is calculated at ₱{{ selectedPackageData?.price_visitor }} per visitor.
                                </p>
                            </div>
                            
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-bold text-sky-900 mb-2">Special Requests or Notes</label>
                                <textarea v-model="contact.requests" rows="4" placeholder="Specific setup, dietary needs, theme requests, accessibility concerns…" class="w-full border border-slate-300 rounded-md px-4 py-3 text-sm font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 shadow-sm resize-none" />
                            </div>
                        </div>
                    </div>

                    <!-- ── STEP 4: REVIEW ─────────────────────────────────── -->
                    <div v-if="currentStep === 4" class="p-6 md:p-10">
                        <div class="flex items-center gap-3 mb-2">
                            <h2 class="font-display text-2xl font-bold text-sky-900">Review Your Booking</h2>
                        </div>
                        <p class="text-sm font-medium text-slate-500 mb-8">Please confirm all details before proceeding to payment.</p>

                        <div class="bg-gradient-to-br from-sky-900 to-sky-700 rounded-xl px-6 py-5 mb-6 flex items-center gap-4 text-white shadow-md">
                            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center">
                                <span class="text-2xl">🚢</span>
                            </div>
                            <div>
                                <p class="font-display font-bold text-xl tracking-wide">Butal Ship Hauz</p>
                                <p class="text-sky-200 text-sm font-medium mt-0.5">Talibon, Bohol</p>
                            </div>
                        </div>

                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <p class="text-xs font-bold text-orange-500 uppercase tracking-widest">
                                    Event Details
                                </p>
                                <span
                                    :class="[
                                        'text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wider',
                                        bookingMode === 'visitor'
                                            ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                            : 'bg-sky-100 text-sky-900 border border-sky-300'
                                    ]"
                                >
                                    {{ bookingMode === 'visitor' ? '👥 Visitor Pass' : '🔒 Exclusive Reservation' }}
                                </span>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-4 gap-x-8 text-sm">
                                <div class="flex flex-col gap-1">
                                    <span class="text-slate-500 font-medium text-xs uppercase">Event Type</span>
                                    <span class="font-bold text-sky-900">{{ selectedEventTypeLabel }}</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-slate-500 font-medium text-xs uppercase">Date & Time</span>
                                    <span class="font-bold text-sky-900">
                                        {{ fmtDate(eventDate) }}<br/>
                                        <span class="text-slate-600 font-medium text-xs">{{ selectedTimeSlotData?.label }} ({{ formatExactTime(exactTime) }})</span>
                                    </span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-slate-500 font-medium text-xs uppercase">Number of {{ bookingMode === 'visitor' ? 'Visitors' : 'Guests' }}</span>
                                    <span class="font-bold text-sky-900">{{ contact.guestCount }} persons</span>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <span class="text-slate-500 font-medium text-xs uppercase">Contact Person</span>
                                    <span class="font-bold text-sky-900">{{ contact.firstName }} {{ contact.lastName }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 mb-6">
                            <p class="text-xs font-bold text-orange-500 uppercase tracking-widest mb-4">Package & Add-ons</p>
                            <div class="flex justify-between items-start text-sm mb-4">
                                <div>
                                    <p class="font-bold text-sky-900 text-base mb-1">{{ selectedPackageData?.name }}</p>
                                    <p class="text-slate-500 font-medium text-xs">
                                        <template v-if="bookingMode === 'visitor'">
                                            ₱{{ selectedPackageData?.price_visitor }} x {{ contact.guestCount }} visitors
                                        </template>
                                        <template v-else>
                                            {{ selectedPackageData?.capacity }} &bull; {{ selectedTimeSlotData?.label }} Rate
                                        </template>
                                    </p>
                                </div>
                                <span class="font-bold text-sky-900 text-base">{{ fmt(packageTotal) }}</span>
                            </div>
                            
                            <template v-if="selectedAddons.length && bookingMode !== 'visitor'">
                                <div class="border-t border-slate-200 pt-4 mt-2 space-y-3">
                                    <div v-for="id in selectedAddons" :key="id" class="flex justify-between text-sm">
                                        <span class="text-slate-600 font-medium">
                                            {{ addons.find(a => a.id === id)?.name }}
                                            <span v-if="addons.find(a => a.id === id)?.pricing_type === 'per_head'" class="text-xs text-slate-400 ml-1">
                                                ({{ fmt(addons.find(a => a.id === id)?.price) }} x {{ addonQuantities[id] }} pax)
                                            </span>
                                        </span>
                                        <span class="font-bold text-slate-700">
                                            + {{ fmt(addons.find(a => a.id === id)?.price * (addonQuantities[id] || 1)) }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="bg-sky-50 border border-sky-200 rounded-xl p-6 flex justify-between items-center shadow-inner">
                            <span class="font-bold text-sky-900 uppercase tracking-wider text-sm">Grand Total</span>
                            <span class="text-3xl font-display font-bold text-orange-600">{{ fmt(grandTotal) }}</span>
                        </div>
                    </div>

                    <!-- ── STEP 5: PAYMENT ─────────────────────────────────── -->
                    <div v-if="currentStep === 5" class="p-6 md:p-10">
                        <div class="flex items-center gap-3 mb-2">
                            <h2 class="font-display text-2xl font-bold text-sky-900">Payment & Reservation Deposit</h2>
                        </div>
                        <p class="text-sm font-medium text-slate-500 mb-6">
                            To officially confirm and lock your date on our calendar, a <strong>50% reservation downpayment</strong> is required.
                        </p>

                        <!-- Deposit vs Total Banner -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 rounded-2xl bg-gradient-to-r from-sky-50 to-orange-50 border border-sky-200/80 mb-6">
                            <div>
                                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-500 block">Total Package Amount</span>
                                <span class="font-display font-black text-xl text-sky-950">{{ fmt(grandTotal) }}</span>
                            </div>
                            <div class="sm:border-l sm:border-slate-200 sm:pl-4">
                                <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-orange-600 flex items-center gap-1">
                                    <font-awesome-icon icon="fa-solid fa-lock" class="text-xs" />
                                    50% Downpayment Due Now
                                </span>
                                <span class="font-display font-black text-2xl text-orange-600">{{ fmt(downpaymentAmount) }}</span>
                            </div>
                        </div>

                        <!-- Payment Method Selector -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                            <button
                                v-for="m in activePaymentOptions" :key="m.id"
                                @click="payment.method = m.id; payment.accountNumber = ''; payment.transactionNumber = '';"
                                :class="[
                                    'border-2 rounded-2xl py-3.5 px-4 text-center font-bold tracking-wide transition-all duration-200 flex items-center justify-center gap-2',
                                    payment.method === m.id
                                        ? 'border-orange-500 bg-orange-50 text-orange-950 shadow-sm'
                                        : 'border-slate-200 text-slate-500 hover:border-orange-300 hover:bg-slate-50'
                                ]"
                            >
                                <font-awesome-icon :icon="m.label.toLowerCase().includes('gcash') ? 'fa-solid fa-qrcode' : 'fa-solid fa-credit-card'" class="text-orange-500" />
                                <span>{{ m.label }}</span>
                            </button>
                        </div>

                        <div v-if="selectedPaymentOption?.isOnline" class="space-y-6">
                            <!-- GCash Scan-to-Pay QR Card -->
                            <div class="overflow-hidden rounded-2xl border-2 border-blue-500/80 bg-white shadow-lg">
                                <!-- GCash Top Brand Banner -->
                                <div class="bg-gradient-to-r from-[#005CE6] to-[#0042A6] px-5 py-3 text-white flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <div class="w-7 h-7 bg-white rounded-full flex items-center justify-center font-black text-[#005CE6] text-sm">
                                            G
                                        </div>
                                        <div>
                                            <p class="font-display font-black text-sm tracking-wide leading-none">GCash Scan to Pay</p>
                                            <p class="text-[10px] text-blue-100 font-mono mt-0.5">Instant Merchant QR Payment</p>
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-0.5 rounded-full bg-white/20 text-white font-mono text-[10px] font-bold uppercase tracking-wider">
                                        Official QR
                                    </span>
                                </div>

                                <div class="p-6 flex flex-col md:flex-row items-center gap-6">
                                    <!-- QR Code Graphic Container -->
                                    <div class="shrink-0 flex flex-col items-center">
                                        <div class="relative p-3 bg-white border-2 border-dashed border-blue-400 rounded-2xl shadow-inner group">
                                            <div class="w-44 h-44 bg-white rounded-xl flex items-center justify-center p-1 relative overflow-hidden">
                                                <img
                                                    src="/images/gcash-qr.png"
                                                    alt="GCash QR Code"
                                                    class="w-full h-full object-contain rounded-lg"
                                                    @error="$event.target.onerror = null; $event.target.src = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=09207139299&margin=10'"
                                                />
                                            </div>
                                        </div>
                                        <p class="text-[10px] font-bold text-slate-400 mt-2 flex items-center gap-1">
                                            <font-awesome-icon icon="fa-solid fa-camera" />
                                            <span>Scan via GCash App</span>
                                        </p>
                                    </div>

                                    <!-- Account & Copy Details -->
                                    <div class="flex-1 space-y-3 w-full text-center md:text-left">
                                        <div>
                                            <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 block">Account Name</span>
                                            <p class="font-display font-black text-lg text-sky-950">
                                                {{ selectedPaymentOption.account || 'Dalve S. / Butal Ship Hauz' }}
                                            </p>
                                        </div>

                                        <div>
                                            <span class="text-[10px] font-mono font-bold uppercase tracking-wider text-slate-400 block">GCash Mobile Number</span>
                                            <div class="flex items-center justify-center md:justify-start gap-2 mt-1">
                                                <span class="font-mono text-xl sm:text-2xl font-black text-blue-600 tracking-wider">
                                                    {{ selectedPaymentOption.number }}
                                                </span>
                                                <button
                                                    type="button"
                                                    @click="copyAccountNumber(selectedPaymentOption.number)"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all shadow-xs"
                                                    :class="copiedNumber ? 'bg-emerald-500 text-white' : 'bg-slate-100 hover:bg-blue-50 text-blue-700 border border-slate-200'"
                                                >
                                                    <font-awesome-icon :icon="copiedNumber ? 'fa-solid fa-check' : 'fa-solid fa-copy'" />
                                                    <span>{{ copiedNumber ? 'Copied!' : 'Copy' }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="pt-2 border-t border-slate-100">
                                            <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                                💡 <strong>How to pay:</strong> Open GCash &rarr; Tap <strong>QR / Scan</strong> &rarr; Scan the QR code above (or send to mobile number) &rarr; Enter <strong>{{ fmt(downpaymentAmount) }}</strong> downpayment &rarr; Paste your 13-digit Reference No. below.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Customer Input: Mobile & Transaction Ref -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2">
                                <div>
                                    <label class="block text-sm font-bold text-sky-900 mb-2">
                                        Your {{ selectedPaymentOption.label }} Mobile Number <span class="text-orange-500">*</span>
                                    </label>
                                    <input
                                        v-model="payment.accountNumber"
                                        type="tel"
                                        placeholder="e.g. 09123456789"
                                        maxlength="11"
                                        required
                                        class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 shadow-sm"
                                    />
                                    <p class="text-[11px] text-slate-400 mt-1">The sender number used on GCash</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-sky-900 mb-2">
                                        GCash Reference Number (13 digits) <span class="text-orange-500">*</span>
                                    </label>
                                    <input
                                        v-model="payment.transactionNumber"
                                        type="text"
                                        placeholder="e.g. 1029384756382"
                                        maxlength="30"
                                        required
                                        class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 shadow-sm font-mono"
                                    />
                                    <p class="text-[11px] text-slate-400 mt-1">Found in your GCash SMS or in-app receipt</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ── STEP 6: CONFIRMED ───────────────────────────────── -->
                    <div v-if="currentStep === 6" class="p-10 text-center">
                        <div class="w-20 h-20 bg-lime-100 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border border-lime-200">
                            <font-awesome-icon icon="fa-solid fa-check" class="text-4xl text-lime-500" />
                        </div>
                        <h2 class="font-display text-3xl font-bold text-sky-900 mb-3">Booking Confirmed!</h2>
                        <p class="text-slate-500 font-medium mb-8">
                            A confirmation has been sent to <strong class="text-sky-800">{{ contact.email }}</strong>
                        </p>

                        <div class="bg-sky-50 border border-sky-200 rounded-2xl p-8 inline-block mb-10 shadow-sm">
                            <p class="text-xs font-bold text-sky-700 uppercase tracking-widest mb-2">Booking Reference</p>
                            <p class="text-4xl font-bold font-mono text-orange-500 tracking-widest drop-shadow-sm">{{ reservationCode }}</p>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center justify-center gap-4">
                            <button @click="resetForm" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-bold px-8 py-3 rounded-md shadow-md shadow-orange-500/20 transition-colors w-full sm:w-auto">Book Another Event</button>
                            <button @click="resetForm" class="bg-white border border-slate-300 hover:bg-slate-50 text-slate-700 text-sm font-bold px-8 py-3 rounded-md shadow-sm transition-colors w-full sm:w-auto">Back to Bookings</button>
                        </div>
                    </div>

                    <!-- ── NAVIGATION ─────────────────────────────────────── -->
                    <div v-if="currentStep < 6" class="px-6 md:px-10 py-6 border-t border-slate-100 bg-slate-50 flex justify-between items-center">
                        <button v-if="currentStep > 1" @click="prevStep" class="flex items-center gap-2 text-sm font-bold text-slate-500 hover:text-sky-900 transition-colors bg-white border border-slate-300 px-5 py-2.5 rounded-md shadow-sm">
                            <font-awesome-icon icon="fa-solid fa-arrow-left" /> Back
                        </button>
                        <div v-else />

                        <button
                            v-if="currentStep === 5"
                            @click="confirmReservation"
                            :disabled="!step5Valid || form.processing"
                            :class="['px-8 py-3 rounded-md font-bold text-sm transition-all shadow-md flex items-center gap-2', step5Valid && !form.processing ? 'bg-lime-500 hover:bg-lime-600 text-sky-950 shadow-lime-500/30' : 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none']"
                        >
                            <font-awesome-icon v-if="form.processing" icon="fa-solid fa-spinner" spin />
                            <span v-else>Confirm Booking</span>
                            <font-awesome-icon v-if="!form.processing" icon="fa-solid fa-check" />
                        </button>

                        <button
                            v-else
                            @click="currentStep === 1 ? checkAvailability() : currentStep === 4 ? openTermsModal() : nextStep()"
                            :disabled="(currentStep === 1 && (!step1Valid || isCheckingAvailability)) || (currentStep === 2 && !step2Valid) || (currentStep === 3 && !step3Valid)"
                            :class="['px-8 py-3 rounded-md font-bold text-sm transition-all shadow-md flex items-center gap-2', (currentStep === 1 && step1Valid && !isCheckingAvailability) || (currentStep === 2 && step2Valid) || (currentStep === 3 && step3Valid) || currentStep === 4 ? 'bg-orange-500 hover:bg-orange-600 text-white shadow-orange-500/20' : 'bg-slate-200 text-slate-400 cursor-not-allowed shadow-none']"
                        >
                            <font-awesome-icon v-if="isCheckingAvailability" icon="fa-solid fa-spinner" spin />
                            <span>{{ currentStep === 4 ? "Proceed to Payment" : isCheckingAvailability ? "Checking..." : "Continue" }}</span>
                            <font-awesome-icon v-if="!isCheckingAvailability" icon="fa-solid fa-arrow-right" />
                        </button>
                    </div>
                </div>

                <!-- Step counter -->
                <p class="text-center text-xs font-bold uppercase tracking-widest text-slate-400 mt-6" v-if="currentStep < 6">
                    Step {{ currentStep }} of 5
                </p>
            </div>
        </div>

        <!-- ── DECK SCHEDULE & RESERVATIONS MODAL ───────────────────────── -->
        <Modal :show="showScheduleModal" max-width="3xl" @close="closeScheduleModal">
            <div class="p-6 border-b border-slate-100 bg-sky-950 text-white flex items-center justify-between">
                <div>
                    <h3 class="font-display text-xl font-bold flex items-center gap-2">
                        <font-awesome-icon icon="fa-solid fa-calendar-days" class="text-orange-400" />
                        Butal Ship Hauz — Deck Booking Schedule & Manifest
                    </h3>
                    <p class="text-xs text-sky-200 mt-1">
                        Upcoming reserved dates and deck shifts. Exclusive slots restrict duplicate bookings, but visitors are always welcome!
                    </p>
                </div>
                <button
                    @click="closeScheduleModal"
                    class="text-sky-300 hover:text-white p-2 rounded-lg transition-colors"
                >
                    <font-awesome-icon icon="fa-solid fa-xmark" class="text-lg" />
                </button>
            </div>

            <!-- Search & Filters -->
            <div class="p-6 bg-slate-50 border-b border-slate-200 flex flex-wrap items-center gap-3">
                <div class="flex-1 min-w-[200px] relative">
                    <input
                        v-model="scheduleSearch"
                        type="text"
                        placeholder="Search by deck, event, or booker…"
                        class="w-full text-xs font-medium border border-slate-300 rounded-lg pl-9 pr-3 py-2 bg-white text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500 focus:border-orange-500 shadow-sm"
                    />
                    <font-awesome-icon
                        icon="fa-solid fa-magnifying-glass"
                        class="text-slate-400 absolute left-3 top-2.5 text-xs"
                    />
                </div>

                <div class="w-auto">
                    <select
                        v-model="scheduleDeckFilter"
                        class="text-xs font-medium border border-slate-300 rounded-lg px-3 py-2 bg-white text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500 shadow-sm"
                    >
                        <option value="all">All Venue Decks</option>
                        <option v-for="d in deckFilterOptions" :key="d" :value="d">{{ d }}</option>
                    </select>
                </div>

                <div class="w-auto">
                    <select
                        v-model="scheduleSlotFilter"
                        class="text-xs font-medium border border-slate-300 rounded-lg px-3 py-2 bg-white text-slate-800 focus:outline-none focus:ring-1 focus:ring-orange-500 shadow-sm"
                    >
                        <option value="all">All Shifts</option>
                        <option value="morning">Morning (8AM–12PM)</option>
                        <option value="afternoon">Afternoon (1PM–5PM)</option>
                        <option value="night">Night (6PM–10PM)</option>
                        <option value="fullday">Full Day</option>
                    </select>
                </div>
            </div>

            <!-- Schedule List -->
            <div class="p-6 max-h-[60vh] overflow-y-auto space-y-3">
                <div
                    v-if="filteredScheduleList.length === 0"
                    class="text-center py-12 text-slate-500"
                >
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                        <font-awesome-icon icon="fa-solid fa-calendar-check" class="text-xl" />
                    </div>
                    <p class="font-bold text-slate-700 text-sm">No bookings match your filter criteria.</p>
                    <p class="text-xs text-slate-400 mt-1">All venue decks are wide open for reservation on these dates!</p>
                </div>

                <div
                    v-for="item in filteredScheduleList"
                    :key="item.id"
                    class="bg-white border border-slate-200 hover:border-sky-300 rounded-xl p-4 shadow-sm transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <div class="flex items-start gap-3.5">
                        <div class="w-12 h-12 rounded-xl bg-sky-50 border border-sky-200 flex flex-col items-center justify-center text-sky-900 flex-shrink-0">
                            <font-awesome-icon icon="fa-solid fa-ship" class="text-sm mb-0.5 text-sky-700" />
                            <span class="text-[9px] font-bold uppercase tracking-wider">Deck</span>
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h4 class="font-bold text-sky-950 text-sm sm:text-base">{{ item.venue_title }}</h4>
                                <span
                                    :class="[
                                        'text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider',
                                        item.booking_mode === 'visitor'
                                            ? 'bg-amber-100 text-amber-900 border border-amber-300'
                                            : 'bg-indigo-100 text-indigo-900 border border-indigo-300'
                                    ]"
                                >
                                    {{ item.booking_mode === 'visitor' ? '👥 Visitor Group' : '🔒 Exclusive Event' }}
                                </span>
                            </div>
                            <p class="text-xs font-semibold text-orange-600 mb-1">
                                {{ item.event_type }} &bull; Reserved by {{ item.booker_name }}
                            </p>
                            <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 font-medium">
                                <span>📅 {{ fmtDate(item.date) }}</span>
                                <span>🕒 {{ item.time_slot }}</span>
                                <span v-if="item.exact_time" class="text-slate-700 font-bold">
                                    (Arrival: {{ formatExactTime(item.exact_time) }})
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="sm:text-right border-t sm:border-t-0 pt-2 sm:pt-0 border-slate-100 flex flex-col sm:items-end justify-center">
                        <span
                            :class="[
                                'inline-block text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider',
                                item.booking_mode === 'exclusive'
                                    ? 'bg-amber-50 text-amber-900 border border-amber-200'
                                    : 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                            ]"
                        >
                            {{ item.booking_mode === 'exclusive' ? 'Exclusive Locked • Visitors Welcome' : 'Open for Bookings' }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                <button
                    @click="closeScheduleModal"
                    class="px-5 py-2 bg-sky-900 hover:bg-sky-800 text-white font-bold rounded-lg text-xs transition-colors shadow-sm"
                >
                    Close Manifest
                </button>
            </div>
        </Modal>

        <!-- TERMS & CONDITIONS MODAL -->
        <Modal :show="showTermsModal" max-width="md" @close="closeTermsModal">
            <div class="px-6 pt-6 pb-6 border-b border-slate-100">
                <h3 class="font-display text-2xl font-bold text-sky-900 mb-2">Terms & Conditions</h3>
                <p class="text-sm font-medium text-slate-500">Please read before proceeding to payment.</p>
            </div>
            <div class="p-6 text-sm font-medium text-slate-600 space-y-4 max-h-72 overflow-y-auto">
                <p>By proceeding to payment, you agree to the following booking terms for Butal Ship Hauz:</p>
                <ul class="list-disc list-inside space-y-3 bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <li>Bookings are confirmed only upon receipt and verification of payment.</li>
                    <li>If you cancel your booking, you will only receive <strong class="text-red-600">50% of your total payment</strong> as a refund. The remaining 50% is retained as a cancellation fee.</li>
                    <li>Rescheduling requests are subject to venue availability and must be made at least 3 days before the event date.</li>
                    <li>Exclusive reservations guarantee sole event usage of the booked deck; Visitor pass holders enjoy general deck access.</li>
                    <li>No-shows on the event date are not eligible for any refund.</li>
                </ul>
            </div>
            <div class="px-6 py-4 bg-slate-50 flex items-center justify-end gap-3 rounded-b-lg border-t border-slate-100">
                <button @click="closeTermsModal" class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold rounded-md transition-colors shadow-sm text-sm">Cancel</button>
                <button @click="acceptTerms" class="px-6 py-2.5 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-md shadow-md shadow-orange-500/30 transition-colors flex items-center gap-2 text-sm">
                    I Agree, Continue <font-awesome-icon icon="fa-solid fa-arrow-right" />
                </button>
            </div>
        </Modal>

        <!-- CANCEL BOOKING CONFIRMATION MODAL -->
        <Modal :show="showCancelModal" max-width="md" @close="closeCancelModal">
            <div class="px-6 pt-6 pb-6 border-b border-slate-100">
                <h3 class="font-display text-2xl font-bold text-sky-900 flex items-center gap-2">
                    <font-awesome-icon icon="fa-solid fa-triangle-exclamation" class="text-red-500" /> Cancel Booking
                </h3>
            </div>
            <div class="p-6">
                <p class="text-base font-medium text-slate-700 mb-3">
                    Are you sure you want to cancel booking <strong class="font-mono text-sky-800 bg-sky-50 px-2 py-0.5 rounded border border-sky-100">{{ bookingToCancel?.ref }}</strong>?
                </p>
                <div class="bg-red-50 border border-red-200 rounded-lg p-4 mt-4">
                    <p class="text-sm font-semibold text-red-800">
                        Per our terms, only <strong>50% of the payment</strong> will be refunded upon cancellation. This action cannot be undone.
                    </p>
                </div>
            </div>
            <div class="px-6 py-4 bg-slate-50 flex items-center justify-end gap-3 rounded-b-lg border-t border-slate-100">
                <button @click="closeCancelModal" :disabled="isCancelling" class="px-5 py-2.5 bg-white border border-slate-300 hover:bg-slate-100 text-slate-700 font-bold rounded-md transition-colors shadow-sm text-sm disabled:opacity-50">Keep Booking</button>
                <button @click="confirmCancel" :disabled="isCancelling" class="px-6 py-2.5 bg-red-500 hover:bg-red-600 text-white font-bold rounded-md shadow-md shadow-red-500/30 transition-colors flex items-center gap-2 text-sm disabled:opacity-50">
                    <font-awesome-icon v-if="isCancelling" icon="fa-solid fa-spinner" spin />
                    <span v-else>Yes, Cancel Booking</span>
                </button>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
