<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
defineProps({ canResetPassword: Boolean, status: String });
const showPassword = ref(false);
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(route('login'), { onFinish: () => form.reset('password') });
</script>

<template>
    <Head title="تسجيل الدخول" />
    <GuestLayout title="مرحباً بعودتك" description="أدخل بيانات حساب المالك للوصول إلى لوحة إدارة فنانة فون.">
        <div v-if="status" class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{{ status }}</div>
        <form class="space-y-5" @submit.prevent="submit">
            <div><label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300">البريد الإلكتروني</label><div class="relative"><svg class="absolute right-3.5 top-3.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg><input id="email" v-model="form.email" type="email" autocomplete="username" required autofocus class="w-full rounded-xl border-slate-300 bg-slate-50 py-3 pr-11 text-slate-900 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="owner@fanana-phone.local" /></div><p v-if="form.errors.email" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.email }}</p></div>
            <div><div class="flex items-center justify-between"><label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-300">كلمة المرور</label><Link v-if="canResetPassword" :href="route('password.request')" class="text-xs font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">نسيت كلمة المرور؟</Link></div><div class="relative mt-1.5"><svg class="absolute right-3.5 top-3.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg><input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'" autocomplete="current-password" required class="w-full rounded-xl border-slate-300 bg-slate-50 py-3 pl-14 pr-11 text-slate-900 shadow-sm transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="••••••••"/><button type="button" class="absolute inset-y-0 left-0 px-4 text-xs font-bold text-slate-500 transition hover:text-indigo-600" @click="showPassword = !showPassword">{{ showPassword ? 'إخفاء' : 'إظهار' }}</button></div><p v-if="form.errors.password" class="mt-1.5 text-xs font-medium text-rose-600">{{ form.errors.password }}</p></div>
            <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300"><input v-model="form.remember" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"/>تذكرني على هذا الجهاز</label>
            <button type="submit" :disabled="form.processing" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-l from-indigo-600 to-blue-600 px-5 py-3.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/25 transition hover:-translate-y-0.5 hover:shadow-xl disabled:translate-y-0 disabled:cursor-not-allowed disabled:opacity-60"><svg v-if="form.processing" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.37 0 0 5.37 0 12h4z"/></svg>{{ form.processing ? 'جارٍ تسجيل الدخول...' : 'تسجيل الدخول' }}</button>
        </form>
    </GuestLayout>
</template>
