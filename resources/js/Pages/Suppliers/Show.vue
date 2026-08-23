<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    supplier: {
        type: Object,
        required: true,
    },

    summary: {
        type: Object,
        default: () => ({}),
    },

    recentPurchases: {
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
    (
        props.supplier.company_name
        || props.supplier.name
        || 'م'
    )
        .trim()
        .slice(0, 2)
));

const displayName = computed(() => (
    props.supplier.company_name
    || props.supplier.name
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
    <Head :title="displayName" />

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
                                {{ displayName }}
                            </h1>

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
                        </div>

                        <p
                            class="mt-1 text-xs text-slate-400"
                        >
                            {{ supplier.name }}
                            <span dir="ltr">
                                · {{ supplier.code }}
                            </span>
                        </p>
                    </div>
                </div>

                <div
                    class="flex flex-wrap gap-2"
                >
                    <Link
                        :href="route('suppliers.statement', supplier.id)"
                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition hover:bg-blue-700"
                    >
                        كشف الحساب
                    </Link>

                    <Link
                        :href="route('suppliers.edit', supplier.id)"
                        class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:border-blue-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                    >
                        تعديل المورد
                    </Link>

                    <Link
                        :href="route('suppliers.index')"
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
                        صافي المشتريات
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-blue-700 dark:text-blue-300"
                    >
                        {{ money(summary.net_purchases) }}
                    </strong>

                    <p
                        class="mt-1 text-[10px] text-blue-600/70"
                    >
                        {{ number(summary.invoice_count) }} فاتورة معتمدة
                    </p>
                </article>

                <article
                    class="rounded-2xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-950/20"
                >
                    <p
                        class="text-xs font-bold text-orange-700 dark:text-orange-300"
                    >
                        مرتجعات المشتريات
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-orange-700 dark:text-orange-300"
                    >
                        {{ money(summary.purchase_returns) }}
                    </strong>
                </article>

                <article
                    class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900"
                >
                    <p
                        class="text-xs text-slate-500"
                    >
                        إجمالي قبل المرتجعات
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-slate-950 dark:text-white"
                    >
                        {{ money(summary.purchase_gross) }}
                    </strong>
                </article>

                <article
                    class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20"
                >
                    <p
                        class="text-xs font-bold text-rose-700 dark:text-rose-300"
                    >
                        فواتير متأخرة
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black text-rose-700 dark:text-rose-300"
                    >
                        {{ number(summary.overdue_count) }}
                    </strong>
                </article>

                <article
                    class="rounded-2xl border border-slate-900 bg-slate-950 p-4 text-white dark:border-slate-700"
                >
                    <p
                        class="text-xs font-bold text-slate-300"
                    >
                        المستحق للمورد
                    </p>

                    <strong
                        class="mt-2 block text-xl font-black"
                    >
                        {{ money(summary.total_due) }}
                    </strong>
                </article>
            </section>

            <section
                class="grid gap-5 lg:grid-cols-[1fr_1.25fr]"
            >
                <article
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div>
                        <h2
                            class="font-black text-slate-950 dark:text-white"
                        >
                            معلومات المورد
                        </h2>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            معلومات الاتصال والبيانات الأساسية.
                        </p>
                    </div>

                    <div
                        class="mt-5 grid gap-3 sm:grid-cols-2"
                    >
                        <a
                            :href="contactHref('phone', supplier.phone)"
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
                                {{ supplier.phone }}
                            </strong>
                        </a>

                        <a
                            v-if="supplier.whatsapp"
                            :href="contactHref('whatsapp', supplier.whatsapp)"
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
                                {{ supplier.whatsapp }}
                            </strong>
                        </a>

                        <a
                            v-if="supplier.email"
                            :href="contactHref('email', supplier.email)"
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
                                {{ supplier.email }}
                            </strong>
                        </a>

                        <div
                            v-if="supplier.address"
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
                                {{ supplier.address }}
                            </strong>
                        </div>
                    </div>

                    <div
                        v-if="supplier.company_name"
                        class="mt-3 rounded-2xl bg-blue-50 p-4 dark:bg-blue-950/20"
                    >
                        <p
                            class="text-[10px] font-bold text-blue-500"
                        >
                            الشركة
                        </p>

                        <strong
                            class="mt-1 block text-sm text-blue-900 dark:text-blue-200"
                        >
                            {{ supplier.company_name }}
                        </strong>
                    </div>

                    <div
                        v-if="supplier.notes"
                        class="mt-3 rounded-2xl bg-slate-50 p-4 text-sm leading-6 text-slate-600 dark:bg-slate-800/60 dark:text-slate-300"
                    >
                        <p
                            class="mb-1 text-[10px] font-black text-slate-400"
                        >
                            ملاحظات
                        </p>

                        {{ supplier.notes }}
                    </div>

                    <div
                        class="mt-4 flex flex-wrap gap-x-5 gap-y-2 border-t border-slate-100 pt-4 text-[10px] text-slate-400 dark:border-slate-800"
                    >
                        <span>
                            أضيف: {{ formatDate(supplier.created_at) }}
                        </span>

                        <span>
                            آخر تحديث: {{ formatDate(supplier.updated_at) }}
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
                                الوضع المالي للمورد
                            </h2>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                المشتريات والمرتجعات والمبلغ المستحق حاليًا.
                            </p>
                        </div>

                        <Link
                            :href="route('suppliers.statement', supplier.id)"
                            class="text-xs font-black text-blue-600 hover:text-blue-700"
                        >
                            كشف الحساب
                        </Link>
                    </div>

                    <div
                        class="mt-5 grid gap-3 sm:grid-cols-2"
                    >
                        <div
                            class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60"
                        >
                            <p
                                class="text-[10px] text-slate-400"
                            >
                                إجمالي فواتير الشراء
                            </p>

                            <strong
                                class="mt-1 block text-sm dark:text-white"
                            >
                                {{ money(summary.purchase_gross) }}
                            </strong>
                        </div>

                        <div
                            class="rounded-2xl bg-orange-50 p-4 dark:bg-orange-950/20"
                        >
                            <p
                                class="text-[10px] text-orange-500"
                            >
                                المرتجعات
                            </p>

                            <strong
                                class="mt-1 block text-sm text-orange-800 dark:text-orange-200"
                            >
                                - {{ money(summary.purchase_returns) }}
                            </strong>
                        </div>

                        <div
                            class="rounded-2xl bg-blue-50 p-4 dark:bg-blue-950/20"
                        >
                            <p
                                class="text-[10px] text-blue-500"
                            >
                                صافي المشتريات
                            </p>

                            <strong
                                class="mt-1 block text-sm text-blue-800 dark:text-blue-200"
                            >
                                {{ money(summary.net_purchases) }}
                            </strong>
                        </div>

                        <div
                            class="rounded-2xl bg-rose-50 p-4 dark:bg-rose-950/20"
                        >
                            <p
                                class="text-[10px] text-rose-500"
                            >
                                مستحق حالي
                            </p>

                            <strong
                                class="mt-1 block text-sm text-rose-800 dark:text-rose-200"
                            >
                                {{ money(summary.total_due) }}
                            </strong>
                        </div>
                    </div>

                    <div
                        v-if="Number(summary.total_due || 0) > 0"
                        class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200"
                    >
                        لدى هذا المورد رصيد مستحق بقيمة
                        <strong>
                            {{ money(summary.total_due) }}
                        </strong>.
                        يمكنك تسجيل دفعة من صفحة مستحقات الموردين.
                    </div>

                    <Link
                        v-if="Number(summary.total_due || 0) > 0"
                        :href="route('payments.supplier-payables', { supplier_id: supplier.id })"
                        class="mt-3 block rounded-xl bg-blue-600 px-4 py-3 text-center text-sm font-black text-white hover:bg-blue-700"
                    >
                        فتح مستحقات المورد
                    </Link>
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
                            آخر فواتير الشراء
                        </h2>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            أحدث الفواتير المعتمدة من هذا المورد.
                        </p>
                    </div>

                    <Link
                        :href="route('payments.supplier-payables', { supplier_id: supplier.id })"
                        class="text-xs font-black text-blue-600"
                    >
                        مستحقات المورد
                    </Link>
                </div>

                <div
                    v-if="recentPurchases.length"
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
                                <th class="px-4 py-3 text-right">فاتورة المورد</th>
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
                                v-for="invoice in recentPurchases"
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
                                    dir="ltr"
                                    class="px-4 py-4 text-right text-slate-500"
                                >
                                    {{ invoice.supplier_invoice_number || '—' }}
                                </td>

                                <td
                                    class="px-4 py-4 text-slate-500"
                                >
                                    {{ formatDate(invoice.purchase_date) }}
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
                                        :href="route('purchases.show', invoice.id)"
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
                    لا توجد فواتير شراء معتمدة لهذا المورد.
                </div>
            </section>

            <section
                class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
            >
                <div
                    class="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700"
                >
                    <div>
                        <h2
                            class="font-black text-slate-950 dark:text-white"
                        >
                            مرتجعات المشتريات
                        </h2>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            آخر المرتجعات المعتمدة لهذا المورد.
                        </p>
                    </div>

                    <Link
                        :href="route('returns.index', { tab: 'purchase' })"
                        class="text-xs font-black text-blue-600"
                    >
                        مركز المرتجعات
                    </Link>
                </div>

                <div
                    v-if="recentReturns.length"
                    class="divide-y divide-slate-100 dark:divide-slate-800"
                >
                    <article
                        v-for="item in recentReturns"
                        :key="item.id"
                        class="grid gap-3 p-4 sm:grid-cols-[1fr_auto]"
                    >
                        <div>
                            <strong
                                class="text-sm text-slate-950 dark:text-white"
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

                            <p
                                v-if="item.reason"
                                class="mt-2 text-xs text-slate-500"
                            >
                                {{ item.reason }}
                            </p>
                        </div>

                        <div
                            class="text-left"
                        >
                            <strong
                                dir="ltr"
                                class="block text-sm text-orange-600"
                            >
                                {{ money(item.total_amount) }}
                            </strong>

                            <span
                                class="mt-1 block text-[10px] text-slate-400"
                            >
                                خُصم من الدين {{ money(item.amount_used_for_debt) }}
                            </span>

                            <span
                                class="mt-1 block text-[10px] text-emerald-600"
                            >
                                مسترد من المورد {{ money(item.amount_refunded) }}
                            </span>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="px-6 py-12 text-center text-sm text-slate-500"
                >
                    لا توجد مرتجعات مشتريات معتمدة لهذا المورد.
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
