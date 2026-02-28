<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
    mode: {
        type: String,
        required: true,
    },
    customer: {
        type: Object,
        default: null,
    },
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    company_name: isEdit.value ? props.customer?.company_name ?? '' : '',
    company_id: isEdit.value ? props.customer?.company_id ?? '' : '',
    vat_id: isEdit.value ? props.customer?.vat_id ?? '' : '',
    first_name: isEdit.value ? props.customer?.first_name ?? '' : '',
    last_name: isEdit.value ? props.customer?.last_name ?? '' : '',
    email: isEdit.value ? props.customer?.email ?? '' : '',
    phone_number: isEdit.value ? props.customer?.phone_number ?? '' : '',
    street: isEdit.value ? props.customer?.street ?? '' : '',
    city: isEdit.value ? props.customer?.city ?? '' : '',
    zip: isEdit.value ? props.customer?.zip ?? '' : '',
    country_code: isEdit.value ? props.customer?.country_code ?? 'CZ' : 'CZ',
});

const pageTitle = computed(() => (isEdit.value ? `${t('customers.edit_title')} - ${props.customer?.company_name ?? ''}` : t('customers.create_title')));
const formTitle = computed(() => (isEdit.value ? t('customers.edit_title') : t('customers.create_title')));
const formSubtitle = computed(() =>
    isEdit.value
        ? t('customers.edit_subtitle')
        : t('customers.create_subtitle'),
);
const submitLabel = computed(() => {
    if (form.processing) {
        return isEdit.value ? t('customers.saving') : t('customers.creating');
    }

    return isEdit.value ? t('customers.save_changes') : t('customers.create');
});

const normalizeCountryCode = () => {
    form.country_code = (form.country_code || '').toUpperCase().slice(0, 2);
};

const submit = () => {
    normalizeCountryCode();

    if (isEdit.value) {
        form.put(route('customers.update', props.customer.id));
        return;
    }

    form.post(route('customers.store'));
};
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-2xl font-semibold text-slate-900">{{ formTitle }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ formSubtitle }}</p>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        :href="route('customers.index')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-all duration-200 hover:-translate-y-px hover:bg-slate-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ $t('customers.back_to_customers') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-800">{{ $t('customers.company_details') }}</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                            <div>
                                <InputLabel for="company_name" :value="$t('customers.company_name')" />
                                <TextInput
                                    id="company_name"
                                    v-model="form.company_name"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.company_name }"
                                    :placeholder="$t('customers.enter_company_name')"
                                />
                                <InputError class="mt-1.5" :message="form.errors.company_name" />
                            </div>

                            <div>
                                <InputLabel for="company_id" :value="$t('customers.company_id')" />
                                <TextInput
                                    id="company_id"
                                    v-model="form.company_id"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.company_id }"
                                    :placeholder="$t('customers.company_registration_id')"
                                />
                                <InputError class="mt-1.5" :message="form.errors.company_id" />
                            </div>

                            <div>
                                <InputLabel for="vat_id" :value="$t('customers.vat_id')" />
                                <TextInput
                                    id="vat_id"
                                    v-model="form.vat_id"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.vat_id }"
                                    :placeholder="$t('customers.optional_vat_id')"
                                />
                                <InputError class="mt-1.5" :message="form.errors.vat_id" />
                            </div>

                            <div>
                                <InputLabel for="country_code" :value="$t('customers.country_code')" />
                                <TextInput
                                    id="country_code"
                                    v-model="form.country_code"
                                    type="text"
                                    maxlength="2"
                                    class="mt-1 block uppercase"
                                    :class="{ 'border-red-500': form.errors.country_code }"
                                    placeholder="CZ"
                                    @blur="normalizeCountryCode"
                                />
                                <InputError class="mt-1.5" :message="form.errors.country_code" />
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-800">{{ $t('customers.contact_and_address') }}</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                            <div>
                                <InputLabel for="first_name" :value="$t('customers.first_name')" />
                                <TextInput
                                    id="first_name"
                                    v-model="form.first_name"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.first_name }"
                                    :placeholder="$t('customers.optional_first_name')"
                                />
                                <InputError class="mt-1.5" :message="form.errors.first_name" />
                            </div>

                            <div>
                                <InputLabel for="last_name" :value="$t('customers.last_name')" />
                                <TextInput
                                    id="last_name"
                                    v-model="form.last_name"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.last_name }"
                                    :placeholder="$t('customers.optional_last_name')"
                                />
                                <InputError class="mt-1.5" :message="form.errors.last_name" />
                            </div>

                            <div>
                                <InputLabel for="email" :value="$t('customers.email')" />
                                <TextInput
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.email }"
                                    placeholder="contact@company.com"
                                />
                                <InputError class="mt-1.5" :message="form.errors.email" />
                            </div>

                            <div>
                                <InputLabel for="phone_number" :value="$t('customers.phone')" />
                                <TextInput
                                    id="phone_number"
                                    v-model="form.phone_number"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.phone_number }"
                                    :placeholder="$t('customers.optional_phone_number')"
                                />
                                <InputError class="mt-1.5" :message="form.errors.phone_number" />
                            </div>

                            <div class="md:col-span-2">
                                <InputLabel for="street" :value="$t('customers.street')" />
                                <TextInput
                                    id="street"
                                    v-model="form.street"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.street }"
                                    :placeholder="$t('customers.street_and_number')"
                                />
                                <InputError class="mt-1.5" :message="form.errors.street" />
                            </div>

                            <div>
                                <InputLabel for="city" :value="$t('customers.city')" />
                                <TextInput
                                    id="city"
                                    v-model="form.city"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.city }"
                                    :placeholder="$t('customers.city')"
                                />
                                <InputError class="mt-1.5" :message="form.errors.city" />
                            </div>

                            <div>
                                <InputLabel for="zip" :value="$t('customers.zip')" />
                                <TextInput
                                    id="zip"
                                    v-model="form.zip"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.zip }"
                                    :placeholder="$t('customers.zip_code')"
                                />
                                <InputError class="mt-1.5" :message="form.errors.zip" />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:justify-end">
                        <Link
                            :href="route('customers.index')"
                            class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition-all duration-200 hover:bg-slate-200"
                        >
                            {{ $t('customers.cancel') }}
                        </Link>
                        <PrimaryButton
                            :disabled="form.processing"
                            class="min-w-36"
                        >
                            {{ submitLabel }}
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
