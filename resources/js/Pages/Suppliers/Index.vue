<script setup>
import { reactive, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    suppliers: {
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
    supplier: null,
    loading: false,
});

const displayName = (supplier) => (
    supplier.company_name
    || supplier.name
);

const initials = (supplier) => (
    displayName(supplier)
        ?.trim()
        ?.slice(0, 2)
    || 'م'
);

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

const hasRelations = (supplier) => (
    Number(
        supplier.invoices_count
        || 0
    ) > 0
);

const dueMeta = (supplier) => {
    const due =
        Number(
            supplier.total_due
            || 0
        );

    if (due <= 0) {
        return {
            label: 'لا يوجد مستحق',
            className:
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
        };
    }

    if (
        Number(
            supplier.overdue_invoices_count
            || 0
        ) > 0
    ) {
        return {
            label: 'متأخر',
            className:
                'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
        };
    }

    return {
        label: 'عليه مستحق',
        className:
            'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
    };
};

const applyFilters = () => {
    router.get(
        route(
            'suppliers.index'
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

const toggleStatus = (supplier) => {
    router.patch(
        route(
            'suppliers.toggle-status',
            supplier.id
        ),
        {},
        {
            preserveScroll: true,
        }
    );
};

const openDeleteModal = (supplier) => {
    if (
        hasRelations(
            supplier
        )
    ) {
        return;
    }

    deleteModal.value = {
        show: true,
        supplier,
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

const deleteSupplier = () => {
    const supplier =
        deleteModal.value
            .supplier;

    if (!supplier) {
        return;
    }

    deleteModal.value.loading =
        true;

    router.delete(
        route(
            'suppliers.destroy',
            supplier.id
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
    <Head title="إدارة الموردين" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <p
                        class="text-sm font-black text-blue-600 dark:text-blue-400"
                    >
                        SUPPLIERS
                    </p>

                    <h1
                        class="mt-1 text-2xl font-black text-slate-950 dark:text-white"
                    >
                        إدارة الموردين
                    </h1>

                    <p
                        class="mt-1 text-sm text-slate-500 dark:text-slate-400"
                    >
                        المشتريات، المستحقات، الفواتير المتأخرة، وآخر تعامل مع كل مورد.
                    </p>
                </div>

                <div
                    class="flex flex-wrap gap-2"
                >
                    <Link
                        :href="route('payments.supplier-payables')"
                        class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-black text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-300"
                    >
                        مستحقات الموردين
                    </Link>

                    <Link
                        :href="route('suppliers.create')"
                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-blue-700"
                    >
                        + إضافة مورد
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
                        إجمالي الموردين
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
                        الموردون النشطون
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-emerald-700 dark:text-emerald-300"
                    >
                        {{ number(stats.active) }}
                    </strong>
                </article>

                <article
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20"
                >
                    <p
                        class="text-xs font-bold text-amber-700 dark:text-amber-300"
                    >
                        موردون لهم مستحق
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-amber-700 dark:text-amber-300"
                    >
                        {{ number(stats.with_due) }}
                    </strong>
                </article>

                <article
                    class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20"
                >
                    <p
                        class="text-xs font-bold text-rose-700 dark:text-rose-300"
                    >
                        موردون متأخرون
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-rose-700 dark:text-rose-300"
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
                        إجمالي المستحق للموردين
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
                        placeholder="اسم المورد، الشركة، الكود، الهاتف..."
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
                            كل حالات المستحق
                        </option>

                        <option value="with_due">
                            لهم مستحقات
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
                <div class="overflow-x-auto">
                    <table
                        class="min-w-[1080px] w-full divide-y divide-slate-200 text-sm dark:divide-slate-700"
                    >
                        <thead
                            class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-950/40 dark:text-slate-400"
                        >
                            <tr>
                                <th class="px-4 py-3 text-right">
                                    المورد
                                </th>

                                <th class="px-4 py-3 text-right">
                                    التواصل
                                </th>

                                <th class="px-4 py-3 text-center">
                                    الفواتير
                                </th>

                                <th class="px-4 py-3 text-left">
                                    المستحق
                                </th>

                                <th class="px-4 py-3 text-right">
                                    آخر شراء
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
                                v-for="supplier in suppliers.data"
                                :key="supplier.id"
                                class="transition hover:bg-slate-50/80 dark:hover:bg-slate-800/40"
                            >
                                <td class="px-4 py-4">
                                    <Link
                                        :href="route('suppliers.show', supplier.id)"
                                        class="flex items-center gap-3"
                                    >
                                        <div
                                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-sm font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                                        >
                                            {{ initials(supplier) }}
                                        </div>

                                        <div class="min-w-0">
                                            <strong
                                                class="block truncate text-slate-950 dark:text-white"
                                            >
                                                {{ displayName(supplier) }}
                                            </strong>

                                            <span
                                                class="mt-1 block truncate text-[10px] text-slate-400"
                                            >
                                                {{ supplier.name }}
                                                ·
                                                <span dir="ltr">
                                                    {{ supplier.code }}
                                                </span>
                                            </span>
                                        </div>
                                    </Link>
                                </td>

                                <td class="px-4 py-4">
                                    <div
                                        class="space-y-1 text-xs"
                                    >
                                        <a
                                            :href="`tel:${supplier.phone}`"
                                            dir="ltr"
                                            class="block text-right font-bold text-slate-700 hover:text-blue-600 dark:text-slate-300"
                                        >
                                            {{ supplier.phone }}
                                        </a>

                                        <span
                                            v-if="supplier.email"
                                            dir="ltr"
                                            class="block max-w-[220px] truncate text-right text-slate-400"
                                        >
                                            {{ supplier.email }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div
                                        class="inline-flex items-center gap-2"
                                    >
                                        <span
                                            class="rounded-xl bg-blue-50 px-2.5 py-1.5 text-[10px] font-black text-blue-700 dark:bg-blue-950/20 dark:text-blue-300"
                                        >
                                            {{ number(supplier.invoices_count) }} فاتورة
                                        </span>

                                        <span
                                            v-if="Number(supplier.overdue_invoices_count || 0) > 0"
                                            class="rounded-xl bg-rose-50 px-2.5 py-1.5 text-[10px] font-black text-rose-700 dark:bg-rose-950/20 dark:text-rose-300"
                                        >
                                            {{ number(supplier.overdue_invoices_count) }} متأخرة
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
                                            Number(supplier.total_due || 0) > 0
                                                ? 'text-rose-600'
                                                : 'text-emerald-600'
                                        "
                                    >
                                        {{ money(supplier.total_due) }}
                                    </strong>
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="text-xs font-bold text-slate-700 dark:text-slate-300"
                                    >
                                        {{ formatDate(supplier.last_activity_at) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div
                                        class="flex flex-col items-center gap-1.5"
                                    >
                                        <span
                                            class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                            :class="
                                                supplier.is_active
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                                                    : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'
                                            "
                                        >
                                            {{ supplier.is_active ? 'نشط' : 'غير نشط' }}
                                        </span>

                                        <span
                                            class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                            :class="dueMeta(supplier).className"
                                        >
                                            {{ dueMeta(supplier).label }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div
                                        class="flex justify-center gap-1"
                                    >
                                        <Link
                                            :href="route('suppliers.show', supplier.id)"
                                            class="rounded-lg px-2.5 py-2 text-xs font-black text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/20"
                                        >
                                            عرض
                                        </Link>

                                        <Link
                                            :href="route('suppliers.statement', supplier.id)"
                                            class="rounded-lg px-2.5 py-2 text-xs font-black text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                                        >
                                            كشف
                                        </Link>

                                        <Link
                                            :href="route('suppliers.edit', supplier.id)"
                                            class="rounded-lg px-2.5 py-2 text-xs font-black text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800"
                                        >
                                            تعديل
                                        </Link>

                                        <button
                                            type="button"
                                            class="rounded-lg px-2.5 py-2 text-xs font-black"
                                            :class="
                                                supplier.is_active
                                                    ? 'text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/20'
                                                    : 'text-emerald-600 hover:bg-emerald-50 dark:hover:bg-emerald-950/20'
                                            "
                                            @click="toggleStatus(supplier)"
                                        >
                                            {{ supplier.is_active ? 'تعطيل' : 'تفعيل' }}
                                        </button>

                                        <button
                                            type="button"
                                            :disabled="hasRelations(supplier)"
                                            class="rounded-lg px-2.5 py-2 text-xs font-black transition disabled:cursor-not-allowed disabled:opacity-30"
                                            :class="
                                                hasRelations(supplier)
                                                    ? 'text-slate-400'
                                                    : 'text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20'
                                            "
                                            :title="
                                                hasRelations(supplier)
                                                    ? 'لا يمكن حذف مورد مرتبط بفواتير شراء'
                                                    : 'حذف المورد'
                                            "
                                            @click="openDeleteModal(supplier)"
                                        >
                                            حذف
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-if="!suppliers.data.length"
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
                                        جرّب تغيير البحث أو الفلاتر.
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
                        {{ suppliers.from || 0 }}
                        -
                        {{ suppliers.to || 0 }}
                        من
                        {{ number(suppliers.total) }}
                        مورد
                    </p>

                    <Pagination
                        :links="suppliers.links"
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
                    حذف المورد
                </h2>

                <p
                    class="mt-2 text-center text-sm leading-6 text-slate-500"
                >
                    سيتم حذف
                    <strong
                        class="text-slate-900 dark:text-white"
                    >
                        {{ displayName(deleteModal.supplier || {}) }}
                    </strong>.
                    المورد المرتبط بفواتير شراء لا يمكن حذفه من هذه الشاشة.
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
                        @click="deleteSupplier"
                    >
                        {{ deleteModal.loading ? 'جاري الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
