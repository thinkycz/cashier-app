<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    customer: Object,
});

const customerDisplayName = (customer) => {
    const fullName = [customer.first_name, customer.last_name].filter(Boolean).join(' ').trim();
    return fullName || customer.company_name;
};
</script>

<template>
    <Head :title="`${$t('customers.title')} - ${customerDisplayName(customer)}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-2xl font-semibold text-slate-900">{{ customerDisplayName(customer) }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $t('customers.review_details') }}</p>
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
                    <Link
                        :href="route('customers.edit', customer.id)"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-px hover:bg-teal-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        {{ $t('customers.edit') }}
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="grid gap-4 px-6 py-5 md:grid-cols-3 md:items-center">
                        <div class="md:col-span-2">
                            <h1 class="text-xl font-semibold text-slate-900">{{ customer.company_name }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ customerDisplayName(customer) }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                    {{ $t('customers.company_id') }}: {{ customer.company_id }}
                                </span>
                                <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                    {{ $t('customers.vat') }}: {{ customer.vat_id || $t('customers.na') }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-lg border border-teal-200/70 bg-gradient-to-br from-teal-50/70 to-cyan-50/60 p-4 text-right">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ $t('customers.country') }}</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ customer.country_code }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ customer.city }}, {{ customer.zip }}</p>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h2 class="text-base font-semibold text-slate-800">{{ $t('customers.company_info') }}</h2>
                        </div>
                        <dl class="space-y-4 px-6 py-5 text-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('customers.company_name') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ customer.company_name }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('customers.company_id') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ customer.company_id }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">{{ $t('customers.vat_id') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ customer.vat_id || $t('customers.not_specified') }}</dd>
                            </div>
                        </dl>
                    </article>

                    <article class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h2 class="text-base font-semibold text-slate-800">{{ $t('customers.contact_info') }}</h2>
                        </div>
                        <dl class="space-y-4 px-6 py-5 text-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('customers.contact_name') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ customerDisplayName(customer) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('customers.email') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ customer.email || $t('customers.not_provided') }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">{{ $t('customers.phone') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ customer.phone_number || $t('customers.not_provided') }}</dd>
                            </div>
                        </dl>
                    </article>
                </section>

                <section class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                        <h2 class="text-base font-semibold text-slate-800">{{ $t('customers.address') }}</h2>
                    </div>
                    <dl class="space-y-4 px-6 py-5 text-sm">
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                            <dt class="text-slate-500">{{ $t('customers.street') }}</dt>
                            <dd class="text-right font-medium text-slate-900">{{ customer.street }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                            <dt class="text-slate-500">{{ $t('customers.city') }}</dt>
                            <dd class="text-right font-medium text-slate-900">{{ customer.city }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                            <dt class="text-slate-500">{{ $t('customers.zip') }}</dt>
                            <dd class="text-right font-medium text-slate-900">{{ customer.zip }}</dd>
                        </div>
                        <div class="flex items-start justify-between gap-4">
                            <dt class="text-slate-500">{{ $t('customers.country_code') }}</dt>
                            <dd class="text-right font-medium text-slate-900">{{ customer.country_code }}</dd>
                        </div>
                    </dl>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
