<script setup>
import { computed } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route("verification.send"));
};

const verificationLinkSent = computed(
    () => props.status === "verification-link-sent",
);
</script>

<template>
    <GuestLayout maxWidth="lg">
        <Head title="Verify Email | Butal Ship Hauz" />

        <!-- Header Box (High-Contrast & Eye-Friendly) -->
        <div class="mb-8 p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-orange-50 via-amber-50 to-sky-50 border border-orange-200/80 text-center shadow-sm">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-orange-500 text-white mb-3 shadow-md shadow-orange-500/20">
                <font-awesome-icon icon="fa-solid fa-envelope-circle-check" class="text-xl" />
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Verify Your Email
            </h2>
            <p class="text-sm sm:text-base text-slate-800 font-semibold mt-2 leading-relaxed max-w-sm mx-auto">
                Thank you for joining Butal Ship Hauz! Before getting started, please check your inbox and click the verification link we sent.
            </p>
        </div>

        <!-- Success Alert -->
        <div
            class="mb-6 text-sm font-medium text-emerald-800 bg-emerald-50 p-4 rounded-xl border border-emerald-200 shadow-sm text-center flex items-center justify-center gap-2.5"
            v-if="verificationLinkSent"
        >
            <font-awesome-icon icon="fa-solid fa-circle-check" class="text-emerald-600 text-base flex-shrink-0" />
            <span>A fresh verification link has been sent to your email address!</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <button
                    type="submit"
                    class="w-full inline-flex justify-center items-center gap-2 px-6 py-3.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 border border-transparent rounded-xl font-bold text-white text-sm tracking-wide shadow-lg shadow-orange-500/25 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 hover:-translate-y-0.5 active:translate-y-0"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <font-awesome-icon icon="fa-solid fa-paper-plane" />
                    <span>{{ form.processing ? 'SENDING LINK...' : 'RESEND VERIFICATION EMAIL' }}</span>
                </button>
            </div>

            <!-- Logout Link -->
            <div class="pt-4 border-t border-slate-100 text-center">
                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="text-xs font-bold text-slate-400 hover:text-orange-600 transition-colors inline-flex items-center gap-1.5"
                >
                    <font-awesome-icon icon="fa-solid fa-right-from-bracket" />
                    <span>Log Out from this Account</span>
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>