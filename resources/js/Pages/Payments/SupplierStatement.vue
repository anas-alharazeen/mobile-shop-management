<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { usePrint } from '@/composables/usePrint';

const props = defineProps({
    supplier: { type: Object, required: true },
    transactions: { type: Array, default: () => [] },
    summary: { type: Object, required: true },
});

const { printPage } = usePrint();

const typeMeta = {
    purchase: {
        label: 'فاتورة شراء',
        className: 'bg-blue-100 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300',
    },
    purchase_payment: {
        label: 'دفعة مورد',
        className: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
    },
    purchase_return: {
        label: 'مرتجع مشتريات',
        className: 'bg-orange-100 text-orange-700 dark:bg-orange-950/30 dark:text-orange-300',
    },
    supplier_refund: {
        label: 'استرداد من المورد',
        className: 'bg-violet-100 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300',
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
    <Head :title="`كشف حساب ${supplier.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-black text-blue-600 dark:text-blue-400">
                        كشف حساب المورد
                    </p>
                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">
                        {{ supplier.company_name || supplier.name }}
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">
                        {{ supplier.name }} · {{ supplier.code }}
                    </p>
                </div>

                <div class="flex gap-2 print:hidden">
                    <Link
                        :href="route('suppliers.show', supplier.id)"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-bold text-slate-700 dark:border-slate-700 dark:text-slate-200"
                    >
                        العودة للمورد
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
            <section class="grid gap-3 sm:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                    <p class="text-xs text-slate-500">صافي المشتريات</p>
                    <strong class="mt-2 block text-lg font-black dark:text-white">
                        {{ money(summary.total_purchases) }}
                    </strong>
                </article>

                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20">
                    <p class="text-xs text-emerald-700 dark:text-emerald-300">صافي المدفوع</p>
                    <strong class="mt-2 block text-lg font-black text-emerald-700 dark:text-emerald-300">
                        {{ money(summary.total_paid) }}
                    </strong>
                </article>

                <article class="rounded-2xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-950/20">
                    <p class="text-xs text-orange-700 dark:text-orange-300">
                        الرصيد المستحق للمورد
                    </p>
                    <strong class="mt-2 block text-lg font-black text-orange-700 dark:text-orange-300">
                        {{ money(summary.balance) }}
                    </strong>
                </article>
            </section>

            <section
                v-if="hasDifference"
                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200"
            >
                <strong>تنبيه مراجعة بيانات:</strong>
                الرصيد الرسمي للفواتير هو {{ money(summary.balance) }}
                بينما كشف التعاملات يعيد بناء {{ money(summary.ledger_balance) }}.
                الفرق {{ money(summary.balance_difference) }}.
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                    <h2 class="font-black dark:text-white">سجل التعاملات</h2>
                    <p class="mt-1 text-xs text-slate-500">
                        الفواتير والدفعات والمرتجعات والاستردادات بترتيب زمني.
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
                                <th class="px-4 py-3 text-left">فاتورة/زيادة</th>
                                <th class="px-4 py-3 text-left">دفعة/تخفيض</th>
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
                    لا توجد تعاملات مسجلة لهذا المورد.
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
