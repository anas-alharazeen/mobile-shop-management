<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({ mustVerifyEmail: Boolean, status: String });
const user = usePage().props.auth.user;
const form = useForm({ name: user.name, email: user.email });
</script>

<template>
    <section>
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-indigo-100 text-lg font-black text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">{{ user.name?.slice(0, 1) }}</div>
            <div><h2 class="text-lg font-bold text-slate-950 dark:text-white">معلومات الحساب</h2><p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">الاسم والبريد المستخدمان للدخول إلى لوحة الإدارة.</p></div>
        </div>
        <form class="mt-6 space-y-5" @submit.prevent="form.patch(route('profile.update'), { preserveScroll: true })">
            <div><label for="owner-name" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">اسم المالك</label><input id="owner-name" v-model="form.name" type="text" autocomplete="name" required class="w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"/><p v-if="form.errors.name" class="mt-1.5 text-xs text-rose-600">{{ form.errors.name }}</p></div>
            <div><label for="owner-email" class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">البريد الإلكتروني</label><input id="owner-email" v-model="form.email" type="email" autocomplete="username" required class="w-full rounded-xl border-slate-300 bg-white text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"/><p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-600">{{ form.errors.email }}</p></div>
            <div v-if="mustVerifyEmail && user.email_verified_at === null" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300">البريد غير موثّق. <Link :href="route('verification.send')" method="post" as="button" class="font-bold underline">إعادة إرسال رابط التحقق</Link><p v-if="status === 'verification-link-sent'" class="mt-2 font-semibold text-emerald-700 dark:text-emerald-300">تم إرسال رابط جديد.</p></div>
            <div class="flex items-center gap-3"><button type="submit" :disabled="form.processing" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:opacity-50">{{ form.processing ? 'جارٍ الحفظ...' : 'حفظ المعلومات' }}</button><span v-if="form.recentlySuccessful" class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">تم الحفظ بنجاح</span></div>
        </form>
    </section>
</template>
