<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    product: Object,
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('cs-CZ', {
        style: 'currency',
        currency: 'CZK'
    }).format(price);
};
</script>

<template>
    <Head :title="`Product - ${product.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Product Details
            </h2>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="mb-6">
                            <Link
                                :href="route('products.index')"
                                class="text-blue-600 hover:text-blue-800"
                            >
                                ← Back to Products
                            </Link>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                <dl class="space-y-2">
                                    <div class="flex justify-between py-2 border-b">
                                        <dt class="text-sm font-medium text-gray-600">Name:</dt>
                                        <dd class="text-sm text-gray-900">{{ product.name }}</dd>
                                    </div>
                                    <div class="flex justify-between py-2 border-b">
                                        <dt class="text-sm font-medium text-gray-600">Category:</dt>
                                        <dd class="text-sm text-gray-900">{{ product.category || '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between py-2 border-b">
                                        <dt class="text-sm font-medium text-gray-600">EAN:</dt>
                                        <dd class="text-sm text-gray-900">{{ product.ean || '-' }}</dd>
                                    </div>
                                    <div class="flex justify-between py-2 border-b">
                                        <dt class="text-sm font-medium text-gray-600">Status:</dt>
                                        <dd>
                                            <span
                                                :class="product.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'"
                                                class="px-2 py-1 text-xs rounded-full"
                                            >
                                                {{ product.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div>
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Pricing</h3>
                                <dl class="space-y-2">
                                    <div class="flex justify-between py-2 border-b">
                                        <dt class="text-sm font-medium text-gray-600">Price:</dt>
                                        <dd class="text-sm text-gray-900 font-semibold">{{ formatPrice(product.price) }}</dd>
                                    </div>
                                    <div class="flex justify-between py-2 border-b">
                                        <dt class="text-sm font-medium text-gray-600">VAT Rate:</dt>
                                        <dd class="text-sm text-gray-900">{{ product.vat_rate }}%</dd>
                                    </div>
                                    <div class="flex justify-between py-2 border-b">
                                        <dt class="text-sm font-medium text-gray-600">Price without VAT:</dt>
                                        <dd class="text-sm text-gray-900">
                                            {{ formatPrice(product.price / (1 + product.vat_rate / 100)) }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <div v-if="product.description" class="mt-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Description</h3>
                            <p class="text-sm text-gray-600">{{ product.description }}</p>
                        </div>

                        <div class="mt-8 flex justify-end space-x-3">
                            <Link
                                :href="route('products.edit', product.id)"
                                class="px-4 py-2 bg-yellow-500 text-white rounded-md hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                            >
                                Edit Product
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
