<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePrint } from '@/composables/usePrint';

const props = defineProps({
    type: {
        type: String,
        required: true,
    },

    data: {
        type: Object,
        default: () => ({}),
    },

    filters: {
        type: Object,
        default: () => ({}),
    },

    title: {
        type: String,
        default: 'تقرير',
    },

    store: {
        type: Object,
        default: () => ({}),
    },

    generatedAt: {
        type: String,
        default: '',
    },

    reportCode: {
        type: String,
        default: '',
    },
});

const { printPage } = usePrint();

const enumValue = (value) => (
    typeof value === 'object' && value !== null
        ? (value.value ?? value.name ?? '')
        : value
);

const money = (value) => `${Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})} شيكل`;

const number = (value, digits = 0) => Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: digits,
    maximumFractionDigits: digits,
});

const percent = (value) => `${Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})}٪`;

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat('ar-PS', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(date);
};

const formatDateTime = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return String(value);
    }

    return new Intl.DateTimeFormat('ar-PS', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
};

const periodLabels = {
    today: 'اليوم',
    yesterday: 'أمس',
    last_7_days: 'آخر 7 أيام',
    this_week: 'هذا الأسبوع',
    this_month: 'هذا الشهر',
    last_month: 'الشهر السابق',
    this_year: 'هذه السنة',
    custom: 'فترة مخصصة',
};

const periodLabel = computed(() => {
    const start = props.filters?.start_date;
    const end = props.filters?.end_date;

    if (start || end) {
        return `من ${start || 'البداية'} إلى ${end || 'اليوم'}`;
    }

    return periodLabels[props.filters?.period] || 'جميع البيانات المتاحة';
});

const generatedLabel = computed(() => (
    props.generatedAt
        ? formatDateTime(props.generatedAt)
        : formatDateTime(new Date())
));

const reportMeta = computed(() => ({
    sales: {
        eyebrow: 'المبيعات والأرباح',
        description: 'تحليل تفصيلي للفواتير والمنتجات والمقبوضات والأرباح.',
        accent: 'indigo',
        backRoute: 'reports.sales',
    },
    purchases: {
        eyebrow: 'المشتريات والموردون',
        description: 'تحليل فواتير الشراء والتوريد والمدفوعات والمبالغ المستحقة.',
        accent: 'violet',
        backRoute: 'reports.purchases',
    },
    inventory: {
        eyebrow: 'المخزون والمستودعات',
        description: 'صورة شاملة عن الأرصدة والقيمة والحركات وحالات انخفاض المخزون.',
        accent: 'cyan',
        backRoute: 'reports.inventory',
    },
    repairs: {
        eyebrow: 'الصيانة وقطع الغيار',
        description: 'متابعة طلبات الصيانة والحالات والإيرادات والأرباح والأجزاء المستخدمة.',
        accent: 'amber',
        backRoute: 'reports.repairs',
    },
    finance: {
        eyebrow: 'الحسابات والتدفقات المالية',
        description: 'ملخص المقبوضات والمدفوعات والمصروفات والأرصدة والحركات المالية.',
        accent: 'emerald',
        backRoute: 'reports.finance',
    },
    returns: {
        eyebrow: 'المرتجعات والاستبدال',
        description: 'تفاصيل مرتجعات المبيعات والمشتريات والاستبدالات والتسويات.',
        accent: 'rose',
        backRoute: 'reports.returns',
    },
}[props.type] || {
    eyebrow: 'التقارير',
    description: 'تقرير تفصيلي من نظام فنانة فون.',
    accent: 'indigo',
    backRoute: 'reports.index',
}));

const summaryCards = computed(() => {
    const summary = props.data?.summary || {};

    const maps = {
        sales: [
            ['إجمالي المبيعات', money(summary.total_sales), 'قبل خصم المرتجعات'],
            ['صافي المبيعات', money(summary.net_sales), 'بعد المرتجعات'],
            ['إجمالي الربح', money(summary.total_profit), 'الربح الإجمالي'],
            ['هامش الربح', percent(summary.profit_margin), 'نسبة الربح'],
            ['المبالغ المحصلة', money(summary.total_paid), 'إجمالي الدفعات'],
            ['المبالغ المتبقية', money(summary.total_remaining), 'مستحقات العملاء'],
            ['مرتجعات المبيعات', money(summary.total_returns), 'مرتجعات معتمدة'],
            ['عدد الفواتير', number(summary.invoice_count), 'فاتورة معتمدة'],
        ],

        purchases: [
            ['إجمالي المشتريات', money(summary.total_purchases), 'قبل المرتجعات'],
            ['صافي المشتريات', money(summary.net_purchases), 'بعد المرتجعات'],
            ['المدفوع للموردين', money(summary.total_paid), 'الدفعات المسجلة'],
            ['المتبقي للموردين', money(summary.total_remaining), 'المستحق الحالي'],
            ['تكاليف الشحن', money(summary.total_shipping), 'شحن التوريدات'],
            ['مصاريف إضافية', money(summary.total_expenses), 'مصاريف الفواتير'],
            ['مرتجعات المشتريات', money(summary.total_returns), 'مرتجعات معتمدة'],
            ['عدد الفواتير', number(summary.invoice_count), 'فاتورة معتمدة'],
        ],

        inventory: [
            ['قيمة المخزون', money(summary.total_inventory_value), 'بسعر الشراء'],
            ['مخزون المبيعات', money(summary.sales_warehouse_value), 'قيمة مستودع المبيعات'],
            ['مخزون الصيانة', money(summary.maintenance_warehouse_value), 'قيمة مستودع الصيانة'],
            ['إجمالي القطع', number(summary.total_pieces), 'كل المستودعات'],
            ['منخفض المخزون', number(summary.low_stock_count), 'يحتاج متابعة'],
            ['نافد المخزون', number(summary.out_of_stock_count), 'غير متوفر حالياً'],
        ],

        repairs: [
            ['مستلم', number(summary.received), 'طلبات جديدة'],
            ['قيد التنفيذ', number(summary.in_progress), 'داخل الصيانة'],
            ['جاهز للاستلام', number(summary.ready), 'بانتظار العميل'],
            ['تم التسليم', number(summary.delivered), 'طلبات مكتملة'],
            ['متأخر', number(summary.overdue), 'تجاوز الموعد المتوقع'],
            ['إيرادات الصيانة', money(summary.total_revenue), 'طلبات مسلمة'],
            ['تكلفة القطع', money(summary.total_cost), 'قطع الغيار المستخدمة'],
            ['ربح الصيانة', money(summary.total_profit), 'الربح المحقق'],
        ],

        finance: [
            ['إجمالي المقبوضات', money(summary.total_collected), 'مبيعات + صيانة'],
            ['مقبوضات المبيعات', money(summary.collected_sales), 'دفعات المبيعات'],
            ['مقبوضات الصيانة', money(summary.collected_repairs), 'دفعات الصيانة'],
            ['المدفوع للموردين', money(summary.paid_to_suppliers), 'دفعات المشتريات'],
            ['المصروفات', money(summary.expenses), 'مصروفات معتمدة'],
            ['صافي التدفق', money(summary.net_cash_flow), 'لا يشمل التحويلات الداخلية'],
            ['حجم التحويلات', money(summary.transfer_volume), 'نقل داخلي بين الحسابات'],
            ['عدد التحويلات', number(summary.transfer_count), 'تحويل مكتمل'],
            ['مستحقات العملاء', money(summary.customer_debts), 'ديون العملاء والصيانة'],
            ['مستحقات الموردين', money(summary.supplier_debts), 'ديون الموردين'],
        ],

        returns: [
            ['مرتجعات المبيعات', money(summary.total_sales_returns), 'قيمة المرتجعات'],
            ['المسترد للعملاء', money(summary.refunded_to_customers), 'استرداد نقدي'],
            ['خفض ديون العملاء', money(summary.debt_reduced), 'تسوية على الدين'],
            ['مرتجعات المشتريات', money(summary.total_purchase_returns), 'قيمة المرتجعات'],
            ['المسترد من الموردين', money(summary.refunded_from_suppliers), 'استردادات موردين'],
            ['خفض مستحق المورد', money(summary.supplier_debt_reduced), 'تسوية على الدين'],
            ['عمليات الاستبدال', number(summary.exchange_count), 'عمليات معتمدة'],
            ['فروقات الأسعار', money(summary.total_price_difference), 'صافي الفرق'],
        ],
    };

    return maps[props.type] || [];
});

const paymentMethodLabel = (value) => ({
    cash: 'كاش',
    bank_transfer: 'تحويل بنكي',
    banking_app: 'تطبيق بنكي',
    exchange_credit: 'رصيد استبدال',
}[enumValue(value)] || enumValue(value) || 'غير محدد');

const paymentStatusLabel = (value) => ({
    paid: 'مدفوعة بالكامل',
    partially_paid: 'مدفوعة جزئياً',
    unpaid: 'غير مدفوعة',
}[enumValue(value)] || enumValue(value) || 'غير محدد');

const salesStatusLabel = (value) => ({
    draft: 'مسودة',
    approved: 'معتمدة',
    cancelled: 'ملغاة',
}[enumValue(value)] || enumValue(value) || 'غير محدد');

const repairStatusLabel = (value) => ({
    received: 'مستلم',
    in_progress: 'قيد التنفيذ',
    ready: 'جاهز للاستلام',
    delivered: 'تم التسليم',
    cancelled: 'ملغي',
}[enumValue(value)] || enumValue(value) || 'غير محدد');

const directionLabel = (value) => ({
    inflow: 'وارد',
    outflow: 'صادر',
}[enumValue(value)] || enumValue(value) || 'غير محدد');

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
}[enumValue(value)] || enumValue(value) || 'غير محدد');

const transferStatusLabel = (value) => ({
    posted: 'مكتمل',
    cancelled: 'ملغي',
}[enumValue(value)] || enumValue(value) || 'غير محدد');

const stockStatusLabel = (value) => ({
    available: 'متوفر',
    low: 'منخفض',
    out: 'نافد',
}[enumValue(value)] || enumValue(value) || 'غير محدد');

const movementTypeLabel = (value) => ({
    purchase: 'شراء',
    sale: 'بيع',
    transfer_in: 'تحويل وارد',
    transfer_out: 'تحويل صادر',
    adjustment_in: 'تسوية زيادة',
    adjustment_out: 'تسوية نقص',
    repair_use: 'استخدام صيانة',
    repair_return: 'إرجاع صيانة',
    sales_return: 'مرتجع مبيعات',
    purchase_return: 'مرتجع مشتريات',
    sales_return_reversal: 'عكس مرتجع مبيعات',
    purchase_return_reversal: 'عكس مرتجع مشتريات',
}[enumValue(value)] || enumValue(value) || 'حركة مخزون');

const returnConditionLabel = (value) => ({
    resellable: 'صالح للبيع',
    damaged: 'تالف',
    needs_inspection: 'يحتاج فحصاً',
}[enumValue(value)] || enumValue(value) || 'غير محدد');

const settlementLabel = (value) => ({
    customer_pays: 'العميل يدفع الفرق',
    customer_gets_refund: 'رد فرق للعميل',
    no_difference: 'بدون فرق',
}[enumValue(value)] || enumValue(value) || 'غير محدد');

/**
 * Laravel / Inertia normally serializes collections as arrays,
 * but cached or keyed collections can occasionally arrive as objects.
 * Normalising them here prevents a runtime .reduce/.length error
 * from blanking the entire print page.
 */
const asArray = (value) => {
    if (Array.isArray(value)) {
        return value;
    }

    if (value && typeof value === 'object') {
        return Object.values(value);
    }

    return [];
};

const salesInvoices = computed(() => (
    asArray(props.data?.invoices)
));

const salesTopProducts = computed(() => (
    asArray(props.data?.top_products)
));

const salesByCategoryRows = computed(() => (
    asArray(props.data?.sales_by_category)
));

const salesPaymentRows = computed(() => (
    asArray(props.data?.payments_by_method)
));

const agingRows = computed(() => {
    const aging = props.data?.aging || {};

    return [
        ['غير مستحقة', aging.not_due || 0],
        ['1–7 أيام', aging['1_7_days'] || 0],
        ['8–30 يوماً', aging['8_30_days'] || 0],
        ['31–60 يوماً', aging['31_60_days'] || 0],
        ['أكثر من 60 يوماً', aging.over_60_days || 0],
    ];
});

const totalSalesPieces = computed(() => (
    salesInvoices.value.reduce(
        (sum, invoice) => sum + asArray(invoice?.items).reduce(
            (itemSum, item) =>
                itemSum + Number(item?.quantity || 0),
            0
        ),
        0
    )
));

const totalPurchasePieces = computed(() => (
    (props.data?.invoices || []).reduce(
        (sum, invoice) => sum + (invoice.items || []).reduce(
            (itemSum, item) => itemSum + Number(item.quantity || 0),
            0
        ),
        0
    )
));

const backHref = computed(() => {
    try {
        return route(reportMeta.value.backRoute, props.filters || {});
    } catch {
        return route('reports.index');
    }
});
</script>

<template>
    <Head :title="title" />

    <main
        dir="rtl"
        class="report-print-page min-h-screen bg-slate-100 p-4 text-slate-900 sm:p-7 print:min-h-0 print:bg-white print:p-0"
    >
        <article
            class="mx-auto max-w-[1480px] overflow-hidden rounded-[30px] bg-white shadow-2xl print:max-w-none print:rounded-none print:shadow-none"
        >
            <!-- Header -->
            <header class="report-hero relative overflow-hidden px-6 py-7 text-white sm:px-8">
                <div class="absolute -left-20 -top-28 h-72 w-72 rounded-full bg-cyan-300/10 blur-3xl print:hidden"></div>
                <div class="absolute -bottom-28 right-1/3 h-64 w-64 rounded-full bg-indigo-400/10 blur-3xl print:hidden"></div>

                <div class="relative flex flex-col gap-7 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex min-w-0 items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/15 bg-white/10 p-2.5 shadow-xl">
                            <img
                                v-if="store.store_logo"
                                :src="`/storage/${store.store_logo}`"
                                alt="شعار المعرض"
                                class="h-full w-full object-contain"
                            />

                            <svg
                                v-else
                                class="h-9 w-9"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.7"
                                    d="M4 19.5V8.25L12 3l8 5.25V19.5M7.5 21h9M9 11.25h6M9 15h6"
                                />
                            </svg>
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-black tracking-wide text-indigo-200">
                                {{ store.store_name || 'فنانة فون' }}
                            </p>

                            <h1 class="mt-1 text-2xl font-black leading-tight sm:text-3xl">
                                {{ title }}
                            </h1>

                            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                                {{ reportMeta.description }}
                            </p>
                        </div>
                    </div>

                    <div class="grid shrink-0 grid-cols-2 gap-2 text-xs sm:min-w-[360px]">
                        <div class="rounded-2xl border border-white/10 bg-white/10 p-3">
                            <p class="text-slate-300">الفترة</p>
                            <p class="mt-1 font-black text-white">{{ periodLabel }}</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/10 p-3">
                            <p class="text-slate-300">تاريخ الإصدار</p>
                            <p class="mt-1 font-black text-white">{{ generatedLabel }}</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/10 p-3">
                            <p class="text-slate-300">القسم</p>
                            <p class="mt-1 font-black text-white">{{ reportMeta.eyebrow }}</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/10 p-3">
                            <p class="text-slate-300">رمز التقرير</p>
                            <p dir="ltr" class="mt-1 text-right font-black text-white">
                                {{ reportCode || 'RPT' }}
                            </p>
                        </div>
                    </div>
                </div>
            </header>

            <div class="space-y-7 p-5 sm:p-7">
                <!-- Executive summary -->
                <section>
                    <div class="mb-4 flex items-end justify-between gap-4">
                        <div>
                            <p class="text-xs font-black text-indigo-600">01</p>
                            <h2 class="mt-1 text-xl font-black text-slate-900">الملخص التنفيذي</h2>
                            <p class="mt-1 text-sm text-slate-500">أهم المؤشرات ضمن الفترة والفلاتر المحددة.</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div
                            v-for="(card, index) in summaryCards"
                            :key="`${card[0]}-${index}`"
                            class="summary-card rounded-2xl border border-slate-200 bg-white p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold text-slate-500">{{ card[0] }}</p>
                                    <p class="mt-2 text-lg font-black text-slate-950">{{ card[1] }}</p>
                                    <p class="mt-1 text-[11px] text-slate-400">{{ card[2] }}</p>
                                </div>

                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-[11px] font-black text-slate-500">
                                    {{ String(index + 1).padStart(2, '0') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- SALES -->
                <template v-if="type === 'sales'">
                    <div
                        v-if="!data || typeof data !== 'object'"
                        class="empty-state"
                    >
                        تعذر تحميل بيانات تقرير المبيعات. أعد تحميل الصفحة أو امسح كاش التقارير.
                    </div>

                    <section
                        v-else
                        class="report-section"
                    >
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">02</span>
                                <h2>تفاصيل فواتير المبيعات</h2>
                                <p>الفواتير والعملاء والمنتجات والأسعار والدفعات المسجلة.</p>
                            </div>

                            <div class="section-chip">
                                {{ salesInvoices.length }} فاتورة · {{ totalSalesPieces }} قطعة
                            </div>
                        </div>

                        <div
                            v-if="salesInvoices.length"
                            class="space-y-4"
                        >
                            <article
                                v-for="invoice in salesInvoices"
                                :key="invoice.id"
                                class="detail-card"
                            >
                                <div class="detail-card-head">
                                    <div>
                                        <p dir="ltr" class="reference-number">{{ invoice.invoice_number }}</p>
                                        <h3>{{ invoice.customer?.name || invoice.customer_name || 'عميل نقدي' }}</h3>
                                        <p>{{ invoice.customer_phone || invoice.customer?.phone || 'بدون رقم هاتف' }}</p>
                                    </div>

                                    <div class="detail-head-meta">
                                        <span>{{ formatDate(invoice.sale_date || invoice.created_at) }}</span>
                                        <span>{{ paymentStatusLabel(invoice.payment_status) }}</span>
                                        <span>{{ salesStatusLabel(invoice.status) }}</span>
                                    </div>
                                </div>

                                <div class="mini-stats">
                                    <div><span>الإجمالي</span><strong>{{ money(invoice.total_amount) }}</strong></div>
                                    <div><span>المدفوع</span><strong class="text-emerald-700">{{ money(invoice.paid_amount) }}</strong></div>
                                    <div><span>المتبقي</span><strong class="text-amber-700">{{ money(invoice.remaining_amount) }}</strong></div>
                                    <div><span>الربح</span><strong class="text-indigo-700">{{ money(invoice.gross_profit) }}</strong></div>
                                </div>

                                <div class="table-shell">
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th>المنتج</th>
                                                <th>الكمية</th>
                                                <th>سعر الوحدة</th>
                                                <th>الخصم</th>
                                                <th>الإجمالي</th>
                                                <th>الربح</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr
                                                v-for="item in asArray(invoice.items)"
                                                :key="item.id"
                                            >
                                                <td>
                                                    <strong>{{ item.product_name || item.product?.name || 'منتج' }}</strong>
                                                    <small dir="ltr">{{ item.product_code || item.product?.code || '—' }}</small>
                                                </td>
                                                <td>{{ number(item.quantity) }}</td>
                                                <td>{{ money(item.unit_selling_price) }}</td>
                                                <td>{{ money(Number(item.line_discount || 0) + Number(item.allocated_invoice_discount || 0)) }}</td>
                                                <td><strong>{{ money(item.line_total) }}</strong></td>
                                                <td>{{ money(item.line_profit) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div
                                    v-if="asArray(invoice.payments).length"
                                    class="payment-strip"
                                >
                                    <span class="payment-strip-title">الدفعات:</span>
                                    <span
                                        v-for="payment in asArray(invoice.payments)"
                                        :key="payment.id"
                                        class="payment-pill"
                                    >
                                        {{ paymentMethodLabel(payment.payment_method) }}
                                        · {{ money(payment.amount) }}
                                        <template v-if="payment.financial_account?.name || payment.financialAccount?.name">
                                            · {{ payment.financial_account?.name || payment.financialAccount?.name }}
                                        </template>
                                    </span>
                                </div>
                            </article>
                        </div>

                        <div v-else class="empty-state">لا توجد فواتير مبيعات ضمن الفلاتر المحددة.</div>
                    </section>

                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">03</span>
                                <h2>التحليل التجاري</h2>
                                <p>المنتجات والفئات وطرق الدفع الأعلى خلال الفترة.</p>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-3">
                            <div class="analysis-card">
                                <h3>المنتجات الأكثر مبيعاً</h3>
                                <table class="compact-table">
                                    <thead><tr><th>المنتج</th><th>الكمية</th><th>الإيراد</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in salesTopProducts" :key="row.id || row.code">
                                            <td>{{ row.name }}</td>
                                            <td>{{ number(row.total_quantity) }}</td>
                                            <td>{{ money(row.total_revenue) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analysis-card">
                                <h3>المبيعات حسب الفئة</h3>
                                <table class="compact-table">
                                    <thead><tr><th>الفئة</th><th>الإجمالي</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in salesByCategoryRows" :key="row.name">
                                            <td>{{ row.name }}</td>
                                            <td>{{ money(row.total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analysis-card">
                                <h3>المقبوضات حسب طريقة الدفع</h3>
                                <table class="compact-table">
                                    <thead><tr><th>الطريقة</th><th>الإجمالي</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in salesPaymentRows" :key="enumValue(row.payment_method)">
                                            <td>{{ paymentMethodLabel(row.payment_method) }}</td>
                                            <td>{{ money(row.total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </template>

                <!-- PURCHASES -->
                <template v-else-if="type === 'purchases'">
                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">02</span>
                                <h2>تفاصيل فواتير المشتريات</h2>
                                <p>الموردون والمنتجات والكميات والتكاليف والمدفوعات.</p>
                            </div>

                            <div class="section-chip">
                                {{ data.invoices?.length || 0 }} فاتورة · {{ totalPurchasePieces }} قطعة
                            </div>
                        </div>

                        <div
                            v-if="data.invoices?.length"
                            class="space-y-4"
                        >
                            <article
                                v-for="invoice in data.invoices"
                                :key="invoice.id"
                                class="detail-card"
                            >
                                <div class="detail-card-head">
                                    <div>
                                        <p dir="ltr" class="reference-number">{{ invoice.invoice_number }}</p>
                                        <h3>{{ invoice.supplier?.name || invoice.supplier?.company_name || 'مورد غير محدد' }}</h3>
                                        <p dir="ltr">فاتورة المورد: {{ invoice.supplier_invoice_number || '—' }}</p>
                                    </div>

                                    <div class="detail-head-meta">
                                        <span>{{ formatDate(invoice.purchase_date || invoice.created_at) }}</span>
                                        <span>{{ paymentStatusLabel(invoice.payment_status) }}</span>
                                        <span>{{ salesStatusLabel(invoice.status) }}</span>
                                    </div>
                                </div>

                                <div class="mini-stats">
                                    <div><span>الإجمالي</span><strong>{{ money(invoice.total_amount) }}</strong></div>
                                    <div><span>المدفوع</span><strong class="text-emerald-700">{{ money(invoice.paid_amount) }}</strong></div>
                                    <div><span>المتبقي</span><strong class="text-amber-700">{{ money(invoice.remaining_amount) }}</strong></div>
                                    <div><span>الشحن والمصاريف</span><strong>{{ money(Number(invoice.shipping_cost || 0) + Number(invoice.additional_expenses || 0)) }}</strong></div>
                                </div>

                                <div class="table-shell">
                                    <table class="report-table">
                                        <thead>
                                            <tr>
                                                <th>المنتج</th>
                                                <th>المخزن</th>
                                                <th>الكمية</th>
                                                <th>تكلفة الوحدة</th>
                                                <th>الخصم</th>
                                                <th>الإجمالي</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <tr
                                                v-for="item in invoice.items || []"
                                                :key="item.id"
                                            >
                                                <td>
                                                    <strong>{{ item.product?.name || item.product_name || 'منتج' }}</strong>
                                                    <small dir="ltr">{{ item.product?.code || item.product_code || '—' }}</small>
                                                </td>
                                                <td>{{ item.warehouse?.name || '—' }}</td>
                                                <td>{{ number(item.quantity) }}</td>
                                                <td>{{ money(item.unit_purchase_price) }}</td>
                                                <td>{{ money(item.line_discount) }}</td>
                                                <td><strong>{{ money(item.line_total) }}</strong></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div
                                    v-if="invoice.payments?.length"
                                    class="payment-strip"
                                >
                                    <span class="payment-strip-title">الدفعات:</span>
                                    <span
                                        v-for="payment in invoice.payments"
                                        :key="payment.id"
                                        class="payment-pill"
                                    >
                                        {{ paymentMethodLabel(payment.payment_method) }}
                                        · {{ money(payment.amount) }}
                                        <template v-if="payment.financial_account?.name || payment.financialAccount?.name">
                                            · {{ payment.financial_account?.name || payment.financialAccount?.name }}
                                        </template>
                                    </span>
                                </div>
                            </article>
                        </div>

                        <div v-else class="empty-state">لا توجد فواتير مشتريات ضمن الفلاتر المحددة.</div>
                    </section>

                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">03</span>
                                <h2>تحليل التوريد</h2>
                                <p>أعلى الموردين والمنتجات والفواتير المتأخرة.</p>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-3">
                            <div class="analysis-card">
                                <h3>المشتريات حسب المورد</h3>
                                <table class="compact-table">
                                    <thead><tr><th>المورد</th><th>الإجمالي</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in data.purchases_by_supplier || []" :key="row.name">
                                            <td>{{ row.name }}</td>
                                            <td>{{ money(row.total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analysis-card">
                                <h3>المنتجات الأكثر شراءً</h3>
                                <table class="compact-table">
                                    <thead><tr><th>المنتج</th><th>الكمية</th><th>القيمة</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in data.top_products || []" :key="row.id || row.code">
                                            <td>{{ row.name }}</td>
                                            <td>{{ number(row.total_quantity) }}</td>
                                            <td>{{ money(row.total_amount) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analysis-card">
                                <h3>الفواتير المتأخرة</h3>
                                <table class="compact-table">
                                    <thead><tr><th>الفاتورة</th><th>الاستحقاق</th><th>المتبقي</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in data.overdue_invoices || []" :key="row.id">
                                            <td dir="ltr">{{ row.invoice_number }}</td>
                                            <td>{{ formatDate(row.due_date) }}</td>
                                            <td>{{ money(row.remaining_amount) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </template>

                <!-- INVENTORY -->
                <template v-else-if="type === 'inventory'">
                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">02</span>
                                <h2>أرصدة المخزون الحالية</h2>
                                <p>تفاصيل كل منتج في مخزن المبيعات والصيانة والقيمة الإجمالية.</p>
                            </div>

                            <div class="section-chip">{{ data.inventory_data?.length || 0 }} منتج</div>
                        </div>

                        <div class="table-shell">
                            <table class="report-table inventory-table">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الفئة</th>
                                        <th>سعر الشراء</th>
                                        <th>مخزون المبيعات</th>
                                        <th>مخزون الصيانة</th>
                                        <th>الإجمالي</th>
                                        <th>قيمة المخزون</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="row in data.inventory_data || []"
                                        :key="row.product?.id"
                                    >
                                        <td>
                                            <strong>{{ row.product?.name }}</strong>
                                            <small dir="ltr">{{ row.product?.code || '—' }}</small>
                                        </td>
                                        <td>{{ row.product?.category?.name || '—' }}</td>
                                        <td>{{ money(row.product?.purchase_price) }}</td>
                                        <td>{{ number(row.sales_qty) }}</td>
                                        <td>{{ number(row.maintenance_qty) }}</td>
                                        <td><strong>{{ number(row.total_qty) }}</strong></td>
                                        <td>{{ money(row.total_value) }}</td>
                                        <td>{{ stockStatusLabel(row.stock_status) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">03</span>
                                <h2>آخر حركات المخزون</h2>
                                <p>أحدث الإضافات والخصومات والتحويلات والتسويات.</p>
                            </div>
                        </div>

                        <div class="table-shell">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>المنتج</th>
                                        <th>المخزن</th>
                                        <th>نوع الحركة</th>
                                        <th>الكمية</th>
                                        <th>الرصيد قبل</th>
                                        <th>الرصيد بعد</th>
                                        <th>الملاحظات</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="movement in data.recent_movements || []"
                                        :key="movement.id"
                                    >
                                        <td>{{ formatDateTime(movement.created_at) }}</td>
                                        <td>{{ movement.product?.name || '—' }}</td>
                                        <td>{{ movement.warehouse?.name || '—' }}</td>
                                        <td>{{ movementTypeLabel(movement.type) }}</td>
                                        <td>{{ number(movement.quantity) }}</td>
                                        <td>{{ number(movement.balance_before) }}</td>
                                        <td>{{ number(movement.balance_after) }}</td>
                                        <td>{{ movement.notes || movement.description || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="report-section">
                        <div class="grid gap-4 lg:grid-cols-2">
                            <div class="analysis-card">
                                <h3>منتجات منخفضة المخزون</h3>
                                <table class="compact-table">
                                    <thead><tr><th>المنتج</th><th>المتوفر</th><th>الحد الأدنى</th></tr></thead>
                                    <tbody>
                                        <tr v-for="product in data.low_stock_products || []" :key="product.id">
                                            <td>{{ product.name }}</td>
                                            <td>{{ number((product.stocks || []).reduce((s, x) => s + Number(x.quantity || 0), 0)) }}</td>
                                            <td>{{ number(product.low_stock_threshold || 5) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analysis-card">
                                <h3>منتجات نافدة</h3>
                                <table class="compact-table">
                                    <thead><tr><th>المنتج</th><th>الكود</th><th>الفئة</th></tr></thead>
                                    <tbody>
                                        <tr v-for="product in data.out_of_stock_products || []" :key="product.id">
                                            <td>{{ product.name }}</td>
                                            <td dir="ltr">{{ product.code || '—' }}</td>
                                            <td>{{ product.category?.name || '—' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </template>

                <!-- REPAIRS -->
                <template v-else-if="type === 'repairs'">
                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">02</span>
                                <h2>تفاصيل طلبات الصيانة</h2>
                                <p>الأجهزة والعملاء والأعطال والحالات والتكاليف والمدفوعات.</p>
                            </div>

                            <div class="section-chip">{{ data.orders?.length || 0 }} طلب</div>
                        </div>

                        <div class="table-shell">
                            <table class="report-table repairs-table">
                                <thead>
                                    <tr>
                                        <th>رقم الطلب</th>
                                        <th>العميل</th>
                                        <th>الجهاز</th>
                                        <th>المشكلة</th>
                                        <th>الحالة</th>
                                        <th>الاستلام</th>
                                        <th>المتوقع</th>
                                        <th>الإجمالي</th>
                                        <th>المدفوع</th>
                                        <th>المتبقي</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="order in data.orders || []"
                                        :key="order.id"
                                    >
                                        <td dir="ltr"><strong>{{ order.order_number }}</strong></td>
                                        <td>
                                            <strong>{{ order.customer?.name || order.customer_name || '—' }}</strong>
                                            <small dir="ltr">{{ order.customer_phone || order.customer?.phone || '—' }}</small>
                                        </td>
                                        <td>
                                            {{ order.device_brand || '' }} {{ order.device_model || order.device_type || '' }}
                                        </td>
                                        <td>{{ order.problem_description || order.fault_cause || '—' }}</td>
                                        <td>{{ repairStatusLabel(order.status) }}</td>
                                        <td>{{ formatDate(order.received_at) }}</td>
                                        <td>{{ formatDate(order.expected_delivery_date) }}</td>
                                        <td>{{ money(order.total_amount) }}</td>
                                        <td>{{ money(order.paid_amount) }}</td>
                                        <td>{{ money(order.remaining_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">03</span>
                                <h2>التحليل الفني</h2>
                                <p>أنواع الأجهزة والأعطال وقطع الغيار الأكثر استخداماً.</p>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-3">
                            <div class="analysis-card">
                                <h3>أنواع الأجهزة</h3>
                                <table class="compact-table">
                                    <thead><tr><th>النوع</th><th>العدد</th></tr></thead>
                                    <tbody>
                                        <tr v-for="(value, label) in data.device_types || {}" :key="label">
                                            <td>{{ label }}</td>
                                            <td>{{ number(value) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analysis-card">
                                <h3>الأعطال الأكثر تكراراً</h3>
                                <table class="compact-table">
                                    <thead><tr><th>العطل</th><th>العدد</th></tr></thead>
                                    <tbody>
                                        <tr v-for="(value, label) in data.faults || {}" :key="label">
                                            <td>{{ label }}</td>
                                            <td>{{ number(value) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analysis-card">
                                <h3>قطع الغيار الأكثر استخداماً</h3>
                                <table class="compact-table">
                                    <thead><tr><th>القطعة</th><th>الكمية</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in data.top_parts || []" :key="row.product_id">
                                            <td>{{ row.product?.name || '—' }}</td>
                                            <td>{{ number(row.total_quantity) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </template>

                <!-- FINANCE -->
                <template v-else-if="type === 'finance'">
                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">02</span>
                                <h2>أرصدة الحسابات المالية</h2>
                                <p>الحسابات الفعالة وأرصدة كل حساب وقت إصدار التقرير.</p>
                            </div>

                            <div class="section-chip">{{ data.accounts?.length || 0 }} حساب</div>
                        </div>

                        <div class="table-shell">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>الحساب</th>
                                        <th>النوع</th>
                                        <th>الرصيد الافتتاحي</th>
                                        <th>الرصيد الحالي</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="account in data.accounts || []"
                                        :key="account.id"
                                    >
                                        <td><strong>{{ account.name }}</strong></td>
                                        <td>{{ account.type_label || enumValue(account.type) }}</td>
                                        <td>{{ money(account.opening_balance) }}</td>
                                        <td><strong>{{ money(account.current_balance) }}</strong></td>
                                        <td>{{ account.is_active === false ? 'غير نشط' : 'نشط' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section
                        v-if="data.transfers?.length"
                        class="report-section"
                    >
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">03</span>
                                <h2>التحويلات بين الحسابات</h2>
                                <p>حركات نقل داخلية لا تغيّر إجمالي أموال المحل ولا تدخل ضمن صافي التدفق العام.</p>
                            </div>

                            <div class="section-chip">{{ data.transfers.length }} تحويل</div>
                        </div>

                        <div class="table-shell">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>رقم التحويل</th>
                                        <th>من حساب</th>
                                        <th>إلى حساب</th>
                                        <th>المبلغ</th>
                                        <th>التاريخ</th>
                                        <th>الحالة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="transfer in data.transfers" :key="transfer.id">
                                        <td dir="ltr"><strong>{{ transfer.transfer_number }}</strong></td>
                                        <td>{{ transfer.from_account?.name || '—' }}</td>
                                        <td>{{ transfer.to_account?.name || '—' }}</td>
                                        <td><strong>{{ money(transfer.amount) }}</strong></td>
                                        <td>{{ formatDateTime(transfer.transfer_date) }}</td>
                                        <td>{{ transferStatusLabel(transfer.status) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section
                        v-if="data.transactions?.length"
                        class="report-section"
                    >
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">{{ data.transfers?.length ? '04' : '03' }}</span>
                                <h2>الحركات المالية التفصيلية</h2>
                                <p>جميع الحركات الواردة والصادرة المطابقة للفترة.</p>
                            </div>

                            <div class="section-chip">{{ data.transactions.length }} حركة</div>
                        </div>

                        <div class="table-shell">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>التاريخ</th>
                                        <th>الحساب</th>
                                        <th>نوع الحركة</th>
                                        <th>الاتجاه</th>
                                        <th>الوصف</th>
                                        <th>المبلغ</th>
                                        <th>قبل</th>
                                        <th>بعد</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="transaction in data.transactions"
                                        :key="transaction.id"
                                    >
                                        <td>{{ formatDateTime(transaction.transaction_date) }}</td>
                                        <td>{{ transaction.account?.name || transaction.financial_account?.name || '—' }}</td>
                                        <td>{{ transactionTypeLabel(transaction.type) }}</td>
                                        <td>{{ directionLabel(transaction.direction) }}</td>
                                        <td>{{ transaction.description || '—' }}</td>
                                        <td><strong>{{ money(transaction.amount) }}</strong></td>
                                        <td>{{ money(transaction.balance_before) }}</td>
                                        <td>{{ money(transaction.balance_after) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">{{ data.transfers?.length && data.transactions?.length ? '05' : (data.transfers?.length || data.transactions?.length ? '04' : '03') }}</span>
                                <h2>الديون والإغلاقات اليومية</h2>
                                <p>أعمار ديون العملاء ونتائج الإغلاق المالي.</p>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-[0.9fr_1.6fr]">
                            <div class="analysis-card">
                                <h3>أعمار الديون</h3>
                                <table class="compact-table">
                                    <thead><tr><th>الفترة</th><th>القيمة</th></tr></thead>
                                    <tbody>
                                        <tr v-for="row in agingRows" :key="row[0]">
                                            <td>{{ row[0] }}</td>
                                            <td>{{ money(row[1]) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="analysis-card">
                                <h3>الإغلاقات اليومية</h3>
                                <table class="compact-table">
                                    <thead>
                                        <tr>
                                            <th>التاريخ</th>
                                            <th>الحساب</th>
                                            <th>المتوقع</th>
                                            <th>الفعلي</th>
                                            <th>الفرق</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr v-for="row in data.closings || []" :key="row.id">
                                            <td>{{ formatDate(row.closing_date) }}</td>
                                            <td>{{ row.account?.name || '—' }}</td>
                                            <td>{{ money(row.expected_balance) }}</td>
                                            <td>{{ money(row.actual_balance) }}</td>
                                            <td>{{ money(row.difference) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </section>
                </template>

                <!-- RETURNS -->
                <template v-else-if="type === 'returns'">
                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">02</span>
                                <h2>مرتجعات المبيعات</h2>
                                <p>العملاء والمنتجات والحالة والمبالغ المستردة أو المخفضة من الدين.</p>
                            </div>

                            <div class="section-chip">{{ data.sales_returns?.length || 0 }} مرتجع</div>
                        </div>

                        <div
                            v-if="data.sales_returns?.length"
                            class="table-shell"
                        >
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>رقم المرتجع</th>
                                        <th>العميل</th>
                                        <th>التاريخ</th>
                                        <th>المنتجات</th>
                                        <th>القيمة</th>
                                        <th>خفض الدين</th>
                                        <th>المسترد</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="row in data.sales_returns"
                                        :key="row.id"
                                    >
                                        <td dir="ltr"><strong>{{ row.return_number }}</strong></td>
                                        <td>{{ row.customer?.name || 'عميل نقدي' }}</td>
                                        <td>{{ formatDate(row.return_date || row.created_at) }}</td>
                                        <td>
                                            <span
                                                v-for="(item, index) in row.items || []"
                                                :key="item.id"
                                            >
                                                {{ item.product?.name || 'منتج' }} × {{ number(item.quantity) }}<template v-if="index < row.items.length - 1">، </template>
                                            </span>
                                        </td>
                                        <td>{{ money(row.total_amount) }}</td>
                                        <td>{{ money(row.amount_used_for_debt) }}</td>
                                        <td>{{ money(row.amount_refunded) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="empty-state">لا توجد مرتجعات مبيعات ضمن الفترة.</div>
                    </section>

                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">03</span>
                                <h2>مرتجعات المشتريات</h2>
                                <p>الموردون والمنتجات والقيم المستردة وتسوية المستحقات.</p>
                            </div>

                            <div class="section-chip">{{ data.purchase_returns?.length || 0 }} مرتجع</div>
                        </div>

                        <div
                            v-if="data.purchase_returns?.length"
                            class="table-shell"
                        >
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>رقم المرتجع</th>
                                        <th>المورد</th>
                                        <th>التاريخ</th>
                                        <th>المنتجات</th>
                                        <th>القيمة</th>
                                        <th>خفض المستحق</th>
                                        <th>المسترد</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="row in data.purchase_returns"
                                        :key="row.id"
                                    >
                                        <td dir="ltr"><strong>{{ row.return_number }}</strong></td>
                                        <td>{{ row.supplier?.name || row.supplier?.company_name || '—' }}</td>
                                        <td>{{ formatDate(row.return_date || row.created_at) }}</td>
                                        <td>
                                            <span
                                                v-for="(item, index) in row.items || []"
                                                :key="item.id"
                                            >
                                                {{ item.product?.name || 'منتج' }} × {{ number(item.quantity) }}<template v-if="index < row.items.length - 1">، </template>
                                            </span>
                                        </td>
                                        <td>{{ money(row.total_amount) }}</td>
                                        <td>{{ money(row.amount_used_for_debt) }}</td>
                                        <td>{{ money(row.amount_refunded) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div v-else class="empty-state">لا توجد مرتجعات مشتريات ضمن الفترة.</div>
                    </section>

                    <section class="report-section">
                        <div class="section-title-row">
                            <div>
                                <span class="section-index">04</span>
                                <h2>عمليات الاستبدال</h2>
                                <p>المرتجع والفاتورة الجديدة وفرق السعر وطريقة التسوية.</p>
                            </div>

                            <div class="section-chip">{{ data.exchanges?.length || 0 }} عملية</div>
                        </div>

                        <div class="table-shell">
                            <table class="report-table">
                                <thead>
                                    <tr>
                                        <th>رقم العملية</th>
                                        <th>التاريخ</th>
                                        <th>قيمة المرتجع</th>
                                        <th>قيمة البدائل</th>
                                        <th>فرق السعر</th>
                                        <th>التسوية</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="row in data.exchanges || []"
                                        :key="row.id"
                                    >
                                        <td dir="ltr"><strong>{{ row.exchange_number }}</strong></td>
                                        <td>{{ formatDate(row.exchanged_at || row.created_at) }}</td>
                                        <td>{{ money(row.return_value) }}</td>
                                        <td>{{ money(row.new_items_value) }}</td>
                                        <td>{{ money(row.price_difference) }}</td>
                                        <td>{{ settlementLabel(row.settlement_type) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="report-section">
                        <div class="analysis-card">
                            <h3>المنتجات الأكثر إرجاعاً</h3>
                            <table class="compact-table">
                                <thead>
                                    <tr>
                                        <th>المنتج</th>
                                        <th>الكود</th>
                                        <th>الكمية</th>
                                        <th>القيمة</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="row in data.top_returned_products || []"
                                        :key="row.id || row.code"
                                    >
                                        <td>{{ row.name }}</td>
                                        <td dir="ltr">{{ row.code || '—' }}</td>
                                        <td>{{ number(row.total_quantity) }}</td>
                                        <td>{{ money(row.total_amount) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </template>

                <!-- Footer note -->
                <footer class="report-footer">
                    <div>
                        <strong>{{ store.store_name || 'فنانة فون' }}</strong>
                        <span>هذا التقرير مستخرج آلياً من بيانات النظام حسب الفلاتر المحددة.</span>
                    </div>

                    <div class="text-left">
                        <span v-if="store.store_phone">{{ store.store_phone }}</span>
                        <span v-if="store.store_address">{{ store.store_address }}</span>
                    </div>
                </footer>

                <!-- Buttons -->
                <div class="flex flex-col justify-center gap-3 sm:flex-row print:hidden">
                    <button
                        type="button"
                        @click="printPage"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 9V3.75h10.5V9m-10.5 8.25H5.25A2.25 2.25 0 013 15V9.75A2.25 2.25 0 015.25 7.5h13.5A2.25 2.25 0 0121 9.75V15a2.25 2.25 0 01-2.25 2.25h-1.5M6.75 13.5h10.5v6.75H6.75V13.5z" />
                        </svg>

                        طباعة التقرير
                    </button>

                    <Link
                        :href="backHref"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-6 py-3 font-black text-slate-700 transition hover:bg-slate-50"
                    >
                        العودة إلى التقرير
                    </Link>
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

.report-hero {
    background:
        linear-gradient(120deg, #07111f 0%, #101a35 48%, #312e81 100%);
}

.summary-card {
    border-top: 3px solid #4f46e5;
    break-inside: avoid;
    page-break-inside: avoid;
}

.report-section {
    break-inside: auto;
    page-break-inside: auto;
}

.section-title-row {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    break-after: avoid;
    page-break-after: avoid;
}

.section-title-row h2 {
    margin-top: .2rem;
    font-size: 1.1rem;
    line-height: 1.5;
    font-weight: 900;
    color: #0f172a;
}

.section-title-row p {
    margin-top: .25rem;
    font-size: .78rem;
    color: #64748b;
}

.section-index {
    color: #4f46e5;
    font-size: .7rem;
    font-weight: 900;
}

.section-chip {
    flex: none;
    border: 1px solid #c7d2fe;
    border-radius: 999px;
    background: #eef2ff;
    padding: .4rem .7rem;
    color: #4338ca;
    font-size: .7rem;
    font-weight: 800;
}

.detail-card {
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: #fff;
    break-inside: avoid;
    page-break-inside: avoid;
}

.detail-card-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
}

.detail-card-head h3 {
    margin-top: .25rem;
    color: #0f172a;
    font-size: .95rem;
    font-weight: 900;
}

.detail-card-head p {
    margin-top: .2rem;
    color: #64748b;
    font-size: .72rem;
}

.reference-number {
    color: #4338ca !important;
    font-size: .8rem !important;
    font-weight: 900;
    text-align: right;
}

.detail-head-meta {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: .4rem;
}

.detail-head-meta span {
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    background: #fff;
    padding: .3rem .55rem;
    color: #475569;
    font-size: .65rem;
    font-weight: 800;
}

.mini-stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    border-bottom: 1px solid #e2e8f0;
}

.mini-stats > div {
    padding: .75rem 1rem;
    border-left: 1px solid #e2e8f0;
}

.mini-stats > div:last-child {
    border-left: 0;
}

.mini-stats span {
    display: block;
    color: #64748b;
    font-size: .65rem;
}

.mini-stats strong {
    display: block;
    margin-top: .2rem;
    color: #0f172a;
    font-size: .78rem;
    font-weight: 900;
}

.table-shell {
    width: 100%;
    overflow-x: auto;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
}

.detail-card .table-shell {
    border: 0;
    border-radius: 0;
}

.report-table,
.compact-table {
    width: 100%;
    border-collapse: collapse;
}

.report-table thead,
.compact-table thead {
    display: table-header-group;
}

.report-table tr,
.compact-table tr {
    break-inside: avoid;
    page-break-inside: avoid;
}

.report-table th {
    padding: .68rem .7rem;
    background: #111827;
    color: #fff;
    text-align: right;
    font-size: .68rem;
    font-weight: 900;
    white-space: nowrap;
}

.report-table td {
    padding: .68rem .7rem;
    border-bottom: 1px solid #edf2f7;
    color: #334155;
    vertical-align: top;
    font-size: .7rem;
}

.report-table tbody tr:nth-child(even) td {
    background: #f8fafc;
}

.report-table td strong {
    color: #0f172a;
    font-weight: 900;
}

.report-table td small {
    display: block;
    margin-top: .2rem;
    color: #94a3b8;
    font-size: .62rem;
    text-align: right;
}

.payment-strip {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: .4rem;
    padding: .7rem 1rem;
    background: #ecfdf5;
    border-top: 1px solid #d1fae5;
}

.payment-strip-title {
    color: #047857;
    font-size: .68rem;
    font-weight: 900;
}

.payment-pill {
    border: 1px solid #a7f3d0;
    border-radius: 999px;
    background: #fff;
    padding: .3rem .55rem;
    color: #065f46;
    font-size: .64rem;
    font-weight: 800;
}

.analysis-card {
    overflow: hidden;
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    background: #fff;
    break-inside: avoid;
    page-break-inside: avoid;
}

.analysis-card h3 {
    padding: .8rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    background: #f8fafc;
    color: #0f172a;
    font-size: .8rem;
    font-weight: 900;
}

.compact-table th {
    padding: .55rem .65rem;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    color: #64748b;
    text-align: right;
    font-size: .64rem;
    font-weight: 900;
}

.compact-table td {
    padding: .55rem .65rem;
    border-bottom: 1px solid #edf2f7;
    color: #334155;
    font-size: .66rem;
}

.empty-state {
    border: 1px dashed #cbd5e1;
    border-radius: 1rem;
    background: #f8fafc;
    padding: 2rem;
    color: #64748b;
    text-align: center;
    font-size: .8rem;
    font-weight: 700;
}

.report-footer {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-top: .5rem;
    padding-top: 1rem;
    border-top: 2px solid #e2e8f0;
    color: #64748b;
    font-size: .68rem;
}

.report-footer strong,
.report-footer span {
    display: block;
}

.report-footer strong {
    color: #0f172a;
    font-size: .75rem;
}

@media (max-width: 900px) {
    .mini-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .section-title-row,
    .detail-card-head,
    .report-footer {
        align-items: stretch;
        flex-direction: column;
    }

    .detail-head-meta {
        justify-content: flex-start;
    }
}

@media print {
    html,
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .report-print-page {
        min-height: 0 !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #fff !important;
    }

    .report-print-page > article {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .report-hero {
        background: #101a35 !important;
    }

    .table-shell {
        overflow: visible !important;
    }

    table {
        width: 100% !important;
    }

    thead {
        display: table-header-group !important;
    }

    tfoot {
        display: table-footer-group !important;
    }

    tr,
    td,
    th {
        page-break-inside: avoid !important;
    }

    a[href]::after {
        content: none !important;
    }
}
</style>
