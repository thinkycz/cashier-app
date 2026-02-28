<script setup>
import BrandLogo from '@/Components/BrandLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    maxWidth: {
        type: String,
        default: 'md',
    },
});

const page = usePage();
const currentLocale = computed(() => page.props.locale || 'en');

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
        '4xl': 'sm:max-w-4xl',
        '5xl': 'sm:max-w-5xl',
    }[props.maxWidth];
});
</script>

<template>
    <div
        class="relative flex min-h-screen flex-col items-center overflow-hidden bg-gradient-to-br from-cyan-50 via-sky-50 to-teal-100 pt-8 sm:justify-center sm:pt-0"
    >
        <div class="absolute top-4 right-4 sm:top-6 sm:right-6 z-50">
            <Dropdown align="right" width="48">
                <template #trigger>
                    <span class="inline-flex">
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-teal-100 bg-white/80 text-xl shadow-sm transition duration-150 ease-in-out hover:bg-teal-50 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-1"
                            :aria-label="`Current language: ${currentLocale}`"
                        >
                            {{ currentLocale === 'cs' ? '🇨🇿' : '🇬🇧' }}
                        </button>
                    </span>
                </template>

                <template #content>
                    <DropdownLink :href="route('language.switch')" method="post" :data="{ language: 'en' }" as="button">
                        <span class="inline-flex items-center gap-2">
                            <span class="text-lg leading-none">🇬🇧</span>
                            <span>English</span>
                        </span>
                    </DropdownLink>
                    <DropdownLink :href="route('language.switch')" method="post" :data="{ language: 'cs' }" as="button">
                        <span class="inline-flex items-center gap-2">
                            <span class="text-lg leading-none">🇨🇿</span>
                            <span>Čeština</span>
                        </span>
                    </DropdownLink>
                </template>
            </Dropdown>
        </div>

        <div class="pointer-events-none absolute inset-0">
            <div class="absolute -top-12 -left-16 h-44 w-44 rounded-full bg-cyan-200/50 blur-2xl"></div>
            <div class="absolute top-14 right-8 h-56 w-56 rounded-full bg-teal-300/30 blur-3xl"></div>
        </div>

        <div class="w-full px-4 sm:px-0" :class="maxWidthClass">
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
