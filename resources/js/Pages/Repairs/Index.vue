<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        إدارة الصيانة
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        إدارة طلبات الصيانة ومتابعة الأجهزة
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link
                        :href="route('repairs.quick-create')"
                        class="flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition-all hover:bg-violet-700 hover:shadow-md"
                    >
                        <span aria-hidden="true">⚡</span>
                        صيانة سريعة
                    </Link>

                    <Link
                        :href="route('repairs.create')"
                        class="flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        استقبال جهاز جديد
                    </Link>
                </div>
            </div>
        </template>

        <!-- البطاقات الإحصائية -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-7">
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-blue-600 dark:text-blue-400">مستلم</p>
                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ stats.received }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-orange-600 dark:text-orange-400">قيد التنفيذ</p>
                <p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ stats.in_progress }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-green-600 dark:text-green-400">جاهز للاستلام</p>
                <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ stats.ready }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">مسلم اليوم</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ stats.delivered_today }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-red-600 dark:text-red-400">متأخرة</p>
                <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ stats.overdue }}</p>
            </div>
            <div class="rounded-xl border border-violet-200 bg-violet-50 p-3 dark:border-violet-900/50 dark:bg-violet-950/20">
                <p class="text-xs font-bold text-violet-600 dark:text-violet-300">سريعة اليوم</p>
                <p class="text-lg font-black text-violet-700 dark:text-violet-300">{{ stats.quick_today || 0 }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-purple-600 dark:text-purple-400">الإيرادات</p>
                <p class="text-lg font-bold text-purple-600 dark:text-purple-400">{{ formatCurrency(stats.total_revenue) }}</p>
            </div>
        </div>

        <!-- تبويبات الحالات -->
        <div class="mt-6 flex flex-wrap gap-2 border-b border-gray-200 dark:border-gray-700">
            <button
                v-for="(label, value) in statuses"
                :key="value"
                @click="setStatusFilter(value)"
                class="px-4 py-2 text-sm font-medium transition-colors border-b-2"
                :class="[
                    activeStatus === value
                        ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'
                ]"
            >
                {{ label }}
                <span class="mr-1 text-xs text-gray-400">({{ getStatusCount(value) }})</span>
            </button>
        </div>

        <!-- الفلاتر -->
        <div class="mt-4 flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap gap-3">
                <div class="relative flex-1 min-w-[200px]">
                    <svg class="absolute right-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="بحث برقم الطلب، اسم العميل، الهاتف..."
                        class="w-full rounded-lg border-gray-300 pr-10 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                        @input="applyFilters"
                    />
                </div>

                <select
                    v-model="filters.repair_mode"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">كل أنواع الصيانة</option>
                    <option
                        v-for="(label, value) in repairModes"
                        :key="value"
                        :value="value"
                    >
                        {{ label }}
                    </option>
                </select>

                <select
                    v-model="filters.payment_status"
                    class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع حالات الدفع</option>
                    <option value="unpaid">غير مدفوعة</option>
                    <option value="partially_paid">مدفوعة جزئياً</option>
                    <option value="paid">مدفوعة بالكامل</option>
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

                <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                    <input
                        v-model="filters.overdue"
                        type="checkbox"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
                        @change="applyFilters"
                    />
                    المتأخرة فقط
                </label>

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
                                رقم الطلب
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                العميل / الجهاز
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                التاريخ
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                التكلفة
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
                        <tr v-if="orders.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mb-4 rounded-full bg-gray-100 p-4 dark:bg-gray-700">
                                        <svg class="h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        لا توجد طلبات صيانة
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        استقبل أول جهاز للصيانة
                                    </p>
                                    <Link
                                        :href="route('repairs.create')"
                                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        استقبال جهاز جديد
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="order in orders.data" :key="order.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-4 py-4">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ order.order_number }}
                                </span>

                                <span
                                    v-if="order.repair_mode === 'quick'"
                                    class="mt-1 inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-black text-violet-700 dark:bg-violet-950/30 dark:text-violet-300"
                                >
                                    ⚡ صيانة سريعة
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ order.customer_name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ order.customer_phone }}</p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ order.brand }} {{ order.model }}</p>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ formatDate(order.received_at) }}
                                <span v-if="order.expected_delivery_date" class="block text-xs">
                                    التسليم: {{ formatDate(order.expected_delivery_date) }}
                                    <span v-if="order.is_overdue" class="text-red-600 dark:text-red-400">(متأخر)</span>
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="text-sm">
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(order.total_amount || order.estimated_cost) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        مدفوع: {{ formatCurrency(order.paid_amount) }}
                                    </p>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="space-y-1">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="{
                                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': order.status === 'received',
                                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': order.status === 'in_progress',
                                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': order.status === 'ready',
                                            'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': order.status === 'delivered',
                                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': order.status === 'cancelled'
                                        }"
                                    >
                                        {{ statuses[order.status] || order.status }}
                                    </span>
                                    <span
                                        v-if="order.payment_status !== 'paid' && order.status !== 'cancelled'"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="{
                                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': order.payment_status === 'unpaid',
                                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': order.payment_status === 'partially_paid'
                                        }"
                                    >
                                        {{ order.payment_status === 'unpaid' ? 'غير مدفوعة' : 'مدفوعة جزئياً' }}
                                    </span>
                                    <span v-if="order.is_overdue" class="inline-block text-xs text-red-600 dark:text-red-400">
                                        متأخر
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <Link
                                        :href="route('repairs.show', order.id)"
                                        class="rounded-lg p-2 text-blue-600 transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                        title="عرض التفاصيل"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <Link
                                        :href="route('repairs.print', order.id)"
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
                عرض {{ orders.from || 0 }} - {{ orders.to || 0 }} من {{ orders.total }} طلب
            </p>
            <Pagination :links="orders.links" />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    orders: Object,
    stats: Object,
    filters: Object,
    customers: Array,
    statuses: Object,
    repairModes: Object,
});

const activeStatus = ref(props.filters.status || '');
const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    payment_status: props.filters.payment_status || '',
    repair_mode: props.filters.repair_mode || '',
    customer_id: props.filters.customer_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
    overdue: props.filters.overdue || false,
});

const getStatusCount = (status) => {
    return props.orders.data.filter(o => o.status === status).length;
};

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ar-EG');
};

const setStatusFilter = (status) => {
    activeStatus.value = status;
    filters.status = status;
    applyFilters();
};

const applyFilters = () => {
    router.get(route('repairs.index'), filters, { preserveState: true, replace: true });
};

const clearFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.payment_status = '';
    filters.repair_mode = '';
    filters.customer_id = '';
    filters.start_date = '';
    filters.end_date = '';
    filters.overdue = false;
    activeStatus.value = '';
    applyFilters()
};
</script>
