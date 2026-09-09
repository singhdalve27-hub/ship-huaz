<script setup>
import { onMounted, onBeforeUnmount, ref, computed } from 'vue';

const model = defineModel({
    type: [String, Array],
    required: true,
});

const props = defineProps({
    options: {
        type: Array,
        required: true,
        // Expected format: [{ value: 'val', label: 'Label' }] or ['val1', 'val2']
    },
    multiple: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: 'Select...',
    },
});

const container = ref(null);
const searchInput = ref(null);
const isOpen = ref(false);
const search = ref('');

const normalizedOptions = computed(() =>
    props.options.map(option =>
        typeof option === 'object'
            ? option
            : { value: option, label: option, disabled: false }
    )
);

const filteredOptions = computed(() =>
    normalizedOptions.value.filter(option =>
        option.label.toLowerCase().includes(search.value.toLowerCase())
    )
);

const selectedLabels = computed(() => {
    if (!props.multiple) return [];
    return (model.value || []).map(val => {
        const found = normalizedOptions.value.find(o => o.value === val);
        return found ? found.label : val;
    });
});

const isSelected = (value) => {
    if (props.multiple) return (model.value || []).includes(value);
    return model.value === value;
};

const toggleOption = (option) => {
    if (option.disabled) return;

    if (props.multiple) {
        const current = Array.isArray(model.value) ? [...model.value] : [];
        const index = current.indexOf(option.value);
        if (index === -1) current.push(option.value);
        else current.splice(index, 1);
        model.value = current;
    } else {
        model.value = option.value;
        closeDropdown();
    }
};

const removeTag = (value) => {
    const current = Array.isArray(model.value) ? [...model.value] : [];
    model.value = current.filter(v => v !== value);
};

const openDropdown = () => {
    isOpen.value = true;
    setTimeout(() => searchInput.value?.focus(), 50);
};

const closeDropdown = () => {
    isOpen.value = false;
    search.value = '';
};

const handleClickOutside = (e) => {
    if (container.value && !container.value.contains(e.target)) {
        closeDropdown();
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', handleClickOutside);
});

defineExpose({ focus: () => searchInput.value?.focus() });
</script>

<template>
    <div class="w-full relative" ref="container">
        <!-- Trigger Box -->
        <div
            class="min-h-[42px] w-full rounded-md border border-slate-300 bg-white px-3 py-1.5 flex flex-wrap gap-1.5 items-center cursor-text shadow-sm focus-within:border-orange-500 focus-within:ring-1 focus-within:ring-orange-500 transition-colors"
            @click="openDropdown"
        >
            <!-- Tags (multiple mode) -->
            <template v-if="multiple">
                <span
                    v-for="(val, index) in (model || [])"
                    :key="val"
                    class="inline-flex items-center gap-1.5 bg-sky-100 text-sky-900 border border-sky-200 font-bold tracking-wide text-xs px-2.5 py-1 rounded-full shadow-sm"
                >
                    {{ selectedLabels[index] }}
                    <button
                        type="button"
                        class="hover:text-red-500 hover:bg-white rounded-full w-[18px] h-[18px] flex items-center justify-center transition-colors focus:outline-none"
                        @click.stop="removeTag(val)"
                    >
                        <font-awesome-icon icon="fa-solid fa-xmark" class="text-[10px]" />
                    </button>
                </span>
            </template>

            <!-- Single mode display -->
            <span v-else-if="model" class="text-sm font-medium text-slate-700">
                {{ normalizedOptions.find(o => o.value === model)?.label || model }}
            </span>

            <!-- Search input -->
            <input
                ref="searchInput"
                v-model="search"
                type="text"
                class="flex-1 min-w-[80px] border-0 outline-none ring-0 focus:ring-0 text-sm font-medium bg-transparent placeholder-slate-400 p-0"
                :placeholder="(!multiple && !model) || (multiple && !(model || []).length) ? placeholder : ''"
                @focus="isOpen = true"
                @keydown.escape="closeDropdown"
            />
        </div>

        <!-- Dropdown with slide-up transition -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-2 scale-95"
        >
            <ul
                v-if="isOpen"
                class="absolute z-50 mt-1.5 w-full bg-white border border-slate-200 rounded-md shadow-xl max-h-60 overflow-y-auto origin-top"
            >
                <li
                    v-for="option in filteredOptions"
                    :key="option.value"
                    class="px-4 py-2.5 text-sm font-medium cursor-pointer select-none transition-colors"
                    :class="{
                        'bg-sky-50 text-sky-900 border-l-4 border-orange-500': isSelected(option.value),
                        'text-slate-400 cursor-not-allowed': option.disabled,
                        'text-slate-700 hover:bg-slate-50 hover:text-orange-500 border-l-4 border-transparent': !isSelected(option.value) && !option.disabled,
                    }"
                    @mousedown.prevent="toggleOption(option)"
                >
                    <div class="flex items-center justify-between">
                        <span>{{ option.label }}</span>
                        <font-awesome-icon v-if="isSelected(option.value)" icon="fa-solid fa-check" class="text-lime-500 text-xs" />
                    </div>
                </li>

                <li v-if="filteredOptions.length === 0" class="px-4 py-4 text-sm font-medium text-slate-400 text-center italic">
                    No options found.
                </li>
            </ul>
        </Transition>
    </div>
</template>