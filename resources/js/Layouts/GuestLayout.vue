<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import { Link } from '@inertiajs/vue3';
defineProps({ title: { type: String, default: 'مرحباً بعودتك' }, description: { type: String, default: 'سجّل الدخول للوصول إلى نظام إدارة فنانة فون.' } });
</script>

<template>
    <div class="relative min-h-screen overflow-hidden bg-slate-50 dark:bg-slate-950">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(79,70,229,0.12),transparent_34%),radial-gradient(circle_at_bottom_left,rgba(14,165,233,0.10),transparent_34%)]"></div>
        <div class="absolute inset-0 opacity-[0.04] dark:opacity-[0.08]" style="background-image: linear-gradient(#64748b 1px, transparent 1px), linear-gradient(90deg, #64748b 1px, transparent 1px); background-size: 38px 38px;"></div>
        <div class="absolute left-4 top-4 z-20 sm:left-6 sm:top-6"><ThemeToggle /></div>
        <div class="relative grid min-h-screen lg:grid-cols-[1.05fr_.95fr]">
            <aside class="hidden overflow-hidden bg-gradient-to-br from-slate-950 via-indigo-950 to-indigo-700 p-10 text-white lg:flex lg:flex-col lg:justify-between xl:p-14">
                <ApplicationLogo light />
                <div class="max-w-xl">
                    <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-bold text-indigo-100 backdrop-blur">منصة إدارة متكاملة</span>
                    <h1 class="mt-7 text-4xl font-black leading-tight xl:text-5xl">إدارة معرضك بوضوح، سرعة، وقرارات أدق.</h1>
                    <p class="mt-5 text-base leading-8 text-indigo-100/80">المبيعات والمخزون والصيانة والمشتريات والأرباح في مساحة عمل واحدة مترابطة.</p>
                    <div class="mt-9 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur"><svg class="h-6 w-6 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 10h18M7 15h1m4 0h2m-9 5h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg><p class="mt-3 text-sm font-bold">مبيعات وفواتير</p></div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur"><svg class="h-6 w-6 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><p class="mt-3 text-sm font-bold">مخزون مترابط</p></div>
                        <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-4 backdrop-blur"><svg class="h-6 w-6 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M11.42 15.17l-5.66 5.66a2 2 0 01-2.83-2.83l5.66-5.66m2.83 2.83l4.24-4.24m-4.24 4.24L8.59 12.34m7.07-1.41l2.12-2.12a2 2 0 000-2.83l-1.41-1.41a2 2 0 00-2.83 0l-2.12 2.12m4.24 4.24l-4.24-4.24"/></svg><p class="mt-3 text-sm font-bold">متابعة الصيانة</p></div>
                    </div>
                </div>
                <p class="text-xs text-indigo-200/70">نظام خاص بمالك معرض فنانة فون</p>
            </aside>

            <main class="flex items-center justify-center p-5 sm:p-8 lg:p-12">
                <div class="w-full max-w-md">
                    <Link :href="route('home')" class="mb-8 flex justify-center lg:hidden"><ApplicationLogo /></Link>
                    <div class="rounded-[2rem] border border-slate-200/80 bg-white/90 p-6 shadow-2xl shadow-slate-950/[0.08] backdrop-blur-xl dark:border-slate-800 dark:bg-slate-900/90 sm:p-8">
                        <div class="mb-7"><h2 class="text-2xl font-black text-slate-950 dark:text-white">{{ title }}</h2><p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">{{ description }}</p></div>
                        <slot />
                    </div>
                    <p class="mt-6 text-center text-xs text-slate-400 dark:text-slate-500">© {{ new Date().getFullYear() }} فنانة فون — جميع الحقوق محفوظة</p>
                </div>
            </main>
        </div>
    </div>
</template>
