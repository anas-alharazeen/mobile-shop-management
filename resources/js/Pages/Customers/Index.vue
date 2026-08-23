<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    customers: {
        type: Object,
        required: true,
    },

    stats: {
        type: Object,
        required: true,
    },

    filters: {
        type: Object,
        default: () => ({}),
    },
});

const filterForm = reactive({
    search:
        props.filters.search
        || '',

    is_active:
        props.filters.is_active === null
        || props.filters.is_active === undefined
            ? ''
            : (
                props.filters.is_active
                    ? '1'
                    : '0'
            ),

    debt_status:
        props.filters.debt_status
        || '',

    sort:
        props.filters.sort
        || 'recent',
});

let searchTimer = null;

const deleteModal = ref({
    show: false,
    customer: null,
    loading: false,
});

const money = (value) => (
    `${Number(value || 0).toLocaleString(
        'ar-PS',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }
    )} شيكل`
);

const number = (value) => (
    Number(value || 0)
        .toLocaleString('ar-PS')
);

const formatDate = (value) => {
    if (!value) {
        return 'لا يوجد نشاط';
    }

    return new Intl.DateTimeFormat(
        'ar-PS',
        {
            dateStyle: 'medium',
        }
    ).format(
        new Date(value)
    );
};

const initials = (customer) => (
    customer.initials
    || customer.name
        ?.trim()
        ?.slice(0, 2)
    || 'ع'
);

const hasRelations = (customer) => (
    Number(
        customer.sales_count
        || 0
    )
    + Number(
        customer.repairs_count
        || 0
    )
    > 0
);

const dueMeta = (customer) => {
    const due =
        Number(
            customer.total_due
            || 0
        );

    if (due <= 0) {
        return {
            label: 'لا يوجد مستحق',
            className:
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
        };
    }

    return {
        label: 'عليه مستحق',
        className:
            'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
    };
};

const applyFilters = () => {
    router.get(
        route(
            'customers.index'
        ),
        {
            search:
                filterForm.search
                || undefined,

            is_active:
                filterForm.is_active === ''
                    ? undefined
                    : filterForm.is_active,

            debt_status:
                filterForm.debt_status
                || undefined,

            sort:
                filterForm.sort
                || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const applySearch = () => {
    clearTimeout(
        searchTimer
    );

    searchTimer =
        setTimeout(
            applyFilters,
            350
        );
};

const clearFilters = () => {
    filterForm.search = '';
    filterForm.is_active = '';
    filterForm.debt_status = '';
    filterForm.sort = 'recent';
    applyFilters();
};

const toggleStatus = (customer) => {
    router.patch(
        route(
            'customers.toggle-status',
            customer.id
        ),
        {},
        {
            preserveScroll: true,
        }
    );
};

const openDeleteModal = (customer) => {
    if (
        hasRelations(
            customer
        )
    ) {
        return;
    }

    deleteModal.value = {
        show: true,
        customer,
        loading: false,
    };
};

const closeDeleteModal = () => {
    if (
        deleteModal.value
            .loading
    ) {
        return;
    }

    deleteModal.value.show =
        false;
};

const deleteCustomer = () => {
    const customer =
        deleteModal.value
            .customer;

    if (!customer) {
        return;
    }

    deleteModal.value.loading =
        true;

    router.delete(
        route(
            'customers.destroy',
            customer.id
        ),
        {
            preserveScroll: true,

            onFinish: () => {
                deleteModal.value.loading =
                    false;
            },

            onSuccess: () => {
                deleteModal.value.show =
                    false;
            },
        }
    );
};
</script>

<template>
    <Head title="إدارة العملاء" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <p
                        class="text-sm font-black text-blue-600 dark:text-blue-400"
                    >
                        CUSTOMERS
                    </p>

                    <h1
                        class="mt-1 text-2xl font-black text-slate-950 dark:text-white"
                    >
                        إدارة العملاء
                    </h1>

                    <p
                        class="mt-1 text-sm text-slate-500 dark:text-slate-400"
                    >
                        العملاء، أرصدتهم، فواتيرهم، وحالة تعاملاتهم من شاشة واحدة.
                    </p>
                </div>

                <div
                    class="flex flex-wrap gap-2"
                >
                    <Link
                        :href="route('payments.customer-receivables')"
                        class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-black text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-300"
                    >
                        مستحقات العملاء
                    </Link>

                    <Link
                        :href="route('customers.create')"
                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-blue-700"
                    >
                        + إضافة عميل
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <section
                class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"
            >
                <article
                    class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <p
                        class="text-xs font-bold text-slate-500"
                    >
                        إجمالي العملاء
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-slate-950 dark:text-white"
                    >
                        {{ number(stats.total) }}
                    </strong>
                </article>

                <article
                    class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20"
                >
                    <p
                        class="text-xs font-bold text-emerald-700 dark:text-emerald-300"
                    >
                        العملاء النشطون
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-emerald-700 dark:text-emerald-300"
                    >
                        {{ number(stats.active) }}
                    </strong>
                </article>

                <article
                    class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20"
                >
                    <p
                        class="text-xs font-bold text-rose-700 dark:text-rose-300"
                    >
                        عملاء عليهم مستحق
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-rose-700 dark:text-rose-300"
                    >
                        {{ number(stats.with_due) }}
                    </strong>
                </article>

                <article
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20"
                >
                    <p
                        class="text-xs font-bold text-amber-700 dark:text-amber-300"
                    >
                        عملاء متأخرون
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-amber-700 dark:text-amber-300"
                    >
                        {{ number(stats.overdue) }}
                    </strong>
                </article>

                <article
                    class="rounded-2xl border border-slate-900 bg-slate-950 p-4 text-white dark:border-slate-700"
                >
                    <p
                        class="text-xs font-bold text-slate-300"
                    >
                        إجمالي المستحقات
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black"
                    >
                        {{ money(stats.total_due) }}
                    </strong>
                </article>
            </section>

            <section
                class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900"
            >
                <div
                    class="grid gap-3 lg:grid-cols-[minmax(0,1.4fr)_170px_190px_160px_auto]"
                >
                    <input
                        v-model="filterForm.search"
                        type="search"
                        placeholder="اسم، كود، هاتف أو بريد..."
                        class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        @input="applySearch"
                    />

                    <select
                        v-model="filterForm.is_active"
                        class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        @change="applyFilters"
                    >
                        <option value="">
                            كل الحالات
                        </option>

                        <option value="1">
                            نشط
                        </option>

                        <option value="0">
                            غير نشط
                        </option>
                    </select>

                    <select
                        v-model="filterForm.debt_status"
                        class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        @change="applyFilters"
                    >
                        <option value="">
                            كل حالات الرصيد
                        </option>

                        <option value="with_due">
                            عليهم مستحقات
                        </option>

                        <option value="without_due">
                            بدون مستحقات
                        </option>

                        <option value="overdue">
                            المتأخرون فقط
                        </option>
                    </select>

                    <select
                        v-model="filterForm.sort"
                        class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        @change="applyFilters"
                    >
                        <option value="recent">
                            الأحدث إضافة
                        </option>

                        <option value="oldest">
                            الأقدم إضافة
                        </option>

                        <option value="name">
                            حسب الاسم
                        </option>
                    </select>

                    <button
                        type="button"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200"
                        @click="clearFilters"
                    >
                        مسح
                    </button>
                </div>
            </section>

            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
            >
                <div
                    class="overflow-x-auto"
                >
                    <table
                        class="min-w-[1100px] w-full divide-y divide-slate-200 text-sm dark:divide-slate-700"
                    >
                        <thead
                            class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-950/40 dark:text-slate-400"
                        >
                            <tr>
                                <th class="px-4 py-3 text-right">
                                    العميل
                                </th>

                                <th class="px-4 py-3 text-right">
                                    التواصل
                                </th>

                                <th class="px-4 py-3 text-center">
                                    التعاملات
                                </th>

                                <th class="px-4 py-3 text-left">
                                    المستحق
                                </th>

                                <th class="px-4 py-3 text-right">
                                    آخر نشاط
                                </th>

                                <th class="px-4 py-3 text-center">
                                    الحالة
                                </th>

                                <th class="px-4 py-3 text-center">
                                    الإجراءات
                                </th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-slate-100 dark:divide-slate-800"
                        >
                            <tr
                                v-for="customer in customers.data"
                                :key="customer.id"
                                class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                            >
                                <td class="px-4 py-4">
                                    <Link
                                        :href="route('customers.show', customer.id)"
                                        class="flex items-center gap-3"
                                    >
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-sm font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                                        >
                                            {{ initials(customer) }}
                                        </div>

                                        <div class="min-w-0">
                                            <strong
                                                class="block truncate text-slate-950 dark:text-white"
                                            >
                                                {{ customer.name }}
                                            </strong>

                                            <span
                                                dir="ltr"
                                                class="mt-1 block text-right text-[10px] font-bold text-slate-400"
                                            >
                                                {{ customer.code }}
                                            </span>
                                        </div>
                                    </Link>
                                </td>

                                <td class="px-4 py-4">
                                    <div
                                        class="space-y-1 text-xs"
                                    >
                                        <a
                                            v-if="customer.phone"
                                            :href="`tel:${customer.phone}`"
                                            dir="ltr"
                                            class="block text-right font-bold text-slate-700 hover:text-blue-600 dark:text-slate-300"
                                        >
                                            {{ customer.phone }}
                                        </a>

                                        <span
                                            v-if="customer.email"
                                            dir="ltr"
                                            class="block max-w-[220px] truncate text-right text-slate-400"
                                        >
                                            {{ customer.email }}
                                        </span>

                                        <span
                                            v-if="!customer.phone && !customer.email"
                                            class="text-slate-400"
                                        >
                                            لا توجد بيانات تواصل
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div
                                        class="flex justify-center gap-2"
                                    >
                                        <span
                                            class="rounded-xl bg-blue-50 px-2.5 py-1.5 text-[10px] font-black text-blue-700 dark:bg-blue-950/20 dark:text-blue-300"
                                        >
                                            {{ number(customer.sales_count) }} بيع
                                        </span>

                                        <span
                                            class="rounded-xl bg-violet-50 px-2.5 py-1.5 text-[10px] font-black text-violet-700 dark:bg-violet-950/20 dark:text-violet-300"
                                        >
                                            {{ number(customer.repairs_count) }} صيانة
                                        </span>

                                        <span
                                            v-if="Number(customer.active_repairs_count || 0) > 0"
                                            class="rounded-xl bg-amber-50 px-2.5 py-1.5 text-[10px] font-black text-amber-700 dark:bg-amber-950/20 dark:text-amber-300"
                                        >
                                            {{ number(customer.active_repairs_count) }} نشط
                                        </span>
                                    </div>
                                </td>

                                <td
                                    dir="ltr"
                                    class="px-4 py-4 text-left"
                                >
                                    <strong
                                        class="block text-base font-black"
                                        :class="
                                            Number(customer.total_due || 0) > 0
                                                ? 'text-rose-600'
                                                : 'text-emerald-600'
                                        "
                                    >
                                        {{ money(customer.total_due) }}
                                    </strong>

                                    <span
                                        v-if="Number(customer.total_due || 0) > 0"
                                        class="mt-1 block text-[10px] text-slate-400"
                                    >
                                        بيع {{ money(customer.sales_due) }}
                                        ·
                                        صيانة {{ money(customer.repair_due) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="text-xs font-bold text-slate-700 dark:text-slate-300"
                                    >
                                        {{ formatDate(customer.last_activity_at) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div
                                        class="flex flex-col items-center gap-1.5"
                                    >
                                        <span
                                            class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                            :class="
                                                customer.is_active
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                                                    : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
                                            "
                                        >
                                            {{ customer.is_active ? 'نشط' : 'غير نشط' }}
                                        </span>

                                        <span
                                            class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                            :class="dueMeta(customer).className"
                                        >
                                            {{ dueMeta(customer).label }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div
                                        class="flex justify-center gap-1"
                                    >
                                        <Link
                                            :href="route('customers.show', customer.id)"
                                            class="rounded-lg px-2.5 py-2 text-xs font-black text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/20"
                                        >
                                            عرض
                                        </Link>

                                        <Link
                                            :href="route('customers.statement', customer.id)"
                                            class="rounded-lg px-2.5 py-2 text-xs font-black text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                                        >
                                            كشف
                                        </Link>

                                        <Link
                                            :href="route('customers.edit', customer.id)"
                                            class="rounded-lg px-2.5 py-2 text-xs font-black text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                                        >
                                            تعديل
                                        </Link>

                                        <button
                                            type="button"
                                            class="rounded-lg px-2.5 py-2 text-xs font-black"
                                            :class="
                                                customer.is_active
                                                    ? 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/20'
                                                    : 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/20'
                                            "
                                            @click="toggleStatus(customer)"
                                        >
                                            {{ customer.is_active ? 'تعطيل' : 'تفعيل' }}
                                        </button>

                                        <button
                                            type="button"
                                            :disabled="hasRelations(customer)"
                                            class="rounded-lg px-2.5 py-2 text-xs font-black transition disabled:cursor-not-allowed disabled:opacity-30"
                                            :class="
                                                hasRelations(customer)
                                                    ? 'text-slate-400'
                                                    : 'text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20'
                                            "
                                            :title="
                                                hasRelations(customer)
                                                    ? 'لا يمكن حذف عميل مرتبط بتعاملات'
                                                    : 'حذف العميل'
                                            "
                                            @click="openDeleteModal(customer)"
                                        >
                                            حذف
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-if="!customers.data.length"
                            >
                                <td
                                    colspan="7"
                                    class="px-6 py-16 text-center"
                                >
                                    <strong
                                        class="block text-sm text-slate-700 dark:text-slate-200"
                                    >
                                        لا توجد نتائج
                                    </strong>

                                    <p
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        جرّب تغيير البحث أو الفلاتر الحالية.
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="flex flex-col gap-3 border-t border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700"
                >
                    <p
                        class="text-xs text-slate-500"
                    >
                        عرض
                        {{ customers.from || 0 }}
                        -
                        {{ customers.to || 0 }}
                        من
                        {{ number(customers.total) }}
                        عميل
                    </p>

                    <Pagination
                        :links="customers.links"
                    />
                </div>
            </section>
        </div>

        <div
            v-if="deleteModal.show"
            class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
            @mousedown.self="closeDeleteModal"
        >
            <section
                class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900"
            >
                <div
                    class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-xl text-rose-600 dark:bg-rose-950/30"
                >
                    !
                </div>

                <h2
                    class="mt-4 text-center text-lg font-black text-slate-950 dark:text-white"
                >
                    حذف العميل
                </h2>

                <p
                    class="mt-2 text-center text-sm leading-6 text-slate-500"
                >
                    سيتم حذف
                    <strong
                        class="text-slate-900 dark:text-white"
                    >
                        {{ deleteModal.customer?.name }}
                    </strong>
                    من قائمة العملاء.
                    لا يظهر زر الحذف أصلًا للعملاء المرتبطين بمبيعات أو صيانة.
                </p>

                <div
                    class="mt-6 grid grid-cols-2 gap-2"
                >
                    <button
                        type="button"
                        :disabled="deleteModal.loading"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200"
                        @click="closeDeleteModal"
                    >
                        إلغاء
                    </button>

                    <button
                        type="button"
                        :disabled="deleteModal.loading"
                        class="rounded-xl bg-rose-600 px-4 py-3 text-sm font-black text-white disabled:opacity-50"
                        @click="deleteCustomer"
                    >
                        {{ deleteModal.loading ? 'جاري الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
