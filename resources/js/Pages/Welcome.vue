<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const features = [
    {
        title: 'المبيعات والفواتير',
        text: 'نقطة بيع سريعة، دفعات مرنة، ومتابعة واضحة للأرباح والحركات اليومية.',
        accent: 'sky',
        path: 'M3 10h18M7 15h1m4 0h2m-9 5h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    },
    {
        title: 'المخزون والمشتريات',
        text: 'أرصدة مبيعات وصيانة، حركات مخزنية، جرد ومشتريات في مسار مترابط.',
        accent: 'emerald',
        path: 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    },
    {
        title: 'إدارة الصيانة',
        text: 'متابعة الجهاز والقطع والدفعات والحالات من الاستلام وحتى التسليم.',
        accent: 'amber',
        path: 'M11.42 15.17l-5.66 5.66a2 2 0 01-2.83-2.83l5.66-5.66m2.83 2.83l4.24-4.24m-4.24 4.24L8.59 12.34m7.07-1.41l2.12-2.12a2 2 0 000-2.83l-1.41-1.41a2 2 0 00-2.83 0l-2.12 2.12m4.24 4.24l-4.24-4.24',
    },
    {
        title: 'المالية والتقارير',
        text: 'حسابات مالية، مصروفات، تحويلات داخلية وتقارير تساعدك على اتخاذ قرار أسرع.',
        accent: 'violet',
        path: 'M3 3v18h18M7 16l4-4 3 3 5-7',
    },
];

const featureAccentClass = {
    sky: 'from-sky-400/20 to-blue-500/5 text-sky-200 ring-sky-300/20',
    emerald: 'from-emerald-400/20 to-teal-500/5 text-emerald-200 ring-emerald-300/20',
    amber: 'from-amber-400/20 to-orange-500/5 text-amber-200 ring-amber-300/20',
    violet: 'from-violet-400/20 to-indigo-500/5 text-violet-200 ring-violet-300/20',
};

const bars = [36, 54, 46, 72, 61, 88, 76];

const developer = {
    name: 'Anas Al-Harazeen',
    role: 'Software Developer • Laravel & PHP',
    email: 'Anas0599299@gmail.com',
    phone: '+970 595 593 106',
    linkedin: 'https://www.linkedin.com/in/anas-al-harazeen-191310267/',
    github: 'https://github.com/anas-alharazeen',
};
</script>

<template>
    <Head title="منصة إدارة فنانة فون" />

    <main dir="rtl" class="landing-shell relative min-h-screen overflow-hidden bg-[#070b14] text-white">
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="landing-grid absolute inset-0 opacity-60"></div>
            <div class="landing-glow landing-glow-one"></div>
            <div class="landing-glow landing-glow-two"></div>
            <div class="landing-glow landing-glow-three"></div>
            <div class="landing-orbit landing-orbit-one"></div>
            <div class="landing-orbit landing-orbit-two"></div>
            <div class="absolute inset-x-0 top-0 h-48 bg-gradient-to-b from-indigo-500/[0.07] to-transparent"></div>
        </div>

        <nav class="relative z-30 mx-auto flex max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10">
            <Link :href="route('home')" class="rounded-2xl focus:outline-none focus:ring-2 focus:ring-indigo-400/50">
                <ApplicationLogo light />
            </Link>

            <div class="flex items-center gap-2.5">
                <div class="rounded-xl border border-white/10 bg-white/[0.055] p-0.5 backdrop-blur-xl">
                    <ThemeToggle />
                </div>

                <Link
                    :href="route('login')"
                    class="group inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/[0.08] px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-black/10 backdrop-blur-xl transition duration-300 hover:-translate-y-0.5 hover:border-indigo-300/30 hover:bg-white/[0.13]"
                >
                    <span>تسجيل الدخول</span>
                    <svg class="h-4 w-4 transition-transform duration-300 group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                    </svg>
                </Link>
            </div>
        </nav>

        <section class="relative z-10 mx-auto grid max-w-7xl items-center gap-14 px-5 pb-20 pt-9 sm:px-8 lg:grid-cols-[1.02fr_.98fr] lg:px-10 lg:pb-28 lg:pt-16">
            <div class="landing-enter landing-enter-one max-w-3xl">
                <div class="inline-flex items-center gap-2.5 rounded-full border border-indigo-300/15 bg-indigo-400/[0.08] px-4 py-2 text-xs font-black text-indigo-100 shadow-[inset_0_1px_0_rgba(255,255,255,.05)] backdrop-blur-xl">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-40"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400 shadow-[0_0_20px_rgba(52,211,153,.8)]"></span>
                    </span>
                    نظام إدارة متكامل لمعرض فنانة فون
                </div>

                <h1 class="mt-7 text-4xl font-black leading-[1.24] tracking-[-0.035em] sm:text-5xl lg:text-[4.15rem]">
                    كل تفاصيل معرضك
                    <span class="mt-1 block bg-gradient-to-l from-sky-300 via-blue-300 to-indigo-300 bg-clip-text text-transparent">
                        تتحرك معك في مكان واحد.
                    </span>
                </h1>

                <p class="mt-6 max-w-2xl text-base leading-8 text-slate-300/90 sm:text-lg">
                    مساحة عمل واحدة تربط المبيعات والمخزون والصيانة والمشتريات والحسابات والتقارير،
                    بواجهة واضحة تساعدك على متابعة العمل واتخاذ القرار بسرعة.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <Link
                        :href="route('login')"
                        class="landing-primary-button group inline-flex items-center gap-3 rounded-2xl bg-white px-6 py-3.5 text-sm font-black text-slate-950 shadow-[0_16px_45px_rgba(79,70,229,.18)] transition duration-300 hover:-translate-y-1 hover:bg-indigo-50"
                    >
                        <span>الدخول إلى النظام</span>
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-950 text-white transition duration-300 group-hover:-translate-x-1">
                            <svg class="h-4 w-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </span>
                    </Link>

                    <div class="inline-flex items-center gap-3 rounded-2xl border border-white/10 bg-white/[0.045] px-4 py-3 text-sm text-slate-300 backdrop-blur-xl">
                        <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300 ring-1 ring-emerald-300/10">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </span>
                        <span>مخصص لمالك المعرض</span>
                    </div>
                </div>

                <div class="mt-10 grid max-w-2xl grid-cols-3 gap-3 border-t border-white/[0.08] pt-6">
                    <div>
                        <p class="text-lg font-black text-white">01</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">منصة واحدة لكل العمليات</p>
                    </div>
                    <div class="border-x border-white/[0.08] px-4">
                        <p class="text-lg font-black text-white">RTL</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">تجربة عربية واضحة وسريعة</p>
                    </div>
                    <div class="pr-4">
                        <p class="text-lg font-black text-white">Live</p>
                        <p class="mt-1 text-xs leading-5 text-slate-500">صورة مباشرة عن العمل</p>
                    </div>
                </div>
            </div>

            <div class="landing-enter landing-enter-two relative lg:pr-2">
                <div class="absolute -inset-12 rounded-full bg-indigo-500/[0.11] blur-3xl"></div>
                <div class="absolute -left-4 top-12 h-24 w-24 rounded-full bg-sky-400/[0.12] blur-2xl"></div>

                <div class="dashboard-float relative overflow-hidden rounded-[2.25rem] border border-white/[0.12] bg-[#0c1322]/80 p-3 shadow-[0_35px_100px_rgba(0,0,0,.42)] backdrop-blur-2xl sm:p-4">
                    <div class="absolute inset-x-16 top-0 h-px bg-gradient-to-r from-transparent via-indigo-300/60 to-transparent"></div>

                    <div class="overflow-hidden rounded-[1.75rem] border border-white/[0.08] bg-gradient-to-br from-white/[0.075] to-white/[0.025] p-4 sm:p-5">
                        <div class="flex items-center justify-between border-b border-white/[0.08] pb-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-500 shadow-lg shadow-indigo-500/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 6h16M4 12h10M4 18h7" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-black text-white">لوحة الإدارة</p>
                                    <p class="mt-1 text-[11px] text-slate-500">ملخص مباشر لأداء المعرض</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 rounded-full border border-white/[0.08] bg-black/10 px-3 py-2">
                                <span class="h-2 w-2 rounded-full bg-rose-400"></span>
                                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
                                <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            </div>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="group rounded-2xl border border-white/[0.07] bg-white/[0.045] p-4 transition duration-300 hover:border-sky-300/20 hover:bg-white/[0.07]">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-500">مبيعات اليوم</p>
                                        <p class="mt-2 text-lg font-black text-white sm:text-xl">0.00 <span class="text-xs text-slate-500">شيكل</span></p>
                                    </div>
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-400/10 text-sky-300 ring-1 ring-sky-300/10">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h2m-9 5h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-white/[0.07]">
                                    <div class="metric-line h-full w-[68%] rounded-full bg-gradient-to-l from-sky-300 to-blue-500"></div>
                                </div>
                            </div>

                            <div class="group rounded-2xl border border-white/[0.07] bg-white/[0.045] p-4 transition duration-300 hover:border-emerald-300/20 hover:bg-white/[0.07]">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-[11px] font-bold text-slate-500">صافي الربح</p>
                                        <p class="mt-2 text-lg font-black text-emerald-300 sm:text-xl">0.00 <span class="text-xs text-slate-500">شيكل</span></p>
                                    </div>
                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300 ring-1 ring-emerald-300/10">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 17l5-5 3 3 4-6" />
                                        </svg>
                                    </span>
                                </div>
                                <div class="mt-4 flex h-7 items-end gap-1.5">
                                    <span
                                        v-for="(height, index) in bars"
                                        :key="index"
                                        class="chart-bar w-full rounded-t-md bg-gradient-to-t from-emerald-500/30 to-emerald-300/80"
                                        :style="{ height: `${height}%`, animationDelay: `${index * 90}ms` }"
                                    ></span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3 rounded-2xl border border-white/[0.07] bg-white/[0.04] p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm font-black text-white">حركة المبيعات</p>
                                    <p class="mt-1 text-[11px] text-slate-500">تحديثات أسبوعية داخل لوحة واحدة</p>
                                </div>
                                <span class="rounded-full bg-indigo-400/10 px-3 py-1.5 text-[10px] font-black text-indigo-200 ring-1 ring-indigo-300/10">آخر 7 أيام</span>
                            </div>

                            <div class="mt-4 overflow-hidden rounded-xl bg-[#09101d]/70 p-2">
                                <svg class="h-28 w-full sm:h-32" viewBox="0 0 500 120" preserveAspectRatio="none">
                                    <defs>
                                        <linearGradient id="area-premium" x1="0" x2="0" y1="0" y2="1">
                                            <stop offset="0" stop-color="#60a5fa" stop-opacity=".38" />
                                            <stop offset="1" stop-color="#60a5fa" stop-opacity="0" />
                                        </linearGradient>
                                        <linearGradient id="line-premium" x1="0" x2="1">
                                            <stop offset="0" stop-color="#38bdf8" />
                                            <stop offset=".55" stop-color="#818cf8" />
                                            <stop offset="1" stop-color="#c084fc" />
                                        </linearGradient>
                                    </defs>
                                    <path class="chart-area" d="M0 98 C65 92,78 57,142 69 S235 104,298 55 S405 20,500 34 L500 120 L0 120 Z" fill="url(#area-premium)" />
                                    <path class="chart-line" d="M0 98 C65 92,78 57,142 69 S235 104,298 55 S405 20,500 34" fill="none" stroke="url(#line-premium)" stroke-width="3" stroke-linecap="round" />
                                </svg>
                            </div>
                        </div>

                        <div class="mt-3 grid grid-cols-3 gap-2.5">
                            <div class="rounded-xl border border-white/[0.06] bg-white/[0.035] px-3 py-3 text-center">
                                <p class="text-[10px] text-slate-500">المخزون</p>
                                <p class="mt-1 text-sm font-black text-white">جاهز</p>
                            </div>
                            <div class="rounded-xl border border-white/[0.06] bg-white/[0.035] px-3 py-3 text-center">
                                <p class="text-[10px] text-slate-500">الصيانة</p>
                                <p class="mt-1 text-sm font-black text-white">متابعة</p>
                            </div>
                            <div class="rounded-xl border border-white/[0.06] bg-white/[0.035] px-3 py-3 text-center">
                                <p class="text-[10px] text-slate-500">التقارير</p>
                                <p class="mt-1 text-sm font-black text-white">مباشرة</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="floating-badge floating-badge-one hidden items-center gap-3 rounded-2xl border border-white/[0.1] bg-[#0c1424]/90 px-4 py-3 shadow-2xl backdrop-blur-xl sm:flex">
                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                    </span>
                    <div><p class="text-xs font-black">المخزون مترابط</p><p class="mt-0.5 text-[10px] text-slate-500">بيع وصيانة في رؤية واحدة</p></div>
                </div>

                <div class="floating-badge floating-badge-two hidden items-center gap-3 rounded-2xl border border-white/[0.1] bg-[#0c1424]/90 px-4 py-3 shadow-2xl backdrop-blur-xl md:flex">
                    <span class="relative flex h-3 w-3"><span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-sky-400 opacity-30"></span><span class="relative inline-flex h-3 w-3 rounded-full bg-sky-400"></span></span>
                    <div><p class="text-xs font-black">بياناتك أمامك</p><p class="mt-0.5 text-[10px] text-slate-500">متابعة أسرع، قرار أوضح</p></div>
                </div>
            </div>
        </section>

        <section class="relative z-10 border-y border-white/[0.07] bg-white/[0.018]">
            <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 lg:px-10 lg:py-16">
                <div class="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p class="text-xs font-black tracking-[0.18em] text-indigo-300">منظومة واحدة</p>
                        <h2 class="mt-3 text-2xl font-black tracking-tight text-white sm:text-3xl">كل جزء من العمل متصل بالآخر.</h2>
                    </div>
                    <p class="max-w-xl text-sm leading-7 text-slate-400">واجهة هادئة، معلومات واضحة، ومسارات عمل مصممة لتقليل التشتت أثناء التشغيل اليومي.</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <article
                        v-for="(feature, index) in features"
                        :key="feature.title"
                        class="feature-card group relative overflow-hidden rounded-[1.75rem] border border-white/[0.08] bg-white/[0.035] p-5 backdrop-blur-xl transition duration-500 hover:-translate-y-1.5 hover:border-white/[0.16] hover:bg-white/[0.055]"
                        :style="{ animationDelay: `${index * 100}ms` }"
                    >
                        <div class="absolute -left-14 -top-14 h-32 w-32 rounded-full bg-indigo-500/[0.08] blur-2xl transition duration-500 group-hover:bg-indigo-500/[0.14]"></div>
                        <div class="relative">
                            <div :class="featureAccentClass[feature.accent]" class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br ring-1 transition duration-300 group-hover:scale-105">
                                <svg class="h-5.5 w-5.5 h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" :d="feature.path" />
                                </svg>
                            </div>
                            <h3 class="mt-5 text-base font-black text-white">{{ feature.title }}</h3>
                            <p class="mt-2 text-sm leading-7 text-slate-400">{{ feature.text }}</p>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <section class="relative z-10 mx-auto max-w-7xl px-5 pt-14 sm:px-8 lg:px-10 lg:pt-20">
            <div class="developer-card group relative overflow-hidden rounded-[2.25rem] border border-white/[0.09] bg-[#0b1220]/72 p-1 shadow-[0_30px_90px_rgba(0,0,0,.24)] backdrop-blur-2xl">
                <div class="developer-shine pointer-events-none absolute inset-x-16 top-0 h-px bg-gradient-to-r from-transparent via-sky-300/80 to-transparent"></div>
                <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-indigo-500/[0.14] blur-3xl transition duration-700 group-hover:bg-indigo-500/[0.2]"></div>
                <div class="pointer-events-none absolute -bottom-28 left-10 h-64 w-64 rounded-full bg-sky-400/[0.08] blur-3xl"></div>

                <div class="relative grid gap-7 rounded-[2rem] border border-white/[0.065] bg-gradient-to-br from-white/[0.065] via-white/[0.03] to-transparent p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center lg:gap-10 lg:p-10">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-center">
                        <div class="developer-avatar relative flex h-20 w-20 shrink-0 items-center justify-center rounded-[1.6rem] border border-white/[0.12] bg-gradient-to-br from-indigo-500 via-blue-500 to-sky-400 text-2xl font-black tracking-tight text-white shadow-[0_18px_50px_rgba(59,130,246,.22)] sm:h-24 sm:w-24 sm:text-3xl">
                            AH
                            <span class="absolute -bottom-1 -left-1 flex h-7 w-7 items-center justify-center rounded-full border-4 border-[#0d1523] bg-emerald-400 shadow-[0_0_22px_rgba(52,211,153,.45)]">
                                <svg class="h-3.5 w-3.5 text-emerald-950" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                        </div>

                        <div class="max-w-2xl">
                            <div class="inline-flex items-center gap-2 rounded-full border border-sky-300/15 bg-sky-400/[0.07] px-3 py-1.5 text-[10px] font-black tracking-[0.14em] text-sky-200">
                                DESIGNED & DEVELOPED BY
                            </div>
                            <h2 class="mt-3 text-2xl font-black tracking-tight text-white sm:text-3xl">
                                {{ developer.name }}
                            </h2>
                            <p class="mt-1.5 text-sm font-bold text-indigo-200">{{ developer.role }}</p>
                            <p class="mt-4 max-w-xl text-sm leading-7 text-slate-400">
                                تم تصميم وتطوير منصة فنانة فون لتقديم تجربة إدارة مترابطة وسريعة، تجمع العمليات اليومية في نظام واحد واضح وسهل الاستخدام.
                            </p>
                        </div>
                    </div>

                    <div class="grid min-w-0 gap-2.5 sm:grid-cols-2 lg:w-[22rem] lg:grid-cols-1">
                        <a
                            :href="developer.linkedin"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="developer-contact group/contact flex items-center justify-between gap-3 rounded-2xl border border-white/[0.08] bg-white/[0.045] px-4 py-3.5 transition duration-300 hover:-translate-y-0.5 hover:border-sky-300/20 hover:bg-white/[0.075]"
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-sky-400/10 text-sky-300 ring-1 ring-sky-300/10">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M6.5 8.5H3.25V19H6.5V8.5ZM4.875 3A1.875 1.875 0 104.875 6.75 1.875 1.875 0 004.875 3ZM20.75 13.02c0-3.15-1.68-4.62-3.92-4.62a3.39 3.39 0 00-3.08 1.69V8.5H10.5V19h3.25v-5.2c0-1.37.26-2.7 1.96-2.7 1.68 0 1.7 1.57 1.7 2.79V19h3.25l.09-5.98Z" />
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[10px] font-bold text-slate-500">LinkedIn</span>
                                    <span class="mt-0.5 block truncate text-xs font-black text-slate-200">Anas Al-Harazeen</span>
                                </span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 rotate-180 text-slate-600 transition group-hover/contact:-translate-x-0.5 group-hover/contact:text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>

                        <a
                            :href="developer.github"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="developer-contact group/contact flex items-center justify-between gap-3 rounded-2xl border border-white/[0.08] bg-white/[0.045] px-4 py-3.5 transition duration-300 hover:-translate-y-0.5 hover:border-violet-300/20 hover:bg-white/[0.075]"
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-violet-400/10 text-violet-300 ring-1 ring-violet-300/10">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.596 2 12.267c0 4.537 2.865 8.385 6.839 9.743.5.095.682-.222.682-.494 0-.244-.009-.89-.014-1.746-2.782.62-3.369-1.376-3.369-1.376-.455-1.187-1.11-1.503-1.11-1.503-.908-.637.069-.624.069-.624 1.004.073 1.532 1.057 1.532 1.057.892 1.569 2.341 1.116 2.91.853.091-.663.349-1.116.635-1.373-2.221-.26-4.555-1.14-4.555-5.074 0-1.121.39-2.037 1.029-2.756-.103-.26-.446-1.305.098-2.72 0 0 .84-.276 2.75 1.053A9.303 9.303 0 0112 6.96a9.27 9.27 0 012.504.347c1.909-1.329 2.748-1.053 2.748-1.053.546 1.415.203 2.46.1 2.72.64.719 1.027 1.635 1.027 2.756 0 3.944-2.338 4.811-4.566 5.066.359.318.679.946.679 1.907 0 1.377-.012 2.487-.012 2.825 0 .274.18.594.688.493C19.138 20.648 22 16.801 22 12.267 22 6.596 17.523 2 12 2Z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[10px] font-bold text-slate-500">GitHub</span>
                                    <span class="mt-0.5 block truncate text-xs font-black text-slate-200">anas-alharazeen</span>
                                </span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 rotate-180 text-slate-600 transition group-hover/contact:-translate-x-0.5 group-hover/contact:text-violet-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>

                        <a
                            :href="`mailto:${developer.email}`"
                            class="developer-contact group/contact flex items-center justify-between gap-3 rounded-2xl border border-white/[0.08] bg-white/[0.045] px-4 py-3.5 transition duration-300 hover:-translate-y-0.5 hover:border-emerald-300/20 hover:bg-white/[0.075]"
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300 ring-1 ring-emerald-300/10">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8m-16 10h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[10px] font-bold text-slate-500">Email</span>
                                    <span dir="ltr" class="mt-0.5 block truncate text-xs font-black text-slate-200">{{ developer.email }}</span>
                                </span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 rotate-180 text-slate-600 transition group-hover/contact:-translate-x-0.5 group-hover/contact:text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>

                        <a
                            :href="`tel:${developer.phone.replace(/\s/g, '')}`"
                            class="developer-contact group/contact flex items-center justify-between gap-3 rounded-2xl border border-white/[0.08] bg-white/[0.045] px-4 py-3.5 transition duration-300 hover:-translate-y-0.5 hover:border-amber-300/20 hover:bg-white/[0.075]"
                        >
                            <span class="flex min-w-0 items-center gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-400/10 text-amber-300 ring-1 ring-amber-300/10">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.95.684l1.5 4.5a1 1 0 01-.502 1.21l-2.26 1.13a11.042 11.042 0 005.516 5.516l1.13-2.26a1 1 0 011.21-.502l4.5 1.5a1 1 0 01.684.95V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[10px] font-bold text-slate-500">Phone</span>
                                    <span dir="ltr" class="mt-0.5 block truncate text-xs font-black text-slate-200">{{ developer.phone }}</span>
                                </span>
                            </span>
                            <svg class="h-4 w-4 shrink-0 rotate-180 text-slate-600 transition group-hover/contact:-translate-x-0.5 group-hover/contact:text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative z-10 mx-auto max-w-7xl px-5 py-14 sm:px-8 lg:px-10 lg:py-20">
            <div class="relative overflow-hidden rounded-[2rem] border border-white/[0.09] bg-gradient-to-l from-indigo-500/[0.09] via-white/[0.035] to-sky-500/[0.055] px-6 py-8 shadow-2xl shadow-black/10 backdrop-blur-xl sm:px-8 lg:flex lg:items-center lg:justify-between lg:px-10">
                <div class="absolute -left-10 top-0 h-40 w-40 rounded-full bg-indigo-400/10 blur-3xl"></div>
                <div class="relative max-w-2xl">
                    <p class="text-xs font-black text-indigo-300">جاهز للعمل</p>
                    <h2 class="mt-2 text-2xl font-black text-white">ادخل إلى لوحة الإدارة وابدأ من نقطة واحدة.</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-400">الوصول مخصص لحساب مالك معرض فنانة فون.</p>
                </div>
                <Link :href="route('login')" class="relative mt-6 inline-flex items-center gap-2 rounded-2xl bg-white px-5 py-3 text-sm font-black text-slate-950 transition duration-300 hover:-translate-y-0.5 hover:bg-indigo-50 lg:mt-0">
                    تسجيل الدخول
                    <svg class="h-4 w-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" /></svg>
                </Link>
            </div>
        </section>

        <footer class="relative z-10 border-t border-white/[0.06]">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-6 text-xs text-slate-600 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10">
                <span>© {{ new Date().getFullYear() }} فنانة فون — جميع الحقوق محفوظة</span>
                <span class="flex flex-wrap items-center gap-1.5">
                    <span>Designed & Developed by</span>
                    <a :href="developer.linkedin" target="_blank" rel="noopener noreferrer" class="font-black text-slate-400 transition hover:text-indigo-300">{{ developer.name }}</a>
                </span>
            </div>
        </footer>
    </main>
</template>

<style scoped>
.landing-shell {
    isolation: isolate;
}

.landing-grid {
    background-image:
        linear-gradient(rgba(148, 163, 184, 0.035) 1px, transparent 1px),
        linear-gradient(90deg, rgba(148, 163, 184, 0.035) 1px, transparent 1px);
    background-size: 54px 54px;
    mask-image: linear-gradient(to bottom, black 0%, rgba(0,0,0,.85) 65%, transparent 100%);
}

.landing-glow {
    position: absolute;
    border-radius: 9999px;
    filter: blur(90px);
    opacity: .35;
    will-change: transform;
}

.landing-glow-one {
    top: 1rem;
    right: -7rem;
    width: 32rem;
    height: 32rem;
    background: rgba(79, 70, 229, .42);
    animation: glow-drift-a 14s ease-in-out infinite alternate;
}

.landing-glow-two {
    top: 33rem;
    left: -11rem;
    width: 30rem;
    height: 30rem;
    background: rgba(14, 165, 233, .2);
    animation: glow-drift-b 17s ease-in-out infinite alternate;
}

.landing-glow-three {
    top: 14rem;
    left: 38%;
    width: 18rem;
    height: 18rem;
    background: rgba(139, 92, 246, .13);
    animation: glow-drift-c 12s ease-in-out infinite alternate;
}

.landing-orbit {
    position: absolute;
    border: 1px solid rgba(129, 140, 248, .08);
    border-radius: 9999px;
}

.landing-orbit::after {
    content: '';
    position: absolute;
    width: 5px;
    height: 5px;
    border-radius: 9999px;
    background: rgba(125, 211, 252, .75);
    box-shadow: 0 0 18px rgba(125, 211, 252, .75);
}

.landing-orbit-one {
    width: 28rem;
    height: 28rem;
    left: 10%;
    top: 8rem;
    animation: orbit-spin 30s linear infinite;
}

.landing-orbit-one::after { right: 4rem; top: 1.5rem; }

.landing-orbit-two {
    width: 18rem;
    height: 18rem;
    right: 8%;
    top: 29rem;
    animation: orbit-spin-reverse 24s linear infinite;
}

.landing-orbit-two::after { left: 2rem; bottom: 2rem; }

.landing-enter {
    animation: enter-up .8s cubic-bezier(.16, 1, .3, 1) both;
}

.landing-enter-two { animation-delay: .12s; }

.dashboard-float {
    animation: dashboard-float 7s ease-in-out infinite;
}

.floating-badge {
    position: absolute;
    z-index: 3;
}

.floating-badge-one {
    right: -2rem;
    bottom: 4.5rem;
    animation: badge-float-a 6s ease-in-out infinite;
}

.floating-badge-two {
    left: -1.5rem;
    top: 5rem;
    animation: badge-float-b 7.5s ease-in-out infinite;
}

.chart-bar {
    transform-origin: bottom;
    animation: bar-rise .8s cubic-bezier(.16, 1, .3, 1) both;
}

.chart-line {
    stroke-dasharray: 700;
    stroke-dashoffset: 700;
    animation: draw-line 2.2s .45s ease forwards;
}

.chart-area {
    opacity: 0;
    animation: area-fade 1.2s 1.3s ease forwards;
}

.metric-line {
    transform-origin: right;
    animation: line-grow 1.1s .4s cubic-bezier(.16, 1, .3, 1) both;
}

.developer-card {
    animation: developer-enter .9s cubic-bezier(.16, 1, .3, 1) both;
}

.developer-avatar {
    animation: developer-avatar-float 6.5s ease-in-out infinite;
}

.developer-shine {
    animation: developer-shine 5s ease-in-out infinite;
}

.developer-contact {
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, .02);
}

@keyframes enter-up {
    from { opacity: 0; transform: translateY(22px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes glow-drift-a {
    from { transform: translate3d(0,0,0) scale(1); }
    to { transform: translate3d(-45px,40px,0) scale(1.1); }
}

@keyframes glow-drift-b {
    from { transform: translate3d(0,0,0) scale(.95); }
    to { transform: translate3d(55px,-35px,0) scale(1.08); }
}

@keyframes glow-drift-c {
    from { transform: translate3d(0,0,0); }
    to { transform: translate3d(25px,38px,0); }
}

@keyframes orbit-spin { to { transform: rotate(360deg); } }
@keyframes orbit-spin-reverse { to { transform: rotate(-360deg); } }

@keyframes dashboard-float {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-8px); }
}

@keyframes badge-float-a {
    0%,100% { transform: translateY(0) rotate(-1deg); }
    50% { transform: translateY(-8px) rotate(1deg); }
}

@keyframes badge-float-b {
    0%,100% { transform: translateY(0); }
    50% { transform: translateY(9px); }
}

@keyframes bar-rise {
    from { transform: scaleY(0); opacity: .2; }
    to { transform: scaleY(1); opacity: 1; }
}

@keyframes draw-line { to { stroke-dashoffset: 0; } }
@keyframes area-fade { to { opacity: 1; } }
@keyframes line-grow { from { transform: scaleX(0); } to { transform: scaleX(1); } }

@keyframes developer-enter {
    from { opacity: 0; transform: translateY(18px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes developer-avatar-float {
    0%, 100% { transform: translateY(0) rotate(0deg); }
    50% { transform: translateY(-5px) rotate(.6deg); }
}

@keyframes developer-shine {
    0%, 100% { opacity: .45; transform: scaleX(.72); }
    50% { opacity: 1; transform: scaleX(1); }
}

@media (max-width: 1280px) {
    .floating-badge-one { right: .25rem; }
    .floating-badge-two { left: .25rem; }
}

@media (prefers-reduced-motion: reduce) {
    .landing-glow,
    .landing-orbit,
    .landing-enter,
    .dashboard-float,
    .floating-badge,
    .chart-bar,
    .chart-line,
    .chart-area,
    .metric-line,
    .developer-card,
    .developer-avatar,
    .developer-shine {
        animation: none !important;
    }

    .chart-line { stroke-dashoffset: 0; }
    .chart-area { opacity: 1; }
}
</style>
