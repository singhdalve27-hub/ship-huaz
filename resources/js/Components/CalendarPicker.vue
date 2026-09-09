<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  modelValue: String,
  min: String,
  reservedDates: {
    type: Array,
    default: () => [],
  },
});
const emit = defineEmits(["update:modelValue"]);

const DAYS   = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
const MONTHS = ["January","February","March","April","May","June",
                "July","August","September","October","November","December"];

const today = new Date(); today.setHours(0, 0, 0, 0);
const current = ref(new Date(today.getFullYear(), today.getMonth(), 1));

const year  = computed(() => current.value.getFullYear());
const month = computed(() => current.value.getMonth());
const monthLabel = computed(() => `${MONTHS[month.value]} ${year.value}`);

const firstDayOffset = computed(() =>
    new Date(year.value, month.value, 1).getDay()
);
const daysInMonth = computed(() =>
    new Date(year.value, month.value + 1, 0).getDate()
);

const minDate = computed(() =>
    props.min ? new Date(props.min) : today
);

const canGoPrev = computed(() => {
    const prev = new Date(year.value, month.value - 1, 1);
    return prev >= new Date(today.getFullYear(), today.getMonth(), 1);
});

function prevMonth() { current.value = new Date(year.value, month.value - 1, 1); }
function nextMonth() { current.value = new Date(year.value, month.value + 1, 1); }

function isPast(d) {
    return new Date(year.value, month.value, d) < minDate.value;
}

function isToday(d) {
    const date = new Date(year.value, month.value, d);
    return date.getTime() === today.getTime();
}

function isSelected(d) {
    if (!props.modelValue) return false;
    const [y, m, day] = props.modelValue.split("-").map(Number);
    return year.value === y && month.value === m - 1 && d === day;
}

function hasBooking(d) {
    if (!props.reservedDates || !props.reservedDates.length) return false;
    const m = String(month.value + 1).padStart(2, "0");
    const day = String(d).padStart(2, "0");
    const dateStr = `${year.value}-${m}-${day}`;
    return props.reservedDates.includes(dateStr);
}

function selectDay(d) {
    if (isPast(d)) return;
    // format as YYYY-MM-DD to match your existing eventDate ref
    const m = String(month.value + 1).padStart(2, "0");
    const day = String(d).padStart(2, "0");
    emit("update:modelValue", `${year.value}-${m}-${day}`);
}
</script>

<template>
  <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden select-none font-body">
    <!-- Header -->
    <div class="flex items-center justify-between px-4 py-3.5 border-b border-slate-100 bg-slate-50">
      <button
        @click="prevMonth"
        :disabled="!canGoPrev"
        class="w-8 h-8 flex items-center justify-center rounded-md border border-slate-200 text-slate-600 bg-white hover:bg-orange-50 hover:text-orange-500 hover:border-orange-200 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
      >
        <font-awesome-icon icon="fa-solid fa-chevron-left" class="text-xs" />
      </button>
      <span class="text-sm font-bold tracking-wide text-sky-900">{{ monthLabel }}</span>
      <button
        @click="nextMonth"
        class="w-8 h-8 flex items-center justify-center rounded-md border border-slate-200 text-slate-600 bg-white hover:bg-orange-50 hover:text-orange-500 hover:border-orange-200 transition-colors"
      >
        <font-awesome-icon icon="fa-solid fa-chevron-right" class="text-xs" />
      </button>
    </div>

    <!-- Day labels -->
    <div class="grid grid-cols-7 px-4 pt-4 pb-2">
      <div
        v-for="d in DAYS" :key="d"
        class="text-center text-[10px] font-bold text-slate-400 uppercase tracking-widest"
      >{{ d }}</div>
    </div>

    <!-- Day grid -->
    <div class="grid grid-cols-7 px-3 pb-4 gap-1">
      <!-- empty offset cells -->
      <div v-for="n in firstDayOffset" :key="'e' + n" />

      <button
        v-for="d in daysInMonth" :key="d"
        @click="selectDay(d)"
        :disabled="isPast(d)"
        :class="[
          'relative flex flex-col items-center justify-center h-10 w-full text-sm rounded-lg transition-all font-medium',
          isSelected(d)
            ? 'bg-orange-500 text-white font-bold shadow-md shadow-orange-500/30'
            : isToday(d)
            ? 'border-2 border-lime-400 text-sky-900 font-bold hover:bg-lime-50'
            : isPast(d)
            ? 'text-slate-300 cursor-not-allowed line-through decoration-slate-200'
            : hasBooking(d)
            ? 'bg-amber-50 text-sky-950 font-bold border border-amber-200 hover:bg-amber-100/70'
            : 'text-slate-700 hover:bg-sky-50 hover:text-sky-900'
        ]"
      >
        <span>{{ d }}</span>
        <span
          v-if="hasBooking(d)"
          :class="[
            'w-1.5 h-1.5 rounded-full absolute bottom-1',
            isSelected(d) ? 'bg-white' : 'bg-amber-500'
          ]"
          title="Reserved events on this date"
        />
      </button>
    </div>
  </div>
</template>