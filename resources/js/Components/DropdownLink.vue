<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Link
        :href="href"
        class="block w-full px-4 py-2.5 text-left text-sm font-semibold text-slate-700 transition duration-150 ease-in-out hover:bg-sky-50 hover:text-orange-500 focus:bg-sky-50 focus:text-orange-500 focus:outline-none"
    >
        <slot />
    </Link>
</template>