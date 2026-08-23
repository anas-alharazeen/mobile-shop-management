<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({}),
    },
});

const money = (value) => `${Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})} شيكل`;

const num = (value) => Number(value || 0).toLocaleString('en-US');

const summaryCards = computed(() => [
    {
        title: 'إجمالي المبيعات',
        value: money(props.summary?.total_sales),
        hint: 'قيمة فواتير المبيعات المعتمدة',
        icon: 'sales',
        color: 'emerald',
    },
    {
        title: 'إجمالي المشتريات',
        value: money(props.summary?.total_purchases),
        hint: 'قيمة فواتير الشراء المعتمدة',
        icon: 'purchases',
        color: 'blue',
    },
    {
        title: 'قيمة المخزون',
        value: money(props.summary?.inventory_value),
        hint: 'إجمالي قيمة المنتجات الحالية',
        icon: 'inventory',
        color: 'violet',
    },
    {
        title: 'مستحقات العملاء',
        value: money(props.summary?.customer_debts),
        hint: 'المبالغ المتبقية على العملاء',
        icon: 'customers',
        color: 'amber',
    },
    {
        title: 'مستحقات الموردين',
        value: money(props.summary?.supplier_debts),
        hint: 'المبالغ المتبقية للموردين',
        icon: 'suppliers',
        color: 'rose',
    },
    {
        title: 'الصيانة النشطة',
        value: num(props.summary?.active_repairs),
        hint: 'طلبات قيد التنفيذ أو بانتظار التسليم',
        icon: 'repairs',
        color: 'cyan',
    },
]);

const reportCards = computed(() => [
    {
        key: 'sales',
        title: 'المبيعات والأرباح',
        description: 'عرض فواتير المبيعات، تفاصيل المنتجات المباعة، الأرباح، الدفعات، والحسابات المالية المستلمة.',
        route: route('reports.sales'),
        badge: 'الأكثر استخداماً',
        accent: 'from-emerald-500 to-teal-500',
        soft: 'bg-emerald-50 dark:bg-emerald-950/25',
        border: 'border-emerald-200 dark:border-emerald-900/40',
        stats: [
            { label: 'المبيعات', value: money(props.summary?.total_sales) },
            { label: 'العملاء', value: num(props.summary?.sales_customers_count) },
        ],
        icon: 'sales',
    },
    {
        key: 'purchases',
        title: 'المشتريات',
        description: 'عرض فواتير الشراء، الموردين، الأصناف المشتراة، قيم الفواتير، والدفعات المسجلة على المشتريات.',
        route: route('reports.purchases'),
        badge: 'إدارة التوريد',
        accent: 'from-blue-500 to-indigo-500',
        soft: 'bg-blue-50 dark:bg-blue-950/25',
        border: 'border-blue-200 dark:border-blue-900/40',
        stats: [
            { label: 'المشتريات', value: money(props.summary?.total_purchases) },
            { label: 'الموردون', value: num(props.summary?.suppliers_count) },
        ],
        icon: 'purchases',
    },
    {
        key: 'inventory',
        title: 'المخزون',
        description: 'عرض أرصدة المنتجات، قيمة المخزون، الحركات المخزنية، المنتجات منخفضة الكمية، والمنتجات النافدة.',
        route: route('reports.inventory'),
        badge: 'تحكم كامل',
        accent: 'from-violet-500 to-fuchsia-500',
        soft: 'bg-violet-50 dark:bg-violet-950/25',
        border: 'border-violet-200 dark:border-violet-900/40',
        stats: [
            { label: 'القيمة', value: money(props.summary?.inventory_value) },
            { label: 'المنتجات', value: num(props.summary?.products_count) },
        ],
        icon: 'inventory',
    },
    {
        key: 'repairs',
        title: 'الصيانة',
        description: 'عرض طلبات الصيانة، حالة كل جهاز، إجمالي إيرادات الصيانة، المتبقي على العملاء، والأجهزة الجاهزة.',
        route: route('reports.repairs'),
        badge: 'متابعة الأجهزة',
        accent: 'from-cyan-500 to-sky-500',
        soft: 'bg-cyan-50 dark:bg-cyan-950/25',
        border: 'border-cyan-200 dark:border-cyan-900/40',
        stats: [
            { label: 'النشطة', value: num(props.summary?.active_repairs) },
            { label: 'الجاهزة', value: num(props.summary?.ready_repairs) },
        ],
        icon: 'repairs',
    },
    {
        key: 'finance',
        title: 'التقرير المالي',
        description: 'عرض الحسابات المالية، الحركات الواردة والصادرة، التدفقات النقدية، والإغلاقات اليومية.',
        route: route('reports.finance'),
        badge: 'الحسابات والدفعات',
        accent: 'from-amber-500 to-orange-500',
        soft: 'bg-amber-50 dark:bg-amber-950/25',
        border: 'border-amber-200 dark:border-amber-900/40',
        stats: [
            { label: 'الحسابات', value: num(props.summary?.financial_accounts_count) },
            { label: 'الرصيد النقدي', value: money(props.summary?.cash_balance) },
        ],
        icon: 'finance',
    },
    {
        key: 'returns',
        title: 'المرتجعات والاستبدال',
        description: 'عرض مرتجعات المبيعات والمشتريات وعمليات الاستبدال وتأثيرها على الرصيد والديون والمخزون.',
        route: route('reports.returns'),
        badge: 'مهم للمراجعة',
        accent: 'from-rose-500 to-pink-500',
        soft: 'bg-rose-50 dark:bg-rose-950/25',
        border: 'border-rose-200 dark:border-rose-900/40',
        stats: [
            { label: 'مرتجعات البيع', value: money(props.summary?.sales_returns_total) },
            { label: 'مرتجعات الشراء', value: money(props.summary?.purchase_returns_total) },
        ],
        icon: 'returns',
    },
]);

const iconBoxClass = (color) => ({
    emerald: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
    blue: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    violet: 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300',
    amber: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    rose: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300',
    cyan: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300',
}[color] || 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300');

const iconSvg = (name) => {
    const icons = {
        sales: 'M3 17l6-6 4 4 8-8M21 10V3h-7',
        purchases: 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 7h12M10 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z',
        inventory: 'M20 7L12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        customers: 'M17 20a5 5 0 00-10 0M12 10a4 4 0 100-8 4 4 0 000 8zm8 10a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75',
        suppliers: 'M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01',
        repairs: 'M16.24 7.76a6 6 0 01-8.48 8.48L3 21l4.76-4.76a6 6 0 018.48-8.48z',
        finance: 'M12 8c-2.21 0-4 .895-4 2s1.79 2 4 2 4 .895 4 2-1.79 2-4 2m0-10v2m0 12v2',
        returns: 'M9 14l-4-4 4-4M5 10h9a5 5 0 010 10h-1M15 10l4 4-4 4M19 14H10a5 5 0 010-10h1',
    };

    return icons[name] || icons.sales;
};
</script>

<template>
    <Head title="التقارير" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-black tracking-wide text-indigo-600 dark:text-indigo-400">
                        مركز التحليلات والتقارير
                    </p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                        التقارير
                    </h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        واجهة موحدة لمتابعة أداء المعرض، مراجعة المبيعات والمشتريات والمخزون والصيانة والمالية والمرتجعات
                        بشكل واضح ومرتب وسريع.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('dashboard')"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        العودة للوحة التحكم
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Hero -->
            <section class="relative overflow-hidden rounded-[28px] bg-gradient-to-l from-slate-950 via-indigo-950 to-indigo-800 p-6 text-white shadow-xl sm:p-8">
                <div class="absolute -left-10 -top-10 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl"></div>
                <div class="absolute -right-8 bottom-0 h-32 w-32 rounded-full bg-fuchsia-400/10 blur-3xl"></div>

                <div class="relative grid gap-6 xl:grid-cols-[1.2fr_0.8fr] xl:items-center">
                    <div>
                        <p class="inline-flex rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs font-bold text-indigo-100">
                            Fanana Phone Reports Hub
                        </p>

                        <h2 class="mt-4 text-2xl font-black sm:text-4xl">
                            كل تقارير المعرض في مكان واحد
                        </h2>

                        <p class="mt-3 max-w-2xl text-sm leading-7 text-indigo-100 sm:text-base">
                            راقب الأداء المالي والتشغيلي بسرعة، وادخل مباشرة إلى التقرير الذي تحتاجه،
                            مع أرقام واضحة وتصميم مرتب ومريح للعين.
                        </p>

                        <div class="mt-5 flex flex-wrap gap-3">
                            <Link
                                :href="route('reports.sales')"
                                class="rounded-xl bg-white px-4 py-2.5 text-sm font-black text-indigo-700 transition hover:bg-indigo-50"
                            >
                                فتح تقرير المبيعات
                            </Link>

                            <Link
                                :href="route('reports.finance')"
                                class="rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-black text-white transition hover:bg-white/15"
                            >
                                فتح التقرير المالي
                            </Link>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <p class="text-xs font-bold text-indigo-100">مبيعات اليوم</p>
                            <p class="mt-2 text-2xl font-black">{{ money(summary?.today_sales) }}</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <p class="text-xs font-bold text-indigo-100">الربح اليوم</p>
                            <p class="mt-2 text-2xl font-black">{{ money(summary?.today_profit) }}</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur">
                            <p class="text-xs font-bold text-indigo-100">الفواتير اليوم</p>
                            <p class="mt-2 text-2xl font-black">{{ num(summary?.today_invoices_count) }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Main summary -->
            <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
                <article
                    v-for="card in summaryCards"
                    :key="card.title"
                    class="group rounded-[24px] border border-slate-200 bg-white p-4 shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-lg dark:border-slate-700 dark:bg-slate-800"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-black text-slate-500 dark:text-slate-400">
                                {{ card.title }}
                            </p>
                            <p class="mt-3 text-xl font-black text-slate-950 dark:text-white">
                                {{ card.value }}
                            </p>
                        </div>

                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl"
                            :class="iconBoxClass(card.color)"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    :d="iconSvg(card.icon)"
                                />
                            </svg>
                        </div>
                    </div>

                    <p class="mt-4 text-xs leading-6 text-slate-500 dark:text-slate-400">
                        {{ card.hint }}
                    </p>
                </article>
            </section>

            <!-- Reports cards -->
            <section>
                <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-xl font-black text-slate-950 dark:text-white">
                            أقسام التقارير
                        </h2>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            اختر التقرير المطلوب وانتقل مباشرة إلى التفاصيل.
                        </p>
                    </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-2 2xl:grid-cols-3">
                    <article
                        v-for="report in reportCards"
                        :key="report.key"
                        class="group overflow-hidden rounded-[28px] border bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl dark:bg-slate-800"
                        :class="report.border"
                    >
                        <div class="p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-[11px] font-black text-slate-700 dark:text-slate-200"
                                        :class="report.soft"
                                    >
                                        {{ report.badge }}
                                    </span>

                                    <h3 class="mt-3 text-xl font-black text-slate-950 dark:text-white">
                                        {{ report.title }}
                                    </h3>

                                    <p class="mt-2 text-sm leading-7 text-slate-500 dark:text-slate-400">
                                        {{ report.description }}
                                    </p>
                                </div>

                                <div
                                    class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-l text-white shadow-lg"
                                    :class="report.accent"
                                >
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="1.8"
                                            :d="iconSvg(report.icon)"
                                        />
                                    </svg>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div
                                    v-for="item in report.stats"
                                    :key="item.label"
                                    class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-900/60"
                                >
                                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                        {{ item.label }}
                                    </p>
                                    <p class="mt-2 text-base font-black text-slate-950 dark:text-white">
                                        {{ item.value }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <Link
                                    :href="report.route"
                                    class="inline-flex items-center justify-center rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-black text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 dark:hover:bg-slate-200"
                                >
                                    عرض التقرير
                                </Link>

                                <Link
                                    :href="report.route"
                                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                >
                                    التفاصيل
                                </Link>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Bottom info -->
            <section class="grid gap-5 xl:grid-cols-[1fr_0.9fr]">
                <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <h3 class="text-lg font-black text-slate-950 dark:text-white">
                        ملخص سريع
                    </h3>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">عدد المنتجات</p>
                            <p class="mt-2 text-xl font-black text-slate-950 dark:text-white">
                                {{ num(summary?.products_count) }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">عدد العملاء</p>
                            <p class="mt-2 text-xl font-black text-slate-950 dark:text-white">
                                {{ num(summary?.customers_count) }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">عدد الموردين</p>
                            <p class="mt-2 text-xl font-black text-slate-950 dark:text-white">
                                {{ num(summary?.suppliers_count) }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400">الحسابات المالية</p>
                            <p class="mt-2 text-xl font-black text-slate-950 dark:text-white">
                                {{ num(summary?.financial_accounts_count) }}
                            </p>
                        </div>
                    </div>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <h3 class="text-lg font-black text-slate-950 dark:text-white">
                        ملاحظات
                    </h3>

                    <div class="mt-4 space-y-3">
                        <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                            <p class="text-sm font-black text-slate-800 dark:text-slate-100">
                                جميع الأرقام في هذه الصفحة تظهر بالأرقام الإنجليزية
                            </p>
                            <p class="mt-1 text-xs leading-6 text-slate-500 dark:text-slate-400">
                                مثل: 1,250.00 بدلاً من ١٬٢٥٠٫٠٠
                            </p>
                        </div>

                        <div class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700">
                            <p class="text-sm font-black text-slate-800 dark:text-slate-100">
                                يمكنك الدخول إلى أي تقرير لمزيد من التفاصيل والطباعة
                            </p>
                            <p class="mt-1 text-xs leading-6 text-slate-500 dark:text-slate-400">
                                كل بطاقة تنقلك إلى صفحة التقرير الفرعي الخاصة بها.
                            </p>
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
