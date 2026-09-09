<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-bold text-blue-600 dark:text-blue-400">
                        <span class="h-2 w-2 rounded-full bg-emerald-500" />
                        Finance Center
                    </div>
                    <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-950 dark:text-white">المركز المالي</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">أرصدة الحسابات، التدفقات اليومية، والتحويلات في مكان واحد.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('finance.transfers')"
                        class="inline-flex items-center gap-2 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-bold text-indigo-700 transition hover:-translate-y-0.5 hover:bg-indigo-100 dark:border-indigo-900/60 dark:bg-indigo-950/30 dark:text-indigo-300"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4" />
                        </svg>
                        تحويل بين الحسابات
                    </Link>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-2xl bg-slate-950 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-slate-950/10 transition hover:-translate-y-0.5 hover:bg-blue-600 dark:bg-blue-600 dark:hover:bg-blue-500"
                        @click="openAccountModal"
                    >
                        <span class="flex h-7 w-7 items-center justify-center rounded-xl bg-white/10">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                        </span>
                        إضافة حساب
                    </button>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                        @click="openSyncModal"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        مزامنة الدفعات
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-slate-800 bg-slate-950 p-6 shadow-xl shadow-slate-950/10 lg:p-7">
                <div class="absolute -left-20 -top-24 h-60 w-60 rounded-full bg-blue-500/20 blur-3xl" />
                <div class="absolute -bottom-24 right-1/3 h-56 w-56 rounded-full bg-cyan-400/10 blur-3xl" />
                <div class="relative grid gap-6 lg:grid-cols-[1.2fr_1fr] lg:items-end">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200/80">Available liquidity</p>
                        <p class="mt-3 text-sm font-semibold text-slate-400">إجمالي الأرصدة الحالية</p>
                        <p class="mt-1 text-4xl font-black tracking-tight text-white sm:text-5xl">{{ formatCurrency(stats.total_balance) }}</p>
                        <div class="mt-5 flex flex-wrap gap-2">
                            <span class="rounded-full bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-300 ring-1 ring-white/10">{{ stats.active_accounts || 0 }} حساب نشط</span>
                            <span v-if="stats.inactive_accounts" class="rounded-full bg-white/5 px-3 py-1.5 text-xs font-semibold text-slate-300 ring-1 ring-white/10">{{ stats.inactive_accounts }} غير نشط</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2.5">
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                            <p class="text-[11px] font-semibold text-emerald-300">كاش</p>
                            <p class="mt-2 text-base font-black text-white">{{ formatCurrency(stats.cash_balance) }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                            <p class="text-[11px] font-semibold text-blue-300">بنوك</p>
                            <p class="mt-2 text-base font-black text-white">{{ formatCurrency(stats.bank_balance) }}</p>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur">
                            <p class="text-[11px] font-semibold text-violet-300">تطبيقات</p>
                            <p class="mt-2 text-base font-black text-white">{{ formatCurrency(stats.app_balance) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m0-16l-4 4m4-4 4 4" /></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-400">اليوم</span>
                    </div>
                    <p class="mt-4 text-xs font-semibold text-slate-500 dark:text-slate-400">الوارد التشغيلي</p>
                    <p class="mt-1 text-xl font-black text-emerald-600 dark:text-emerald-400">{{ formatCurrency(stats.today_inflows) }}</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20V4m0 16l4-4m-4 4-4-4" /></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-400">اليوم</span>
                    </div>
                    <p class="mt-4 text-xs font-semibold text-slate-500 dark:text-slate-400">الصادر التشغيلي</p>
                    <p class="mt-1 text-xl font-black text-rose-600 dark:text-rose-400">{{ formatCurrency(stats.today_outflows) }}</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4" /></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-400">{{ stats.today_transfer_count || 0 }} عملية</span>
                    </div>
                    <p class="mt-4 text-xs font-semibold text-slate-500 dark:text-slate-400">تحويلات داخلية</p>
                    <p class="mt-1 text-xl font-black text-indigo-600 dark:text-indigo-400">{{ formatCurrency(stats.today_transfer_volume) }}</p>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 12h16m-4-4 4 4-4 4" /></svg>
                        </span>
                        <span class="text-[11px] font-semibold text-slate-400">بدون التحويلات</span>
                    </div>
                    <p class="mt-4 text-xs font-semibold text-slate-500 dark:text-slate-400">صافي التدفق</p>
                    <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ formatCurrency(stats.today_net_flow) }}</p>
                </div>
            </section>

            <section>
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-lg font-black text-slate-950 dark:text-white">حساباتك المالية</h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">اضغط على الحساب لفتح كشف الحركة، أو استخدم زر التعديل لتحديث بياناته وشعاره.</p>
                    </div>
                    <button type="button" class="inline-flex w-fit items-center gap-2 text-sm font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400" @click="openAccountModal">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        حساب جديد
                    </button>
                </div>

                <div v-if="accounts.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="account in accounts"
                        :key="account.id"
                        class="group relative overflow-hidden rounded-3xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-xl dark:bg-slate-900"
                        :class="account.is_active ? 'border-slate-200 dark:border-slate-800' : 'border-dashed border-slate-300 opacity-75 dark:border-slate-700'"
                    >
                        <button type="button" class="absolute inset-0 z-0" :aria-label="`فتح ${account.name}`" @click="goToAccount(account.id)" />

                        <div class="relative z-10 pointer-events-none">
                            <div class="flex items-start justify-between gap-4">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                        <img v-if="account.logo_url" :src="account.logo_url" :alt="account.name" class="h-full w-full object-contain p-2" />
                                        <svg v-else-if="account.type === 'cash'" class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m4-6h-4a2 2 0 00-2 2v2a2 2 0 002 2h4a2 2 0 002-2V9z" />
                                        </svg>
                                        <svg v-else class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 6h14l2 4H3l2-4zm0 4v8m4-8v8m6-8v8m4-8v8M3 18h18" />
                                        </svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="truncate text-base font-black text-slate-900 dark:text-white">{{ account.name }}</h3>
                                        <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">{{ account.type_label }}</p>
                                    </div>
                                </div>

                                <span
                                    class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-bold"
                                    :class="account.is_active
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                                        : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="account.is_active ? 'bg-emerald-500' : 'bg-slate-400'" />
                                    {{ account.is_active ? 'نشط' : 'متوقف' }}
                                </span>
                            </div>

                            <div class="mt-6">
                                <p class="text-xs font-semibold text-slate-400">الرصيد الحالي</p>
                                <p class="mt-1 text-2xl font-black text-slate-950 dark:text-white">{{ formatCurrency(account.current_balance) }}</p>
                            </div>

                            <div class="mt-5 grid grid-cols-2 gap-2">
                                <div class="rounded-2xl bg-emerald-50/70 p-3 dark:bg-emerald-950/20">
                                    <p class="text-[10px] font-semibold text-emerald-700/70 dark:text-emerald-300/70">وارد اليوم</p>
                                    <p class="mt-1 text-xs font-black text-emerald-700 dark:text-emerald-300">{{ formatCurrency(account.today_inflows) }}</p>
                                </div>
                                <div class="rounded-2xl bg-rose-50/70 p-3 dark:bg-rose-950/20">
                                    <p class="text-[10px] font-semibold text-rose-700/70 dark:text-rose-300/70">صادر اليوم</p>
                                    <p class="mt-1 text-xs font-black text-rose-700 dark:text-rose-300">{{ formatCurrency(account.today_outflows) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="relative z-20 mt-4 flex gap-2 border-t border-slate-100 pt-4 dark:border-slate-800">
                            <button
                                type="button"
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-blue-800 dark:hover:bg-blue-950/30 dark:hover:text-blue-300"
                                @click.stop="openEditAccountModal(account)"
                            >
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                تعديل
                            </button>
                            <button
                                type="button"
                                class="inline-flex flex-1 items-center justify-center rounded-xl border px-3 py-2 text-xs font-bold transition"
                                :class="account.is_active
                                    ? 'border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/30'
                                    : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-950/30'"
                                @click.stop="toggleAccount(account)"
                            >
                                {{ account.is_active ? 'تعطيل' : 'تفعيل' }}
                            </button>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-3xl border-2 border-dashed border-slate-200 bg-white px-6 py-14 text-center dark:border-slate-800 dark:bg-slate-900">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-800">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 6h14l2 4H3l2-4zm0 4v8m4-8v8m6-8v8m4-8v8M3 18h18" /></svg>
                    </div>
                    <p class="mt-4 font-black text-slate-800 dark:text-slate-100">لا توجد حسابات مالية بعد</p>
                    <button type="button" class="mt-3 text-sm font-bold text-blue-600" @click="openAccountModal">أضف أول حساب</button>
                </div>
            </section>

            <section>
                <div class="mb-4">
                    <h2 class="text-lg font-black text-slate-950 dark:text-white">عمليات سريعة</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">اختصارات لأكثر الحركات استخدامًا.</p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <Link :href="route('finance.expenses.create')" class="group flex items-center gap-4 rounded-3xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-rose-200 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-300"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8v8m0 0v1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span>
                        <div><p class="text-sm font-black text-slate-900 dark:text-white">مصروف جديد</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">تسجيل حركة صادرة</p></div>
                        <svg class="mr-auto h-4 w-4 text-slate-300 transition group-hover:-translate-x-1 group-hover:text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                    <Link :href="route('finance.transfers')" class="group flex items-center gap-4 rounded-3xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-300"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4" /></svg></span>
                        <div><p class="text-sm font-black text-slate-900 dark:text-white">تحويل بين الحسابات</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">نقل سيولة داخلي</p></div>
                        <svg class="mr-auto h-4 w-4 text-slate-300 transition group-hover:-translate-x-1 group-hover:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                    <button type="button" class="group flex items-center gap-4 rounded-3xl border border-slate-200 bg-white p-5 text-right transition hover:-translate-y-0.5 hover:border-violet-200 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900" @click="openManualTransactionModal">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-950/30 dark:text-violet-300"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V7m0 9v1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg></span>
                        <div><p class="text-sm font-black text-slate-900 dark:text-white">إيداع / سحب</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">حركة يدوية موثقة</p></div>
                        <svg class="mr-auto h-4 w-4 text-slate-300 transition group-hover:-translate-x-1 group-hover:text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </button>
                    <Link :href="route('finance.closings')" class="group flex items-center gap-4 rounded-3xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-amber-200 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
                        <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 5h14a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" /></svg></span>
                        <div><p class="text-sm font-black text-slate-900 dark:text-white">الإغلاق اليومي</p><p class="mt-1 text-xs text-slate-500 dark:text-slate-400">مطابقة الرصيد الفعلي</p></div>
                        <svg class="mr-auto h-4 w-4 text-slate-300 transition group-hover:-translate-x-1 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                    </Link>
                </div>
            </section>
        </div>

        <Modal :show="accountModal.show" max-width="3xl" @close="closeAccountModal">
            <template #title>{{ editingAccount ? 'تعديل الحساب المالي' : 'إضافة حساب مالي' }}</template>
            <template #content>
                <form @submit.prevent="submitAccount" class="space-y-6">
                    <div class="grid gap-6 lg:grid-cols-[220px_1fr]">
                        <div class="space-y-4">
                            <div class="rounded-3xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950/40">
                                <p class="text-xs font-black text-slate-700 dark:text-slate-200">شعار الحساب</p>
                                <p class="mt-1 text-[11px] leading-5 text-slate-400">أضف شعار البنك أو صورة تميز الحساب بسرعة.</p>

                                <div class="mx-auto mt-4 flex h-28 w-28 items-center justify-center overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                    <img v-if="accountLogoDisplay" :src="accountLogoDisplay" alt="معاينة شعار الحساب" class="h-full w-full object-contain p-3" />
                                    <svg v-else-if="accountForm.type === 'cash'" class="h-10 w-10 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m4-6h-4a2 2 0 00-2 2v2a2 2 0 002 2h4a2 2 0 002-2V9z" /></svg>
                                    <svg v-else class="h-10 w-10 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M5 6h14l2 4H3l2-4zm0 4v8m4-8v8m6-8v8m4-8v8M3 18h18" /></svg>
                                </div>

                                <label class="mt-4 inline-flex w-full cursor-pointer items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:border-blue-300 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                                    <input type="file" class="hidden" accept="image/png,image/jpeg,image/webp" @change="handleAccountLogo" />
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2 1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    {{ accountLogoDisplay ? 'تغيير الشعار' : 'رفع شعار' }}
                                </label>
                                <button v-if="accountLogoDisplay" type="button" class="mt-2 w-full text-center text-xs font-bold text-rose-600 hover:text-rose-700" @click="removeAccountLogo">إزالة الشعار</button>
                                <p v-if="accountForm.errors.logo" class="mt-2 text-xs font-semibold text-rose-600">{{ accountForm.errors.logo }}</p>
                                <p class="mt-2 text-center text-[10px] text-slate-400">PNG / JPG / WebP — حتى 2MB</p>
                            </div>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">اسم الحساب <span class="text-rose-500">*</span></label>
                                <input
                                    v-model="accountForm.name"
                                    type="text"
                                    maxlength="255"
                                    placeholder="مثال: بنك فلسطين - الحساب الرئيسي"
                                    class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                    :class="{ 'border-rose-400 ring-1 ring-rose-300': accountForm.errors.name }"
                                    required
                                />
                                <p v-if="accountForm.errors.name" class="mt-2 text-xs font-semibold text-rose-600">{{ accountForm.errors.name }}</p>
                            </div>

                            <div>
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <label class="text-sm font-black text-slate-700 dark:text-slate-200">نوع الحساب <span class="text-rose-500">*</span></label>
                                    <span v-if="editingAccount?.has_transactions" class="text-[11px] font-bold text-amber-600 dark:text-amber-400">مقفل بعد وجود حركات</span>
                                </div>
                                <div class="grid gap-2.5 sm:grid-cols-3">
                                    <button
                                        v-for="(label, value) in accountTypes"
                                        :key="value"
                                        type="button"
                                        :disabled="Boolean(editingAccount?.has_transactions)"
                                        class="rounded-2xl border p-3.5 text-right transition disabled:cursor-not-allowed disabled:opacity-60"
                                        :class="accountForm.type === value
                                            ? 'border-blue-400 bg-blue-50 ring-2 ring-blue-100 dark:border-blue-600 dark:bg-blue-950/30 dark:ring-blue-900/30'
                                            : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900'"
                                        @click="accountForm.type = value"
                                    >
                                        <span class="block text-sm font-black text-slate-800 dark:text-slate-100">{{ label }}</span>
                                        <span class="mt-1 block text-[10px] text-slate-400">{{ accountTypeHint(value) }}</span>
                                    </button>
                                </div>
                                <p v-if="accountForm.errors.type" class="mt-2 text-xs font-semibold text-rose-600">{{ accountForm.errors.type }}</p>
                            </div>

                            <div>
                                <div class="mb-2 flex items-center justify-between gap-3">
                                    <label class="text-sm font-black text-slate-700 dark:text-slate-200">الرصيد الافتتاحي</label>
                                    <span v-if="editingAccount?.has_transactions" class="text-[11px] text-slate-400">لا يمكن تغييره بعد أول حركة</span>
                                </div>
                                <div class="relative">
                                    <input
                                        v-model.number="accountForm.opening_balance"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        :disabled="Boolean(editingAccount?.has_transactions)"
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50 py-3 pl-16 pr-4 text-sm font-black text-slate-900 shadow-none transition focus:border-blue-500 focus:bg-white focus:ring-blue-500 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                    />
                                    <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-xs font-bold text-slate-400">شيكل</span>
                                </div>
                                <p v-if="accountForm.errors.opening_balance" class="mt-2 text-xs font-semibold text-rose-600">{{ accountForm.errors.opening_balance }}</p>
                            </div>

                            <div>
                                <div class="mb-2 flex items-center justify-between"><label class="text-sm font-black text-slate-700 dark:text-slate-200">الوصف</label><span class="text-[11px] text-slate-400">{{ accountForm.description.length }}/500</span></div>
                                <textarea
                                    v-model="accountForm.description"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="مثال: الحساب البنكي المستخدم للتحويلات والمشتريات..."
                                    class="block w-full resize-none rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-7 text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                />
                            </div>

                            <button type="button" class="flex w-full items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-right dark:border-slate-700 dark:bg-slate-950/40" @click="accountForm.is_active = !accountForm.is_active">
                                <div><p class="text-sm font-black text-slate-800 dark:text-slate-100">الحساب متاح للعمليات الجديدة</p><p class="mt-1 text-xs text-slate-400">يمكن تعطيله مع الاحتفاظ بكل الحركات والرصيد.</p></div>
                                <span class="relative inline-flex h-7 w-12 shrink-0 rounded-full p-1 transition" :class="accountForm.is_active ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'"><span class="h-5 w-5 rounded-full bg-white shadow transition-transform" :class="accountForm.is_active ? '-translate-x-5' : 'translate-x-0'" /></span>
                            </button>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 dark:border-slate-800 sm:flex-row sm:justify-end">
                        <button type="button" class="rounded-2xl border border-slate-200 px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800" @click="closeAccountModal">إلغاء</button>
                        <button type="submit" :disabled="accountForm.processing" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 py-2.5 text-sm font-black text-white shadow-lg shadow-blue-600/20 hover:bg-blue-700 disabled:opacity-50">
                            <svg v-if="accountForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            {{ accountForm.processing ? 'جاري الحفظ...' : (editingAccount ? 'حفظ التعديلات' : 'إضافة الحساب') }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>

        <Modal :show="syncModal.show" max-width="lg" @close="syncModal.show = false">
            <template #title>مزامنة الدفعات مع الحسابات المالية</template>
            <template #content>
                <form @submit.prevent="submitSync" class="space-y-5">
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-200">سيتم ربط الدفعات السابقة بالحساب المالي المحدد لإنشاء سجل مالي كامل.</div>
                    <div>
                        <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">الحساب المالي <span class="text-rose-500">*</span></label>
                        <select v-model="syncForm.account_id" class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold dark:border-slate-700 dark:bg-slate-950/60 dark:text-white" required>
                            <option value="">اختر الحساب</option>
                            <option v-for="account in activeAccounts" :key="account.id" :value="account.id">{{ account.name }}</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3"><button type="button" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold dark:border-slate-700 dark:text-slate-300" @click="syncModal.show = false">إلغاء</button><button type="submit" :disabled="syncForm.processing" class="rounded-2xl bg-blue-600 px-5 py-2.5 text-sm font-black text-white disabled:opacity-50">{{ syncForm.processing ? 'جاري المزامنة...' : 'مزامنة' }}</button></div>
                </form>
            </template>
        </Modal>

        <Modal :show="manualModal.show" max-width="xl" @close="manualModal.show = false">
            <template #title>إيداع / سحب يدوي</template>
            <template #content>
                <form @submit.prevent="submitManual" class="space-y-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">الحساب <span class="text-rose-500">*</span></label><select v-model="manualForm.account_id" class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold dark:border-slate-700 dark:bg-slate-950/60 dark:text-white" required><option value="">اختر الحساب</option><option v-for="account in activeAccounts" :key="account.id" :value="account.id">{{ account.name }}</option></select></div>
                        <div><label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">نوع الحركة <span class="text-rose-500">*</span></label><select v-model="manualForm.type" class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold dark:border-slate-700 dark:bg-slate-950/60 dark:text-white" required><option value="">اختر النوع</option><option value="deposit">إيداع</option><option value="withdrawal">سحب</option></select></div>
                        <div><label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">المبلغ <span class="text-rose-500">*</span></label><input v-model.number="manualForm.amount" type="number" step="0.01" min="0.01" class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold dark:border-slate-700 dark:bg-slate-950/60 dark:text-white" required /></div>
                        <div><label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">التاريخ <span class="text-rose-500">*</span></label><input v-model="manualForm.date" type="date" class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold dark:border-slate-700 dark:bg-slate-950/60 dark:text-white" required /></div>
                    </div>
                    <div><label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">السبب <span class="text-rose-500">*</span></label><textarea v-model="manualForm.reason" rows="2" maxlength="500" class="block w-full resize-none rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-950/60 dark:text-white" placeholder="سبب الحركة" required /></div>
                    <div><label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">ملاحظات</label><textarea v-model="manualForm.notes" rows="2" maxlength="500" class="block w-full resize-none rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm dark:border-slate-700 dark:bg-slate-950/60 dark:text-white" placeholder="ملاحظات إضافية" /></div>
                    <div class="flex justify-end gap-3"><button type="button" class="rounded-2xl border border-slate-200 px-4 py-2.5 text-sm font-bold dark:border-slate-700 dark:text-slate-300" @click="manualModal.show = false">إلغاء</button><button type="submit" :disabled="manualForm.processing" class="rounded-2xl bg-violet-600 px-5 py-2.5 text-sm font-black text-white disabled:opacity-50">{{ manualForm.processing ? 'جاري التسجيل...' : 'تسجيل الحركة' }}</button></div>
                </form>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    accounts: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        default: () => ({}),
    },
    accountTypes: {
        type: Object,
        default: () => ({}),
    },
});

const activeAccounts = computed(() => props.accounts.filter((account) => account.is_active));
const accountModal = ref({ show: false });
const syncModal = ref({ show: false });
const manualModal = ref({ show: false });
const editingAccount = ref(null);
const accountLogoPreview = ref(null);

const accountForm = useForm({
    name: '',
    type: '',
    opening_balance: 0,
    description: '',
    is_active: true,
    logo: null,
    remove_logo: false,
});

const syncForm = useForm({ account_id: '' });
const manualForm = useForm({
    account_id: '',
    type: '',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    reason: '',
    notes: '',
});

const accountLogoDisplay = computed(() => {
    if (accountLogoPreview.value) return accountLogoPreview.value;
    if (accountForm.remove_logo) return null;
    return editingAccount.value?.logo_url || null;
});

const formatCurrency = (value) => `${Number(value || 0).toFixed(2)} شيكل`;

const accountTypeHint = (type) => ({
    cash: 'صندوق نقدي',
    bank: 'حساب مصرفي',
    banking_app: 'محفظة / تطبيق',
}[type] || 'حساب مالي');

const revokeAccountPreview = () => {
    if (accountLogoPreview.value?.startsWith('blob:')) {
        URL.revokeObjectURL(accountLogoPreview.value);
    }
    accountLogoPreview.value = null;
};

const resetAccountForm = () => {
    revokeAccountPreview();
    accountForm.reset();
    accountForm.clearErrors();
    accountForm.name = '';
    accountForm.type = '';
    accountForm.opening_balance = 0;
    accountForm.description = '';
    accountForm.is_active = true;
    accountForm.logo = null;
    accountForm.remove_logo = false;
};

const openAccountModal = () => {
    editingAccount.value = null;
    resetAccountForm();
    accountModal.value.show = true;
};

const openEditAccountModal = (account) => {
    editingAccount.value = account;
    resetAccountForm();
    accountForm.name = account.name || '';
    accountForm.type = account.type || '';
    accountForm.opening_balance = Number(account.opening_balance || 0);
    accountForm.description = account.description || '';
    accountForm.is_active = Boolean(account.is_active);
    accountModal.value.show = true;
};

const closeAccountModal = () => {
    accountModal.value.show = false;
    editingAccount.value = null;
    resetAccountForm();
};

const handleAccountLogo = (event) => {
    const file = event.target.files?.[0] || null;
    if (!file) return;

    revokeAccountPreview();
    accountForm.logo = file;
    accountForm.remove_logo = false;
    accountLogoPreview.value = URL.createObjectURL(file);
};

const removeAccountLogo = () => {
    revokeAccountPreview();
    accountForm.logo = null;
    accountForm.remove_logo = true;
};

const submitAccount = () => {
    const target = editingAccount.value
        ? route('finance.update-account', editingAccount.value.id)
        : route('finance.store-account');

    accountForm.post(target, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: closeAccountModal,
    });
};

const openSyncModal = () => {
    syncForm.reset();
    syncForm.clearErrors();
    syncModal.value.show = true;
};

const openManualTransactionModal = () => {
    manualForm.reset();
    manualForm.clearErrors();
    manualForm.date = new Date().toISOString().split('T')[0];
    manualModal.value.show = true;
};

const goToAccount = (id) => router.get(route('finance.show-account', id));

const toggleAccount = (account) => {
    const action = account.is_active ? 'تعطيل' : 'تفعيل';
    if (!window.confirm(`${action} حساب «${account.name}»؟`)) return;

    router.patch(route('finance.toggle-account', account.id), {
        is_active: !account.is_active,
    }, {
        preserveScroll: true,
    });
};

const submitSync = () => {
    syncForm.post(route('finance.sync-payments'), {
        preserveScroll: true,
        onSuccess: () => { syncModal.value.show = false; },
    });
};

const submitManual = () => {
    manualForm.post(route('finance.manual-transaction'), {
        preserveScroll: true,
        onSuccess: () => { manualModal.value.show = false; },
    });
};

onBeforeUnmount(revokeAccountPreview);
</script>
