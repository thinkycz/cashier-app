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
                                <stop offset="0.6" stop-color="#EFFCF9" />
                                <stop offset="1" stop-color="#ECFEFF" />
                            </linearGradient>
                            <linearGradient id="preview-panel" x1="30" y1="90" x2="1130" y2="590" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FFFFFF" />
                                <stop offset="1" stop-color="#F3F8FC" />
                            </linearGradient>
                            <linearGradient id="preview-toolbar" x1="360" y1="86" x2="1130" y2="182" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#0F766E" />
                                <stop offset="1" stop-color="#0891B2" />
                            </linearGradient>
                            <linearGradient id="preview-table" x1="360" y1="360" x2="1130" y2="590" gradientUnits="userSpaceOnUse">
                                <stop stop-color="#FFFFFF" />
                                <stop offset="1" stop-color="#F0FDFA" />
                            </linearGradient>
                            <radialGradient id="preview-glow-a" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(1040 120) rotate(129.23) scale(184 128)">
                                <stop stop-color="#67E8F9" stop-opacity="0.55" />
                                <stop offset="1" stop-color="#67E8F9" stop-opacity="0" />
                            </radialGradient>
                            <radialGradient id="preview-glow-b" cx="0" cy="0" r="1" gradientUnits="userSpaceOnUse" gradientTransform="translate(210 510) rotate(-20.5) scale(180 120)">
                                <stop stop-color="#5EEAD4" stop-opacity="0.45" />
                                <stop offset="1" stop-color="#5EEAD4" stop-opacity="0" />
                            </radialGradient>
                        </defs>

                        <rect x="1" y="1" width="1158" height="618" rx="20" fill="url(#preview-bg)" stroke="#CCFBF1" stroke-width="2" />
                        <rect x="760" y="36" width="340" height="220" fill="url(#preview-glow-a)" />
                        <rect x="42" y="382" width="340" height="220" fill="url(#preview-glow-b)" />

                        <rect x="28" y="24" width="1104" height="44" rx="10" fill="url(#preview-panel)" stroke="#DCEAF2" />
                        <circle cx="48" cy="46" r="5" fill="#14B8A6" />
                        <circle cx="64" cy="46" r="5" fill="#67E8F9" />
                        <circle cx="80" cy="46" r="5" fill="#99F6E4" />
                        <rect x="106" y="40" width="178" height="12" rx="6" fill="#7C8EA3" />
                        <rect x="1008" y="36" width="104" height="20" rx="10" fill="#ECFEFF" />

                        <rect x="28" y="88" width="304" height="500" rx="16" fill="url(#preview-panel)" stroke="#E6F1F6" />
                        <rect x="48" y="112" width="264" height="64" rx="12" fill="#F0FDFA" />
                        <rect x="66" y="128" width="96" height="10" rx="5" fill="#5F748B" />
                        <rect x="66" y="146" width="146" height="12" rx="6" fill="#9DB0C2" />
                        <rect x="48" y="196" width="264" height="2" rx="1" fill="#E2E8F0" />

                        <rect x="48" y="222" width="264" height="118" rx="12" fill="#FFFFFF" stroke="#E7EEF4" />
                        <rect x="64" y="242" width="118" height="9" rx="4.5" fill="#6A7F95" />
                        <rect x="64" y="262" width="220" height="8" rx="4" fill="#D3DEE8" />
                        <rect x="64" y="278" width="172" height="8" rx="4" fill="#E2EAF1" />
                        <rect x="64" y="300" width="220" height="24" rx="7" fill="#ECFEFF" />

                        <rect x="48" y="362" width="264" height="206" rx="12" fill="#FFFFFF" stroke="#E7EEF4" />
                        <rect x="64" y="386" width="142" height="9" rx="4.5" fill="#6A7F95" />
                        <rect x="64" y="410" width="220" height="8" rx="4" fill="#D3DEE8" />
                        <rect x="64" y="426" width="182" height="8" rx="4" fill="#E2EAF1" />
                        <rect x="64" y="458" width="220" height="86" rx="10" fill="#F0FDFA" />
                        <rect x="82" y="478" width="126" height="8" rx="4" fill="#8AA0B6" />
                        <rect x="82" y="500" width="184" height="24" rx="7" fill="#FFFFFF" stroke="#D8E6EE" />

                        <rect x="360" y="88" width="772" height="112" rx="16" fill="url(#preview-toolbar)" />
                        <rect x="388" y="116" width="204" height="14" rx="7" fill="#E6FFFB" fill-opacity="0.9" />
                        <rect x="388" y="140" width="168" height="10" rx="5" fill="#BDFCF3" fill-opacity="0.8" />
                        <rect x="988" y="114" width="122" height="36" rx="10" fill="#ECFEFF" />

                        <rect x="360" y="224" width="372" height="130" rx="16" fill="#FFFFFF" stroke="#E6F1F6" />
                        <rect x="378" y="246" width="130" height="10" rx="5" fill="#6D8298" />
                        <rect x="378" y="270" width="332" height="12" rx="6" fill="#D9E4ED" />
                        <rect x="378" y="292" width="208" height="12" rx="6" fill="#E5EDF3" />
                        <rect x="378" y="316" width="332" height="18" rx="8" fill="#ECFEFF" />

                        <rect x="760" y="224" width="372" height="130" rx="16" fill="#FFFFFF" stroke="#E6F1F6" />
                        <rect x="778" y="246" width="148" height="10" rx="5" fill="#6D8298" />
                        <rect x="778" y="272" width="334" height="64" rx="10" fill="#F0FDFA" />
                        <path d="M796 314C828 266 860 326 892 288C924 250 956 320 988 294C1018 268 1050 332 1084 300" stroke="#14B8A6" stroke-width="3" stroke-linecap="round" />
                        <circle cx="892" cy="288" r="5" fill="#06B6D4" />

                        <rect x="360" y="374" width="772" height="214" rx="16" fill="url(#preview-table)" stroke="#DDECF4" />
                        <rect x="384" y="398" width="146" height="10" rx="5" fill="#6D8298" />
                        <rect x="384" y="424" width="424" height="34" rx="9" fill="#FFFFFF" stroke="#D3E3EC" />
                        <rect x="1010" y="424" width="98" height="34" rx="9" fill="#CCFBF1" />
                        <rect x="384" y="476" width="724" height="90" rx="12" fill="#FFFFFF" stroke="#E5EEF4" />
                        <line x1="384" y1="508" x2="1108" y2="508" stroke="#E9F2F7" stroke-width="2" />
                        <line x1="384" y1="536" x2="1108" y2="536" stroke="#E9F2F7" stroke-width="2" />
                        <rect x="404" y="488" width="148" height="8" rx="4" fill="#9FB2C4" />
                        <rect x="958" y="488" width="132" height="10" rx="5" fill="#D6FBF4" />
                        <rect x="404" y="516" width="106" height="8" rx="4" fill="#CCD8E3" />
                        <rect x="972" y="516" width="118" height="10" rx="5" fill="#E0F2FE" />
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
