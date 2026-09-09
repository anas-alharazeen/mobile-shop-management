<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const asArray = (value) => Array.isArray(value)
    ? value
    : (value && typeof value === 'object' ? Object.values(value) : []);

const accounts = computed(() => asArray(props.data?.accounts));
const transactions = computed(() => asArray(props.data?.transactions));
const transfers = computed(() => asArray(props.data?.transfers));
const closings = computed(() => asArray(props.data?.closings));

const periods = [
    ['today', 'اليوم'],
    ['yesterday', 'أمس'],
    ['last_7_days', 'آخر 7 أيام'],
    ['this_week', 'هذا الأسبوع'],
    ['this_month', 'هذا الشهر'],
    ['last_month', 'الشهر الماضي'],
    ['this_year', 'هذه السنة'],
];

const filters = reactive({
    period: props.filters?.period || '',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

const money = (value) => `${Number(value || 0).toLocaleString('ar-PS', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})} شيكل`;

const dateTime = (value) => {
    if (!value) return '—';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? value
        : parsed.toLocaleString('ar-PS');
};

const date = (value) => {
    if (!value) return '—';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? value
        : parsed.toLocaleDateString('ar-PS');
};

const enumValue = (value) => (
    typeof value === 'object' && value !== null
        ? (value.value ?? value.name ?? '')
        : value
);

const directionLabel = (value) => ({
    inflow: 'وارد',
    outflow: 'صادر',
}[enumValue(value)] || enumValue(value) || '—');

const transactionTypeLabel = (value) => ({
    sale_payment: 'دفعة مبيعات',
    repair_payment: 'دفعة صيانة',
    repair_part_purchase: 'شراء قطعة صيانة خارجية',
    purchase_payment: 'دفعة مشتريات',
    sales_refund: 'استرداد مرتجع مبيعات',
    purchase_refund: 'استرداد مرتجع مشتريات',
    exchange_difference: 'تسوية فرق استبدال',
    expense: 'مصروف',
    manual_deposit: 'إيداع يدوي',
    manual_withdrawal: 'سحب يدوي',
    balance_adjustment: 'تسوية رصيد',
    account_transfer: 'تحويل بين الحسابات',
    reversal: 'عكس حركة',
}[enumValue(value)] || enumValue(value) || '—');

const transferStatusLabel = (value) => ({
    posted: 'مكتمل',
    cancelled: 'ملغي',
}[enumValue(value)] || enumValue(value) || '—');

const agingLabels = {
    not_due: 'غير مستحق',
    '1_7_days': '1-7 أيام',
    '8_30_days': '8-30 يوماً',
    '31_60_days': '31-60 يوماً',
    over_60_days: 'أكثر من 60 يوماً',
};

const setPeriod = (period) => {
    filters.period = period;
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const applyFilters = () => router.get(
    route('reports.finance'),
    { ...filters },
    { preserveState: true, replace: true }
);

const clearFilters = () => {
    Object.assign(filters, {
        period: '',
        start_date: '',
        end_date: '',
    });
    applyFilters();
};

const printReport = () => window.open(
    route('reports.print', {
        type: 'finance',
        ...filters,
    }),
    '_blank'
);
</script>

<template>
    <Head title="التقرير المالي" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black text-emerald-600">تقرير تفصيلي</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        الدفعات والديون والحسابات
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        التدفقات المالية والحسابات والحركات والديون والإغلاقات اليومية.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button @click="printReport" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white">طباعة</button>
                    <a :href="route('reports.finance.pdf', filters)" target="_blank" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-black text-white">PDF</a>
                    <a :href="route('reports.finance.excel', filters)" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Excel</a>
                    <Link :href="route('reports.index')" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200">العودة</Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-wrap gap-2">
                    <button v-for="period in periods" :key="period[0]" @click="setPeriod(period[0])" class="rounded-xl px-3 py-2 text-xs font-black" :class="filters.period === period[0] ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">
                        {{ period[1] }}
                    </button>
                    <input v-model="filters.start_date" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters" />
                    <input v-model="filters.end_date" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters" />
                    <button @click="clearFilters" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-black dark:border-slate-600 dark:text-slate-300">مسح</button>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                <article v-for="item in [
                    ['إجمالي المقبوضات', money(data.summary?.total_collected)],
                    ['مقبوضات المبيعات', money(data.summary?.collected_sales)],
                    ['مقبوضات الصيانة', money(data.summary?.collected_repairs)],
                    ['مدفوع للموردين', money(data.summary?.paid_to_suppliers)],
                    ['المصروفات', money(data.summary?.expenses)],
                    ['صافي التدفق', money(data.summary?.net_cash_flow)],
                    ['حجم التحويلات الداخلية', money(data.summary?.transfer_volume)],
                    ['عدد التحويلات', Number(data.summary?.transfer_count || 0).toLocaleString('ar-PS')],
                    ['مستحقات العملاء', money(data.summary?.customer_debts)],
                    ['مستحقات الموردين', money(data.summary?.supplier_debts)],
                ]" :key="item[0]" class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[11px] text-slate-500">{{ item[0] }}</p>
                    <p class="mt-2 text-sm font-black dark:text-white">{{ item[1] }}</p>
                </article>
            </section>

            <section class="grid gap-5 xl:grid-cols-[1fr_1.15fr]">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h2 class="font-black dark:text-white">أرصدة الحسابات المالية</h2>
                    <div class="mt-4 space-y-2">
                        <div v-for="account in accounts" :key="account.id" class="flex items-center justify-between rounded-xl bg-slate-50 p-3 text-sm dark:bg-slate-900">
                            <div>
                                <strong class="block">{{ account.name }}</strong>
                                <span class="text-xs text-slate-400">
                                    {{ account.type_label || enumValue(account.type) }}
                                </span>
                            </div>
                            <strong>{{ money(account.current_balance) }}</strong>
                        </div>
                        <p v-if="!accounts.length" class="text-xs text-slate-500">لا توجد حسابات مالية.</p>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h2 class="font-black dark:text-white">أعمار ديون العملاء</h2>
                    <div class="mt-4 space-y-2">
                        <div v-for="(amount, key) in data.aging || {}" :key="key" class="flex justify-between border-b border-slate-100 py-2 text-sm dark:border-slate-700">
                            <span>{{ agingLabels[key] || key }}</span>
                            <strong>{{ money(amount) }}</strong>
                        </div>
                    </div>
                </article>
            </section>

            <section v-if="transfers.length" class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">التحويلات بين الحسابات</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ transfers.length }} تحويل ضمن الفترة. لا تدخل قيمتها ضمن إجمالي الوارد والصادر العام.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[900px] w-full text-sm">
                        <thead class="bg-slate-100 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-right">رقم التحويل</th>
                                <th class="px-4 py-3 text-right">من حساب</th>
                                <th class="px-4 py-3 text-right">إلى حساب</th>
                                <th class="px-4 py-3 text-right">المبلغ</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="transfer in transfers" :key="transfer.id">
                                <td class="px-4 py-3 font-black">{{ transfer.transfer_number }}</td>
                                <td class="px-4 py-3">{{ transfer.from_account?.name || '—' }}</td>
                                <td class="px-4 py-3">{{ transfer.to_account?.name || '—' }}</td>
                                <td class="px-4 py-3 font-black">{{ money(transfer.amount) }}</td>
                                <td class="px-4 py-3">{{ dateTime(transfer.transfer_date) }}</td>
                                <td class="px-4 py-3">{{ transferStatusLabel(transfer.status) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">الحركات المالية التفصيلية</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ transactions.length }} حركة ضمن الفترة.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1200px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">الحساب</th>
                                <th class="px-4 py-3 text-right">نوع الحركة</th>
                                <th class="px-4 py-3 text-right">الاتجاه</th>
                                <th class="px-4 py-3 text-right">الوصف</th>
                                <th class="px-4 py-3 text-right">المبلغ</th>
                                <th class="px-4 py-3 text-right">الرصيد قبل</th>
                                <th class="px-4 py-3 text-right">الرصيد بعد</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="transaction in transactions" :key="transaction.id">
                                <td class="px-4 py-3">{{ dateTime(transaction.transaction_date) }}</td>
                                <td class="px-4 py-3 font-bold">
                                    {{ transaction.account?.name || transaction.financial_account?.name || '—' }}
                                </td>
                                <td class="px-4 py-3">{{ transactionTypeLabel(transaction.type) }}</td>
                                <td class="px-4 py-3" :class="enumValue(transaction.direction) === 'inflow' ? 'text-emerald-600' : 'text-rose-600'">
                                    {{ directionLabel(transaction.direction) }}
                                </td>
                                <td class="px-4 py-3 text-xs">{{ transaction.description || '—' }}</td>
                                <td class="px-4 py-3 font-black">{{ money(transaction.amount) }}</td>
                                <td class="px-4 py-3">{{ money(transaction.balance_before) }}</td>
                                <td class="px-4 py-3">{{ money(transaction.balance_after) }}</td>
                            </tr>

                            <tr v-if="!transactions.length">
                                <td colspan="8" class="py-12 text-center text-slate-500">
                                    لا توجد حركات مالية ضمن الفترة.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">الإغلاقات اليومية</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[800px] w-full text-sm">
                        <thead class="bg-slate-100 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">الحساب</th>
                                <th class="px-4 py-3 text-right">المتوقع</th>
                                <th class="px-4 py-3 text-right">الفعلي</th>
                                <th class="px-4 py-3 text-right">الفرق</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="closing in closings" :key="closing.id" class="border-t border-slate-100 dark:border-slate-700">
                                <td class="px-4 py-3">{{ date(closing.closing_date) }}</td>
                                <td class="px-4 py-3">{{ closing.account?.name || '—' }}</td>
                                <td class="px-4 py-3">{{ money(closing.expected_balance) }}</td>
                                <td class="px-4 py-3">{{ money(closing.actual_balance) }}</td>
                                <td class="px-4 py-3">{{ money(closing.difference) }}</td>
                            </tr>

                            <tr v-if="!closings.length">
                                <td colspan="5" class="py-10 text-center text-slate-500">
                                    لا توجد إغلاقات يومية ضمن الفترة.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
