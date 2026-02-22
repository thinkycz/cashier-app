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
        return roundMoney(items.value.reduce((totalValue, item) => totalValue + Number(item.total || 0), 0));
    });

    const total = computed(() => {
        return subtotal.value;
    });

    const itemCount = computed(() => {
        return items.value.reduce((count, item) => count + item.quantity, 0);
    });

    const adjustment = computed(() => {
        const type = normalizeAdjustmentType(currentTransaction.value?.adjustment_type);
        const percent = clampPercent(currentTransaction.value?.adjustment_percent ?? 0);

        if (!type || percent <= 0) {
            return {
                type: null,
                percent: 0,
            };
        }

        return {
            type,
            percent,
        };
    });

    function addItem(product, quantity = 1) {
        const currentItems = getCurrentItems();

        if (!currentItems) {
            return;
        }

        const normalizedProductId = normalizeProductId(product?.id);
        const existingItem = currentItems.find(item => item.product_id && item.product_id === normalizedProductId);
        const safeQuantity = Math.max(1, Number(quantity) || 1);
        const baseUnitPrice = Math.max(0, Number(product.price || 0));

        if (existingItem) {
            existingItem.packages = Number(existingItem.packages || 1);
            existingItem.quantity += safeQuantity;
            recalculateLine(existingItem);
        } else {
            const line = {
                line_id: createLineId('catalog'),
                product_id: normalizedProductId,
                product,
                packages: 1,
                quantity: safeQuantity,
                base_unit_price: baseUnitPrice,
                unit_price: baseUnitPrice,
                vat_rate: Number(product.vat_rate || 0),
                total: 0,
            };

            recalculateLine(line);
            currentItems.push(line);
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
        const baseUnitPrice = Math.max(0, Number(unitPrice) || 0);
        const safePackages = Math.max(1, Number(packages) || 1);
        const resolvedVatRate = Number(vatRate ?? DEFAULT_MANUAL_VAT_RATE);
        const lineId = createLineId(normalizedProductId ? 'catalog' : 'manual');
        const productNameValue = String(productName || '').trim() || 'Unknown product';

        const line = {
            line_id: lineId,
            product_id: normalizedProductId,
            product: {
                id: normalizedProductId ?? lineId,
                name: productNameValue,
            },
            packages: safePackages,
            quantity: safeQuantity,
            base_unit_price: baseUnitPrice,
            unit_price: baseUnitPrice,
            vat_rate: resolvedVatRate,
            total: 0,
        };

        recalculateLine(line);
        currentItems.push(line);

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
            recalculateLine(item);
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
        if (transaction) {
            applyTransactionDefaults(transaction);
        }

        currentTransaction.value = transaction;

        if (!currentReceiptKey.value) {
            selectedCustomer.value = null;
            persistState();
            return;
        }

        if (!itemsByReceipt.value[currentReceiptKey.value]) {
            const transactionItems = transaction?.transaction_items || [];
            itemsByReceipt.value[currentReceiptKey.value] = transactionItems.map((item) => {
                const productId = normalizeProductId(item.product_id ?? item.product?.id);
                const normalizedLine = {
                    line_id: `transaction-item-${item.id}`,
                    product_id: productId,
                    product: item.product,
                    packages: Number(item.packages || 1),
                    quantity: Number(item.quantity),
                    base_unit_price: Number((item.base_unit_price ?? item.unit_price) || 0),
                    unit_price: Number(item.unit_price || 0),
                    vat_rate: Number(item.vat_rate),
                    total: Number(item.total),
                };

                return normalizeLine(normalizedLine, `transaction:${transaction?.id || 'unknown'}`, 0);
            });
        }

        recalculateCurrentReceiptItems();
        selectedCustomer.value = transaction?.customer || null;
        persistState();
    }

    function setCustomer(customer) {
        selectedCustomer.value = customer;
        persistState();
    }

    function setAdjustment({ type, percent }) {
        if (!currentTransaction.value) {
            return;
        }

        const normalizedType = normalizeAdjustmentType(type);
        const normalizedPercent = clampPercent(percent);

        currentTransaction.value.adjustment_type = normalizedType;
        currentTransaction.value.adjustment_percent = normalizedType ? normalizedPercent : 0;

        recalculateCurrentReceiptItems();
        persistState();
    }

    function clearAdjustment() {
        if (!currentTransaction.value) {
            return;
        }

        currentTransaction.value.adjustment_type = null;
        currentTransaction.value.adjustment_percent = 0;
        currentTransaction.value.adjustment_amount = 0;

        recalculateCurrentReceiptItems();
        persistState();
    }

    function recalculateCurrentReceiptItems() {
        const currentItems = getCurrentItems();

        if (!currentItems) {
            return;
        }

        currentItems.forEach((item) => {
            recalculateLine(item);
        });

        const currentAdjustment = adjustment.value;
        const baseSubtotal = roundMoney(currentItems.reduce((value, item) => {
            return value + ((Number(item.packages || 1) * Number(item.quantity || 1) * Number(item.base_unit_price || 0)) || 0);
        }, 0));

        if (currentTransaction.value) {
            currentTransaction.value.adjustment_amount = currentAdjustment.type
                ? roundMoney(baseSubtotal * (currentAdjustment.percent / 100))
                : 0;
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

    function getReceiptTotal(transaction) {
        const receiptKey = getReceiptKey(transaction);

        if (!receiptKey) {
            return Number(transaction?.total || 0);
        }

        const receiptItems = itemsByReceipt.value[receiptKey];
        if (!Array.isArray(receiptItems) || receiptItems.length === 0) {
            return Number(transaction?.total || 0);
        }

        return roundMoney(receiptItems.reduce((sum, item) => sum + Number(item.total || 0), 0));
    }

    return {
        items,
        currentTransaction,
        selectedCustomer,
        subtotal,
        total,
        itemCount,
        adjustment,
        addItem,
        addManualItem,
        removeItem,
        updateQuantity,
        clearCart,
        clearTransactionItems,
        getReceiptTotal,
        setTransaction,
        setCustomer,
        setAdjustment,
        clearAdjustment,
        recalculateCurrentReceiptItems,
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

    function recalculateLine(item) {
        const safePackages = Math.max(1, Number(item.packages || 1));
        const safeQuantity = Math.max(1, Number(item.quantity || 1));
        const baseUnitPrice = Math.max(0, Number((item.base_unit_price ?? item.unit_price) || 0));
        const adjustedUnitPrice = applyAdjustmentToUnitPrice(baseUnitPrice, adjustment.value);

        item.packages = safePackages;
        item.quantity = safeQuantity;
        item.base_unit_price = baseUnitPrice;
        item.unit_price = adjustedUnitPrice;
        item.total = calculateLineTotal(safePackages, safeQuantity, adjustedUnitPrice);
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
            const normalizedTransaction = parsed?.currentTransaction || null;

            if (normalizedTransaction) {
                applyTransactionDefaults(normalizedTransaction);
            }

            return {
                itemsByReceipt: sanitizeItemsByReceipt(parsed?.itemsByReceipt),
                currentTransaction: normalizedTransaction,
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
                    ? receiptItems.map((item, index) => normalizeLine(item, key, index))
                    : [],
            ]),
        );
    }

    function normalizeLine(item, key, index) {
        const productId = normalizeProductId(item?.product_id ?? item?.product?.id);
        const hasVatRate = Object.prototype.hasOwnProperty.call(item || {}, 'vat_rate');
        const fallbackVatRate = productId ? 0 : DEFAULT_MANUAL_VAT_RATE;
        const baseUnitPrice = Math.max(0, Number((item?.base_unit_price ?? item?.unit_price) || 0));

        return {
            ...item,
            line_id: String(item?.line_id || item?.product?.id || `${key}-line-${index}`),
            product_id: productId,
            product: item?.product || {
                id: productId || `${key}-line-${index}`,
                name: 'Unknown product',
            },
            packages: Number(item?.packages || 1),
            quantity: Number(item?.quantity || 1),
            base_unit_price: baseUnitPrice,
            unit_price: Number(item?.unit_price || baseUnitPrice),
            vat_rate: Number(hasVatRate ? item?.vat_rate : fallbackVatRate),
            total: Number(item?.total || 0),
        };
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

        return roundMoney(safePackages * safeQuantity * safeUnitPrice);
    }

    function applyAdjustmentToUnitPrice(baseUnitPrice, resolvedAdjustment) {
        const safeBaseUnitPrice = Math.max(0, Number(baseUnitPrice) || 0);

        if (!resolvedAdjustment?.type || resolvedAdjustment.percent <= 0) {
            return safeBaseUnitPrice;
        }

        if (resolvedAdjustment.type === 'discount') {
            return roundMoney(safeBaseUnitPrice * (1 - (resolvedAdjustment.percent / 100)));
        }

        if (resolvedAdjustment.type === 'surcharge') {
            return roundMoney(safeBaseUnitPrice * (1 + (resolvedAdjustment.percent / 100)));
        }

        return safeBaseUnitPrice;
    }

    function clampPercent(value) {
        const numericValue = Number(value || 0);
        if (Number.isNaN(numericValue)) {
            return 0;
        }

        return Math.min(100, Math.max(0, roundMoney(numericValue)));
    }

    function normalizeAdjustmentType(value) {
        return value === 'discount' || value === 'surcharge' ? value : null;
    }

    function roundMoney(amount) {
        return Math.round((Number(amount || 0) + Number.EPSILON) * 100) / 100;
    }

    function createLineId(prefix = 'line') {
        return `${prefix}-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`;
    }

    function normalizeProductId(value) {
        const parsed = Number(value);
        return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
    }

    function applyTransactionDefaults(transaction) {
        if (!transaction) {
            return;
        }

        transaction.adjustment_type = normalizeAdjustmentType(transaction.adjustment_type);
        transaction.adjustment_percent = clampPercent(transaction.adjustment_percent ?? 0);
        transaction.adjustment_amount = roundMoney(transaction.adjustment_amount ?? 0);
    }
});
