<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    invoices: { type: Object, required: true },
    suppliers: { type: Array, default: () => [] },
    financialAccounts: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const filters = reactive({
    search: props.filters.search || '',
    supplier_id: props.filters.supplier_id || '',
    overdue_only: Boolean(props.filters.overdue_only),
});

let searchTimer = null;

const applyFilters = () => {
    router.get(
        route('payments.supplier-payables'),
        {
            search: filters.search || undefined,
            supplier_id: filters.supplier_id || undefined,
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
    filters.supplier_id = '';
    filters.overdue_only = false;
    applyFilters();
};

const today = new Date().toISOString().slice(0, 10);

const paymentModal = ref({
    show: false,
    invoice: null,
});

const paymentForm = useForm({
    type: 'purchase',
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

const compatibleAccounts = computed(() => (
    props.financialAccounts.filter(
        (account) =>
            account.type === expectedAccountType(paymentForm.payment_method)
    )
));

const selectedAccount = computed(() => (
    props.financialAccounts.find(
        (account) =>
            Number(account.id) === Number(paymentForm.financial_account_id)
    ) || null
));

const projectedBalance = computed(() => (
    Number(selectedAccount.value?.current_balance || 0)
    - Number(paymentForm.amount || 0)
));

const isElectronic = computed(() => (
    ['bank_transfer', 'banking_app'].includes(paymentForm.payment_method)
));

watch(
    () => paymentForm.payment_method,
    (method) => {
        const accounts = props.financialAccounts.filter(
            (account) =>
                account.type === expectedAccountType(method)
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
    Number(paymentModal.value.invoice?.remaining_amount || 0)
));

const sourceDate = computed(() => (
    paymentModal.value.invoice?.purchase_date
        ? String(paymentModal.value.invoice.purchase_date).slice(0, 10)
        : null
));

const paymentError = computed(() => {
    const amount = Number(paymentForm.amount || 0);

    if (amount <= 0) return 'أدخل مبلغاً أكبر من صفر.';
    if (amount > sourceRemaining.value + 0.00001) {
        return 'المبلغ أكبر من المستحق على الفاتورة.';
    }
    if (!paymentForm.payment_method) return 'اختر طريقة الدفع.';
    if (!compatibleAccounts.value.length) {
        return 'لا يوجد حساب مالي نشط ومتوافق مع طريقة الدفع المختارة.';
    }
    if (!paymentForm.financial_account_id) {
        return 'اختر الحساب المالي الذي ستخرج منه الدفعة.';
    }
    if (
        selectedAccount.value
        && Number(selectedAccount.value.current_balance || 0) + 0.00001 < amount
    ) {
        return `رصيد الحساب غير كافٍ. المتوفر ${formatCurrency(selectedAccount.value.current_balance)}.`;
    }
    if (!paymentForm.paid_at) return 'اختر تاريخ الدفع.';
    if (paymentForm.paid_at > today) {
        return 'تاريخ الدفعة لا يمكن أن يكون في المستقبل.';
    }
    if (
        sourceDate.value
        && paymentForm.paid_at < sourceDate.value
    ) {
        return 'تاريخ الدفعة لا يمكن أن يسبق تاريخ فاتورة الشراء.';
    }

    return '';
});

const openPaymentModal = (invoice) => {
    paymentModal.value = { show: true, invoice };

    paymentForm.clearErrors();
    paymentForm.type = 'purchase';
    paymentForm.invoice_id = invoice.id;
    paymentForm.amount = Number(invoice.remaining_amount || 0);
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

const supplierName = (invoice) => (
    invoice?.supplier?.company_name
    || invoice?.supplier?.name
    || 'مورد غير محدد'
);
</script>

<template>
    <Head title="مستحقات الموردين" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-black text-blue-600 dark:text-blue-400">
                        مركز الدفعات
                    </p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        مستحقات الموردين
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        فواتير الشراء المعتمدة التي ما زال عليها مبلغ للمورد.
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
            <section class="grid gap-3 sm:grid-cols-2">
                <article class="rounded-2xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-950/20">
                    <p class="text-xs font-bold text-orange-700 dark:text-orange-300">
                        إجمالي المستحق للموردين
                    </p>
                    <strong class="mt-2 block text-xl font-black text-orange-700 dark:text-orange-300">
                        {{ formatCurrency(summary.total_due) }}
                    </strong>
                </article>

                <article class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
                    <p class="text-xs font-bold text-blue-700 dark:text-blue-300">
                        عدد الفواتير المستحقة
                    </p>
                    <strong class="mt-2 block text-xl font-black text-blue-700 dark:text-blue-300">
                        {{ Number(summary.invoice_count || 0).toLocaleString('ar-PS') }}
                    </strong>
                </article>
            </section>

            <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_220px_auto_auto]">
                    <input
                        v-model="filters.search"
                        type="search"
                        placeholder="بحث بالمورد أو رقم الفاتورة..."
                        class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        @input="applySearch"
                    />

                    <select
                        v-model="filters.supplier_id"
                        class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        @change="applyFilters"
                    >
                        <option value="">جميع الموردين</option>
                        <option
                            v-for="supplier in suppliers"
                            :key="supplier.id"
                            :value="supplier.id"
                        >
                            {{ supplier.company_name || supplier.name }}
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
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-right">الفاتورة</th>
                                <th class="px-4 py-3 text-right">المورد</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-left">الإجمالي</th>
                                <th class="px-4 py-3 text-left">المدفوع</th>
                                <th class="px-4 py-3 text-left">المتبقي</th>
                                <th class="px-4 py-3 text-center">الإجراء</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="invoice in invoices.data"
                                :key="invoice.id"
                            >
                                <td class="px-4 py-4 font-black dark:text-white">
                                    {{ invoice.invoice_number }}
                                    <span
                                        v-if="invoice.supplier_invoice_number"
                                        dir="ltr"
                                        class="mt-1 block text-[10px] font-normal text-slate-400"
                                    >
                                        Supplier: {{ invoice.supplier_invoice_number }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 dark:text-white">
                                    {{ supplierName(invoice) }}
                                </td>

                                <td class="px-4 py-4 text-slate-500">
                                    {{ formatDate(invoice.purchase_date) }}
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
                                <td dir="ltr" class="px-4 py-4 text-left font-black text-orange-600">
                                    {{ formatCurrency(invoice.remaining_amount) }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="flex justify-center gap-2">
                                        <button
                                            type="button"
                                            class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-black text-white hover:bg-blue-700"
                                            @click="openPaymentModal(invoice)"
                                        >
                                            دفع للمورد
                                        </button>

                                        <Link
                                            :href="route('purchases.show', invoice.id)"
                                            class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200"
                                        >
                                            عرض
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!invoices.data.length">
                                <td colspan="7" class="px-6 py-14 text-center text-slate-500">
                                    لا توجد مستحقات موردين ضمن الفلاتر.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="invoices.links?.length"
                    class="border-t border-slate-200 p-4 dark:border-slate-700"
                >
                    <Pagination :links="invoices.links" />
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
                    <p class="text-xs font-black text-orange-600">
                        SUPPLIER PAYMENT
                    </p>
                    <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">
                        تسجيل دفعة للمورد
                    </h2>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ paymentModal.invoice?.invoice_number }}
                        ·
                        {{ supplierName(paymentModal.invoice) }}
                    </p>
                </div>

                <div class="mt-5 grid gap-3 rounded-2xl bg-slate-50 p-4 sm:grid-cols-3 dark:bg-slate-800/60">
                    <div>
                        <p class="text-[10px] text-slate-400">المتبقي</p>
                        <strong class="text-sm text-orange-600">
                            {{ formatCurrency(sourceRemaining) }}
                        </strong>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400">دفعة الآن</p>
                        <strong class="text-sm dark:text-white">
                            {{ formatCurrency(paymentForm.amount) }}
                        </strong>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400">المتبقي بعد الدفع</p>
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
                        class="grid gap-3 rounded-2xl border p-4 sm:grid-cols-2"
                        :class="
                            projectedBalance < 0
                                ? 'border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/20'
                                : 'border-blue-100 bg-blue-50 dark:border-blue-900/40 dark:bg-blue-950/20'
                        "
                    >
                        <div>
                            <p class="text-[10px] text-slate-500">رصيد الحساب الحالي</p>
                            <strong class="text-sm dark:text-white">
                                {{ formatCurrency(selectedAccount.current_balance) }}
                            </strong>
                        </div>
                        <div>
                            <p class="text-[10px] text-slate-500">الرصيد بعد الدفع</p>
                            <strong
                                class="text-sm"
                                :class="projectedBalance < 0 ? 'text-rose-600' : 'text-blue-600'"
                            >
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
                            :min="sourceDate || undefined"
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
