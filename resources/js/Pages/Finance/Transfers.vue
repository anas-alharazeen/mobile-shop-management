<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-2 flex items-center gap-2 text-xs font-bold text-gray-400 dark:text-gray-500">
                        <Link :href="route('finance.index')" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">
                            المركز المالي
                        </Link>
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                        <span class="text-gray-700 dark:text-gray-300">التحويلات</span>
                    </div>

                    <h1 class="text-2xl font-black tracking-tight text-gray-950 dark:text-white sm:text-3xl">
                        التحويل بين الحسابات
                    </h1>
                    <p class="mt-1.5 max-w-2xl text-sm leading-6 text-gray-500 dark:text-gray-400">
                        حرّك السيولة بين حساباتك بأمان، مع تسجيل الحركة الصادرة والواردة تلقائياً والمحافظة على أثر مالي كامل لكل تحويل.
                    </p>
                </div>

                <Link
                    :href="route('finance.index')"
                    class="inline-flex items-center justify-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-extrabold text-gray-700 shadow-sm transition hover:-translate-y-0.5 hover:border-gray-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200 dark:hover:border-gray-600"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    العودة للمركز المالي
                </Link>
            </div>
        </template>

        <div class="space-y-6 pb-8">
            <!-- Premium hero -->
            <section class="relative overflow-hidden rounded-[28px] bg-gray-950 px-5 py-6 text-white shadow-xl shadow-gray-900/10 sm:px-7 sm:py-7 dark:border dark:border-gray-800">
                <div class="pointer-events-none absolute -left-16 -top-24 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 right-10 h-60 w-60 rounded-full bg-cyan-400/10 blur-3xl"></div>

                <div class="relative grid gap-6 xl:grid-cols-[1.15fr_1fr] xl:items-end">
                    <div>
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-bold text-gray-300 backdrop-blur">
                            <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            تحويلات داخلية آمنة ومترابطة
                        </div>

                        <h2 class="mt-4 max-w-xl text-2xl font-black leading-tight sm:text-3xl">
                            سيولتك بين حساباتك،
                            <span class="text-indigo-300">بحركة واحدة واضحة.</span>
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-gray-400">
                            التحويل الداخلي لا يغيّر إجمالي أموالك ولا يُحتسب كدخل أو مصروف تشغيلي؛ بل ينقل الرصيد فقط من حساب إلى آخر مع توثيق الطرفين.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-2">
                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-sm">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-gray-400">تحويلات اليوم</span>
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-400/10 text-indigo-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4" />
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-3 text-lg font-black text-white sm:text-xl">{{ money(stats.today_volume) }}</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-sm">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-gray-400">إجمالي التحويلات</span>
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-cyan-400/10 text-cyan-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-8 5h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-3 text-lg font-black text-white sm:text-xl">{{ money(stats.total_volume) }}</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-sm">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-gray-400">مكتملة</span>
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-3 text-xl font-black text-white">{{ number(stats.transfer_count) }}</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-4 backdrop-blur-sm">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-bold text-gray-400">ملغاة</span>
                                <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-rose-400/10 text-rose-300">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </span>
                            </div>
                            <p class="mt-3 text-xl font-black text-white">{{ number(stats.cancelled_count) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Accounts strip -->
            <section>
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white">الحسابات النشطة</h3>
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">نظرة سريعة على الأرصدة المتاحة للتحويل.</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1.5 text-xs font-bold text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                        {{ activeAccounts.length }} حساب
                    </span>
                </div>

                <div class="flex gap-3 overflow-x-auto pb-2">
                    <button
                        v-for="account in activeAccounts"
                        :key="account.id"
                        type="button"
                        class="group min-w-[210px] rounded-2xl border bg-white p-4 text-right shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800"
                        :class="String(transferForm.from_account_id) === String(account.id)
                            ? 'border-indigo-300 ring-4 ring-indigo-50 dark:border-indigo-700 dark:ring-indigo-950/30'
                            : 'border-gray-200 hover:border-indigo-200 dark:border-gray-700 dark:hover:border-indigo-800'"
                        @click="selectSourceAccount(account.id)"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-gray-100 text-gray-600 transition group-hover:bg-indigo-50 group-hover:text-indigo-600 dark:bg-gray-700 dark:text-gray-300 dark:group-hover:bg-indigo-950/40 dark:group-hover:text-indigo-300">
                                <svg v-if="isCashType(account.type)" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 6h14l2 4H3l2-4zm0 4v8m4-8v8m6-8v8m4-8v8M3 18h18" />
                                </svg>
                            </div>
                            <span class="rounded-full bg-gray-50 px-2.5 py-1 text-[11px] font-bold text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                                {{ account.type_label }}
                            </span>
                        </div>
                        <p class="mt-3 truncate text-sm font-black text-gray-900 dark:text-white">{{ account.name }}</p>
                        <p class="mt-1 text-lg font-black text-gray-950 dark:text-white">{{ money(account.current_balance) }}</p>
                    </button>
                </div>
            </section>

            <section class="grid gap-6 2xl:grid-cols-[480px_minmax(0,1fr)]">
                <!-- Transfer composer -->
                <article class="self-start overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 2xl:sticky 2xl:top-6">
                    <div class="border-b border-gray-100 bg-gradient-to-l from-indigo-50/80 via-white to-white px-5 py-5 dark:border-gray-700 dark:from-indigo-950/20 dark:via-gray-800 dark:to-gray-800 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20">
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4" />
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-black text-gray-950 dark:text-white">تحويل جديد</h2>
                                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">اختر الطرفين وحدد المبلغ، والباقي يتم تلقائياً.</p>
                            </div>
                        </div>
                    </div>

                    <form class="space-y-5 p-5 sm:p-6" @submit.prevent="submitTransfer">
                        <div v-if="activeAccounts.length < 2" class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                            <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z" />
                            </svg>
                            <div>
                                <p class="font-black">لا يمكن تنفيذ تحويل حالياً</p>
                                <p class="mt-1 text-xs leading-5 opacity-80">تحتاج إلى حسابين ماليين نشطين على الأقل.</p>
                            </div>
                        </div>

                        <!-- Source account -->
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <label class="text-sm font-black text-gray-800 dark:text-gray-200">
                                    الحساب المصدر <span class="text-rose-500">*</span>
                                </label>
                                <span v-if="sourceAccount" class="text-xs font-bold text-gray-400">الرصيد: {{ money(sourceAccount.current_balance) }}</span>
                            </div>
                            <div class="relative">
                                <select
                                    v-model="transferForm.from_account_id"
                                    class="block w-full appearance-none rounded-2xl border-gray-200 bg-gray-50/80 py-3.5 pr-4 pl-10 text-sm font-bold text-gray-900 shadow-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-indigo-600 dark:focus:ring-indigo-950/40"
                                    required
                                >
                                    <option value="">اختر الحساب الذي سيتم الخصم منه</option>
                                    <option v-for="account in activeAccounts" :key="account.id" :value="account.id">
                                        {{ account.name }} — {{ money(account.current_balance) }}
                                    </option>
                                </select>
                                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <p v-if="transferForm.errors.from_account_id" class="mt-1.5 text-xs font-bold text-rose-600">{{ transferForm.errors.from_account_id }}</p>
                        </div>

                        <!-- Direction connector -->
                        <div class="relative py-1">
                            <div class="absolute inset-x-0 top-1/2 border-t border-dashed border-gray-200 dark:border-gray-700"></div>
                            <div class="relative flex justify-center">
                                <button
                                    type="button"
                                    :disabled="!transferForm.from_account_id || !transferForm.to_account_id"
                                    class="group inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-indigo-100 bg-white text-indigo-600 shadow-sm transition hover:-translate-y-0.5 hover:rotate-180 hover:border-indigo-200 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-40 dark:border-indigo-900/60 dark:bg-gray-800 dark:text-indigo-300"
                                    title="تبديل الحسابين"
                                    @click="swapAccounts"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Destination account -->
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <label class="text-sm font-black text-gray-800 dark:text-gray-200">
                                    الحساب المستلم <span class="text-rose-500">*</span>
                                </label>
                                <span v-if="destinationAccount" class="text-xs font-bold text-gray-400">الرصيد: {{ money(destinationAccount.current_balance) }}</span>
                            </div>
                            <div class="relative">
                                <select
                                    v-model="transferForm.to_account_id"
                                    class="block w-full appearance-none rounded-2xl border-gray-200 bg-gray-50/80 py-3.5 pr-4 pl-10 text-sm font-bold text-gray-900 shadow-none transition focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-indigo-600 dark:focus:ring-indigo-950/40"
                                    required
                                >
                                    <option value="">اختر الحساب الذي سيستقبل المبلغ</option>
                                    <option v-for="account in destinationAccounts" :key="account.id" :value="account.id">
                                        {{ account.name }} — {{ money(account.current_balance) }}
                                    </option>
                                </select>
                                <svg class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </div>
                            <p v-if="transferForm.errors.to_account_id" class="mt-1.5 text-xs font-bold text-rose-600">{{ transferForm.errors.to_account_id }}</p>
                        </div>

                        <!-- Amount -->
                        <div>
                            <div class="mb-2 flex items-center justify-between gap-2">
                                <label class="text-sm font-black text-gray-800 dark:text-gray-200">
                                    مبلغ التحويل <span class="text-rose-500">*</span>
                                </label>
                                <button
                                    v-if="sourceAccount && Number(sourceAccount.current_balance) > 0"
                                    type="button"
                                    class="text-xs font-black text-indigo-600 transition hover:text-indigo-700 dark:text-indigo-400"
                                    @click="setAmountPercentage(100)"
                                >
                                    كامل الرصيد
                                </button>
                            </div>

                            <div class="relative">
                                <input
                                    v-model.number="transferForm.amount"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    class="block w-full rounded-2xl border-gray-200 bg-gray-50/80 py-4 pr-4 pl-20 text-xl font-black text-gray-950 shadow-none transition placeholder:text-gray-300 focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-600 dark:focus:border-indigo-600 dark:focus:ring-indigo-950/40"
                                    placeholder="0.00"
                                    required
                                />
                                <span class="absolute inset-y-0 left-4 flex items-center rounded-xl text-xs font-black text-gray-400">شيكل</span>
                            </div>

                            <div v-if="sourceAccount && Number(sourceAccount.current_balance) > 0" class="mt-2.5 grid grid-cols-4 gap-2">
                                <button
                                    v-for="percentage in [25, 50, 75, 100]"
                                    :key="percentage"
                                    type="button"
                                    class="rounded-xl border border-gray-200 bg-white px-2 py-2 text-xs font-black text-gray-500 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:hover:border-indigo-800 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-300"
                                    @click="setAmountPercentage(percentage)"
                                >
                                    {{ percentage === 100 ? 'الكل' : `${percentage}%` }}
                                </button>
                            </div>

                            <p v-if="transferForm.errors.amount" class="mt-1.5 text-xs font-bold text-rose-600">{{ transferForm.errors.amount }}</p>
                            <p v-else-if="amountExceedsBalance" class="mt-1.5 flex items-center gap-1 text-xs font-bold text-rose-600">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z" />
                                </svg>
                                المبلغ أكبر من الرصيد المتاح في الحساب المصدر.
                            </p>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="text-sm font-black text-gray-800 dark:text-gray-200">
                                    تاريخ التحويل <span class="text-rose-500">*</span>
                                </label>
                                <input
                                    v-model="transferForm.transfer_date"
                                    :max="todayString"
                                    type="date"
                                    class="mt-2 block w-full rounded-2xl border-gray-200 bg-gray-50/80 py-3 text-sm font-bold shadow-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-indigo-600 dark:focus:ring-indigo-950/40"
                                    required
                                />
                                <p v-if="transferForm.errors.transfer_date" class="mt-1.5 text-xs font-bold text-rose-600">{{ transferForm.errors.transfer_date }}</p>
                            </div>

                            <div class="sm:col-span-1">
                                <label class="text-sm font-black text-gray-800 dark:text-gray-200">ملاحظة مختصرة</label>
                                <input
                                    v-model="transferForm.notes"
                                    type="text"
                                    maxlength="500"
                                    class="mt-2 block w-full rounded-2xl border-gray-200 bg-gray-50/80 py-3 text-sm shadow-none focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:border-indigo-600 dark:focus:ring-indigo-950/40"
                                    placeholder="مثال: إيداع يومي"
                                />
                                <p v-if="transferForm.errors.notes" class="mt-1.5 text-xs font-bold text-rose-600">{{ transferForm.errors.notes }}</p>
                            </div>
                        </div>

                        <!-- Live preview -->
                        <div v-if="showPreview" class="overflow-hidden rounded-2xl border border-indigo-100 bg-indigo-50/50 dark:border-indigo-900/50 dark:bg-indigo-950/20">
                            <div class="flex items-center justify-between border-b border-indigo-100 px-4 py-3 dark:border-indigo-900/50">
                                <div class="flex items-center gap-2">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-600 text-white">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </span>
                                    <span class="text-xs font-black text-indigo-800 dark:text-indigo-200">معاينة ما بعد التحويل</span>
                                </div>
                                <span class="text-xs font-black text-indigo-600 dark:text-indigo-300">{{ money(numericAmount) }}</span>
                            </div>

                            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 p-4">
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-bold text-gray-500 dark:text-gray-400">{{ sourceAccount.name }}</p>
                                    <p class="mt-1 text-base font-black" :class="sourceAfterBalance < 0 ? 'text-rose-600' : 'text-gray-950 dark:text-white'">
                                        {{ money(sourceAfterBalance) }}
                                    </p>
                                </div>

                                <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-indigo-600 shadow-sm dark:bg-gray-800 dark:text-indigo-300">
                                    <svg class="h-4 w-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0-5 5m5-5H6" />
                                    </svg>
                                </div>

                                <div class="min-w-0 text-left">
                                    <p class="truncate text-xs font-bold text-gray-500 dark:text-gray-400">{{ destinationAccount.name }}</p>
                                    <p class="mt-1 text-base font-black text-emerald-600 dark:text-emerald-400">{{ money(destinationAfterBalance) }}</p>
                                </div>
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="transferForm.processing || activeAccounts.length < 2 || !canSubmitTransfer"
                            class="group inline-flex w-full items-center justify-center gap-2.5 rounded-2xl bg-gray-950 px-5 py-4 text-sm font-black text-white shadow-lg shadow-gray-900/10 transition hover:-translate-y-0.5 hover:bg-indigo-600 hover:shadow-indigo-600/20 disabled:translate-y-0 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:shadow-none dark:bg-white dark:text-gray-950 dark:hover:bg-indigo-500 dark:hover:text-white dark:disabled:bg-gray-700 dark:disabled:text-gray-400"
                        >
                            <svg v-if="transferForm.processing" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            <svg v-else class="h-5 w-5 transition group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0-5 5m5-5H6" />
                            </svg>
                            {{ transferForm.processing ? 'جاري تنفيذ التحويل...' : 'تنفيذ التحويل الآن' }}
                        </button>

                        <div class="flex items-start gap-2 rounded-xl bg-gray-50 px-3.5 py-3 text-[11px] leading-5 text-gray-500 dark:bg-gray-900 dark:text-gray-400">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            يتم خصم وإضافة المبلغ ضمن عملية واحدة؛ إذا فشل أي طرف فلن يُحفظ التحويل جزئياً.
                        </div>
                    </form>
                </article>

                <!-- History -->
                <article class="min-w-0 overflow-hidden rounded-[28px] border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-100 px-5 py-5 dark:border-gray-700 sm:px-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2 class="text-lg font-black text-gray-950 dark:text-white">سجل التحويلات</h2>
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-1 text-[11px] font-black text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-300">
                                        {{ transfers.total || 0 }} عملية
                                    </span>
                                </div>
                                <p class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400">
                                    سجل كامل للتحويلات مع إمكانية البحث والتصفية وعكس العمليات عند الحاجة.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-2xl border px-4 py-2.5 text-xs font-black transition"
                                :class="showFilters
                                    ? 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-300'
                                    : 'border-gray-200 bg-white text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:hover:bg-gray-700'"
                                @click="showFilters = !showFilters"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 01.8 1.6L14 13.667V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.333L3.2 4.6A1 1 0 013 4z" />
                                </svg>
                                {{ showFilters ? 'إخفاء الفلاتر' : 'بحث وتصفية' }}
                                <span v-if="activeFilterCount" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-indigo-600 px-1.5 text-[10px] text-white">
                                    {{ activeFilterCount }}
                                </span>
                            </button>
                        </div>

                        <transition
                            enter-active-class="transition duration-200 ease-out"
                            enter-from-class="-translate-y-2 opacity-0"
                            enter-to-class="translate-y-0 opacity-100"
                            leave-active-class="transition duration-150 ease-in"
                            leave-from-class="translate-y-0 opacity-100"
                            leave-to-class="-translate-y-2 opacity-0"
                        >
                            <div v-if="showFilters" class="mt-5 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/60">
                                <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                                    <div class="xl:col-span-2">
                                        <label class="mb-1.5 block text-[11px] font-black text-gray-500 dark:text-gray-400">بحث</label>
                                        <div class="relative">
                                            <input
                                                v-model="filterForm.search"
                                                type="search"
                                                class="block w-full rounded-xl border-gray-200 bg-white py-2.5 pr-9 text-sm shadow-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                                placeholder="رقم التحويل أو اسم الحساب..."
                                                @keyup.enter="applyFilters"
                                            />
                                            <svg class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-[11px] font-black text-gray-500 dark:text-gray-400">الحساب</label>
                                        <select v-model="filterForm.account_id" class="block w-full rounded-xl border-gray-200 bg-white py-2.5 text-sm shadow-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                            <option value="">كل الحسابات</option>
                                            <option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }}</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-[11px] font-black text-gray-500 dark:text-gray-400">الحالة</label>
                                        <select v-model="filterForm.status" class="block w-full rounded-xl border-gray-200 bg-white py-2.5 text-sm shadow-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white">
                                            <option value="">كل الحالات</option>
                                            <option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option>
                                        </select>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2 xl:col-span-1">
                                        <div>
                                            <label class="mb-1.5 block text-[11px] font-black text-gray-500 dark:text-gray-400">من</label>
                                            <input v-model="filterForm.start_date" type="date" class="block w-full rounded-xl border-gray-200 bg-white py-2.5 text-xs shadow-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                                        </div>
                                        <div>
                                            <label class="mb-1.5 block text-[11px] font-black text-gray-500 dark:text-gray-400">إلى</label>
                                            <input v-model="filterForm.end_date" type="date" class="block w-full rounded-xl border-gray-200 bg-white py-2.5 text-xs shadow-none focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-800 dark:text-white" />
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-3 flex flex-wrap justify-end gap-2">
                                    <button type="button" class="rounded-xl px-3.5 py-2 text-xs font-black text-gray-500 transition hover:bg-white dark:text-gray-400 dark:hover:bg-gray-800" @click="clearFilters">
                                        مسح الكل
                                    </button>
                                    <button type="button" class="rounded-xl bg-gray-950 px-4 py-2 text-xs font-black text-white transition hover:bg-indigo-600 dark:bg-white dark:text-gray-950 dark:hover:bg-indigo-500 dark:hover:text-white" @click="applyFilters">
                                        تطبيق الفلاتر
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </div>

                    <!-- Desktop table -->
                    <div class="hidden overflow-x-auto md:block">
                        <table class="min-w-[920px] w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/70 text-[11px] font-black uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-400">
                                    <th class="px-5 py-3.5 text-right">العملية</th>
                                    <th class="px-4 py-3.5 text-right">المسار</th>
                                    <th class="px-4 py-3.5 text-right">المبلغ</th>
                                    <th class="px-4 py-3.5 text-right">التاريخ</th>
                                    <th class="px-4 py-3.5 text-right">الحالة</th>
                                    <th class="px-5 py-3.5 text-left">الإجراء</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                <tr v-for="transfer in transfers.data || []" :key="transfer.id" class="group transition hover:bg-gray-50/80 dark:hover:bg-gray-900/40">
                                    <td class="px-5 py-4 align-middle">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/40 dark:text-indigo-300">
                                                <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4" />
                                                </svg>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-black text-gray-950 dark:text-white">{{ transfer.transfer_number }}</p>
                                                <p v-if="transfer.notes" class="mt-1 max-w-[210px] truncate text-xs text-gray-400" :title="transfer.notes">{{ transfer.notes }}</p>
                                                <p v-else class="mt-1 text-xs text-gray-300 dark:text-gray-600">بدون ملاحظات</p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 align-middle">
                                        <div class="flex min-w-[230px] items-center gap-2">
                                            <div class="min-w-0 flex-1 rounded-xl bg-rose-50/70 px-3 py-2 dark:bg-rose-950/20">
                                                <p class="truncate text-xs font-black text-gray-800 dark:text-gray-200">{{ transfer.from_account?.name || '—' }}</p>
                                                <p class="mt-0.5 text-[10px] font-bold text-rose-500">صادر</p>
                                            </div>
                                            <svg class="h-4 w-4 shrink-0 rotate-180 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0-5 5m5-5H6" />
                                            </svg>
                                            <div class="min-w-0 flex-1 rounded-xl bg-emerald-50/70 px-3 py-2 dark:bg-emerald-950/20">
                                                <p class="truncate text-xs font-black text-gray-800 dark:text-gray-200">{{ transfer.to_account?.name || '—' }}</p>
                                                <p class="mt-0.5 text-[10px] font-bold text-emerald-500">وارد</p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-4 align-middle">
                                        <p class="whitespace-nowrap text-sm font-black text-gray-950 dark:text-white">{{ money(transfer.amount) }}</p>
                                    </td>

                                    <td class="px-4 py-4 align-middle">
                                        <p class="whitespace-nowrap text-xs font-bold text-gray-600 dark:text-gray-300">{{ formatDate(transfer.transfer_date) }}</p>
                                    </td>

                                    <td class="px-4 py-4 align-middle">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1.5 text-[11px] font-black"
                                            :class="statusValue(transfer.status) === 'posted'
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="statusValue(transfer.status) === 'posted' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                            {{ transfer.status_label || statusLabel(transfer.status) }}
                                        </span>
                                        <p v-if="transfer.cancellation_reason" class="mt-1.5 max-w-[170px] truncate text-[10px] font-bold text-rose-500" :title="transfer.cancellation_reason">
                                            {{ transfer.cancellation_reason }}
                                        </p>
                                    </td>

                                    <td class="px-5 py-4 text-left align-middle">
                                        <button
                                            v-if="transfer.can_be_cancelled"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-[11px] font-black text-gray-600 opacity-70 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-600 group-hover:opacity-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-rose-900 dark:hover:bg-rose-950/30 dark:hover:text-rose-300"
                                            @click="openCancelModal(transfer)"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h11a4 4 0 010 8H9m0 0l3-3m-3 3l3 3M21 6H10a4 4 0 000 8h1" />
                                            </svg>
                                            إلغاء وعكس
                                        </button>
                                        <span v-else class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                    </td>
                                </tr>

                                <tr v-if="!(transfers.data || []).length">
                                    <td colspan="6" class="px-6 py-16">
                                        <EmptyState :filtered="activeFilterCount > 0" @clear="clearFilters" />
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile cards -->
                    <div class="divide-y divide-gray-100 dark:divide-gray-700 md:hidden">
                        <div v-for="transfer in transfers.data || []" :key="transfer.id" class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-black text-gray-950 dark:text-white">{{ transfer.transfer_number }}</p>
                                    <p class="mt-1 text-xs text-gray-400">{{ formatDate(transfer.transfer_date) }}</p>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-black"
                                    :class="statusValue(transfer.status) === 'posted'
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                                        : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300'"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="statusValue(transfer.status) === 'posted' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                    {{ transfer.status_label || statusLabel(transfer.status) }}
                                </span>
                            </div>

                            <div class="mt-4 rounded-2xl border border-gray-100 bg-gray-50/70 p-3 dark:border-gray-700 dark:bg-gray-900/50">
                                <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-2">
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-rose-500">من</p>
                                        <p class="mt-0.5 truncate text-xs font-black text-gray-800 dark:text-gray-200">{{ transfer.from_account?.name || '—' }}</p>
                                    </div>
                                    <svg class="h-4 w-4 rotate-180 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0-5 5m5-5H6" />
                                    </svg>
                                    <div class="min-w-0 text-left">
                                        <p class="text-[10px] font-bold text-emerald-500">إلى</p>
                                        <p class="mt-0.5 truncate text-xs font-black text-gray-800 dark:text-gray-200">{{ transfer.to_account?.name || '—' }}</p>
                                    </div>
                                </div>
                                <p class="mt-3 border-t border-gray-200 pt-3 text-center text-base font-black text-gray-950 dark:border-gray-700 dark:text-white">{{ money(transfer.amount) }}</p>
                            </div>

                            <p v-if="transfer.notes" class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-400">{{ transfer.notes }}</p>
                            <p v-if="transfer.cancellation_reason" class="mt-2 rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-600 dark:bg-rose-950/30 dark:text-rose-300">
                                سبب الإلغاء: {{ transfer.cancellation_reason }}
                            </p>

                            <button
                                v-if="transfer.can_be_cancelled"
                                type="button"
                                class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-rose-200 bg-white px-3 py-2.5 text-xs font-black text-rose-600 transition hover:bg-rose-50 dark:border-rose-900 dark:bg-gray-800 dark:text-rose-300 dark:hover:bg-rose-950/30"
                                @click="openCancelModal(transfer)"
                            >
                                إلغاء التحويل وعكس الحركة
                            </button>
                        </div>

                        <div v-if="!(transfers.data || []).length" class="p-8">
                            <EmptyState :filtered="activeFilterCount > 0" @clear="clearFilters" />
                        </div>
                    </div>

                    <div v-if="transfers.links?.length" class="border-t border-gray-100 px-4 py-4 dark:border-gray-700 sm:px-6">
                        <Pagination :links="transfers.links" />
                    </div>
                </article>
            </section>
        </div>

        <!-- Cancellation modal -->
        <Modal :show="cancelModal.show" @close="closeCancelModal">
            <template #title>إلغاء التحويل وعكس الحركات</template>
            <template #content>
                <form class="space-y-5" @submit.prevent="submitCancellation">
                    <div class="flex gap-3 rounded-2xl border border-rose-100 bg-rose-50 p-4 text-sm text-rose-800 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-200">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.74-3L13.74 4a2 2 0 00-3.48 0L3.33 16a2 2 0 001.74 3z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-black">سيتم عكس الحركة وليس حذفها</p>
                            <p class="mt-1 text-xs leading-5 opacity-80">سيبقى التحويل الأصلي في السجل، وسيتم إنشاء حركتي Reversal للمحافظة على الأثر المالي.</p>
                        </div>
                    </div>

                    <div v-if="cancelModal.transfer" class="rounded-2xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-xs font-bold text-gray-400">{{ cancelModal.transfer.transfer_number }}</p>
                                <p class="mt-1 text-lg font-black text-gray-950 dark:text-white">{{ money(cancelModal.transfer.amount) }}</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-[11px] font-black text-gray-500 shadow-sm dark:bg-gray-800 dark:text-gray-400">
                                {{ formatDate(cancelModal.transfer.transfer_date) }}
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-[1fr_auto_1fr] items-center gap-2 text-xs">
                            <div class="rounded-xl bg-white p-3 dark:bg-gray-800">
                                <p class="text-[10px] font-bold text-rose-500">من</p>
                                <p class="mt-1 truncate font-black text-gray-800 dark:text-gray-200">{{ cancelModal.transfer.from_account?.name }}</p>
                            </div>
                            <svg class="h-4 w-4 rotate-180 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0-5 5m5-5H6" />
                            </svg>
                            <div class="rounded-xl bg-white p-3 text-left dark:bg-gray-800">
                                <p class="text-[10px] font-bold text-emerald-500">إلى</p>
                                <p class="mt-1 truncate font-black text-gray-800 dark:text-gray-200">{{ cancelModal.transfer.to_account?.name }}</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-black text-gray-800 dark:text-gray-200">
                            سبب الإلغاء <span class="text-rose-500">*</span>
                        </label>
                        <textarea
                            v-model="cancelForm.reason"
                            rows="3"
                            maxlength="500"
                            class="mt-2 block w-full rounded-2xl border-gray-200 bg-gray-50 text-sm shadow-none focus:border-rose-500 focus:bg-white focus:ring-4 focus:ring-rose-100 dark:border-gray-700 dark:bg-gray-900 dark:text-white dark:focus:ring-rose-950/30"
                            placeholder="اكتب سبب الإلغاء بشكل واضح..."
                            required
                        />
                        <p v-if="cancelForm.errors.reason" class="mt-1.5 text-xs font-bold text-rose-600">{{ cancelForm.errors.reason }}</p>
                    </div>

                    <div class="flex justify-end gap-2 pt-1">
                        <button type="button" class="rounded-xl px-4 py-2.5 text-sm font-black text-gray-500 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700" @click="closeCancelModal">
                            تراجع
                        </button>
                        <button type="submit" :disabled="cancelForm.processing" class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-black text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <svg v-if="cancelForm.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>
                            {{ cancelForm.processing ? 'جاري العكس...' : 'تأكيد الإلغاء والعكس' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, defineComponent, h, reactive, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    transfers: { type: Object, default: () => ({ data: [], links: [] }) },
    accounts: { type: Array, default: () => [] },
    activeAccounts: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    statuses: { type: Object, default: () => ({}) },
});

const EmptyState = defineComponent({
    props: {
        filtered: { type: Boolean, default: false },
    },
    emits: ['clear'],
    setup(componentProps, { emit }) {
        return () => h('div', { class: 'flex flex-col items-center text-center' }, [
            h('div', { class: 'flex h-14 w-14 items-center justify-center rounded-2xl bg-gray-100 text-gray-400 dark:bg-gray-700 dark:text-gray-500' }, [
                h('svg', { class: 'h-6 w-6', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
                    h('path', {
                        'stroke-linecap': 'round',
                        'stroke-linejoin': 'round',
                        'stroke-width': '2',
                        d: 'M7 16V4m0 0L3 8m4-4 4 4m6 0v12m0 0 4-4m-4 4-4-4',
                    }),
                ]),
            ]),
            h('p', { class: 'mt-3 text-sm font-black text-gray-800 dark:text-gray-200' }, componentProps.filtered ? 'لا توجد نتائج مطابقة' : 'لا توجد تحويلات بعد'),
            h('p', { class: 'mt-1 max-w-xs text-xs leading-5 text-gray-400' }, componentProps.filtered ? 'جرّب تغيير معايير البحث أو مسح الفلاتر الحالية.' : 'عند تنفيذ أول تحويل بين حساباتك سيظهر هنا مباشرة.'),
            componentProps.filtered
                ? h('button', {
                    type: 'button',
                    class: 'mt-3 rounded-xl bg-gray-100 px-3 py-2 text-xs font-black text-gray-600 transition hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600',
                    onClick: () => emit('clear'),
                }, 'مسح الفلاتر')
                : null,
        ]);
    },
});

const localToday = () => {
    const now = new Date();
    const adjusted = new Date(now.getTime() - now.getTimezoneOffset() * 60000);
    return adjusted.toISOString().slice(0, 10);
};

const todayString = localToday();
const showFilters = ref(Boolean(
    props.filters.search
    || props.filters.account_id
    || props.filters.status
    || props.filters.start_date
    || props.filters.end_date
));

const transferForm = useForm({
    from_account_id: '',
    to_account_id: '',
    amount: '',
    transfer_date: todayString,
    notes: '',
});

const filterForm = reactive({
    search: props.filters.search || '',
    account_id: props.filters.account_id || '',
    status: props.filters.status || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const cancelModal = ref({ show: false, transfer: null });
const cancelForm = useForm({ reason: '' });

const sourceAccount = computed(() => props.activeAccounts.find(
    (account) => String(account.id) === String(transferForm.from_account_id)
) || null);

const destinationAccount = computed(() => props.activeAccounts.find(
    (account) => String(account.id) === String(transferForm.to_account_id)
) || null);

const destinationAccounts = computed(() => props.activeAccounts.filter(
    (account) => String(account.id) !== String(transferForm.from_account_id)
));

const numericAmount = computed(() => Number(transferForm.amount || 0));

const sourceAfterBalance = computed(() => (
    Number(sourceAccount.value?.current_balance || 0) - numericAmount.value
));

const destinationAfterBalance = computed(() => (
    Number(destinationAccount.value?.current_balance || 0) + numericAmount.value
));

const amountExceedsBalance = computed(() => (
    sourceAccount.value
    && numericAmount.value > Number(sourceAccount.value.current_balance || 0)
));

const showPreview = computed(() => (
    sourceAccount.value
    && destinationAccount.value
    && numericAmount.value > 0
));

const canSubmitTransfer = computed(() => (
    showPreview.value
    && !amountExceedsBalance.value
    && sourceAfterBalance.value >= 0
    && String(transferForm.from_account_id) !== String(transferForm.to_account_id)
));

const activeFilterCount = computed(() => [
    filterForm.search,
    filterForm.account_id,
    filterForm.status,
    filterForm.start_date,
    filterForm.end_date,
].filter(Boolean).length);

watch(
    () => transferForm.from_account_id,
    (value) => {
        if (String(value) === String(transferForm.to_account_id)) {
            transferForm.to_account_id = '';
        }
    }
);

const money = (value) => `${Number(value || 0).toLocaleString('ar-PS', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})} شيكل`;

const number = (value) => Number(value || 0).toLocaleString('ar-PS');

const formatDate = (value) => {
    if (!value) return '—';
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString('ar-PS', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const statusValue = (value) => (
    typeof value === 'object' && value !== null
        ? (value.value ?? value.name ?? '')
        : value
);

const statusLabel = (value) => props.statuses[statusValue(value)] || statusValue(value) || '—';

const isCashType = (type) => String(type || '').toLowerCase().includes('cash');

const selectSourceAccount = (accountId) => {
    transferForm.from_account_id = accountId;
};

const setAmountPercentage = (percentage) => {
    if (!sourceAccount.value) return;

    const balance = Number(sourceAccount.value.current_balance || 0);
    const amount = balance * (Number(percentage) / 100);
    transferForm.amount = Number(amount.toFixed(2));
};

const swapAccounts = () => {
    const oldSource = transferForm.from_account_id;
    transferForm.from_account_id = transferForm.to_account_id;
    transferForm.to_account_id = oldSource;
};

const submitTransfer = () => {
    transferForm.post(route('finance.store-transfer'), {
        preserveScroll: true,
        onSuccess: () => {
            transferForm.reset();
            transferForm.transfer_date = todayString;
        },
    });
};

const applyFilters = () => {
    router.get(
        route('finance.transfers'),
        { ...filterForm },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const clearFilters = () => {
    Object.assign(filterForm, {
        search: '',
        account_id: '',
        status: '',
        start_date: '',
        end_date: '',
    });
    applyFilters();
};

const openCancelModal = (transfer) => {
    cancelModal.value = { show: true, transfer };
    cancelForm.reset();
};

const closeCancelModal = () => {
    cancelModal.value = { show: false, transfer: null };
    cancelForm.reset();
};

const submitCancellation = () => {
    if (!cancelModal.value.transfer) return;

    cancelForm.post(
        route('finance.cancel-transfer', cancelModal.value.transfer.id),
        {
            preserveScroll: true,
            onSuccess: closeCancelModal,
        }
    );
};
</script>
