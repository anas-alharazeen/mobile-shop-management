<script setup>
import {
    computed,
    reactive,
    ref,
} from 'vue';

import {
    Head,
    Link,
    router,
    useForm,
} from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    salesReturns: {
        type: Object,
        required: true,
    },

    purchaseReturns: {
        type: Object,
        required: true,
    },

    exchanges: {
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
});

const activeTab = ref(
    props.filters?.tab || 'sales'
);

const query = reactive({
    search:
        props.filters?.search || '',

    status:
        props.filters?.status || '',
});

const cancelModal = ref({
    show: false,
    type: null,
    item: null,
});

const cancelForm = useForm({
    reason: '',
});

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

    return new Intl.DateTimeFormat(
        'ar-PS-u-nu-latn',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }
    ).format(
        new Date(value)
    );
};

const statusLabel = (status) => ({
    draft: 'مسودة',
    approved: 'معتمد',
    cancelled: 'ملغي',
}[status] || status);

const statusClass = (status) => ({
    draft:
        'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-950/30 dark:text-blue-300 dark:ring-blue-900/50',

    approved:
        'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50',

    cancelled:
        'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50',
}[status] || 'bg-slate-100 text-slate-700');

const settlementLabel = (type) => ({
    customer_pays:
        'العميل يدفع الفرق',

    customer_gets_refund:
        'رد فرق للعميل',

    no_difference:
        'بدون فرق',
}[type] || type || '—');

const tabs = computed(() => [
    {
        key: 'sales',
        label: 'مرتجعات المبيعات',
        count:
            props.salesReturns?.total || 0,
    },
    {
        key: 'purchase',
        label: 'مرتجعات المشتريات',
        count:
            props.purchaseReturns?.total || 0,
    },
    {
        key: 'exchange',
        label: 'الاستبدالات',
        count:
            props.exchanges?.total || 0,
    },
]);

const statsCards = computed(() => [
    {
        label:
            'مرتجعات مبيعات اليوم',
        value:
            money(
                props.stats.sales_today
            ),
        tone:
            'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
    },
    {
        label:
            'مرتجعات مشتريات الشهر',
        value:
            money(
                props.stats.purchase_month
            ),
        tone:
            'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300',
    },
    {
        label:
            'مبالغ رُدت للعملاء',
        value:
            money(
                props.stats
                    .refunded_to_customers
            ),
        tone:
            'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    },
    {
        label:
            'مبالغ استُردت من الموردين',
        value:
            money(
                props.stats
                    .refunded_from_suppliers
            ),
        tone:
            'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
    },
    {
        label:
            'عمليات الاستبدال',
        value:
            number(
                props.stats.exchanges_count
            ),
        tone:
            'bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300',
    },
    {
        label:
            'مسودات بانتظار المراجعة',
        value:
            number(
                props.stats.drafts_count
            ),
        tone:
            'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    },
]);

const applyFilters = () => {
    router.get(
        route('returns.index'),
        {
            tab:
                activeTab.value,
            search:
                query.search || undefined,
            status:
                query.status || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const clearFilters = () => {
    query.search = '';
    query.status = '';
    applyFilters();
};

const approveSales = (item) => {
    if (
        ! window.confirm(
            `اعتماد المرتجع ${item.return_number} وتنفيذ أثره المالي والمخزني؟`
        )
    ) {
        return;
    }

    router.post(
        route(
            'returns.approve-sales',
            item.id
        ),
        {},
        {
            preserveScroll: true,
        }
    );
};

const approvePurchase = (item) => {
    if (
        ! window.confirm(
            `اعتماد المرتجع ${item.return_number} وخصم الكميات من المخزون؟`
        )
    ) {
        return;
    }

    router.post(
        route(
            'returns.approve-purchase',
            item.id
        ),
        {},
        {
            preserveScroll: true,
        }
    );
};

const openCancel = (
    item,
    type
) => {
    cancelForm.reset();

    cancelModal.value = {
        show: true,
        type,
        item,
    };
};

const closeCancel = () => {
    cancelModal.value.show = false;
    cancelModal.value.item = null;
    cancelModal.value.type = null;
    cancelForm.reset();
};

const submitCancel = () => {
    if (
        ! cancelModal.value.item
        || ! cancelForm.reason.trim()
    ) {
        return;
    }

    const isSales =
        cancelModal.value.type
        === 'sales';

    const routeName =
        isSales
            ? 'returns.cancel-sales'
            : 'returns.cancel-purchase';

    cancelForm.post(
        route(
            routeName,
            cancelModal.value.item.id
        ),
        {
            preserveScroll: true,
            onSuccess:
                closeCancel,
        }
    );
};
</script>

<template>
    <Head title="المرتجعات والاستبدال" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-rose-50 px-3 py-1 text-[11px] font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">
                            Returns Center
                        </span>
                        <span class="rounded-full bg-indigo-50 px-3 py-1 text-[11px] font-black text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300">
                            مخزون + مالية + فواتير
                        </span>
                    </div>

                    <h1 class="mt-2 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                        المرتجعات والاستبدال
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        إدارة مرتجعات العملاء والموردين والاستبدالات من شاشة واحدة،
                        مع تسوية الديون والحسابات المالية والمخزون بشكل مترابط.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('returns.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-rose-600/20 transition hover:bg-rose-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7H8a5 5 0 000 10h8m-3-3 3 3-3 3" />
                        </svg>
                        إرجاع جديد
                    </Link>

                    <Link
                        :href="route('returns.create-exchange')"
                        class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-violet-600/20 transition hover:bg-violet-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4" />
                        </svg>
                        استبدال جديد
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <section class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6">
                <article
                    v-for="card in statsCards"
                    :key="card.label"
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div class="inline-flex rounded-xl px-2.5 py-1 text-[10px] font-black" :class="card.tone">
                        {{ card.label }}
                    </div>

                    <p dir="ltr" class="mt-3 text-right text-lg font-black text-slate-950 dark:text-white">
                        {{ card.value }}
                    </p>
                </article>
            </section>

            <section class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="rounded-xl px-4 py-2.5 text-xs font-black transition"
                            :class="activeTab === tab.key
                                ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-300'"
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                            <span
                                dir="ltr"
                                class="mr-1 opacity-70"
                            >
                                {{ number(tab.count) }}
                            </span>
                        </button>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <input
                            v-model.trim="query.search"
                            type="search"
                            placeholder="رقم مرتجع، فاتورة، عميل أو مورد..."
                            class="w-full rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white sm:w-72"
                            @keyup.enter="applyFilters"
                        />

                        <select
                            v-model="query.status"
                            class="rounded-xl border-slate-300 bg-white px-3 py-2.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            @change="applyFilters"
                        >
                            <option value="">كل الحالات</option>
                            <option value="draft">مسودة</option>
                            <option value="approved">معتمد</option>
                            <option value="cancelled">ملغي</option>
                        </select>

                        <button
                            type="button"
                            class="rounded-xl bg-indigo-600 px-4 py-2.5 text-xs font-black text-white"
                            @click="applyFilters"
                        >
                            بحث
                        </button>

                        <button
                            v-if="query.search || query.status"
                            type="button"
                            class="rounded-xl border border-slate-300 px-4 py-2.5 text-xs font-black text-slate-600 dark:border-slate-600 dark:text-slate-300"
                            @click="clearFilters"
                        >
                            مسح
                        </button>
                    </div>
                </div>
            </section>

            <!-- SALES RETURNS -->
            <section
                v-if="activeTab === 'sales'"
                class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black text-slate-950 dark:text-white">
                        مرتجعات المبيعات
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        الإرجاع يقلل دين العميل أولاً، ثم يُرد أي مبلغ زائد من الحساب المالي المحدد.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1100px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">المرتجع</th>
                                <th class="px-4 py-3 text-right">العميل / الفاتورة</th>
                                <th class="px-4 py-3 text-right">القيمة</th>
                                <th class="px-4 py-3 text-right">خفض الدين</th>
                                <th class="px-4 py-3 text-right">المسترد</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">الحالة</th>
                                <th class="px-4 py-3 text-center">الإجراءات</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr
                                v-for="item in salesReturns.data"
                                :key="item.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-700/30"
                            >
                                <td dir="ltr" class="px-4 py-3 text-right font-black text-rose-700 dark:text-rose-300">
                                    {{ item.return_number }}
                                </td>

                                <td class="px-4 py-3">
                                    <strong class="block text-slate-900 dark:text-white">
                                        {{ item.customer?.name || item.invoice?.customer_name || 'عميل نقدي' }}
                                    </strong>
                                    <span dir="ltr" class="mt-1 block text-right text-xs text-slate-400">
                                        {{ item.invoice?.invoice_number || '—' }}
                                    </span>
                                </td>

                                <td dir="ltr" class="px-4 py-3 text-right font-black">
                                    {{ money(item.total_amount) }}
                                </td>

                                <td dir="ltr" class="px-4 py-3 text-right text-amber-600">
                                    {{ money(item.amount_used_for_debt) }}
                                </td>

                                <td dir="ltr" class="px-4 py-3 text-right text-rose-600">
                                    {{ money(item.amount_refunded) }}
                                </td>

                                <td class="px-4 py-3">{{ date(item.return_date) }}</td>

                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 ring-inset"
                                        :class="statusClass(item.status)"
                                    >
                                        {{ statusLabel(item.status) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-1.5">
                                        <button
                                            v-if="item.status === 'draft'"
                                            type="button"
                                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-black text-white"
                                            @click="approveSales(item)"
                                        >
                                            اعتماد
                                        </button>

                                        <Link
                                            :href="route('returns.print-sales', item.id)"
                                            target="_blank"
                                            class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                                        >
                                            طباعة
                                        </Link>

                                        <button
                                            v-if="item.status !== 'cancelled' && !item.exchange"
                                            type="button"
                                            class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                                            @click="openCancel(item, 'sales')"
                                        >
                                            إلغاء
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!salesReturns.data.length">
                                <td colspan="8" class="py-14 text-center text-slate-500">
                                    لا توجد مرتجعات مبيعات مطابقة.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                    <Pagination :links="salesReturns.links" />
                </div>
            </section>

            <!-- PURCHASE RETURNS -->
            <section
                v-if="activeTab === 'purchase'"
                class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black text-slate-950 dark:text-white">
                        مرتجعات المشتريات
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        المرتجع يقلل مستحق المورد أولاً، ثم يسجل أي مبلغ مسترد كحركة واردة للحساب المالي.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1100px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">المرتجع</th>
                                <th class="px-4 py-3 text-right">المورد / الفاتورة</th>
                                <th class="px-4 py-3 text-right">القيمة</th>
                                <th class="px-4 py-3 text-right">خفض المستحق</th>
                                <th class="px-4 py-3 text-right">المسترد</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">الحالة</th>
                                <th class="px-4 py-3 text-center">الإجراءات</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr
                                v-for="item in purchaseReturns.data"
                                :key="item.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-700/30"
                            >
                                <td dir="ltr" class="px-4 py-3 text-right font-black text-blue-700 dark:text-blue-300">
                                    {{ item.return_number }}
                                </td>

                                <td class="px-4 py-3">
                                    <strong class="block text-slate-900 dark:text-white">
                                        {{ item.supplier?.company_name || item.supplier?.name || '—' }}
                                    </strong>
                                    <span dir="ltr" class="mt-1 block text-right text-xs text-slate-400">
                                        {{ item.invoice?.invoice_number || '—' }}
                                    </span>
                                </td>

                                <td dir="ltr" class="px-4 py-3 text-right font-black">
                                    {{ money(item.total_amount) }}
                                </td>

                                <td dir="ltr" class="px-4 py-3 text-right text-amber-600">
                                    {{ money(item.amount_used_for_debt) }}
                                </td>

                                <td dir="ltr" class="px-4 py-3 text-right text-emerald-600">
                                    {{ money(item.amount_refunded) }}
                                </td>

                                <td class="px-4 py-3">{{ date(item.return_date) }}</td>

                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-black ring-1 ring-inset"
                                        :class="statusClass(item.status)"
                                    >
                                        {{ statusLabel(item.status) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex justify-center gap-1.5">
                                        <button
                                            v-if="item.status === 'draft'"
                                            type="button"
                                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-black text-white"
                                            @click="approvePurchase(item)"
                                        >
                                            اعتماد
                                        </button>

                                        <Link
                                            :href="route('returns.print-purchase', item.id)"
                                            target="_blank"
                                            class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                                        >
                                            طباعة
                                        </Link>

                                        <button
                                            v-if="item.status !== 'cancelled'"
                                            type="button"
                                            class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                                            @click="openCancel(item, 'purchase')"
                                        >
                                            إلغاء
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="!purchaseReturns.data.length">
                                <td colspan="8" class="py-14 text-center text-slate-500">
                                    لا توجد مرتجعات مشتريات مطابقة.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                    <Pagination :links="purchaseReturns.links" />
                </div>
            </section>

            <!-- EXCHANGES -->
            <section
                v-if="activeTab === 'exchange'"
                class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black text-slate-950 dark:text-white">
                        عمليات الاستبدال
                    </h2>
                    <p class="mt-1 text-xs text-slate-500">
                        كل عملية تربط مرتجع المبيعات بفاتورة جديدة وتسوية فرق السعر.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-[1000px] w-full text-sm">
                        <thead class="bg-slate-950 text-white">
                            <tr>
                                <th class="px-4 py-3 text-right">الاستبدال</th>
                                <th class="px-4 py-3 text-right">العميل</th>
                                <th class="px-4 py-3 text-right">المرتجع</th>
                                <th class="px-4 py-3 text-right">الفاتورة الجديدة</th>
                                <th class="px-4 py-3 text-right">رصيد الاستبدال</th>
                                <th class="px-4 py-3 text-right">فرق السعر</th>
                                <th class="px-4 py-3 text-right">التسوية</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            <tr
                                v-for="item in exchanges.data"
                                :key="item.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-700/30"
                            >
                                <td dir="ltr" class="px-4 py-3 text-right font-black text-violet-700 dark:text-violet-300">
                                    {{ item.exchange_number }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ item.sales_return?.customer?.name || item.sales_return?.invoice?.customer_name || 'عميل نقدي' }}
                                </td>

                                <td dir="ltr" class="px-4 py-3 text-right text-slate-500">
                                    {{ item.sales_return?.return_number || '—' }}
                                </td>

                                <td dir="ltr" class="px-4 py-3 text-right text-slate-500">
                                    {{ item.new_invoice?.invoice_number || '—' }}
                                </td>

                                <td dir="ltr" class="px-4 py-3 text-right font-black">
                                    {{ money(item.exchange_credit) }}
                                </td>

                                <td
                                    dir="ltr"
                                    class="px-4 py-3 text-right font-black"
                                    :class="Number(item.price_difference || 0) > 0
                                        ? 'text-emerald-600'
                                        : Number(item.price_difference || 0) < 0
                                            ? 'text-rose-600'
                                            : 'text-slate-500'"
                                >
                                    {{ money(item.price_difference) }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ settlementLabel(item.settlement_type) }}
                                </td>

                                <td class="px-4 py-3">{{ date(item.exchanged_at) }}</td>

                                <td class="px-4 py-3">
                                    <Link
                                        :href="route('returns.print-exchange', item.id)"
                                        target="_blank"
                                        class="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200"
                                    >
                                        طباعة
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="!exchanges.data.length">
                                <td colspan="9" class="py-14 text-center text-slate-500">
                                    لا توجد عمليات استبدال مطابقة.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 p-4 dark:border-slate-700">
                    <Pagination :links="exchanges.links" />
                </div>
            </section>
        </div>

        <Modal
            :show="cancelModal.show"
            @close="closeCancel"
        >
            <div class="p-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/30 dark:text-rose-300">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M5 19h14L12 5 5 19z" />
                    </svg>
                </div>

                <h3 class="mt-4 text-lg font-black text-slate-950 dark:text-white">
                    إلغاء
                    {{ cancelModal.type === 'sales' ? 'مرتجع المبيعات' : 'مرتجع المشتريات' }}
                </h3>

                <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                    إذا كان المرتجع معتمداً فسيتم عكس أثر المخزون والديون والحركة المالية تلقائياً.
                </p>

                <textarea
                    v-model="cancelForm.reason"
                    rows="3"
                    placeholder="اكتب سبب الإلغاء..."
                    class="mt-4 block w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                ></textarea>

                <p
                    v-if="cancelForm.errors.reason"
                    class="mt-1 text-xs font-bold text-rose-600"
                >
                    {{ cancelForm.errors.reason }}
                </p>

                <div class="mt-5 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black dark:border-slate-600 dark:text-slate-200"
                        @click="closeCancel"
                    >
                        تراجع
                    </button>

                    <button
                        type="button"
                        :disabled="cancelForm.processing || !cancelForm.reason.trim()"
                        class="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-black text-white disabled:opacity-50"
                        @click="submitCancel"
                    >
                        {{ cancelForm.processing ? 'جاري الإلغاء...' : 'تأكيد الإلغاء' }}
                    </button>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
