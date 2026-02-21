<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');

const form = useForm({});

const deleteProduct = (id) => {
    if (confirm('Are you sure you want to delete this product?')) {
        form.delete(route('products.destroy', id));
    }
};

watch(search, (value) => {
    form.get(route('products.index'), { search: value }, {
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
</script>

<template>
    <Head title="Products" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Products Management
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div class="flex-1 max-w-md">
                                <input
                                    v-model="search"
                                    type="text"
                                    placeholder="Search products..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                />
                            </div>
                            <Link
                                :href="route('products.create')"
                                class="ml-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                Create Product
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3">Name</th>
                                        <th class="px-6 py-3">Category</th>
                                        <th class="px-6 py-3">EAN</th>
                                        <th class="px-6 py-3">VAT Rate</th>
                                        <th class="px-6 py-3">Price</th>
                                        <th class="px-6 py-3">Status</th>
                                        <th class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="product in products.data"
                                        :key="product.id"
                                        class="bg-white border-b hover:bg-gray-50"
                                    >
                                        <td class="px-6 py-4 font-medium text-gray-900">
                                            {{ product.name }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ product.category || '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ product.ean || '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ product.vat_rate }}%
                                        </td>
                                        <td class="px-6 py-4">
                                            {{ formatPrice(product.price) }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span
                                                :class="product.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                class="px-2 py-1 text-xs rounded-full"
                                            >
                                                {{ product.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex space-x-2">
                                                <Link
                                                    :href="route('products.show', product.id)"
                                                    class="text-blue-600 hover:text-blue-900"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    :href="route('products.edit', product.id)"
                                                    class="text-yellow-600 hover:text-yellow-900"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    @click="deleteProduct(product.id)"
                                                    class="text-red-600 hover:text-red-900"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-if="products.links" class="mt-6">
                            <div class="flex justify-center">
                                <nav class="flex space-x-2">
                                    <template v-for="link in products.links" :key="link.label">
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
