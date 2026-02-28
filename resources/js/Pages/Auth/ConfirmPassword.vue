<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="$t('auth.confirm_password_title')" />

        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $t('auth.confirm_password_heading') }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $t('auth.secure_action_requires') }}</p>
        </div>

        <div class="mb-4 text-sm text-slate-600">
            {{ $t('auth.secure_area') }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="password" :value="$t('auth.password')" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4 flex justify-end">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ $t('auth.confirm') }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
