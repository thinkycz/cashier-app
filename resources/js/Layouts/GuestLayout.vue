<script setup>
import BrandLogo from '@/Components/BrandLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center overflow-hidden bg-gradient-to-br from-cyan-50 via-sky-50 to-teal-100 pt-8 sm:justify-center sm:pt-0"
    >
        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-12 -left-16 h-44 w-44 rounded-full bg-cyan-200/50 blur-2xl"></div>
            <div class="absolute top-14 right-8 h-56 w-56 rounded-full bg-teal-300/30 blur-3xl"></div>
        </div>

        <div class="w-full px-4 sm:max-w-md sm:px-0">
            <div class="flex justify-center">
                <Link href="/" class="inline-flex justify-center">
                    <BrandLogo class="mx-auto block" />
                </Link>
            </div>

            <div
                class="relative mt-6 w-full overflow-hidden rounded-2xl border border-teal-100/80 bg-white/90 px-6 py-6 shadow-xl shadow-teal-200/30"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
