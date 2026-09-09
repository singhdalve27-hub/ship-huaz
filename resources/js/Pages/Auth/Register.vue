<script setup>
import { ref } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
    first_name: "",
    middle_name: "",
    last_name: "",
    birth_date: "",
    phone: "",
    address: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const submit = () => {
    form.post(route("register"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <GuestLayout maxWidth="2xl">
        <Head title="Create Account | Butal Ship Hauz" />

        <!-- Header Box (High-Contrast & Eye-Friendly) -->
        <div class="mb-8 p-5 sm:p-6 rounded-2xl bg-gradient-to-r from-orange-50 via-amber-50 to-sky-50 border border-orange-200/80 text-center shadow-sm">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-2xl bg-orange-500 text-white mb-3 shadow-md shadow-orange-500/20">
                <font-awesome-icon icon="fa-solid fa-user-plus" class="text-xl" />
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
                Create Your Account
            </h2>
            <p class="text-sm sm:text-base text-slate-800 font-semibold mt-2 leading-relaxed max-w-md mx-auto">
                Join Butal Ship Hauz to reserve our signature decks, schedule ocular tours, and receive instant booking confirmations.
            </p>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            
            <!-- ── Section 1: Client Personal Details ── -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200 text-slate-900 font-bold text-xs font-mono uppercase tracking-wider">
                    <font-awesome-icon icon="fa-solid fa-id-card" class="text-orange-500 text-sm" />
                    <span>Personal & Contact Information</span>
                </div>

                <!-- Names Grid (3 cols on desktop) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <InputLabel for="first_name" value="First Name" class="text-slate-800 font-bold text-xs mb-1" />
                        <TextInput
                            id="first_name"
                            type="text"
                            class="block w-full bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl px-3.5 py-2.5 text-slate-900 font-medium text-sm transition-all placeholder:text-slate-400"
                            v-model="form.first_name"
                            required
                            autocomplete="first_name"
                            placeholder="e.g. Maria"
                        />
                        <InputError class="mt-1" :message="form.errors.first_name" />
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <InputLabel for="middle_name" value="Middle Name" class="text-slate-800 font-bold text-xs" />
                            <span class="text-[10px] text-slate-500 font-mono font-semibold">Optional</span>
                        </div>
                        <TextInput
                            id="middle_name"
                            type="text"
                            class="block w-full bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl px-3.5 py-2.5 text-slate-900 font-medium text-sm transition-all placeholder:text-slate-400"
                            v-model="form.middle_name"
                            autocomplete="middle_name"
                            placeholder="e.g. Santos"
                        />
                        <InputError class="mt-1" :message="form.errors.middle_name" />
                    </div>

                    <div>
                        <InputLabel for="last_name" value="Last Name" class="text-slate-800 font-bold text-xs mb-1" />
                        <TextInput
                            id="last_name"
                            type="text"
                            class="block w-full bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl px-3.5 py-2.5 text-slate-900 font-medium text-sm transition-all placeholder:text-slate-400"
                            v-model="form.last_name"
                            required
                            autocomplete="last_name"
                            placeholder="e.g. Dela Cruz"
                        />
                        <InputError class="mt-1" :message="form.errors.last_name" />
                    </div>
                </div>

                <!-- Demographics (Birthdate & Phone) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="birth_date" value="Date of Birth" class="text-slate-800 font-bold text-xs mb-1" />
                        <TextInput
                            id="birth_date"
                            type="date"
                            class="block w-full bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl px-3.5 py-2 text-slate-900 font-medium text-sm transition-all cursor-pointer"
                            v-model="form.birth_date"
                            required
                            autocomplete="birth_date"
                        />
                        <InputError class="mt-1" :message="form.errors.birth_date" />
                    </div>

                    <div>
                        <InputLabel for="phone" value="Mobile Phone (Philippines)" class="text-slate-800 font-bold text-xs mb-1" />
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center font-mono text-xs font-bold text-slate-600 pointer-events-none">
                                +63
                            </span>
                            <TextInput
                                id="phone"
                                type="tel"
                                class="block w-full pl-12 pr-3.5 py-2.5 bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl text-slate-900 font-medium text-sm transition-all placeholder:text-slate-400"
                                v-model="form.phone"
                                required
                                autocomplete="phone"
                                placeholder="9123456789"
                                pattern="^9\d{9}$"
                                maxlength="10"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.phone" />
                    </div>
                </div>

                <!-- Address -->
                <div>
                    <InputLabel for="address" value="Complete Address" class="text-slate-800 font-bold text-xs mb-1" />
                    <TextInput
                        id="address"
                        type="text"
                        class="block w-full bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl px-3.5 py-2.5 text-slate-900 font-medium text-sm transition-all placeholder:text-slate-400"
                        v-model="form.address"
                        required
                        autocomplete="address"
                        placeholder="e.g. San Roque, Talibon, Bohol"
                    />
                    <InputError class="mt-1" :message="form.errors.address" />
                </div>
            </div>

            <!-- ── Section 2: Account Security & Credentials ── -->
            <div class="space-y-4 pt-2">
                <div class="flex items-center gap-2 pb-2 border-b border-slate-200 text-slate-900 font-bold text-xs font-mono uppercase tracking-wider">
                    <font-awesome-icon icon="fa-solid fa-lock" class="text-orange-500 text-sm" />
                    <span>Account Security & Login Credentials</span>
                </div>

                <!-- Email -->
                <div>
                    <InputLabel for="email" value="Email Address" class="text-slate-800 font-bold text-xs mb-1" />
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                            <font-awesome-icon icon="fa-solid fa-envelope" />
                        </div>
                        <TextInput
                            id="email"
                            type="email"
                            class="block w-full pl-10 pr-4 py-2.5 bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl text-slate-900 font-medium text-sm transition-all placeholder:text-slate-400"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="youremail@example.com"
                        />
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium mt-1 block">Your booking confirmations and payment receipts will be sent here.</span>
                    <InputError class="mt-1" :message="form.errors.email" />
                </div>

                <!-- Password and Confirm Password Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="password" value="Password" class="text-slate-800 font-bold text-xs mb-1" />
                        <div class="relative">
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full pr-10 py-2.5 bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl text-slate-900 font-medium text-sm transition-all placeholder:text-slate-400"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-800 transition-colors"
                                tabindex="-1"
                                aria-label="Toggle password visibility"
                            >
                                <font-awesome-icon :icon="showPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs" />
                            </button>
                        </div>
                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <div>
                        <InputLabel for="password_confirmation" value="Confirm Password" class="text-slate-800 font-bold text-xs mb-1" />
                        <div class="relative">
                            <TextInput
                                id="password_confirmation"
                                :type="showConfirmPassword ? 'text' : 'password'"
                                class="block w-full pr-10 py-2.5 bg-white border border-slate-300 focus:border-orange-500 focus:ring-2 focus:ring-orange-500/20 shadow-sm rounded-xl text-slate-900 font-medium text-sm transition-all placeholder:text-slate-400"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                @click="showConfirmPassword = !showConfirmPassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-500 hover:text-slate-800 transition-colors"
                                tabindex="-1"
                                aria-label="Toggle confirm password visibility"
                            >
                                <font-awesome-icon :icon="showConfirmPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'" class="text-xs" />
                            </button>
                        </div>
                        <InputError class="mt-1" :message="form.errors.password_confirmation" />
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4">
                <button
                    type="submit"
                    class="w-full inline-flex justify-center items-center gap-2 px-6 py-3.5 bg-gradient-to-r from-orange-500 to-amber-500 hover:from-orange-600 hover:to-amber-600 border border-transparent rounded-xl font-black text-white text-sm tracking-wide shadow-md shadow-orange-500/25 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 hover:-translate-y-0.5 active:translate-y-0"
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <font-awesome-icon icon="fa-solid fa-check" />
                    <span>{{ form.processing ? 'CREATING ACCOUNT...' : 'REGISTER & START BOOKING' }}</span>
                </button>
            </div>
        </form>

        <!-- Bottom Link to Login -->
        <div class="mt-8 pt-6 border-t border-slate-200 text-center">
            <p class="text-xs text-slate-600 font-medium">
                Already registered with Butal Ship Hauz?
                <Link
                    :href="route('login')"
                    class="font-black text-orange-600 hover:text-orange-700 underline ml-1 transition-colors"
                >
                    Sign in to your account &rarr;
                </Link>
            </p>
        </div>
    </GuestLayout>
</template>