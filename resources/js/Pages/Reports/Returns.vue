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

const salesReturns = computed(() => asArray(props.data?.sales_returns));
const purchaseReturns = computed(() => asArray(props.data?.purchase_returns));
const exchanges = computed(() => asArray(props.data?.exchanges));
const topProducts = computed(() => asArray(props.data?.top_returned_products));

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

const date = (value) => {
    if (!value) return '—';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? value
        : parsed.toLocaleDateString('ar-PS');
};

const settlementLabel = (value) => ({
    customer_pays: 'العميل يدفع الفرق',
    customer_gets_refund: 'رد فرق للعميل',
    no_difference: 'بدون فرق',
}[value] || value || '—');

const setPeriod = (period) => {
    filters.period = period;
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const applyFilters = () => router.get(
    route('reports.returns'),
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
        type: 'returns',
        ...filters,
    }),
    '_blank'
);
</script>

<template>
    <Head title="تقرير المرتجعات والاستبدال" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black text-rose-600">تقرير تفصيلي</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        المرتجعات والاستبدال
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        مرتجعات البيع والشراء والاستبدالات والتسويات.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button @click="printReport" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white">طباعة</button>
                    <a :href="route('reports.returns.pdf', filters)" target="_blank" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-black text-white">PDF</a>
                    <a :href="route('reports.returns.excel', filters)" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Excel</a>
                    <Link :href="route('reports.index')" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200">العودة</Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-wrap gap-2">
                    <button v-for="period in periods" :key="period[0]" @click="setPeriod(period[0])" class="rounded-xl px-3 py-2 text-xs font-black" :class="filters.period === period[0] ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">
                        {{ period[1] }}
                    </button>
                    <input v-model="filters.start_date" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters" />
                    <input v-model="filters.end_date" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters" />
                    <button @click="clearFilters" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-black dark:border-slate-600 dark:text-slate-300">مسح</button>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                <article v-for="item in [
                    ['مرتجعات المبيعات', money(data.summary?.total_sales_returns)],
                    ['مسترد للعملاء', money(data.summary?.refunded_to_customers)],
                    ['خفض ديون العملاء', money(data.summary?.debt_reduced)],
                    ['مرتجعات المشتريات', money(data.summary?.total_purchase_returns)],
                    ['مسترد من الموردين', money(data.summary?.refunded_from_suppliers)],
                    ['خفض مستحق المورد', money(data.summary?.supplier_debt_reduced)],
                    ['الاستبدالات', data.summary?.exchange_count || 0],
                    ['فرق الأسعار', money(data.summary?.total_price_difference)],
                ]" :key="item[0]" class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[11px] text-slate-500">{{ item[0] }}</p>
                    <p class="mt-2 text-sm font-black dark:text-white">{{ item[1] }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">مرتجعات المبيعات</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1000px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">رقم المرتجع</th>
                                <th class="px-4 py-3 text-right">العميل</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">الأصناف</th>
                                <th class="px-4 py-3 text-right">القيمة</th>
                                <th class="px-4 py-3 text-right">خفض الدين</th>
                                <th class="px-4 py-3 text-right">المسترد</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="row in salesReturns" :key="row.id">
                                <td dir="ltr" class="px-4 py-3 text-right font-black text-rose-700">{{ row.return_number }}</td>
                                <td class="px-4 py-3">{{ row.customer?.name || 'عميل نقدي' }}</td>
                                <td class="px-4 py-3">{{ date(row.return_date || row.created_at) }}</td>
                                <td class="px-4 py-3 text-xs">
                                    <div v-for="line in asArray(row.items)" :key="line.id">
                                        {{ line.product?.name || 'منتج' }} × {{ line.quantity }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-black">{{ money(row.total_amount) }}</td>
                                <td class="px-4 py-3">{{ money(row.amount_used_for_debt) }}</td>
                                <td class="px-4 py-3">{{ money(row.amount_refunded) }}</td>
                            </tr>

                            <tr v-if="!salesReturns.length">
                                <td colspan="7" class="py-10 text-center text-slate-500">لا توجد مرتجعات مبيعات.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">مرتجعات المشتريات</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1000px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">رقم المرتجع</th>
                                <th class="px-4 py-3 text-right">المورد</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">الأصناف</th>
                                <th class="px-4 py-3 text-right">القيمة</th>
                                <th class="px-4 py-3 text-right">خفض المستحق</th>
                                <th class="px-4 py-3 text-right">المسترد</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="row in purchaseReturns" :key="row.id">
                                <td dir="ltr" class="px-4 py-3 text-right font-black text-orange-700">{{ row.return_number }}</td>
                                <td class="px-4 py-3">{{ row.supplier?.name || '—' }}</td>
                                <td class="px-4 py-3">{{ date(row.return_date || row.created_at) }}</td>
                                <td class="px-4 py-3 text-xs">
                                    <div v-for="line in asArray(row.items)" :key="line.id">
                                        {{ line.product?.name || 'منتج' }} × {{ line.quantity }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-black">{{ money(row.total_amount) }}</td>
                                <td class="px-4 py-3">{{ money(row.amount_used_for_debt) }}</td>
                                <td class="px-4 py-3">{{ money(row.amount_refunded) }}</td>
                            </tr>

                            <tr v-if="!purchaseReturns.length">
                                <td colspan="7" class="py-10 text-center text-slate-500">لا توجد مرتجعات مشتريات.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">عمليات الاستبدال</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[850px] w-full text-sm">
                        <thead class="bg-slate-100 dark:bg-slate-700">
                            <tr>
                                <th class="px-4 py-3 text-right">العملية</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">قيمة المرتجع</th>
                                <th class="px-4 py-3 text-right">قيمة البدائل</th>
                                <th class="px-4 py-3 text-right">فرق السعر</th>
                                <th class="px-4 py-3 text-right">التسوية</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="row in exchanges" :key="row.id" class="border-t border-slate-100 dark:border-slate-700">
                                <td dir="ltr" class="px-4 py-3 text-right font-black">{{ row.exchange_number || `#${row.id}` }}</td>
                                <td class="px-4 py-3">{{ date(row.exchanged_at || row.created_at) }}</td>
                                <td class="px-4 py-3">{{ money(row.return_value) }}</td>
                                <td class="px-4 py-3">{{ money(row.new_items_value) }}</td>
                                <td class="px-4 py-3 font-black">{{ money(row.price_difference) }}</td>
                                <td class="px-4 py-3">{{ settlementLabel(row.settlement_type) }}</td>
                            </tr>

                            <tr v-if="!exchanges.length">
                                <td colspan="6" class="py-10 text-center text-slate-500">لا توجد عمليات استبدال.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <h2 class="font-black dark:text-white">المنتجات الأكثر إرجاعاً</h2>
                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <article v-for="product in topProducts" :key="product.id" class="rounded-2xl bg-slate-50 p-4 text-sm dark:bg-slate-900">
                        <strong class="block">{{ product.name }}</strong>
                        <span dir="ltr" class="block text-right text-xs text-slate-400">{{ product.code }}</span>
                        <div class="mt-3 flex justify-between text-xs">
                            <span>{{ product.total_quantity }} قطعة</span>
                            <strong>{{ money(product.total_amount) }}</strong>
                        </div>
                    </article>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
