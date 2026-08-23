<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    payments: {
        type: Object,
        default: () => ({
            data: [],
            links: [],
            total: 0,
            from: 0,
            to: 0,
        }),
    },

    paymentMethods: {
        type: Object,
        default: () => ({}),
    },

    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filters = reactive({
    search: props.filters?.search ?? '',
    start_date: props.filters?.start_date ?? '',
    end_date: props.filters?.end_date ?? '',
});

let searchTimer = null;

const formatCurrency = (value) => {
    return `${Number(value || 0).toLocaleString('ar-PS', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`;
};

const formatDateTime = (value) => {
    if (!value) {
        return '—';
    }

    const parsedDate = new Date(value);

    if (Number.isNaN(parsedDate.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('ar-PS', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(parsedDate);
};

const paymentTypeLabel = (type) => {
    return {
        sales: 'دفعة مبيعات',
        purchase: 'دفعة مشتريات',
        repair: 'دفعة صيانة',
    }[type] ?? 'دفعة';
};

const paymentTypeClasses = (type) => {
    return {
        sales:
            'border-emerald-200 bg-emerald-50 text-emerald-700 '
            + 'dark:border-emerald-800 dark:bg-emerald-900/20 '
            + 'dark:text-emerald-300',

        purchase:
            'border-orange-200 bg-orange-50 text-orange-700 '
            + 'dark:border-orange-800 dark:bg-orange-900/20 '
            + 'dark:text-orange-300',

        repair:
            'border-violet-200 bg-violet-50 text-violet-700 '
            + 'dark:border-violet-800 dark:bg-violet-900/20 '
            + 'dark:text-violet-300',
    }[type] ?? 'border-slate-200 bg-slate-50 text-slate-700';
};

const paymentMethodLabel = (method) => {
    const normalizedMethod =
        typeof method === 'object' && method !== null
            ? method.value
            : method;

    return (
        props.paymentMethods?.[normalizedMethod]
        ?? normalizedMethod
        ?? 'غير محدد'
    );
};

const detailsRoute = (payment) => {
    if (!payment?.source_id) {
        return null;
    }

    if (payment.type === 'sales') {
        return route('sales.show', payment.source_id);
    }

    if (payment.type === 'purchase') {
        return route('purchases.show', payment.source_id);
    }

    if (payment.type === 'repair') {
        return route('repairs.show', payment.source_id);
    }

    return null;
};

const buildQuery = () => {
    const query = {};

    if (filters.search.trim() !== '') {
        query.search = filters.search.trim();
    }

    if (filters.start_date) {
        query.start_date = filters.start_date;
    }

    if (filters.end_date) {
        query.end_date = filters.end_date;
    }

    return query;
};

const loadPayments = () => {
    router.get(
        route('payments.all-payments'),
        buildQuery(),
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: [
                'payments',
                'filters',
            ],
        }
    );
};

const applySearch = () => {
    window.clearTimeout(searchTimer);

    searchTimer = window.setTimeout(() => {
        loadPayments();
    }, 350);
};

const applyFilters = () => {
    loadPayments();
};

const clearFilters = () => {
    filters.search = '';
    filters.start_date = '';
    filters.end_date = '';

    loadPayments();
};
</script>

<template>
    <Head title="سجل الدفعات" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400"
                    >
                        المركز المالي
                    </p>

                    <h1
                        class="mt-1 text-2xl font-black text-slate-900 dark:text-white"
                    >
                        سجل الدفعات
                    </h1>

                    <p
                        class="mt-1 text-sm text-slate-500 dark:text-slate-400"
                    >
                        جميع دفعات المبيعات والمشتريات والصيانة
                        المسجلة في النظام.
                    </p>
                </div>

                <Link
                    :href="route('payments.index')"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
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
                            d="M10 19l-7-7m0 0l7-7m-7 7h18"
                        />
                    </svg>

                    العودة إلى مركز الدفعات
                </Link>
            </div>
        </template>

        <div class="space-y-5">
            <!-- الفلاتر -->
            <section
                class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <div
                    class="grid gap-3 lg:grid-cols-[minmax(260px,1fr)_190px_190px_auto]"
                >
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="M21 21l-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z"
                            />
                        </svg>

                        <input
                            v-model="filters.search"
                            type="search"
                            placeholder="اسم العميل أو المورد أو رقم المرجع..."
                            class="w-full rounded-xl border-slate-300 bg-white py-2.5 pr-11 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            @input="applySearch"
                        />
                    </div>

                    <input
                        v-model="filters.start_date"
                        type="date"
                        aria-label="تاريخ البداية"
                        class="rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        @change="applyFilters"
                    />

                    <input
                        v-model="filters.end_date"
                        type="date"
                        aria-label="تاريخ النهاية"
                        class="rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        @change="applyFilters"
                    />

                    <button
                        type="button"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700"
                        @click="clearFilters"
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
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>

                        مسح الفلاتر
                    </button>
                </div>
            </section>

            <!-- الجدول -->
            <section
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <div
                    class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700"
                >
                    <div>
                        <h2
                            class="font-black text-slate-900 dark:text-white"
                        >
                            الدفعات المسجلة
                        </h2>

                        <p
                            class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                        >
                            {{ payments.total || 0 }} دفعة مطابقة
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead
                            class="bg-slate-50 dark:bg-slate-900/60"
                        >
                            <tr>
                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                    التاريخ
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                    نوع العملية
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                    رقم المرجع
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                    العميل أو المورد
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                    المبلغ
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                    طريقة الدفع
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                    الحساب المالي
                                </th>

                                <th class="px-5 py-3 text-right text-xs font-bold text-slate-500">
                                    المرجع البنكي
                                </th>

                                <th class="px-5 py-3 text-center text-xs font-bold text-slate-500">
                                    الإجراء
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-slate-100 dark:divide-slate-700"
                        >
                            <tr
                                v-for="payment in payments.data"
                                :key="`${payment.type}-${payment.id}`"
                                class="transition hover:bg-slate-50 dark:hover:bg-slate-700/40"
                            >
                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-slate-600 dark:text-slate-300"
                                >
                                    {{ formatDateTime(payment.paid_at) }}
                                </td>

                                <td class="whitespace-nowrap px-5 py-4">
                                    <span
                                        class="inline-flex rounded-full border px-3 py-1 text-xs font-bold"
                                        :class="paymentTypeClasses(payment.type)"
                                    >
                                        {{ paymentTypeLabel(payment.type) }}
                                    </span>
                                </td>

                                <td
                                    dir="ltr"
                                    class="whitespace-nowrap px-5 py-4 text-right text-sm font-bold text-slate-900 dark:text-white"
                                >
                                    {{ payment.reference_number || '—' }}
                                </td>

                                <td
                                    class="px-5 py-4 text-sm font-semibold text-slate-800 dark:text-slate-100"
                                >
                                    {{ payment.party_name || 'غير محدد' }}
                                </td>

                                <td
                                    dir="ltr"
                                    class="whitespace-nowrap px-5 py-4 text-right text-sm font-black"
                                    :class="
                                        payment.type === 'purchase'
                                            ? 'text-orange-600 dark:text-orange-400'
                                            : 'text-emerald-600 dark:text-emerald-400'
                                    "
                                >
                                    {{ formatCurrency(payment.amount) }}
                                </td>

                                <td
                                    class="whitespace-nowrap px-5 py-4 text-sm text-slate-600 dark:text-slate-300"
                                >
                                    {{
                                        paymentMethodLabel(
                                            payment.payment_method
                                        )
                                    }}
                                </td>

                                <td
                                    class="px-5 py-4 text-sm text-slate-600 dark:text-slate-300"
                                >
                                    {{
                                        payment.financial_account_name
                                        || payment.bank_or_app_name
                                        || 'غير محدد'
                                    }}
                                </td>

                                <td
                                    dir="ltr"
                                    class="px-5 py-4 text-right text-sm text-slate-500 dark:text-slate-400"
                                >
                                    {{
                                        payment.transaction_reference
                                        || '—'
                                    }}
                                </td>

                                <td class="px-5 py-4 text-center">
                                    <Link
                                        v-if="detailsRoute(payment)"
                                        :href="detailsRoute(payment)"
                                        class="inline-flex items-center justify-center rounded-lg bg-indigo-50 px-3 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-300 dark:hover:bg-indigo-900/50"
                                    >
                                        عرض التفاصيل
                                    </Link>

                                    <span
                                        v-else
                                        class="text-xs text-slate-400"
                                    >
                                        غير متاح
                                    </span>
                                </td>
                            </tr>

                            <tr v-if="!payments.data?.length">
                                <td colspan="9" class="px-6 py-16">
                                    <div class="text-center">
                                        <div
                                            class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-700"
                                        >
                                            <svg
                                                class="h-7 w-7"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="1.6"
                                                    d="M9 14.25l2.25 2.25L15 12.75m-6-4.5h6M6 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6A2.25 2.25 0 016 3.75z"
                                                />
                                            </svg>
                                        </div>

                                        <h3
                                            class="mt-4 font-bold text-slate-900 dark:text-white"
                                        >
                                            لا توجد دفعات مسجلة
                                        </h3>

                                        <p
                                            class="mt-1 text-sm text-slate-500 dark:text-slate-400"
                                        >
                                            لم يتم العثور على دفعات مطابقة
                                            للفلاتر المحددة.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="payments.total > 0"
                    class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700"
                >
                    <p
                        class="text-sm text-slate-500 dark:text-slate-400"
                    >
                        عرض
                        {{ payments.from || 0 }}
                        إلى
                        {{ payments.to || 0 }}
                        من
                        {{ payments.total || 0 }}
                        دفعة
                    </p>

                    <Pagination
                        v-if="payments.links?.length"
                        :links="payments.links"
                    />
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
