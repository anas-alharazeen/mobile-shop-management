<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    salesInvoices: { type: Object, required: true },
    repairOrders: { type: Object, required: true },
    customers: { type: Array, default: () => [] },
    financialAccounts: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const filters = reactive({
    search: props.filters.search || '',
    customer_id: props.filters.customer_id || '',
    overdue_only: Boolean(props.filters.overdue_only),
});

let searchTimer = null;

const applyFilters = () => {
    router.get(
        route('payments.customer-receivables'),
        {
            search: filters.search || undefined,
            customer_id: filters.customer_id || undefined,
            overdue_only: filters.overdue_only ? 1 : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const applySearch = () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
};

const clearFilters = () => {
    filters.search = '';
    filters.customer_id = '';
    filters.overdue_only = false;
    applyFilters();
};

const paymentModal = ref({
    show: false,
    type: 'sales',
    source: null,
});

const today = new Date().toISOString().slice(0, 10);

const paymentForm = useForm({
    type: '',
    invoice_id: null,
    amount: '',
    payment_method: '',
    financial_account_id: '',
    bank_or_app_name: '',
    transaction_reference: '',
    paid_at: today,
    notes: '',
});

const paymentMethods = {
    cash: 'كاش',
    bank_transfer: 'تحويل بنكي',
    banking_app: 'تطبيق بنكي',
};

const expectedAccountType = (method) => ({
    cash: 'cash',
    bank_transfer: 'bank',
    banking_app: 'banking_app',
}[method] || null);

const compatibleAccounts = computed(() => {
    const type = expectedAccountType(paymentForm.payment_method);
    return type
        ? props.financialAccounts.filter((account) => account.type === type)
        : [];
});

const selectedAccount = computed(() => (
    props.financialAccounts.find(
        (account) =>
            Number(account.id) === Number(paymentForm.financial_account_id)
    ) || null
));

const projectedBalance = computed(() => (
    Number(selectedAccount.value?.current_balance || 0)
    + Number(paymentForm.amount || 0)
));

const isElectronic = computed(() => (
    ['bank_transfer', 'banking_app'].includes(paymentForm.payment_method)
));

watch(
    () => paymentForm.payment_method,
    (method) => {
        const accounts = props.financialAccounts.filter(
            (account) => account.type === expectedAccountType(method)
        );

        const selectedValid = accounts.some(
            (account) =>
                Number(account.id) === Number(paymentForm.financial_account_id)
        );

        if (!selectedValid) {
            paymentForm.financial_account_id = accounts[0]?.id || '';
        }

        if (!['bank_transfer', 'banking_app'].includes(method)) {
            paymentForm.bank_or_app_name = '';
            paymentForm.transaction_reference = '';
        }
    }
);

const sourceRemaining = computed(() => (
    Number(paymentModal.value.source?.remaining_amount || 0)
));

const sourceDocumentDate = computed(() => {
    const source = paymentModal.value.source;
    if (!source) return null;

    const raw = paymentModal.value.type === 'sales'
        ? source.sale_date
        : source.received_at;

    return raw ? String(raw).slice(0, 10) : null;
});

const paymentError = computed(() => {
    const amount = Number(paymentForm.amount || 0);

    if (amount <= 0) return 'أدخل مبلغاً أكبر من صفر.';
    if (amount > sourceRemaining.value + 0.00001) {
        return 'المبلغ أكبر من الرصيد المتبقي.';
    }
    if (!paymentForm.payment_method) return 'اختر طريقة الدفع.';
    if (!compatibleAccounts.value.length) {
        return 'لا يوجد حساب مالي نشط ومتوافق مع طريقة الدفع المختارة.';
    }
    if (!paymentForm.financial_account_id) {
        return 'اختر الحساب المالي الذي ستدخل إليه الدفعة.';
    }
    if (!paymentForm.paid_at) return 'اختر تاريخ الدفع.';
    if (paymentForm.paid_at > today) {
        return 'تاريخ الدفعة لا يمكن أن يكون في المستقبل.';
    }
    if (
        sourceDocumentDate.value
        && paymentForm.paid_at < sourceDocumentDate.value
    ) {
        return 'تاريخ الدفعة لا يمكن أن يسبق تاريخ المستند.';
    }

    return '';
});

const openPaymentModal = (type, source) => {
    paymentModal.value = { show: true, type, source };

    paymentForm.clearErrors();
    paymentForm.type = type;
    paymentForm.invoice_id = source.id;
    paymentForm.amount = Number(source.remaining_amount || 0);
    paymentForm.payment_method = 'cash';
    paymentForm.financial_account_id = '';
    paymentForm.bank_or_app_name = '';
    paymentForm.transaction_reference = '';
    paymentForm.paid_at = today;
    paymentForm.notes = '';
};

const closePaymentModal = () => {
    if (paymentForm.processing) return;
    paymentModal.value.show = false;
    paymentForm.clearErrors();
};

const submitPayment = () => {
    if (paymentError.value) return;

    paymentForm.post(
        route('payments.store'),
        {
            preserveScroll: true,
            onSuccess: () => {
                paymentModal.value.show = false;
                paymentForm.reset();
            },
        }
    );
};

const formatCurrency = (value) => (
    `${Number(value || 0).toLocaleString('ar-PS', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`
);

const formatDate = (value) => {
    if (!value) return '—';
    return new Intl.DateTimeFormat('ar-PS', {
        dateStyle: 'medium',
    }).format(new Date(value));
};

const dateOnly = (value) => (
    value ? String(value).slice(0, 10) : null
);

const isOverdue = (dueDate) => {
    const date = dateOnly(dueDate);
    return Boolean(date && date < today);
};

const overdueDays = (dueDate) => {
    const date = dateOnly(dueDate);
    if (!date) return 0;

    return Math.max(
        0,
        Math.floor(
            (
                new Date(`${today}T00:00:00`)
                - new Date(`${date}T00:00:00`)
            ) / 86400000
        )
    );
};

const partyName = (source) => (
    source?.customer_name
    || source?.customer?.name
    || 'عميل نقدي'
);
</script>

<template>
    <Head title="مستحقات العملاء" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-black text-blue-600 dark:text-blue-400">
                        مركز الدفعات
                    </p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        مستحقات العملاء
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        فواتير البيع وطلبات الصيانة التي ما زال عليها رصيد.
                    </p>
                </div>

                <Link
                    :href="route('payments.index')"
                    class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-blue-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                >
                    العودة لمركز الدفعات
                </Link>
            </div>
        </template>

        <div class="space-y-5">
            <section class="grid gap-3 sm:grid-cols-3">
                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20">
                    <p class="text-xs font-bold text-rose-700 dark:text-rose-300">
                        مستحقات المبيعات
                    </p>
                    <strong class="mt-2 block text-xl font-black text-rose-700 dark:text-rose-300">
                        {{ formatCurrency(summary.sales_due) }}
                    </strong>
                </article>

                <article class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900/50 dark:bg-violet-950/20">
                    <p class="text-xs font-bold text-violet-700 dark:text-violet-300">
                        مستحقات الصيانة
                    </p>
                    <strong class="mt-2 block text-xl font-black text-violet-700 dark:text-violet-300">
                        {{ formatCurrency(summary.repair_due) }}
                    </strong>
                </article>

                <article class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
                    <p class="text-xs font-bold text-blue-700 dark:text-blue-300">
                        إجمالي المستحق
                    </p>
                    <strong class="mt-2 block text-xl font-black text-blue-700 dark:text-blue-300">
                        {{ formatCurrency(summary.total_due) }}
                    </strong>
                </article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto_auto]">
                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="بحث بالعميل أو رقم الفاتورة/الصيانة..."
                        class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        @input="applySearch"
                    />

                    <select
                        v-model="filters.customer_id"
                        class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        @change="applyFilters"
                    >
                        <option value="">جميع العملاء</option>
                        <option
                            v-for="customer in customers"
                            :key="customer.id"
                            :value="customer.id"
                        >
                            {{ customer.name }}
                        </option>
                    </select>

                    <label class="flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-2 text-sm font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                        <input
                            v-model="filters.overdue_only"
                            type="checkbox"
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            @change="applyFilters"
                        />
                        المتأخرة فقط
                    </label>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200"
                        @click="clearFilters"
                    >
                        مسح الفلاتر
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black text-slate-950 dark:text-white">فواتير البيع</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-right">الفاتورة</th>
                                <th class="px-4 py-3 text-right">العميل</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-left">الإجمالي</th>
                                <th class="px-4 py-3 text-left">المدفوع</th>
                                <th class="px-4 py-3 text-left">المتبقي</th>
                                <th class="px-4 py-3 text-center">الإجراء</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="invoice in salesInvoices.data"
                                :key="invoice.id"
                            >
                                <td class="px-4 py-4 font-black dark:text-white">
                                    {{ invoice.invoice_number }}
                                </td>

                                <td class="px-4 py-4 dark:text-white">
                                    {{ partyName(invoice) }}
                                </td>

                                <td class="px-4 py-4 text-slate-500">
                                    {{ formatDate(invoice.sale_date) }}
                                    <span
                                        v-if="isOverdue(invoice.due_date)"
                                        class="mt-1 block text-[10px] font-black text-rose-600"
                                    >
                                        متأخرة {{ overdueDays(invoice.due_date) }} يوم
                                    </span>
                                </td>

                                <td dir="ltr" class="px-4 py-4 text-left font-bold dark:text-white">
                                    {{ formatCurrency(invoice.total_amount) }}
                                </td>
                                <td dir="ltr" class="px-4 py-4 text-left font-bold text-emerald-600">
                                    {{ formatCurrency(invoice.paid_amount) }}
                                </td>
                                <td dir="ltr" class="px-4 py-4 text-left font-black text-rose-600">
                                    {{ formatCurrency(invoice.remaining_amount) }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-black text-white hover:bg-blue-700"
                                            @click="openPaymentModal('sales', invoice)"
                                        >
                                            تسجيل دفعة
                                        </button>
                                        <Link
                                            :href="route('sales.show', invoice.id)"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200"
                                        >
                                            عرض
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!salesInvoices.data.length">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    لا توجد مستحقات مبيعات ضمن الفلاتر.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="salesInvoices.links?.length"
                    class="border-t border-slate-200 p-4 dark:border-slate-700"
                >
                    <Pagination :links="salesInvoices.links" />
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black text-slate-950 dark:text-white">طلبات الصيانة</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-right">الطلب</th>
                                <th class="px-4 py-3 text-right">العميل</th>
                                <th class="px-4 py-3 text-right">الجهاز</th>
                                <th class="px-4 py-3 text-left">الإجمالي</th>
                                <th class="px-4 py-3 text-left">المدفوع</th>
                                <th class="px-4 py-3 text-left">المتبقي</th>
                                <th class="px-4 py-3 text-center">الإجراء</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="order in repairOrders.data"
                                :key="order.id"
                            >
                                <td class="px-4 py-4 font-black dark:text-white">
                                    {{ order.order_number }}
                                    <span
                                        v-if="isOverdue(order.due_date)"
                                        class="mt-1 block text-[10px] font-black text-rose-600"
                                    >
                                        متأخرة {{ overdueDays(order.due_date) }} يوم
                                    </span>
                                </td>

                                <td class="px-4 py-4 dark:text-white">
                                    {{ partyName(order) }}
                                </td>

                                <td class="px-4 py-4 text-slate-500">
                                    {{ [order.brand, order.model, order.device_type].filter(Boolean).join(' · ') || '—' }}
                                </td>

                                <td dir="ltr" class="px-4 py-4 text-left font-bold dark:text-white">
                                    {{ formatCurrency(order.total_amount) }}
                                </td>
                                <td dir="ltr" class="px-4 py-4 text-left font-bold text-emerald-600">
                                    {{ formatCurrency(order.paid_amount) }}
                                </td>
                                <td dir="ltr" class="px-4 py-4 text-left font-black text-rose-600">
                                    {{ formatCurrency(order.remaining_amount) }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-black text-white hover:bg-blue-700"
                                            @click="openPaymentModal('repair', order)"
                                        >
                                            تسجيل دفعة
                                        </button>
                                        <Link
                                            :href="route('repairs.show', order.id)"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200"
                                        >
                                            عرض
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!repairOrders.data.length">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    لا توجد مستحقات صيانة ضمن الفلاتر.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="repairOrders.links?.length"
                    class="border-t border-slate-200 p-4 dark:border-slate-700"
                >
                    <Pagination :links="repairOrders.links" />
                </div>
            </section>
        </div>

        <Modal
            :show="paymentModal.show"
            max-width="2xl"
            @close="closePaymentModal"
        >
            <div class="max-h-[90vh] overflow-y-auto p-6">
                <div>
                    <p class="text-xs font-black text-blue-600">COLLECTION</p>
                    <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">
                        تسجيل دفعة عميل
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ paymentModal.type === 'sales' ? 'فاتورة بيع' : 'طلب صيانة' }}
                        ·
                        {{ paymentModal.source?.invoice_number || paymentModal.source?.order_number }}
                    </p>
                </div>

                <div class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-3 dark:bg-slate-800/60">
                    <div>
                        <p class="text-[10px] text-slate-400">العميل</p>
                        <strong class="text-sm dark:text-white">
                            {{ partyName(paymentModal.source) }}
                        </strong>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400">المتبقي</p>
                        <strong class="text-sm text-rose-600">
                            {{ formatCurrency(sourceRemaining) }}
                        </strong>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400">بعد الدفعة</p>
                        <strong class="text-sm text-blue-600">
                            {{ formatCurrency(Math.max(0, sourceRemaining - Number(paymentForm.amount || 0))) }}
                        </strong>
                    </div>
                </div>

                <form class="mt-5 space-y-4" @submit.prevent="submitPayment">
                    <div>
                        <label class="text-sm font-black dark:text-white">قيمة الدفعة</label>
                        <input
                            v-model.number="paymentForm.amount"
                            type="number"
                            min="0.01"
                            step="0.01"
                            :max="sourceRemaining"
                            class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="text-sm font-black dark:text-white">طريقة الدفع</label>
                            <select
                                v-model="paymentForm.payment_method"
                                class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                            >
                                <option
                                    v-for="(label, value) in paymentMethods"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="text-sm font-black dark:text-white">الحساب المالي</label>
                            <select
                                v-model="paymentForm.financial_account_id"
                                class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                            >
                                <option value="">اختر الحساب</option>
                                <option
                                    v-for="account in compatibleAccounts"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.name }} — {{ formatCurrency(account.current_balance) }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div
                        v-if="selectedAccount"
                        class="grid gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-4 sm:grid-cols-2 dark:border-blue-900/40 dark:bg-blue-950/20"
                    >
                        <div>
                            <p class="text-[10px] text-blue-500">رصيد الحساب الحالي</p>
                            <strong class="text-sm text-blue-800 dark:text-blue-200">
                                {{ formatCurrency(selectedAccount.current_balance) }}
                            </strong>
                        </div>
                        <div>
                            <p class="text-[10px] text-blue-500">الرصيد بعد التحصيل</p>
                            <strong class="text-sm text-blue-800 dark:text-blue-200">
                                {{ formatCurrency(projectedBalance) }}
                            </strong>
                        </div>
                    </div>

                    <div v-if="isElectronic">
                        <label class="text-sm font-black dark:text-white">رقم/مرجع العملية</label>
                        <input
                            v-model.trim="paymentForm.transaction_reference"
                            type="text"
                            maxlength="255"
                            placeholder="اختياري"
                            class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-black dark:text-white">تاريخ الدفع</label>
                        <input
                            v-model="paymentForm.paid_at"
                            type="date"
                            :min="sourceDocumentDate || undefined"
                            :max="today"
                            class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        />
                    </div>

                    <div>
                        <label class="text-sm font-black dark:text-white">ملاحظات</label>
                        <textarea
                            v-model.trim="paymentForm.notes"
                            rows="2"
                            maxlength="500"
                            class="mt-2 w-full resize-none rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        />
                    </div>

                    <p
                        v-if="paymentError"
                        class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                    >
                        {{ paymentError }}
                    </p>

                    <p
                        v-else-if="Object.keys(paymentForm.errors).length"
                        class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                    >
                        {{ Object.values(paymentForm.errors)[0] }}
                    </p>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <button
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200"
                            @click="closePaymentModal"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="paymentForm.processing || Boolean(paymentError)"
                            class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white disabled:opacity-50"
                        >
                            {{ paymentForm.processing ? 'جاري التسجيل...' : 'تسجيل الدفعة' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
