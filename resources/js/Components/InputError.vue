<script setup>
defineProps({
    message: {
        type: String,
    },
});
</script>

<template>
    <div v-show="message" class="flex items-center gap-1.5 mt-1.5">
        <font-awesome-icon icon="fa-solid fa-circle-exclamation" class="text-red-500 text-xs" />
        <p class="text-sm font-semibold text-red-500 tracking-wide">
            {{ message }}
        </p>
    </div>
</template>