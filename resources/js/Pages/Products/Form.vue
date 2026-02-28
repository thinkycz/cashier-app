<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const isVatPayer = computed(() => usePage().props.auth.user.is_vat_payer);

const props = defineProps({
    mode: {
        type: String,
        required: true,
    },
    product: {
        type: Object,
        default: null,
    },
});

const isEdit = computed(() => props.mode === 'edit');

const form = useForm({
    name: isEdit.value ? props.product?.name ?? '' : '',
    short_name: isEdit.value ? props.product?.short_name ?? '' : '',
    ean: isEdit.value ? props.product?.ean ?? '' : '',
    vat_rate: isEdit.value ? props.product?.vat_rate ?? 21 : 21,
    price: isEdit.value ? props.product?.price ?? '' : '',
    is_active: isEdit.value ? props.product?.is_active ?? true : true,
});

const pageTitle = computed(() => (isEdit.value ? `Edit Product - ${props.product?.name ?? ''}` : 'Create Product'));
const formTitle = computed(() => (isEdit.value ? 'Edit Product' : 'Create Product'));
const formSubtitle = computed(() =>
    isEdit.value
        ? 'Update product details and pricing for this item.'
        : 'Add a new product to your catalog with pricing and VAT settings.',
);
const submitLabel = computed(() => {
    if (form.processing) {
        return isEdit.value ? 'Saving...' : 'Creating...';
    }

    return isEdit.value ? 'Save Changes' : 'Create Product';
});

const submit = () => {
    if (!isVatPayer.value) {
        form.vat_rate = 0;
    }

    if (isEdit.value) {
        form.put(route('products.update', props.product.id));
        return;
    }

    form.post(route('products.store'));
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
                        :href="route('products.index')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-all duration-200 hover:-translate-y-px hover:bg-slate-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Products
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-800">Basic Information</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                            <div>
                                <InputLabel for="name" value="Product Name *" />
                                <TextInput
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.name }"
                                    placeholder="Enter product name"
                                    required
                                />
                                <InputError class="mt-1.5" :message="form.errors.name" />
                            </div>

                            <div>
                                <InputLabel for="short_name" value="Short Name" />
                                <TextInput
                                    id="short_name"
                                    v-model="form.short_name"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.short_name }"
                                    placeholder="Optional short name"
                                />
                                <InputError class="mt-1.5" :message="form.errors.short_name" />
                            </div>

                            <div>
                                <InputLabel for="ean" value="EAN Code" />
                                <TextInput
                                    id="ean"
                                    v-model="form.ean"
                                    type="text"
                                    class="mt-1 block"
                                    :class="{ 'border-red-500': form.errors.ean }"
                                    placeholder="Optional barcode"
                                />
                                <InputError class="mt-1.5" :message="form.errors.ean" />
                            </div>

                            <div class="col-span-full">
                                <label class="inline-flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                    <Checkbox
                                        id="is_active"
                                        v-model:checked="form.is_active"
                                        class="text-teal-600 focus:ring-teal-500"
                                    />
                                    <span>Active product</span>
                                </label>
                                <InputError class="mt-1.5" :message="form.errors.is_active" />
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-800">Pricing</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                            <div :class="isVatPayer ? '' : 'md:col-span-2'">
                                <InputLabel for="price" value="Price (Kc) *" />
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">Kc</span>
                                    <TextInput
                                        id="price"
                                        v-model="form.price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="mt-1 block pl-9"
                                        :class="{ 'border-red-500': form.errors.price }"
                                        placeholder="0.00"
                                        required
                                    />
                                </div>
                                <InputError class="mt-1.5" :message="form.errors.price" />
                            </div>

                            <div v-if="isVatPayer">
                                <InputLabel for="vat_rate" value="VAT Rate *" />
                                <SelectInput
                                    id="vat_rate"
                                    v-model="form.vat_rate"
                                    class="mt-1 block"
                                    :class="{ 'border-rose-500': form.errors.vat_rate }"
                                    required
                                >
                                    <option :value="0">0%</option>
                                    <option :value="10">10%</option>
                                    <option :value="15">15%</option>
                                    <option :value="21">21%</option>
                                </SelectInput>
                                <InputError class="mt-1.5" :message="form.errors.vat_rate" />
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 pt-1 sm:flex-row sm:justify-end">
                        <Link
                            :href="route('products.index')"
                            class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 transition-all duration-200 hover:bg-slate-200"
                        >
                            Cancel
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
