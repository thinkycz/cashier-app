<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    transactions: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const form = useForm({});

watch([search, status], () => {
    form.get(route('bills.index'), { 
        search: search.value, 
        status: status.value 
    }, {
        preserveState: true,
        replace: true,
    });
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
</script>

<template>
    <Head title="Bills" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Bills Management
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
                            <div class="flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-4 flex-1">
                                <div class="flex-1 max-w-md">
                                    <input
                                        v-model="search"
                                        type="text"
                                        placeholder="Search by transaction ID or customer..."
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    />
                                </div>
                                <div>
                                    <select
                                        v-model="status"
                                        class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    >
                                        <option value="">All Status</option>
                                        <option value="open">Open</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Transaction ID</th>
                                        <th class="px-6 py-3">Customer</th>
                                        <th class="px-6 py-3">Date</th>
                                        <th class="px-6 py-3">Subtotal</th>
                                        <th class="px-6 py-3">Discount</th>
                                        <th class="px-6 py-3">Total</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="transaction in transactions.data"
                                        :key="transaction.id"
                                        class="bg-white border-b hover:bg-gray-50"
                                    >
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            {{ transaction.transaction_id }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ transaction.customer?.name || 'No customer' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ formatDate(transaction.created_at) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ formatPrice(transaction.subtotal) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ formatPrice(transaction.discount) }}
                                        </td>
                                        <td class="px-6 py-4 font-semibold">
                                            {{ formatPrice(transaction.total) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                :class="getStatusColor(transaction.status)"
                                                class="px-2 py-1 text-xs rounded-full capitalize"
                                            >
                                                {{ transaction.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <Link
                                                :href="route('bills.show', transaction.id)"
                                                class="text-blue-600 hover:text-blue-900"
                                            >
                                                View
                                            </Link>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="transactions.data.length === 0" class="text-center py-8 text-gray-500">
                            No transactions found.
                        </div>

                        <div v-if="transactions.links" class="mt-6">
                            <div class="flex justify-center">
                                <nav class="flex space-x-2">
                                    <template v-for="link in transactions.links" :key="link.label">
                                        <Link
                                            v-if="link.url"
                                            :href="link.url"
                                            v-html="link.label"
                                            :class="link.active 
                                                ? 'px-3 py-2 bg-blue-500 text-white rounded-md' 
                                                : 'px-3 py-2 bg-white text-gray-700 border border-gray-300 rounded-md hover:bg-gray-50'"
                                            class="inline-flex items-center"
                                        />
                                        <span
                                            v-else
                                            v-html="link.label"
                                            class="px-3 py-2 text-gray-400 border border-gray-300 rounded-md"
                                        />
                                    </template>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
