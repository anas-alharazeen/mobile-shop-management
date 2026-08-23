<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { usePrint } from '@/composables/usePrint';

const props = defineProps({
    store: {
        type: Object,
        default: () => ({}),
    },

    account: {
        type: Object,
        required: true,
    },

    transactions: {
        type: Array,
        default: () => [],
    },

    summary: {
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

const { printPage } = usePrint();

const enumValue = (value) => (
    typeof value === 'object' && value !== null
        ? (value.value ?? value.name ?? '')
        : value
);

const formatCurrency = (value) => `${Number(value || 0).toLocaleString('ar-PS', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})} شيكل`;

const formatDateTime = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('ar-PS', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
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

const periodLabel = () => {
    const start = props.filters?.start_date;
    const end = props.filters?.end_date;

    if (!start && !end) {
        return 'جميع الحركات المسجلة';
    }

    return `من ${start || 'البداية'} إلى ${end || 'اليوم'}`;
};
</script>

<template>
    <Head :title="`كشف حساب ${account.name}`" />

    <main dir="rtl" class="print-page min-h-screen bg-slate-100 p-4 text-slate-900 sm:p-8 print:min-h-0 print:bg-white print:p-0">
        <article class="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-white shadow-2xl print:max-w-none print:rounded-none print:shadow-none">
            <header class="relative overflow-hidden bg-gradient-to-l from-slate-950 via-indigo-950 to-indigo-800 p-7 text-white">
                <div class="absolute -left-20 -top-24 h-64 w-64 rounded-full bg-cyan-300/10 blur-3xl print:hidden"></div>
                <div class="relative flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/15 bg-white/10 p-2">
                            <img v-if="store.store_logo" :src="`/storage/${store.store_logo}`" alt="شعار المعرض" class="h-full w-full object-contain" />
                            <svg v-else class="h-9 w-9" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 10h18M7 15h1m4 0h2m-9 5h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <div>
                            <p class="text-xs font-bold text-indigo-200">{{ store.store_name || 'فنانة فون' }}</p>
                            <h1 class="mt-1 text-2xl font-black sm:text-3xl">كشف حساب مالي تفصيلي</h1>
                            <p class="mt-2 text-sm text-indigo-100/80">{{ account.name }} · {{ account.type_label || enumValue(account.type) }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 px-5 py-4 text-sm backdrop-blur">
                        <p class="text-xs text-indigo-200">الفترة</p>
                        <p class="mt-1 font-black">{{ periodLabel() }}</p>
                        <p class="mt-3 text-xs text-indigo-200">تاريخ الإصدار</p>
                        <p dir="ltr" class="mt-1 text-right font-black">{{ formatDateTime(summary.generated_at) }}</p>
                    </div>
                </div>
            </header>

            <div class="space-y-6 p-6 sm:p-7">
                <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <p class="text-xs font-bold text-slate-500">رصيد بداية الفترة</p>
                        <p dir="ltr" class="mt-2 text-right text-lg font-black">{{ formatCurrency(summary.opening_balance) }}</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                        <p class="text-xs font-bold text-emerald-700">إجمالي الوارد</p>
                        <p dir="ltr" class="mt-2 text-right text-lg font-black text-emerald-700">{{ formatCurrency(summary.total_inflows) }}</p>
                    </div>
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-4">
                        <p class="text-xs font-bold text-rose-700">إجمالي الصادر</p>
                        <p dir="ltr" class="mt-2 text-right text-lg font-black text-rose-700">{{ formatCurrency(summary.total_outflows) }}</p>
                    </div>
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                        <p class="text-xs font-bold text-indigo-700">صافي الحركة</p>
                        <p dir="ltr" class="mt-2 text-right text-lg font-black text-indigo-700">{{ formatCurrency(summary.net_flow) }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-300 bg-slate-900 p-4 text-white">
                        <p class="text-xs font-bold text-slate-300">رصيد نهاية الفترة</p>
                        <p dir="ltr" class="mt-2 text-right text-lg font-black">{{ formatCurrency(summary.closing_balance) }}</p>
                    </div>
                </section>

                <section class="overflow-hidden rounded-2xl border border-slate-200">
                    <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                        <div>
                            <h2 class="font-black">تفاصيل الحركات</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ summary.transaction_count || 0 }} حركة مالية</p>
                        </div>
                        <p class="text-xs text-slate-500">العملة: الشيكل</p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[1180px] border-collapse text-[11px]">
                            <thead class="bg-slate-900 text-white">
                                <tr>
                                    <th class="px-3 py-3 text-right">#</th>
                                    <th class="px-3 py-3 text-right">التاريخ</th>
                                    <th class="px-3 py-3 text-right">النوع</th>
                                    <th class="px-3 py-3 text-right">البيان</th>
                                    <th class="px-3 py-3 text-center">الاتجاه</th>
                                    <th class="px-3 py-3 text-left">المبلغ</th>
                                    <th class="px-3 py-3 text-left">قبل الحركة</th>
                                    <th class="px-3 py-3 text-left">بعد الحركة</th>
                                    <th class="px-3 py-3 text-right">المرجع</th>
                                    <th class="px-3 py-3 text-right">الملاحظات</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-200">
                                <tr v-for="(transaction, index) in transactions" :key="transaction.id" class="odd:bg-white even:bg-slate-50">
                                    <td class="px-3 py-3 font-black">{{ index + 1 }}</td>
                                    <td class="whitespace-nowrap px-3 py-3">{{ formatDateTime(transaction.transaction_date) }}</td>
                                    <td class="px-3 py-3 font-bold">{{ typeLabel(transaction.type) }}</td>
                                    <td class="max-w-xs px-3 py-3 font-bold">{{ transaction.description || 'حركة مالية' }}</td>
                                    <td class="px-3 py-3 text-center font-black" :class="enumValue(transaction.direction) === 'inflow' ? 'text-emerald-700' : 'text-rose-700'">{{ directionLabel(transaction.direction) }}</td>
                                    <td dir="ltr" class="whitespace-nowrap px-3 py-3 text-left font-black" :class="enumValue(transaction.direction) === 'inflow' ? 'text-emerald-700' : 'text-rose-700'">
                                        {{ enumValue(transaction.direction) === 'inflow' ? '+' : '-' }}{{ formatCurrency(transaction.amount) }}
                                    </td>
                                    <td dir="ltr" class="whitespace-nowrap px-3 py-3 text-left">{{ formatCurrency(transaction.balance_before) }}</td>
                                    <td dir="ltr" class="whitespace-nowrap px-3 py-3 text-left font-black">{{ formatCurrency(transaction.balance_after) }}</td>
                                    <td dir="ltr" class="px-3 py-3 text-right">{{ transaction.reference_type || '—' }}{{ transaction.reference_id ? ` #${transaction.reference_id}` : '' }}</td>
                                    <td class="max-w-xs px-3 py-3 text-slate-600">{{ transaction.notes || '—' }}</td>
                                </tr>

                                <tr v-if="!transactions.length">
                                    <td colspan="10" class="px-4 py-12 text-center text-sm text-slate-500">لا توجد حركات مطابقة للفترة والفلاتر المحددة.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <footer class="grid gap-3 border-t border-slate-200 pt-5 text-xs text-slate-500 sm:grid-cols-2">
                    <div>
                        <p class="font-bold text-slate-700">{{ store.store_name || 'فنانة فون' }}</p>
                        <p class="mt-1">{{ store.store_address || '' }}</p>
                        <p dir="ltr" class="mt-1 text-right">{{ store.store_phone || '' }}</p>
                    </div>
                    <div class="sm:text-left">
                        <p>هذا الكشف صادر آلياً من النظام وفق الحركات المسجلة وقت الإصدار.</p>
                        <p class="mt-1">الحساب: {{ account.name }}</p>
                    </div>
                </footer>

                <div class="flex justify-center gap-3 print:hidden">
                    <button type="button" class="rounded-xl bg-indigo-600 px-6 py-3 font-black text-white transition hover:bg-indigo-700" @click="printPage">طباعة الكشف</button>
                    <Link :href="route('finance.show-account', account.id)" class="rounded-xl border border-slate-300 px-6 py-3 font-black text-slate-700 transition hover:bg-slate-50">العودة للحساب</Link>
                </div>
            </div>
        </article>
    </main>
</template>

<style>
@page {
    size: A4 landscape;
    margin: 8mm;
}

@media print {
    html,
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-page {
        width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    article {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    thead {
        display: table-header-group;
    }

    tr,
    td,
    th {
        page-break-inside: avoid;
    }

    a[href]::after {
        content: none !important;
    }
}
</style>
