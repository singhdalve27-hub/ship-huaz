<script setup>
import { ref } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";

const showPassword = ref(false);

const form = useForm({
    password: "",
});

const submit = () => {
    form.post(route("password.confirm"), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout maxWidth="md">
        <Head title="Confirm Password | Butal Ship Hauz" />

        <!-- Header Box (High-Contrast & Eye-Friendly) -->
        <div class="mb-8 p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-orange-50 via-amber-50 to-sky-50 border border-orange-200/80 text-center shadow-sm">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-orange-500 text-white mb-3 shadow-md shadow-orange-500/20">
                <font-awesome-icon icon="fa-solid fa-shield-halved" class="text-xl" />
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Confirm Password
            </h2>
            <p class="text-sm sm:text-base text-slate-800 font-semibold mt-2 leading-relaxed max-w-sm mx-auto">
                This is a secure area of Butal Ship Hauz. Please confirm your password to proceed.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="password" value="Your Password" class="text-sky-950 font-bold text-xs uppercase tracking-wider mb-1.5" />
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <font-awesome-icon icon="fa-solid fa-lock" />
                    </div>
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full pl-10 pr-11 py-3 bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl text-slate-900 font-medium text-sm transition-all"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 transition-colors"
                        tabindex="-1"
                        aria-label="Toggle password visibility"
                    >
                        <font-awesome-icon :icon="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm" />
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="pt-3">
                <button
                    type="submit"
                    class="w-full inline-flex justify-center items-center gap-2 px-6 py-3.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 border border-transparent rounded-xl font-bold text-white text-sm tracking-wide shadow-lg shadow-orange-500/25 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 hover:-translate-y-0.5 active:translate-y-0"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <font-awesome-icon icon="fa-solid fa-lock" />
                    <span>{{ form.processing ? 'VERIFYING...' : 'CONFIRM PASSWORD' }}</span>
                </button>
            </div>
        </form>
    </GuestLayout>
</template>