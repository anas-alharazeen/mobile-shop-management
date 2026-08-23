<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        الجرد المخزني
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        إدارة جلسات الجرد ومقارنة الكميات الفعلية مع المسجلة
                    </p>
                </div>
                <Link
                    :href="route('inventory-counts.create')"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    بدء جرد جديد
                </Link>
            </div>
        </template>

        <!-- البطاقات الإحصائية -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">إجمالي الجرد</p>
                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-blue-600 dark:text-blue-400">قيد التنفيذ</p>
                <p class="mt-1 text-xl font-bold text-blue-600 dark:text-blue-400">{{ stats.in_progress }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-green-600 dark:text-green-400">معتمد</p>
                <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">{{ stats.completed }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-blue-600 dark:text-blue-400">إجمالي الزيادة</p>
                <p class="mt-1 text-xl font-bold text-blue-600 dark:text-blue-400">{{ formatCurrency(stats.total_surplus) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs font-medium text-red-600 dark:text-red-400">إجمالي العجز</p>
                <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">{{ formatCurrency(stats.total_shortage) }}</p>
            </div>
        </div>

        <!-- الفلاتر والبحث -->
        <div class="mt-6 flex flex-col gap-4 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row">
                <div class="relative flex-1">
                    <svg class="absolute right-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input
                        v-model="filters.search"
                        type="text"
                        placeholder="ابحث برقم الجرد..."
                        class="w-full rounded-lg border-gray-300 pr-10 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:text-sm"
                        @input="applyFilters"
                    />
                </div>

                <select
                    v-model="filters.status"
                    class="w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:w-40 sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع الحالات</option>
                    <option v-for="(label, value) in statuses" :key="value" :value="value">
                        {{ label }}
                    </option>
                </select>

                <select
                    v-model="filters.warehouse_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:w-40 sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع المخازن</option>
                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                        {{ warehouse.name }}
                    </option>
                </select>

                <input
                    v-model="filters.start_date"
                    type="date"
                    class="w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:w-36 sm:text-sm"
                    @change="applyFilters"
                />

                <input
                    v-model="filters.end_date"
                    type="date"
                    class="w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:w-36 sm:text-sm"
                    @change="applyFilters"
                />

                <button
                    @click="clearFilters"
                    class="flex items-center justify-center gap-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
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
                                رقم الجرد
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                المخزن
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                التاريخ
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                التقدم
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الفروقات
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
                        <tr v-if="counts.data.length === 0">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mb-4 rounded-full bg-gray-100 p-4 dark:bg-gray-700">
                                        <svg class="h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        لا توجد جلسات جرد
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        ابدأ جلسة جرد جديدة
                                    </p>
                                    <Link
                                        :href="route('inventory-counts.create')"
                                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        بدء جرد جديد
                                    </Link>
                                </div>
                            </td>
                        </tr>
                        <tr v-for="count in counts.data" :key="count.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-4 py-4">
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ count.reference_number }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ count.warehouse?.name || '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-600 dark:text-gray-400">
                                {{ formatDate(count.count_date) }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-16 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div
                                            class="h-full rounded-full bg-blue-600 transition-all"
                                            :style="{ width: count.progress + '%' }"
                                        />
                                    </div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ count.progress }}%
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center gap-2 text-sm">
                                    <span v-if="count.summary?.surplus > 0" class="text-blue-600 dark:text-blue-400">
                                        +{{ count.summary.surplus }}
                                    </span>
                                    <span v-if="count.summary?.shortage > 0" class="text-red-600 dark:text-red-400">
                                        -{{ count.summary.shortage }}
                                    </span>
                                    <span v-if="count.summary?.surplus === 0 && count.summary?.shortage === 0" class="text-green-600 dark:text-green-400">
                                        مطابق
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': count.status === 'draft',
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': count.status === 'in_progress',
                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': count.status === 'completed',
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': count.status === 'cancelled'
                                    }"
                                >
                                    {{ statuses[count.status] || count.status }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <Link
                                        :href="route('inventory-counts.show', count.id)"
                                        class="rounded-lg p-2 text-blue-600 transition-colors hover:bg-blue-50 dark:hover:bg-blue-900/30"
                                        title="متابعة الجرد"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>
                                    <button
                                        v-if="count.status !== 'completed' && count.status !== 'cancelled'"
                                        @click="openCancelModal(count)"
                                        class="rounded-lg p-2 text-red-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                        title="إلغاء"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>
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
                عرض {{ counts.from || 0 }} - {{ counts.to || 0 }} من {{ counts.total }} جلسة
            </p>
            <Pagination :links="counts.links" />
        </div>

        <!-- Modal إلغاء الجرد -->
        <Modal :show="cancelModal.show" @close="cancelModal.show = false">
            <template #title>إلغاء جلسة الجرد</template>
            <template #content>
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                    هل أنت متأكد من إلغاء جلسة الجرد
                    <span class="font-semibold text-gray-900 dark:text-white">{{ cancelModal.count?.reference_number }}</span>؟
                </p>
                <form @submit.prevent="submitCancel">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            سبب الإلغاء
                        </label>
                        <textarea
                            v-model="cancelForm.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="سبب إلغاء الجرد"
                        />
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="cancelModal.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="cancelForm.processing"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-70"
                        >
                            {{ cancelForm.processing ? 'جاري الإلغاء...' : 'تأكيد الإلغاء' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    counts: Object,
    stats: Object,
    filters: Object,
    warehouses: Array,
    statuses: Object,
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    warehouse_id: props.filters.warehouse_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const cancelModal = ref({
    show: false,
    count: null,
});

const cancelForm = useForm({
    notes: '',
});

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ar-EG');
};

const applyFilters = () => {
    router.get(route('inventory-counts.index'), filters, { preserveState: true, replace: true });
};

const clearFilters = () => {
    filters.search = '';
    filters.status = '';
    filters.warehouse_id = '';
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const openCancelModal = (count) => {
    cancelModal.value = {
        show: true,
        count: count,
    };
    cancelForm.notes = '';
};

const submitCancel = () => {
    cancelForm.post(route('inventory-counts.cancel', cancelModal.value.count.id), {
        preserveScroll: true,
        onSuccess: () => {
            cancelModal.value.show = false;
        },
    });
};
</script>
