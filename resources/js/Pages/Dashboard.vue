<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { useCartStore } from '@/stores/cart';
import axios from 'axios';

const cart = useCartStore();

const props = defineProps({
    products: Array,
    openTransactions: Array,
    customers: Array,
});

const searchQuery = ref('');
const searchMode = ref('name');
const openReceipts = ref([...(props.openTransactions || [])]);
const isCreatingReceipt = ref(false);

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;

    const query = searchQuery.value.toLowerCase();
    return props.products.filter((product) => {
        if (searchMode.value === 'ean') {
            return product.ean?.toLowerCase().includes(query);
        }

        return product.name.toLowerCase().includes(query);
    });
});

const activeReceiptLabel = computed(() => {
    return cart.currentTransaction?.transaction_id || 'No active receipt';
});

const addToCart = (product) => {
    cart.addItem(product);
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('cs-CZ', {
        style: 'currency',
        currency: 'CZK',
    }).format(price || 0);
};

const formatVat = (vatRate) => {
    return Number(vatRate || 0).toFixed(2);
};

const productSubtitle = (product) => {
    return product.short_name || product.description || '';
};

const createNewTransaction = async () => {
    if (isCreatingReceipt.value) {
        return;
    }

    isCreatingReceipt.value = true;

    try {
        const { data } = await axios.post(route('dashboard.receipts.store'));
        const transaction = data.transaction;

        openReceipts.value = [transaction, ...openReceipts.value.filter((receipt) => receipt.id !== transaction.id)];
        cart.setTransaction(transaction);
    } finally {
        isCreatingReceipt.value = false;
    }
};

const selectTransaction = (transaction) => {
    cart.setTransaction(transaction);
};

const isActiveReceipt = (transaction) => {
    return cart.currentTransaction?.id === transaction.id;
};

onMounted(() => {
    if (openReceipts.value.length > 0 && !cart.currentTransaction) {
        cart.setTransaction(openReceipts.value[0]);
    }
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-2xl font-semibold text-slate-900">Dashboard</h2>
                    <p class="mt-1 text-sm text-slate-500">Run checkout, manage open receipts, and quickly add products.</p>
                </div>
                <div class="text-sm text-slate-500 sm:text-right">
                    {{ formatPrice(cart.total) }} in cart
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-4 sm:px-6 lg:grid-cols-[22rem_minmax(0,1fr)] lg:px-8">
                <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    <div class="bg-gradient-to-r from-slate-800 to-slate-700 px-5 py-4 text-white">
                        <p class="text-xs uppercase tracking-wide text-slate-200">Current Total</p>
                        <p class="mt-1 text-3xl font-semibold">{{ formatPrice(cart.total) }}</p>
                    </div>

                    <div class="space-y-4 p-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-1">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-600">Packages</label>
                                <input type="number" min="1" value="1" class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-600">Quantity</label>
                                <input type="number" min="1" value="1" class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700" />
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-xs font-medium text-slate-600">Manual Price</label>
                            <input type="number" min="0" step="0.01" class="h-10 w-full rounded-md border border-slate-300 px-3 text-sm text-slate-700" />
                        </div>

                        <div class="overflow-hidden rounded-md border border-slate-200">
                            <div class="grid grid-cols-4 bg-slate-50 px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                <span>#</span>
                                <span>Qty</span>
                                <span>Unit</span>
                                <span class="text-right">Total</span>
                            </div>
                            <div v-if="cart.items.length === 0" class="px-3 py-8 text-center text-sm text-slate-500">
                                Cart is empty
                            </div>
                            <div
                                v-for="(item, index) in cart.items"
                                :key="item.product.id"
                                class="grid grid-cols-4 border-t border-slate-100 px-3 py-2 text-sm text-slate-700"
                            >
                                <span>{{ index + 1 }}</span>
                                <span>{{ item.quantity }}</span>
                                <span>{{ formatPrice(item.unit_price) }}</span>
                                <span class="text-right font-medium text-slate-900">{{ formatPrice(item.total) }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                            <div>
                                <h3 class="text-xl font-semibold text-slate-900">{{ activeReceiptLabel }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ cart.selectedCustomer?.name || 'No customer selected' }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" class="inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Discount / Surcharge</button>
                                <button type="button" class="inline-flex items-center rounded-md border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Select Customer</button>
                                <button
                                    type="button"
                                    :disabled="isCreatingReceipt"
                                    class="inline-flex items-center rounded-md border border-transparent bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="createNewTransaction"
                                >
                                    {{ isCreatingReceipt ? 'Creating...' : 'New Receipt' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Open Receipts</h4>
                        </div>
                        <div class="space-y-2 p-4">
                            <button
                                v-for="transaction in openReceipts"
                                :key="transaction.id"
                                type="button"
                                class="flex w-full items-center justify-between rounded-md border px-3 py-2 text-left transition-colors"
                                :class="isActiveReceipt(transaction) ? 'border-sky-300 bg-sky-50' : 'border-slate-200 bg-white hover:bg-slate-50'"
                                @click="selectTransaction(transaction)"
                            >
                                <span>
                                    <span class="block text-sm font-semibold text-slate-900">{{ transaction.transaction_id }}</span>
                                    <span class="block text-xs text-slate-500">{{ formatPrice(transaction.total) }}</span>
                                </span>
                                <span class="text-xs text-slate-500">Open</span>
                            </button>
                            <p v-if="openReceipts.length === 0" class="text-sm text-slate-500">No open receipts.</p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 bg-slate-50 px-4 py-3">
                            <h4 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Find Product</h4>
                        </div>

                        <div class="space-y-4 p-4">
                            <div class="flex flex-col gap-3 md:flex-row md:items-center">
                                <div class="relative flex-1">
                                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search products"
                                        class="h-10 w-full rounded-md border border-slate-300 pl-10 pr-3 text-sm text-slate-700 focus:border-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-600/20"
                                    />
                                </div>

                                <div class="inline-flex rounded-md border border-slate-200 bg-slate-50 p-1">
                                    <button
                                        type="button"
                                        class="rounded px-3 py-1.5 text-xs font-medium"
                                        :class="searchMode === 'name' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'"
                                        @click="searchMode = 'name'"
                                    >
                                        Name
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded px-3 py-1.5 text-xs font-medium"
                                        :class="searchMode === 'ean' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'"
                                        @click="searchMode = 'ean'"
                                    >
                                        EAN
                                    </button>
                                </div>

                                <Link
                                    :href="route('products.create')"
                                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-sky-600 px-3 py-2 text-sm font-medium text-white hover:bg-sky-700"
                                >
                                    Create Product
                                </Link>
                            </div>

                            <div class="hidden overflow-x-auto lg:block">
                                <table class="min-w-full border-collapse">
                                    <thead>
                                        <tr class="bg-slate-50">
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Product</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">EAN</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">VAT</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Price</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="product in filteredProducts"
                                            :key="product.id"
                                            class="border-t border-slate-100 transition-colors hover:bg-slate-50"
                                        >
                                            <td class="px-4 py-3 align-top">
                                                <p class="text-sm font-semibold text-slate-900">{{ product.name }}</p>
                                                <p v-if="productSubtitle(product)" class="mt-0.5 text-xs text-slate-500">{{ productSubtitle(product) }}</p>
                                            </td>
                                            <td class="px-4 py-3 text-xs font-mono text-slate-600">{{ product.ean || '-' }}</td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">
                                                    {{ formatVat(product.vat_rate) }}%
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <p class="text-sm font-semibold text-slate-900">{{ formatPrice(product.price) }}</p>
                                                <p class="mt-0.5 text-xs text-slate-500">incl. VAT</p>
                                            </td>
                                            <td class="px-4 py-3 text-right">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center rounded-md border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-700 hover:bg-sky-100"
                                                    @click="addToCart(product)"
                                                >
                                                    Add
                                                </button>
                                            </td>
                                        </tr>
                                        <tr v-if="filteredProducts.length === 0">
                                            <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500">No products found.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="space-y-3 lg:hidden">
                                <article v-for="product in filteredProducts" :key="product.id" class="rounded-lg border border-slate-200 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h5 class="text-sm font-semibold text-slate-900">{{ product.name }}</h5>
                                            <p v-if="productSubtitle(product)" class="mt-1 text-xs text-slate-500">{{ productSubtitle(product) }}</p>
                                        </div>
                                        <p class="text-sm font-semibold text-slate-900">{{ formatPrice(product.price) }}</p>
                                    </div>
                                    <div class="mt-3 grid grid-cols-3 gap-3 text-xs text-slate-600">
                                        <div>
                                            <p class="text-slate-500">EAN</p>
                                            <p class="mt-1 font-mono">{{ product.ean || '-' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-slate-500">VAT</p>
                                            <p class="mt-1 font-medium">{{ formatVat(product.vat_rate) }}%</p>
                                        </div>
                                        <div>
                                            <p class="text-slate-500">Price</p>
                                            <p class="mt-1 font-medium text-slate-900">{{ formatPrice(product.price) }}</p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-medium text-sky-700"
                                        @click="addToCart(product)"
                                    >
                                        Add to Cart
                                    </button>
                                </article>
                                <p v-if="filteredProducts.length === 0" class="rounded-lg border border-slate-200 px-4 py-8 text-center text-sm text-slate-500">No products found.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
