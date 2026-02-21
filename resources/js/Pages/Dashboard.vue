<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { useCartStore } from '@/stores/cart';

const cart = useCartStore();

const props = defineProps({
    products: Array,
    openTransactions: Array,
    customers: Array,
});

const searchQuery = ref('');
const searchType = ref('name');

const filteredProducts = computed(() => {
    if (!searchQuery.value) return props.products;
    
    const query = searchQuery.value.toLowerCase();
    return props.products.filter(product => {
        if (searchType.value === 'name') {
            return product.name.toLowerCase().includes(query);
        } else if (searchType.value === 'category') {
            return product.category?.toLowerCase().includes(query);
        } else if (searchType.value === 'ean') {
            return product.ean?.toLowerCase().includes(query);
        }
        return false;
    });
});

const addToCart = (product) => {
    cart.addItem(product);
};

const formatPrice = (price) => {
    return new Intl.NumberFormat('cs-CZ', {
        style: 'currency',
        currency: 'CZK'
    }).format(price);
};

const createNewTransaction = () => {
    cart.clearCart();
    cart.setTransaction({
        id: null,
        transaction_id: '',
        discount: 0,
        status: 'open'
    });
};

const selectTransaction = (transaction) => {
    cart.setTransaction(transaction);
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Point of Sale
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column - Current Transaction -->
                    <div class="lg:col-span-2 space-y-4">
                        <!-- Transaction Header -->
                        <div class="bg-white rounded-lg shadow p-4">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="text-lg font-semibold">
                                        {{ cart.currentTransaction?.transaction_id || 'Nová účtenka' }}
                                    </h3>
                                    <p class="text-sm text-gray-600">
                                        {{ cart.selectedCustomer?.name || 'Žádný zákazník' }}
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <button class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">
                                        Sleva a přirážka
                                    </button>
                                    <button class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600">
                                        Vybrat zákazníka
                                    </button>
                                    <button 
                                        @click="createNewTransaction"
                                        class="px-3 py-1 bg-gray-500 text-white rounded hover:bg-gray-600"
                                    >
                                        Nová účtenka
                                    </button>
                                </div>
                            </div>

                            <!-- Cart Items -->
                            <div class="mb-4">
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b">
                                                <th class="text-left py-2">#</th>
                                                <th class="text-left py-2">POČET</th>
                                                <th class="text-left py-2">CENA ZA MJ</th>
                                                <th class="text-left py-2">CELKEM</th>
                                                <th class="text-left py-2"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-if="cart.items.length === 0">
                                                <td colspan="5" class="text-center py-4 text-gray-500">
                                                    Košík je prázdný
                                                </td>
                                            </tr>
                                            <tr v-for="(item, index) in cart.items" :key="item.product.id" class="border-b">
                                                <td class="py-2">{{ index + 1 }}</td>
                                                <td class="py-2">{{ item.quantity }}</td>
                                                <td class="py-2">{{ formatPrice(item.unit_price) }}</td>
                                                <td class="py-2">{{ formatPrice(item.total) }}</td>
                                                <td class="py-2">
                                                    <button 
                                                        @click="cart.removeItem(item.product.id)"
                                                        class="text-red-500 hover:text-red-700"
                                                    >
                                                        Odstranit
                                                    </button>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Total -->
                            <div class="border-t pt-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-lg font-semibold">Celkem:</span>
                                    <span class="text-2xl font-bold">{{ formatPrice(cart.total) }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Product Search -->
                        <div class="bg-white rounded-lg shadow p-4">
                            <h3 class="text-lg font-semibold mb-4">NAJÍT PRODUKT</h3>
                            <div class="flex space-x-2 mb-4">
                                <input
                                    v-model="searchQuery"
                                    type="text"
                                    placeholder="Hledat produkt..."
                                    class="flex-1 px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                                <select 
                                    v-model="searchType"
                                    class="px-3 py-2 border rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                                >
                                    <option value="name">Název</option>
                                    <option value="category">CAT</option>
                                    <option value="ean">EAN</option>
                                </select>
                                <button class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                                    Vytvořit produkt
                                </button>
                            </div>

                            <!-- Product List -->
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b">
                                            <th class="text-left py-2">PRODUKT</th>
                                            <th class="text-left py-2">CAT</th>
                                            <th class="text-left py-2">EAN</th>
                                            <th class="text-left py-2">SAZBA DPH</th>
                                            <th class="text-left py-2">CENA</th>
                                            <th class="text-left py-2"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="product in filteredProducts" :key="product.id" class="border-b hover:bg-gray-50">
                                            <td class="py-2">{{ product.name }}</td>
                                            <td class="py-2">{{ product.category || '-' }}</td>
                                            <td class="py-2">{{ product.ean || '-' }}</td>
                                            <td class="py-2">{{ product.vat_rate }}%</td>
                                            <td class="py-2">{{ formatPrice(product.price) }}</td>
                                            <td class="py-2">
                                                <button 
                                                    @click="addToCart(product)"
                                                    class="px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600"
                                                >
                                                    Přidat
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column - Open Receipts -->
                    <div class="space-y-4">
                        <div class="bg-white rounded-lg shadow p-4">
                            <h3 class="text-lg font-semibold mb-4">OTEVŘENÉ ÚČTENKY</h3>
                            <div class="space-y-2">
                                <div 
                                    v-for="transaction in openTransactions" 
                                    :key="transaction.id"
                                    @click="selectTransaction(transaction)"
                                    class="p-3 border rounded hover:bg-gray-50 cursor-pointer"
                                >
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium">{{ transaction.transaction_id }}</span>
                                        <span>{{ formatPrice(transaction.total) }}</span>
                                    </div>
                                    <div class="text-sm text-gray-600">
                                        {{ transaction.customer?.name || 'Bez zákazníka' }}
                                    </div>
                                </div>
                                <div v-if="openTransactions.length === 0" class="text-center py-4 text-gray-500">
                                    Žádné otevřené účtenky
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
