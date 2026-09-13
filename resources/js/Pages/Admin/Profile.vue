<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";

const user = usePage().props.auth.user;

const user_information = useForm({
    first_name: user.user_info?.first_name,
    middle_name: user.user_info?.middle_name ?? "",
    last_name: user.user_info?.last_name,
    birth_date: user.user_info?.birth_date,
    phone: user.user_info?.phone,
    address: user.user_info?.address,
});

const user_credentials = useForm({
    email: user.email,
    current_password: "",
    password: "",
    password_confirmation: "",
});

const submitInformation = () => {
    user_information.put(route("update-information"));
};

const submitCredentials = () => {
    user_credentials.put(route("update-credentials"), {
        onSuccess: () =>
            user_credentials.reset("current_password", "password", "password_confirmation"),
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AdminLayout>
        <div class="flex flex-col sm:flex-row gap-3 mx-auto">
            <div class="w-full">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h1 class="sm:text-xl font-bold">
                            <font-awesome-icon icon="fa-solid fa-circle-info" />
                            Information
                        </h1>
                        <form @submit.prevent="submitInformation">
                            <div class="mt-3">
                                <InputLabel
                                    for="first_name"
                                    value="First Name"
                                />
                                <TextInput
                                    id="first_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="user_information.first_name"
                                    required
                                    autocomplete="first_name"
                                    placeholder="e.g. Alex"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="
                                        user_information.errors.first_name
                                    "
                                />
                            </div>
                            <div class="mt-3">
                                <InputLabel
                                    for="middle_name"
                                    value="Middle Name"
                                />
                                <TextInput
                                    id="middle_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="user_information.middle_name"
                                    autocomplete="middle_name"
                                    placeholder="e.g. Alexey"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="
                                        user_information.errors.middle_name
                                    "
                                />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="last_name" value="Last Name" />
                                <TextInput
                                    id="last_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="user_information.last_name"
                                    required
                                    autocomplete="last_name"
                                    placeholder="e.g. Doe"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="user_information.errors.last_name"
                                />
                            </div>
                            <div class="mt-3">
                                <InputLabel
                                    for="birth_date"
                                    value="Birth Date"
                                />
                                <TextInput
                                    id="birth_date"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="user_information.birth_date"
                                    required
                                    autocomplete="birth_date"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="
                                        user_information.errors.birth_date
                                    "
                                />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="phone" value="Phone" />
                                <TextInput
                                    id="phone"
                                    type="tel"
                                    class="mt-1 block w-full"
                                    v-model="user_information.phone"
                                    required
                                    autocomplete="phone"
                                    placeholder="e.g. 9123456789"
                                    pattern="^9\d{9}$"
                                    maxlength="10"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="user_information.errors.phone"
                                />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="address" value="Address" />
                                <TextInput
                                    id="address"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="user_information.address"
                                    required
                                    autocomplete="address"
                                    placeholder="e.g. Pob. Talibon, Bohol"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="user_information.errors.address"
                                />
                            </div>
                            <div class="mt-3 flex items-center justify-center">
                                <PrimaryButton
                                    :class="{
                                        'opacity-25':
                                            user_information.processing,
                                    }"
                                    :disabled="
                                        user_information.processing ||
                                        user_credentials.processing
                                    "
                                >
                                    <div
                                        class="text-sm"
                                        v-if="user_information.processing"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-spinner"
                                            spin
                                        />
                                    </div>
                                    <font-awesome-icon
                                        class="mx-1"
                                        icon="fa-solid fa-paper-plane"
                                    />
                                    Save Information
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>

                <div
                    class="overflow-hidden bg-white mt-4 shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        <h1 class="sm:text-xl font-bold">
                            <font-awesome-icon icon="fa-solid fa-id-card" />
                            Credentials
                        </h1>
                        <form @submit.prevent="submitCredentials">
                            <div class="mt-3">
                                <InputLabel for="email" value="Email" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="user_credentials.email"
                                    required
                                    autocomplete="username"
                                    placeholder="e.g. alex@gmail.com"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="user_credentials.errors.email"
                                />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="current_password" value="Current Password (Required to change password)" />
                                <TextInput
                                    id="current_password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="user_credentials.current_password"
                                    autocomplete="current-password"
                                    placeholder="Enter your current password"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="user_credentials.errors.current_password"
                                />
                            </div>
                            <div class="mt-3">
                                <InputLabel for="password" value="New Password (Leave blank to keep current)" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="user_credentials.password"
                                    autocomplete="new-password"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="user_credentials.errors.password"
                                />
                            </div>
                            <div class="mt-3">
                                <InputLabel
                                    for="password_confirmation"
                                    value="Confirm Password"
                                />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    class="mt-1 block w-full"
                                    v-model="
                                        user_credentials.password_confirmation
                                    "
                                    autocomplete="new-password"
                                />
                                <InputError
                                    class="mt-2"
                                    :message="
                                        user_credentials.errors
                                            .password_confirmation
                                    "
                                />
                            </div>
                            <div class="mt-3 flex items-center justify-center">
                                <PrimaryButton
                                    :class="{
                                        'opacity-25':
                                            user_credentials.processing,
                                    }"
                                    :disabled="
                                        user_credentials.processing ||
                                        user_information.processing
                                    "
                                >
                                    <div
                                        class="text-sm"
                                        v-if="user_credentials.processing"
                                    >
                                        <font-awesome-icon
                                            icon="fa-solid fa-spinner"
                                            spin
                                        />
                                    </div>
                                    <font-awesome-icon
                                        class="me-1"
                                        icon="fa-solid fa-paper-plane"
                                    />
                                    Save Credentials
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
