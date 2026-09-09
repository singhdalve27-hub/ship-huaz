<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import Table from "@/Components/Table.vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { useFormatter } from "@/Composables/useFormatter";

const props = defineProps({
    bookings: Array,
});

const user = usePage().props.auth.user;

const user_information = useForm({
    first_name: user.user_info?.first_name,
    middle_name: user.user_info?.middle_name,
    last_name: user.user_info?.last_name,
    birth_date: user.user_info?.birth_date,
    phone: user.user_info?.phone,
    address: user.user_info?.address,
});

const user_credentials = useForm({
    email: user.email,
    password: "",
    password_confirmation: "",
});

const submitInformation = () => {
    user_information.put(route("update-information"));
};

const submitCredentials = () => {
    user_credentials.put(route("update-credentials"), {
        onSuccess: () =>
            user_credentials.reset("password", "password_confirmation"),
    });
};

// --- Booking History ---

const TableColumns = [
    { key: "ref", label: "Booking Ref", slot: "ref" },
    { key: "event", label: "Event" },
    { key: "date", label: "Date", slot: "date" },
    { key: "time", label: "Time" },
    { key: "package", label: "Deck Package", slot: "package" },
    { key: "amount", label: "Amount", slot: "amount" },
    { key: "payment_method", label: "Payment Channel", slot: "payment" },
    { key: "status", label: "Status", slot: "status" },
];

const tableData = computed(() =>
    (props.bookings ?? []).map((b) => ({
        ref: b.booking_ref,
        event: b.event_type?.type ?? "—",
        date: b.date,
        time: b.time_slot,
        package: b.venue_package?.title ?? "—",
        addons:
            Array.isArray(b.package_add_ons) && b.package_add_ons.length
                ? b.package_add_ons.map((a) => a.title ?? a).join(", ")
                : "",
        amount: b.total_payment,
        payment_method: b.payment_option?.payment ?? "Pay at Venue",
        payment_ref: b.payment_transaction_ref ?? "—",
        status: b.status,
    })),
);

const statusConfig = {
    pending: {
        label: "Pending Review",
        classes: "bg-amber-50 text-amber-800 border border-amber-200",
    },
    confirmed: {
        label: "Confirmed",
        classes: "bg-sky-50 text-sky-800 border border-sky-200",
    },
    cancelled: {
        label: "Cancelled",
        classes: "bg-rose-50 text-rose-800 border border-rose-200",
    },
    completed: {
        label: "Completed",
        classes: "bg-emerald-50 text-emerald-800 border border-emerald-200",
    },
};

const { formatDate, formatAmount } = useFormatter();
</script>

<template>
    <Head title="Passenger Profile & Stateroom | Butal Ship Hauz" />

    <AuthenticatedLayout>
        <div class="space-y-8">
            <!-- ── PAGE HEADER ── -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-sky-50 border border-sky-100 text-[10px] font-mono font-bold tracking-widest uppercase text-sky-800 mb-1.5">
                        <font-awesome-icon icon="fa-solid fa-id-card-clip" class="text-orange-500" />
                        <span>Passenger Credentials • Stateroom Pass</span>
                    </div>
                    <h1 class="font-display text-2xl sm:text-3xl font-black text-sky-950">
                        Passenger Profile & Stateroom
                    </h1>
                    <p class="text-xs sm:text-sm font-medium text-slate-500 mt-1">
                        Manage your passenger identity, security credentials, and past voyage reservation history.
                    </p>
                </div>
            </div>

            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Left Column: Booking History (Table) -->
                <div class="w-full lg:w-[60%] xl:w-[62%]">
                    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/90 overflow-hidden flex flex-col h-full">
                        <div class="p-6 sm:p-8 flex-1 flex flex-col space-y-6">
                            <!-- Header -->
                            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-700 flex items-center justify-center">
                                        <font-awesome-icon icon="fa-solid fa-clock-rotate-left" class="text-orange-500" />
                                    </div>
                                    <div>
                                        <h2 class="font-display text-xl font-bold text-sky-950">
                                            Voyage Booking History
                                        </h2>
                                        <p class="text-xs text-slate-400 font-mono">
                                            All current and historical reservations
                                        </p>
                                    </div>
                                </div>
                                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-mono font-bold">
                                    {{ tableData.length }} records
                                </span>
                            </div>

                            <!-- Table -->
                            <div class="flex-1 min-h-[350px]">
                                <Table :data="tableData" :columns="TableColumns">
                                    <template #ref="{ value }">
                                        <span class="font-mono text-xs text-sky-900 font-bold bg-sky-50 px-2 py-0.5 rounded border border-sky-100">
                                            {{ value }}
                                        </span>
                                    </template>

                                    <template #event="{ row }">
                                        <span class="font-bold text-slate-800 text-xs sm:text-sm">{{ row.event }}</span>
                                    </template>

                                    <template #date="{ value }">
                                        <span class="font-medium text-slate-600 text-xs">{{ formatDate(value) }}</span>
                                    </template>

                                    <template #package="{ row }">
                                        <span class="font-bold text-sky-950 text-xs block">{{ row.package }}</span>
                                        <span
                                            v-if="row.addons"
                                            class="block text-slate-400 font-medium text-[11px] mt-0.5 truncate max-w-[150px]"
                                        >
                                            + {{ row.addons }}
                                        </span>
                                    </template>

                                    <template #amount="{ value }">
                                        <span class="font-display font-bold text-orange-600 text-xs sm:text-sm">
                                            {{ formatAmount(value) }}
                                        </span>
                                    </template>

                                    <template #payment="{ row }">
                                        <span class="font-bold text-slate-700 text-xs block">{{ row.payment_method }}</span>
                                        <span class="block text-slate-400 font-mono text-[10px] mt-0.5">Ref: {{ row.payment_ref }}</span>
                                    </template>

                                    <template #status="{ value }">
                                        <span
                                            class="inline-block text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase tracking-wider"
                                            :class="statusConfig[value]?.classes ?? 'bg-slate-100 text-slate-600'"
                                        >
                                            {{ statusConfig[value]?.label ?? value }}
                                        </span>
                                    </template>
                                </Table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Passenger Stateroom Pass & Account Forms -->
                <div class="w-full lg:w-[40%] xl:w-[38%] flex flex-col gap-6">
                    <!-- Luxury Passenger Stateroom Pass Card -->
                    <div class="rounded-3xl bg-gradient-to-br from-sky-950 via-sky-900 to-slate-900 text-white p-6 shadow-xl border border-sky-800/60 relative overflow-hidden">
                        <!-- Watermark Anchor -->
                        <div class="absolute -right-6 -bottom-6 opacity-10 pointer-events-none">
                            <font-awesome-icon icon="fa-solid fa-anchor" class="text-9xl text-white" />
                        </div>

                        <div class="relative z-10 space-y-4">
                            <div class="flex items-center justify-between border-b border-white/10 pb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                                    <span class="font-mono text-[10px] uppercase tracking-widest text-orange-400 font-bold">
                                        Stateroom Boarding Pass
                                    </span>
                                </div>
                                <span class="font-mono text-[10px] text-slate-300">
                                    PORT OF TALIBON
                                </span>
                            </div>

                            <div class="flex items-center gap-4">
                                <div class="relative">
                                    <img
                                        v-if="user.profile_photo"
                                        :src="'/storage/' + user.profile_photo"
                                        alt="Profile"
                                        class="w-14 h-14 rounded-2xl object-cover border-2 border-orange-400 shadow-md"
                                    />
                                    <div
                                        v-else
                                        class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-orange-500 to-amber-400 text-white flex items-center justify-center font-bold text-xl shadow-md"
                                    >
                                        {{ user.name ? user.name.charAt(0).toUpperCase() : 'P' }}
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1">
                                    <h3 class="font-display font-black text-xl text-white truncate">
                                        {{ user.name }}
                                    </h3>
                                    <p class="text-xs text-sky-200 truncate font-mono">
                                        {{ user.email }}
                                    </p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-md bg-white/10 text-emerald-300 text-[10px] font-mono font-bold">
                                            Verified Passenger
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information Form -->
                    <div class="overflow-hidden bg-white shadow-sm border border-slate-200/90 rounded-3xl p-6 sm:p-8">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6">
                            <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-500 flex items-center justify-center">
                                <font-awesome-icon icon="fa-solid fa-user" />
                            </div>
                            <div>
                                <h3 class="font-display text-lg font-bold text-sky-950">
                                    Personal Details
                                </h3>
                                <p class="text-xs text-slate-400 font-mono">Passenger registry profile</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitInformation" class="space-y-4">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="first_name" value="First Name" class="text-slate-700 text-xs font-bold uppercase tracking-wider" />
                                    <TextInput
                                        id="first_name"
                                        type="text"
                                        class="mt-1 block w-full border-slate-200 focus:border-orange-500 focus:ring-orange-500 rounded-xl text-sm"
                                        v-model="user_information.first_name"
                                        required
                                        autocomplete="first_name"
                                        placeholder="e.g. Alex"
                                    />
                                    <InputError class="mt-1" :message="user_information.errors.first_name" />
                                </div>

                                <div>
                                    <InputLabel for="last_name" value="Last Name" class="text-slate-700 text-xs font-bold uppercase tracking-wider" />
                                    <TextInput
                                        id="last_name"
                                        type="text"
                                        class="mt-1 block w-full border-slate-200 focus:border-orange-500 focus:ring-orange-500 rounded-xl text-sm"
                                        v-model="user_information.last_name"
                                        required
                                        autocomplete="last_name"
                                        placeholder="e.g. Smith"
                                    />
                                    <InputError class="mt-1" :message="user_information.errors.last_name" />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="phone" value="Mobile / Contact No." class="text-slate-700 text-xs font-bold uppercase tracking-wider" />
                                    <TextInput
                                        id="phone"
                                        type="text"
                                        class="mt-1 block w-full border-slate-200 focus:border-orange-500 focus:ring-orange-500 rounded-xl text-sm"
                                        v-model="user_information.phone"
                                        placeholder="0912 345 6789"
                                    />
                                    <InputError class="mt-1" :message="user_information.errors.phone" />
                                </div>

                                <div>
                                    <InputLabel for="birth_date" value="Date of Birth" class="text-slate-700 text-xs font-bold uppercase tracking-wider" />
                                    <TextInput
                                        id="birth_date"
                                        type="date"
                                        class="mt-1 block w-full border-slate-200 focus:border-orange-500 focus:ring-orange-500 rounded-xl text-sm"
                                        v-model="user_information.birth_date"
                                    />
                                    <InputError class="mt-1" :message="user_information.errors.birth_date" />
                                </div>
                            </div>

                            <div>
                                <InputLabel for="address" value="Residential Address" class="text-slate-700 text-xs font-bold uppercase tracking-wider" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full border-slate-200 focus:border-orange-500 focus:ring-orange-500 rounded-xl text-sm"
                                    v-model="user_information.address"
                                    placeholder="e.g. Poblacion, Talibon, Bohol"
                                />
                                <InputError class="mt-1" :message="user_information.errors.address" />
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                                <button
                                    type="submit"
                                    class="bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-sm shadow-orange-500/20 transition-all flex items-center gap-2"
                                    :disabled="user_information.processing"
                                >
                                    <font-awesome-icon v-if="user_information.processing" icon="fa-solid fa-spinner" spin />
                                    <span v-else>Save Information</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Security & Credentials Form -->
                    <div class="overflow-hidden bg-white shadow-sm border border-slate-200/90 rounded-3xl p-6 sm:p-8">
                        <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6">
                            <div class="w-9 h-9 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                                <font-awesome-icon icon="fa-solid fa-shield-halved" />
                            </div>
                            <div>
                                <h3 class="font-display text-lg font-bold text-sky-950">
                                    Account Security
                                </h3>
                                <p class="text-xs text-slate-400 font-mono">Email & password access</p>
                            </div>
                        </div>

                        <form @submit.prevent="submitCredentials" class="space-y-4">
                            <div>
                                <InputLabel for="email" value="Email Address" class="text-slate-700 text-xs font-bold uppercase tracking-wider" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full border-slate-200 focus:border-orange-500 focus:ring-orange-500 rounded-xl text-sm"
                                    v-model="user_credentials.email"
                                    required
                                    autocomplete="username"
                                />
                                <InputError class="mt-1" :message="user_credentials.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="password" value="New Password (Leave blank to keep)" class="text-slate-700 text-xs font-bold uppercase tracking-wider" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-1 block w-full border-slate-200 focus:border-orange-500 focus:ring-orange-500 rounded-xl text-sm"
                                    v-model="user_credentials.password"
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                />
                                <InputError class="mt-1" :message="user_credentials.errors.password" />
                            </div>

                            <div>
                                <InputLabel for="password_confirmation" value="Confirm New Password" class="text-slate-700 text-xs font-bold uppercase tracking-wider" />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full border-slate-200 focus:border-orange-500 focus:ring-orange-500 rounded-xl text-sm"
                                    v-model="user_credentials.password_confirmation"
                                    autocomplete="new-password"
                                    placeholder="••••••••"
                                />
                                <InputError class="mt-1" :message="user_credentials.errors.password_confirmation" />
                            </div>

                            <div class="pt-3 border-t border-slate-100 flex items-center justify-end">
                                <button
                                    type="submit"
                                    class="bg-sky-900 hover:bg-sky-800 text-white font-bold text-xs px-5 py-2.5 rounded-xl shadow-sm transition-all flex items-center gap-2"
                                    :disabled="user_credentials.processing"
                                >
                                    <font-awesome-icon v-if="user_credentials.processing" icon="fa-solid fa-spinner" spin />
                                    <span v-else>Update Security</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
