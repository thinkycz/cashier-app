import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useCartStore = defineStore('cart', () => {
    const STORAGE_KEY = 'cashier-cart-v1';
    const DEFAULT_MANUAL_VAT_RATE = 21;
    const hasLocalStorage = typeof window !== 'undefined' && typeof window.localStorage !== 'undefined';
    const persistedState = loadPersistedState();

    const itemsByReceipt = ref(persistedState.itemsByReceipt);
    const currentTransaction = ref(persistedState.currentTransaction);
    const selectedCustomer = ref(persistedState.selectedCustomer);

    const currentReceiptKey = computed(() => {
        if (!currentTransaction.value) {
            return null;
        }

        if (currentTransaction.value.id) {
            return `transaction:${currentTransaction.value.id}`;
        }

        if (currentTransaction.value.transaction_id) {
            return `transaction-code:${currentTransaction.value.transaction_id}`;
        }

        return null;
    });

    const items = computed(() => {
        if (!currentReceiptKey.value) {
            return [];
        }

        return itemsByReceipt.value[currentReceiptKey.value] || [];
    });

    const subtotal = computed(() => {
        return items.value.reduce((total, item) => total + item.total, 0);
    });

    const total = computed(() => {
        return subtotal.value - Number(currentTransaction.value?.discount || 0);
    });

    const itemCount = computed(() => {
        return items.value.reduce((count, item) => count + item.quantity, 0);
    });

    function addItem(product, quantity = 1) {
        const currentItems = getCurrentItems();

        if (!currentItems) {
            return;
        }

        const normalizedProductId = normalizeProductId(product?.id);
        const existingItem = currentItems.find(item => item.product_id && item.product_id === normalizedProductId);
        const safeQuantity = Math.max(1, Number(quantity) || 1);
        const unitPrice = Number(product.price || 0);

        if (existingItem) {
            existingItem.packages = Number(existingItem.packages || 1);
            existingItem.quantity += safeQuantity;
            existingItem.total = calculateLineTotal(existingItem.packages, existingItem.quantity, existingItem.unit_price);
        } else {
            currentItems.push({
                line_id: createLineId('catalog'),
                product_id: normalizedProductId,
                product,
                packages: 1,
                quantity: safeQuantity,
                unit_price: unitPrice,
                vat_rate: Number(product.vat_rate || 0),
                total: calculateLineTotal(1, safeQuantity, unitPrice)
            });
        }

        persistState();
    }

    function addManualItem({ productName, quantity = 1, unitPrice = 0, packages = 1, productId = null, vatRate = null }) {
        const currentItems = getCurrentItems();

        if (!currentItems) {
            return;
        }

        const normalizedProductId = normalizeProductId(productId);
        const safeQuantity = Math.max(1, Number(quantity) || 1);
        const safeUnitPrice = Math.max(0, Number(unitPrice) || 0);
        const safePackages = Math.max(1, Number(packages) || 1);
        const resolvedVatRate = Number(vatRate ?? DEFAULT_MANUAL_VAT_RATE);
        const lineId = createLineId(normalizedProductId ? 'catalog' : 'manual');
        const productNameValue = String(productName || '').trim() || 'Unknown product';

        currentItems.push({
            line_id: lineId,
            product_id: normalizedProductId,
            product: {
                id: normalizedProductId ?? lineId,
                name: productNameValue,
            },
            packages: safePackages,
            quantity: safeQuantity,
            unit_price: safeUnitPrice,
            vat_rate: resolvedVatRate,
            total: calculateLineTotal(safePackages, safeQuantity, safeUnitPrice),
        });

        persistState();
    }

    function removeItem(lineId) {
        const currentItems = getCurrentItems();
        if (!currentItems) {
            return;
        }

        const index = currentItems.findIndex(item => item.line_id === lineId || item.product?.id === lineId);
        if (index > -1) {
            currentItems.splice(index, 1);
            persistState();
        }
    }

    function updateQuantity(lineId, quantity) {
        const currentItems = getCurrentItems();
        if (!currentItems) {
            return;
        }

        const item = currentItems.find(item => item.line_id === lineId || item.product?.id === lineId);
        if (item) {
            item.packages = Number(item.packages || 1);
            item.quantity = Math.max(1, Number(quantity) || 1);
            item.total = calculateLineTotal(item.packages, item.quantity, item.unit_price);
            persistState();
        }
    }

    function clearCart() {
        itemsByReceipt.value = {};
        currentTransaction.value = null;
        selectedCustomer.value = null;
        persistState();
    }

    function setTransaction(transaction) {
        currentTransaction.value = transaction;

        if (!currentReceiptKey.value) {
            selectedCustomer.value = null;
            persistState();
            return;
        }

        if (!itemsByReceipt.value[currentReceiptKey.value]) {
            const transactionItems = transaction?.transaction_items || [];
            itemsByReceipt.value[currentReceiptKey.value] = transactionItems.map((item) => ({
                line_id: `transaction-item-${item.id}`,
                product_id: normalizeProductId(item.product_id ?? item.product?.id),
                product: item.product,
                packages: Number(item.packages || 1),
                quantity: Number(item.quantity),
                unit_price: Number(item.unit_price),
                vat_rate: Number(item.vat_rate),
                total: Number(item.total),
            }));
        }

        selectedCustomer.value = transaction?.customer || null;
        persistState();
    }

    function setCustomer(customer) {
        selectedCustomer.value = customer;
        persistState();
    }

    function setDiscount(amount) {
        if (currentTransaction.value) {
            currentTransaction.value.discount = amount;
            persistState();
        }
    }

    function clearTransactionItems(transaction) {
        const receiptKey = getReceiptKey(transaction);
        if (!receiptKey) {
            return;
        }

        delete itemsByReceipt.value[receiptKey];

        if (currentTransaction.value?.id === transaction?.id) {
            currentTransaction.value = null;
            selectedCustomer.value = null;
        }

        persistState();
    }

    return {
        items,
        currentTransaction,
        selectedCustomer,
        subtotal,
        total,
        itemCount,
        addItem,
        addManualItem,
        removeItem,
        updateQuantity,
        clearCart,
        clearTransactionItems,
        setTransaction,
        setCustomer,
        setDiscount
    };

    function getCurrentItems() {
        if (!currentReceiptKey.value) {
            return null;
        }

        if (!itemsByReceipt.value[currentReceiptKey.value]) {
            itemsByReceipt.value[currentReceiptKey.value] = [];
        }

        return itemsByReceipt.value[currentReceiptKey.value];
    }

    function getReceiptKey(transaction) {
        if (!transaction) {
            return null;
        }

        if (transaction.id) {
            return `transaction:${transaction.id}`;
        }

        if (transaction.transaction_id) {
            return `transaction-code:${transaction.transaction_id}`;
        }

        return null;
    }

    function persistState() {
        if (!hasLocalStorage) {
            return;
        }

        window.localStorage.setItem(STORAGE_KEY, JSON.stringify({
            itemsByReceipt: itemsByReceipt.value,
            currentTransaction: currentTransaction.value,
            selectedCustomer: selectedCustomer.value,
        }));
    }

    function loadPersistedState() {
        if (!hasLocalStorage) {
            return defaultState();
        }

        try {
            const rawState = window.localStorage.getItem(STORAGE_KEY);
            if (!rawState) {
                return defaultState();
            }

            const parsed = JSON.parse(rawState);
            return {
                itemsByReceipt: sanitizeItemsByReceipt(parsed?.itemsByReceipt),
                currentTransaction: parsed?.currentTransaction || null,
                selectedCustomer: parsed?.selectedCustomer || null,
            };
        } catch {
            return defaultState();
        }
    }

    function sanitizeItemsByReceipt(value) {
        if (!value || typeof value !== 'object') {
            return {};
        }

        return Object.fromEntries(
            Object.entries(value).map(([key, receiptItems]) => [
                key,
                Array.isArray(receiptItems)
                    ? receiptItems.map((item, index) => {
                        const productId = normalizeProductId(item?.product_id ?? item?.product?.id);
                        const hasVatRate = Object.prototype.hasOwnProperty.call(item || {}, 'vat_rate');
                        const fallbackVatRate = productId ? 0 : DEFAULT_MANUAL_VAT_RATE;

                        return {
                            ...item,
                            line_id: String(item?.line_id || item?.product?.id || `${key}-line-${index}`),
                            product_id: productId,
                            product: item?.product || {
                                id: productId || `${key}-line-${index}`,
                                name: 'Unknown product',
                            },
                            packages: Number(item?.packages || 1),
                            quantity: Number(item?.quantity || 0),
                            unit_price: Number(item?.unit_price || 0),
                            vat_rate: Number(hasVatRate ? item?.vat_rate : fallbackVatRate),
                            total: Number(item?.total || 0),
                        };
                    })
                    : [],
            ]),
        );
    }

    function defaultState() {
        return {
            itemsByReceipt: {},
            currentTransaction: null,
            selectedCustomer: null,
        };
    }

    function calculateLineTotal(packages, quantity, unitPrice) {
        const safePackages = Math.max(1, Number(packages) || 1);
        const safeQuantity = Math.max(1, Number(quantity) || 1);
        const safeUnitPrice = Math.max(0, Number(unitPrice) || 0);

        return Math.round((safePackages * safeQuantity * safeUnitPrice + Number.EPSILON) * 100) / 100;
    }

    function createLineId(prefix = 'line') {
        return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    }

    function normalizeProductId(value) {
        const parsed = Number(value);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
    }
});
