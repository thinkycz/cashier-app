<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const form = useForm({
    first_name: '',
    last_name: '',
    company_id: '',
    company_name: '',
    vat_id: '',
    is_vat_payer: false,
    subject_type: 'fyzicka osoba',
    street: '',
    city: '',
    zip: '',
    country_code: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const aresLoading = ref(false);
const aresError = ref('');

const fillFromAres = async () => {
    aresError.value = '';

    const ico = String(form.company_id ?? '').replace(/\D/g, '');
    if (ico.length !== 8) {
        aresError.value = 'Company ID must be 8 digits.';
        return;
    }

    aresLoading.value = true;

    try {
        const response = await window.axios.get(route('ares.company'), {
            params: { company_id: ico },
        });

        const data = response?.data ?? {};

        form.company_id = data.company_id ?? form.company_id;
        form.company_name = data.company_name ?? '';
        form.vat_id = data.vat_id ?? '';
        form.is_vat_payer = data.is_vat_payer ?? false;
        form.subject_type = data.subject_type ?? 'fyzicka osoba';
        form.street = data.street ?? '';
        form.city = data.city ?? '';
        form.zip = data.zip ?? '';
        form.country_code = data.country_code ?? '';
    } catch (error) {
        const message =
            error?.response?.data?.errors?.company_id?.[0] ??
            error?.response?.data?.message ??
            'Company lookup failed.';

        aresError.value = message;
    } finally {
        aresLoading.value = false;
    }
};

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout maxWidth="4xl">
        <Head title="Register" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create your account</h1>
            <p class="mt-1 text-sm text-slate-600">Start using Cashier in a few seconds.</p>
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-12 gap-y-10">
                <!-- Left Column -->
                <div class="space-y-10">
                    <!-- Personal Details -->
                    <div>
                        <div class="border-b border-slate-200 pb-3 mb-5">
                            <h2 class="text-base font-semibold leading-7 text-slate-900">Personal Details</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Your basic information.</p>
                        </div>
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="first_name" value="First Name" />
                                <TextInput
                                    id="first_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.first_name"
                                    required
                                    autofocus
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
                                    required
                                    autocomplete="family-name"
                                />
                                <InputError class="mt-2" :message="form.errors.last_name" />
                            </div>
                        </div>
                    </div>

                    <!-- Account Details -->
                    <div>
                         <div class="border-b border-slate-200 pb-3 mb-5">
                            <h2 class="text-base font-semibold leading-7 text-slate-900">Account Security</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Set up your login credentials.</p>
                        </div>
                        
                        <div class="space-y-6">
                            <div>
                                <InputLabel for="email" value="Email Address" />
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

                            <div class="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <InputLabel for="password" value="Password" />
                                    <TextInput
                                        id="password"
                                        type="password"
                                        class="mt-1 block w-full"
                                        v-model="form.password"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <InputError class="mt-2" :message="form.errors.password" />
                                </div>

                                <div>
                                    <InputLabel
                                        for="password_confirmation"
                                        value="Confirm Password"
                                    />
                                    <TextInput
                                        id="password_confirmation"
                                        type="password"
                                        class="mt-1 block w-full"
                                        v-model="form.password_confirmation"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <InputError
                                        class="mt-2"
                                        :message="form.errors.password_confirmation"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-10">
                    <!-- Company Details -->
                    <div>
                         <div class="border-b border-slate-200 pb-3 mb-5">
                            <h2 class="text-base font-semibold leading-7 text-slate-900">Company Details</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Information about your business or freelancing activity.</p>
                        </div>
                        
                        <div class="space-y-6">
                            <div>
                                <InputLabel for="company_id" value="Company ID (IČO)" />
                                <div class="mt-1 flex gap-2">
                                    <TextInput
                                        id="company_id"
                                        type="text"
                                        class="block w-full"
                                        v-model="form.company_id"
                                        required
                                        autocomplete="organization"
                                    />
                                    <SecondaryButton
                                        class="shrink-0"
                                        :disabled="aresLoading || form.processing"
                                        @click.prevent="fillFromAres"
                                    >
                                        <svg v-if="aresLoading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-slate-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Fill from ARES
                                    </SecondaryButton>
                                </div>
                                <InputError class="mt-2" :message="form.errors.company_id" />
                                <InputError class="mt-2" :message="aresError" />
                            </div>

                            <div class="grid gap-6 sm:grid-cols-2">
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
                                    <InputLabel for="vat_id" value="VAT ID (DIČ)" />
                                    <TextInput
                                        id="vat_id"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.vat_id"
                                    />
                                    <InputError class="mt-2" :message="form.errors.vat_id" />
                                </div>
                            </div>

                            <div class="grid gap-6 sm:grid-cols-2 items-start">
                                <div>
                                    <InputLabel for="subject_type" value="Subject Type" />
                                    <SelectInput
                                        id="subject_type"
                                        class="mt-1 block w-full"
                                        v-model="form.subject_type"
                                    >
                                        <option value="fyzicka osoba">Fyzická osoba (Natural Person)</option>
                                        <option value="pravnicka osoba">Právnická osoba (Legal Entity)</option>
                                    </SelectInput>
                                    <InputError class="mt-2" :message="form.errors.subject_type" />
                                </div>

                                <div class="sm:mt-8">
                                    <label class="flex items-center gap-3 bg-slate-50 p-3 rounded-lg border border-slate-200 cursor-pointer hover:bg-slate-100 transition-colors">
                                        <Checkbox name="is_vat_payer" v-model:checked="form.is_vat_payer" />
                                        <span class="text-sm font-medium text-slate-700">I am a registered VAT Payer</span>
                                    </label>
                                    <InputError class="mt-2" :message="form.errors.is_vat_payer" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <div class="border-b border-slate-200 pb-3 mb-5">
                            <h2 class="text-base font-semibold leading-7 text-slate-900">Registered Address</h2>
                            <p class="mt-1 text-sm leading-6 text-slate-500">Where is your business officially located?</p>
                        </div>
                        
                        <div class="space-y-6">
                            <div>
                                <InputLabel for="street" value="Street & Number" />
                                <TextInput
                                    id="street"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.street"
                                    autocomplete="address-line1"
                                />
                                <InputError class="mt-2" :message="form.errors.street" />
                            </div>

                            <div class="grid gap-6 sm:grid-cols-3">
                                <div class="sm:col-span-2">
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
                                    <InputLabel for="zip" value="ZIP / Postal Code" />
                                    <TextInput
                                        id="zip"
                                        type="text"
                                        class="mt-1 block w-full"
                                        v-model="form.zip"
                                        autocomplete="postal-code"
                                    />
                                    <InputError class="mt-2" :message="form.errors.zip" />
                                </div>
                            </div>
                            
                            <div>
                                <InputLabel for="country_code" value="Country Code" />
                                <TextInput
                                    id="country_code"
                                    type="text"
                                    class="mt-1 block w-full sm:max-w-xs"
                                    v-model="form.country_code"
                                    maxlength="2"
                                    placeholder="e.g. CZ"
                                    autocomplete="country"
                                />
                                <InputError class="mt-2" :message="form.errors.country_code" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="pt-6 border-t border-slate-200 flex items-center justify-between">
                <Link
                    :href="route('login')"
                    class="text-sm font-medium text-slate-600 hover:text-teal-600 transition-colors"
                >
                    &larr; Back to login
                </Link>

                <PrimaryButton
                    :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                    :disabled="form.processing"
                >
                    <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Complete Registration
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
