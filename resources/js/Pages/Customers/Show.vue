<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    customer: {
        type: Object,
        required: true,
    },

    summary: {
        type: Object,
        default: () => ({}),
    },

    recentSales: {
        type: Array,
        default: () => [],
    },

    recentRepairs: {
        type: Array,
        default: () => [],
    },

    recentReturns: {
        type: Array,
        default: () => [],
    },
});

const today = new Date()
    .toISOString()
    .slice(0, 10);

const initials = computed(() => (
    props.customer.name
        ?.trim()
        ?.slice(0, 2)
        || 'ع'
));

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

const dateOnly = (value) => (
    value
        ? String(value)
            .slice(0, 10)
        : null
);

const formatDate = (value) => {
    if (!value) {
        return '—';
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

const isOverdue = (
    dueDate,
    remaining
) => {
    const date =
        dateOnly(dueDate);

    return Boolean(
        date
        && Number(remaining || 0) > 0
        && date < today
    );
};

const paymentStatusMeta = (status) => {
    const value =
        typeof status === 'object'
            ? status?.value
            : status;

    return {
        paid: {
            label: 'مدفوع',
            className:
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
        },

        partial: {
            label: 'جزئي',
            className:
                'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
        },

        unpaid: {
            label: 'غير مدفوع',
            className:
                'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
        },
    }[value] || {
        label: value || '—',
        className:
            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    };
};

const repairStatusMeta = (status) => {
    const value =
        typeof status === 'object'
            ? status?.value
            : status;

    return {
        received: {
            label: 'مستلم',
            className:
                'bg-blue-100 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300',
        },

        in_progress: {
            label: 'قيد الصيانة',
            className:
                'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
        },

        ready: {
            label: 'جاهز',
            className:
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
        },

        delivered: {
            label: 'تم التسليم',
            className:
                'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        },
    }[value] || {
        label: value || '—',
        className:
            'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    };
};

const contactHref = (type, value) => {
    if (!value) {
        return null;
    }

    if (type === 'phone') {
        return `tel:${value}`;
    }

    if (type === 'email') {
        return `mailto:${value}`;
    }

    if (type === 'whatsapp') {
        const digits =
            String(value)
                .replace(
                    /\D/g,
                    ''
                );

        return digits
            ? `https://wa.me/${digits}`
            : null;
    }

    return null;
};
</script>

<template>
    <Head :title="customer.name" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <div
                    class="flex min-w-0 items-center gap-4"
                >
                    <div
                        class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-lg font-black text-white shadow-lg shadow-blue-600/20"
                    >
                        {{ initials }}
                    </div>

                    <div class="min-w-0">
                        <div
                            class="flex flex-wrap items-center gap-2"
                        >
                            <h1
                                class="truncate text-2xl font-black text-slate-950 dark:text-white"
                            >
                                {{ customer.name }}
                            </h1>

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
                        </div>

                        <p
                            dir="ltr"
                            class="mt-1 text-right text-xs font-bold text-slate-400"
                        >
                            {{ customer.code }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-wrap gap-2"
                >
                    <Link
                        :href="route('customers.statement', customer.id)"
                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-blue-700"
                    >
                        كشف الحساب
                    </Link>

                    <Link
                        :href="route('customers.edit', customer.id)"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-blue-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                    >
                        تعديل العميل
                    </Link>

                    <Link
                        :href="route('customers.index')"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200"
                    >
                        رجوع
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <section
                class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5"
            >
                <article
                    class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20"
                >
                    <p
                        class="text-xs font-bold text-blue-700 dark:text-blue-300"
                    >
                        صافي المبيعات
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-blue-700 dark:text-blue-300"
                    >
                        {{ money(summary.net_sales) }}
                    </strong>

                    <p
                        class="mt-1 text-[10px] text-blue-600/70 dark:text-blue-300/70"
                    >
                        {{ number(summary.sales_count) }} فاتورة معتمدة
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-950/20"
                >
                    <p
                        class="text-xs font-bold text-orange-700 dark:text-orange-300"
                    >
                        مرتجعات المبيعات
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-orange-700 dark:text-orange-300"
                    >
                        {{ money(summary.sales_returns) }}
                    </strong>

                    <p
                        class="mt-1 text-[10px] text-orange-600/70"
                    >
                        تخصم من إجمالي مبيعات العميل
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900/50 dark:bg-violet-950/20"
                >
                    <p
                        class="text-xs font-bold text-violet-700 dark:text-violet-300"
                    >
                        قيمة الصيانة
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-violet-700 dark:text-violet-300"
                    >
                        {{ money(summary.repair_total) }}
                    </strong>

                    <p
                        class="mt-1 text-[10px] text-violet-600/70"
                    >
                        {{ number(summary.active_repairs) }} طلب نشط الآن
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20"
                >
                    <p
                        class="text-xs font-bold text-rose-700 dark:text-rose-300"
                    >
                        مستحق المبيعات
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-rose-700 dark:text-rose-300"
                    >
                        {{ money(summary.sales_due) }}
                    </strong>
                </article>

                <article
                    class="rounded-2xl border border-slate-900 bg-slate-950 p-4 text-white dark:border-slate-700"
                >
                    <p
                        class="text-xs font-bold text-slate-300"
                    >
                        إجمالي المستحق
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black"
                    >
                        {{ money(summary.total_due) }}
                    </strong>

                    <p
                        class="mt-1 text-[10px] text-slate-400"
                    >
                        منها صيانة {{ money(summary.repair_due) }}
                    </p>
                </article>
            </section>

            <section
                class="grid gap-5 lg:grid-cols-[1fr_1.25fr]"
            >
                <article
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between gap-3"
                    >
                        <div>
                            <h2
                                class="font-black text-slate-950 dark:text-white"
                            >
                                معلومات العميل
                            </h2>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                بيانات التواصل والملاحظات الأساسية.
                            </p>
                        </div>

                        <span
                            dir="ltr"
                            class="rounded-full bg-slate-100 px-3 py-1 text-[10px] font-black text-slate-500 dark:bg-slate-800 dark:text-slate-300"
                        >
                            {{ customer.code }}
                        </span>
                    </div>

                    <div
                        class="mt-5 grid gap-3 sm:grid-cols-2"
                    >
                        <a
                            v-if="customer.phone"
                            :href="contactHref('phone', customer.phone)"
                            class="rounded-2xl border border-slate-200 p-4 transition hover:border-blue-300 hover:bg-blue-50/50 dark:border-slate-700 dark:hover:border-blue-800 dark:hover:bg-blue-950/20"
                        >
                            <p
                                class="text-[10px] font-bold text-slate-400"
                            >
                                الهاتف
                            </p>

                            <strong
                                dir="ltr"
                                class="mt-1 block text-right text-sm text-slate-950 dark:text-white"
                            >
                                {{ customer.phone }}
                            </strong>
                        </a>

                        <a
                            v-if="customer.whatsapp"
                            :href="contactHref('whatsapp', customer.whatsapp)"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="rounded-2xl border border-slate-200 p-4 transition hover:border-emerald-300 hover:bg-emerald-50/50 dark:border-slate-700 dark:hover:border-emerald-800 dark:hover:bg-emerald-950/20"
                        >
                            <p
                                class="text-[10px] font-bold text-slate-400"
                            >
                                واتساب
                            </p>

                            <strong
                                dir="ltr"
                                class="mt-1 block text-right text-sm text-emerald-700 dark:text-emerald-300"
                            >
                                {{ customer.whatsapp }}
                            </strong>
                        </a>

                        <a
                            v-if="customer.email"
                            :href="contactHref('email', customer.email)"
                            class="rounded-2xl border border-slate-200 p-4 transition hover:border-blue-300 hover:bg-blue-50/50 dark:border-slate-700 dark:hover:border-blue-800 dark:hover:bg-blue-950/20"
                        >
                            <p
                                class="text-[10px] font-bold text-slate-400"
                            >
                                البريد الإلكتروني
                            </p>

                            <strong
                                dir="ltr"
                                class="mt-1 block break-all text-right text-sm text-slate-950 dark:text-white"
                            >
                                {{ customer.email }}
                            </strong>
                        </a>

                        <div
                            v-if="customer.address"
                            class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700"
                        >
                            <p
                                class="text-[10px] font-bold text-slate-400"
                            >
                                العنوان
                            </p>

                            <strong
                                class="mt-1 block text-sm text-slate-950 dark:text-white"
                            >
                                {{ customer.address }}
                            </strong>
                        </div>
                    </div>

                    <div
                        v-if="customer.notes"
                        class="mt-3 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600 dark:bg-slate-800/60 dark:text-slate-300"
                    >
                        <p
                            class="mb-1 text-[10px] font-black text-slate-400"
                        >
                            ملاحظات
                        </p>

                        {{ customer.notes }}
                    </div>

                    <div
                        class="mt-4 flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-100 pt-4 text-[10px] text-slate-400 dark:border-slate-800"
                    >
                        <span>
                            أضيف: {{ formatDate(customer.created_at) }}
                        </span>

                        <span>
                            آخر تحديث: {{ formatDate(customer.updated_at) }}
                        </span>
                    </div>
                </article>

                <article
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between gap-3"
                    >
                        <div>
                            <h2
                                class="font-black text-slate-950 dark:text-white"
                            >
                                نظرة مالية سريعة
                            </h2>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                مقارنة الإجمالي والمرتجعات والرصيد الحالي.
                            </p>
                        </div>

                        <Link
                            :href="route('customers.statement', customer.id)"
                            class="text-xs font-black text-blue-600 hover:text-blue-700"
                        >
                            فتح كشف الحساب
                        </Link>
                    </div>

                    <div
                        class="mt-5 space-y-4"
                    >
                        <div>
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <span
                                    class="text-slate-500"
                                >
                                    إجمالي المبيعات قبل المرتجعات
                                </span>

                                <strong
                                    class="text-slate-950 dark:text-white"
                                >
                                    {{ money(summary.sales_gross) }}
                                </strong>
                            </div>

                            <div
                                class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                            >
                                <div
                                    class="h-full rounded-full bg-blue-600"
                                    style="width: 100%"
                                />
                            </div>
                        </div>

                        <div>
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <span
                                    class="text-slate-500"
                                >
                                    المرتجعات
                                </span>

                                <strong
                                    class="text-orange-600"
                                >
                                    - {{ money(summary.sales_returns) }}
                                </strong>
                            </div>
                        </div>

                        <div
                            class="grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-2 dark:border-slate-800"
                        >
                            <div
                                class="rounded-2xl bg-blue-50 p-4 dark:bg-blue-950/20"
                            >
                                <p
                                    class="text-[10px] text-blue-500"
                                >
                                    صافي المبيعات
                                </p>

                                <strong
                                    class="mt-1 block text-sm text-blue-800 dark:text-blue-200"
                                >
                                    {{ money(summary.net_sales) }}
                                </strong>
                            </div>

                            <div
                                class="rounded-2xl bg-rose-50 p-4 dark:bg-rose-950/20"
                            >
                                <p
                                    class="text-[10px] text-rose-500"
                                >
                                    الرصيد المستحق حاليًا
                                </p>

                                <strong
                                    class="mt-1 block text-sm text-rose-800 dark:text-rose-200"
                                >
                                    {{ money(summary.total_due) }}
                                </strong>
                            </div>
                        </div>
                    </div>
                </article>
            </section>

            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
            >
                <div
                    class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700"
                >
                    <div>
                        <h2
                            class="font-black text-slate-950 dark:text-white"
                        >
                            آخر فواتير البيع
                        </h2>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            أحدث الفواتير المعتمدة لهذا العميل.
                        </p>
                    </div>

                    <Link
                        :href="route('payments.customer-receivables', { customer_id: customer.id })"
                        class="text-xs font-black text-blue-600"
                    >
                        عرض المستحقات
                    </Link>
                </div>

                <div
                    v-if="recentSales.length"
                    class="overflow-x-auto"
                >
                    <table
                        class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700"
                    >
                        <thead
                            class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-950/40 dark:text-slate-400"
                        >
                            <tr>
                                <th class="px-4 py-3 text-right">الفاتورة</th>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-left">الإجمالي</th>
                                <th class="px-4 py-3 text-left">المتبقي</th>
                                <th class="px-4 py-3 text-center">الدفع</th>
                                <th class="px-4 py-3 text-center">عرض</th>
                            </tr>
                        </thead>

                        <tbody
                            class="divide-y divide-slate-100 dark:divide-slate-800"
                        >
                            <tr
                                v-for="invoice in recentSales"
                                :key="invoice.id"
                            >
                                <td
                                    class="px-4 py-4 font-black text-slate-950 dark:text-white"
                                >
                                    {{ invoice.invoice_number }}

                                    <span
                                        v-if="isOverdue(invoice.due_date, invoice.remaining_amount)"
                                        class="mt-1 block text-[10px] font-black text-rose-600"
                                    >
                                        متأخرة
                                    </span>
                                </td>

                                <td
                                    class="px-4 py-4 text-slate-500"
                                >
                                    {{ formatDate(invoice.sale_date) }}
                                </td>

                                <td
                                    dir="ltr"
                                    class="px-4 py-4 text-left font-bold dark:text-white"
                                >
                                    {{ money(invoice.total_amount) }}
                                </td>

                                <td
                                    dir="ltr"
                                    class="px-4 py-4 text-left font-black"
                                    :class="
                                        Number(invoice.remaining_amount || 0) > 0
                                            ? 'text-rose-600'
                                            : 'text-emerald-600'
                                    "
                                >
                                    {{ money(invoice.remaining_amount) }}
                                </td>

                                <td
                                    class="px-4 py-4 text-center"
                                >
                                    <span
                                        class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                        :class="paymentStatusMeta(invoice.payment_status).className"
                                    >
                                        {{ paymentStatusMeta(invoice.payment_status).label }}
                                    </span>
                                </td>

                                <td
                                    class="px-4 py-4 text-center"
                                >
                                    <Link
                                        :href="route('sales.show', invoice.id)"
                                        class="text-xs font-black text-blue-600 hover:text-blue-700"
                                    >
                                        فتح
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-else
                    class="px-6 py-12 text-center text-sm text-slate-500"
                >
                    لا توجد فواتير بيع معتمدة لهذا العميل.
                </div>
            </section>

            <section
                class="grid gap-5 xl:grid-cols-2"
            >
                <article
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="border-b border-slate-200 px-5 py-4 dark:border-slate-700"
                    >
                        <h2
                            class="font-black text-slate-950 dark:text-white"
                        >
                            طلبات الصيانة الأخيرة
                        </h2>
                    </div>

                    <div
                        v-if="recentRepairs.length"
                        class="divide-y divide-slate-100 dark:divide-slate-800"
                    >
                        <Link
                            v-for="order in recentRepairs"
                            :key="order.id"
                            :href="route('repairs.show', order.id)"
                            class="flex items-center justify-between gap-3 p-4 transition hover:bg-slate-50 dark:hover:bg-slate-800/50"
                        >
                            <div class="min-w-0">
                                <strong
                                    class="block truncate text-sm text-slate-950 dark:text-white"
                                >
                                    {{ order.order_number }}
                                </strong>

                                <span
                                    class="mt-1 block truncate text-xs text-slate-500"
                                >
                                    {{ [order.brand, order.model, order.device_type].filter(Boolean).join(' · ') || 'جهاز غير محدد' }}
                                </span>
                            </div>

                            <div
                                class="shrink-0 text-left"
                            >
                                <span
                                    class="rounded-full px-2 py-1 text-[10px] font-black"
                                    :class="repairStatusMeta(order.status).className"
                                >
                                    {{ repairStatusMeta(order.status).label }}
                                </span>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-xs text-slate-700 dark:text-slate-300"
                                >
                                    {{ money(order.remaining_amount) }}
                                </strong>
                            </div>
                        </Link>
                    </div>

                    <div
                        v-else
                        class="px-6 py-12 text-center text-sm text-slate-500"
                    >
                        لا توجد طلبات صيانة لهذا العميل.
                    </div>
                </article>

                <article
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700"
                    >
                        <h2
                            class="font-black text-slate-950 dark:text-white"
                        >
                            آخر المرتجعات
                        </h2>

                        <Link
                            :href="route('returns.index', { tab: 'sales' })"
                            class="text-xs font-black text-blue-600"
                        >
                            مركز المرتجعات
                        </Link>
                    </div>

                    <div
                        v-if="recentReturns.length"
                        class="divide-y divide-slate-100 dark:divide-slate-800"
                    >
                        <div
                            v-for="item in recentReturns"
                            :key="item.id"
                            class="p-4"
                        >
                            <div
                                class="flex items-start justify-between gap-3"
                            >
                                <div>
                                    <strong
                                        class="block text-sm text-slate-950 dark:text-white"
                                    >
                                        {{ item.return_number }}
                                    </strong>

                                    <p
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        {{ item.invoice?.invoice_number || '—' }}
                                        ·
                                        {{ formatDate(item.return_date) }}
                                    </p>
                                </div>

                                <strong
                                    dir="ltr"
                                    class="text-sm text-orange-600"
                                >
                                    {{ money(item.total_amount) }}
                                </strong>
                            </div>

                            <div
                                class="mt-3 grid grid-cols-2 gap-2 text-[10px]"
                            >
                                <div
                                    class="rounded-xl bg-slate-50 p-2 dark:bg-slate-800"
                                >
                                    خُصم من الدين:
                                    <strong
                                        dir="ltr"
                                        class="mr-1"
                                    >
                                        {{ money(item.amount_used_for_debt) }}
                                    </strong>
                                </div>

                                <div
                                    class="rounded-xl bg-slate-50 p-2 dark:bg-slate-800"
                                >
                                    مسترد نقدًا:
                                    <strong
                                        dir="ltr"
                                        class="mr-1"
                                    >
                                        {{ money(item.amount_refunded) }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="px-6 py-12 text-center text-sm text-slate-500"
                    >
                        لا توجد مرتجعات معتمدة لهذا العميل.
                    </div>
                </article>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
