<script setup>
import { Head, Link } from '@inertiajs/vue3';
import BrandLogo from '@/Components/BrandLogo.vue';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Cashier App - Simple POS Solution" />

    <div class="min-h-screen bg-gradient-to-br from-cyan-50 via-sky-50 to-teal-100">
        <!-- Navigation -->
        <nav class="bg-white/85 shadow-sm backdrop-blur border-b border-teal-100/80">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <BrandLogo class="mr-3" />
                    </div>
                    <div class="flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="text-slate-600 hover:text-teal-700 px-3 py-2 rounded-md text-sm font-medium transition"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="text-slate-600 hover:text-teal-700 px-3 py-2 rounded-md text-sm font-medium transition"
                            >
                                Log in
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-md text-sm font-medium transition shadow-sm shadow-teal-300/40"
                            >
                                Get Started
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -top-12 -left-16 h-44 w-44 rounded-full bg-cyan-200/50 blur-2xl"></div>
                <div class="absolute top-16 right-8 h-56 w-56 rounded-full bg-teal-300/35 blur-3xl"></div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                <div class="text-center relative">
                    <h1 class="text-4xl md:text-6xl font-bold text-slate-900 mb-6 tracking-tight">
                        Simple POS Solution
                        <span class="block text-teal-600">for Your Business</span>
                    </h1>
                    <p class="text-xl text-slate-600 mb-8 max-w-2xl mx-auto">
                        Manage your products, handle transactions, and generate bills with ease.
                        A modern point-of-sale system designed for small to medium businesses.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <Link
                            v-if="$page.props.auth.user"
                            :href="route('dashboard')"
                            class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-lg text-lg font-medium transition duration-200 shadow-lg shadow-teal-300/30"
                        >
                            Go to Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-lg text-lg font-medium transition duration-200 shadow-lg shadow-teal-300/30"
                            >
                                Start Free Trial
                            </Link>
                            <Link
                                v-if="canLogin"
                                :href="route('login')"
                                class="border-2 border-teal-200 hover:border-teal-300 text-teal-700 hover:text-teal-800 bg-white/80 px-8 py-3 rounded-lg text-lg font-medium transition duration-200"
                            >
                                Sign In
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Preview -->
        <div class="pb-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="rounded-3xl border border-teal-100/80 bg-white/90 p-4 shadow-lg shadow-teal-100/30 backdrop-blur sm:p-6">
                    <svg class="w-full h-auto" viewBox="0 0 1160 620" fill="none" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Dashboard preview">
                        <defs>
                            <linearGradient id="preview-bg" x1="0" y1="0" x2="1160" y2="620" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#F8FAFC" />
                                <stop offset="1" stop-color="#F0FDFA" />
                            </linearGradient>
                            <linearGradient id="preview-panel" x1="26" y1="86" x2="1134" y2="592" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FFFFFF" />
                                <stop offset="1" stop-color="#F8FAFC" />
                            </linearGradient>
                            <linearGradient id="preview-surface" x1="40" y1="120" x2="1116" y2="584" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#F8FAFC" />
                                <stop offset="1" stop-color="#F1F5F9" />
                            </linearGradient>
                        </defs>

                        <rect x="1" y="1" width="1158" height="618" rx="20" fill="url(#preview-bg)" stroke="#D1FAE5" stroke-width="2" />
                        <rect x="28" y="24" width="1104" height="44" rx="10" fill="url(#preview-panel)" stroke="#EAF2F7" />
                        <rect x="46" y="38" width="164" height="12" rx="6" fill="#94A3B8" />
                        <rect x="980" y="36" width="130" height="16" rx="8" fill="#ECFEFF" />

                        <rect x="28" y="88" width="310" height="500" rx="16" fill="url(#preview-panel)" stroke="#F1F5F9" />
                        <rect x="45" y="106" width="276" height="92" rx="12" fill="url(#preview-surface)" />
                        <rect x="63" y="126" width="88" height="10" rx="5" fill="#94A3B8" />
                        <rect x="63" y="146" width="126" height="18" rx="7" fill="#334155" fill-opacity="0.75" />

                        <rect x="45" y="220" width="276" height="148" rx="12" fill="url(#preview-panel)" stroke="#F1F5F9" />
                        <rect x="65" y="244" width="230" height="10" rx="5" fill="#CBD5E1" />
                        <rect x="65" y="264" width="176" height="10" rx="5" fill="#E2E8F0" />
                        <rect x="65" y="294" width="230" height="10" rx="5" fill="#E2E8F0" />

                        <rect x="45" y="390" width="276" height="180" rx="12" fill="url(#preview-panel)" stroke="#F1F5F9" />
                        <rect x="65" y="418" width="112" height="10" rx="5" fill="#CBD5E1" />
                        <rect x="65" y="438" width="220" height="10" rx="5" fill="#E2E8F0" />

                        <rect x="360" y="88" width="772" height="96" rx="16" fill="url(#preview-panel)" stroke="#EAF2F7" />
                        <rect x="388" y="118" width="186" height="14" rx="7" fill="#64748B" />
                        <rect x="388" y="140" width="164" height="10" rx="5" fill="#CBD5E1" />
                        <rect x="1004" y="116" width="106" height="34" rx="8" fill="#E0F2FE" />

                        <rect x="360" y="204" width="772" height="146" rx="16" fill="url(#preview-panel)" stroke="#F1F5F9" />
                        <rect x="386" y="224" width="114" height="10" rx="5" fill="#94A3B8" />
                        <rect x="386" y="258" width="716" height="30" rx="9" fill="url(#preview-surface)" />
                        <rect x="386" y="298" width="716" height="30" rx="9" fill="url(#preview-surface)" />

                        <rect x="360" y="370" width="772" height="218" rx="16" fill="url(#preview-panel)" stroke="#F1F5F9" />
                        <rect x="386" y="392" width="122" height="10" rx="5" fill="#94A3B8" />
                        <rect x="386" y="426" width="360" height="34" rx="8" fill="#FFFFFF" stroke="#CBD5E1" />
                        <rect x="1004" y="426" width="98" height="34" rx="8" fill="#E0F2FE" />

                        <rect x="386" y="490" width="716" height="82" rx="10" fill="url(#preview-surface)" />
                        <line x1="386" y1="528" x2="1102" y2="528" stroke="#F8FAFC" stroke-width="2" />
                        <rect x="408" y="504" width="120" height="8" rx="4" fill="#CBD5E1" />
                        <rect x="934" y="538" width="150" height="12" rx="6" fill="#CCFBF1" />
                    </svg>
                </div>
            </div>
        </div>

        <!-- Features Section -->
        <div class="bg-gradient-to-b from-transparent via-white/30 to-cyan-50/65 py-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl font-bold text-slate-900 mb-4">Everything You Need</h2>
                    <p class="text-xl text-slate-600">Powerful features to streamline your operations</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="text-center p-6 bg-white rounded-2xl border border-teal-100 shadow-sm shadow-teal-100/60 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-teal-200/50">
                        <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4 ring-4 ring-teal-50">
                            <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">Product Management</h3>
                        <p class="text-slate-600">Easily add, update, and organize your product catalog with detailed information and pricing.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="text-center p-6 bg-white rounded-2xl border border-teal-100 shadow-sm shadow-teal-100/60 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-teal-200/50">
                        <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4 ring-4 ring-teal-50">
                            <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">Transaction Handling</h3>
                        <p class="text-slate-600">Process sales quickly and efficiently with real-time transaction tracking and receipt generation.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="text-center p-6 bg-white rounded-2xl border border-teal-100 shadow-sm shadow-teal-100/60 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-teal-200/50">
                        <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mx-auto mb-4 ring-4 ring-teal-50">
                            <svg class="w-8 h-8 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-slate-900 mb-2">Bill Management</h3>
                        <p class="text-slate-600">Generate professional invoices and bills with automatic calculations and customer details.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white/70 backdrop-blur border-t border-teal-100/50 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <p class="text-slate-600"> 2024 Cashier App. Built for modern businesses.</p>
            </div>
        </footer>
    </div>
</template>
