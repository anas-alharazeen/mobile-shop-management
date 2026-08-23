<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        سجل حركات المخزون
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        عرض جميع عمليات الإضافة والخصم والنقل
                    </p>
                </div>
                <Link
                    :href="route('inventory.index')"
                    class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    العودة للمخزون
                </Link>
            </div>
        </template>

        <!-- الفلاتر -->
        <div class="flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center">
            <div class="grid flex-1 grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <select
                    v-model="filters.product_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع المنتجات</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.code }})</option>
                </select>

                <select
                    v-model="filters.warehouse_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع المخازن</option>
                    <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
                </select>

                <select
                    v-model="filters.type"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع الأنواع</option>
                    <option v-for="(label, value) in types" :key="value" :value="value">{{ label }}</option>
                </select>

                <input
                    v-model="filters.start_date"
                    type="date"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                />

                <input
                    v-model="filters.end_date"
                    type="date"
                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @change="applyFilters"
                />
            </div>

            <button
                @click="clearFilters"
                class="shrink-0 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                مسح الفلاتر
            </button>
        </div>

        <!-- الجدول -->
        <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                التاريخ والوقت
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                المنتج
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                النوع
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                المخزن
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الكمية
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                قبل/بعد
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                ملاحظات
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        <tr v-if="movements.data.length === 0">
                            <td colspan="7" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                                لا توجد حركات مخزون مسجلة
                            </td>
                        </tr>
                        <tr v-for="movement in movements.data" :key="movement.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ new Date(movement.created_at).toLocaleString('ar-EG') }}
                            </td>
                            <td class="px-4 py-4 text-sm text-gray-900 dark:text-white">
                                {{ movement.product?.name || '-' }}
                                <span class="block text-xs text-gray-500">{{ movement.product?.code || '' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': movement.type === 'opening',
                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': ['manual_addition', 'transfer_in'].includes(movement.type),
                                        'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': ['manual_deduction', 'transfer_out'].includes(movement.type),
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': ['damaged', 'lost'].includes(movement.type)
                                    }"
                                >
                                    {{ types[movement.type] || movement.type }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ movement.warehouse?.name || '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm font-medium">
                                <span
                                    :class="{
                                        'text-green-600 dark:text-green-400': movement.type === 'opening' || movement.type === 'manual_addition' || movement.type === 'transfer_in',
                                        'text-red-600 dark:text-red-400': ['manual_deduction', 'transfer_out', 'damaged', 'lost'].includes(movement.type)
                                    }"
                                >
                                    {{ movement.quantity }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ movement.quantity_before }} → {{ movement.quantity_after }}
                            </td>
                            <td class="max-w-xs px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ movement.notes || '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex items-center justify-between">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                عرض {{ movements.from || 0 }} - {{ movements.to || 0 }} من {{ movements.total }} حركة
            </p>
            <Pagination :links="movements.links" />
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    movements: Object,
    filters: Object,
    products: Array,
    warehouses: Array,
    types: Object,
});

const filters = reactive({
    product_id: props.filters.product_id || '',
    warehouse_id: props.filters.warehouse_id || '',
    type: props.filters.type || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const applyFilters = () => {
    router.get(route('inventory.movements'), filters, { preserveState: true, replace: true });
};

const clearFilters = () => {
    filters.product_id = '';
    filters.warehouse_id = '';
    filters.type = '';
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};
</script>
