<script setup>
defineProps({
    type: {
        type: String,
        default: 'submit',
    },
});
</script>

<template>
    <button
        :type="type"
        class="inline-flex justify-center items-center px-6 py-2.5 bg-orange-500 hover:bg-orange-600 border border-transparent rounded-md font-bold text-white tracking-widest shadow-md shadow-orange-500/30 transition ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2"
    >
        <slot />
    </button>
</template>