<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { Link, Head, useForm } from "@inertiajs/vue3";

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: "",
});

const submit = () => {
    form.post(route("password.email"));
};
</script>

<template>
    <GuestLayout maxWidth="lg">
        <Head title="Forgot Password | Butal Ship Hauz" />

        <!-- Header Box (High-Contrast & Eye-Friendly) -->
        <div class="mb-8 p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-orange-50 via-amber-50 to-sky-50 border border-orange-200/80 text-center shadow-sm">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-orange-500 text-white mb-3 shadow-md shadow-orange-500/20">
                <font-awesome-icon icon="fa-solid fa-key" class="text-xl" />
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Forgot Your Password?
            </h2>
            <p class="text-sm sm:text-base text-slate-800 font-semibold mt-2 leading-relaxed max-w-sm mx-auto">
                No problem. Enter your registered email address and we will send you a secure password reset link to choose a new one.
            </p>
        </div>

        <!-- Status Alert -->
        <div 
            v-if="status" 
            class="mb-6 text-sm font-semibold text-emerald-800 bg-emerald-50 p-4 rounded-xl border border-emerald-200 shadow-sm flex items-center gap-2.5"
        >
            <font-awesome-icon icon="fa-solid fa-circle-check" class="text-emerald-600 text-base flex-shrink-0" />
            <span>{{ status }}</span>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="Email Address" class="text-slate-800 font-bold text-xs uppercase tracking-wider mb-1.5" />
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <font-awesome-icon icon="fa-solid fa-envelope" />
                    </div>
                    <TextInput
                        id="email"
                        type="email"
                        class="block w-full pl-10 pr-4 py-3 bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl text-slate-900 font-medium text-sm transition-all placeholder:text-slate-400"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="youremail@example.com"
                    />
                </div>
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="pt-3">
                <button
                    type="submit"
                    class="w-full inline-flex justify-center items-center gap-2 px-6 py-3.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 border border-transparent rounded-xl font-black text-white text-sm tracking-wide shadow-md shadow-orange-500/25 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 hover:-translate-y-0.5 active:translate-y-0"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <font-awesome-icon icon="fa-solid fa-paper-plane" />
                    <span>{{ form.processing ? 'SENDING LINK...' : 'SEND PASSWORD RESET LINK' }}</span>
                </button>
            </div>
        </form>

        <!-- Back to Login -->
        <div class="mt-8 pt-6 border-t border-slate-200 text-center">
            <Link
                :href="route('login')"
                class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-orange-600 transition-colors"
            >
                <font-awesome-icon icon="fa-solid fa-arrow-left" class="text-[10px]" />
                <span>Return to Login</span>
            </Link>
        </div>
    </GuestLayout>
</template>