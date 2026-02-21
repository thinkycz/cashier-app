<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    product: Object,
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('cs-CZ', {
        style: 'currency',
        currency: 'CZK',
    }).format(price);
};

const priceExcludingVat = Number(props.product.price) / (1 + Number(props.product.vat_rate) / 100);
const vatAmount = Number(props.product.price) - priceExcludingVat;
</script>

<template>
    <Head :title="`Product - ${product.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-2xl font-semibold text-slate-900">{{ product.name }}</h2>
                    <p class="mt-1 text-sm text-slate-500">Review product details, status, and VAT pricing breakdown.</p>
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
                    <Link
                        :href="route('products.edit', product.id)"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-transparent bg-sky-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-px hover:bg-sky-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit Product
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="grid gap-4 px-6 py-5 md:grid-cols-3 md:items-center">
                        <div class="md:col-span-2">
                            <h1 class="text-xl font-semibold text-slate-900">{{ product.name }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ product.short_name || 'No short name provided.' }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span
                                    :class="product.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ product.is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                    EAN: {{ product.ean || 'N/A' }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-right">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Price (incl. VAT)</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatPrice(product.price) }}</p>
                            <p class="mt-1 text-xs text-slate-500">VAT rate: {{ product.vat_rate }}%</p>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                            <h2 class="text-base font-semibold text-slate-800">Product Details</h2>
                        </div>
                        <dl class="space-y-4 px-6 py-5 text-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">Product Name</dt>
                                <dd class="text-right font-medium text-slate-900">{{ product.name }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">Short Name</dt>
                                <dd class="text-right font-medium text-slate-900">{{ product.short_name || 'Not specified' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">EAN</dt>
                                <dd class="font-mono text-right text-xs text-slate-900">{{ product.ean || 'Not specified' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Status</dt>
                                <dd class="text-right font-medium text-slate-900">{{ product.is_active ? 'Active' : 'Inactive' }}</dd>
                            </div>
                        </dl>
                    </article>

                    <article class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                            <h2 class="text-base font-semibold text-slate-800">Pricing Breakdown</h2>
                        </div>
                        <dl class="space-y-4 px-6 py-5 text-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">Price (incl. VAT)</dt>
                                <dd class="text-right text-base font-semibold text-slate-900">{{ formatPrice(product.price) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">VAT Rate</dt>
                                <dd class="text-right font-medium text-slate-900">{{ product.vat_rate }}%</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">Price (excl. VAT)</dt>
                                <dd class="text-right font-medium text-slate-900">{{ formatPrice(priceExcludingVat) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">VAT Amount</dt>
                                <dd class="text-right font-medium text-slate-900">{{ formatPrice(vatAmount) }}</dd>
                            </div>
                        </dl>
                    </article>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
