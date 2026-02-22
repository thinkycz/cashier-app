import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useCartStore = defineStore('cart', () => {
    const itemsByReceipt = ref({});
    const currentTransaction = ref(null);
    const selectedCustomer = ref(null);

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
    }

    function removeItem(productId) {
        const currentItems = getCurrentItems();
        if (!currentItems) {
            return;
        }

        const index = currentItems.findIndex(item => item.product.id === productId);
        if (index > -1) {
            currentItems.splice(index, 1);
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
        }
    }

    function clearCart() {
        itemsByReceipt.value = {};
        currentTransaction.value = null;
        selectedCustomer.value = null;
    }

    function setTransaction(transaction) {
        currentTransaction.value = transaction;

        if (!currentReceiptKey.value) {
            return;
        }

        if (!itemsByReceipt.value[currentReceiptKey.value]) {
            const transactionItems = transaction?.transaction_items || [];
            itemsByReceipt.value[currentReceiptKey.value] = transactionItems.map((item) => ({
                product: item.product,
                quantity: Number(item.quantity),
                unit_price: Number(item.unit_price),
                vat_rate: Number(item.vat_rate),
                total: Number(item.total),
            }));
        }

        selectedCustomer.value = transaction?.customer || null;
    }

    function setCustomer(customer) {
        selectedCustomer.value = customer;
    }

    function setDiscount(amount) {
        if (currentTransaction.value) {
            currentTransaction.value.discount = amount;
        }
    }

    return {
        items,
        currentTransaction,
        selectedCustomer,
        subtotal,
        total,
        itemCount,
        addItem,
        removeItem,
        updateQuantity,
        clearCart,
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
});
