<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    href: {
        type: String,
        required: true,
    },
    method: {
        type: String,
        default: 'get',
    },
    as: {
        type: String,
        default: 'a',
    },
});
</script>

<template>
    <Link
        :href="href"
        :method="method"
        :as="as"
        class="block w-full px-4 py-2 text-start text-sm leading-5 text-slate-700 transition duration-150 ease-in-out hover:bg-teal-50 hover:text-teal-700 focus:bg-teal-50 focus:text-teal-700 focus:outline-none"
    >
        <slot />
    </Link>
</template>
