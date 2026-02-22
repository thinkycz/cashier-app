<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const userInitial = computed(() => {
    const name = page.props.auth?.user?.name ?? '';
    return name.trim().charAt(0).toUpperCase() || '?';
});
</script>

<template>
    <Head title="Profile" />

    <AuthenticatedLayout>
        <div class="py-10 sm:py-12">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section
                    class="overflow-hidden rounded-2xl border border-teal-100/80 bg-gradient-to-r from-teal-600 to-cyan-600 p-6 text-white shadow-lg shadow-teal-900/10 sm:p-8"
                >
                    <div
                        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <p class="text-sm font-medium text-teal-100">
                                Account center
                            </p>
                            <h1 class="mt-1 text-2xl font-semibold sm:text-3xl">
                                Manage your profile
                            </h1>
                            <p class="mt-2 max-w-2xl text-sm text-cyan-100">
                                Update your personal details, keep your password
                                secure, and control account access in one place.
                            </p>
                        </div>
                        <div
                            class="inline-flex h-14 w-14 items-center justify-center rounded-full border border-white/35 bg-white/20 text-xl font-semibold"
                            :title="$page.props.auth.user.name"
                        >
                            {{ userInitial }}
                        </div>
                    </div>
                </section>

                <div
                    class="rounded-2xl border border-teal-100/80 bg-white p-6 shadow-sm shadow-teal-900/5 sm:p-8"
                >
                    <UpdateProfileInformationForm
                        :must-verify-email="mustVerifyEmail"
                        :status="status"
                        class="max-w-xl"
                    />
                </div>

                <div
                    class="rounded-2xl border border-teal-100/80 bg-white p-6 shadow-sm shadow-teal-900/5 sm:p-8"
                >
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <div
                    class="rounded-2xl border border-red-100 bg-white p-6 shadow-sm shadow-red-900/5 sm:p-8"
                >
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
