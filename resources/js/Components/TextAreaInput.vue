<script setup>
import { onMounted, ref } from 'vue';

const model = defineModel({
    type: String,
    required: true,
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
    <textarea
        class="rounded-md border-slate-300 shadow-sm focus:border-orange-500 focus:ring-1 focus:ring-orange-500 text-sm font-medium text-slate-700 placeholder-slate-400 resize-none"
        v-model="model"
        ref="input"
    />
</template>