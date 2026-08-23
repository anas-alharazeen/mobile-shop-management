<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePrint } from '@/composables/usePrint';

const props = defineProps({
    customer: { type: Object, required: true },
    transactions: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
});

const { printPage } = usePrint();

const typeMeta = {
    sale: {
        label: 'فاتورة بيع',
        className: 'bg-blue-100 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300',
    },
    sale_payment: {
        label: 'دفعة بيع',
        className: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
    },
    sales_return: {
        label: 'مرتجع مبيعات',
        className: 'bg-orange-100 text-orange-700 dark:bg-orange-950/30 dark:text-orange-300',
    },
    sales_refund: {
        label: 'رد نقدي للعميل',
        className: 'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300',
    },
    repair: {
        label: 'صيانة',
        className: 'bg-violet-100 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300',
    },
    repair_payment: {
        label: 'دفعة صيانة',
        className: 'bg-teal-100 text-teal-700 dark:bg-teal-950/30 dark:text-teal-300',
    },
};

const metaFor = (type) => (
    typeMeta[type] || {
        label: type,
        className: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
    }
);

const hasDifference = computed(() => (
    Math.abs(Number(props.summary.balance_difference || 0)) > 0.01
));

const money = (value) => (
    `${Number(value || 0).toLocaleString('ar-PS', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`
);

const date = (value) => (
    value
        ? new Intl.DateTimeFormat('ar-PS', { dateStyle: 'medium' }).format(new Date(value))
        : '—'
);
</script>

<template>
    <Head :title="`كشف حساب ${customer.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-black text-blue-600 dark:text-blue-400">
                        كشف حساب العميل
                    </p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        {{ customer.name }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ customer.code }}
                        <span v-if="customer.phone">· {{ customer.phone }}</span>
                    </p>
                </div>

                <div class="flex gap-2 print:hidden">
                    <Link
                        :href="route('customers.show', customer.id)"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200"
                    >
                        العودة للعميل
                    </Link>
                    <button
                        type="button"
                        class="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white hover:bg-blue-700"
                        @click="printPage"
                    >
                        طباعة الكشف
                    </button>
                </div>
            </div>
        </template>

        <div class="space-y-5">
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-xs text-slate-500">صافي المبيعات</p>
                    <strong class="mt-2 block text-lg font-black dark:text-white">
                        {{ money(summary.total_sales) }}
                    </strong>
                </article>

                <article class="rounded-2xl border border-violet-200 bg-violet-50 p-4 dark:border-violet-900/50 dark:bg-violet-950/20">
                    <p class="text-xs text-violet-700 dark:text-violet-300">الصيانة</p>
                    <strong class="mt-2 block text-lg font-black text-violet-700 dark:text-violet-300">
                        {{ money(summary.total_repairs) }}
                    </strong>
                </article>

                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                    <p class="text-xs text-emerald-700 dark:text-emerald-300">صافي المدفوع</p>
                    <strong class="mt-2 block text-lg font-black text-emerald-700 dark:text-emerald-300">
                        {{ money(summary.total_paid) }}
                    </strong>
                </article>

                <article class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20">
                    <p class="text-xs text-rose-700 dark:text-rose-300">مستحق مبيعات</p>
                    <strong class="mt-2 block text-lg font-black text-rose-700 dark:text-rose-300">
                        {{ money(summary.sales_due) }}
                    </strong>
                </article>

                <article class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20">
                    <p class="text-xs text-blue-700 dark:text-blue-300">الرصيد المستحق</p>
                    <strong class="mt-2 block text-lg font-black text-blue-700 dark:text-blue-300">
                        {{ money(summary.balance) }}
                    </strong>
                    <p
                        v-if="Number(summary.repair_due || 0) > 0"
                        class="mt-1 text-[10px] text-blue-600"
                    >
                        منها صيانة: {{ money(summary.repair_due) }}
                    </p>
                </article>
            </section>

            <section
                v-if="hasDifference"
                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200"
            >
                <strong>تنبيه مراجعة بيانات:</strong>
                الرصيد الحالي في الفواتير هو {{ money(summary.balance) }}
                بينما إعادة بناء كشف التعاملات تعطي {{ money(summary.ledger_balance) }}.
                يوجد فرق {{ money(summary.balance_difference) }} يحتاج مراجعة بيانات قديمة.
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">سجل التعاملات</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        الفواتير والدفعات والمرتجعات مرتبة زمنياً مع الرصيد التراكمي.
                    </p>
                </div>

                <div v-if="transactions.length" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-950/40 dark:text-slate-400">
                            <tr>
                                <th class="px-4 py-3 text-right">التاريخ</th>
                                <th class="px-4 py-3 text-right">النوع</th>
                                <th class="px-4 py-3 text-right">المرجع</th>
                                <th class="px-4 py-3 text-right">البيان</th>
                                <th class="px-4 py-3 text-left">مدين</th>
                                <th class="px-4 py-3 text-left">دائن</th>
                                <th class="px-4 py-3 text-left">الرصيد</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="(item, index) in transactions"
                                :key="`${item.type}-${item.reference}-${index}`"
                            >
                                <td class="whitespace-nowrap px-4 py-4 text-slate-500">
                                    {{ date(item.date) }}
                                </td>
                                <td class="px-4 py-4">
                                    <span
                                        class="rounded-full px-2.5 py-1 text-xs font-bold"
                                        :class="metaFor(item.type).className"
                                    >
                                        {{ metaFor(item.type).label }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 font-bold dark:text-white">
                                    {{ item.reference }}
                                </td>
                                <td class="px-4 py-4 text-slate-600 dark:text-slate-300">
                                    {{ item.description }}
                                </td>
                                <td dir="ltr" class="px-4 py-4 text-left font-bold text-rose-600">
                                    {{ Number(item.debit || 0) > 0 ? money(item.debit) : '—' }}
                                </td>
                                <td dir="ltr" class="px-4 py-4 text-left font-bold text-emerald-600">
                                    {{ Number(item.credit || 0) > 0 ? money(item.credit) : '—' }}
                                </td>
                                <td dir="ltr" class="px-4 py-4 text-left font-black dark:text-white">
                                    {{ money(item.balance) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="px-6 py-14 text-center text-sm text-slate-500">
                    لا توجد تعاملات مسجلة لهذا العميل.
                </div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
@media print {
    :deep(aside),
    :deep(header),
    .print\:hidden {
        display: none !important;
    }
}
</style>
