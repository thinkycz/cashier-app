<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const isVatPayer = computed(() => usePage().props.auth.user.is_vat_payer);

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
                    <p class="mt-1 text-sm text-slate-600">{{ $t('products.review_details') }}</p>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        :href="route('products.index')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-all duration-200 hover:-translate-y-px hover:bg-slate-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ $t('products.back_to_products') }}
                    </Link>
                    <Link
                        :href="route('products.edit', product.id)"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-px hover:bg-teal-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ $t('products.edit') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="grid gap-4 px-6 py-5 md:grid-cols-3 md:items-center">
                        <div class="md:col-span-2">
                            <h1 class="text-xl font-semibold text-slate-900">{{ product.name }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ product.short_name || $t('products.no_short_name_provided') }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span
                                    :class="product.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                    {{ product.is_active ? $t('products.active') : $t('products.inactive') }}
                                </span>
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                    EAN: {{ product.ean || 'N/A' }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-lg border border-teal-200/70 bg-gradient-to-br from-teal-50/70 to-cyan-50/60 p-4 text-right">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ $t('products.price') }}{{ isVatPayer ? ` (${$t('products.incl_vat')})` : '' }}</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatPrice(product.price) }}</p>
                            <p v-if="isVatPayer" class="mt-1 text-xs text-slate-500">{{ $t('products.vat_rate') }} {{ product.vat_rate }}%</p>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h2 class="text-base font-semibold text-slate-800">{{ $t('products.product_details') }}</h2>
                        </div>
                        <dl class="space-y-4 px-6 py-5 text-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('products.product_name_req').replace(' *', '') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ product.name }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('products.short_name') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ product.short_name || $t('products.not_specified') }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('products.ean') }}</dt>
                                <dd>
                                    <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 font-mono text-xs text-slate-700">
                                        {{ product.ean || $t('products.not_specified') }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">{{ $t('products.status') }}</dt>
                                <dd>
                                    <span
                                        :class="product.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ product.is_active ? $t('products.active') : $t('products.inactive') }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </article>

                    <article v-if="isVatPayer" class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h2 class="text-base font-semibold text-slate-800">{{ $t('products.pricing_breakdown') }}</h2>
                        </div>
                        <dl class="space-y-4 px-6 py-5 text-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('products.price_incl_vat') }}</dt>
                                <dd class="text-right text-base font-semibold text-slate-900">{{ formatPrice(product.price) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('products.vat_rate_req').replace(' *', '') }}</dt>
                                <dd>
                                    <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">
                                        {{ product.vat_rate }}%
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('products.price_excl_vat') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ formatPrice(priceExcludingVat) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">{{ $t('products.vat_amount') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ formatPrice(vatAmount) }}</dd>
                            </div>
                        </dl>
                    </article>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
