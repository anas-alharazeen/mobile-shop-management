<script setup>
import { computed, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    account: {
        type: Object,
        required: true,
    },

    transactions: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            total: 0,
            from: 0,
            to: 0,
        }),
    },

    stats: {
        type: Object,
        default: () => ({}),
    },

    filters: {
        type: Object,
        default: () => ({}),
    },

    transactionTypes: {
        type: Object,
        default: () => ({}),
    },

    directions: {
        type: Object,
        default: () => ({}),
    },
});

const form = reactive({
    search: props.filters?.search ?? '',
    type: props.filters?.type ?? '',
    direction: props.filters?.direction ?? '',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
});

let searchTimer = null;

const enumValue = (value) => (
    typeof value === 'object' && value !== null
        ? (value.value ?? value.name ?? '')
        : value
);

const formatCurrency = (value) => `${Number(value || 0).toLocaleString('ar-PS', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})} شيكل`;

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('ar-PS', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(parsed);
};

const typeLabel = (value) => (
    props.transactionTypes?.[enumValue(value)]
    ?? enumValue(value)
    ?? 'غير محدد'
);

const directionLabel = (value) => (
    props.directions?.[enumValue(value)]
    ?? enumValue(value)
    ?? 'غير محدد'
);

const queryParameters = () => {
    const query = {};

    Object.entries(form).forEach(([key, value]) => {
        if (String(value ?? '').trim() !== '') {
            query[key] = value;
        }
    });

    return query;
};

const printUrl = computed(() => route('finance.print-account', {
    financialAccount: props.account.id,
    ...queryParameters(),
}));

const loadTransactions = () => {
    router.get(
        route('finance.show-account', props.account.id),
        queryParameters(),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const delayedSearch = () => {
    window.clearTimeout(searchTimer);
    searchTimer = window.setTimeout(loadTransactions, 350);
};

const clearFilters = () => {
    form.search = '';
    form.type = '';
    form.direction = '';
    form.start_date = '';
    form.end_date = '';

    loadTransactions();
};

const toggleAccount = () => {
    const action = props.account.is_active ? 'تعطيل' : 'تفعيل';

    if (!window.confirm(`${action} حساب «${props.account.name}»؟`)) {
        return;
    }

    router.patch(
        route('finance.toggle-account', props.account.id),
        { is_active: !props.account.is_active },
        { preserveScroll: true }
    );
};
</script>

<template>
    <Head :title="`الحساب المالي - ${account.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="text-xs font-black uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                            المركز المالي
                        </p>

                        <span
                            class="rounded-full px-2.5 py-1 text-[11px] font-black"
                            :class="account.is_active
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                        >
                            {{ account.is_active ? 'حساب نشط' : 'حساب معطل' }}
                        </span>
                    </div>

                    <h1 class="mt-2 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                        {{ account.name }}
                    </h1>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        كشف تفصيلي لجميع الحركات الواردة والصادرة والأرصدة المتتابعة.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="printUrl"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 9V3.75h10.5V9m-10.5 8.25H5.25A2.25 2.25 0 013 15V9.75A2.25 2.25 0 015.25 7.5h13.5A2.25 2.25 0 0121 9.75V15a2.25 2.25 0 01-2.25 2.25h-1.5M6.75 13.5h10.5v6.75H6.75V13.5z" />
                        </svg>
                        طباعة كشف الحساب
                    </a>

                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border px-4 py-2.5 text-sm font-black transition"
                        :class="account.is_active
                            ? 'border-rose-200 bg-white text-rose-600 hover:bg-rose-50 dark:border-rose-900 dark:bg-slate-800 dark:text-rose-300'
                            : 'border-emerald-200 bg-white text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900 dark:bg-slate-800 dark:text-emerald-300'"
                        @click="toggleAccount"
                    >
                        {{ account.is_active ? 'تعطيل الحساب' : 'تفعيل الحساب' }}
                    </button>

                    <Link
                        :href="route('finance.index')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        العودة
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="relative overflow-hidden rounded-3xl bg-gradient-to-l from-slate-950 via-indigo-950 to-indigo-800 p-6 text-white shadow-2xl shadow-indigo-950/20">
                <div class="absolute -left-20 -top-24 h-64 w-64 rounded-full bg-cyan-300/10 blur-3xl"></div>
                <div class="absolute -bottom-28 right-1/3 h-64 w-64 rounded-full bg-indigo-300/10 blur-3xl"></div>

                <div class="relative grid gap-6 xl:grid-cols-[minmax(0,1fr)_minmax(520px,auto)] xl:items-end">
                    <div>
                        <span class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-black text-indigo-100">
                            {{ account.type_label || enumValue(account.type) }}
                        </span>

                        <p class="mt-5 text-sm text-indigo-200">الرصيد الحالي</p>
                        <p dir="ltr" class="mt-1 text-right text-4xl font-black tracking-tight sm:text-5xl">
                            {{ formatCurrency(account.current_balance) }}
                        </p>

                        <p class="mt-3 max-w-2xl text-sm leading-7 text-indigo-100/80">
                            {{ account.description || 'حساب مالي ضمن منظومة فنانة فون.' }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <p class="text-xs text-indigo-200">رصيد بداية الفترة</p>
                            <p dir="ltr" class="mt-2 text-right text-lg font-black">{{ formatCurrency(stats.period_opening_balance) }}</p>
                        </div>

                        <div class="rounded-2xl border border-emerald-300/15 bg-emerald-300/10 p-4 backdrop-blur">
                            <p class="text-xs text-emerald-100">إجمالي الوارد</p>
                            <p dir="ltr" class="mt-2 text-right text-lg font-black text-emerald-200">{{ formatCurrency(stats.total_inflows) }}</p>
                        </div>

                        <div class="rounded-2xl border border-rose-300/15 bg-rose-300/10 p-4 backdrop-blur">
                            <p class="text-xs text-rose-100">إجمالي الصادر</p>
                            <p dir="ltr" class="mt-2 text-right text-lg font-black text-rose-200">{{ formatCurrency(stats.total_outflows) }}</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <p class="text-xs text-indigo-200">رصيد نهاية الفترة</p>
                            <p dir="ltr" class="mt-2 text-right text-lg font-black">{{ formatCurrency(stats.period_closing_balance) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="grid gap-3 lg:grid-cols-[minmax(220px,1fr)_190px_170px_170px_170px_auto]">
                    <div class="relative">
                        <svg class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" />
                        </svg>
                        <input
                            v-model="form.search"
                            type="search"
                            placeholder="بحث في الوصف أو الملاحظات أو المرجع..."
                            class="block w-full rounded-xl border-slate-300 bg-white py-2.5 pr-11 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            @input="delayedSearch"
                        />
                    </div>

                    <select v-model="form.type" class="rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="loadTransactions">
                        <option value="">كل أنواع الحركات</option>
                        <option v-for="(label, value) in transactionTypes" :key="value" :value="value">{{ label }}</option>
                    </select>

                    <select v-model="form.direction" class="rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="loadTransactions">
                        <option value="">وارد وصادر</option>
                        <option v-for="(label, value) in directions" :key="value" :value="value">{{ label }}</option>
                    </select>

                    <input v-model="form.start_date" type="date" aria-label="من تاريخ" class="rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="loadTransactions" />
                    <input v-model="form.end_date" type="date" aria-label="إلى تاريخ" class="rounded-xl border-slate-300 bg-white text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="loadTransactions" />

                    <button type="button" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black text-slate-600 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-700" @click="clearFilters">
                        مسح
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="font-black text-slate-950 dark:text-white">الحركات المالية</h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            {{ transactions.total || 0 }} حركة مطابقة · صافي الحركة {{ formatCurrency(stats.net_flow) }}
                        </p>
                    </div>

                    <div class="flex gap-2 text-xs font-black">
                        <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">وارد {{ formatCurrency(stats.total_inflows) }}</span>
                        <span class="rounded-full bg-rose-50 px-3 py-1.5 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">صادر {{ formatCurrency(stats.total_outflows) }}</span>
                    </div>
                </div>

                <div v-if="transactions.data?.length" class="overflow-x-auto">
                    <table class="min-w-[1180px] w-full text-sm">
                        <thead class="bg-slate-50 text-xs font-black text-slate-500 dark:bg-slate-900/60 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">نوع الحركة</th>
                                <th class="px-4 py-3 text-right">البيان</th>
                                <th class="px-4 py-3 text-center">الاتجاه</th>
                                <th class="px-4 py-3 text-left">المبلغ</th>
                                <th class="px-4 py-3 text-left">الرصيد قبل</th>
                                <th class="px-4 py-3 text-left">الرصيد بعد</th>
                                <th class="px-4 py-3 text-right">المرجع / الملاحظات</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="transaction in transactions.data" :key="transaction.id" class="align-top transition hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                <td class="whitespace-nowrap px-4 py-4 text-slate-600 dark:text-slate-300">{{ formatDate(transaction.transaction_date) }}</td>
                                <td class="px-4 py-4">
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-black text-slate-600 dark:bg-slate-700 dark:text-slate-200">{{ typeLabel(transaction.type) }}</span>
                                </td>
                                <td class="max-w-sm px-4 py-4">
                                    <p class="font-black text-slate-900 dark:text-white">{{ transaction.description || 'حركة مالية' }}</p>
                                    <p v-if="transaction.notes" class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{{ transaction.notes }}</p>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-black" :class="enumValue(transaction.direction) === 'inflow' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'">
                                        {{ directionLabel(transaction.direction) }}
                                    </span>
                                </td>
                                <td dir="ltr" class="whitespace-nowrap px-4 py-4 text-left font-black" :class="enumValue(transaction.direction) === 'inflow' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ enumValue(transaction.direction) === 'inflow' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                                </td>
                                <td dir="ltr" class="whitespace-nowrap px-4 py-4 text-left text-slate-600 dark:text-slate-300">{{ formatCurrency(transaction.balance_before) }}</td>
                                <td dir="ltr" class="whitespace-nowrap px-4 py-4 text-left font-black text-slate-900 dark:text-white">{{ formatCurrency(transaction.balance_after) }}</td>
                                <td class="px-4 py-4 text-xs text-slate-500 dark:text-slate-400">
                                    <div v-if="transaction.reference_type || transaction.reference_id" dir="ltr" class="text-right">{{ transaction.reference_type || 'reference' }} #{{ transaction.reference_id || '—' }}</div>
                                    <div v-else>—</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-6 py-16 text-center">
                    <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-slate-100 text-slate-400 dark:bg-slate-700">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 10h18M7 15h1m4 0h2m-9 5h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h3 class="mt-4 font-black text-slate-900 dark:text-white">لا توجد حركات مطابقة</h3>
                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">غيّر الفلاتر أو ابدأ بتسجيل العمليات على هذا الحساب.</p>
                </div>

                <div v-if="transactions.total > 0" class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        عرض {{ transactions.from || 0 }} إلى {{ transactions.to || 0 }} من {{ transactions.total || 0 }} حركة
                    </p>
                    <Pagination v-if="transactions.links?.length" :links="transactions.links" />
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
