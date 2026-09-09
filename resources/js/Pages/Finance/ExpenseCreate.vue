<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('finance.expenses')"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:-translate-x-0.5 hover:border-slate-300 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:text-white"
                        aria-label="العودة إلى المصروفات"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">
                            تسجيل مصروف جديد
                        </h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            سجل المصروف وحدد الحساب الذي سيتم الخصم منه بدقة.
                        </p>
                    </div>
                </div>

                <div class="inline-flex items-center gap-2 self-start rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300 sm:self-auto">
                    <span class="h-2 w-2 rounded-full bg-emerald-500" />
                    الحركة تُرحّل مباشرة للحساب المالي
                </div>
            </div>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 shadow-xl shadow-slate-950/10 dark:border-slate-800">
                <div class="absolute -left-20 -top-20 h-56 w-56 rounded-full bg-blue-500/20 blur-3xl" />
                <div class="absolute -bottom-24 right-1/3 h-52 w-52 rounded-full bg-cyan-400/10 blur-3xl" />

                <div class="relative grid gap-5 p-6 md:grid-cols-[1fr_auto] md:items-center lg:p-8">
                    <div>
                        <div class="mb-3 flex items-center gap-2 text-sm font-semibold text-blue-200">
                            <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-white/10">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            مصروفات Fanana Phone
                        </div>
                        <h2 class="max-w-2xl text-2xl font-black text-white sm:text-3xl">
                            أدخل التفاصيل مرة واحدة، والنظام يتولى الحركة المالية.
                        </h2>
                        <p class="mt-3 max-w-2xl text-sm leading-7 text-slate-300">
                            عند الحفظ سيُنشأ المصروف وتُسجل حركة صادرة من الحساب المحدد مع الاحتفاظ بالمرفق والمرجع المالي.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-right backdrop-blur">
                        <p class="text-xs font-semibold text-slate-400">تاريخ المصروف</p>
                        <p class="mt-1 text-lg font-black text-white">{{ formattedSelectedDate }}</p>
                    </div>
                </div>
            </section>

            <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-6">
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m4.5-1.5a2.121 2.121 0 010 3l-9 9a2.121 2.121 0 01-3 0l-3-3a2.121 2.121 0 010-3l9-9a2.121 2.121 0 013 0l3 3z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900 dark:text-white">تفاصيل المصروف</h3>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">البيانات الأساسية التي ستظهر في السجل والتقارير.</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <div class="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        التصنيف <span class="text-rose-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.expense_category_id"
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 shadow-none transition focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                        :class="{ 'border-rose-400 ring-1 ring-rose-300': form.errors.expense_category_id }"
                                        required
                                    >
                                        <option value="">اختر تصنيف المصروف</option>
                                        <option v-for="category in categories" :key="category.id" :value="category.id">
                                            {{ category.name }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.expense_category_id" class="mt-2 text-xs font-semibold text-rose-600">
                                        {{ form.errors.expense_category_id }}
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        تاريخ المصروف <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.expense_date"
                                        type="date"
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 shadow-none transition focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                        required
                                    />
                                    <p v-if="form.errors.expense_date" class="mt-2 text-xs font-semibold text-rose-600">
                                        {{ form.errors.expense_date }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-[220px_1fr]">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        المبلغ <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model.number="form.amount"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            inputmode="decimal"
                                            placeholder="0.00"
                                            class="block w-full rounded-2xl border-slate-200 bg-slate-50 py-3 pl-16 pr-4 text-lg font-black text-slate-950 shadow-none transition focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                            :class="{ 'border-rose-400 ring-1 ring-rose-300': form.errors.amount }"
                                            required
                                        />
                                        <span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-xs font-bold text-slate-400">شيكل</span>
                                    </div>
                                    <p v-if="form.errors.amount" class="mt-2 text-xs font-semibold text-rose-600">
                                        {{ form.errors.amount }}
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">المستفيد</label>
                                    <input
                                        v-model="form.beneficiary"
                                        type="text"
                                        maxlength="255"
                                        placeholder="مثال: شركة الكهرباء، موظف، مورد خدمة..."
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                    />
                                    <p v-if="form.errors.beneficiary" class="mt-2 text-xs font-semibold text-rose-600">
                                        {{ form.errors.beneficiary }}
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                    الوصف <span class="text-rose-500">*</span>
                                </label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="اكتب وصفًا واضحًا للمصروف ليسهل الرجوع إليه لاحقًا..."
                                    class="block w-full resize-none rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-7 text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                    :class="{ 'border-rose-400 ring-1 ring-rose-300': form.errors.description }"
                                    required
                                />
                                <div class="mt-2 flex items-center justify-between gap-3">
                                    <p v-if="form.errors.description" class="text-xs font-semibold text-rose-600">
                                        {{ form.errors.description }}
                                    </p>
                                    <span class="mr-auto text-[11px] font-medium text-slate-400">{{ form.description.length }}/500</span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="border-b border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-50 text-violet-600 dark:bg-violet-950/40 dark:text-violet-300">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5.002 5.002 0 0115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-black text-slate-900 dark:text-white">المرفق والملاحظات</h3>
                                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">اختياري — مفيد للفواتير والإيصالات والتفاصيل الإضافية.</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <label
                                class="group flex cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 px-5 py-7 text-center transition hover:border-blue-300 hover:bg-blue-50/50 dark:border-slate-700 dark:bg-slate-950/40 dark:hover:border-blue-700 dark:hover:bg-blue-950/20"
                            >
                                <input
                                    type="file"
                                    class="hidden"
                                    accept=".jpg,.jpeg,.png,.webp,.pdf"
                                    @change="handleAttachment"
                                />
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-slate-500 shadow-sm transition group-hover:text-blue-600 dark:bg-slate-800 dark:text-slate-300">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828a4 4 0 00-5.657-5.657L5.757 10.757a6 6 0 108.486 8.486L20.5 13" />
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-bold text-slate-700 dark:text-slate-200">
                                    {{ attachmentName || 'اختر إيصالًا أو مرفقًا' }}
                                </p>
                                <p class="mt-1 text-xs text-slate-400">JPG, PNG, WebP أو PDF — حتى 5MB</p>
                            </label>
                            <p v-if="form.errors.attachment" class="text-xs font-semibold text-rose-600">
                                {{ form.errors.attachment }}
                            </p>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">ملاحظات إضافية</label>
                                <textarea
                                    v-model="form.notes"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="أي تفاصيل داخلية تريد الاحتفاظ بها..."
                                    class="block w-full resize-none rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-7 text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                />
                            </div>
                        </div>
                    </section>
                </div>

                <aside class="space-y-5 lg:sticky lg:top-6 lg:self-start">
                    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="mb-4 flex items-center justify-between">
                            <div>
                                <p class="text-sm font-black text-slate-900 dark:text-white">الحساب المالي</p>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">سيتم الخصم من هذا الحساب.</p>
                            </div>
                            <span class="text-rose-500">*</span>
                        </div>

                        <div class="space-y-2.5">
                            <button
                                v-for="account in accounts"
                                :key="account.id"
                                type="button"
                                class="flex w-full items-center gap-3 rounded-2xl border p-3 text-right transition"
                                :class="Number(form.financial_account_id) === Number(account.id)
                                    ? 'border-blue-400 bg-blue-50 ring-2 ring-blue-100 dark:border-blue-600 dark:bg-blue-950/30 dark:ring-blue-900/30'
                                    : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800'"
                                @click="form.financial_account_id = account.id"
                            >
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                                    <img v-if="account.logo_url" :src="account.logo_url" :alt="account.name" class="h-full w-full object-contain p-1.5" />
                                    <svg v-else class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path v-if="account.type === 'cash'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-2m4-6h-4a2 2 0 00-2 2v2a2 2 0 002 2h4a2 2 0 002-2V9z" />
                                        <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M5 6h14l2 4H3l2-4zm0 4v8m4-8v8m6-8v8m4-8v8M3 18h18" />
                                    </svg>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ account.name }}</p>
                                    <p class="mt-0.5 text-[11px] font-medium text-slate-500 dark:text-slate-400">{{ account.type_label }}</p>
                                </div>
                                <div class="text-left">
                                    <p class="text-xs font-black text-slate-800 dark:text-slate-100">{{ formatCurrency(account.current_balance) }}</p>
                                    <p class="mt-0.5 text-[10px] text-slate-400">الرصيد</p>
                                </div>
                            </button>
                        </div>

                        <p v-if="form.errors.financial_account_id" class="mt-3 text-xs font-semibold text-rose-600">
                            {{ form.errors.financial_account_id }}
                        </p>
                    </section>

                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div class="bg-slate-950 p-5 text-white">
                            <p class="text-xs font-semibold text-slate-400">ملخص قبل التسجيل</p>
                            <div class="mt-3 flex items-end justify-between gap-3">
                                <div>
                                    <p class="text-3xl font-black">{{ formatCurrency(form.amount) }}</p>
                                    <p class="mt-1 text-xs text-slate-400">قيمة المصروف</p>
                                </div>
                                <span class="rounded-full bg-rose-500/15 px-2.5 py-1 text-[11px] font-bold text-rose-300">صادر</span>
                            </div>
                        </div>

                        <div class="space-y-3 p-5 text-sm">
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-500 dark:text-slate-400">الحساب</span>
                                <span class="truncate font-bold text-slate-900 dark:text-white">{{ selectedAccount?.name || 'لم يتم الاختيار' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-slate-500 dark:text-slate-400">الرصيد الحالي</span>
                                <span class="font-bold text-slate-900 dark:text-white">{{ formatCurrency(selectedAccount?.current_balance) }}</span>
                            </div>
                            <div class="h-px bg-slate-100 dark:bg-slate-800" />
                            <div class="flex items-center justify-between gap-4">
                                <span class="font-semibold text-slate-600 dark:text-slate-300">الرصيد بعد المصروف</span>
                                <span
                                    class="font-black"
                                    :class="remainingBalance < 0 ? 'text-rose-600' : 'text-emerald-600 dark:text-emerald-400'"
                                >
                                    {{ formatCurrency(remainingBalance) }}
                                </span>
                            </div>
                            <p v-if="remainingBalance < 0" class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-semibold leading-5 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">
                                الرصيد غير كافٍ. لن يسمح النظام بتنفيذ المصروف بهذه القيمة.
                            </p>
                        </div>
                    </section>

                    <div class="flex flex-col gap-3">
                        <button
                            type="submit"
                            :disabled="form.processing || !canSubmit"
                            class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ form.processing ? 'جاري التسجيل...' : 'تسجيل المصروف' }}
                        </button>

                        <Link
                            :href="route('finance.expenses')"
                            class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                        >
                            إلغاء والعودة
                        </Link>
                    </div>
                </aside>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
    accounts: {
        type: Array,
        default: () => [],
    },
    today: {
        type: String,
        required: true,
    },
});

const attachmentName = ref('');

const form = useForm({
    expense_category_id: '',
    financial_account_id: '',
    amount: '',
    expense_date: props.today,
    beneficiary: '',
    description: '',
    notes: '',
    attachment: null,
});

const selectedAccount = computed(() => (
    props.accounts.find((account) => Number(account.id) === Number(form.financial_account_id)) || null
));

const remainingBalance = computed(() => (
    Number(selectedAccount.value?.current_balance || 0) - Number(form.amount || 0)
));

const canSubmit = computed(() => (
    Boolean(form.expense_category_id)
    && Boolean(form.financial_account_id)
    && Number(form.amount) > 0
    && remainingBalance.value >= 0
    && Boolean(form.expense_date)
    && form.description.trim().length > 0
));

const formattedSelectedDate = computed(() => {
    if (!form.expense_date) return '-';

    const [year, month, day] = form.expense_date.split('-').map(Number);
    return new Intl.DateTimeFormat('ar', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date(year, month - 1, day));
});

const formatCurrency = (value) => `${Number(value || 0).toFixed(2)} شيكل`;

const handleAttachment = (event) => {
    const file = event.target.files?.[0] || null;
    form.attachment = file;
    attachmentName.value = file?.name || '';
};

const submit = () => {
    form.post(route('finance.store-expense'), {
        forceFormData: true,
        preserveScroll: true,
    });
};
</script>
