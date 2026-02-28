<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import Dropdown from '@/Components/Dropdown.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const isVatPayer = computed(() => usePage().props.auth.user.is_vat_payer);

const props = defineProps({
    bill: Object,
});

const openForm = useForm({});
const deleteForm = useForm({});
const showPrintPreviewModal = ref(false);
const previewFrameRef = ref(null);
const selectedPrintDocument = ref('bill');
const basePreviewUrl = route('bills.preview', props.bill.id);
const previewUrl = computed(() => `${basePreviewUrl}?document=${selectedPrintDocument.value}`);
const embeddedPreviewUrl = computed(() => `${previewUrl.value}&embedded=1`);
const currentPreviewLabel = computed(() => {
    if (selectedPrintDocument.value === 'invoice') return t('bills.vat_invoice');
    if (selectedPrintDocument.value === 'non_vat_invoice') return t('bills.non_vat_invoice');
    if (selectedPrintDocument.value === 'delivery_note') return t('bills.delivery_note');
    if (selectedPrintDocument.value === 'non_vat_bill') return t('bills.non_vat_bill');
    if (selectedPrintDocument.value === 'quotation') return t('bills.quotation');
    if (selectedPrintDocument.value === 'vat_bill') return t('bills.vat_bill');
    return t('bills.title');
});

const customerDisplayName = (customer) => {
    if (!customer) return t('bills.not_provided');

    const fullName = [customer.first_name, customer.last_name].filter(Boolean).join(' ').trim();
    return fullName || customer.company_name || t('bills.not_provided');
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('cs-CZ', {
        style: 'currency',
        currency: 'CZK',
    }).format(price);
};

const priceExcludingVat = (amountIncludingVat, vatRate) => {
    const safeAmount = Number(amountIncludingVat || 0);
    const safeVatRate = Number(vatRate || 0);
    const divisor = 1 + (safeVatRate / 100);

    if (divisor <= 0) {
        return safeAmount;
    }

    return safeAmount / divisor;
};

const billSubtotalExcludingVat = () => {
    return (props.bill?.transaction_items || []).reduce((sum, item) => {
        return sum + priceExcludingVat(item.total, item.vat_rate);
    }, 0);
};

const adjustmentType = () => {
    const type = props.bill?.adjustment_type;
    return type === 'discount' || type === 'surcharge' ? type : null;
};

const adjustmentPercent = () => {
    return Number(props.bill?.adjustment_percent || 0);
};

const adjustmentAmount = () => {
    const type = adjustmentType();

    if (type) {
        return Number(props.bill?.adjustment_amount || 0);
    }

    return Number(props.bill?.discount || 0);
};

const hasAdjustment = () => {
    return adjustmentAmount() > 0;
};

const billTotalExcludingVat = () => {
    const subtotalExclVat = billSubtotalExcludingVat();
    const type = adjustmentType();

    if (!type && Number(props.bill?.discount || 0) > 0) {
        const discount = Number(props.bill?.discount || 0);
        return Math.max(0, subtotalExclVat - discount);
    }

    return subtotalExclVat;
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

const openPrintPreviewModal = (documentType = 'bill') => {
    selectedPrintDocument.value = documentType;
    showPrintPreviewModal.value = true;
};

const closePrintPreviewModal = () => {
    showPrintPreviewModal.value = false;
};

const printPreview = () => {
    const frameWindow = previewFrameRef.value?.contentWindow;

    if (frameWindow) {
        frameWindow.focus();
        frameWindow.print();
    }
};

const openPreviewInNewWindow = () => {
    window.open(previewUrl.value, '_blank', 'noopener');
};

const adjustmentLabel = () => {
    const type = adjustmentType();

    if (!type) {
        return Number(props.bill?.discount || 0) > 0 ? t('bills.legacy_discount') : t('bills.adjustment');
    }

    return type === 'discount' ? t('bills.discount') : t('bills.surcharge');
};

const openBill = () => {
    openForm.post(route('bills.open', props.bill.id));
};

const deleteBill = () => {
    if (!confirm(t('bills.delete_confirm'))) {
        return;
    }

    deleteForm.delete(route('bills.destroy', props.bill.id));
};
</script>

<template>
    <Head :title="`${$t('bills.transaction')} - ${bill.transaction_id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-2xl font-semibold text-slate-900">{{ $t('bills.transaction') }} {{ bill.transaction_id }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ $t('bills.detailed_breakdown') }}</p>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        :href="route('bills.index')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-all duration-200 hover:-translate-y-px hover:bg-slate-50"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        {{ $t('bills.back_to_bills') }}
                    </Link>
                    <button
                        type="button"
                        :disabled="openForm.processing"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-px hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="openBill"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                        {{ openForm.processing ? $t('bills.opening') : $t('bills.open_in_dashboard') }}
                    </button>
                    <button
                        type="button"
                        :disabled="deleteForm.processing"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-transparent bg-rose-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-px hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="deleteBill"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3m-4 0h14" />
                        </svg>
                        {{ deleteForm.processing ? $t('bills.deleting') : $t('bills.delete') }}
                    </button>
                </div>
            </div>
        </template>

        <div class="screen-bill-content py-6">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <section class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="grid gap-4 px-6 py-5 md:grid-cols-3 md:items-center">
                        <div class="md:col-span-2">
                            <h1 class="text-xl font-semibold text-slate-900">{{ $t('bills.transaction') }} {{ bill.transaction_id }}</h1>
                            <p class="mt-1 text-sm text-slate-500">{{ formatDate(bill.created_at) }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex rounded-full bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                    {{ $t('bills.customer') }}: {{ customerDisplayName(bill.customer) }}
                                </span>
                            </div>
                        </div>
                        <div class="rounded-lg border border-teal-200/70 bg-gradient-to-br from-teal-50/70 to-cyan-50/60 p-4 text-right">
                            <p class="text-xs uppercase tracking-wide text-slate-500">{{ $t('bills.total') }}</p>
                            <p class="mt-2 text-2xl font-semibold text-slate-900">{{ formatPrice(bill.total) }}</p>
                            <p v-if="isVatPayer" class="mt-0.5 text-xs text-slate-500">{{ $t('bills.excl_vat') }} {{ formatPrice(billTotalExcludingVat()) }}</p>
                        </div>
                    </div>
                </section>

                <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <article class="relative z-10 rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="rounded-t-xl border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-800">{{ $t('bills.transaction_details') }}</h3>
                        </div>
                        <dl class="space-y-4 px-6 py-5 text-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('bills.bill_id') }}</dt>
                                <dd class="font-medium text-slate-900">{{ bill.transaction_id }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('bills.date') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ formatDate(bill.created_at) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('bills.status') }}</dt>
                                <dd>
                                    <span
                                        :class="getStatusColor(bill.status)"
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium capitalize"
                                    >
                                        {{ $t('bills.status_' + bill.status) }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <dt class="text-slate-500">{{ $t('bills.documents') }}</dt>
                                <dd>
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition-all duration-200 hover:bg-slate-50"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0H7m10 0v2a2 2 0 01-2 2H9a2 2 0 01-2-2v-2m10-8V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4" />
                                                </svg>
                                                {{ $t('bills.print') }}
                                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <button
                                            v-if="isVatPayer"
                                            type="button"
                                            class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none transition duration-150 ease-in-out"
                                            @click="openPrintPreviewModal('vat_bill')"
                                        >
                                            {{ $t('bills.vat_bill') }}
                                        </button>
                                            <button
                                                v-if="!isVatPayer"
                                                type="button"
                                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none transition duration-150 ease-in-out"
                                                @click="openPrintPreviewModal('non_vat_bill')"
                                            >
                                                {{ $t('bills.non_vat_bill') }}
                                            </button>
                                            <button
                                                v-if="isVatPayer"
                                                type="button"
                                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none transition duration-150 ease-in-out"
                                                @click="openPrintPreviewModal('invoice')"
                                            >
                                                {{ $t('bills.vat_invoice') }}
                                            </button>
                                            <button
                                                v-if="!isVatPayer"
                                                type="button"
                                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none transition duration-150 ease-in-out"
                                                @click="openPrintPreviewModal('non_vat_invoice')"
                                            >
                                                {{ $t('bills.non_vat_invoice') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none transition duration-150 ease-in-out"
                                                @click="openPrintPreviewModal('delivery_note')"
                                            >
                                                {{ $t('bills.delivery_note') }}
                                            </button>
                                            <button
                                                type="button"
                                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none transition duration-150 ease-in-out"
                                                @click="openPrintPreviewModal('quotation')"
                                            >
                                                {{ $t('bills.quotation') }}
                                            </button>
                                        </template>
                                    </Dropdown>
                                </dd>
                            </div>
                        </dl>
                    </article>

                    <article class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                            <h3 class="text-base font-semibold text-slate-800">{{ $t('bills.customer_details') }}</h3>
                        </div>
                        <dl class="space-y-4 px-6 py-5 text-sm">
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('bills.name') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ customerDisplayName(bill.customer) }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('bills.company_id') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ bill.customer?.company_id || $t('bills.not_provided') }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4 border-b border-slate-100 pb-3">
                                <dt class="text-slate-500">{{ $t('bills.email') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ bill.customer?.email || $t('bills.not_provided') }}</dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-slate-500">{{ $t('bills.phone') }}</dt>
                                <dd class="text-right font-medium text-slate-900">{{ bill.customer?.phone_number || $t('bills.not_provided') }}</dd>
                            </div>
                        </dl>
                    </article>
                </section>

                <section class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-800">{{ $t('bills.items') }}</h3>
                    </div>
                    <div class="hidden overflow-x-auto lg:block">
                        <table class="min-w-full border-collapse">
                            <thead>
                                <tr class="bg-gradient-to-r from-teal-50/70 to-cyan-50/60">
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">#</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">{{ $t('bills.product') }}</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">{{ $t('bills.ean') }}</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-teal-700/80">{{ $t('bills.packages') }}</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-teal-700/80">{{ $t('bills.qty') }}</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">{{ $t('bills.unit_price') }}</th>
                                    <th v-if="isVatPayer" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">{{ $t('bills.vat') }}</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">{{ $t('bills.total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(item, index) in bill.transaction_items"
                                    :key="item.id"
                                    class="border-t border-teal-100/70 transition-colors duration-150 odd:bg-teal-50/15 even:bg-white hover:bg-teal-50/45"
                                >
                                    <td class="px-5 py-4 text-sm text-slate-700">{{ index + 1 }}</td>
                                    <td class="px-5 py-4 align-top">
                                        <p class="text-sm font-medium text-slate-900">{{ item.product.name }}</p>
                                        <p v-if="item.product?.short_name" class="mt-0.5 text-xs text-slate-500">{{ item.product.short_name }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-mono text-slate-700">
                                            {{ item.product?.ean || '-' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-center text-sm text-slate-700">{{ item.packages || 1 }}</td>
                                    <td class="px-5 py-4 text-center text-sm text-slate-700">{{ item.quantity }}</td>
                                    <td class="px-5 py-4 text-right">
                                        <p class="text-sm text-slate-700">{{ formatPrice(item.unit_price) }}</p>
                                        <p v-if="isVatPayer" class="mt-0.5 text-xs text-slate-500">{{ $t('bills.excl_vat') }} {{ formatPrice(priceExcludingVat(item.unit_price, item.vat_rate)) }}</p>
                                    </td>
                                    <td v-if="isVatPayer" class="px-5 py-4 text-right text-sm text-slate-700">
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">
                                            {{ item.vat_rate }}%
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <p class="text-sm font-semibold text-slate-900">{{ formatPrice(item.total) }}</p>
                                        <p v-if="isVatPayer" class="mt-0.5 text-xs text-slate-500">{{ $t('bills.excl_vat') }} {{ formatPrice(priceExcludingVat(item.total, item.vat_rate)) }}</p>
                                    </td>
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
                                    <p v-if="item.product?.short_name" class="mt-0.5 text-xs text-slate-500">{{ item.product.short_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $t('bills.item_index') }} #{{ index + 1 }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-semibold text-slate-900">{{ formatPrice(item.total) }}</p>
                                    <p v-if="isVatPayer" class="mt-0.5 text-xs text-slate-500">{{ $t('bills.excl_vat') }} {{ formatPrice(priceExcludingVat(item.total, item.vat_rate)) }}</p>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-3 gap-3 text-xs text-slate-600">
                                <div>
                                    <p class="text-slate-500">{{ $t('bills.ean') }}</p>
                                    <p class="mt-1">
                                        <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-mono text-slate-700">
                                            {{ item.product?.ean || '-' }}
                                        </span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-slate-500">{{ $t('bills.packages') }}</p>
                                    <p class="mt-1 font-medium">{{ item.packages || 1 }}</p>
                                </div>
                                <div>
                                    <p class="text-slate-500">{{ $t('bills.qty') }}</p>
                                    <p class="mt-1 font-medium">{{ item.quantity }}</p>
                                </div>
                                <div :class="isVatPayer ? '' : 'col-span-3'">
                                    <p class="text-slate-500">{{ $t('bills.unit') }}</p>
                                    <p class="mt-1 font-medium">{{ formatPrice(item.unit_price) }}</p>
                                    <p v-if="isVatPayer" class="mt-0.5 text-[11px] text-slate-500">{{ $t('bills.excl_vat') }} {{ formatPrice(priceExcludingVat(item.unit_price, item.vat_rate)) }}</p>
                                </div>
                                <div v-if="isVatPayer">
                                    <p class="text-slate-500">{{ $t('bills.vat') }}</p>
                                    <p class="mt-1">
                                        <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">
                                            {{ item.vat_rate }}%
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-800">{{ $t('bills.summary') }}</h3>
                    </div>
                    <div class="px-6 py-5">
                        <dl class="ml-auto max-w-sm space-y-3 text-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <dt class="text-slate-500">{{ $t('bills.subtotal') }}</dt>
                                <dd class="text-right">
                                    <p class="font-medium text-slate-900">{{ formatPrice(bill.subtotal) }}</p>
                                    <p v-if="isVatPayer" class="mt-0.5 text-xs text-slate-500">{{ $t('bills.excl_vat') }} {{ formatPrice(billSubtotalExcludingVat()) }}</p>
                                </dd>
                            </div>
                            <div v-if="hasAdjustment()" class="flex items-center justify-between border-b border-slate-100 pb-2">
                                <dt class="text-slate-500">{{ adjustmentLabel() }}</dt>
                                <dd
                                    class="text-right font-medium"
                                    :class="adjustmentType() === 'discount' || (!adjustmentType() && bill.discount > 0) ? 'text-rose-600' : adjustmentType() === 'surcharge' ? 'text-amber-700' : 'text-slate-900'"
                                >
                                    <p>
                                        {{ adjustmentType() === 'discount' || (!adjustmentType() && bill.discount > 0)
                                            ? `-${formatPrice(adjustmentAmount())}`
                                            : adjustmentType() === 'surcharge'
                                                ? `+${formatPrice(adjustmentAmount())}`
                                                : formatPrice(0) }}
                                    </p>
                                    <p v-if="adjustmentType()" class="mt-0.5 text-xs text-slate-500">
                                        {{ Number(adjustmentPercent() || 0).toFixed(2) }}%
                                    </p>
                                </dd>
                            </div>
                            <div class="flex items-center justify-between pt-1">
                                <dt class="text-base font-semibold text-slate-900">{{ $t('bills.total') }}</dt>
                                <dd class="text-right">
                                    <p class="text-base font-semibold text-slate-900">{{ formatPrice(bill.total) }}</p>
                                    <p v-if="isVatPayer" class="mt-0.5 text-xs text-slate-500">{{ $t('bills.excl_vat') }} {{ formatPrice(billTotalExcludingVat()) }}</p>
                                </dd>
                            </div>
                        </dl>
                    </div>
                </section>

                <section v-if="bill.notes" class="overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/65 to-cyan-50/55 px-6 py-4">
                        <h3 class="text-base font-semibold text-slate-800">{{ $t('bills.notes') }}</h3>
                    </div>
                    <p class="px-6 py-5 text-sm text-slate-700">{{ bill.notes }}</p>
                </section>
            </div>
        </div>

        <Modal :show="showPrintPreviewModal" max-width="7xl" @close="closePrintPreviewModal">
            <div class="preview-modal-content">
                <div class="preview-modal-header">
                    <h3 class="text-lg font-semibold text-slate-900">{{ $t('bills.preview') }} - {{ currentPreviewLabel }}</h3>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="no-print inline-flex items-center gap-1.5 rounded-md border border-transparent bg-gradient-to-r from-teal-600 to-cyan-600 px-3 py-2 text-sm font-semibold text-white shadow-sm shadow-teal-200/70 transition-all duration-200 hover:from-teal-700 hover:to-cyan-700"
                            @click="printPreview"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m10 0H7m10 0v2a2 2 0 01-2 2H9a2 2 0 01-2-2v-2m10-8V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4" />
                            </svg>
                            {{ $t('bills.print_btn') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50"
                            @click="openPreviewInNewWindow"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 3h7m0 0v7m0-7L10 14" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5h6m-6 0v14h14v-6" />
                            </svg>
                            {{ $t('bills.open_in_new_window') }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-md text-slate-500 transition-colors hover:bg-slate-100 hover:text-slate-700"
                            @click="closePrintPreviewModal"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="preview-canvas">
                    <iframe
                        ref="previewFrameRef"
                        :src="embeddedPreviewUrl"
                        :title="`${$t('bills.preview')} ${currentPreviewLabel}`"
                        class="preview-frame"
                    />
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.preview-modal-content {
    padding: 1.25rem;
}

.preview-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.preview-canvas {
    margin-top: 1rem;
    height: min(82vh, 1100px);
    border-radius: 0.75rem;
    background: #9aa9bf;
    overflow: hidden;
}

.preview-frame {
    height: 100%;
    width: 100%;
    border: 0;
    border-radius: 0.75rem;
    background: #9aa9bf;
}
</style>
