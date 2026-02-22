<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    transactions: Object,
    filters: Object,
});

const statusOptions = [
    {
        value: 'open',
        label: 'Open',
        activeClasses: 'border-amber-300 bg-amber-50 text-amber-800',
        inactiveClasses: 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
        dotClasses: 'bg-amber-500',
    },
    {
        value: 'cash',
        label: 'Cash',
        activeClasses: 'border-emerald-300 bg-emerald-50 text-emerald-800',
        inactiveClasses: 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
        dotClasses: 'bg-emerald-500',
    },
    {
        value: 'card',
        label: 'Card',
        activeClasses: 'border-cyan-300 bg-cyan-50 text-cyan-800',
        inactiveClasses: 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
        dotClasses: 'bg-cyan-500',
    },
    {
        value: 'order',
        label: 'Order',
        activeClasses: 'border-sky-300 bg-sky-50 text-sky-800',
        inactiveClasses: 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50',
        dotClasses: 'bg-sky-500',
    },
];

const defaultStatuses = ['cash', 'card', 'order'];

const normalizeStatuses = (rawStatuses) => {
    const allowedValues = statusOptions.map((option) => option.value);

    if (Array.isArray(rawStatuses)) {
        return [...new Set(
            rawStatuses
                .filter((status) => typeof status === 'string')
                .map((status) => status.trim())
                .filter((status) => allowedValues.includes(status)),
        )];
    }

    if (typeof rawStatuses === 'string' && allowedValues.includes(rawStatuses.trim())) {
        return [rawStatuses.trim()];
    }

    return [];
};

const customerDisplayName = (customer) => {
    if (!customer) return 'No customer';

    const fullName = [customer.first_name, customer.last_name].filter(Boolean).join(' ').trim();
    return fullName || customer.company_name || 'No customer';
};

const search = ref(props.filters?.search || '');
const statuses = ref(normalizeStatuses(props.filters?.status));

watch([search, statuses], ([searchValue, statusValues]) => {
    const queryStatuses = statusValues.length > 0 ? statusValues : [''];

    router.get(route('bills.index'), {
        search: searchValue,
        status: queryStatuses,
    }, {
        preserveState: true,
        replace: true,
    });
}, { deep: true });

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
    });
};

const formatTime = (date) => {
    return new Date(date).toLocaleTimeString('cs-CZ', {
        hour: '2-digit',
        minute: '2-digit',
    });
};

const getStatusColor = (transactionStatus) => {
    switch (transactionStatus) {
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

const toggleStatus = (statusValue) => {
    if (statuses.value.includes(statusValue)) {
        statuses.value = statuses.value.filter((value) => value !== statusValue);
        return;
    }

    statuses.value = [...statuses.value, statusValue];
};

const isStatusSelected = (statusValue) => statuses.value.includes(statusValue);

const areStatusSelectionsEqual = (left, right) => {
    if (left.length !== right.length) {
        return false;
    }

    const sortedLeft = [...left].sort();
    const sortedRight = [...right].sort();

    return sortedLeft.every((status, index) => status === sortedRight[index]);
};

const isDefaultStatusSelection = computed(() => areStatusSelectionsEqual(statuses.value, defaultStatuses));
const isFiltering = computed(() => Boolean(search.value?.trim()) || !isDefaultStatusSelection.value);
const isEmpty = computed(() => props.transactions.data.length === 0);
</script>

<template>
    <Head title="Bills" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-2xl font-semibold text-slate-900">Bills</h2>
                    <p class="mt-1 text-sm text-slate-600">Review transaction history, customer links, and billing totals.</p>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                    <div class="relative w-full sm:w-80">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <TextInput
                            id="bill-search"
                            v-model="search"
                            type="text"
                            placeholder="Search by bill ID or customer"
                            class="h-10 pl-10 pr-3 text-sm"
                        />
                    </div>
                    <div id="bill-status" class="flex flex-wrap items-center gap-2">
                        <button
                            v-for="option in statusOptions"
                            :key="option.value"
                            type="button"
                            :title="option.label"
                            :aria-label="option.label"
                            :aria-pressed="isStatusSelected(option.value)"
                            :class="[
                                'inline-flex h-10 w-12 items-center justify-center rounded-md border transition-colors',
                                isStatusSelected(option.value) ? option.activeClasses : option.inactiveClasses,
                            ]"
                            @click="toggleStatus(option.value)"
                        >
                            <span :class="['h-2 w-2 rounded-full', option.dotClasses]"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <div class="rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div v-if="isEmpty" class="px-6 py-16 text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3 class="mt-3 text-base font-semibold text-slate-900">
                            {{ isFiltering ? 'No matching bills' : 'No bills yet' }}
                        </h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ isFiltering ? 'Try a different search or status selection.' : 'Bills will appear here once transactions are created.' }}
                        </p>
                    </div>

                    <div v-else>
                        <div class="hidden overflow-x-auto lg:block">
                            <table class="min-w-full border-collapse">
                                <thead>
                                    <tr class="bg-gradient-to-r from-teal-50/70 to-cyan-50/60">
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">Bill ID</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">Customer</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">Date</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">Total</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">Status</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="transaction in transactions.data"
                                        :key="transaction.id"
                                        class="border-t border-teal-100/70 bg-teal-50/10 transition-colors duration-200 odd:bg-white even:bg-teal-50/15 hover:bg-teal-50/60"
                                    >
                                        <td class="px-5 py-4 align-top">
                                            <p class="text-sm font-semibold text-slate-900">{{ transaction.transaction_id }}</p>
                                        </td>
                                        <td class="px-5 py-4 align-top text-sm text-slate-700">
                                            {{ customerDisplayName(transaction.customer) }}
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <p class="text-sm font-medium text-slate-900">{{ formatDate(transaction.created_at) }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">{{ formatTime(transaction.created_at) }}</p>
                                        </td>
                                        <td class="px-5 py-4 text-right align-top">
                                            <p class="text-sm font-semibold text-slate-900">{{ formatPrice(transaction.total) }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">Subtotal {{ formatPrice(transaction.subtotal) }}</p>
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <span
                                                :class="getStatusColor(transaction.status)"
                                                class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium capitalize"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                {{ transaction.status }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <div class="flex justify-end">
                                                <Link
                                                    :href="route('bills.show', transaction.id)"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-100"
                                                    title="View bill"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="space-y-3 p-4 lg:hidden">
                            <article
                                v-for="transaction in transactions.data"
                                :key="transaction.id"
                                class="rounded-lg border border-slate-200 p-4"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">{{ transaction.transaction_id }}</h3>
                                        <p class="mt-1 text-xs text-slate-500">{{ customerDisplayName(transaction.customer) }}</p>
                                    </div>
                                    <span
                                        :class="getStatusColor(transaction.status)"
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium capitalize"
                                    >
                                        {{ transaction.status }}
                                    </span>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-slate-600">
                                    <div>
                                        <p class="text-slate-500">Date</p>
                                        <p class="mt-1 font-medium">{{ formatDate(transaction.created_at) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Total</p>
                                        <p class="mt-1 font-semibold text-slate-900">{{ formatPrice(transaction.total) }}</p>
                                    </div>
                                </div>
                                <Link
                                    :href="route('bills.show', transaction.id)"
                                    class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-700"
                                >
                                    View Bill
                                </Link>
                            </article>
                        </div>
                    </div>
                </div>

                <div v-if="transactions.links && !isEmpty" class="flex justify-center">
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <template v-for="(link, index) in transactions.links" :key="`${index}-${link.label}-${link.url}`">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="link.active
                                    ? 'inline-flex min-w-9 items-center justify-center rounded-lg border border-teal-600 bg-gradient-to-r from-teal-600 to-cyan-600 px-3 py-2 text-xs font-semibold text-white shadow-sm shadow-teal-300/30'
                                    : 'inline-flex min-w-9 items-center justify-center rounded-lg border border-teal-200 bg-teal-50/35 px-3 py-2 text-xs font-medium text-teal-800 transition-colors hover:bg-teal-50/70'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="inline-flex min-w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-slate-100/80 px-3 py-2 text-xs font-medium text-slate-400"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
