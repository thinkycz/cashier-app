<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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
                        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-800">Basic Information</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                            <div>
                                <label for="name" class="mb-1.5 block text-xs font-medium text-slate-700">Product Name *</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700 transition-all duration-200 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20"
                                    :class="{ 'border-red-500': form.errors.name }"
                                    placeholder="Enter product name"
                                    required
                                />
                                <div v-if="form.errors.name" class="mt-1.5 text-xs font-medium text-red-600">{{ form.errors.name }}</div>
                            </div>

                            <div>
                                <label for="short_name" class="mb-1.5 block text-xs font-medium text-slate-700">Short Name</label>
                                <input
                                    id="short_name"
                                    v-model="form.short_name"
                                    type="text"
                                    class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700 transition-all duration-200 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20"
                                    :class="{ 'border-red-500': form.errors.short_name }"
                                    placeholder="Optional short name"
                                />
                                <div v-if="form.errors.short_name" class="mt-1.5 text-xs font-medium text-red-600">{{ form.errors.short_name }}</div>
                            </div>

                            <div>
                                <label for="ean" class="mb-1.5 block text-xs font-medium text-slate-700">EAN Code</label>
                                <input
                                    id="ean"
                                    v-model="form.ean"
                                    type="text"
                                    class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700 transition-all duration-200 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20"
                                    :class="{ 'border-red-500': form.errors.ean }"
                                    placeholder="Optional barcode"
                                />
                                <div v-if="form.errors.ean" class="mt-1.5 text-xs font-medium text-red-600">{{ form.errors.ean }}</div>
                            </div>

                            <div>
                                <span class="mb-1.5 block text-xs font-medium text-slate-700">Status</span>
                                <label class="inline-flex items-center gap-3 rounded-md border border-slate-200 px-3 py-2 text-sm text-slate-700">
                                    <input
                                        id="is_active"
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                    />
                                    <span>Active product</span>
                                </label>
                                <div v-if="form.errors.is_active" class="mt-1.5 text-xs font-medium text-red-600">{{ form.errors.is_active }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-800">Pricing</h3>
                        </div>
                        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                            <div>
                                <label for="price" class="mb-1.5 block text-xs font-medium text-slate-700">Price (Kc) *</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-500">Kc</span>
                                    <input
                                        id="price"
                                        v-model="form.price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="h-10 w-full rounded-md border border-slate-300 pl-9 pr-3 text-sm text-slate-700 transition-all duration-200 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20"
                                        :class="{ 'border-red-500': form.errors.price }"
                                        placeholder="0.00"
                                        required
                                    />
                                </div>
                                <div v-if="form.errors.price" class="mt-1.5 text-xs font-medium text-red-600">{{ form.errors.price }}</div>
                            </div>

                            <div>
                                <label for="vat_rate" class="mb-1.5 block text-xs font-medium text-slate-700">VAT Rate *</label>
                                <select
                                    id="vat_rate"
                                    v-model="form.vat_rate"
                                    class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700 transition-all duration-200 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20"
                                    :class="{ 'border-red-500': form.errors.vat_rate }"
                                    required
                                >
                                    <option :value="0">0%</option>
                                    <option :value="10">10%</option>
                                    <option :value="15">15%</option>
                                    <option :value="21">21%</option>
                                </select>
                                <div v-if="form.errors.vat_rate" class="mt-1.5 text-xs font-medium text-red-600">{{ form.errors.vat_rate }}</div>
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
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex min-w-36 items-center justify-center rounded-md border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-teal-700 disabled:cursor-not-allowed disabled:bg-slate-400"
                        >
                            {{ submitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
