<script setup>
import { ref } from "vue";
import Checkbox from "@/Components/Checkbox.vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post(route("login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <GuestLayout maxWidth="lg">
        <Head title="Sign In | Butal Ship Hauz" />

        <!-- Header Box (High-Contrast & Eye-Friendly) -->
        <div class="mb-8 p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-orange-50 via-amber-50 to-sky-50 border border-orange-200/80 text-center shadow-sm">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-orange-500 text-white mb-3 shadow-md shadow-orange-500/20">
                <font-awesome-icon icon="fa-solid fa-ship" class="text-xl" />
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Welcome Aboard!
            </h2>
            <p class="text-sm sm:text-base text-slate-800 font-semibold mt-2 leading-relaxed max-w-md mx-auto">
                Sign in to manage your venue reservations, customize event packages, and track booking approvals.
            </p>
        </div>

        <!-- Status Message -->
        <div 
            v-if="status" 
            class="mb-6 text-sm font-semibold text-emerald-800 bg-emerald-50 p-4 rounded-xl border border-emerald-200 shadow-sm flex items-center gap-2.5"
        >
            <font-awesome-icon icon="fa-solid fa-circle-check" class="text-emerald-600 text-base" />
            <span>{{ status }}</span>
        </div>

        <!-- Form -->
        <form @submit.prevent="submit" class="space-y-5">
            <!-- Email Field -->
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

            <!-- Password Field with Show/Hide Toggle -->
            <div>
                <div class="flex items-center justify-between mb-1.5">
                    <InputLabel for="password" value="Password" class="text-slate-800 font-bold text-xs uppercase tracking-wider" />
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-xs font-bold text-orange-600 hover:text-orange-700 hover:underline transition-colors"
                    >
                        Forgot password?
                    </Link>
                </div>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                        <font-awesome-icon icon="fa-solid fa-lock" />
                    </div>
                    <TextInput
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        class="block w-full pl-10 pr-11 py-3 bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl text-slate-900 font-medium text-sm transition-all placeholder:text-slate-400"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-slate-800 transition-colors"
                        tabindex="-1"
                        aria-label="Toggle password visibility"
                    >
                        <font-awesome-icon :icon="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-sm" />
                    </button>
                </div>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- Remember Me -->
            <div class="flex items-center justify-between pt-1">
                <label class="flex items-center cursor-pointer select-none group">
                    <Checkbox 
                        name="remember" 
                        v-model:checked="form.remember" 
                        class="text-orange-500 focus:ring-orange-500 border-slate-300 rounded-md group-hover:border-orange-500 transition-colors"
                    />
                    <span class="ms-2.5 text-xs font-bold text-slate-700 group-hover:text-slate-900 transition-colors">
                        Keep me signed in
                    </span>
                </label>
            </div>

            <!-- Submit Button -->
            <div class="pt-3">
                <button
                    type="submit"
                    class="w-full inline-flex justify-center items-center gap-2 px-6 py-3.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 border border-transparent rounded-xl font-black text-white text-sm tracking-wide shadow-md shadow-orange-500/25 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 hover:-translate-y-0.5 active:translate-y-0"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <font-awesome-icon icon="fa-solid fa-right-to-bracket" />
                    <span>{{ form.processing ? 'SIGNING IN...' : 'SIGN IN TO MY ACCOUNT' }}</span>
                </button>
            </div>
        </form>

        <!-- Bottom Registration Call to Action -->
        <div class="mt-8 pt-6 border-t border-slate-200 text-center">
            <p class="text-xs text-slate-600 font-medium">
                New to Butal Ship Hauz?
                <Link
                    :href="route('register')"
                    class="font-black text-orange-600 hover:text-orange-700 underline ml-1 transition-colors"
                >
                    Create an account to book venues &rarr;
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>