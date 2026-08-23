<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
const showCurrent = ref(false); const showNew = ref(false);
const form = useForm({ current_password: '', password: '', password_confirmation: '' });
const submit = () => form.put(route('password.update'), { preserveScroll: true, onSuccess: () => form.reset() });
</script>

<template>
    <section>
        <div class="flex items-start gap-4"><div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg></div><div><h2 class="text-lg font-bold text-slate-950 dark:text-white">تغيير كلمة المرور</h2><p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">استخدم كلمة مرور قوية ومختلفة لحماية بيانات المعرض.</p></div></div>
        <form class="mt-6 space-y-5" @submit.prevent="submit">
            <div><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">كلمة المرور الحالية</label><div class="relative"><input v-model="form.current_password" :type="showCurrent ? 'text' : 'password'" autocomplete="current-password" class="w-full rounded-xl border-slate-300 bg-white pl-12 text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"/><button type="button" class="absolute inset-y-0 left-0 px-4 text-xs font-semibold text-slate-500" @click="showCurrent = !showCurrent">{{ showCurrent ? 'إخفاء' : 'إظهار' }}</button></div><p v-if="form.errors.current_password" class="mt-1.5 text-xs text-rose-600">{{ form.errors.current_password }}</p></div>
            <div><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">كلمة المرور الجديدة</label><div class="relative"><input v-model="form.password" :type="showNew ? 'text' : 'password'" autocomplete="new-password" class="w-full rounded-xl border-slate-300 bg-white pl-12 text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"/><button type="button" class="absolute inset-y-0 left-0 px-4 text-xs font-semibold text-slate-500" @click="showNew = !showNew">{{ showNew ? 'إخفاء' : 'إظهار' }}</button></div><p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-600">{{ form.errors.password }}</p></div>
            <div><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">تأكيد كلمة المرور</label><input v-model="form.password_confirmation" :type="showNew ? 'text' : 'password'" autocomplete="new-password" class="w-full rounded-xl border-slate-300 bg-white text-slate-900 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"/></div>
            <div class="flex items-center gap-3"><button type="submit" :disabled="form.processing" class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">{{ form.processing ? 'جارٍ التحديث...' : 'تحديث كلمة المرور' }}</button><span v-if="form.recentlySuccessful" class="text-sm font-semibold text-emerald-600">تم التحديث</span></div>
        </form>
    </section>
</template>
