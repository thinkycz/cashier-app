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
const openReceipts = ref([...(props.openTransactions || [])]);
const isCreatingReceipt = ref(false);
const isCheckingOut = ref(false);
const manualProductName = ref('');
const manualPackages = ref(1);
const manualQuantity = ref(1);
const manualPrice = ref(0);
const productNameInputRef = ref(null);
const packagesInputRef = ref(null);
const quantityInputRef = ref(null);
const priceInputRef = ref(null);

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;

    const query = searchQuery.value.toLowerCase();
    return props.products.filter((product) => {
        const matchesName = product.name?.toLowerCase().includes(query);
        const matchesEan = product.ean?.toLowerCase().includes(query);
        return matchesName || matchesEan;
    });
});

const cartItemsNewestFirst = computed(() => {
    return [...cart.items].reverse();
});

const activeReceiptLabel = computed(() => {
    return cart.currentTransaction?.transaction_id || 'No active receipt';
});

const addToCart = (product) => {
    cart.addItem(product);
};

const canAddManualItem = computed(() => {
    return Boolean(cart.currentTransaction) && manualProductName.value.trim().length > 0;
});

const addManualBillItem = () => {
    if (!canAddManualItem.value) {
        return;
    }

    cart.addManualItem({
        productName: manualProductName.value,
        packages: manualPackages.value,
        quantity: manualQuantity.value,
        unitPrice: manualPrice.value,
    });

    manualProductName.value = '';
    manualPackages.value = 1;
    manualQuantity.value = 1;
    manualPrice.value = 0;
    productNameInputRef.value?.focus();
};

const canCheckout = computed(() => {
    return Boolean(cart.currentTransaction) && cart.items.length > 0 && !isCheckingOut.value;
});

const focusPackagesInput = () => {
    packagesInputRef.value?.focus();
};

const focusQuantityInput = () => {
    quantityInputRef.value?.focus();
};

const focusPriceInput = () => {
    priceInputRef.value?.focus();
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
    return product.short_name || '';
};

const customerDisplayName = (customer) => {
    if (!customer) return 'No customer selected';

    const fullName = [customer.first_name, customer.last_name].filter(Boolean).join(' ').trim();
    return fullName || customer.company_name || 'No customer selected';
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

const checkoutReceipt = async (checkoutMethod) => {
    if (!canCheckout.value) {
        return;
    }

    const activeTransaction = cart.currentTransaction;
    if (!activeTransaction?.id) {
        return;
    }

    isCheckingOut.value = true;

    try {
        await axios.patch(route('dashboard.receipts.checkout', activeTransaction.id), {
            checkout_method: checkoutMethod,
            subtotal: cart.subtotal,
            discount: Number(activeTransaction.discount || 0),
            total: cart.total,
            items: cart.items.map((item) => {
                const parsedProductId = Number(item.product?.id);
                const productId = Number.isInteger(parsedProductId) && parsedProductId > 0 ? parsedProductId : null;

                return {
                    product_id: productId,
                    product_name: item.product?.name || 'Unknown product',
                    packages: Number(item.packages || 1),
                    quantity: Number(item.quantity || 0),
                    unit_price: Number(item.unit_price || 0),
                    vat_rate: Number(item.vat_rate || 0),
                    total: Number(item.total || 0),
                };
            }),
        });

        openReceipts.value = openReceipts.value.filter((receipt) => receipt.id !== activeTransaction.id);
        cart.clearTransactionItems(activeTransaction);

        if (openReceipts.value.length > 0) {
            cart.setTransaction(openReceipts.value[0]);
        } else {
            cart.setTransaction(null);
        }
    } finally {
        isCheckingOut.value = false;
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
                    <h2 class="text-2xl font-semibold text-slate-900">{{ activeReceiptLabel }}</h2>
                    <p class="mt-1 text-sm text-slate-600">{{ customerDisplayName(cart.selectedCustomer) }}</p>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                    <button type="button" class="inline-flex items-center justify-center rounded-md border border-teal-100 bg-teal-50/60 px-4 py-2 text-sm font-medium text-teal-700 transition-all duration-200 hover:-translate-y-px hover:bg-teal-100/70">Discount / Surcharge</button>
                    <button type="button" class="inline-flex items-center justify-center rounded-md border border-teal-100 bg-teal-50/60 px-4 py-2 text-sm font-medium text-teal-700 transition-all duration-200 hover:-translate-y-px hover:bg-teal-100/70">Select Customer</button>
                    <button
                        type="button"
                        :disabled="isCreatingReceipt"
                        class="inline-flex items-center justify-center rounded-md border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-px hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
                        @click="createNewTransaction"
                    >
                        {{ isCreatingReceipt ? 'Creating...' : 'New Receipt' }}
                    </button>
                </div>
            </div>
        </template>

        <div class="relative py-6">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-4 sm:px-6 lg:grid-cols-[22rem_minmax(0,1fr)] lg:px-8">
                <section class="flex h-full flex-col overflow-hidden rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/60">
                    <div class="bg-gradient-to-r from-teal-700 to-cyan-700 px-5 py-4 text-white">
                        <p class="text-xs uppercase tracking-wide text-cyan-100">Current Total</p>
                        <p class="mt-1 text-3xl font-semibold">{{ formatPrice(cart.total) }}</p>
                    </div>

                    <div class="flex min-h-0 flex-1 flex-col">
                        <div class="space-y-3 px-4 pt-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-slate-600">Product Name</label>
                                <input
                                    ref="productNameInputRef"
                                    v-model="manualProductName"
                                    type="text"
                                    placeholder="Product Name"
                                    class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                                    @keydown.enter.prevent="focusPackagesInput"
                                />
                            </div>

                            <div class="grid grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)_auto] items-end gap-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Packages</label>
                                    <input
                                        ref="packagesInputRef"
                                        v-model.number="manualPackages"
                                        type="number"
                                        min="1"
                                        class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                                        @keydown.enter.prevent="focusQuantityInput"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Quantity</label>
                                    <input
                                        ref="quantityInputRef"
                                        v-model.number="manualQuantity"
                                        type="number"
                                        min="1"
                                        class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                                        @keydown.enter.prevent="focusPriceInput"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Manual Price</label>
                                    <input
                                        ref="priceInputRef"
                                        v-model.number="manualPrice"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="h-10 w-full rounded-md border border-slate-200 px-3 text-sm text-slate-700 focus:border-teal-500 focus:outline-none focus:ring-2 focus:ring-teal-500/20"
                                        @keydown.enter.prevent="addManualBillItem"
                                    />
                                </div>
                                <div class="flex items-end">
                                <button
                                    type="button"
                                    :disabled="!canAddManualItem"
                                    class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-transparent bg-teal-600 text-white hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
                                    @click="addManualBillItem"
                                    aria-label="Add Item"
                                    title="Add Item"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M10 4a.75.75 0 01.75.75v4.5h4.5a.75.75 0 010 1.5h-4.5v4.5a.75.75 0 01-1.5 0v-4.5h-4.5a.75.75 0 010-1.5h4.5v-4.5A.75.75 0 0110 4z" />
                                    </svg>
                                </button>
                            </div>
                            </div>
                        </div>

                        <div class="mt-4 px-4">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-teal-700/80">Bill Items</h4>
                        </div>

                        <div class="mt-3 min-h-0 flex-1 max-h-[34rem] space-y-3 overflow-y-auto px-4 pb-4">
                            <article v-if="cart.items.length === 0" class="rounded-lg border border-dashed border-slate-300 bg-slate-50/60 px-4 py-8 text-center text-sm text-slate-500">
                                Cart is empty
                            </article>

                            <article
                                v-for="(item, index) in cartItemsNewestFirst"
                                :key="item.product.id"
                                class="min-h-24 rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm shadow-slate-100/70"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ item.product?.name || 'Unknown product' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">Line #{{ cart.items.length - index }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-slate-900">{{ formatPrice(item.total) }}</p>
                                </div>

                                <div class="mt-3 grid grid-cols-3 gap-3 text-xs text-slate-600">
                                    <div>
                                        <p class="text-slate-500">Packages</p>
                                        <p class="mt-1 font-medium text-slate-900">{{ item.packages || 1 }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Qty</p>
                                        <p class="mt-1 font-medium text-slate-900">{{ item.quantity }}</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Unit</p>
                                        <p class="mt-1 font-medium text-slate-900">{{ formatPrice(item.unit_price) }}</p>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div
                            v-if="cart.items.length > 0"
                            class="border-t border-slate-200/80 bg-white/80 px-4 pb-4 pt-3 shadow-[0_-6px_14px_-12px_rgba(15,23,42,0.45)]"
                        >
                            <div class="grid grid-cols-3 gap-3">
                            <button
                                type="button"
                                :disabled="!canCheckout"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700 disabled:cursor-not-allowed disabled:opacity-60"
                                @click="checkoutReceipt('cash')"
                            >
                                Hotove
                            </button>
                            <button
                                type="button"
                                :disabled="!canCheckout"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-teal-700 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60"
                                @click="checkoutReceipt('card')"
                            >
                                Kartou
                            </button>
                            <button
                                type="button"
                                :disabled="!canCheckout"
                                class="inline-flex items-center justify-center rounded-md border border-transparent bg-cyan-700 px-3 py-2 text-sm font-semibold text-white hover:bg-cyan-800 disabled:cursor-not-allowed disabled:opacity-60"
                                @click="checkoutReceipt('order')"
                            >
                                Objednavka
                            </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/70 to-cyan-50/60 px-4 py-3">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-teal-700/80">Open Receipts</h4>
                        </div>
                        <div class="space-y-2 p-4">
                            <button
                                v-for="transaction in openReceipts"
                                :key="transaction.id"
                                type="button"
                                class="flex w-full items-center justify-between rounded-md border px-3 py-2 text-left transition-colors"
                                :class="isActiveReceipt(transaction) ? 'border-teal-300 bg-teal-50' : 'border-slate-200 bg-white hover:bg-slate-50'"
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

                    <div class="rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                        <div class="border-b border-teal-200/70 bg-gradient-to-r from-teal-50/70 to-cyan-50/60 px-4 py-3">
                            <h4 class="text-xs font-semibold uppercase tracking-wide text-teal-700/80">Find Product</h4>
                        </div>

                        <div class="space-y-4">
                            <div class="flex flex-col gap-3 px-4 pt-4 md:flex-row md:items-center">
                                <div class="relative flex-1">
                                    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder="Search products"
                                        class="h-10 w-full rounded-md border border-slate-300 pl-10 pr-3 text-sm text-slate-700 focus:border-teal-600 focus:outline-none focus:ring-2 focus:ring-teal-600/20"
                                    />
                                </div>

                                <Link
                                    :href="route('products.create')"
                                    class="inline-flex items-center justify-center rounded-md border border-transparent bg-teal-600 px-3 py-2 text-sm font-medium text-white hover:bg-teal-700"
                                >
                                    Create Product
                                </Link>
                            </div>

                            <div class="hidden overflow-x-auto lg:block">
                                <table class="min-w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-teal-50/70 to-cyan-50/60">
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">Product</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">EAN</th>
                                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">VAT</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">Price</th>
                                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">Action</th>
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
                                                    class="inline-flex items-center rounded-md border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-medium text-teal-700 hover:bg-teal-100"
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

                            <div class="space-y-3 px-4 pb-4 lg:hidden">
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
                                        class="mt-4 inline-flex w-full items-center justify-center rounded-md border border-teal-200 bg-teal-50 px-3 py-2 text-xs font-medium text-teal-700"
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
