<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    customers: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const asArray = (value) => Array.isArray(value)
    ? value
    : (value && typeof value === 'object' ? Object.values(value) : []);

const invoices = computed(() => asArray(props.data?.invoices));
const topProducts = computed(() => asArray(props.data?.top_products));
const salesByCategory = computed(() => asArray(props.data?.sales_by_category));
const paymentsByMethod = computed(() => asArray(props.data?.payments_by_method));

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
    customer_id: props.filters?.customer_id || '',
    category_id: props.filters?.category_id || '',
    product_id: props.filters?.product_id || '',
});

const expandedInvoiceId = ref(null);

const money = (value) => `${Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})} شيكل`;

const num = (value) => Number(value || 0).toLocaleString('en-US');

const date = (value) => {
    if (!value) return '—';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? value
        : parsed.toLocaleDateString('en-GB');
};

const enumValue = (value) => (
    typeof value === 'object' && value !== null
        ? (value.value ?? value.name ?? '')
        : value
);

const paymentMethodLabel = (value) => ({
    cash: 'كاش',
    bank_transfer: 'تحويل بنكي',
    banking_app: 'تطبيق بنكي',
    exchange_credit: 'رصيد استبدال',
}[enumValue(value)] || enumValue(value) || '—');

const paymentStatusLabel = (value) => ({
    paid: 'مدفوعة بالكامل',
    partially_paid: 'مدفوعة جزئياً',
    unpaid: 'غير مدفوعة',
}[enumValue(value)] || enumValue(value) || '—');

const setPeriod = (period) => {
    filters.period = period;
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const applyFilters = () => router.get(
    route('reports.sales'),
    { ...filters },
    { preserveState: true, replace: true }
);

const clearFilters = () => {
    Object.assign(filters, {
        period: '',
        start_date: '',
        end_date: '',
        customer_id: '',
        category_id: '',
        product_id: '',
    });
    applyFilters();
};

const printReport = () => window.open(
    route('reports.print', {
        type: 'sales',
        ...filters,
    }),
    '_blank'
);

const toggleInvoice = (id) => {
    expandedInvoiceId.value =
        expandedInvoiceId.value === id ? null : id;
};
</script>

<template>
    <Head title="تقرير المبيعات والأرباح" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black text-indigo-600 dark:text-indigo-400">
                        تقرير تفصيلي
                    </p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        المبيعات والأرباح
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        الفواتير، المنتجات المباعة، الأرباح، الدفعات والحساب المالي الذي استقبل كل دفعة.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button @click="printReport" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white hover:bg-indigo-700">
                        طباعة
                    </button>
                    <a :href="route('reports.sales.pdf', filters)" target="_blank" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-black text-white">
                        PDF
                    </a>
                    <a :href="route('reports.sales.excel', filters)" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">
                        Excel
                    </a>
                    <Link :href="route('reports.index')" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200">
                        العودة
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="period in periods"
                        :key="period[0]"
                        @click="setPeriod(period[0])"
                        class="rounded-xl px-3 py-2 text-xs font-black"
                        :class="filters.period === period[0]
                            ? 'bg-indigo-600 text-white'
                            : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'"
                    >
                        {{ period[1] }}
                    </button>

                    <input v-model="filters.start_date" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters" />
                    <input v-model="filters.end_date" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters" />

                    <select v-model="filters.customer_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters">
                        <option value="">جميع العملاء</option>
                        <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                            {{ customer.name }}
                        </option>
                    </select>

                    <select v-model="filters.category_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters">
                        <option value="">جميع الفئات</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">
                            {{ category.name }}
                        </option>
                    </select>

                    <select v-model="filters.product_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters">
                        <option value="">جميع المنتجات</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">
                            {{ product.name }}
                        </option>
                    </select>

                    <button @click="clearFilters" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-black text-slate-600 dark:border-slate-600 dark:text-slate-300">
                        مسح الفلاتر
                    </button>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                <article v-for="item in [
                    ['إجمالي المبيعات', money(data.summary?.total_sales)],
                    ['صافي المبيعات', money(data.summary?.net_sales)],
                    ['تكلفة البضاعة', money(data.summary?.total_cost)],
                    ['إجمالي الربح', money(data.summary?.total_profit)],
                    ['المحصل', money(data.summary?.total_paid)],
                    ['المتبقي', money(data.summary?.total_remaining)],
                    ['المرتجعات', money(data.summary?.total_returns)],
                    ['عدد الفواتير', num(data.summary?.invoice_count)],
                ]" :key="item[0]" class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[11px] font-bold text-slate-500">{{ item[0] }}</p>
                    <p class="mt-2 text-sm font-black text-slate-950 dark:text-white">{{ item[1] }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-end justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <div>
                        <h2 class="font-black text-slate-900 dark:text-white">
                            فواتير المبيعات بالتفصيل
                        </h2>
                        <p class="mt-1 text-xs text-slate-500">
                            اضغط "التفاصيل" لرؤية كل صنف والدفعات والحساب المالي.
                        </p>
                    </div>
                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-black text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300">
                        {{ invoices.length }} فاتورة
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1100px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">الفاتورة</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">العميل</th>
                                <th class="px-4 py-3 text-right">الإجمالي</th>
                                <th class="px-4 py-3 text-right">المدفوع</th>
                                <th class="px-4 py-3 text-right">المتبقي</th>
                                <th class="px-4 py-3 text-right">الربح</th>
                                <th class="px-4 py-3 text-right">حالة الدفع</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <template v-for="invoice in invoices" :key="invoice.id">
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-700/30">
                                    <td dir="ltr" class="px-4 py-3 text-right font-black text-indigo-700 dark:text-indigo-300">
                                        {{ invoice.invoice_number }}
                                    </td>
                                    <td class="px-4 py-3">{{ date(invoice.sale_date) }}</td>
                                    <td class="px-4 py-3">
                                        <strong class="block dark:text-white">
                                            {{ invoice.customer?.name || invoice.customer_name || 'عميل نقدي' }}
                                        </strong>
                                        <span class="text-xs text-slate-400">
                                            {{ invoice.customer?.phone || invoice.customer_phone || '' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 font-black">{{ money(invoice.total_amount) }}</td>
                                    <td class="px-4 py-3 text-emerald-600">{{ money(invoice.paid_amount) }}</td>
                                    <td class="px-4 py-3 text-amber-600">{{ money(invoice.remaining_amount) }}</td>
                                    <td class="px-4 py-3 text-indigo-600">{{ money(invoice.gross_profit) }}</td>
                                    <td class="px-4 py-3">{{ paymentStatusLabel(invoice.payment_status) }}</td>
                                    <td class="px-4 py-3">
                                        <button @click="toggleInvoice(invoice.id)" class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-black dark:bg-slate-700 dark:text-white">
                                            {{ expandedInvoiceId === invoice.id ? 'إخفاء' : 'التفاصيل' }}
                                        </button>
                                    </td>
                                </tr>

                                <tr v-if="expandedInvoiceId === invoice.id">
                                    <td colspan="9" class="bg-slate-50 p-4 dark:bg-slate-900/40">
                                        <div class="grid gap-4 xl:grid-cols-[1fr_360px]">
                                            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                                                <table class="w-full text-xs">
                                                    <thead class="bg-slate-100 dark:bg-slate-700">
                                                        <tr>
                                                            <th class="px-3 py-2 text-right">المنتج</th>
                                                            <th class="px-3 py-2 text-right">الكمية</th>
                                                            <th class="px-3 py-2 text-right">سعر البيع</th>
                                                            <th class="px-3 py-2 text-right">الخصم</th>
                                                            <th class="px-3 py-2 text-right">الإجمالي</th>
                                                            <th class="px-3 py-2 text-right">الربح</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr v-for="line in asArray(invoice.items)" :key="line.id" class="border-t border-slate-100 dark:border-slate-700">
                                                            <td class="px-3 py-2">
                                                                <strong>{{ line.product?.name || line.product_name || 'منتج' }}</strong>
                                                                <span dir="ltr" class="block text-right text-[10px] text-slate-400">
                                                                    {{ line.product?.code || line.product_code || '' }}
                                                                </span>
                                                            </td>
                                                            <td class="px-3 py-2">{{ num(line.quantity) }}</td>
                                                            <td class="px-3 py-2">{{ money(line.unit_selling_price) }}</td>
                                                            <td class="px-3 py-2">
                                                                {{ money(Number(line.line_discount || 0) + Number(line.allocated_invoice_discount || 0)) }}
                                                            </td>
                                                            <td class="px-3 py-2 font-black">{{ money(line.line_total) }}</td>
                                                            <td class="px-3 py-2 text-emerald-600">{{ money(line.line_profit) }}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <aside class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                                                <h3 class="text-sm font-black dark:text-white">
                                                    الدفعات والحسابات المالية
                                                </h3>

                                                <div v-if="asArray(invoice.payments).length" class="mt-3 space-y-2">
                                                    <div v-for="payment in asArray(invoice.payments)" :key="payment.id" class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900">
                                                        <div class="flex justify-between gap-3">
                                                            <span class="font-bold">{{ paymentMethodLabel(payment.payment_method) }}</span>
                                                            <strong>{{ money(payment.amount) }}</strong>
                                                        </div>
                                                        <p class="mt-1 text-[11px] text-slate-500">
                                                            الحساب:
                                                            {{ payment.financial_account?.name || payment.financialAccount?.name || 'غير محدد' }}
                                                        </p>
                                                        <p v-if="payment.transaction_reference" class="mt-1 text-[11px] text-slate-400">
                                                            المرجع: {{ payment.transaction_reference }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <p v-else class="mt-3 text-xs text-slate-500">
                                                    لا توجد دفعات مسجلة.
                                                </p>
                                            </aside>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="!invoices.length">
                                <td colspan="9" class="py-12 text-center text-slate-500">
                                    لا توجد فواتير ضمن الفلاتر المحددة.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-3">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h3 class="font-black dark:text-white">المنتجات الأكثر مبيعاً</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="product in topProducts" :key="product.id" class="flex justify-between gap-4 border-b border-slate-100 py-2 text-xs dark:border-slate-700">
                            <div>
                                <strong class="block">{{ product.name }}</strong>
                                <span class="text-slate-400">{{ num(product.total_quantity) }} قطعة</span>
                            </div>
                            <div class="text-left">
                                <strong class="block">{{ money(product.total_revenue) }}</strong>
                                <span class="text-emerald-600">ربح {{ money(product.total_profit) }}</span>
                            </div>
                        </div>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h3 class="font-black dark:text-white">المبيعات حسب الفئة</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="row in salesByCategory" :key="row.name" class="flex justify-between border-b border-slate-100 py-2 text-xs dark:border-slate-700">
                            <span>{{ row.name }}</span>
                            <strong>{{ money(row.total) }}</strong>
                        </div>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h3 class="font-black dark:text-white">المقبوضات حسب طريقة الدفع</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="row in paymentsByMethod" :key="enumValue(row.payment_method)" class="flex justify-between border-b border-slate-100 py-2 text-xs dark:border-slate-700">
                            <span>{{ paymentMethodLabel(row.payment_method) }}</span>
                            <strong>{{ money(row.total) }}</strong>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
