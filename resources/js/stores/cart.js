import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useCartStore = defineStore('cart', () => {
    const items = ref([]);
    const currentTransaction = ref(null);
    const selectedCustomer = ref(null);

    const subtotal = computed(() => {
        return items.value.reduce((total, item) => total + (item.total), 0);
    });

    const total = computed(() => {
        return subtotal.value - (currentTransaction.value?.discount || 0);
    });

    const itemCount = computed(() => {
        return items.value.reduce((count, item) => count + item.quantity, 0);
    });

    function addItem(product, quantity = 1) {
        const existingItem = items.value.find(item => item.product.id === product.id);
        
        if (existingItem) {
            existingItem.quantity += quantity;
            existingItem.total = existingItem.quantity * existingItem.unit_price;
        } else {
            items.value.push({
                product,
                quantity,
                unit_price: product.price,
                vat_rate: product.vat_rate,
                total: product.price * quantity
            });
        }
    }

    function removeItem(productId) {
        const index = items.value.findIndex(item => item.product.id === productId);
        if (index > -1) {
            items.value.splice(index, 1);
        }
    }

    function updateQuantity(productId, quantity) {
        const item = items.value.find(item => item.product.id === productId);
        if (item) {
            item.quantity = quantity;
            item.total = item.quantity * item.unit_price;
        }
    }

    function clearCart() {
        items.value = [];
        currentTransaction.value = null;
        selectedCustomer.value = null;
    }

    function setTransaction(transaction) {
        currentTransaction.value = transaction;
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
});
