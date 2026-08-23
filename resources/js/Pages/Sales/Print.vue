<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { usePrint } from '@/composables/usePrint';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },

    store: {
        type: Object,
        default: () => ({}),
    },

    invoiceSettings: {
        type: Object,
        default: () => ({}),
    },
});

const { printPage } = usePrint();

const money = (value) => {
    return `${Number(value || 0).toLocaleString('ar', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`;
};

const date = (value) => {
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

const methodLabel = (value) => {
    const normalizedValue =
        typeof value === 'object' && value !== null
            ? value.value
            : value;

    return {
        cash: 'كاش',
        bank_transfer: 'تحويل بنكي',
        banking_app: 'تطبيق بنكي',
    }[normalizedValue] ?? normalizedValue ?? 'غير محدد';
};

const customerName = () => {
    return (
        props.invoice.customer?.name
        || props.invoice.customer_name
        || 'عميل نقدي'
    );
};

const customerPhone = () => {
    return (
        props.invoice.customer?.phone
        || props.invoice.customer_phone
        || 'لا يوجد رقم هاتف'
    );
};

const totalDiscount = () => {
    return (
        Number(props.invoice.items_discount || 0)
        + Number(props.invoice.invoice_discount || 0)
    );
};
</script>

<template>
    <Head :title="`فاتورة ${invoice.invoice_number}`" />

    <main
        dir="rtl"
        class="print-page min-h-screen bg-slate-100 p-4 text-slate-900 sm:p-8 print:min-h-0 print:bg-white print:p-0"
    >
        <article
            class="mx-auto max-w-4xl overflow-hidden rounded-3xl bg-white shadow-xl print:max-w-none print:rounded-none print:shadow-none"
        >
            <!-- رأس الفاتورة -->
            <header
                class="relative overflow-hidden bg-gradient-to-l from-indigo-800 via-indigo-700 to-blue-600 p-7 text-white print:bg-indigo-800"
            >
                <div
                    class="absolute -left-16 -top-20 h-48 w-48 rounded-full bg-white/10 blur-3xl print:hidden"
                ></div>

                <div
                    class="absolute -bottom-24 right-1/3 h-52 w-52 rounded-full bg-cyan-300/10 blur-3xl print:hidden"
                ></div>

                <div
                    class="relative flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/20 bg-white/15 shadow-lg backdrop-blur"
                        >
                            <img
                                v-if="store.store_logo"
                                :src="`/storage/${store.store_logo}`"
                                alt="شعار المعرض"
                                class="h-full w-full object-contain p-2"
                            />

                            <svg
                                v-else
                                class="h-9 w-9"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M7 2h10a2 2 0 012 2v16a2 2 0 01-2 2H7a2 2 0 01-2-2V4a2 2 0 012-2zm5 17h.01"
                                />
                            </svg>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-indigo-100">
                                نظام إدارة المبيعات والمخزون
                            </p>

                            <h1 class="mt-1 text-2xl font-black sm:text-3xl">
                                {{ store.store_name || 'فنانة فون' }}
                            </h1>

                            <p class="mt-1 text-sm text-indigo-100">
                                فاتورة بيع رسمية
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-white/15 bg-white/10 px-5 py-4 text-right backdrop-blur sm:text-left"
                    >
                        <p class="text-xs text-indigo-100">
                            رقم الفاتورة
                        </p>

                        <p
                            dir="ltr"
                            class="mt-1 text-lg font-black tracking-wide"
                        >
                            {{ invoice.invoice_number }}
                        </p>

                        <p class="mt-3 text-xs text-indigo-100">
                            تاريخ البيع
                        </p>

                        <p dir="ltr" class="mt-1 text-sm font-semibold">
                            {{ date(invoice.sale_date) }}
                        </p>
                    </div>
                </div>
            </header>

            <div class="space-y-6 p-5 sm:p-7">
                <!-- بيانات العميل والمعرض -->
                <section
                    class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:grid-cols-2 print:bg-slate-50"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700"
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
                                        d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0"
                                    />
                                </svg>
                            </span>

                            <p class="text-xs font-semibold text-slate-500">
                                بيانات العميل
                            </p>
                        </div>

                        <p class="mt-3 text-base font-bold text-slate-900">
                            {{ customerName() }}
                        </p>

                        <p
                            dir="ltr"
                            class="mt-1 text-right text-sm text-slate-500"
                        >
                            {{ customerPhone() }}
                        </p>
                    </div>

                    <div class="sm:text-left">
                        <div
                            class="flex items-center gap-2 sm:flex-row-reverse"
                        >
                            <span
                                class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700"
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
                                        d="M3 21h18M5 21V7l7-4 7 4v14M9 10h.01M15 10h.01M9 14h.01M15 14h.01"
                                    />
                                </svg>
                            </span>

                            <p class="text-xs font-semibold text-slate-500">
                                بيانات المعرض
                            </p>
                        </div>

                        <p
                            v-if="store.store_phone"
                            dir="ltr"
                            class="mt-3 text-right text-sm font-semibold text-slate-800 sm:text-left"
                        >
                            {{ store.store_phone }}
                        </p>

                        <p
                            v-if="store.store_address"
                            class="mt-1 text-sm leading-6 text-slate-500"
                        >
                            {{ store.store_address }}
                        </p>
                    </div>
                </section>

                <!-- جدول المنتجات -->
                <section
                    class="overflow-hidden rounded-2xl border border-slate-200"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-4"
                    >
                        <div>
                            <h2 class="font-bold text-slate-900">
                                المنتجات المباعة
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                تفاصيل الأصناف والكميات والأسعار
                            </p>
                        </div>

                        <span
                            class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700"
                        >
                            {{ invoice.items?.length || 0 }} صنف
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[680px] text-sm">
                            <thead
                                class="bg-slate-900 text-xs font-semibold text-white"
                            >
                                <tr>
                                    <th class="px-4 py-3 text-right">
                                        المنتج
                                    </th>

                                    <th class="px-4 py-3 text-center">
                                        الكمية
                                    </th>

                                    <th class="px-4 py-3 text-left">
                                        سعر الوحدة
                                    </th>

                                    <th class="px-4 py-3 text-left">
                                        الخصم
                                    </th>

                                    <th class="px-4 py-3 text-left">
                                        الإجمالي
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <tr
                                    v-for="item in invoice.items"
                                    :key="item.id"
                                    class="transition hover:bg-slate-50"
                                >
                                    <td class="px-4 py-4">
                                        <p class="font-bold text-slate-900">
                                            {{
                                                item.product_name
                                                || item.product?.name
                                                || 'منتج غير معروف'
                                            }}
                                        </p>

                                        <span
                                            dir="ltr"
                                            class="mt-1 block text-right text-xs font-normal text-slate-400"
                                        >
                                            {{
                                                item.product_code
                                                || item.product?.code
                                                || '—'
                                            }}
                                        </span>
                                    </td>

                                    <td
                                        class="px-4 py-4 text-center font-bold text-slate-700"
                                    >
                                        {{ item.quantity }}
                                    </td>

                                    <td
                                        dir="ltr"
                                        class="whitespace-nowrap px-4 py-4 text-left"
                                    >
                                        {{ money(item.unit_selling_price) }}
                                    </td>

                                    <td
                                        dir="ltr"
                                        class="whitespace-nowrap px-4 py-4 text-left text-rose-600"
                                    >
                                        {{
                                            money(
                                                Number(
                                                    item.line_discount || 0
                                                )
                                                + Number(
                                                    item.allocated_invoice_discount
                                                    || 0
                                                )
                                            )
                                        }}
                                    </td>

                                    <td
                                        dir="ltr"
                                        class="whitespace-nowrap px-4 py-4 text-left font-black text-slate-900"
                                    >
                                        {{ money(item.line_total) }}
                                    </td>
                                </tr>

                                <tr v-if="!invoice.items?.length">
                                    <td
                                        colspan="5"
                                        class="px-4 py-10 text-center text-slate-500"
                                    >
                                        لا توجد منتجات مسجلة في الفاتورة.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <!-- الملاحظات والإجماليات -->
                <section
                    class="grid gap-5 md:grid-cols-[1fr_340px]"
                >
                    <div class="space-y-4">
                        <div
                            v-if="invoice.notes"
                            class="rounded-2xl border border-slate-200 p-5 text-sm"
                        >
                            <div class="flex items-center gap-2">
                                <svg
                                    class="h-5 w-5 text-indigo-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M8.25 6.75h7.5M8.25 10.5h7.5M8.25 14.25h4.5M6 3.75h12A2.25 2.25 0 0120.25 6v12A2.25 2.25 0 0118 20.25H6A2.25 2.25 0 013.75 18V6A2.25 2.25 0 016 3.75z"
                                    />
                                </svg>

                                <p class="font-bold">
                                    ملاحظات الفاتورة
                                </p>
                            </div>

                            <p class="mt-3 leading-7 text-slate-600">
                                {{ invoice.notes }}
                            </p>
                        </div>

                        <div
                            v-if="invoiceSettings.return_policy"
                            class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-950"
                        >
                            <div class="flex items-center gap-2">
                                <svg
                                    class="h-5 w-5 text-amber-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M12 9v3.75m9-1.5a9 9 0 11-18 0 9 9 0 0118 0zM12 16.5h.008v.008H12V16.5z"
                                    />
                                </svg>

                                <p class="font-bold">
                                    سياسة الاستبدال والمرتجعات
                                </p>
                            </div>

                            <p class="mt-3 leading-7">
                                {{ invoiceSettings.return_policy }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
                    >
                        <div
                            class="border-b border-slate-200 bg-slate-50 px-5 py-4"
                        >
                            <h2 class="font-bold text-slate-900">
                                ملخص الفاتورة
                            </h2>
                        </div>

                        <div class="space-y-3 p-5 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">
                                    الإجمالي قبل الخصم
                                </span>

                                <span
                                    dir="ltr"
                                    class="font-semibold text-slate-800"
                                >
                                    {{ money(invoice.subtotal) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between">
                                <span class="text-slate-500">
                                    إجمالي الخصومات
                                </span>

                                <span
                                    dir="ltr"
                                    class="font-semibold text-rose-600"
                                >
                                    -{{ money(totalDiscount()) }}
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between border-t border-slate-200 pt-4"
                            >
                                <strong class="text-base">
                                    الإجمالي النهائي
                                </strong>

                                <strong
                                    dir="ltr"
                                    class="text-lg text-indigo-700"
                                >
                                    {{ money(invoice.total_amount) }}
                                </strong>
                            </div>

                            <div
                                class="flex items-center justify-between rounded-xl bg-emerald-50 px-3 py-2.5"
                            >
                                <span class="font-medium text-emerald-800">
                                    المدفوع
                                </span>

                                <span
                                    dir="ltr"
                                    class="font-black text-emerald-700"
                                >
                                    {{ money(invoice.paid_amount) }}
                                </span>
                            </div>

                            <div
                                class="flex items-center justify-between rounded-xl bg-amber-50 px-3 py-2.5"
                            >
                                <span class="font-medium text-amber-900">
                                    المتبقي
                                </span>

                                <span
                                    dir="ltr"
                                    class="font-black text-amber-700"
                                >
                                    {{ money(invoice.remaining_amount) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- طرق الدفع -->
                <section
                    v-if="invoice.payments?.length"
                    class="rounded-2xl border border-slate-200 p-5"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-bold text-slate-900">
                                الدفعات المسجلة
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                طرق الدفع والمبالغ المحصلة
                            </p>
                        </div>

                        <span
                            class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700"
                        >
                            {{ invoice.payments.length }} دفعة
                        </span>
                    </div>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <span
                            v-for="payment in invoice.payments"
                            :key="payment.id"
                            class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-700"
                        >
                            {{ methodLabel(payment.payment_method) }}

                            <span class="mx-1 text-slate-300">
                                •
                            </span>

                            <span dir="ltr">
                                {{ money(payment.amount) }}
                            </span>
                        </span>
                    </div>
                </section>

                <!-- التذييل -->
                <footer
                    class="border-t border-slate-200 pt-5 text-center text-xs leading-6 text-slate-500"
                >
                    <p class="font-semibold text-slate-700">
                        {{
                            invoiceSettings.invoice_footer
                            || store.footer_text
                            || 'شكراً لثقتكم بفنانة فون'
                        }}
                    </p>

                    <p class="mt-1">
                        هذه الفاتورة صادرة آلياً من نظام
                        {{ store.store_name || 'فنانة فون' }}.
                    </p>
                </footer>

                <!-- الأزرار -->
                <div
                    class="flex flex-col justify-center gap-3 sm:flex-row print:hidden"
                >
                    <button
                        type="button"
                        @click="printPage"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200"
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
                                d="M6.75 9V3.75h10.5V9m-10.5 8.25H5.25A2.25 2.25 0 013 15V9.75A2.25 2.25 0 015.25 7.5h13.5A2.25 2.25 0 0121 9.75V15a2.25 2.25 0 01-2.25 2.25h-1.5M6.75 13.5h10.5v6.75H6.75V13.5z"
                            />
                        </svg>

                        طباعة الفاتورة
                    </button>

                    <Link
                        :href="route('sales.show', invoice.id)"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-6 py-3 font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-100"
                    >
                        العودة إلى الفاتورة
                    </Link>
                </div>
            </div>
        </article>
    </main>
</template>

<style>
@page {
    size: A4;
    margin: 9mm;
}

@media print {
    html,
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print-page {
        width: 100% !important;
        min-height: auto !important;
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    article {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    table {
        page-break-inside: auto;
    }

    thead {
        display: table-header-group;
    }

    tr,
    td,
    th {
        page-break-inside: avoid;
    }

    section {
        page-break-inside: avoid;
    }

    a[href]::after {
        content: none !important;
    }
}
</style>
