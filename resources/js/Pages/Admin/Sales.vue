<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import Table from "@/Components/Table.vue";
import { Head, Link } from "@inertiajs/vue3";
import { ref, computed } from "vue";
import { useFormatter } from "@/Composables/useFormatter";

const props = defineProps({
    bookings: Array,
});

const { formatAmount, formatDate, formatPhone } = useFormatter();

const tableColumns = [
    { key: "booking_ref", label: "Booking Ref", slot: "ref" },
    { key: "event_details", label: "Event Details", slot: "event_details" },
    { key: "package", label: "Package", slot: "package" },
    { key: "contact", label: "Contact", slot: "contact" },
    { key: "payment", label: "Payment", slot: "payment" },
    { key: "date", label: "Date", slot: "date" },
];

const tableActions = {
    isDateFilterShow: true,
    isPerPageShow: true,
    isSearchShow: false,
};

const filteredRows = ref([...(props.bookings ?? [])]);
const activeDateFrom = ref("");
const activeDateTo = ref("");
const hasFilters = ref(false);

const handleFilteredChange = ({
    rows,
    dateFrom,
    dateTo,
    hasFilters: active,
}) => {
    filteredRows.value = rows && rows.length ? rows : (active ? [] : [...(props.bookings ?? [])]);
    activeDateFrom.value = dateFrom;
    activeDateTo.value = dateTo;
    hasFilters.value = active;
};

const grandTotal = computed(() =>
    filteredRows.value.reduce(
        (sum, b) => sum + Number(b.total_payment || 0),
        0,
    ),
);

const canAct = computed(
    () => filteredRows.value.length > 0 || (props.bookings && props.bookings.length > 0),
);

const handlePrint = () => {
    window.print();
};
</script>

<template>
    <Head title="Daily Sales Report" />

    <AdminLayout>
        <div>
            <div
                class="bg-white rounded-xl shadow-sm border border-stone-100 overflow-hidden px-4 py-4"
            >
                <div
                    class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-2 print:hidden"
                >
                    <h1 class="text-xl font-bold text-stone-800">
                        Daily Sales Report
                    </h1>

                    <div class="flex flex-wrap gap-1">
                        <button
                            @click="handlePrint"
                            :disabled="!canAct"
                            class="flex items-center gap-2 bg-stone-600 hover:bg-stone-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold px-3 py-2 rounded-md transition-colors"
                        >
                            <font-awesome-icon icon="fa-solid fa-print" />
                            Print
                        </button>
                        <Link
                            :href="route('admin.sales.index')"
                            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold px-3 py-2 rounded-md transition-colors"
                        >
                            <font-awesome-icon icon="fa-solid fa-sync" />
                            Refresh
                        </Link>
                    </div>
                </div>
                <hr class="my-2" />
                <!-- Interactive, paginated table (hidden while printing) -->
                <div class="print:hidden">
                    <Table
                        :data="bookings"
                        :columns="tableColumns"
                        :actions="tableActions"
                        :initial-empty="false"
                        empty-state-message="No sales records found"
                        @filtered-change="handleFilteredChange"
                    >
                        <!-- Ref -->
                        <template #ref="{ row }">
                            <p class="text-sm font-medium text-stone-700">
                                {{ row.booking_ref }}
                            </p>
                        </template>

                        <!-- Event Details -->
                        <template #event_details="{ row }">
                            <p class="text-sm font-medium text-stone-700">
                                {{ row.event_type?.type ?? "—" }}
                            </p>
                            <p class="text-sm text-stone-500">
                                {{ row.date ? formatDate(row.date) : "—" }}
                            </p>
                            <p class="text-sm text-stone-500">
                                {{ row.time_slot ?? "—" }}
                            </p>
                            <p class="text-sm text-stone-500">
                                {{ row.guest_count }} guests
                            </p>
                        </template>

                        <!-- Package -->
                        <template #package="{ row }">
                            <p class="text-sm font-medium text-stone-700">
                                {{ row.venue_package?.title ?? "—" }}
                            </p>
                            <p class="text-sm text-stone-500">
                                {{
                                    row.venue_package?.price
                                        ? formatAmount(row.venue_package.price)
                                        : "—"
                                }}
                            </p>
                            <template v-if="row.package_add_ons?.length">
                                <hr class="my-1 border-stone-200" />
                                <p class="text-xs text-stone-400 mb-0.5">
                                    Add-ons:
                                </p>
                                <p
                                    v-for="(
                                        addon, index
                                    ) in row.package_add_ons"
                                    :key="index"
                                    class="text-sm text-stone-500"
                                >
                                    • {{ addon.title }} ({{
                                        formatAmount(addon.price)
                                    }})
                                </p>
                            </template>
                            <hr class="my-1 border-stone-200" />
                            <p class="text-sm font-semibold text-stone-700">
                                Total: {{ formatAmount(row.total_payment) }}
                            </p>
                        </template>

                        <!-- Contact -->
                        <template #contact="{ row }">
                            <p class="text-sm font-medium text-stone-700">
                                Client: {{ row.user?.user_info.first_name }}
                                {{ row.user?.user_info.last_name }}
                            </p>
                            <p class="text-sm text-stone-500">
                                Client Email: {{ row.user?.email }}
                            </p>
                            <p class="text-sm text-stone-500">
                                Client Phone:
                                {{ formatPhone(row.user?.user_info.phone) }}
                            </p>
                            <hr class="my-1 border-stone-200" />
                            <p class="text-sm font-medium text-stone-700">
                                Guest: {{ row.guest_first_name }}
                                {{ row.guest_last_name }}
                            </p>
                            <p class="text-sm text-stone-500">
                                Guest Email: {{ row.guest_email }}
                            </p>
                            <p class="text-sm text-stone-500">
                                Guest Phone: {{ formatPhone(row.guest_phone) }}
                            </p>
                        </template>

                        <!-- Payment -->
                        <template #payment="{ row }">
                            <p class="text-sm font-medium text-stone-700">
                                {{ row.payment_option?.payment ?? "Walk-in" }}
                            </p>
                            <template v-if="row.payment_option">
                                <p class="text-sm text-stone-500">
                                    {{ row.payment_option.account }}
                                </p>
                                <p class="text-sm text-stone-500">
                                    {{ formatPhone(row.payment_option.number) }}
                                </p>
                            </template>
                            <template v-if="row.payment_transaction_ref">
                                <hr class="my-1 border-stone-200" />
                                <p class="text-xs text-stone-400">Ref #</p>
                                <p class="text-sm text-stone-500">
                                    {{ row.payment_transaction_ref }}
                                </p>
                                <p class="text-sm text-stone-500">
                                    {{
                                        formatPhone(row.payment_account_number)
                                    }}
                                </p>
                            </template>
                        </template>

                        <!-- Date -->
                        <template #date="{ row }">
                            <p class="text-sm text-stone-600">
                                {{ row.date ? formatDate(row.date) : "—" }}
                            </p>
                        </template>

                        <!-- Grand total row: sums ALL filtered rows, not just the current page -->
                        <template #summary>
                            <tr class="bg-stone-50">
                                <td
                                    :colspan="tableColumns.length"
                                    class="px-3 py-2.5 border border-stone-400 text-sm text-right font-semibold text-stone-700"
                                >
                                    Grand Total ({{ filteredRows.length }}
                                    {{
                                        filteredRows.length === 1
                                            ? "sale"
                                            : "sales"
                                    }}):
                                    {{ formatAmount(grandTotal) }}
                                </td>
                            </tr>
                        </template>
                    </Table>
                </div>

                <!-- Print-only view: full filtered list, no pagination, plus a grand total -->
                <div id="print-table" class="hidden print:block text-black">
                    <div class="mb-4 pb-3 border-b-2 border-slate-900 flex justify-between items-end">
                        <div>
                            <h2 class="text-2xl font-black tracking-tight text-slate-900">Daily Sales Report</h2>
                            <p class="text-sm font-bold text-orange-600 font-display">Butal Ship Hauz • Talibon, Bohol</p>
                            <p class="text-xs text-slate-600 mt-1">
                                <strong>Period:</strong> 
                                {{ activeDateFrom ? formatDate(activeDateFrom) : "All Time / Manifest" }}
                                <template v-if="activeDateTo"> to {{ formatDate(activeDateTo) }}</template>
                            </p>
                        </div>
                        <div class="text-right text-xs text-slate-500 font-mono">
                            <p>Total Records: <strong>{{ filteredRows.length }}</strong></p>
                            <p>Printed: {{ new Date().toLocaleString("en-PH") }}</p>
                        </div>
                    </div>

                    <table class="w-full text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-100">
                                <th class="border border-slate-400 px-2 py-1.5 text-center">#</th>
                                <th class="border border-slate-400 px-2 py-1.5 text-left">Booking Ref</th>
                                <th class="border border-slate-400 px-2 py-1.5 text-left">Event</th>
                                <th class="border border-slate-400 px-2 py-1.5 text-left">Package</th>
                                <th class="border border-slate-400 px-2 py-1.5 text-left">Client</th>
                                <th class="border border-slate-400 px-2 py-1.5 text-center">Payment</th>
                                <th class="border border-slate-400 px-2 py-1.5 text-center">Date</th>
                                <th class="border border-slate-400 px-2 py-1.5 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(row, index) in filteredRows" :key="row.id" class="border-b border-slate-200">
                                <td class="border border-slate-300 px-2 py-1 text-center font-mono">{{ ++index }}</td>
                                <td class="border border-slate-300 px-2 py-1 font-mono font-bold">{{ row.booking_ref }}</td>
                                <td class="border border-slate-300 px-2 py-1">{{ row.event_type?.type ?? "—" }}</td>
                                <td class="border border-slate-300 px-2 py-1">{{ row.venue_package?.title ?? "—" }}</td>
                                <td class="border border-slate-300 px-2 py-1">{{ row.user?.user_info?.first_name }} {{ row.user?.user_info?.last_name }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-center">{{ row.payment_option?.payment ?? "Walk-in" }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-center">{{ row.date ? formatDate(row.date) : "—" }}</td>
                                <td class="border border-slate-300 px-2 py-1 text-right font-bold">{{ formatAmount(row.total_payment) }}</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="bg-slate-100">
                                <td colspan="7" class="border border-slate-400 px-2 py-2 text-right font-bold text-sm">
                                    Grand Total:
                                </td>
                                <td class="border border-slate-400 px-2 py-2 text-right font-black text-sm text-slate-900">
                                    {{ formatAmount(grandTotal) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style>
@media print {
    @page {
        size: auto;
        margin: 10mm 12mm;
    }
    body {
        background: white !important;
        color: black !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    header, footer, nav, aside, .print\:hidden {
        display: none !important;
    }
    #print-table {
        display: block !important;
        width: 100% !important;
    }
    #print-table table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    #print-table th, #print-table td {
        border: 1px solid #94a3b8 !important;
    }
    #print-table th {
        background-color: #f1f5f9 !important;
        color: #0f172a !important;
    }
}
</style>
