<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        فواتير البيع
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        إدارة فواتير المبيعات وعمليات البيع
                    </p>
                </div>
                <Link
                    :href="route('pos')"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    بيع جديد
                </Link>
            </div>
        </template>

        <!-- البطاقات الإحصائية -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">مبيعات اليوم</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.today) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">مبيعات الشهر</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ formatCurrency(stats.month) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-green-600 dark:text-green-400">الأرباح</p>
                <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ formatCurrency(stats.profit) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-red-600 dark:text-red-400">غير مدفوعة</p>
                <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ formatCurrency(stats.unpaid) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-orange-600 dark:text-orange-400">المستحق</p>
                <p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ formatCurrency(stats.total_due) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-blue-600 dark:text-blue-400">فواتير اليوم</p>
                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ stats.today_count }}</p>
            </div>
        </div>

        <!-- الفلاتر -->
        <div class="mt-6 flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute right-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="بحث برقم الفاتورة، اسم العميل..."
                        class="w-full rounded-lg border-gray-300 pr-10 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                        @input="applyFilters"
                    />
                </div>

                <select
                    v-model="filters.status"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع الحالات</option>
                    <option v-for="(label, value) in statuses" :key="value" :value="value">
                        {{ label }}
                    </option>
                </select>

                <select
                    v-model="filters.payment_status"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع حالات الدفع</option>
                    <option v-for="(label, value) in paymentStatuses" :key="value" :value="value">
                        {{ label }}
                    </option>
                </select>

                <select
                    v-model="filters.customer_id"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع العملاء</option>
                    <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                        {{ customer.name }}
                    </option>
                </select>

                <input
                    v-model="filters.start_date"
                    type="date"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                />

                <input
                    v-model="filters.end_date"
                    type="date"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                />

                <button
                    @click="clearFilters"
                    class="flex items-center gap-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    مسح
                </button>
            </div>
        </div>

        <!-- الجدول -->
        <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                رقم الفاتورة
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                العميل
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                التاريخ
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الإجمالي
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                المدفوع / المتبقي
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الربح
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الحالة
                            </th>
                            <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الإجراءات
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        <tr v-if="invoices.data.length === 0">
                            <td colspan="8" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                                لا توجد فواتير بيع
                            </td>
                        </tr>
                        <tr v-for="invoice in invoices.data" :key="invoice.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-4 py-4">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ invoice.invoice_number }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                {{ invoice.customer_name || 'عميل نقدي' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ formatDate(invoice.sale_date) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm font-bold text-gray-900 dark:text-white">
                                {{ formatCurrency(invoice.total_amount) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="text-sm">
                                    <p class="text-green-600 dark:text-green-400">مدفوع: {{ formatCurrency(invoice.paid_amount) }}</p>
                                    <p class="text-red-600 dark:text-red-400">متبقي: {{ formatCurrency(invoice.remaining_amount) }}</p>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-green-600 dark:text-green-400">
                                {{ formatCurrency(invoice.gross_profit) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="space-y-1">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="{
                                            'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': invoice.status === 'draft',
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': invoice.status === 'approved',
                                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': invoice.status === 'cancelled'
                                        }"
                                    >
                                        {{ statuses[invoice.status] || invoice.status }}
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="{
                                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': invoice.payment_status === 'unpaid',
                                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': invoice.payment_status === 'partially_paid',
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': invoice.payment_status === 'paid'
                                        }"
                                    >
                                        {{ paymentStatuses[invoice.payment_status] || invoice.payment_status }}
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <Link
                                        :href="route('sales.show', invoice.id)"
                                        class="rounded-lg p-2 text-blue-600 transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                        title="عرض التفاصيل"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <Link
                                        v-if="invoice.status === 'draft'"
                                        :href="route('sales.edit', invoice.id)"
                                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/30"
                                        title="تعديل"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>
                                    <Link
                                        :href="route('sales.print', invoice.id)"
                                        target="_blank"
                                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                                        title="طباعة"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                عرض {{ invoices.from || 0 }} - {{ invoices.to || 0 }} من {{ invoices.total }} فاتورة
            </p>
            <Pagination :links="invoices.links" />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    invoices: Object,
    stats: Object,
    filters: Object,
    customers: Array,
    statuses: Object,
    paymentStatuses: Object,
    paymentMethods: Object,
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    payment_status: props.filters.payment_status || '',
    customer_id: props.filters.customer_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ar-EG');
};

const applyFilters = () => {
    router.get(route('sales.index'), filters, { preserveState: true, replace: true });
};

const clearFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.payment_status = '';
    filters.customer_id = '';
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};
</script>
