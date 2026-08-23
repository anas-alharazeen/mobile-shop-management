<script setup>
import {
    computed,
    onBeforeUnmount,
    onMounted,
    reactive,
    ref,
} from 'vue';

import {
    Head,
    Link,
    router,
} from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    dashboardData: {
        type: Object,
        default: () => ({
            metrics: {},
            sales_chart: {
                labels: [],
                revenue: [],
                sales: [],
                profit: [],
                expenses: [],
            },
            sales_distribution: {
                by_category: [],
                by_payment_method: [],
                revenue_sources: {},
            },
            repair_stats: {
                statuses: {},
                active_total: 0,
                overdue: 0,
                waiting_approval: 0,
                waiting_parts: 0,
                ready_orders: 0,
                recent_orders: [],
            },
            top_products: [],
            low_stock_products: [],
            out_of_stock_products: [],
            alerts: [],
            recent_activities: {
                sales: [],
                purchases: [],
                repairs: [],
                expenses: [],
            },
            financial_summary: {
                accounts: [],
                total_balance: 0,
                cash_balance: 0,
                period_inflows: 0,
                period_outflows: 0,
                net_flow: 0,
                period_expenses: 0,
                recent_transactions: [],
            },
            receivables: {
                customer_debts: 0,
                sales_debts: 0,
                repair_debts: 0,
                supplier_debts: 0,
                customer_due_documents: 0,
                supplier_due_documents: 0,
                customers_with_due: 0,
                suppliers_with_due: 0,
                overdue_customer_documents: 0,
                overdue_supplier_documents: 0,
            },
            entity_counts: {},
            date_range: {
                start: null,
                end: null,
            },
            generated_at: null,
        }),
    },

    filters: {
        type: Object,
        default: () => ({
            period: 'today',
        }),
    },
});

const periods = [
    {
        key: 'today',
        label: 'اليوم',
    },
    {
        key: 'yesterday',
        label: 'أمس',
    },
    {
        key: 'last_7_days',
        label: 'آخر 7 أيام',
    },
    {
        key: 'this_week',
        label: 'هذا الأسبوع',
    },
    {
        key: 'this_month',
        label: 'هذا الشهر',
    },
    {
        key: 'last_month',
        label: 'الشهر الماضي',
    },
    {
        key: 'this_year',
        label: 'هذه السنة',
    },
];

const filters = reactive({
    period:
        props.filters?.period
        || 'today',

    start_date:
        props.filters?.start_date
        || '',

    end_date:
        props.filters?.end_date
        || '',
});

const refreshing = ref(false);
const autoRefresh = ref(true);
const customDateError = ref('');
let refreshTimer = null;

const localDateInput = (date = new Date()) => {
    const localTime = new Date(
        date.getTime()
        - date.getTimezoneOffset() * 60000
    );

    return localTime
        .toISOString()
        .slice(0, 10);
};

const todayInput = localDateInput();

const asArray = (value) => (
    Array.isArray(value)
        ? value
        : (
            value
            && typeof value === 'object'
                ? Object.values(value)
                : []
        )
);

const metrics = computed(
    () => props.dashboardData?.metrics || {}
);

const salesChart = computed(
    () => props.dashboardData?.sales_chart || {
        labels: [],
        revenue: [],
        sales: [],
        profit: [],
        expenses: [],
    }
);

const salesDistribution = computed(
    () => props.dashboardData?.sales_distribution || {
        by_category: [],
        by_payment_method: [],
        revenue_sources: {},
    }
);

const repairStats = computed(
    () => props.dashboardData?.repair_stats || {
        statuses: {},
        active_total: 0,
        overdue: 0,
        waiting_approval: 0,
        waiting_parts: 0,
        ready_orders: 0,
    }
);

const topProducts = computed(
    () => asArray(
        props.dashboardData?.top_products
    )
);

const lowStockProducts = computed(
    () => asArray(
        props.dashboardData?.low_stock_products
    )
);

const outOfStockProducts = computed(
    () => asArray(
        props.dashboardData?.out_of_stock_products
    )
);

const alerts = computed(
    () => asArray(
        props.dashboardData?.alerts
    )
);

const recentActivities = computed(
    () => props.dashboardData?.recent_activities || {
        sales: [],
        purchases: [],
        repairs: [],
        expenses: [],
    }
);

const financialSummary = computed(
    () => props.dashboardData?.financial_summary || {}
);

const receivables = computed(
    () => props.dashboardData?.receivables || {}
);

const entityCounts = computed(
    () => props.dashboardData?.entity_counts || {}
);

const formatCurrency = (value) => (
    `${Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`
);

const formatNumber = (value) => (
    Number(value || 0).toLocaleString('en-US')
);

const formatPercent = (value) => {
    if (
        value === null
        || value === undefined
        || Number.isNaN(Number(value))
    ) {
        return '—';
    }

    return `${Number(value).toLocaleString('en-US', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
    })}%`;
};

const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(
        'ar-PS-u-nu-latn',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }
    ).format(parsed);
};

const formatDateTime = (value) => {
    if (!value) {
        return '—';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat(
        'ar-PS-u-nu-latn',
        {
            day: '2-digit',
            month: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        }
    ).format(parsed);
};

const currentDate = computed(() => (
    new Intl.DateTimeFormat(
        'ar-PS-u-nu-latn',
        {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }
    ).format(new Date())
));

const lastUpdated = computed(() => (
    formatDateTime(
        props.dashboardData?.generated_at
    )
));

const dateRange = computed(() => {
    const start =
        props.dashboardData
            ?.date_range
            ?.start;

    const end =
        props.dashboardData
            ?.date_range
            ?.end;

    if (!start || !end) {
        return 'الفترة غير محددة';
    }

    if (start === end) {
        return formatDate(start);
    }

    return `${formatDate(start)} — ${formatDate(end)}`;
});

const periodLabel = computed(() => {
    if (
        props.dashboardData?.period
        === 'custom'
    ) {
        return 'فترة مخصصة';
    }

    return periods.find(
        (period) =>
            period.key
            === props.dashboardData?.period
    )?.label || 'اليوم';
});

const paymentMethodLabels = {
    cash: 'كاش',
    bank_transfer: 'تحويل بنكي',
    banking_app: 'تطبيق بنكي',
    exchange_credit: 'رصيد استبدال',
};

const transactionDirectionLabels = {
    inflow: 'وارد',
    outflow: 'صادر',
};

const statusLabels = {
    draft: 'مسودة',
    approved: 'معتمدة',
    cancelled: 'ملغاة',
    received: 'مستلم',
    in_progress: 'قيد التنفيذ',
    ready: 'جاهز',
    delivered: 'تم التسليم',
};

const enumValue = (value) => (
    value
    && typeof value === 'object'
        ? (
            value.value
            ?? value.name
            ?? ''
        )
        : value
);

const paymentMethodLabel = (value) => (
    paymentMethodLabels[
        enumValue(value)
    ]
    || enumValue(value)
    || 'غير محدد'
);

const directionLabel = (value) => (
    transactionDirectionLabels[
        enumValue(value)
    ]
    || enumValue(value)
    || '—'
);

const statusLabel = (value) => (
    statusLabels[
        enumValue(value)
    ]
    || enumValue(value)
    || '—'
);

const mainCards = computed(() => [
    {
        title: 'صافي الإيرادات',
        value: formatCurrency(
            metrics.value.total_revenue
        ),
        subtitle:
            'صافي المبيعات + الصيانة المسلمة',
        icon: 'revenue',
        shell:
            'from-blue-600 via-blue-600 to-sky-600',
        chip:
            'bg-white/15 text-white',
    },
    {
        title: 'صافي الربح',
        value: formatCurrency(
            metrics.value.net_profit
        ),
        subtitle:
            'الربح بعد خصم المصروفات',
        icon: 'profit',
        shell:
            Number(
                metrics.value.net_profit || 0
            ) >= 0
                ? 'from-emerald-600 via-emerald-600 to-teal-600'
                : 'from-rose-600 via-rose-600 to-red-600',
        chip:
            'bg-white/15 text-white',
    },
    {
        title: 'صافي المبيعات',
        value: formatCurrency(
            metrics.value.net_sales
        ),
        subtitle:
            `بعد المرتجعات ${formatCurrency(metrics.value.total_returns)}`,
        icon: 'sales',
        shell:
            'from-slate-950 via-slate-900 to-slate-800',
        chip:
            'bg-white/10 text-white',
    },
    {
        title: 'عدد الفواتير',
        value: formatNumber(
            metrics.value.sales_count
        ),
        subtitle:
            `متوسط الفاتورة ${formatCurrency(metrics.value.average_ticket)}`,
        icon: 'invoice',
        shell:
            'from-violet-600 via-purple-600 to-fuchsia-600',
        chip:
            'bg-white/15 text-white',
    },
]);

const secondaryCards = computed(() => [
    {
        title: 'إجمالي البيع قبل المرتجعات',
        value: formatCurrency(
            metrics.value.total_sales
        ),
        subtitle:
            `${formatNumber(metrics.value.sales_count)} فاتورة`,
        icon: 'sales',
        href: route('reports.sales'),
        tone:
            'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300',
    },
    {
        title: 'الربح الإجمالي',
        value: formatCurrency(
            metrics.value.total_profit
        ),
        subtitle:
            `هامش ${formatPercent(metrics.value.profit_margin)}`,
        icon: 'profit',
        href: route('reports.sales'),
        tone:
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
    },
    {
        title: 'المشتريات الصافية',
        value: formatCurrency(
            metrics.value.total_purchases
        ),
        subtitle:
            `مرتجع ${formatCurrency(metrics.value.purchase_returns)}`,
        icon: 'purchase',
        href: route('reports.purchases'),
        tone:
            'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    },
    {
        title: 'المصروفات',
        value: formatCurrency(
            metrics.value.total_expenses
        ),
        subtitle: 'خلال الفترة المحددة',
        icon: 'expense',
        href: route('reports.finance'),
        tone:
            'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
    },
    {
        title: 'قيمة المخزون',
        value: formatCurrency(
            metrics.value.inventory_value
        ),
        subtitle: 'الرصيد الحالي للمخزون',
        icon: 'inventory',
        href: route('reports.inventory'),
        tone:
            'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/30 dark:text-cyan-300',
    },
    {
        title: 'مستحقات العملاء',
        value: formatCurrency(
            receivables.value.customer_debts
        ),
        subtitle:
            `${formatNumber(receivables.value.customers_with_due)} عميل · ${formatNumber(receivables.value.overdue_customer_documents)} متأخر`,
        icon: 'customerDebt',
        href: route('payments.customer-receivables'),
        tone:
            'bg-orange-50 text-orange-700 dark:bg-orange-950/30 dark:text-orange-300',
    },
    {
        title: 'مستحقات الموردين',
        value: formatCurrency(
            receivables.value.supplier_debts
        ),
        subtitle:
            `${formatNumber(receivables.value.suppliers_with_due)} مورد · ${formatNumber(receivables.value.overdue_supplier_documents)} متأخر`,
        icon: 'supplierDebt',
        href: route('payments.supplier-payables'),
        tone:
            'bg-fuchsia-50 text-fuchsia-700 dark:bg-fuchsia-950/30 dark:text-fuchsia-300',
    },
    {
        title: 'الصيانة النشطة',
        value: formatNumber(
            repairStats.value.active_total
        ),
        subtitle:
            `${formatNumber(repairStats.value.overdue)} متأخر · ${formatNumber(repairStats.value.ready_orders)} جاهز`,
        icon: 'repair',
        href: route('repairs.index'),
        tone:
            'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300',
    },
]);

const quickActions = [
    {
        label: 'بيع جديد',
        description: 'فتح نقطة البيع',
        href: route('pos'),
        icon: 'cart',
        tone:
            'bg-blue-600 text-white hover:bg-blue-700',
    },
    {
        label: 'فاتورة شراء',
        description: 'إضافة مخزون',
        href: route('purchases.create'),
        icon: 'purchase',
        tone:
            'bg-white text-slate-800 border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-white dark:border-slate-700 dark:hover:bg-slate-700',
    },
    {
        label: 'استقبال صيانة',
        description: 'طلب صيانة جديد',
        href: route('repairs.create'),
        icon: 'repair',
        tone:
            'bg-white text-slate-800 border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-white dark:border-slate-700 dark:hover:bg-slate-700',
    },
    {
        label: 'تحصيل عميل',
        description: 'المستحقات والتحصيل',
        href: route('payments.customer-receivables'),
        icon: 'customerDebt',
        tone:
            'bg-white text-slate-800 border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-white dark:border-slate-700 dark:hover:bg-slate-700',
    },
    {
        label: 'مصروف جديد',
        description: 'المصروفات المالية',
        href: route('finance.expenses'),
        icon: 'expense',
        tone:
            'bg-white text-slate-800 border border-slate-200 hover:bg-slate-50 dark:bg-slate-800 dark:text-white dark:border-slate-700 dark:hover:bg-slate-700',
    },
    {
        label: 'التقارير',
        description: 'تحليلات تفصيلية',
        href: route('reports.index'),
        icon: 'report',
        tone:
            'bg-slate-950 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200',
    },
];

const repairStatusCards = computed(() => [
    {
        key: 'received',
        label: 'مستلم',
        value:
            repairStats.value
                ?.statuses
                ?.received || 0,
        tone:
            'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300',
    },
    {
        key: 'in_progress',
        label: 'قيد التنفيذ',
        value:
            repairStats.value
                ?.statuses
                ?.in_progress || 0,
        tone:
            'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    },
    {
        key: 'ready',
        label: 'جاهز',
        value:
            repairStats.value
                ?.statuses
                ?.ready || 0,
        tone:
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
    },
    {
        key: 'delivered',
        label: 'تم التسليم',
        value:
            repairStats.value
                ?.statuses
                ?.delivered || 0,
        tone:
            'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    },
]);

const categoryRows = computed(() => (
    asArray(
        salesDistribution.value.by_category
    )
));

const paymentRows = computed(() => (
    asArray(
        salesDistribution.value
            .by_payment_method
    )
));

const categoryMax = computed(() => (
    Math.max(
        1,
        ...categoryRows.value.map(
            (row) =>
                Math.abs(
                    Number(row.total || 0)
                )
        )
    )
));

const paymentMax = computed(() => (
    Math.max(
        1,
        ...paymentRows.value.map(
            (row) =>
                Math.abs(
                    Number(row.total || 0)
                )
        )
    )
));

const revenueSources = computed(() => {
    const sales = Number(
        salesDistribution.value
            ?.revenue_sources
            ?.sales || 0
    );

    const repairs = Number(
        salesDistribution.value
            ?.revenue_sources
            ?.repairs || 0
    );

    const total =
        Math.abs(sales)
        + Math.abs(repairs);

    return [
        {
            label: 'المبيعات',
            value: sales,
            percent:
                total > 0
                    ? (
                        Math.abs(sales)
                        / total
                    ) * 100
                    : 0,
            tone: 'bg-blue-500',
        },
        {
            label: 'الصيانة',
            value: repairs,
            percent:
                total > 0
                    ? (
                        Math.abs(repairs)
                        / total
                    ) * 100
                    : 0,
            tone: 'bg-cyan-500',
        },
    ];
});

const financialAccounts = computed(() => (
    asArray(
        financialSummary.value.accounts
    )
));

const recentTransactions = computed(() => (
    asArray(
        financialSummary.value
            .recent_transactions
    )
));

const chartRevenue = computed(() => (
    asArray(
        salesChart.value.revenue
            ?? salesChart.value.sales
    ).map(Number)
));

const chartProfit = computed(() => (
    asArray(
        salesChart.value.profit
    ).map(Number)
));

const chartExpenses = computed(() => (
    asArray(
        salesChart.value.expenses
    ).map(Number)
));

const chartLabels = computed(() => (
    asArray(
        salesChart.value.labels
    )
));

const chartValues = computed(() => [
    ...chartRevenue.value,
    ...chartProfit.value,
    ...chartExpenses.value,
]);

const chartMin = computed(() => (
    Math.min(
        0,
        ...chartValues.value,
    )
));

const chartMax = computed(() => (
    Math.max(
        1,
        ...chartValues.value,
    )
));

const chartWidth = 900;
const chartHeight = 280;
const chartPaddingX = 42;
const chartPaddingY = 24;

const chartY = (value) => {
    const min =
        chartMin.value;

    const max =
        chartMax.value;

    const range =
        max - min || 1;

    const usableHeight =
        chartHeight
        - chartPaddingY * 2;

    return (
        chartPaddingY
        + (
            (
                max
                - Number(value || 0)
            )
            / range
        ) * usableHeight
    );
};

const chartX = (index, total) => {
    if (total <= 1) {
        return chartWidth / 2;
    }

    const usableWidth =
        chartWidth
        - chartPaddingX * 2;

    return (
        chartPaddingX
        + (
            index
            / (total - 1)
        ) * usableWidth
    );
};

const chartPoints = (series) => {
    if (!series?.length) {
        return '';
    }

    return series
        .map(
            (value, index) =>
                `${chartX(index, series.length)},${chartY(value)}`
        )
        .join(' ');
};

const chartGrid = computed(() => {
    const min = chartMin.value;
    const max = chartMax.value;
    const steps = 4;

    return Array.from(
        {
            length: steps + 1,
        },
        (_, index) => {
            const value =
                max
                - (
                    (max - min)
                    / steps
                ) * index;

            return {
                value,
                y: chartY(value),
            };
        }
    );
});

const baselineY = computed(
    () => chartY(0)
);

const shouldShowChartLabel = (
    index,
    total
) => {
    if (total <= 8) {
        return true;
    }

    const step =
        Math.ceil(total / 8);

    return (
        index % step === 0
        || index === total - 1
    );
};

const setPeriod = (period) => {
    filters.period = period;
    filters.start_date = '';
    filters.end_date = '';
    customDateError.value = '';

    loadDashboard();
};

const applyCustomDates = () => {
    customDateError.value = '';

    if (
        !filters.start_date
        || !filters.end_date
    ) {
        customDateError.value =
            'اختر تاريخ البداية والنهاية معاً.';
        return;
    }

    if (
        filters.start_date
        > filters.end_date
    ) {
        customDateError.value =
            'تاريخ البداية يجب أن يسبق أو يساوي تاريخ النهاية.';
        return;
    }

    if (
        filters.end_date
        > todayInput
    ) {
        customDateError.value =
            'لا يمكن اختيار فترة تنتهي في المستقبل.';
        return;
    }

    filters.period = 'custom';

    loadDashboard();
};

const resetToToday = () => {
    filters.period = 'today';
    filters.start_date = '';
    filters.end_date = '';
    customDateError.value = '';

    loadDashboard();
};

const filterQuery = () => {
    const query = {};

    if (filters.period) {
        query.period =
            filters.period;
    }

    if (filters.start_date) {
        query.start_date =
            filters.start_date;
    }

    if (filters.end_date) {
        query.end_date =
            filters.end_date;
    }

    return query;
};

const loadDashboard = () => {
    router.get(
        route('dashboard'),
        filterQuery(),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const refreshData = () => {
    if (refreshing.value) {
        return;
    }

    refreshing.value = true;

    /*
     * No refresh=1 is needed anymore.
     * Dashboard data is live on every request.
     */
    router.reload({
        only: [
            'dashboardData',
        ],
        preserveScroll: true,

        onFinish: () => {
            refreshing.value = false;
        },
    });
};

const alertTone = (type) => ({
    danger:
        'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/25 dark:text-rose-200',
    warning:
        'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/25 dark:text-amber-200',
    info:
        'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/25 dark:text-blue-200',
}[type] || 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200');

const iconPath = (icon) => ({
    revenue:
        'M3 17l6-6 4 4 8-8M21 10V3h-7',
    sales:
        'M3 17l6-6 4 4 8-8',
    profit:
        'M4 19V9m5 10V5m5 14v-7m5 7V3',
    invoice:
        'M6 3h12a2 2 0 012 2v16l-4-2-4 2-4-2-4 2V5a2 2 0 012-2zm2 5h8M8 12h8',
    purchase:
        'M3 3h2l2.2 9.2a2 2 0 002 1.55h7.7a2 2 0 001.94-1.5L20 7H7M10 20h.01M17 20h.01',
    expense:
        'M12 3v18m5-13H9.5a3.5 3.5 0 000 7H14a3 3 0 010 6H6',
    inventory:
        'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
    customerDebt:
        'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm9-2v6m3-3h-6',
    supplierDebt:
        'M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01',
    repair:
        'M14.7 6.3a4 4 0 01-5 5L4 17l3 3 5.7-5.7a4 4 0 005-5l-3 3-3-3 3-3z',
    cart:
        'M3 3h2l2 10h10l3-7H6M9 20h.01M17 20h.01',
    report:
        'M4 19V9m5 10V5m5 14v-7m5 7V3',
}[icon] || 'M12 6v6l4 2');

onMounted(() => {
    refreshTimer = window.setInterval(
        () => {
            if (
                autoRefresh.value
                && document.visibilityState
                    === 'visible'
                && !refreshing.value
            ) {
                refreshData();
            }
        },
        60_000
    );
});

onBeforeUnmount(() => {
    if (refreshTimer) {
        window.clearInterval(
            refreshTimer
        );
    }
});
</script>

<template>
    <Head title="لوحة التحكم" />

    <AuthenticatedLayout>
        <template #header>
            <div class="space-y-5">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-black text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">
                                لوحة التشغيل الرئيسية
                            </span>

                            <span class="inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                بيانات مباشرة
                            </span>
                        </div>

                        <h1 class="mt-2 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                            لوحة التحكم
                        </h1>

                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                            {{ currentDate }}
                            <span class="mx-2 text-slate-300 dark:text-slate-600">•</span>
                            آخر تحديث {{ lastUpdated }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Link
                            v-for="action in quickActions"
                            :key="action.label"
                            :href="action.href"
                            class="inline-flex items-center gap-2 rounded-xl px-3.5 py-2.5 text-xs font-black shadow-sm transition"
                            :class="action.tone"
                        >
                            <svg
                                class="h-4 w-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    :d="iconPath(action.icon)"
                                />
                            </svg>

                            <span>
                                {{ action.label }}
                            </span>
                        </Link>
                    </div>
                </div>

                <!-- Filter center -->
                <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex flex-col gap-3 2xl:flex-row 2xl:items-center 2xl:justify-between">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="period in periods"
                                :key="period.key"
                                type="button"
                                class="rounded-xl px-3 py-2 text-xs font-black transition"
                                :class="filters.period === period.key
                                    ? 'bg-blue-600 text-white shadow-md shadow-blue-600/20'
                                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:hover:bg-slate-600'"
                                @click="setPeriod(period.key)"
                            >
                                {{ period.label }}
                            </button>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="filters.start_date"
                                    type="date"
                                    :max="todayInput"
                                    class="w-full rounded-xl border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white sm:w-auto"
                                    @change="applyCustomDates"
                                />

                                <span class="text-xs font-bold text-slate-400">
                                    إلى
                                </span>

                                <input
                                    v-model="filters.end_date"
                                    type="date"
                                    :max="todayInput"
                                    class="w-full rounded-xl border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-slate-600 dark:bg-slate-900 dark:text-white sm:w-auto"
                                    @change="applyCustomDates"
                                />
                            </div>

                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-black text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700"
                                @click="resetToToday"
                            >
                                إعادة لليوم
                            </button>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-950 px-3 py-2 text-xs font-black text-white transition hover:bg-slate-800 disabled:opacity-50 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                                :disabled="refreshing"
                                @click="refreshData"
                            >
                                <svg
                                    class="h-4 w-4"
                                    :class="{ 'animate-spin': refreshing }"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M20 11a8.1 8.1 0 00-15.5-2M4 4v5h5m-5 4a8.1 8.1 0 0015.5 2M20 20v-5h-5"
                                    />
                                </svg>

                                تحديث الآن
                            </button>

                            <button
                                type="button"
                                class="rounded-xl border px-3 py-2 text-xs font-black transition"
                                :class="autoRefresh
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/50 dark:bg-emerald-950/25 dark:text-emerald-300'
                                    : 'border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-400'"
                                @click="autoRefresh = !autoRefresh"
                            >
                                تحديث تلقائي
                                {{ autoRefresh ? 'ON' : 'OFF' }}
                            </button>
                        </div>
                    </div>

                    <p
                        v-if="customDateError"
                        class="mt-3 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300"
                    >
                        {{ customDateError }}
                    </p>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3 text-xs dark:border-slate-700">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-black text-slate-700 dark:text-slate-200">
                                {{ periodLabel }}
                            </span>
                            <span class="text-slate-300 dark:text-slate-600">•</span>
                            <span dir="ltr" class="text-slate-500 dark:text-slate-400">
                                {{ dateRange }}
                            </span>
                        </div>

                        <span class="text-slate-400">
                            يتم التحديث تلقائياً كل 60 ثانية أثناء بقاء الصفحة مفتوحة
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Primary KPI cards -->
            <section class="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
                <article
                    v-for="card in mainCards"
                    :key="card.title"
                    class="relative overflow-hidden rounded-[26px] bg-gradient-to-l p-5 text-white shadow-lg"
                    :class="card.shell"
                >
                    <div class="absolute -left-8 -top-8 h-28 w-28 rounded-full bg-white/10 blur-2xl"></div>

                    <div class="relative">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-black text-white/70">
                                    {{ card.title }}
                                </p>
                                <p dir="ltr" class="mt-3 text-right text-2xl font-black tracking-tight">
                                    {{ card.value }}
                                </p>
                            </div>

                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                                :class="card.chip"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        :d="iconPath(card.icon)"
                                    />
                                </svg>
                            </div>
                        </div>

                        <p class="mt-4 text-xs leading-5 text-white/70">
                            {{ card.subtitle }}
                        </p>
                    </div>
                </article>
            </section>

            <!-- Comparison strip -->
            <section class="grid gap-3 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                        تغير صافي المبيعات
                    </p>
                    <div class="mt-2 flex items-end justify-between gap-4">
                        <strong
                            dir="ltr"
                            class="text-2xl"
                            :class="Number(metrics.sales_change || 0) >= 0
                                ? 'text-emerald-600'
                                : 'text-rose-600'"
                        >
                            {{ formatPercent(metrics.sales_change) }}
                        </strong>
                        <span class="text-[11px] text-slate-400">
                            مقابل فترة مساوية سابقة
                        </span>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                        هامش الربح التشغيلي
                    </p>
                    <div class="mt-2 flex items-end justify-between gap-4">
                        <strong dir="ltr" class="text-2xl text-blue-600 dark:text-blue-300">
                            {{ formatPercent(metrics.profit_margin) }}
                        </strong>
                        <span class="text-[11px] text-slate-400">
                            الربح / الإيرادات
                        </span>
                    </div>
                </article>

                <article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-xs font-bold text-slate-500 dark:text-slate-400">
                        صافي التدفق المالي خلال الفترة
                    </p>
                    <div class="mt-2 flex items-end justify-between gap-4">
                        <strong
                            dir="ltr"
                            class="text-2xl"
                            :class="Number(financialSummary.net_flow || 0) >= 0
                                ? 'text-emerald-600'
                                : 'text-rose-600'"
                        >
                            {{ formatCurrency(financialSummary.net_flow) }}
                        </strong>
                        <span class="text-[11px] text-slate-400">
                            الوارد - الصادر
                        </span>
                    </div>
                </article>
            </section>

            <!-- Secondary KPI cards -->
            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 2xl:grid-cols-8">
                <Link
                    v-for="card in secondaryCards"
                    :key="card.title"
                    :href="card.href"
                    class="group rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-blue-900/60"
                >
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl"
                        :class="card.tone"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                :d="iconPath(card.icon)"
                            />
                        </svg>
                    </div>

                    <p class="mt-3 text-[11px] font-bold leading-5 text-slate-500 dark:text-slate-400">
                        {{ card.title }}
                    </p>

                    <p dir="ltr" class="mt-1 text-right text-sm font-black text-slate-950 dark:text-white">
                        {{ card.value }}
                    </p>

                    <p
                        v-if="card.subtitle"
                        class="mt-2 line-clamp-2 text-[10px] leading-4 text-slate-400 transition group-hover:text-slate-600 dark:group-hover:text-slate-300"
                    >
                        {{ card.subtitle }}
                    </p>
                </Link>
            </section>

            <!-- Chart -->
            <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-base font-black text-slate-950 dark:text-white">
                            الإيرادات والأرباح والمصروفات
                        </h2>
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                            حركة الأداء خلال {{ periodLabel }}
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-4 text-xs font-bold">
                        <span class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span>
                            الإيرادات
                        </span>
                        <span class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                            الأرباح
                        </span>
                        <span class="inline-flex items-center gap-2 text-slate-600 dark:text-slate-300">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                            المصروفات
                        </span>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <div
                        v-if="chartLabels.length"
                        class="overflow-x-auto"
                    >
                        <svg
                            class="h-[300px] min-w-[760px] w-full"
                            :viewBox="`0 0 ${chartWidth} ${chartHeight + 30}`"
                            preserveAspectRatio="none"
                        >
                            <g>
                                <line
                                    v-for="grid in chartGrid"
                                    :key="grid.y"
                                    :x1="chartPaddingX"
                                    :x2="chartWidth - chartPaddingX"
                                    :y1="grid.y"
                                    :y2="grid.y"
                                    stroke="currentColor"
                                    class="text-slate-100 dark:text-slate-700"
                                    stroke-width="1"
                                />

                                <text
                                    v-for="grid in chartGrid"
                                    :key="`label-${grid.y}`"
                                    x="4"
                                    :y="grid.y + 4"
                                    class="fill-slate-400 text-[9px]"
                                >
                                    {{ Number(grid.value).toLocaleString('en-US', { maximumFractionDigits: 0 }) }}
                                </text>
                            </g>

                            <line
                                :x1="chartPaddingX"
                                :x2="chartWidth - chartPaddingX"
                                :y1="baselineY"
                                :y2="baselineY"
                                stroke="currentColor"
                                class="text-slate-300 dark:text-slate-500"
                                stroke-width="1.2"
                            />

                            <polyline
                                :points="chartPoints(chartRevenue)"
                                fill="none"
                                stroke="#6366F1"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                vector-effect="non-scaling-stroke"
                            />

                            <polyline
                                :points="chartPoints(chartProfit)"
                                fill="none"
                                stroke="#10B981"
                                stroke-width="3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                vector-effect="non-scaling-stroke"
                            />

                            <polyline
                                :points="chartPoints(chartExpenses)"
                                fill="none"
                                stroke="#F43F5E"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                vector-effect="non-scaling-stroke"
                            />

                            <template
                                v-for="(label, index) in chartLabels"
                                :key="`${label}-${index}`"
                            >
                                <text
                                    v-if="shouldShowChartLabel(index, chartLabels.length)"
                                    :x="chartX(index, chartLabels.length)"
                                    :y="chartHeight + 18"
                                    text-anchor="middle"
                                    class="fill-slate-400 text-[9px]"
                                >
                                    {{ label }}
                                </text>
                            </template>
                        </svg>
                    </div>

                    <div
                        v-else
                        class="py-16 text-center text-sm text-slate-500"
                    >
                        لا توجد حركة مالية خلال الفترة المحددة.
                    </div>
                </div>
            </section>

            <!-- Analysis row -->
            <section class="grid gap-5 xl:grid-cols-3">
                <!-- Categories -->
                <article class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">
                                المبيعات حسب الفئة
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                بعد احتساب المرتجعات
                            </p>
                        </div>

                        <Link
                            :href="route('reports.sales')"
                            class="text-xs font-black text-blue-600 dark:text-blue-300"
                        >
                            التفاصيل
                        </Link>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div
                            v-for="row in categoryRows.slice(0, 6)"
                            :key="row.name"
                        >
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                                <span class="font-bold text-slate-700 dark:text-slate-200">
                                    {{ row.name }}
                                </span>

                                <strong dir="ltr" class="text-slate-950 dark:text-white">
                                    {{ formatCurrency(row.total) }}
                                </strong>
                            </div>

                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full bg-blue-500"
                                    :style="{
                                        width: `${Math.min(100, (Math.abs(Number(row.total || 0)) / categoryMax) * 100)}%`,
                                    }"
                                ></div>
                            </div>
                        </div>

                        <p
                            v-if="!categoryRows.length"
                            class="py-6 text-center text-xs text-slate-500"
                        >
                            لا توجد مبيعات حسب الفئة.
                        </p>
                    </div>
                </article>

                <!-- Payment methods -->
                <article class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div>
                        <h3 class="font-black text-slate-950 dark:text-white">
                            التحصيل حسب طريقة الدفع
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            الدفعات المقبوضة خلال الفترة
                        </p>
                    </div>

                    <div class="mt-5 space-y-4">
                        <div
                            v-for="row in paymentRows"
                            :key="enumValue(row.payment_method)"
                        >
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-xs">
                                <div>
                                    <strong class="text-slate-700 dark:text-slate-200">
                                        {{ paymentMethodLabel(row.payment_method) }}
                                    </strong>
                                    <span class="mr-2 text-slate-400">
                                        {{ formatNumber(row.payment_count) }} دفعة
                                    </span>
                                </div>

                                <strong dir="ltr" class="text-slate-950 dark:text-white">
                                    {{ formatCurrency(row.total) }}
                                </strong>
                            </div>

                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full bg-emerald-500"
                                    :style="{
                                        width: `${Math.min(100, (Math.abs(Number(row.total || 0)) / paymentMax) * 100)}%`,
                                    }"
                                ></div>
                            </div>
                        </div>

                        <p
                            v-if="!paymentRows.length"
                            class="py-6 text-center text-xs text-slate-500"
                        >
                            لا توجد دفعات خلال الفترة.
                        </p>
                    </div>
                </article>

                <!-- Revenue sources -->
                <article class="rounded-[26px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div>
                        <h3 class="font-black text-slate-950 dark:text-white">
                            مصادر الإيرادات
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            مبيعات المنتجات مقابل الصيانة
                        </p>
                    </div>

                    <div class="mt-6 flex h-3 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                        <div
                            v-for="source in revenueSources"
                            :key="source.label"
                            class="h-full"
                            :class="source.tone"
                            :style="{
                                width: `${source.percent}%`,
                            }"
                        ></div>
                    </div>

                    <div class="mt-5 space-y-3">
                        <div
                            v-for="source in revenueSources"
                            :key="source.label"
                            class="flex items-center justify-between rounded-xl bg-slate-50 p-3 dark:bg-slate-900/60"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="h-2.5 w-2.5 rounded-full"
                                    :class="source.tone"
                                ></span>
                                <span class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                    {{ source.label }}
                                </span>
                            </div>

                            <div class="text-left">
                                <strong dir="ltr" class="block text-sm text-slate-950 dark:text-white">
                                    {{ formatCurrency(source.value) }}
                                </strong>
                                <span dir="ltr" class="text-[11px] text-slate-400">
                                    {{ formatPercent(source.percent) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Financial + repairs -->
            <section class="grid gap-5 xl:grid-cols-[1.2fr_0.8fr]">
                <!-- Financial center -->
                <article class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">
                                المركز المالي
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                أرصدة حالية + حركة الفترة المحددة
                            </p>
                        </div>

                        <Link
                            :href="route('finance.index')"
                            class="text-xs font-black text-blue-600 dark:text-blue-300"
                        >
                            فتح المركز المالي
                        </Link>
                    </div>

                    <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-2xl bg-slate-950 p-4 text-white dark:bg-slate-900">
                            <p class="text-xs text-slate-300">إجمالي الأرصدة</p>
                            <strong dir="ltr" class="mt-2 block text-lg">
                                {{ formatCurrency(financialSummary.total_balance) }}
                            </strong>
                        </div>

                        <div class="rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-950/25">
                            <p class="text-xs text-emerald-700 dark:text-emerald-300">الوارد خلال الفترة</p>
                            <strong dir="ltr" class="mt-2 block text-lg text-emerald-700 dark:text-emerald-300">
                                {{ formatCurrency(financialSummary.period_inflows) }}
                            </strong>
                        </div>

                        <div class="rounded-2xl bg-rose-50 p-4 dark:bg-rose-950/25">
                            <p class="text-xs text-rose-700 dark:text-rose-300">الصادر خلال الفترة</p>
                            <strong dir="ltr" class="mt-2 block text-lg text-rose-700 dark:text-rose-300">
                                {{ formatCurrency(financialSummary.period_outflows) }}
                            </strong>
                        </div>

                        <div class="rounded-2xl bg-blue-50 p-4 dark:bg-blue-950/25">
                            <p class="text-xs text-blue-700 dark:text-blue-300">رصيد الكاش الحالي</p>
                            <strong dir="ltr" class="mt-2 block text-lg text-blue-700 dark:text-blue-300">
                                {{ formatCurrency(financialSummary.cash_balance) }}
                            </strong>
                        </div>
                    </div>

                    <div class="grid gap-4 border-t border-slate-100 p-5 dark:border-slate-700 lg:grid-cols-2">
                        <div>
                            <h4 class="text-xs font-black text-slate-500 dark:text-slate-400">
                                الحسابات المالية
                            </h4>

                            <div class="mt-3 space-y-2">
                                <Link
                                    v-for="account in financialAccounts.slice(0, 5)"
                                    :key="account.id"
                                    :href="route('finance.show-account', account.id)"
                                    class="flex items-center justify-between rounded-xl bg-slate-50 p-3 transition hover:bg-blue-50 dark:bg-slate-900/60 dark:hover:bg-blue-950/30"
                                >
                                    <div>
                                        <strong class="block text-sm text-slate-800 dark:text-slate-100">
                                            {{ account.name }}
                                        </strong>
                                        <span class="text-[11px] text-slate-400">
                                            {{ enumValue(account.type) }}
                                        </span>
                                    </div>

                                    <strong dir="ltr" class="text-sm text-slate-950 dark:text-white">
                                        {{ formatCurrency(account.current_balance) }}
                                    </strong>
                                </Link>
                            </div>
                        </div>

                        <div>
                            <h4 class="text-xs font-black text-slate-500 dark:text-slate-400">
                                أحدث الحركات المالية
                            </h4>

                            <div class="mt-3 space-y-2">
                                <div
                                    v-for="transaction in recentTransactions.slice(0, 5)"
                                    :key="transaction.id"
                                    class="flex items-center justify-between rounded-xl border border-slate-100 p-3 dark:border-slate-700"
                                >
                                    <div class="min-w-0">
                                        <strong class="block truncate text-sm text-slate-800 dark:text-slate-100">
                                            {{ transaction.account?.name || 'حساب مالي' }}
                                        </strong>
                                        <span class="text-[11px] text-slate-400">
                                            {{ formatDateTime(transaction.transaction_date) }}
                                        </span>
                                    </div>

                                    <div class="text-left">
                                        <strong
                                            dir="ltr"
                                            class="block text-sm"
                                            :class="enumValue(transaction.direction) === 'inflow'
                                                ? 'text-emerald-600'
                                                : 'text-rose-600'"
                                        >
                                            {{ enumValue(transaction.direction) === 'inflow' ? '+' : '-' }}
                                            {{ formatCurrency(transaction.amount) }}
                                        </strong>
                                        <span class="text-[10px] text-slate-400">
                                            {{ directionLabel(transaction.direction) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Repairs current snapshot -->
                <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">
                                حالة الصيانة الآن
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                هذا القسم لحظي ولا يتأثر بفلتر الفترة
                            </p>
                        </div>

                        <Link
                            :href="route('repairs.index')"
                            class="text-xs font-black text-violet-600 dark:text-violet-300"
                        >
                            كل الطلبات
                        </Link>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <Link
                            v-for="status in repairStatusCards"
                            :key="status.key"
                            :href="route('repairs.index', { status: status.key })"
                            class="rounded-2xl p-4 transition hover:-translate-y-0.5"
                            :class="status.tone"
                        >
                            <p class="text-xs font-bold">
                                {{ status.label }}
                            </p>
                            <strong dir="ltr" class="mt-2 block text-2xl">
                                {{ formatNumber(status.value) }}
                            </strong>
                        </Link>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2">
                        <div class="rounded-xl bg-rose-50 p-3 text-center dark:bg-rose-950/25">
                            <p class="text-[10px] font-bold text-rose-700 dark:text-rose-300">
                                متأخر
                            </p>
                            <strong dir="ltr" class="mt-1 block text-lg text-rose-700 dark:text-rose-300">
                                {{ formatNumber(repairStats.overdue) }}
                            </strong>
                        </div>

                        <div class="rounded-xl bg-amber-50 p-3 text-center dark:bg-amber-950/25">
                            <p class="text-[10px] font-bold text-amber-700 dark:text-amber-300">
                                انتظار موافقة
                            </p>
                            <strong dir="ltr" class="mt-1 block text-lg text-amber-700 dark:text-amber-300">
                                {{ formatNumber(repairStats.waiting_approval) }}
                            </strong>
                        </div>

                        <div class="rounded-xl bg-blue-50 p-3 text-center dark:bg-blue-950/25">
                            <p class="text-[10px] font-bold text-blue-700 dark:text-blue-300">
                                انتظار قطعة
                            </p>
                            <strong dir="ltr" class="mt-1 block text-lg text-blue-700 dark:text-blue-300">
                                {{ formatNumber(repairStats.waiting_parts) }}
                            </strong>
                        </div>
                    </div>
                </article>
            </section>

            <!-- Products + stock -->
            <section class="grid gap-5 xl:grid-cols-[1.1fr_0.9fr]">
                <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">
                                المنتجات الأكثر مبيعاً
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                صافي الكمية بعد المرتجعات
                            </p>
                        </div>

                        <Link
                            :href="route('reports.sales')"
                            class="text-xs font-black text-blue-600 dark:text-blue-300"
                        >
                            تقرير المبيعات
                        </Link>
                    </div>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-[650px] w-full text-sm">
                            <thead>
                                <tr class="text-xs text-slate-400">
                                    <th class="pb-3 text-right">المنتج</th>
                                    <th class="pb-3 text-right">الكمية</th>
                                    <th class="pb-3 text-right">الإيراد</th>
                                    <th class="pb-3 text-right">الربح</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                <tr
                                    v-for="product in topProducts"
                                    :key="product.id"
                                >
                                    <td class="py-3">
                                        <strong class="block text-slate-800 dark:text-slate-100">
                                            {{ product.name }}
                                        </strong>
                                        <span dir="ltr" class="block text-right text-[11px] text-slate-400">
                                            {{ product.code || '—' }}
                                        </span>
                                    </td>
                                    <td dir="ltr" class="py-3 text-right font-black">
                                        {{ formatNumber(product.total_quantity) }}
                                    </td>
                                    <td dir="ltr" class="py-3 text-right font-black">
                                        {{ formatCurrency(product.total_revenue) }}
                                    </td>
                                    <td dir="ltr" class="py-3 text-right font-black text-emerald-600">
                                        {{ formatCurrency(product.total_profit) }}
                                    </td>
                                </tr>

                                <tr v-if="!topProducts.length">
                                    <td colspan="4" class="py-10 text-center text-xs text-slate-500">
                                        لا توجد منتجات مباعة خلال الفترة.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">
                                صحة المخزون
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                حالة المخزون الحالية
                            </p>
                        </div>

                        <Link
                            :href="route('inventory.index')"
                            class="text-xs font-black text-cyan-600 dark:text-cyan-300"
                        >
                            المخزون
                        </Link>
                    </div>

                    <div class="mt-5 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-amber-50 p-4 dark:bg-amber-950/25">
                            <p class="text-xs font-bold text-amber-700 dark:text-amber-300">
                                منخفض المخزون
                            </p>
                            <strong dir="ltr" class="mt-2 block text-2xl text-amber-700 dark:text-amber-300">
                                {{ formatNumber(lowStockProducts.length) }}
                            </strong>
                        </div>

                        <div class="rounded-2xl bg-rose-50 p-4 dark:bg-rose-950/25">
                            <p class="text-xs font-bold text-rose-700 dark:text-rose-300">
                                نافد
                            </p>
                            <strong dir="ltr" class="mt-2 block text-2xl text-rose-700 dark:text-rose-300">
                                {{ formatNumber(outOfStockProducts.length) }}
                            </strong>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        <Link
                            v-for="product in outOfStockProducts.slice(0, 3)"
                            :key="`out-${product.id}`"
                            :href="route('inventory.index', { product_id: product.id })"
                            class="flex items-center justify-between rounded-xl bg-rose-50 p-3 transition hover:bg-rose-100 dark:bg-rose-950/20 dark:hover:bg-rose-950/35"
                        >
                            <div>
                                <strong class="block text-xs text-slate-800 dark:text-slate-100">
                                    {{ product.name }}
                                </strong>
                                <span class="text-[10px] text-slate-400">
                                    {{ product.category?.name || 'بدون فئة' }}
                                </span>
                            </div>

                            <span class="rounded-lg bg-white px-2 py-1 text-[10px] font-black text-rose-700 shadow-sm dark:bg-slate-800">
                                نافد
                            </span>
                        </Link>

                        <Link
                            v-for="product in lowStockProducts.slice(0, Math.max(0, 5 - Math.min(3, outOfStockProducts.length)))"
                            :key="`low-${product.id}`"
                            :href="route('inventory.index', { product_id: product.id })"
                            class="flex items-center justify-between rounded-xl bg-slate-50 p-3 transition hover:bg-amber-50 dark:bg-slate-900/60 dark:hover:bg-amber-950/20"
                        >
                            <div>
                                <strong class="block text-xs text-slate-800 dark:text-slate-100">
                                    {{ product.name }}
                                </strong>
                                <span class="text-[10px] text-slate-400">
                                    {{ product.category?.name || 'بدون فئة' }}
                                </span>
                            </div>

                            <span dir="ltr" class="rounded-lg bg-white px-2 py-1 text-xs font-black text-amber-700 shadow-sm dark:bg-slate-800">
                                {{ formatNumber(product.total_stock) }}
                            </span>
                        </Link>

                        <p
                            v-if="!lowStockProducts.length && !outOfStockProducts.length"
                            class="rounded-xl bg-emerald-50 p-4 text-center text-xs font-bold text-emerald-700 dark:bg-emerald-950/25 dark:text-emerald-300"
                        >
                            المخزون بحالة جيدة حالياً.
                        </p>
                    </div>
                </article>
            </section>

            <!-- Alerts -->
            <section
                v-if="alerts.length"
                class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-black text-slate-950 dark:text-white">
                            تحتاج انتباهك
                        </h3>
                        <p class="mt-1 text-xs text-slate-500">
                            تنبيهات تشغيلية حالية
                        </p>
                    </div>

                    <span class="rounded-full bg-rose-50 px-3 py-1 text-xs font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">
                        {{ formatNumber(alerts.length) }}
                    </span>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    <Link
                        v-for="alert in alerts"
                        :key="`${alert.title}-${alert.description}`"
                        :href="alert.link || '#'"
                        class="rounded-2xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm"
                        :class="alertTone(alert.type)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <strong class="text-sm">
                                    {{ alert.title }}
                                </strong>
                                <p class="mt-1 text-xs leading-5 opacity-80">
                                    {{ alert.description }}
                                </p>
                            </div>

                            <span class="text-lg">←</span>
                        </div>
                    </Link>
                </div>
            </section>

            <!-- Recent operations -->
            <section class="grid gap-5 xl:grid-cols-2">
                <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">
                                أحدث فواتير البيع
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                آخر ما حدث في نقطة البيع
                            </p>
                        </div>

                        <Link
                            :href="route('sales.index')"
                            class="text-xs font-black text-blue-600 dark:text-blue-300"
                        >
                            كل الفواتير
                        </Link>
                    </div>

                    <div class="mt-4 space-y-2">
                        <Link
                            v-for="sale in asArray(recentActivities.sales).slice(0, 5)"
                            :key="sale.id"
                            :href="route('sales.show', sale.id)"
                            class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 p-3 transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40"
                        >
                            <div class="min-w-0">
                                <strong dir="ltr" class="block truncate text-right text-sm text-slate-800 dark:text-slate-100">
                                    {{ sale.invoice_number }}
                                </strong>
                                <span class="text-[11px] text-slate-400">
                                    {{ sale.customer?.name || sale.customer_name || 'عميل نقدي' }}
                                </span>
                            </div>

                            <div class="shrink-0 text-left">
                                <strong dir="ltr" class="block text-sm text-slate-950 dark:text-white">
                                    {{ formatCurrency(sale.total_amount) }}
                                </strong>
                                <span class="text-[10px] text-slate-400">
                                    {{ formatDateTime(sale.created_at) }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">
                                أحدث فواتير الشراء
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                آخر عمليات التوريد
                            </p>
                        </div>

                        <Link
                            :href="route('purchases.index')"
                            class="text-xs font-black text-amber-600 dark:text-amber-300"
                        >
                            كل المشتريات
                        </Link>
                    </div>

                    <div class="mt-4 space-y-2">
                        <Link
                            v-for="purchase in asArray(recentActivities.purchases).slice(0, 5)"
                            :key="purchase.id"
                            :href="route('purchases.show', purchase.id)"
                            class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 p-3 transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40"
                        >
                            <div class="min-w-0">
                                <strong dir="ltr" class="block truncate text-right text-sm text-slate-800 dark:text-slate-100">
                                    {{ purchase.invoice_number }}
                                </strong>
                                <span class="text-[11px] text-slate-400">
                                    {{ purchase.supplier?.name || 'مورد' }}
                                </span>
                            </div>

                            <div class="shrink-0 text-left">
                                <strong dir="ltr" class="block text-sm text-slate-950 dark:text-white">
                                    {{ formatCurrency(purchase.total_amount) }}
                                </strong>
                                <span class="text-[10px] text-slate-400">
                                    {{ formatDateTime(purchase.created_at) }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">
                                أحدث طلبات الصيانة
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                آخر الأجهزة المستلمة
                            </p>
                        </div>

                        <Link
                            :href="route('repairs.index')"
                            class="text-xs font-black text-violet-600 dark:text-violet-300"
                        >
                            كل الصيانة
                        </Link>
                    </div>

                    <div class="mt-4 space-y-2">
                        <Link
                            v-for="repair in asArray(recentActivities.repairs).slice(0, 5)"
                            :key="repair.id"
                            :href="route('repairs.show', repair.id)"
                            class="flex items-center justify-between gap-4 rounded-xl border border-slate-100 p-3 transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40"
                        >
                            <div class="min-w-0">
                                <strong dir="ltr" class="block truncate text-right text-sm text-slate-800 dark:text-slate-100">
                                    {{ repair.order_number }}
                                </strong>
                                <span class="text-[11px] text-slate-400">
                                    {{ repair.customer?.name || repair.customer_name || '—' }}
                                </span>
                            </div>

                            <div class="shrink-0 text-left">
                                <span class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-black text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                                    {{ statusLabel(repair.status) }}
                                </span>
                                <span class="mt-1 block text-[10px] text-slate-400">
                                    {{ formatDateTime(repair.created_at) }}
                                </span>
                            </div>
                        </Link>
                    </div>
                </article>

                <article class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-black text-slate-950 dark:text-white">
                                نظرة على النظام
                            </h3>
                            <p class="mt-1 text-xs text-slate-500">
                                أعداد أساسية حالية
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                            <p class="text-xs text-slate-500">المنتجات</p>
                            <strong dir="ltr" class="mt-2 block text-2xl text-slate-950 dark:text-white">
                                {{ formatNumber(entityCounts.products) }}
                            </strong>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                            <p class="text-xs text-slate-500">العملاء</p>
                            <strong dir="ltr" class="mt-2 block text-2xl text-slate-950 dark:text-white">
                                {{ formatNumber(entityCounts.customers) }}
                            </strong>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                            <p class="text-xs text-slate-500">الموردون</p>
                            <strong dir="ltr" class="mt-2 block text-2xl text-slate-950 dark:text-white">
                                {{ formatNumber(entityCounts.suppliers) }}
                            </strong>
                        </div>

                        <div class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900/60">
                            <p class="text-xs text-slate-500">الحسابات المالية</p>
                            <strong dir="ltr" class="mt-2 block text-2xl text-slate-950 dark:text-white">
                                {{ formatNumber(entityCounts.financial_accounts) }}
                            </strong>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-3">
                        <Link
                            :href="route('payments.customer-receivables')"
                            class="rounded-xl border border-slate-200 p-3 transition hover:border-blue-200 hover:bg-blue-50/50 dark:border-slate-700 dark:hover:border-blue-900/50 dark:hover:bg-blue-950/20"
                        >
                            <p class="text-[10px] text-slate-400">ديون المبيعات</p>
                            <strong dir="ltr" class="mt-1 block text-sm text-slate-800 dark:text-slate-100">
                                {{ formatCurrency(receivables.sales_debts) }}
                            </strong>
                        </Link>

                        <Link
                            :href="route('payments.customer-receivables')"
                            class="rounded-xl border border-slate-200 p-3 transition hover:border-violet-200 hover:bg-violet-50/50 dark:border-slate-700 dark:hover:border-violet-900/50 dark:hover:bg-violet-950/20"
                        >
                            <p class="text-[10px] text-slate-400">ديون الصيانة</p>
                            <strong dir="ltr" class="mt-1 block text-sm text-slate-800 dark:text-slate-100">
                                {{ formatCurrency(receivables.repair_debts) }}
                            </strong>
                        </Link>
                    </div>

                    <div
                        v-if="Number(receivables.overdue_customer_documents || 0) > 0 || Number(receivables.overdue_supplier_documents || 0) > 0"
                        class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-[10px] leading-5 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200"
                    >
                        متأخر الآن:
                        <strong>{{ formatNumber(receivables.overdue_customer_documents) }}</strong>
                        مستحق عميل
                        ·
                        <strong>{{ formatNumber(receivables.overdue_supplier_documents) }}</strong>
                        مستحق مورد.
                    </div>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
