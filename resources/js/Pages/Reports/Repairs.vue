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

const orders = computed(() => asArray(props.data?.orders));
const topParts = computed(() => asArray(props.data?.top_parts));

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
    status: props.filters?.status || '',
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

const enumValue = (value) => (
    typeof value === 'object' && value !== null
        ? (value.value ?? value.name ?? '')
        : value
);

const statusLabel = (value) => ({
    received: 'مستلم',
    in_progress: 'قيد التنفيذ',
    ready: 'جاهز للاستلام',
    delivered: 'تم التسليم',
    cancelled: 'ملغي',
}[enumValue(value)] || enumValue(value) || '—');

const setPeriod = (period) => {
    filters.period = period;
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const applyFilters = () => router.get(
    route('reports.repairs'),
    { ...filters },
    { preserveState: true, replace: true }
);

const clearFilters = () => {
    Object.assign(filters, {
        period: '',
        start_date: '',
        end_date: '',
        status: '',
    });
    applyFilters();
};

const printReport = () => window.open(
    route('reports.print', {
        type: 'repairs',
        ...filters,
    }),
    '_blank'
);
</script>

<template>
    <Head title="تقرير الصيانة" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black text-violet-600">تقرير تفصيلي</p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        الصيانة
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        جميع طلبات الصيانة والحالات والأجهزة والتكاليف والأرباح.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button @click="printReport" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white">طباعة</button>
                    <a :href="route('reports.repairs.pdf', filters)" target="_blank" class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-black text-white">PDF</a>
                    <a :href="route('reports.repairs.excel', filters)" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">Excel</a>
                    <Link :href="route('reports.index')" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200">العودة</Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="rounded-3xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-wrap gap-2">
                    <button v-for="period in periods" :key="period[0]" @click="setPeriod(period[0])" class="rounded-xl px-3 py-2 text-xs font-black" :class="filters.period === period[0] ? 'bg-violet-600 text-white' : 'bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">
                        {{ period[1] }}
                    </button>
                    <input v-model="filters.start_date" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters" />
                    <input v-model="filters.end_date" type="date" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters" />
                    <select v-model="filters.status" class="rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white" @change="applyFilters">
                        <option value="">جميع الحالات</option>
                        <option value="received">مستلم</option>
                        <option value="in_progress">قيد التنفيذ</option>
                        <option value="ready">جاهز للاستلام</option>
                        <option value="delivered">تم التسليم</option>
                        <option value="cancelled">ملغي</option>
                    </select>
                    <button @click="clearFilters" class="rounded-xl border border-slate-300 px-4 py-2 text-xs font-black dark:border-slate-600 dark:text-slate-300">مسح</button>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-8">
                <article v-for="item in [
                    ['مستلم', data.summary?.received || 0],
                    ['قيد التنفيذ', data.summary?.in_progress || 0],
                    ['جاهز', data.summary?.ready || 0],
                    ['تم التسليم', data.summary?.delivered || 0],
                    ['متأخر', data.summary?.overdue || 0],
                    ['الإيرادات', money(data.summary?.total_revenue)],
                    ['تكلفة القطع', money(data.summary?.total_cost)],
                    ['الربح', money(data.summary?.total_profit)],
                ]" :key="item[0]" class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-[11px] text-slate-500">{{ item[0] }}</p>
                    <p class="mt-2 text-sm font-black dark:text-white">{{ item[1] }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">طلبات الصيانة بالتفصيل</h2>
                    <p class="mt-1 text-xs text-slate-500">{{ orders.length }} طلب ضمن التقرير.</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1250px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">رقم الطلب</th>
                                <th class="px-4 py-3 text-right">العميل</th>
                                <th class="px-4 py-3 text-right">الجهاز</th>
                                <th class="px-4 py-3 text-right">المشكلة</th>
                                <th class="px-4 py-3 text-right">الحالة</th>
                                <th class="px-4 py-3 text-right">الاستلام</th>
                                <th class="px-4 py-3 text-right">الموعد المتوقع</th>
                                <th class="px-4 py-3 text-right">الإجمالي</th>
                                <th class="px-4 py-3 text-right">المدفوع</th>
                                <th class="px-4 py-3 text-right">المتبقي</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr v-for="order in orders" :key="order.id">
                                <td dir="ltr" class="px-4 py-3 text-right font-black text-violet-700 dark:text-violet-300">
                                    {{ order.order_number }}
                                </td>
                                <td class="px-4 py-3">
                                    <strong class="block dark:text-white">{{ order.customer?.name || order.customer_name || '—' }}</strong>
                                    <span class="text-xs text-slate-400">{{ order.customer?.phone || order.customer_phone || '' }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ order.device_brand || order.brand || '' }}
                                    {{ order.device_model || order.model || order.device_type || '' }}
                                </td>
                                <td class="max-w-xs px-4 py-3 text-xs text-slate-600 dark:text-slate-300">
                                    {{ order.problem_description || order.fault_cause || '—' }}
                                </td>
                                <td class="px-4 py-3">{{ statusLabel(order.status) }}</td>
                                <td class="px-4 py-3">{{ date(order.received_at) }}</td>
                                <td class="px-4 py-3">{{ date(order.expected_delivery_date) }}</td>
                                <td class="px-4 py-3 font-black">{{ money(order.total_amount) }}</td>
                                <td class="px-4 py-3 text-emerald-600">{{ money(order.paid_amount) }}</td>
                                <td class="px-4 py-3 text-amber-600">{{ money(order.remaining_amount) }}</td>
                            </tr>

                            <tr v-if="!orders.length">
                                <td colspan="10" class="py-12 text-center text-slate-500">
                                    لا توجد طلبات صيانة ضمن الفلاتر المحددة.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-3">
                <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h3 class="font-black dark:text-white">أنواع الأجهزة الأكثر دخولاً</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="(count, type) in data.device_types || {}" :key="type" class="flex justify-between border-b border-slate-100 py-2 text-xs dark:border-slate-700">
                            <span>{{ type }}</span>
                            <strong>{{ count }}</strong>
                        </div>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h3 class="font-black dark:text-white">الأعطال الأكثر تكراراً</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="(count, fault) in data.faults || {}" :key="fault" class="flex justify-between border-b border-slate-100 py-2 text-xs dark:border-slate-700">
                            <span>{{ fault }}</span>
                            <strong>{{ count }}</strong>
                        </div>
                    </div>
                </article>

                <article class="rounded-3xl border border-slate-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                    <h3 class="font-black dark:text-white">قطع الغيار الأكثر استخداماً</h3>
                    <div class="mt-3 space-y-2">
                        <div v-for="part in topParts" :key="part.product_id" class="flex justify-between border-b border-slate-100 py-2 text-xs dark:border-slate-700">
                            <div>
                                <strong>{{ part.product?.name || '—' }}</strong>
                                <span dir="ltr" class="mr-2 text-slate-400">{{ part.product?.code || '' }}</span>
                            </div>
                            <strong>{{ part.total_quantity }}</strong>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
