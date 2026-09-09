<script setup>
import { ref, computed, watch } from "vue";

const props = defineProps({
    data: {
        type: Array,
        default: () => [],
    },
    columns: {
        type: Array,
        required: true,
        // e.g. [{ key: 'ref', label: 'Reference #' }, { key: 'status', label: 'Status', slot: 'status' }]
    },
    actions: {
        type: Object,
        default: () => ({
            isDateFilterShow: true,
            isPerPageShow: true,
            isSearchShow: true,
        }),
    },
    initialEmpty: {
        type: Boolean,
        default: false,
    },
    emptyStateMessage: {
        type: String,
        default: null,
    },
});

// Filters
const dateFrom = ref("");
const dateTo = ref("");
const searchQuery = ref("");
const perPage = ref(10);
const currentPage = ref(1);
const perPageOptions = [5, 10, 25, 50];

// Reset page on any filter change
watch([dateFrom, dateTo, searchQuery, perPage], () => {
    currentPage.value = 1;
});

// Filtered data
const filteredBookings = computed(() => {
    let result = [...props.data];

    // Date range filter
    if (dateFrom.value) {
        result = result.filter(
            (b) => new Date(b.date) >= new Date(dateFrom.value),
        );
    }
    if (dateTo.value) {
        result = result.filter(
            (b) => new Date(b.date) <= new Date(dateTo.value),
        );
    }

    // Search query across all column keys
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase();
        result = result.filter((row) =>
            props.columns.some((col) => {
                const val = row[col.key];
                return val != null && String(val).toLowerCase().includes(query);
            }),
        );
    }

    return result;
});

// Pagination
const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredBookings.value.length / perPage.value)),
);

const paginatedData = computed(() => {
    if (props.initialEmpty && !hasActiveFilters.value) return [];
    const start = (currentPage.value - 1) * perPage.value;
    return filteredBookings.value.slice(start, start + perPage.value);
});

const goToPage = (page) => {
    if (page >= 1 && page <= totalPages.value) {
        currentPage.value = page;
    }
};

const visiblePages = computed(() => {
    const total = totalPages.value;
    const current = currentPage.value;
    const delta = 2;
    const pages = [];

    for (
        let i = Math.max(1, current - delta);
        i <= Math.min(total, current + delta);
        i++
    ) {
        pages.push(i);
    }

    if (pages[0] > 1) {
        if (pages[0] > 2) pages.unshift("...");
        pages.unshift(1);
    }
    if (pages[pages.length - 1] < total) {
        if (pages[pages.length - 1] < total - 1) pages.push("...");
        pages.push(total);
    }

    return pages;
});

const hasActiveFilters = computed(
    () => dateFrom.value || dateTo.value || searchQuery.value,
);

const clearFilters = () => {
    dateFrom.value = "";
    dateTo.value = "";
    searchQuery.value = "";
    currentPage.value = 1;
};

const rangeStart = computed(() =>
    filteredBookings.value.length === 0
        ? 0
        : (currentPage.value - 1) * perPage.value + 1,
);
const rangeEnd = computed(() =>
    Math.min(currentPage.value * perPage.value, filteredBookings.value.length),
);

const isShowingResults = computed(
    () => !props.initialEmpty || hasActiveFilters.value,
);
</script>

<template>
    <!-- Search & Filters -->
    <div class="space-y-3 mb-4">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center justify-between">
            <!-- Filter Row -->
            <div class="flex flex-wrap items-center gap-3" v-if="actions.isDateFilterShow">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <span class="text-xs font-bold font-mono uppercase tracking-wider text-slate-700">From</span>
                    <input
                        v-model="dateFrom"
                        type="date"
                        class="text-xs font-medium border border-slate-300 rounded-xl px-3 py-2 w-full sm:w-auto focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 bg-white text-slate-800 shadow-xs transition-all"
                    />
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <span class="text-xs font-bold font-mono uppercase tracking-wider text-slate-700">To</span>
                    <input
                        v-model="dateTo"
                        type="date"
                        class="text-xs font-medium border border-slate-300 rounded-xl px-3 py-2 w-full sm:w-auto focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 bg-white text-slate-800 shadow-xs transition-all"
                    />
                </div>

                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1.5 px-3.5 py-2 w-full sm:w-auto rounded-xl border border-rose-200 hover:bg-rose-50 transition-colors shadow-xs bg-white cursor-pointer"
                >
                    <font-awesome-icon icon="fa-solid fa-xmark" />
                    <span>Clear Filters</span>
                </button>
            </div>

            <!-- Per-page selector -->
            <div class="flex items-center gap-2 bg-white border border-slate-200 px-3.5 py-1.5 rounded-xl shadow-xs ml-auto sm:ml-0" v-if="actions.isPerPageShow">
                <span class="text-xs font-semibold text-slate-600">Show</span>
                <select
                    v-model="perPage"
                    class="text-xs border-none bg-transparent py-1 pl-1 pr-6 focus:outline-none focus:ring-0 text-slate-900 font-bold cursor-pointer"
                >
                    <option v-for="n in perPageOptions" :key="n" :value="n">
                        {{ n }}
                    </option>
                </select>
                <span class="text-xs font-semibold text-slate-600">entries</span>
            </div>
        </div>

        <div class="flex items-center" v-if="actions.isSearchShow">
            <!-- Search Bar -->
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none">
                    <font-awesome-icon icon="fa-solid fa-magnifying-glass" class="text-sm" />
                </span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search table records..."
                    class="w-full pl-10 pr-4 py-2.5 text-sm font-medium border border-slate-300 rounded-xl focus:outline-none focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-xs bg-white text-slate-900 placeholder:text-slate-400 transition-all"
                />
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-hidden rounded-2xl border border-slate-200 shadow-sm bg-white">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-900 border-b-2 border-orange-500">
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-5 py-4 border-r border-slate-800 last:border-r-0 text-xs font-bold text-white uppercase tracking-wider whitespace-nowrap"
                        >
                            {{ col.label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-800">
                    <tr
                        v-for="row in paginatedData"
                        :key="row.id ?? row.ref"
                        class="hover:bg-orange-50/30 transition-colors"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="px-5 py-3.5 border-r border-slate-100 last:border-r-0 whitespace-nowrap"
                        >
                            <!-- If column has a slot, let the parent render it -->
                            <slot
                                v-if="col.slot"
                                :name="col.slot"
                                :row="row"
                                :value="row[col.key]"
                            />

                            <!-- Otherwise just render the value -->
                            <span v-else class="text-slate-800 font-medium">{{ row[col.key] ?? "—" }}</span>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr v-if="paginatedData.length === 0">
                        <td
                            :colspan="columns.length"
                            class="py-16 text-center text-slate-400"
                        >
                            <div class="w-14 h-14 rounded-2xl bg-orange-50 text-orange-500 flex items-center justify-center mx-auto mb-3 text-2xl shadow-xs">
                                <font-awesome-icon icon="fa-solid fa-folder-open" />
                            </div>
                            <p class="text-base font-bold text-slate-900">
                                {{
                                    isShowingResults
                                        ? "No matching records found"
                                        : (emptyStateMessage ?? "Apply a filter to view records")
                                }}
                            </p>
                            <p class="text-sm font-medium mt-1 text-slate-500" v-if="isShowingResults">
                                Try clearing your search query or broadening your filters.
                            </p>
                        </td>
                    </tr>
                </tbody>

                <!-- Optional summary row (e.g. a grand total) -->
                <tfoot v-if="$slots.summary && isShowingResults && filteredBookings.length > 0">
                    <slot name="summary" />
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Pagination Footer -->
    <div
        v-if="filteredBookings.length > 0 && isShowingResults"
        class="flex flex-col sm:flex-row items-center justify-between gap-4 mt-5 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs"
    >
        <!-- Range info -->
        <p class="text-xs font-semibold text-slate-600">
            Showing
            <span class="font-bold text-slate-900">{{ rangeStart }}–{{ rangeEnd }}</span>
            of
            <span class="font-bold text-slate-900">{{ filteredBookings.length }}</span>
            entries
        </p>

        <!-- Page buttons -->
        <div class="flex items-center gap-1.5">
            <!-- Prev -->
            <button
                @click="currentPage--"
                :disabled="currentPage === 1"
                class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold transition-all cursor-pointer"
                :class="
                    currentPage === 1
                        ? 'bg-slate-50 text-slate-300 cursor-not-allowed border-slate-200'
                        : 'bg-white text-slate-700 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-300 shadow-xs'
                "
            >
                <font-awesome-icon icon="fa-solid fa-chevron-left" />
            </button>

            <!-- Page numbers -->
            <template v-for="(page, i) in visiblePages" :key="i">
                <span
                    v-if="page === '...'"
                    class="px-2 py-1 text-xs font-bold text-slate-400"
                    >…</span
                >
                <button
                    v-else
                    @click="goToPage(page)"
                    class="min-w-[34px] px-2.5 py-1.5 rounded-xl border text-xs font-bold transition-all cursor-pointer"
                    :class="
                        page === currentPage
                            ? 'bg-gradient-to-r from-orange-500 to-amber-500 text-white border-transparent shadow-md shadow-orange-500/25'
                            : 'bg-white border-slate-200 text-slate-700 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-300 shadow-xs'
                    "
                >
                    {{ page }}
                </button>
            </template>

            <!-- Next -->
            <button
                @click="currentPage++"
                :disabled="currentPage === totalPages"
                class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-bold transition-all cursor-pointer"
                :class="
                    currentPage === totalPages
                        ? 'bg-slate-50 text-slate-300 cursor-not-allowed border-slate-200'
                        : 'bg-white text-slate-700 hover:bg-orange-50 hover:text-orange-600 hover:border-orange-300 shadow-xs'
                "
            >
                <font-awesome-icon icon="fa-solid fa-chevron-right" />
            </button>
        </div>
    </div>
</template>
