<script setup>
import {
    computed,
    ref,
} from 'vue';

import {
    Head,
    Link,
} from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    salesInvoices: {
        type: Array,
        default: () => [],
    },

    purchaseInvoices: {
        type: Array,
        default: () => [],
    },
});

const mode = ref('sales');
const search = ref('');

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

const normalize = (value) => (
    String(value ?? '')
        .trim()
        .toLowerCase()
);

const filteredSales = computed(() => {
    const term =
        normalize(search.value);

    if (!term) {
        return props.salesInvoices;
    }

    return props.salesInvoices.filter(
        (invoice) =>
            [
                invoice.invoice_number,
                invoice.customer?.name,
                invoice.customer_name,
                invoice.customer?.phone,
                invoice.customer_phone,
                ...invoice.items.map(
                    (item) =>
                        item.product?.name
                        || item.product_name
                ),
            ]
                .filter(Boolean)
                .some(
                    (value) =>
                        normalize(value)
                            .includes(term)
                )
    );
});

const filteredPurchases = computed(() => {
    const term =
        normalize(search.value);

    if (!term) {
        return props.purchaseInvoices;
    }

    return props.purchaseInvoices.filter(
        (invoice) =>
            [
                invoice.invoice_number,
                invoice.supplier?.name,
                invoice.supplier?.company_name,
                invoice.supplier_invoice_number,
                ...invoice.items.map(
                    (item) =>
                        item.product?.name
                ),
            ]
                .filter(Boolean)
                .some(
                    (value) =>
                        normalize(value)
                            .includes(term)
                )
    );
});

const activeInvoices = computed(() => (
    mode.value === 'sales'
        ? filteredSales.value
        : filteredPurchases.value
));

const partyName = (invoice) => (
    mode.value === 'sales'
        ? (
            invoice.customer?.name
            || invoice.customer_name
            || 'عميل نقدي'
        )
        : (
            invoice.supplier?.company_name
            || invoice.supplier?.name
            || 'مورد'
        )
);

const invoiceDate = (invoice) => (
    mode.value === 'sales'
        ? invoice.sale_date
        : invoice.purchase_date
);

const createHref = (invoice) => (
    mode.value === 'sales'
        ? route(
            'returns.create-sales',
            invoice.id
        )
        : route(
            'returns.create-purchase',
            invoice.id
        )
);
</script>

<template>
    <Head title="إرجاع جديد" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black text-rose-600 dark:text-rose-400">
                        خطوة 1 من 2
                    </p>

                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                        إنشاء مرتجع جديد
                    </h1>

                    <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">
                        اختر نوع المرتجع أولاً، ثم حدد الفاتورة الأصلية.
                        لا تظهر هنا إلا الفواتير المعتمدة التي ما زالت تحتوي كميات قابلة للإرجاع.
                    </p>
                </div>

                <Link
                    :href="route('returns.index')"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                >
                    العودة للمركز
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <section class="grid gap-4 md:grid-cols-2">
                <button
                    type="button"
                    class="rounded-[26px] border p-5 text-right transition"
                    :class="mode === 'sales'
                        ? 'border-rose-400 bg-rose-50 shadow-lg shadow-rose-500/10 dark:border-rose-700 dark:bg-rose-950/20'
                        : 'border-slate-200 bg-white hover:border-rose-200 dark:border-slate-700 dark:bg-slate-800'"
                    @click="mode = 'sales'; search = ''"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="rounded-full bg-rose-100 px-3 py-1 text-[11px] font-black text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                Customer Return
                            </span>

                            <h2 class="mt-3 text-xl font-black text-slate-950 dark:text-white">
                                مرتجع مبيعات
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                عميل يعيد منتجاً من فاتورة بيع سابقة.
                                يتم تخفيض دين الفاتورة أولاً ثم رد أي مبلغ زائد.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-rose-600 px-3 py-2 text-sm font-black text-white">
                            {{ number(salesInvoices.length) }}
                        </div>
                    </div>
                </button>

                <button
                    type="button"
                    class="rounded-[26px] border p-5 text-right transition"
                    :class="mode === 'purchase'
                        ? 'border-blue-400 bg-blue-50 shadow-lg shadow-blue-500/10 dark:border-blue-700 dark:bg-blue-950/20'
                        : 'border-slate-200 bg-white hover:border-blue-200 dark:border-slate-700 dark:bg-slate-800'"
                    @click="mode = 'purchase'; search = ''"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <span class="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-black text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                Supplier Return
                            </span>

                            <h2 class="mt-3 text-xl font-black text-slate-950 dark:text-white">
                                مرتجع مشتريات
                            </h2>

                            <p class="mt-2 text-sm leading-6 text-slate-500">
                                إعادة منتجات إلى المورد من فاتورة شراء معتمدة،
                                مع خصم الكمية من المخزون وتسوية مستحق المورد.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-blue-600 px-3 py-2 text-sm font-black text-white">
                            {{ number(purchaseInvoices.length) }}
                        </div>
                    </div>
                </button>
            </section>

            <section class="rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                <div class="border-b border-slate-200 p-5 dark:border-slate-700">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="font-black text-slate-950 dark:text-white">
                                اختر الفاتورة الأصلية
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                ابحث برقم الفاتورة أو اسم العميل/المورد أو اسم المنتج.
                            </p>
                        </div>

                        <input
                            v-model.trim="search"
                            type="search"
                            :placeholder="mode === 'sales'
                                ? 'رقم فاتورة، عميل، هاتف أو منتج...'
                                : 'رقم فاتورة، مورد أو منتج...'"
                            class="w-full rounded-xl border-slate-300 bg-white px-4 py-2.5 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white sm:w-80"
                        />
                    </div>
                </div>

                <div class="grid gap-4 p-5 lg:grid-cols-2 2xl:grid-cols-3">
                    <article
                        v-for="invoice in activeInvoices"
                        :key="`${mode}-${invoice.id}`"
                        class="group rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:-translate-y-0.5 hover:border-indigo-300 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-indigo-700 dark:hover:bg-slate-900"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p dir="ltr" class="text-right text-sm font-black text-indigo-700 dark:text-indigo-300">
                                    {{ invoice.invoice_number }}
                                </p>

                                <h3 class="mt-1 font-black text-slate-950 dark:text-white">
                                    {{ partyName(invoice) }}
                                </h3>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ date(invoiceDate(invoice)) }}
                                </p>
                            </div>

                            <span
                                class="rounded-xl px-2.5 py-1 text-[10px] font-black"
                                :class="mode === 'sales'
                                    ? 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300'
                                    : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'"
                            >
                                {{ number(invoice.returnable_quantity) }} قطعة
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="rounded-xl bg-white p-3 dark:bg-slate-800">
                                <p class="text-[10px] text-slate-400">
                                    قيمة الفاتورة
                                </p>

                                <strong dir="ltr" class="mt-1 block text-right text-sm text-slate-900 dark:text-white">
                                    {{ money(invoice.total_amount) }}
                                </strong>
                            </div>

                            <div class="rounded-xl bg-white p-3 dark:bg-slate-800">
                                <p class="text-[10px] text-slate-400">
                                    قيمة قابلة للإرجاع
                                </p>

                                <strong dir="ltr" class="mt-1 block text-right text-sm text-rose-600">
                                    {{ money(invoice.returnable_value) }}
                                </strong>
                            </div>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-1.5">
                            <span
                                v-for="item in invoice.items.filter(item => Number(item.available_quantity || 0) > 0).slice(0, 3)"
                                :key="item.id"
                                class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-300"
                            >
                                {{ item.product?.name || item.product_name }}
                                ×
                                {{ number(item.available_quantity) }}
                            </span>

                            <span
                                v-if="invoice.items.filter(item => Number(item.available_quantity || 0) > 0).length > 3"
                                class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] text-slate-400 dark:bg-slate-700"
                            >
                                + المزيد
                            </span>
                        </div>

                        <Link
                            :href="createHref(invoice)"
                            class="mt-4 inline-flex w-full items-center justify-center rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-black text-white transition group-hover:bg-indigo-600 dark:bg-white dark:text-slate-950 dark:group-hover:bg-indigo-500 dark:group-hover:text-white"
                        >
                            بدء المرتجع
                        </Link>
                    </article>

                    <div
                        v-if="!activeInvoices.length"
                        class="col-span-full rounded-2xl border-2 border-dashed border-slate-200 py-16 text-center dark:border-slate-700"
                    >
                        <p class="font-black text-slate-700 dark:text-slate-200">
                            لا توجد فاتورة مؤهلة مطابقة
                        </p>

                        <p class="mt-2 text-sm text-slate-500">
                            الفاتورة يجب أن تكون معتمدة وأن تحتوي كمية لم يتم إرجاعها سابقاً.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
