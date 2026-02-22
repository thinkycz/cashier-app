<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    company_name: user.company_name ?? '',
    company_id: user.company_id ?? '',
    vat_id: user.vat_id ?? '',
    first_name: user.first_name ?? '',
    last_name: user.last_name ?? '',
    email: user.email ?? '',
    phone_number: user.phone_number ?? '',
    street: user.street ?? '',
    city: user.city ?? '',
    zip: user.zip ?? '',
    country_code: user.country_code ?? '',
});
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-semibold text-slate-900">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-slate-600">
                Update your account and billing details.
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="space-y-5"
        >
            <div>
                <InputLabel for="company_name" value="Company Name" />

                <TextInput
                    id="company_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.company_name"
                    autocomplete="organization"
                />

                <InputError class="mt-2" :message="form.errors.company_name" />
            </div>

            <div>
                <InputLabel for="company_id" value="Company ID" />

                <TextInput
                    id="company_id"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.company_id"
                />

                <InputError class="mt-2" :message="form.errors.company_id" />
            </div>

            <div>
                <InputLabel for="vat_id" value="VAT ID" />

                <TextInput
                    id="vat_id"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.vat_id"
                />

                <InputError class="mt-2" :message="form.errors.vat_id" />
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <InputLabel for="first_name" value="First Name" />

                    <TextInput
                        id="first_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.first_name"
                        autocomplete="given-name"
                    />

                    <InputError class="mt-2" :message="form.errors.first_name" />
                </div>

                <div>
                    <InputLabel for="last_name" value="Last Name" />

                    <TextInput
                        id="last_name"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.last_name"
                        autocomplete="family-name"
                    />

                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="phone_number" value="Phone Number" />

                <TextInput
                    id="phone_number"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.phone_number"
                    autocomplete="tel"
                />

                <InputError class="mt-2" :message="form.errors.phone_number" />
            </div>

            <div>
                <InputLabel for="street" value="Street" />

                <TextInput
                    id="street"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.street"
                    autocomplete="address-line1"
                />

                <InputError class="mt-2" :message="form.errors.street" />
            </div>

            <div class="grid gap-5 sm:grid-cols-3">
                <div>
                    <InputLabel for="city" value="City" />

                    <TextInput
                        id="city"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.city"
                        autocomplete="address-level2"
                    />

                    <InputError class="mt-2" :message="form.errors.city" />
                </div>

                <div>
                    <InputLabel for="zip" value="ZIP" />

                    <TextInput
                        id="zip"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.zip"
                        autocomplete="postal-code"
                    />

                    <InputError class="mt-2" :message="form.errors.zip" />
                </div>

                <div>
                    <InputLabel for="country_code" value="Country Code" />

                    <TextInput
                        id="country_code"
                        type="text"
                        class="mt-1 block w-full"
                        v-model="form.country_code"
                        maxlength="2"
                        autocomplete="country"
                    />

                    <InputError class="mt-2" :message="form.errors.country_code" />
                </div>
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-slate-700">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm font-medium text-teal-700 underline underline-offset-2 hover:text-teal-800 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4 pt-1">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm font-medium text-emerald-700"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
