<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
defineProps({ status: String });
const form = useForm({ email: '' });
const submit = () => form.post(route('password.email'));
</script>
<template><Head title="استعادة كلمة المرور"/><GuestLayout title="نسيت كلمة المرور؟" description="أدخل بريد حساب المالك وسنرسل رابطاً آمناً لإعادة تعيين كلمة المرور."><div v-if="status" class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300">{{ status }}</div><form class="space-y-5" @submit.prevent="submit"><div><label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-300">البريد الإلكتروني</label><input v-model="form.email" type="email" required autofocus autocomplete="username" class="w-full rounded-xl border-slate-300 bg-slate-50 py-3 focus:border-indigo-500 focus:bg-white focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"/><p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-600">{{ form.errors.email }}</p></div><button :disabled="form.processing" class="w-full rounded-xl bg-indigo-600 px-5 py-3.5 text-sm font-bold text-white transition hover:bg-indigo-700 disabled:opacity-50">{{ form.processing ? 'جارٍ الإرسال...' : 'إرسال رابط الاستعادة' }}</button><Link :href="route('login')" class="block text-center text-sm font-bold text-indigo-600 dark:text-indigo-400">العودة إلى تسجيل الدخول</Link></form></GuestLayout></template>
