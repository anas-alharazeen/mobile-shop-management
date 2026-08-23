<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    data: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
    categories: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
});

const asArray = (value) => Array.isArray(value)
    ? value
    : (value && typeof value === 'object' ? Object.values(value) : []);

const inventoryRows = computed(() => asArray(props.data?.inventory_data));
const movements = computed(() => asArray(props.data?.recent_movements));
const lowStock = computed(() => asArray(props.data?.low_stock_products));
const outStock = computed(() => asArray(props.data?.out_of_stock_products));
const warehouses = computed(() => asArray(props.data?.warehouses));
const warehouseBreakdown = computed(() => asArray(props.data?.warehouse_breakdown));
const selectedWarehouse = computed(() => props.data?.selected_warehouse || null);

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
    category_id: props.filters?.category_id || '',
    product_id: props.filters?.product_id || '',
    warehouse_id: props.filters?.warehouse_id || '',
});

const money = (value) => `${Number(value || 0).toLocaleString('ar-PS', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})} شيكل`;

const num = (value) => Number(value || 0).toLocaleString('ar-PS');

const dateTime = (value) => {
    if (!value) return '—';
    const parsed = new Date(value);
    return Number.isNaN(parsed.getTime())
        ? value
        : parsed.toLocaleString('ar-PS');
};

const enumValue = (value) => (
    typeof value === 'object' && value !== null
        ? (value.value ?? value.name ?? '')
        : value
);

const movementLabel = (value) => ({
    opening: 'رصيد افتتاحي',
    manual_addition: 'إضافة يدوية',
    manual_deduction: 'خصم يدوي',
    transfer_in: 'استلام من مخزن',
    transfer_out: 'تحويل إلى مخزن',
    damaged: 'تالف',
    lost: 'مفقود',
    sale: 'بيع',
    sale_cancellation: 'إلغاء بيع',
    purchase: 'شراء',
    purchase_cancellation: 'إلغاء شراء',
    sales_return: 'مرتجع مبيعات',
    purchase_return: 'مرتجع مشتريات',
    repair_part: 'استخدام صيانة',
    repair_part_reversal: 'إرجاع قطعة صيانة',
    inventory_surplus: 'زيادة جرد',
    inventory_shortage: 'عجز جرد',
}[enumValue(value)] || enumValue(value) || 'حركة');

const stockStatusLabel = (value) => ({
    available: 'متوفر',
    low: 'منخفض',
    out: 'نافد',
}[value] || value || '—');

const reportProductStock = (product) => Number(
    product?.report_stock_quantity
    ?? 0
);

const warehouseTypeLabel = (value) => ({
    sales: 'مبيعات',
    maintenance: 'صيانة',
}[enumValue(value)] || enumValue(value) || 'مخزن');

const setPeriod = (period) => {
    filters.period = period;
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const applyFilters = () => router.get(
    route('reports.inventory'),
    { ...filters },
    { preserveState: true, replace: true }
);

const clearFilters = () => {
    Object.assign(filters, {
        period: '',
        start_date: '',
        end_date: '',
        category_id: '',
        product_id: '',
        warehouse_id: '',
    });
    applyFilters();
};

const printReport = () => window.open(
    route('reports.print', {
        type: 'inventory',
        ...filters,
    }),
    '_blank'
);
</script>

<template>
    <Head title="تقرير المخزون" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black text-emerald-600">تقرير تفصيلي</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        المخزون والمستودعات
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        الأرصدة الحالية، قيمة المخزون، حالات النقص وحركات المخزون.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button @click="printReport" class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white">طباعة</button>
                    <a :href="route('reports.inventory.pdf', filters)" target="_blank" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-black text-white">PDF</a>
                    <a :href="route('reports.inventory.excel', filters)" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Excel</a>
                    <Link :href="route('reports.index')" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200">العودة</Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-wrap gap-2">
                    <button v-for="period in periods" :key="period[0]" @click="setPeriod(period[0])" class="rounded-xl px-3 py-2 text-xs font-black" :class="filters.period === period[0] ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">
                        {{ period[1] }}
                    </button>
                    <input v-model="filters.start_date" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters" />
                    <input v-model="filters.end_date" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters" />

                    <select v-model="filters.category_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters">
                        <option value="">جميع الفئات</option>
                        <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                    </select>

                    <select v-model="filters.product_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters">
                        <option value="">جميع المنتجات</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                    </select>

                    <select v-model="filters.warehouse_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters">
                        <option value="">جميع المستودعات</option>
                        <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">{{ warehouse.name }}</option>
                    </select>

                    <button @click="clearFilters" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-black dark:border-slate-600 dark:text-slate-300">مسح</button>
                </div>
            </section>

            <section
                class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs leading-6 text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-200"
            >
                <strong class="font-black">ملاحظة التقرير:</strong>
                قيمة وكميات المخزون المعروضة هي
                <strong>الرصيد الحالي الآن</strong>.
                الفترة الزمنية بالأعلى تؤثر على
                <strong>حركات المخزون وعمليات الجرد</strong>
                فقط، ولا تعيد بناء رصيد تاريخي للمخزون.

                <span
                    v-if="selectedWarehouse"
                    class="mr-1 font-black"
                >
                    النطاق الحالي: {{ selectedWarehouse.name }}.
                </span>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <article v-for="item in [
                    ['قيمة المخزون', money(data.summary?.total_inventory_value)],
                    ['مخزون المبيعات', money(data.summary?.sales_warehouse_value)],
                    ['مخزون الصيانة', money(data.summary?.maintenance_warehouse_value)],
                    ['إجمالي القطع', num(data.summary?.total_pieces)],
                    ['منخفض المخزون', num(data.summary?.low_stock_count)],
                    ['نافد', num(data.summary?.out_of_stock_count)],
                ]" :key="item[0]" class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[11px] text-slate-500">{{ item[0] }}</p>
                    <p class="mt-2 text-sm font-black dark:text-white">{{ item[1] }}</p>
                </article>
            </section>

            <section
                v-if="warehouseBreakdown.length"
                class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <div>
                    <h2 class="font-black dark:text-white">
                        توزيع المخزون حسب المستودع
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        القيم محسوبة بنفس فلاتر المنتج والفئة الحالية.
                    </p>
                </div>

                <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="warehouse in warehouseBreakdown"
                        :key="warehouse.id"
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/60"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <strong class="block text-sm dark:text-white">
                                    {{ warehouse.name }}
                                </strong>
                                <span class="mt-1 block text-[10px] text-slate-400">
                                    {{ warehouseTypeLabel(warehouse.type) }}
                                    ·
                                    {{ warehouse.is_active ? 'نشط' : 'غير نشط' }}
                                </span>
                            </div>

                            <span
                                class="rounded-full px-2 py-1 text-[10px] font-black"
                                :class="
                                    warehouse.is_active
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                                        : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
                                "
                            >
                                {{ num(warehouse.pieces) }} قطعة
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="rounded-xl bg-white p-3 dark:bg-slate-800">
                                <p class="text-[10px] text-slate-400">
                                    قيمة المخزون
                                </p>
                                <strong class="mt-1 block text-xs dark:text-white">
                                    {{ money(warehouse.value) }}
                                </strong>
                            </div>

                            <div class="rounded-xl bg-white p-3 dark:bg-slate-800">
                                <p class="text-[10px] text-slate-400">
                                    منتجات برصيد
                                </p>
                                <strong class="mt-1 block text-xs dark:text-white">
                                    {{ num(warehouse.products_with_stock) }}
                                </strong>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">كل المنتجات وأرصدة المخزون</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ inventoryRows.length }} منتج ضمن التقرير.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1000px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">المنتج</th>
                                <th class="px-4 py-3 text-right">الفئة</th>
                                <th class="px-4 py-3 text-right">سعر الشراء</th>
                                <th class="px-4 py-3 text-right">مخزون المبيعات</th>
                                <th class="px-4 py-3 text-right">مخزون الصيانة</th>
                                <th class="px-4 py-3 text-right">الإجمالي</th>
                                <th class="px-4 py-3 text-right">قيمة المخزون</th>
                                <th class="px-4 py-3 text-right">الحالة</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="row in inventoryRows" :key="row.product?.id">
                                <td class="px-4 py-3">
                                    <strong class="block dark:text-white">{{ row.product?.name }}</strong>
                                    <span dir="ltr" class="block text-right text-xs text-slate-400">{{ row.product?.code || '' }}</span>
                                </td>
                                <td class="px-4 py-3">{{ row.product?.category?.name || '—' }}</td>
                                <td class="px-4 py-3">{{ money(row.product?.purchase_price) }}</td>
                                <td class="px-4 py-3">{{ num(row.sales_qty) }}</td>
                                <td class="px-4 py-3">{{ num(row.maintenance_qty) }}</td>
                                <td class="px-4 py-3 font-black">{{ num(row.total_qty) }}</td>
                                <td class="px-4 py-3 font-black">{{ money(row.total_value) }}</td>
                                <td class="px-4 py-3">{{ stockStatusLabel(row.stock_status) }}</td>
                            </tr>

                            <tr v-if="!inventoryRows.length">
                                <td colspan="8" class="py-12 text-center text-slate-500">لا توجد منتجات.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">حركات المخزون</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        الحركات تحترم الفترة والفئة والمنتج والمستودع المحدد.
                        يظهر آخر {{ num(movements.length) }} حركة
                        من أصل {{ num(data.summary?.movement_count) }} ضمن الفلاتر.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1050px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">المنتج</th>
                                <th class="px-4 py-3 text-right">المخزن</th>
                                <th class="px-4 py-3 text-right">نوع الحركة</th>
                                <th class="px-4 py-3 text-right">الكمية</th>
                                <th class="px-4 py-3 text-right">قبل</th>
                                <th class="px-4 py-3 text-right">بعد</th>
                                <th class="px-4 py-3 text-right">الملاحظات</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="movement in movements" :key="movement.id">
                                <td class="px-4 py-3">{{ dateTime(movement.created_at) }}</td>
                                <td class="px-4 py-3 font-bold">{{ movement.product?.name || '—' }}</td>
                                <td class="px-4 py-3">{{ movement.warehouse?.name || '—' }}</td>
                                <td class="px-4 py-3">{{ movementLabel(movement.type) }}</td>
                                <td class="px-4 py-3 font-black">{{ num(movement.quantity) }}</td>
                                <td class="px-4 py-3">{{ num(movement.balance_before ?? movement.quantity_before) }}</td>
                                <td class="px-4 py-3">{{ num(movement.balance_after ?? movement.quantity_after) }}</td>
                                <td class="px-4 py-3 text-xs text-slate-500">{{ movement.notes || movement.description || '—' }}</td>
                            </tr>

                            <tr v-if="!movements.length">
                                <td colspan="8" class="py-12 text-center text-slate-500">لا توجد حركات.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h3 class="font-black dark:text-white">منتجات منخفضة المخزون</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="product in lowStock" :key="product.id" class="flex justify-between border-b border-slate-100 py-2 text-xs dark:border-slate-700">
                            <div>
                                <strong>{{ product.name }}</strong>
                                <span class="mr-2 text-slate-400">{{ product.category?.name }}</span>
                            </div>
                            <span>{{ num(reportProductStock(product)) }} / حد {{ num(product.low_stock_threshold || 5) }}</span>
                        </div>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h3 class="font-black dark:text-white">المنتجات النافدة</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="product in outStock" :key="product.id" class="flex justify-between border-b border-slate-100 py-2 text-xs dark:border-slate-700">
                            <div>
                                <strong>{{ product.name }}</strong>
                                <span class="mr-2 text-slate-400">
                                    الرصيد: {{ num(reportProductStock(product)) }}
                                </span>
                            </div>
                            <span dir="ltr">{{ product.code || '—' }}</span>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
