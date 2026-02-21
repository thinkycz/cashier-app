<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useCartStore } from '@/stores/cart';

const cart = useCartStore();

const props = defineProps({
    products: Array,
    openTransactions: Array,
    customers: Array,
});

const searchQuery = ref('');
const searchMode = ref('name');

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;

    const query = searchQuery.value.toLowerCase();
    return props.products.filter((product) => {
        if (searchMode.value === 'category') {
            return product.category?.toLowerCase().includes(query);
        }

        if (searchMode.value === 'ean') {
            return product.ean?.toLowerCase().includes(query);
        }

        return product.name.toLowerCase().includes(query);
    });
});

const activeReceiptLabel = computed(() => {
    return cart.currentTransaction?.transaction_id || 'UC2602214523';
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
    return product.description || product.category || '';
};

const createNewTransaction = () => {
    cart.clearCart();
    cart.setTransaction({
        id: null,
        transaction_id: '',
        discount: 0,
        status: 'open',
    });
};

const selectTransaction = (transaction) => {
    cart.setTransaction(transaction);
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="pos-page py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-4 xl:grid-cols-[38%_62%]">
                    <section class="pos-card left-panel">
                        <div class="total-bar">
                            <span class="total-label">Celkem</span>
                            <span class="total-amount">{{ formatPrice(cart.total) }}</span>
                        </div>

                        <div class="left-input-wrap">
                            <div class="left-fake-select">
                                <span class="left-fake-select-chevron">⌄</span>
                            </div>

                            <div class="left-inline-grid">
                                <div>
                                    <label class="left-inline-label">Balíků</label>
                                    <input type="number" min="1" value="1" class="left-inline-input" />
                                </div>
                                <div>
                                    <label class="left-inline-label">Počet</label>
                                    <input type="number" min="1" value="1" class="left-inline-input" />
                                </div>
                                <div>
                                    <label class="left-inline-label">Cena</label>
                                    <input type="number" min="0" step="0.01" class="left-inline-input" />
                                </div>
                                <button type="button" class="left-check-btn" aria-label="Potvrdit položku">
                                    ✓
                                </button>
                            </div>
                        </div>

                        <div class="left-table-head">
                            <span>#</span>
                            <span>POČET</span>
                            <span>CENA ZA MJ</span>
                            <span>CELKEM</span>
                        </div>

                        <div class="left-table-body">
                            <div v-if="cart.items.length === 0" class="left-empty"></div>

                            <div
                                v-for="(item, index) in cart.items"
                                :key="item.product.id"
                                class="left-item-row"
                            >
                                <span>{{ index + 1 }}</span>
                                <span>{{ item.quantity }}</span>
                                <span>{{ formatPrice(item.unit_price) }}</span>
                                <span class="left-row-total">{{ formatPrice(item.total) }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="pos-card right-panel">
                        <div class="right-topbar">
                            <div>
                                <h2 class="receipt-id">{{ activeReceiptLabel }}</h2>
                                <p class="receipt-customer">{{ cart.selectedCustomer?.name || 'Žádný zákazník' }}</p>
                            </div>

                            <div class="right-actions">
                                <button type="button" class="btn btn-secondary">Sleva a přirážka</button>
                                <button type="button" class="btn btn-secondary">Vybrat zákazníka</button>
                                <button type="button" class="btn btn-primary" @click="createNewTransaction">
                                    Nová účtenka
                                </button>
                            </div>
                        </div>

                        <div class="right-section">
                            <h3 class="section-title">OTEVŘENÉ ÚČTENKY</h3>

                            <div class="open-list">
                                <button
                                    v-for="transaction in openTransactions"
                                    :key="transaction.id"
                                    type="button"
                                    class="open-item"
                                    @click="selectTransaction(transaction)"
                                >
                                    <span class="open-item-icon">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M8 3H13L18 8V20H8V3Z" stroke="currentColor" stroke-width="1.7" />
                                            <path d="M13 3V8H18" stroke="currentColor" stroke-width="1.7" />
                                        </svg>
                                    </span>
                                    <span class="open-item-body">
                                        <span class="open-item-id">{{ transaction.transaction_id }}</span>
                                        <span class="open-item-total">{{ formatPrice(transaction.total) }}</span>
                                    </span>
                                    <span class="open-item-more">⋮</span>
                                </button>

                                <p v-if="openTransactions.length === 0" class="no-open-items">Žádné otevřené účtenky</p>
                            </div>
                        </div>

                        <div class="right-section">
                            <h3 class="section-title">NAJÍT PRODUKT</h3>

                            <div class="search-row">
                                <div class="search-wrap">
                                    <input
                                        v-model="searchQuery"
                                        type="text"
                                        placeholder=""
                                        class="search-input"
                                    />
                                    <div class="search-tabs" role="group" aria-label="Režim hledání">
                                        <button
                                            type="button"
                                            class="tab-btn"
                                            :class="{ active: searchMode === 'category' }"
                                            @click="searchMode = 'category'"
                                        >
                                            CAT
                                        </button>
                                        <button
                                            type="button"
                                            class="tab-btn"
                                            :class="{ active: searchMode === 'ean' }"
                                            @click="searchMode = 'ean'"
                                        >
                                            EAN
                                        </button>
                                    </div>
                                </div>

                                <Link :href="route('products.create')" class="btn btn-primary create-btn">
                                    Vytvořit produkt
                                </Link>
                            </div>
                        </div>

                        <div class="product-table-wrap">
                            <table class="product-table">
                                <thead>
                                    <tr>
                                        <th>PRODUKT</th>
                                        <th>CAT</th>
                                        <th>EAN</th>
                                        <th>SAZBA DPH</th>
                                        <th>CENA</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="product in filteredProducts" :key="product.id">
                                        <td class="product-name-cell">
                                            <div class="product-name">{{ product.name }}</div>
                                            <div class="product-subtitle">{{ productSubtitle(product) }}</div>
                                        </td>
                                        <td>{{ product.category || '' }}</td>
                                        <td>{{ product.ean || '' }}</td>
                                        <td>{{ formatVat(product.vat_rate) }}</td>
                                        <td>
                                            <div>{{ formatPrice(product.price) }}</div>
                                            <div class="muted-sub">{{ formatPrice(product.price) }}</div>
                                        </td>
                                        <td class="action-cell">
                                            <button
                                                type="button"
                                                class="add-link"
                                                @click="addToCart(product)"
                                            >
                                                Přidat
                                            </button>
                                        </td>
                                    </tr>

                                    <tr v-if="filteredProducts.length === 0">
                                        <td colspan="6" class="empty-products">Žádné produkty</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="table-footer">
                            <button type="button" class="more-link">Další</button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.pos-page {
    --panel-bg: #f8fafc;
    --panel-border: #d8dee8;
    --muted-text: #6b7280;
    --heading-text: #5f6979;
    --body-text: #1f2937;
    --primary: #0d8ad5;
    --primary-hover: #0a79bb;
    --dark-total: #1d2b40;
}

.pos-card {
    background: var(--panel-bg);
    border: 1px solid var(--panel-border);
    border-radius: 10px;
    box-shadow: 0 1px 3px rgba(24, 39, 75, 0.08);
    overflow: hidden;
}

.left-panel {
    min-height: 760px;
    padding: 12px;
}

.total-bar {
    height: 72px;
    background: linear-gradient(120deg, #203147, var(--dark-total));
    color: #fff;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
}

.total-label {
    font-size: 1.05rem;
    line-height: 1;
}

.total-amount {
    font-size: 2.2rem;
    font-weight: 500;
    line-height: 1;
    letter-spacing: 0.02em;
}

.left-input-wrap {
    margin-top: 12px;
}

.left-fake-select {
    height: 44px;
    background: #fff;
    border: 1px solid var(--panel-border);
    border-radius: 8px;
    position: relative;
}

.left-fake-select-chevron {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #9aa2b1;
    font-size: 16px;
}

.left-inline-grid {
    margin-top: 10px;
    display: grid;
    grid-template-columns: 1fr 1fr 1fr 44px;
    gap: 8px;
    align-items: end;
}

.left-inline-label {
    display: block;
    margin-bottom: 4px;
    font-size: 0.95rem;
    color: #4f5969;
}

.left-inline-input {
    width: 100%;
    height: 44px;
    border: 1px solid var(--panel-border);
    border-radius: 8px;
    background: #fff;
    font-size: 1.05rem;
    color: #1e293b;
    padding: 8px 10px;
}

.left-check-btn {
    height: 44px;
    border: 0;
    border-radius: 8px;
    background: var(--primary);
    color: #fff;
    font-size: 1.1rem;
    cursor: pointer;
}

.left-check-btn:hover {
    background: var(--primary-hover);
}

.left-table-head {
    margin-top: 12px;
    border-top: 1px solid var(--panel-border);
    border-bottom: 1px solid var(--panel-border);
    height: 44px;
    display: grid;
    grid-template-columns: 0.5fr 1fr 1.2fr 1fr;
    align-items: center;
    color: #748093;
    font-size: 0.78rem;
    letter-spacing: 0.03em;
    padding: 0 10px;
}

.left-table-body {
    min-height: 520px;
    background: #fbfcfd;
}

.left-empty {
    min-height: 520px;
}

.left-item-row {
    display: grid;
    grid-template-columns: 0.5fr 1fr 1.2fr 1fr;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #e2e7ee;
    font-size: 0.88rem;
    color: #2b3545;
}

.left-row-total {
    text-align: right;
}

.right-panel {
    min-height: 760px;
}

.right-topbar {
    border-bottom: 1px solid var(--panel-border);
    padding: 14px 16px;
    display: flex;
    gap: 14px;
    justify-content: space-between;
    align-items: center;
}

.receipt-id {
    font-size: 2rem;
    color: #2c3646;
    line-height: 1.1;
    margin: 0;
}

.receipt-customer {
    margin-top: 2px;
    color: #778194;
    font-size: 1.2rem;
}

.right-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.btn {
    height: 44px;
    padding: 0 16px;
    border-radius: 8px;
    border: 1px solid var(--panel-border);
    font-size: 0.95rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    white-space: nowrap;
}

.btn-secondary {
    background: #f2f4f8;
    color: #626e80;
}

.btn-primary {
    border-color: transparent;
    background: var(--primary);
    color: #fff;
}

.btn-primary:hover {
    background: var(--primary-hover);
}

.right-section {
    border-bottom: 1px solid var(--panel-border);
    padding: 14px 16px 16px;
}

.section-title {
    font-size: 1.1rem;
    letter-spacing: 0.04em;
    color: #707a8b;
    margin-bottom: 10px;
}

.open-list {
    max-width: 360px;
}

.open-item {
    width: 100%;
    height: 72px;
    border: 1px solid var(--panel-border);
    border-radius: 10px;
    background: #fff;
    display: flex;
    align-items: stretch;
    overflow: hidden;
    cursor: pointer;
}

.open-item-icon {
    width: 62px;
    background: var(--primary);
    color: #fff;
    display: grid;
    place-items: center;
}

.open-item-icon svg {
    width: 20px;
    height: 20px;
}

.open-item-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: flex-start;
    padding: 0 16px;
}

.open-item-id {
    font-size: 1.25rem;
    color: #2f3a4b;
}

.open-item-total {
    font-size: 1rem;
    color: #6a7486;
    margin-top: 1px;
}

.open-item-more {
    width: 40px;
    color: #9aa2b1;
    font-size: 1.1rem;
    display: grid;
    place-items: center;
}

.no-open-items {
    color: #80899b;
    font-size: 0.9rem;
}

.search-row {
    display: flex;
    gap: 10px;
    align-items: center;
}

.search-wrap {
    flex: 1;
    border: 1px solid var(--panel-border);
    border-radius: 8px;
    background: #fff;
    height: 44px;
    display: flex;
    align-items: center;
    overflow: hidden;
}

.search-input {
    flex: 1;
    height: 100%;
    border: 0;
    padding: 0 10px;
    font-size: 0.95rem;
    color: #2d3748;
}

.search-input:focus {
    outline: none;
}

.search-tabs {
    display: flex;
    gap: 2px;
    margin-right: 6px;
}

.tab-btn {
    height: 34px;
    min-width: 52px;
    border: 1px solid var(--panel-border);
    border-radius: 6px;
    background: #f4f6fa;
    color: #8a94a5;
    font-size: 0.8rem;
    padding: 0 10px;
}

.tab-btn.active {
    background: #e7f4fd;
    border-color: #9acdec;
    color: #3179a8;
}

.create-btn {
    text-decoration: none;
    min-width: 170px;
}

.product-table-wrap {
    overflow-x: auto;
}

.product-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 720px;
}

.product-table thead th {
    height: 52px;
    border-bottom: 1px solid var(--panel-border);
    color: #707a8b;
    font-size: 0.82rem;
    font-weight: 500;
    text-align: left;
    letter-spacing: 0.03em;
    padding: 0 16px;
}

.product-table tbody td {
    border-bottom: 1px solid #dde3eb;
    color: #4b5568;
    font-size: 1rem;
    padding: 10px 16px;
    vertical-align: middle;
}

.product-name {
    color: var(--body-text);
    font-size: 1.15rem;
}

.product-subtitle {
    margin-top: 2px;
    color: #5f6979;
    font-size: 1rem;
}

.muted-sub {
    color: #666f80;
    font-size: 0.95rem;
}

.action-cell {
    text-align: right;
}

.add-link {
    border: 0;
    background: transparent;
    color: var(--primary);
    font-size: 1.1rem;
    cursor: pointer;
    padding: 0;
}

.add-link:hover {
    text-decoration: underline;
}

.empty-products {
    text-align: center;
    color: #8a93a3;
    font-size: 0.9rem;
    padding: 24px;
}

.table-footer {
    display: flex;
    justify-content: flex-end;
    padding: 10px 14px;
}

.more-link {
    border: 0;
    background: transparent;
    color: var(--primary);
    font-size: 1rem;
    cursor: pointer;
}

@media (max-width: 1279px) {
    .left-panel,
    .right-panel {
        min-height: auto;
    }

    .left-table-body,
    .left-empty {
        min-height: 260px;
    }

    .right-topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .right-actions {
        justify-content: flex-start;
    }

    .search-row {
        flex-direction: column;
        align-items: stretch;
    }

    .create-btn {
        width: 100%;
    }

    .left-inline-grid {
        grid-template-columns: 1fr 1fr;
    }

    .product-name {
        font-size: 1.05rem;
    }
}
</style>
