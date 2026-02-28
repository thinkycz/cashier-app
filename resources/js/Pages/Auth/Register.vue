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
    <GuestLayout>
        <Head title="Register" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Create your account</h1>
            <p class="mt-1 text-sm text-slate-600">Start using Cashier in a few seconds.</p>
        </div>

        <form @submit.prevent="submit">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="first_name" value="First Name" />

                    <TextInput
                        id="first_name"
                        type="text"
                        class="mt-1 block"
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
                        class="mt-1 block"
                        v-model="form.last_name"
                        required
                        autocomplete="family-name"
                    />

                    <InputError class="mt-2" :message="form.errors.last_name" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="company_id" value="Company ID" />

                <div class="mt-1 flex gap-2">
                    <TextInput
                        id="company_id"
                        type="text"
                        class="block flex-1"
                        v-model="form.company_id"
                        required
                        autocomplete="organization"
                    />

                    <SecondaryButton
                        :disabled="aresLoading || form.processing"
                        @click.prevent="fillFromAres"
                    >
                        Fill from ARES
                    </SecondaryButton>
                </div>

                <InputError class="mt-2" :message="form.errors.company_id" />
                <InputError class="mt-2" :message="aresError" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="company_name" value="Company Name" />

                    <TextInput
                        id="company_name"
                        type="text"
                        class="mt-1 block"
                        v-model="form.company_name"
                        autocomplete="organization"
                    />

                    <InputError class="mt-2" :message="form.errors.company_name" />
                </div>

                <div>
                    <InputLabel for="vat_id" value="VAT ID" />

                    <TextInput
                        id="vat_id"
                        type="text"
                        class="mt-1 block"
                        v-model="form.vat_id"
                    />

                    <InputError class="mt-2" :message="form.errors.vat_id" />
                </div>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <InputLabel for="subject_type" value="Subject Type" />

                    <SelectInput
                        id="subject_type"
                        class="mt-1 block"
                        v-model="form.subject_type"
                    >
                        <option value="fyzicka osoba">Fyzická osoba</option>
                        <option value="pravnicka osoba">Právnická osoba</option>
                    </SelectInput>

                    <InputError class="mt-2" :message="form.errors.subject_type" />
                </div>

                <div class="flex items-center mt-6">
                    <label class="flex items-center">
                        <Checkbox name="is_vat_payer" v-model:checked="form.is_vat_payer" />
                        <span class="ms-2 text-sm text-slate-600">VAT Payer</span>
                    </label>

                    <InputError class="mt-2" :message="form.errors.is_vat_payer" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="street" value="Street" />

                <TextInput
                    id="street"
                    type="text"
                    class="mt-1 block"
                    v-model="form.street"
                    autocomplete="address-line1"
                />

                <InputError class="mt-2" :message="form.errors.street" />
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <InputLabel for="city" value="City" />

                    <TextInput
                        id="city"
                        type="text"
                        class="mt-1 block"
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
                        class="mt-1 block"
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
                        class="mt-1 block"
                        v-model="form.country_code"
                        maxlength="2"
                        autocomplete="country"
                    />

                    <InputError class="mt-2" :message="form.errors.country_code" />
                </div>
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirm Password"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-slate-600 underline decoration-teal-200 underline-offset-4 hover:text-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                >
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
