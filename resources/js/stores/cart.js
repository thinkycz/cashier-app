import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useCartStore = defineStore('cart', () => {
    const STORAGE_KEY = 'cashier-cart-v1';
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

        const existingItem = currentItems.find(item => item.product.id === product.id);
        const unitPrice = Number(product.price || 0);

        if (existingItem) {
            existingItem.quantity += quantity;
            existingItem.total = existingItem.quantity * existingItem.unit_price;
        } else {
            currentItems.push({
                product,
                quantity,
                unit_price: unitPrice,
                vat_rate: Number(product.vat_rate || 0),
                total: unitPrice * quantity
            });
        }

        persistState();
    }

    function addManualItem({ productName, quantity = 1, unitPrice = 0, packages = 1 }) {
        const currentItems = getCurrentItems();

        if (!currentItems) {
            return;
        }

        const safeQuantity = Math.max(1, Number(quantity) || 1);
        const safeUnitPrice = Math.max(0, Number(unitPrice) || 0);
        const safePackages = Math.max(1, Number(packages) || 1);
        const lineId = `manual-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;

        currentItems.push({
            product: {
                id: lineId,
                name: String(productName || '').trim() || 'Unknown product',
            },
            packages: safePackages,
            quantity: safeQuantity,
            unit_price: safeUnitPrice,
            vat_rate: 0,
            total: safeUnitPrice * safeQuantity,
        });

        persistState();
    }

    function removeItem(productId) {
        const currentItems = getCurrentItems();
        if (!currentItems) {
            return;
        }

        const index = currentItems.findIndex(item => item.product.id === productId);
        if (index > -1) {
            currentItems.splice(index, 1);
            persistState();
        }
    }

    function updateQuantity(productId, quantity) {
        const currentItems = getCurrentItems();
        if (!currentItems) {
            return;
        }

        const item = currentItems.find(item => item.product.id === productId);
        if (item) {
            item.quantity = quantity;
            item.total = item.quantity * item.unit_price;
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
                    ? receiptItems.map((item) => ({
                        ...item,
                        packages: Number(item?.packages || 1),
                        quantity: Number(item?.quantity || 0),
                        unit_price: Number(item?.unit_price || 0),
                        vat_rate: Number(item?.vat_rate || 0),
                        total: Number(item?.total || 0),
                    }))
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
});
