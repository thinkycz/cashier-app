<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    bill: Object,
});

const customerDisplayName = (customer) => {
    if (!customer) return 'No customer';

    const fullName = [customer.first_name, customer.last_name].filter(Boolean).join(' ').trim();
    return fullName || customer.company_name || 'No customer';
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('cs-CZ', {
        style: 'currency',
        currency: 'CZK',
    }).format(price);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('cs-CZ', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'cash':
            return 'bg-emerald-100 text-emerald-700';
        case 'card':
            return 'bg-cyan-100 text-cyan-700';
        case 'order':
            return 'bg-sky-100 text-sky-700';
        case 'open':
            return 'bg-amber-100 text-amber-700';
        default:
            return 'bg-slate-100 text-slate-700';
    }
};

const printBill = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Bill - ${bill.transaction_id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-2xl font-semibold text-slate-900">Bill {{ bill.transaction_id }}</h2>
                    <p class="mt-1 text-sm text-slate-600">Detailed transaction breakdown, customer info, and totals.</p>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        :href="route('bills.index')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-all duration-200 hover:-translate-y-px hover:bg-slate-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Bills
                    </Link>
                    <button
                        type="button"
                        @click="printBill"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-transparent bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-px hover:bg-emerald-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0H7m10 0v2a2 2 0 01-2 2H9a2 2 0 01-2-2v-2m10-8V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4" />
                        </svg>
                        Print Bill
                    </button>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="grid gap-4 px-6 py-5 md:grid-cols-3 md:items-center">
                        <div class="md:col-span-2">
                            <h1 class="text-xl font-semibold text-slate-900">Transaction {{ bill.transaction_id }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ formatDate(bill.created_at) }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span
                                    :class="getStatusColor(bill.status)"
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium capitalize"
                                >
                                    {{ bill.status }}
                                </span>
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                    Customer: {{ customerDisplayName(bill.customer) }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-lg border border-teal-200/70 bg-gradient-to-br from-teal-50/70 to-cyan-50/60 p-4 text-right">
                            <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatPrice(bill.total) }}</p>
                            <p class="mt-1 text-xs text-slate-500">Subtotal {{ formatPrice(bill.subtotal) }}</p>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-800">Transaction Details</h3>
                        </div>
                        <dl class="space-y-4 px-6 py-5 text-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">Transaction ID</dt>
                                <dd class="font-medium text-slate-900">{{ bill.transaction_id }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">Date</dt>
                                <dd class="text-right font-medium text-slate-900">{{ formatDate(bill.created_at) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Status</dt>
                                <dd>
                                    <span
                                        :class="getStatusColor(bill.status)"
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium capitalize"
                                    >
                                        {{ bill.status }}
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </article>

                    <article class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-800">Customer Details</h3>
                        </div>
                        <dl class="space-y-4 px-6 py-5 text-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">Name</dt>
                                <dd class="text-right font-medium text-slate-900">{{ customerDisplayName(bill.customer) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">Company ID</dt>
                                <dd class="text-right font-medium text-slate-900">{{ bill.customer?.company_id || 'Not provided' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">Email</dt>
                                <dd class="text-right font-medium text-slate-900">{{ bill.customer?.email || 'Not provided' }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">Phone</dt>
                                <dd class="text-right font-medium text-slate-900">{{ bill.customer?.phone_number || 'Not provided' }}</dd>
                            </div>
                        </dl>
                    </article>
                </section>

                <section class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-800">Items</h3>
                    </div>
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="min-w-full border-collapse">
                            <thead>
                                <tr class="bg-gradient-to-r from-teal-50/70 to-cyan-50/60">
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">#</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">Product</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-teal-700/80">Packages</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-teal-700/80">Qty</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">Unit Price</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">VAT</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in bill.transaction_items"
                                    :key="item.id"
                                    class="border-t border-teal-100/70 transition-colors duration-150 odd:bg-teal-50/15 even:bg-white hover:bg-teal-50/45"
                                >
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ index + 1 }}</td>
                                    <td class="px-5 py-4 text-sm font-medium text-slate-900">{{ item.product.name }}</td>
                                    <td class="px-5 py-4 text-center text-sm text-slate-700">{{ item.packages || 1 }}</td>
                                    <td class="px-5 py-4 text-center text-sm text-slate-700">{{ item.quantity }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-slate-700">{{ formatPrice(item.unit_price) }}</td>
                                    <td class="px-5 py-4 text-right text-sm text-slate-700">{{ item.vat_rate }}%</td>
                                    <td class="px-5 py-4 text-right text-sm font-semibold text-slate-900">{{ formatPrice(item.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="space-y-3 p-4 lg:hidden">
                        <article
                            v-for="(item, index) in bill.transaction_items"
                            :key="item.id"
                            class="rounded-lg border border-slate-200 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-semibold text-slate-900">{{ item.product.name }}</h4>
                                    <p class="mt-1 text-xs text-slate-500">Item #{{ index + 1 }}</p>
                                </div>
                                <p class="text-sm font-semibold text-slate-900">{{ formatPrice(item.total) }}</p>
                            </div>
                            <div class="mt-3 grid grid-cols-4 gap-3 text-xs text-slate-600">
                                <div>
                                    <p class="text-slate-500">Packages</p>
                                    <p class="mt-1 font-medium">{{ item.packages || 1 }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500">Qty</p>
                                    <p class="mt-1 font-medium">{{ item.quantity }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500">Unit</p>
                                    <p class="mt-1 font-medium">{{ formatPrice(item.unit_price) }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500">VAT</p>
                                    <p class="mt-1 font-medium">{{ item.vat_rate }}%</p>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-800">Summary</h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="ml-auto max-w-sm space-y-3 text-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <dt class="text-slate-500">Subtotal</dt>
                                <dd class="font-medium text-slate-900">{{ formatPrice(bill.subtotal) }}</dd>
                            </div>
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <dt class="text-slate-500">Discount</dt>
                                <dd :class="bill.discount > 0 ? 'text-rose-600' : 'text-slate-900'" class="font-medium">
                                    {{ bill.discount > 0 ? `-${formatPrice(bill.discount)}` : formatPrice(0) }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <dt class="text-base font-semibold text-slate-900">Total</dt>
                                <dd class="text-base font-semibold text-slate-900">{{ formatPrice(bill.total) }}</dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section v-if="bill.notes" class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-800">Notes</h3>
                    </div>
                    <p class="px-6 py-5 text-sm text-slate-700">{{ bill.notes }}</p>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
