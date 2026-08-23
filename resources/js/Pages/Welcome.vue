<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
const features = [
    { title: 'المبيعات والفواتير', text: 'نقطة بيع سريعة، دفعات مرنة، وأرباح محسوبة بدقة.', path: 'M3 10h18M7 15h1m4 0h2m-9 5h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { title: 'المخزون والمشتريات', text: 'متابعة أرصدة البيع والصيانة والتنبيهات والجرد.', path: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4' },
    { title: 'إدارة الصيانة', text: 'رحلة الجهاز من الاستلام حتى التسليم مع قطع الغيار.', path: 'M11.42 15.17l-5.66 5.66a2 2 0 01-2.83-2.83l5.66-5.66m2.83 2.83l4.24-4.24m-4.24 4.24L8.59 12.34m7.07-1.41l2.12-2.12a2 2 0 000-2.83l-1.41-1.41a2 2 0 00-2.83 0l-2.12 2.12m4.24 4.24l-4.24-4.24' },
    { title: 'التقارير والتحليلات', text: 'لوحة مؤشرات وتقارير مالية ومخزنية قابلة للتصدير.', path: 'M3 3v18h18M7 16l4-4 3 3 5-7' },
];
</script>

<template>
    <Head title="منصة إدارة فنانة فون" />
    <main class="relative min-h-screen overflow-hidden bg-slate-950 text-white">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_15%,rgba(79,70,229,.42),transparent_32%),radial-gradient(circle_at_15%_85%,rgba(14,165,233,.22),transparent_30%)]"></div>
        <div class="absolute inset-0 opacity-[.07]" style="background-image: linear-gradient(#fff 1px, transparent 1px), linear-gradient(90deg, #fff 1px, transparent 1px); background-size: 48px 48px;"></div>
        <nav class="relative z-20 mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10">
            <ApplicationLogo light />
            <div class="flex items-center gap-2"><ThemeToggle/><Link :href="route('login')" class="rounded-xl border border-white/15 bg-white/10 px-4 py-2.5 text-sm font-bold text-white backdrop-blur transition hover:bg-white/15">تسجيل الدخول</Link></div>
        </nav>

        <section class="relative z-10 mx-auto grid max-w-7xl items-center gap-12 px-5 pb-20 pt-10 sm:px-8 lg:grid-cols-[1.05fr_.95fr] lg:px-10 lg:pb-28 lg:pt-20">
            <div>
                <span class="inline-flex items-center gap-2 rounded-full border border-indigo-300/20 bg-indigo-400/10 px-4 py-2 text-xs font-bold text-indigo-200 backdrop-blur"><span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_0_5px_rgba(52,211,153,.12)]"></span>نظام إدارة خاص ومتكامل</span>
                <h1 class="mt-7 max-w-3xl text-4xl font-black leading-[1.25] sm:text-5xl lg:text-6xl">كل تفاصيل معرضك،<span class="block bg-gradient-to-l from-sky-300 via-blue-300 to-indigo-300 bg-clip-text text-transparent">في مساحة عمل واحدة.</span></h1>
                <p class="mt-6 max-w-2xl text-base leading-8 text-slate-300 sm:text-lg">منصة فنانة فون تربط المبيعات والمخزون والصيانة والمشتريات والدفعات والتقارير، لتمنحك صورة واضحة عن عملك في كل لحظة.</p>
                <div class="mt-8 flex flex-wrap gap-3"><Link :href="route('login')" class="inline-flex items-center gap-2 rounded-2xl bg-white px-6 py-3.5 text-sm font-black text-slate-950 shadow-xl shadow-black/20 transition hover:-translate-y-0.5 hover:bg-indigo-50">الدخول إلى النظام<svg class="h-4 w-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg></Link><span class="inline-flex items-center rounded-2xl border border-white/10 bg-white/[.06] px-5 py-3.5 text-sm text-slate-300 backdrop-blur">مخصص لمالك المعرض</span></div>
            </div>

            <div class="relative">
                <div class="absolute -inset-10 rounded-full bg-indigo-500/20 blur-3xl"></div>
                <div class="relative overflow-hidden rounded-[2rem] border border-white/15 bg-white/[.08] p-4 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-6">
                    <div class="flex items-center justify-between border-b border-white/10 pb-4"><div><p class="text-sm font-bold">لوحة الإدارة</p><p class="mt-1 text-xs text-slate-400">نظرة مباشرة على أداء المعرض</p></div><div class="flex gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-rose-400"></span><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span></div></div>
                    <div class="mt-5 grid grid-cols-2 gap-3"><div class="rounded-2xl bg-white/10 p-4"><p class="text-xs text-slate-400">مبيعات اليوم</p><p class="mt-2 text-xl font-black">0.00 شيكل</p><div class="mt-4 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full w-2/3 rounded-full bg-sky-400"></div></div></div><div class="rounded-2xl bg-white/10 p-4"><p class="text-xs text-slate-400">صافي الربح</p><p class="mt-2 text-xl font-black text-emerald-300">0.00 شيكل</p><div class="mt-4 flex items-end gap-1"><span v-for="h in [3,5,4,7,6,9,8]" :key="h" class="w-full rounded-sm bg-emerald-400/60" :style="{height: `${h*3}px`}"></span></div></div></div>
                    <div class="mt-3 rounded-2xl bg-white/10 p-4"><div class="flex items-center justify-between"><p class="text-sm font-bold">حركة المبيعات</p><span class="text-xs text-slate-400">آخر 7 أيام</span></div><svg class="mt-4 h-32 w-full" viewBox="0 0 500 120" preserveAspectRatio="none"><defs><linearGradient id="area" x1="0" x2="0" y1="0" y2="1"><stop offset="0" stop-color="#60a5fa" stop-opacity=".45"/><stop offset="1" stop-color="#60a5fa" stop-opacity="0"/></linearGradient></defs><path d="M0 100 C70 90,80 55,145 70 S240 105,300 55 S410 15,500 35 L500 120 L0 120 Z" fill="url(#area)"/><path d="M0 100 C70 90,80 55,145 70 S240 105,300 55 S410 15,500 35" fill="none" stroke="#93c5fd" stroke-width="3" stroke-linecap="round"/></svg></div>
                </div>
            </div>
        </section>

        <section class="relative z-10 border-t border-white/10 bg-white/[.03]"><div class="mx-auto grid max-w-7xl gap-4 px-5 py-12 sm:grid-cols-2 sm:px-8 lg:grid-cols-4 lg:px-10"><article v-for="feature in features" :key="feature.title" class="rounded-3xl border border-white/10 bg-white/[.05] p-5 backdrop-blur transition hover:-translate-y-1 hover:border-indigo-300/30 hover:bg-white/[.08]"><div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-400/15 text-indigo-200"><svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" :d="feature.path"/></svg></div><h2 class="mt-4 font-bold">{{ feature.title }}</h2><p class="mt-2 text-sm leading-6 text-slate-400">{{ feature.text }}</p></article></div></section>
    </main>
</template>
