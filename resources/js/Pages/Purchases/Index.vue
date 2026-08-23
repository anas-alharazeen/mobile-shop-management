<script setup>
import {
    computed,
    reactive,
    ref,
    watch,
} from 'vue';

import {
    Head,
    Link,
    router,
    useForm,
} from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    invoices: {
        type: Object,
        required: true,
    },

    stats: {
        type: Object,
        default: () => ({}),
    },

    filters: {
        type: Object,
        default: () => ({}),
    },

    suppliers: {
        type: Array,
        default: () => [],
    },

    statuses: {
        type: Object,
        default: () => ({}),
    },

    paymentStatuses: {
        type: Object,
        default: () => ({}),
    },

    paymentMethods: {
        type: Object,
        default: () => ({}),
    },

    financialAccounts: {
        type: Array,
        default: () => [],
    },
});

const periodOptions = [
    ['today', 'اليوم'],
    ['yesterday', 'أمس'],
    ['last_7_days', 'آخر 7 أيام'],
    ['this_week', 'هذا الأسبوع'],
    ['this_month', 'هذا الشهر'],
    ['last_month', 'الشهر الماضي'],
    ['this_year', 'هذه السنة'],
];

const filters = reactive({
    search:
        props.filters?.search || '',

    period:
        props.filters?.period || '',

    status:
        props.filters?.status || '',

    payment_status:
        props.filters?.payment_status || '',

    supplier_id:
        props.filters?.supplier_id || '',

    start_date:
        props.filters?.start_date || '',

    end_date:
        props.filters?.end_date || '',
});

const approveModal = ref({
    show: false,
    invoice: null,
});

const paymentModal = ref({
    show: false,
    invoice: null,
});

const cancelModal = ref({
    show: false,
    invoice: null,
});

const approveForm = useForm({});

const paymentForm = useForm({
    amount: '',
    payment_method: 'cash',
    financial_account_id: '',
    bank_or_app_name: '',
    transaction_reference: '',
    paid_at: '',
    notes: '',
});

const cancelForm = useForm({
    reason: '',
});

const localDate = () => {
    const now = new Date();
    const offset =
        now.getTimezoneOffset();

    return new Date(
        now.getTime()
        - offset * 60 * 1000
    )
        .toISOString()
        .slice(0, 10);
};

const money = (value) => (
    `${Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`
);

const number = (value) => (
    Number(value || 0)
        .toLocaleString('en-US')
);

const date = (value) => {
    if (!value) {
        return '—';
    }

    const parsed =
        new Date(value);

    if (
        Number.isNaN(
            parsed.getTime()
        )
    ) {
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

const statusLabel = (value) => (
    props.statuses[
        enumValue(value)
    ]
    || enumValue(value)
    || '—'
);

const paymentStatusLabel = (value) => (
    props.paymentStatuses[
        enumValue(value)
    ]
    || enumValue(value)
    || '—'
);

const statusClass = (value) => ({
    draft:
        'bg-slate-100 text-slate-700 ring-slate-200 dark:bg-slate-700 dark:text-slate-200 dark:ring-slate-600',

    approved:
        'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50',

    cancelled:
        'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50',
}[enumValue(value)] || 'bg-slate-100 text-slate-700');

const paymentStatusClass = (value) => ({
    unpaid:
        'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50',

    partially_paid:
        'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/50',

    paid:
        'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50',
}[enumValue(value)] || 'bg-slate-100 text-slate-700');

const statsCards = computed(() => [
    {
        label: 'مشتريات اليوم',
        value:
            money(
                props.stats.today_amount
            ),
        hint:
            `${number(props.stats.today_count)} فاتورة معتمدة`,
        tone:
            'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300',
    },
    {
        label: 'مشتريات الشهر',
        value:
            money(
                props.stats.month_amount
            ),
        hint:
            `${number(props.stats.month_count)} فاتورة معتمدة`,
        tone:
            'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300',
    },
    {
        label: 'المستحق للموردين',
        value:
            money(
                props.stats.total_due
            ),
        hint:
            'على الفواتير المعتمدة',
        tone:
            'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    },
    {
        label: 'متأخر السداد',
        value:
            money(
                props.stats.overdue_due
            ),
        hint:
            `${number(props.stats.overdue_count)} فاتورة متأخرة`,
        tone:
            'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
    },
    {
        label: 'مسودات',
        value:
            number(
                props.stats.draft_count
            ),
        hint:
            'بانتظار المراجعة والاعتماد',
        tone:
            'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    },
    {
        label: 'فواتير معتمدة',
        value:
            number(
                props.stats.approved_count
            ),
        hint:
            'تم تحديث المخزون لها',
        tone:
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
    },
]);

const expectedAccountType = computed(() => ({
    cash:
        'cash',

    bank_transfer:
        'bank',

    banking_app:
        'banking_app',
}[paymentForm.payment_method] || null));

const compatibleAccounts = computed(() => (
    props.financialAccounts.filter(
        (account) =>
            account.is_active !== false
            && (
                !expectedAccountType.value
                || enumValue(account.type)
                    === expectedAccountType.value
            )
    )
));

const selectedAccount = computed(() => (
    props.financialAccounts.find(
        (account) =>
            Number(account.id)
            === Number(
                paymentForm
                    .financial_account_id
            )
    ) || null
));

const roundMoney = (value) => (
    Math.round(
        (
            Number(value || 0)
            + Number.EPSILON
        ) * 100
    ) / 100
);

const paymentAmount = computed(() => (
    roundMoney(
        paymentForm.amount
    )
));

const currentInvoiceRemaining = computed(() => (
    roundMoney(
        paymentModal.value
            .invoice
            ?.remaining_amount
        || 0
    )
));

const currentInvoicePaid = computed(() => (
    roundMoney(
        paymentModal.value
            .invoice
            ?.paid_amount
        || 0
    )
));

const currentInvoiceTotal = computed(() => (
    roundMoney(
        paymentModal.value
            .invoice
            ?.total_amount
        || 0
    )
));

const paidAfterPayment = computed(() => (
    roundMoney(
        currentInvoicePaid.value
        + paymentAmount.value
    )
));

const remainingAfterPayment = computed(() => (
    roundMoney(
        Math.max(
            0,
            currentInvoiceRemaining.value
            - paymentAmount.value
        )
    )
));

const paymentPercentageBefore = computed(() => {
    if (
        currentInvoiceTotal.value
        <= 0
    ) {
        return 0;
    }

    return Math.min(
        100,
        Math.max(
            0,
            (
                currentInvoicePaid.value
                / currentInvoiceTotal.value
            ) * 100
        )
    );
});

const paymentPercentageAfter = computed(() => {
    if (
        currentInvoiceTotal.value
        <= 0
    ) {
        return 0;
    }

    return Math.min(
        100,
        Math.max(
            0,
            (
                paidAfterPayment.value
                / currentInvoiceTotal.value
            ) * 100
        )
    );
});

const paymentStatusAfter = computed(() => {
    if (
        remainingAfterPayment.value
        <= 0.00001
    ) {
        return {
            label: 'مدفوعة بالكامل',
            class:
                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
        };
    }

    return {
        label: 'مدفوعة جزئياً',
        class:
            'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    };
});

const selectedPaymentMethodLabel = computed(() => (
    props.paymentMethods[
        paymentForm.payment_method
    ]
    || paymentForm.payment_method
    || '—'
));

const accountBalanceAfterPayment = computed(() => {
    if (
        !selectedAccount.value
    ) {
        return null;
    }

    return roundMoney(
        Number(
            selectedAccount.value
                .current_balance
            || 0
        )
        - paymentAmount.value
    );
});

const paymentDateError = computed(() => {
    const invoice =
        paymentModal.value.invoice;

    if (
        !invoice
        || !paymentForm.paid_at
    ) {
        return '';
    }

    const invoiceDate =
        String(
            invoice.purchase_date
            || ''
        ).slice(
            0,
            10
        );

    if (
        invoiceDate
        && paymentForm.paid_at
            < invoiceDate
    ) {
        return 'تاريخ الدفع لا يمكن أن يسبق تاريخ فاتورة الشراء.';
    }

    if (
        paymentForm.paid_at
        > localDate()
    ) {
        return 'تاريخ الدفع لا يمكن أن يكون في المستقبل.';
    }

    return '';
});

const paymentError = computed(() => {
    const invoice =
        paymentModal.value.invoice;

    if (!invoice) {
        return '';
    }

    if (
        paymentAmount.value
        <= 0
    ) {
        return 'أدخل قيمة دفعة أكبر من صفر.';
    }

    if (
        paymentAmount.value
        > currentInvoiceRemaining.value
    ) {
        return 'قيمة الدفعة أكبر من المبلغ المتبقي على الفاتورة.';
    }

    if (
        !paymentForm
            .payment_method
    ) {
        return 'اختر طريقة الدفع.';
    }

    if (
        !paymentForm
            .financial_account_id
    ) {
        return 'اختر الحساب المالي الذي ستخرج منه الدفعة.';
    }

    if (
        selectedAccount.value
        && paymentAmount.value
            > Number(
                selectedAccount.value
                    .current_balance
                || 0
            )
    ) {
        return `رصيد الحساب غير كافٍ. المتوفر ${money(
            selectedAccount.value.current_balance
        )}.`;
    }

    if (
        paymentDateError.value
    ) {
        return paymentDateError.value;
    }

    return '';
});

const setQuickPayment = (
    type
) => {
    const remaining =
        currentInvoiceRemaining.value;

    if (
        remaining <= 0
    ) {
        paymentForm.amount = '';
        return;
    }

    switch (type) {
        case 'quarter':
            paymentForm.amount =
                roundMoney(
                    remaining * 0.25
                );
            break;

        case 'half':
            paymentForm.amount =
                roundMoney(
                    remaining * 0.50
                );
            break;

        case 'full':
            paymentForm.amount =
                remaining;
            break;
    }
};

const closePaymentModal = () => {
    paymentModal.value = {
        show: false,
        invoice: null,
    };

    paymentForm.reset();
    paymentForm.clearErrors();
};

const hasFilters = computed(() => (
    Boolean(
        filters.search
        || filters.period
        || filters.status
        || filters.payment_status
        || filters.supplier_id
        || filters.start_date
        || filters.end_date
    )
));

const applyFilters = () => {
    router.get(
        route('purchases.index'),
        {
            search:
                filters.search
                || undefined,

            period:
                filters.period
                || undefined,

            status:
                filters.status
                || undefined,

            payment_status:
                filters.payment_status
                || undefined,

            supplier_id:
                filters.supplier_id
                || undefined,

            start_date:
                filters.start_date
                || undefined,

            end_date:
                filters.end_date
                || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const setPeriod = (period) => {
    filters.period =
        period;

    filters.start_date = '';
    filters.end_date = '';

    applyFilters();
};

const applyCustomDate = () => {
    if (
        filters.start_date
        || filters.end_date
    ) {
        filters.period = '';
    }

    applyFilters();
};

const clearFilters = () => {
    Object.assign(
        filters,
        {
            search: '',
            period: '',
            status: '',
            payment_status: '',
            supplier_id: '',
            start_date: '',
            end_date: '',
        }
    );

    applyFilters();
};

const confirmApprove = (invoice) => {
    approveModal.value = {
        show: true,
        invoice,
    };
};

const submitApprove = () => {
    if (
        !approveModal.value.invoice
    ) {
        return;
    }

    approveForm.post(
        route(
            'purchases.approve',
            approveModal.value
                .invoice
                .id
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                approveModal.value.show =
                    false;
            },
        }
    );
};

const openPaymentModal = (
    invoice
) => {
    paymentModal.value = {
        show: true,
        invoice,
    };

    paymentForm.reset();
    paymentForm.clearErrors();

    paymentForm.amount =
        roundMoney(
            invoice.remaining_amount
            || 0
        );

    paymentForm.payment_method =
        'cash';

    paymentForm.paid_at =
        localDate();

    paymentForm.financial_account_id =
        props.financialAccounts.find(
            (account) =>
                account.is_active !== false
                && enumValue(account.type)
                    === 'cash'
                && Number(
                    account.current_balance
                    || 0
                ) >= Number(
                    invoice.remaining_amount
                    || 0
                )
        )?.id
        || props.financialAccounts.find(
            (account) =>
                account.is_active !== false
                && enumValue(account.type)
                    === 'cash'
        )?.id
        || '';

    paymentForm.bank_or_app_name =
        '';

    paymentForm.transaction_reference =
        '';

    paymentForm.notes =
        '';
};

const submitPayment = () => {
    if (
        paymentError.value
        || !paymentModal.value.invoice
    ) {
        return;
    }

    paymentForm.post(
        route(
            'purchases.add-payment',
            paymentModal.value
                .invoice
                .id
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                closePaymentModal();
            },
        }
    );
};

const openCancelModal = (invoice) => {
    cancelModal.value = {
        show: true,
        invoice,
    };

    cancelForm.reset();
};

const submitCancel = () => {
    if (
        !cancelModal.value.invoice
        || !cancelForm.reason.trim()
    ) {
        return;
    }

    cancelForm.post(
        route(
            'purchases.cancel',
            cancelModal.value
                .invoice
                .id
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                cancelModal.value.show =
                    false;
            },
        }
    );
};

const overdue = (invoice) => (
    enumValue(invoice.status)
        === 'approved'
    && Number(
        invoice.remaining_amount
        || 0
    ) > 0
    && invoice.due_date
    && String(invoice.due_date)
        .slice(0, 10)
        < localDate()
);

watch(
    () =>
        paymentForm.payment_method,

    () => {
        const currentAccountStillCompatible =
            compatibleAccounts.value.some(
                (account) =>
                    Number(account.id)
                    === Number(
                        paymentForm
                            .financial_account_id
                    )
            );

        if (!currentAccountStillCompatible) {
            const enoughBalance =
                compatibleAccounts.value.find(
                    (account) =>
                        Number(
                            account.current_balance
                            || 0
                        ) >= paymentAmount.value
                );

            const account =
                enoughBalance
                || compatibleAccounts.value[0];

            paymentForm.financial_account_id =
                account?.id || '';
        }

        if (
            paymentForm.payment_method
            === 'cash'
        ) {
            paymentForm.bank_or_app_name =
                '';
            paymentForm.transaction_reference =
                '';
        } else {
            paymentForm.bank_or_app_name =
                selectedAccount.value?.name
                || '';
        }
    }
);

watch(
    () =>
        paymentForm
            .financial_account_id,

    () => {
        if (
            paymentForm.payment_method
            !== 'cash'
            && selectedAccount.value
        ) {
            paymentForm.bank_or_app_name =
                selectedAccount.value.name;
        }
    }
);

</script>

<template>
    <Head title="المشتريات" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-black text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300">
                            Purchase Management
                        </span>

                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                            مخزون + موردون + مالية
                        </span>
                    </div>

                    <h1 class="mt-2 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                        المشتريات
                    </h1>

                    <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        إدارة فواتير الموردين، الاعتماد، تحديث المخزون، الدفعات والمستحقات من شاشة واحدة.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('returns.index', { tab: 'purchase' })"
                        class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        مرتجعات المشتريات
                    </Link>

                    <Link
                        :href="route('purchases.create')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700"
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
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>

                        فاتورة شراء جديدة
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats -->
            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                <article
                    v-for="card in statsCards"
                    :key="card.label"
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="inline-flex rounded-xl px-2.5 py-1 text-[10px] font-black"
                        :class="card.tone"
                    >
                        {{ card.label }}
                    </div>

                    <p
                        dir="ltr"
                        class="mt-3 text-right text-lg font-black text-slate-950 dark:text-white"
                    >
                        {{ card.value }}
                    </p>

                    <p class="mt-1 text-[10px] leading-5 text-slate-400">
                        {{ card.hint }}
                    </p>
                </article>
            </section>

            <!-- Filters -->
            <section class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="period in periodOptions"
                        :key="period[0]"
                        type="button"
                        class="rounded-xl px-3 py-2 text-xs font-black transition"
                        :class="filters.period === period[0]
                            ? 'bg-indigo-600 text-white'
                            : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300'"
                        @click="setPeriod(period[0])"
                    >
                        {{ period[1] }}
                    </button>
                </div>

                <div class="mt-4 grid gap-3 lg:grid-cols-2 2xl:grid-cols-[1.4fr_repeat(5,minmax(0,1fr))_auto]">
                    <input
                        v-model.trim="filters.search"
                        type="search"
                        placeholder="رقم الفاتورة، رقم فاتورة المورد أو اسم المورد..."
                        class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        @keyup.enter="applyFilters"
                    />

                    <select
                        v-model="filters.status"
                        class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        @change="applyFilters"
                    >
                        <option value="">كل الحالات</option>

                        <option
                            v-for="(label, value) in statuses"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>

                    <select
                        v-model="filters.payment_status"
                        class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        @change="applyFilters"
                    >
                        <option value="">
                            كل حالات الدفع
                        </option>

                        <option
                            v-for="(label, value) in paymentStatuses"
                            :key="value"
                            :value="value"
                        >
                            {{ label }}
                        </option>
                    </select>

                    <select
                        v-model="filters.supplier_id"
                        class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        @change="applyFilters"
                    >
                        <option value="">
                            كل الموردين
                        </option>

                        <option
                            v-for="supplier in suppliers"
                            :key="supplier.id"
                            :value="supplier.id"
                        >
                            {{ supplier.company_name || supplier.name }}
                        </option>
                    </select>

                    <input
                        v-model="filters.start_date"
                        type="date"
                        class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        @change="applyCustomDate"
                    />

                    <input
                        v-model="filters.end_date"
                        type="date"
                        class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        @change="applyCustomDate"
                    />

                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="rounded-xl bg-slate-950 px-4 py-2 text-xs font-black text-white dark:bg-white dark:text-slate-950"
                            @click="applyFilters"
                        >
                            تطبيق
                        </button>

                        <button
                            v-if="hasFilters"
                            type="button"
                            class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-black text-slate-600 dark:border-slate-600 dark:text-slate-300"
                            @click="clearFilters"
                        >
                            مسح
                        </button>
                    </div>
                </div>
            </section>

            <!-- Table -->
            <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <div>
                        <h2 class="font-black text-slate-950 dark:text-white">
                            فواتير الشراء
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            {{ number(invoices.total) }} فاتورة
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1250px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">الفاتورة</th>
                                <th class="px-4 py-3 text-right">المورد</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">المنتجات</th>
                                <th class="px-4 py-3 text-right">الإجمالي</th>
                                <th class="px-4 py-3 text-right">المدفوع / المتبقي</th>
                                <th class="px-4 py-3 text-right">الحالة</th>
                                <th class="px-4 py-3 text-right">الاستحقاق</th>
                                <th class="px-4 py-3 text-center">الإجراءات</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr
                                v-for="invoice in invoices.data"
                                :key="invoice.id"
                                class="transition hover:bg-slate-50 dark:hover:bg-slate-700/30"
                            >
                                <td class="px-4 py-3">
                                    <Link
                                        dir="ltr"
                                        :href="route('purchases.show', invoice.id)"
                                        class="block text-right font-black text-indigo-700 hover:underline dark:text-indigo-300"
                                    >
                                        {{ invoice.invoice_number }}
                                    </Link>

                                    <span
                                        v-if="invoice.supplier_invoice_number"
                                        dir="ltr"
                                        class="mt-1 block text-right text-[10px] text-slate-400"
                                    >
                                        Supplier: {{ invoice.supplier_invoice_number }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <strong class="block text-slate-900 dark:text-white">
                                        {{ invoice.supplier?.company_name || invoice.supplier?.name || '—' }}
                                    </strong>

                                    <span
                                        v-if="invoice.supplier?.company_name && invoice.supplier?.name"
                                        class="mt-1 block text-xs text-slate-400"
                                    >
                                        {{ invoice.supplier.name }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    {{ date(invoice.purchase_date) }}
                                </td>

                                <td class="px-4 py-3">
                                    <strong dir="ltr" class="block text-right">
                                        {{ number(invoice.total_pieces) }} قطعة
                                    </strong>

                                    <span class="text-[10px] text-slate-400">
                                        {{ number(invoice.items_count) }} بند
                                    </span>
                                </td>

                                <td
                                    dir="ltr"
                                    class="px-4 py-3 text-right font-black text-slate-950 dark:text-white"
                                >
                                    {{ money(invoice.total_amount) }}
                                </td>

                                <td class="px-4 py-3">
                                    <strong dir="ltr" class="block text-right text-emerald-600">
                                        {{ money(invoice.paid_amount) }}
                                    </strong>

                                    <span dir="ltr" class="mt-1 block text-right text-xs text-amber-600">
                                        باقي {{ money(invoice.remaining_amount) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col items-start gap-1.5">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black ring-1 ring-inset"
                                            :class="statusClass(invoice.status)"
                                        >
                                            {{ statusLabel(invoice.status) }}
                                        </span>

                                        <span
                                            class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black ring-1 ring-inset"
                                            :class="paymentStatusClass(invoice.payment_status)"
                                        >
                                            {{ paymentStatusLabel(invoice.payment_status) }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <template v-if="invoice.due_date">
                                        <span
                                            :class="overdue(invoice)
                                                ? 'font-black text-rose-600'
                                                : 'text-slate-600 dark:text-slate-300'"
                                        >
                                            {{ date(invoice.due_date) }}
                                        </span>

                                        <span
                                            v-if="overdue(invoice)"
                                            class="mt-1 block text-[10px] font-black text-rose-500"
                                        >
                                            متأخرة
                                        </span>
                                    </template>

                                    <span v-else class="text-slate-400">
                                        —
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-1.5">
                                        <Link
                                            :href="route('purchases.show', invoice.id)"
                                            class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                                        >
                                            عرض
                                        </Link>

                                        <Link
                                            v-if="enumValue(invoice.status) === 'draft'"
                                            :href="route('purchases.edit', invoice.id)"
                                            class="rounded-lg bg-blue-50 px-2.5 py-1.5 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                                        >
                                            تعديل
                                        </Link>

                                        <button
                                            v-if="enumValue(invoice.status) === 'draft'"
                                            type="button"
                                            class="rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-black text-white"
                                            @click="confirmApprove(invoice)"
                                        >
                                            اعتماد
                                        </button>

                                        <button
                                            v-if="enumValue(invoice.status) === 'approved'
                                                && enumValue(invoice.payment_status) !== 'paid'"
                                            type="button"
                                            class="rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-black text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300"
                                            @click="openPaymentModal(invoice)"
                                        >
                                            دفعة
                                        </button>

                                        <button
                                            v-if="enumValue(invoice.status) === 'approved'
                                                && Number(invoice.paid_amount || 0) <= 0"
                                            type="button"
                                            class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                                            @click="openCancelModal(invoice)"
                                        >
                                            إلغاء
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!invoices.data.length">
                                <td
                                    colspan="9"
                                    class="px-6 py-16 text-center"
                                >
                                    <div class="mx-auto max-w-md">
                                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-700">
                                            <svg
                                                class="h-7 w-7"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.8"
                                                    d="M6 3h12a2 2 0 012 2v16l-4-2-4 2-4-2-4 2V5a2 2 0 012-2zm2 5h8m-8 4h8"
                                                />
                                            </svg>
                                        </div>

                                        <h3 class="mt-4 font-black text-slate-800 dark:text-slate-100">
                                            لا توجد فواتير مطابقة
                                        </h3>

                                        <p class="mt-2 text-sm text-slate-500">
                                            عدّل الفلاتر أو أنشئ فاتورة شراء جديدة.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 p-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">
                        عرض
                        <span dir="ltr">{{ number(invoices.from || 0) }}</span>
                        -
                        <span dir="ltr">{{ number(invoices.to || 0) }}</span>
                        من
                        <span dir="ltr">{{ number(invoices.total || 0) }}</span>
                    </p>

                    <Pagination :links="invoices.links" />
                </div>
            </section>
        </div>

        <!-- Approve -->
        <Modal
            :show="approveModal.show"
            @close="approveModal.show = false"
        >
            <template #title>
                اعتماد فاتورة الشراء
            </template>

            <template #content>
                <div class="space-y-4">
                    <div class="rounded-2xl bg-amber-50 p-4 text-sm text-amber-800 dark:bg-amber-950/30 dark:text-amber-200">
                        اعتماد الفاتورة سيضيف المنتجات للمخزون، ويحدّث متوسط تكلفة الشراء، ويرحل أي دفعات مسجلة إلى الحسابات المالية.
                    </div>

                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-slate-900">
                        <p dir="ltr" class="text-right font-black text-indigo-700 dark:text-indigo-300">
                            {{ approveModal.invoice?.invoice_number }}
                        </p>

                        <p class="mt-1 text-sm text-slate-500">
                            {{ approveModal.invoice?.supplier?.company_name || approveModal.invoice?.supplier?.name }}
                        </p>

                        <p dir="ltr" class="mt-2 text-right text-lg font-black">
                            {{ money(approveModal.invoice?.total_amount) }}
                        </p>
                    </div>

                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black dark:border-slate-600 dark:text-slate-200"
                            @click="approveModal.show = false"
                        >
                            تراجع
                        </button>

                        <button
                            type="button"
                            :disabled="approveForm.processing"
                            class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white disabled:opacity-50"
                            @click="submitApprove"
                        >
                            {{ approveForm.processing ? 'جاري الاعتماد...' : 'تأكيد الاعتماد' }}
                        </button>
                    </div>
                </div>
            </template>
        </Modal>

        <!-- Payment -->
        <Modal
            :show="paymentModal.show"
            @close="closePaymentModal"
        >
            <template #title>
                تسجيل دفعة للمورد
            </template>

            <template #content>
                <div
                    class="payment-scroll max-h-[calc(100dvh-8.5rem)] overflow-y-auto overscroll-contain px-1 pb-1 sm:max-h-[calc(100dvh-9rem)]"
                >
                    <form
                        class="space-y-4 pr-1"
                        @submit.prevent="submitPayment"
                    >
                    <!-- Invoice summary -->
                    <section
                        class="overflow-hidden rounded-[24px] border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800"
                    >
                        <div
                            class="bg-gradient-to-l from-slate-950 via-indigo-950 to-indigo-700 p-4 text-white"
                        >
                            <div
                                class="flex items-start justify-between gap-4"
                            >
                                <div>
                                    <p
                                        class="text-xs font-bold text-indigo-200"
                                    >
                                        فاتورة الشراء
                                    </p>

                                    <h3
                                        dir="ltr"
                                        class="mt-1 text-right text-xl font-black"
                                    >
                                        {{
                                            paymentModal
                                                .invoice
                                                ?.invoice_number
                                        }}
                                    </h3>

                                    <p
                                        class="mt-2 text-sm text-indigo-100"
                                    >
                                        {{
                                            paymentModal
                                                .invoice
                                                ?.supplier
                                                ?.company_name
                                            ||
                                            paymentModal
                                                .invoice
                                                ?.supplier
                                                ?.name
                                            ||
                                            'المورد'
                                        }}
                                    </p>
                                </div>

                                <div
                                    class="rounded-2xl bg-white/10 px-3 py-2 text-left backdrop-blur"
                                >
                                    <p
                                        class="text-[10px] text-indigo-200"
                                    >
                                        المتبقي
                                    </p>

                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-lg"
                                    >
                                        {{
                                            money(
                                                currentInvoiceRemaining
                                            )
                                        }}
                                    </strong>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div
                                    class="mb-2 flex items-center justify-between text-[10px] text-indigo-100"
                                >
                                    <span>
                                        نسبة السداد الحالية
                                    </span>

                                    <span dir="ltr">
                                        {{
                                            Number(
                                                paymentPercentageBefore
                                            ).toLocaleString(
                                                'en-US',
                                                {
                                                    maximumFractionDigits: 1,
                                                }
                                            )
                                        }}%
                                    </span>
                                </div>

                                <div
                                    class="h-2 overflow-hidden rounded-full bg-white/15"
                                >
                                    <div
                                        class="h-full rounded-full bg-emerald-400 transition-all duration-300"
                                        :style="{
                                            width:
                                                `${paymentPercentageBefore}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="grid grid-cols-3 divide-x divide-x-reverse divide-slate-100 dark:divide-slate-700"
                        >
                            <div class="p-3 text-center">
                                <p
                                    class="text-[10px] text-slate-400"
                                >
                                    الإجمالي
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-sm text-slate-950 dark:text-white"
                                >
                                    {{ money(currentInvoiceTotal) }}
                                </strong>
                            </div>

                            <div class="p-3 text-center">
                                <p
                                    class="text-[10px] text-slate-400"
                                >
                                    المدفوع
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-sm text-emerald-600"
                                >
                                    {{ money(currentInvoicePaid) }}
                                </strong>
                            </div>

                            <div class="p-3 text-center">
                                <p
                                    class="text-[10px] text-slate-400"
                                >
                                    المتبقي
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-sm text-amber-600"
                                >
                                    {{ money(currentInvoiceRemaining) }}
                                </strong>
                            </div>
                        </div>
                    </section>

                    <!-- Amount -->
                    <section>
                        <div
                            class="flex items-end justify-between gap-3"
                        >
                            <div>
                                <label
                                    class="block text-sm font-black text-slate-800 dark:text-slate-100"
                                >
                                    قيمة الدفعة
                                </label>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    أدخل المبلغ أو استخدم اختياراً سريعاً.
                                </p>
                            </div>

                            <span
                                dir="ltr"
                                class="text-xs font-bold text-slate-400"
                            >
                                Max:
                                {{ money(currentInvoiceRemaining) }}
                            </span>
                        </div>

                        <div class="relative mt-3">
                            <input
                                v-model.number="paymentForm.amount"
                                type="number"
                                min="0.01"
                                :max="currentInvoiceRemaining"
                                step="0.01"
                                class="block w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-4 pl-20 text-xl font-black text-slate-950 transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />

                            <span
                                class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 rounded-lg bg-slate-200 px-2 py-1 text-xs font-black text-slate-600 dark:bg-slate-700 dark:text-slate-200"
                            >
                                شيكل
                            </span>
                        </div>

                        <div
                            class="mt-3 grid grid-cols-3 gap-2"
                        >
                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-indigo-950/30"
                                @click="setQuickPayment('quarter')"
                            >
                                25%
                            </button>

                            <button
                                type="button"
                                class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-indigo-950/30"
                                @click="setQuickPayment('half')"
                            >
                                50%
                            </button>

                            <button
                                type="button"
                                class="rounded-xl bg-indigo-50 px-3 py-2.5 text-xs font-black text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300"
                                @click="setQuickPayment('full')"
                            >
                                دفع المتبقي كامل
                            </button>
                        </div>
                    </section>

                    <!-- Preview -->
                    <section
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/60"
                    >
                        <div
                            class="flex items-center justify-between gap-3"
                        >
                            <h4
                                class="text-sm font-black text-slate-950 dark:text-white"
                            >
                                بعد تسجيل الدفعة
                            </h4>

                            <span
                                class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                :class="paymentStatusAfter.class"
                            >
                                {{ paymentStatusAfter.label }}
                            </span>
                        </div>

                        <div
                            class="mt-4 grid grid-cols-2 gap-3"
                        >
                            <div
                                class="rounded-xl bg-white p-3 dark:bg-slate-800"
                            >
                                <p class="text-[10px] text-slate-400">
                                    إجمالي المدفوع
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-base text-emerald-600"
                                >
                                    {{ money(paidAfterPayment) }}
                                </strong>
                            </div>

                            <div
                                class="rounded-xl bg-white p-3 dark:bg-slate-800"
                            >
                                <p class="text-[10px] text-slate-400">
                                    المتبقي الجديد
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-base text-amber-600"
                                >
                                    {{ money(remainingAfterPayment) }}
                                </strong>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div
                                class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                            >
                                <div
                                    class="h-full rounded-full bg-emerald-500 transition-all duration-300"
                                    :style="{
                                        width:
                                            `${paymentPercentageAfter}%`,
                                    }"
                                ></div>
                            </div>
                        </div>
                    </section>

                    <!-- Payment method -->
                    <section>
                        <label
                            class="block text-sm font-black text-slate-800 dark:text-slate-100"
                        >
                            طريقة الدفع
                        </label>

                        <div
                            class="mt-3 grid gap-2 sm:grid-cols-3"
                        >
                            <button
                                v-for="(label, value) in paymentMethods"
                                :key="value"
                                type="button"
                                class="rounded-2xl border p-3 text-right transition"
                                :class="paymentForm.payment_method === value
                                    ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/10 dark:border-indigo-500 dark:bg-indigo-950/30'
                                    : 'border-slate-200 bg-white hover:border-indigo-200 dark:border-slate-700 dark:bg-slate-800'"
                                @click="paymentForm.payment_method = value"
                            >
                                <span
                                    class="block text-xs font-black text-slate-900 dark:text-white"
                                >
                                    {{ label }}
                                </span>

                                <span
                                    class="mt-1 block text-[10px] text-slate-400"
                                >
                                    {{
                                        value === 'cash'
                                            ? 'دفع نقدي'
                                            : value === 'bank_transfer'
                                                ? 'من حساب بنكي'
                                                : 'من تطبيق بنكي'
                                    }}
                                </span>
                            </button>
                        </div>
                    </section>

                    <!-- Accounts -->
                    <section>
                        <div
                            class="flex items-center justify-between gap-3"
                        >
                            <label
                                class="text-sm font-black text-slate-800 dark:text-slate-100"
                            >
                                الحساب المالي
                            </label>

                            <span
                                class="text-[10px] text-slate-400"
                            >
                                {{ selectedPaymentMethodLabel }}
                            </span>
                        </div>

                        <div
                            v-if="compatibleAccounts.length"
                            class="mt-3 grid gap-2"
                        >
                            <button
                                v-for="account in compatibleAccounts"
                                :key="account.id"
                                type="button"
                                class="flex items-center justify-between gap-4 rounded-2xl border p-3 text-right transition"
                                :disabled="paymentAmount > 0 && Number(account.current_balance || 0) < paymentAmount"
                                :class="[
                                    Number(paymentForm.financial_account_id) === Number(account.id)
                                        ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/10 dark:border-indigo-500 dark:bg-indigo-950/30'
                                        : 'border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800',

                                    paymentAmount > 0 && Number(account.current_balance || 0) < paymentAmount
                                        ? 'cursor-not-allowed opacity-50'
                                        : 'hover:border-indigo-300',
                                ]"
                                @click="paymentForm.financial_account_id = account.id"
                            >
                                <div>
                                    <strong
                                        class="block text-sm text-slate-900 dark:text-white"
                                    >
                                        {{ account.name }}
                                    </strong>

                                    <span
                                        class="mt-1 block text-[10px] text-slate-400"
                                    >
                                        {{ account.type_label || account.type }}
                                    </span>
                                </div>

                                <div class="text-left">
                                    <span
                                        class="block text-[10px] text-slate-400"
                                    >
                                        الرصيد
                                    </span>

                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-sm"
                                        :class="Number(account.current_balance || 0) >= paymentAmount
                                            ? 'text-emerald-600'
                                            : 'text-rose-600'"
                                    >
                                        {{ money(account.current_balance) }}
                                    </strong>
                                </div>
                            </button>
                        </div>

                        <div
                            v-else
                            class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
                        >
                            لا يوجد حساب مالي نشط ومتوافق مع طريقة الدفع المختارة.

                            <Link
                                :href="route('finance.index')"
                                class="mr-1 font-black underline"
                            >
                                فتح المركز المالي
                            </Link>
                        </div>

                        <div
                            v-if="selectedAccount"
                            class="mt-3 grid grid-cols-2 gap-3"
                        >
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900"
                            >
                                <p class="text-[10px] text-slate-400">
                                    الرصيد قبل الدفع
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-sm"
                                >
                                    {{ money(selectedAccount.current_balance) }}
                                </strong>
                            </div>

                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900"
                            >
                                <p class="text-[10px] text-slate-400">
                                    الرصيد بعد الدفع
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-sm"
                                    :class="accountBalanceAfterPayment >= 0
                                        ? 'text-emerald-600'
                                        : 'text-rose-600'"
                                >
                                    {{ money(accountBalanceAfterPayment) }}
                                </strong>
                            </div>
                        </div>
                    </section>

                    <!-- Date / reference -->
                    <section
                        class="grid gap-4 sm:grid-cols-2"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300"
                            >
                                تاريخ الدفع
                            </label>

                            <input
                                v-model="paymentForm.paid_at"
                                type="date"
                                :min="String(paymentModal.invoice?.purchase_date || '').slice(0, 10)"
                                :max="localDate()"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />
                        </div>

                        <div
                            v-if="paymentForm.payment_method !== 'cash'"
                        >
                            <label
                                class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300"
                            >
                                مرجع العملية
                            </label>

                            <input
                                v-model.trim="paymentForm.transaction_reference"
                                type="text"
                                placeholder="رقم التحويل أو المرجع - اختياري"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />
                        </div>
                    </section>

                    <div>
                        <label
                            class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300"
                        >
                            ملاحظات
                        </label>

                        <textarea
                            v-model.trim="paymentForm.notes"
                            rows="2"
                            maxlength="500"
                            placeholder="أي ملاحظة مرتبطة بالدفعة..."
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        ></textarea>
                    </div>

                    <!-- Errors -->
                    <div
                        v-if="paymentError || Object.keys(paymentForm.errors).length"
                        class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
                    >
                        <p
                            v-if="paymentError"
                            class="font-black"
                        >
                            {{ paymentError }}
                        </p>

                        <p
                            v-for="(error, key) in paymentForm.errors"
                            :key="key"
                            class="mt-1"
                        >
                            {{ error }}
                        </p>
                    </div>

                    <!-- Final action -->
                    <div
                        class="sticky bottom-0 z-20 -mx-1 rounded-2xl border border-slate-800 bg-slate-950 p-4 text-white shadow-[0_-12px_30px_rgba(15,23,42,0.18)] dark:bg-slate-900"
                    >
                        <div
                            class="flex items-center justify-between gap-4"
                        >
                            <div>
                                <p
                                    class="text-[10px] text-slate-400"
                                >
                                    سيتم خصم
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-lg"
                                >
                                    {{ money(paymentAmount) }}
                                </strong>

                                <p
                                    v-if="selectedAccount"
                                    class="mt-1 text-[10px] text-slate-400"
                                >
                                    من
                                    {{ selectedAccount.name }}
                                </p>
                            </div>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-white/15 px-4 py-3 text-sm font-black text-white transition hover:bg-white/10"
                                    @click="closePaymentModal"
                                >
                                    تراجع
                                </button>

                                <button
                                    type="submit"
                                    :disabled="paymentForm.processing || Boolean(paymentError)"
                                    class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-black text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    {{
                                        paymentForm.processing
                                            ? 'جاري تسجيل الدفعة...'
                                            : remainingAfterPayment <= 0.00001
                                                ? 'دفع المتبقي وإغلاق الفاتورة'
                                                : 'تأكيد تسجيل الدفعة'
                                    }}
                                </button>
                            </div>
                        </div>
                    </div>
                    </form>
                </div>
            </template>
        </Modal>

        <!-- Cancel -->
        <Modal
            :show="cancelModal.show"
            @close="cancelModal.show = false"
        >
            <template #title>
                إلغاء فاتورة الشراء
            </template>

            <template #content>
                <form
                    class="space-y-4"
                    @submit.prevent="submitCancel"
                >
                    <div class="rounded-xl bg-rose-50 p-4 text-sm leading-6 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">
                        الإلغاء المباشر مسموح فقط عندما لا توجد دفعات ولا حركات لاحقة على المنتجات.
                        إذا تحرك المخزون بعد اعتماد الفاتورة سيطلب منك النظام استخدام مرتجع المشتريات بدلاً من الإلغاء.
                    </div>

                    <textarea
                        v-model.trim="cancelForm.reason"
                        rows="3"
                        maxlength="500"
                        placeholder="سبب الإلغاء..."
                        class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    ></textarea>

                    <div class="flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black dark:border-slate-600 dark:text-slate-200"
                            @click="cancelModal.show = false"
                        >
                            تراجع
                        </button>

                        <button
                            type="submit"
                            :disabled="cancelForm.processing || !cancelForm.reason.trim()"
                            class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-black text-white disabled:opacity-50"
                        >
                            {{ cancelForm.processing ? 'جاري الإلغاء...' : 'تأكيد الإلغاء' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.payment-scroll {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
    -webkit-overflow-scrolling: touch;
}

.payment-scroll::-webkit-scrollbar {
    width: 6px;
}

.payment-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.payment-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}

.payment-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

:global(.dark) .payment-scroll {
    scrollbar-color: #475569 transparent;
}

:global(.dark) .payment-scroll::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
