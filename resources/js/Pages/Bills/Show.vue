<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    bill: Object,
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('cs-CZ', {
        style: 'currency',
        currency: 'CZK'
    }).format(price);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('cs-CZ', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getStatusColor = (status) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'open':
            return 'bg-yellow-100 text-yellow-800';
        case 'cancelled':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const printBill = () => {
    window.print();
};
</script>

<template>
    <Head :title="`Bill - ${bill.transaction_id}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Bill Details
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="mb-6 flex justify-between items-start">
                            <div>
                                <Link
                                    :href="route('bills.index')"
                                    class="text-blue-600 hover:text-blue-800"
                                >
                                    ← Back to Bills
                                </Link>
                            </div>
                            <div class="flex space-x-3">
                                <button
                                    @click="printBill"
                                    class="px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500"
                                >
                                    Print Bill
                                </button>
                            </div>
                        </div>

                        <!-- Bill Header -->
                        <div class="mb-8 border-b pb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Transaction Information</h3>
                                    <dl class="space-y-1">
                                        <div class="flex">
                                            <dt class="text-sm font-medium text-gray-600 w-32">Transaction ID:</dt>
                                            <dd class="text-sm text-gray-900">{{ bill.transaction_id }}</dd>
                                        </div>
                                        <div class="flex">
                                            <dt class="text-sm font-medium text-gray-600 w-32">Date:</dt>
                                            <dd class="text-sm text-gray-900">{{ formatDate(bill.created_at) }}</dd>
                                        </div>
                                        <div class="flex">
                                            <dt class="text-sm font-medium text-gray-600 w-32">Status:</dt>
                                            <dd>
                                                <span
                                                    :class="getStatusColor(bill.status)"
                                                    class="px-2 py-1 text-xs rounded-full capitalize"
                                                >
                                                    {{ bill.status }}
                                                </span>
                                            </dd>
                                        </div>
                                    </dl>
                                </div>

                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Customer Information</h3>
                                    <dl class="space-y-1">
                                        <div class="flex">
                                            <dt class="text-sm font-medium text-gray-600 w-32">Name:</dt>
                                            <dd class="text-sm text-gray-900">{{ bill.customer?.name || 'No customer' }}</dd>
                                        </div>
                                        <div v-if="bill.customer?.email" class="flex">
                                            <dt class="text-sm font-medium text-gray-600 w-32">Email:</dt>
                                            <dd class="text-sm text-gray-900">{{ bill.customer.email }}</dd>
                                        </div>
                                        <div v-if="bill.customer?.phone" class="flex">
                                            <dt class="text-sm font-medium text-gray-600 w-32">Phone:</dt>
                                            <dd class="text-sm text-gray-900">{{ bill.customer.phone }}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                        </div>

                        <!-- Bill Items -->
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Items</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left">#</th>
                                            <th class="px-4 py-3 text-left">Product</th>
                                            <th class="px-4 py-3 text-center">Quantity</th>
                                            <th class="px-4 py-3 text-right">Unit Price</th>
                                            <th class="px-4 py-3 text-right">VAT Rate</th>
                                            <th class="px-4 py-3 text-right">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(item, index) in bill.transaction_items"
                                            :key="item.id"
                                            class="border-b hover:bg-gray-50"
                                        >
                                            <td class="px-4 py-3">{{ index + 1 }}</td>
                                            <td class="px-4 py-3 font-medium">{{ item.product.name }}</td>
                                            <td class="px-4 py-3 text-center">{{ item.quantity }}</td>
                                            <td class="px-4 py-3 text-right">{{ formatPrice(item.unit_price) }}</td>
                                            <td class="px-4 py-3 text-right">{{ item.vat_rate }}%</td>
                                            <td class="px-4 py-3 text-right font-semibold">{{ formatPrice(item.total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Bill Summary -->
                        <div class="border-t pt-6">
                            <div class="max-w-xs ml-auto">
                                <dl class="space-y-2">
                                    <div class="flex justify-between">
                                        <dt class="text-sm text-gray-600">Subtotal:</dt>
                                        <dd class="text-sm text-gray-900">{{ formatPrice(bill.subtotal) }}</dd>
                                    </div>
                                    <div v-if="bill.discount > 0" class="flex justify-between">
                                        <dt class="text-sm text-gray-600">Discount:</dt>
                                        <dd class="text-sm text-red-600">-{{ formatPrice(bill.discount) }}</dd>
                                    </div>
                                    <div class="flex justify-between pt-2 border-t">
                                        <dt class="text-lg font-semibold text-gray-900">Total:</dt>
                                        <dd class="text-lg font-bold text-gray-900">{{ formatPrice(bill.total) }}</dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div v-if="bill.notes" class="mt-6 border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Notes</h3>
                            <p class="text-sm text-gray-600">{{ bill.notes }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
