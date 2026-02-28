<script setup>
import { computed } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    status: {
        type: String,
    },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <GuestLayout>
        <Head :title="$t('auth.email_verification')" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $t('auth.verify_your_email') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $t('auth.one_last_step') }}</p>
        </div>

        <div class="mb-4 text-sm text-slate-600">
            {{ $t('auth.thanks_for_signing_up') }}
        </div>

        <div
            class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700"
            v-if="verificationLinkSent"
        >
            {{ $t('auth.new_verification_link_sent') }}
        </div>

        <form @submit.prevent="submit">
            <div class="mt-4 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                <PrimaryButton
                    class="w-full justify-center sm:w-auto"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ $t('auth.resend_verification_email') }}
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="rounded-md text-center text-sm text-slate-600 underline decoration-teal-200 underline-offset-4 hover:text-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 sm:text-left"
                    >{{ $t('auth.log_out') }}</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
