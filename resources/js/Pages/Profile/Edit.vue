<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ mustVerifyEmail: Boolean, status: String });
</script>

<template>
    <Head title="إعدادات الحساب" />
    <AuthenticatedLayout>
        <template #header>
            <div>
                <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">الحساب والأمان</p>
                <h1 class="mt-1 text-2xl font-bold text-slate-950 dark:text-white">إعدادات حساب المالك</h1>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">حدّث معلومات الدخول وكلمة المرور الخاصة بحساب فنانة فون.</p>
            </div>
        </template>

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" />
            </section>
            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <UpdatePasswordForm />
            </section>
        </div>
    </AuthenticatedLayout>
</template>
