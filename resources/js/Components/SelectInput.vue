<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
});

const props = defineProps({
    options: {
        type: Array,
        required: true,
        // Expected format: [{ value: 'val', label: 'Label' }] or ['val1', 'val2']
    },
});

const input = ref(null);

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus();
    }
});

defineExpose({ focus: () => input.value.focus() });
</script>

<template>
    <select
        class="rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 text-sm font-medium text-slate-700 disabled:opacity-50 disabled:bg-slate-50"
        v-model="model"
        ref="input"
    >
        <option
            v-for="option in options"
            :key="typeof option === 'object' ? option.value : option"
            :value="typeof option === 'object' ? option.value : option"
            :disabled="typeof option === 'object' ? option.disabled : false"
        >
            {{ typeof option === 'object' ? option.label : option }}
        </option>
    </select>
</template>