<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    products: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const form = useForm({});

const deleteProduct = (id) => {
    if (confirm('Are you sure you want to delete this product?')) {
        form.delete(route('products.destroy', id));
    }
};

watch(search, (value) => {
    router.get(route('products.index'), { search: value }, {
        preserveState: true,
        replace: true,
    });
});

const formatPrice = (price) => {
    return new Intl.NumberFormat('cs-CZ', {
        style: 'currency',
        currency: 'CZK',
    }).format(price);
};

const isSearchActive = computed(() => Boolean(search.value?.trim()));
const isEmpty = computed(() => props.products.data.length === 0);
</script>

<template>
    <Head title="Products" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-2xl font-semibold text-slate-900">Products</h2>
                    <p class="mt-1 text-sm text-slate-600">Manage your catalog, pricing, and availability in one place.</p>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row sm:items-center sm:justify-end">
                    <div class="relative w-full sm:w-80">
                        <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <TextInput
                            id="product-search"
                            v-model="search"
                            type="text"
                            placeholder="Search by name, category, or EAN"
                            class="h-10 pl-10 pr-3 text-sm"
                        />
                    </div>
                    <Link
                        :href="route('products.create')"
                        class="inline-flex items-center justify-center gap-1.5 rounded-md border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:-translate-y-px hover:bg-teal-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Product
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl space-y-4 sm:px-6 lg:px-8">
                <div class="rounded-xl border border-teal-100 bg-white/90 shadow-sm shadow-teal-100/50">
                    <div v-if="isEmpty" class="px-6 py-16 text-center">
                        <svg class="mx-auto h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5" />
                        </svg>
                        <h3 class="mt-3 text-base font-semibold text-slate-900">{{ isSearchActive ? 'No matching products' : 'No products yet' }}</h3>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ isSearchActive ? 'Try a broader search term.' : 'Create your first product to start selling items.' }}
                        </p>
                        <Link
                            v-if="!isSearchActive"
                            :href="route('products.create')"
                            class="mt-5 inline-flex items-center rounded-md bg-teal-600 px-4 py-2 text-sm font-medium text-white transition-all duration-200 hover:bg-teal-700"
                        >
                            Create Product
                        </Link>
                    </div>

                    <div v-else>
                        <div class="hidden overflow-x-auto lg:block">
                            <table class="min-w-full border-collapse">
                                <thead>
                                    <tr class="bg-gradient-to-r from-teal-50/70 to-cyan-50/60">
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">Product</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">EAN</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">VAT</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">Price</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-teal-700/80">Status</th>
                                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-teal-700/80">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="product in products.data"
                                        :key="product.id"
                                        class="border-t border-teal-100/70 bg-teal-50/10 transition-colors duration-200 odd:bg-white even:bg-teal-50/15 hover:bg-teal-50/60"
                                    >
                                        <td class="px-5 py-4 align-top">
                                            <p class="text-sm font-semibold text-slate-900">{{ product.name }}</p>
                                            <p v-if="product.short_name" class="mt-0.5 text-xs text-slate-500">{{ product.short_name }}</p>
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <span class="inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-mono text-slate-700">
                                                {{ product.ean || '-' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <span class="inline-flex rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-800">{{ product.vat_rate }}%</span>
                                        </td>
                                        <td class="px-5 py-4 text-right align-top">
                                            <p class="text-sm font-semibold text-slate-900">{{ formatPrice(product.price) }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">incl. VAT</p>
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <span
                                                :class="product.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                                class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                            >
                                                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                                {{ product.is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 align-top">
                                            <div class="flex justify-end gap-2">
                                                <Link
                                                    :href="route('products.show', product.id)"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-600 transition-colors hover:bg-slate-100"
                                                    title="View"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </Link>
                                                <Link
                                                    :href="route('products.edit', product.id)"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-200 bg-amber-50 text-amber-700 transition-colors hover:bg-amber-100"
                                                    title="Edit"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                </Link>
                                                <button
                                                    @click="deleteProduct(product.id)"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-700 transition-colors hover:bg-rose-100"
                                                    title="Delete"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="space-y-3 p-4 lg:hidden">
                            <article v-for="product in products.data" :key="product.id" class="rounded-lg border border-slate-200 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-sm font-semibold text-slate-900">{{ product.name }}</h3>
                                        <p v-if="product.short_name" class="mt-1 text-xs text-slate-500">{{ product.short_name }}</p>
                                        <span class="mt-1 inline-flex rounded-md bg-slate-100 px-2 py-1 text-xs font-mono text-slate-700">
                                            EAN: {{ product.ean || 'N/A' }}
                                        </span>
                                    </div>
                                    <span
                                        :class="product.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                        {{ product.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-3 text-xs text-slate-600">
                                    <div>
                                        <p class="text-slate-500">VAT</p>
                                        <p class="mt-1 font-medium">{{ product.vat_rate }}%</p>
                                    </div>
                                    <div>
                                        <p class="text-slate-500">Price</p>
                                        <p class="mt-1 font-medium text-slate-900">{{ formatPrice(product.price) }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-3 gap-2">
                                    <Link
                                        :href="route('products.show', product.id)"
                                        class="inline-flex items-center justify-center rounded-md border border-slate-200 bg-white px-2 py-2 text-xs font-medium text-slate-700"
                                    >
                                        View
                                    </Link>
                                    <Link
                                        :href="route('products.edit', product.id)"
                                        class="inline-flex items-center justify-center rounded-md border border-amber-200 bg-amber-50 px-2 py-2 text-xs font-medium text-amber-700"
                                    >
                                        Edit
                                    </Link>
                                    <button
                                        @click="deleteProduct(product.id)"
                                        class="inline-flex items-center justify-center rounded-md border border-rose-200 bg-rose-50 px-2 py-2 text-xs font-medium text-rose-700"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>

                <div v-if="products.links && !isEmpty" class="flex justify-center">
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <template v-for="(link, index) in products.links" :key="`${index}-${link.label}-${link.url}`">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                :class="link.active
                                    ? 'inline-flex min-w-9 items-center justify-center rounded-lg border border-teal-600 bg-gradient-to-r from-teal-600 to-cyan-600 px-3 py-2 text-xs font-semibold text-white shadow-sm shadow-teal-300/30'
                                    : 'inline-flex min-w-9 items-center justify-center rounded-lg border border-teal-200 bg-teal-50/35 px-3 py-2 text-xs font-medium text-teal-800 transition-colors hover:bg-teal-50/70'"
                                v-html="link.label"
                            />
                            <span
                                v-else
                                class="inline-flex min-w-9 cursor-not-allowed items-center justify-center rounded-lg border border-slate-200 bg-slate-100/80 px-3 py-2 text-xs font-medium text-slate-400"
                                v-html="link.label"
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
