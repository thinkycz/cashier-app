<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('auth.forgot_password_title')" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $t('auth.forgot_password_heading') }}</h1>
            <p class="mt-1 text-sm text-slate-600">
                {{ $t('auth.enter_email_link') }}
            </p>
        </div>

        <div class="mb-4 text-sm text-slate-600">
            {{ $t('auth.forgot_password_desc') }}
        </div>

        <div
            v-if="status"
            class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="email" :value="$t('auth.email')" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4 flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ $t('auth.email_password_reset_link') }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
