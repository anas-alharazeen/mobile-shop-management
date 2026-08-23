<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        جلسة الجرد
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ count.reference_number }}
                        <span class="mr-2 text-xs text-gray-400">|</span>
                        {{ count.warehouse?.name }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('inventory-counts.index')"
                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        القائمة
                    </Link>
                    <button
                        v-if="count.status === 'in_progress'"
                        @click="confirmComplete"
                        class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-green-700 hover:shadow-md"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        اعتماد الجرد
                    </button>
                </div>
            </div>
        </template>

        <!-- ملخص الجرد -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-7">
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">التقدم</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ count.progress }}%</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">إجمالي</p>
                <p class="text-lg font-bold text-gray-900 dark:text-white">{{ count.summary?.total || 0 }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">معدود</p>
                <p class="text-lg font-bold text-green-600 dark:text-green-400">{{ count.summary?.counted || 0 }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">متبقي</p>
                <p class="text-lg font-bold text-orange-600 dark:text-orange-400">{{ count.summary?.remaining || 0 }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-blue-600 dark:text-blue-400">زيادة</p>
                <p class="text-lg font-bold text-blue-600 dark:text-blue-400">{{ count.summary?.surplus || 0 }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-red-600 dark:text-red-400">عجز</p>
                <p class="text-lg font-bold text-red-600 dark:text-red-400">{{ count.summary?.shortage || 0 }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">الحالة</p>
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
            </div>
        </div>

        <!-- الفلاتر -->
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <svg class="absolute right-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="search"
                    type="text"
                    placeholder="ابحث باسم المنتج أو الكود..."
                    class="w-full rounded-lg border-gray-300 pr-10 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @input="filterItems"
                />
            </div>
            <select
                v-model="filterStatus"
                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:w-40 sm:text-sm"
                @change="filterItems"
            >
                <option value="all">جميع الحالات</option>
                <option value="uncounted">غير معدود</option>
                <option value="match">مطابق</option>
                <option value="surplus">زيادة</option>
                <option value="shortage">عجز</option>
            </select>
            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ filteredItems.length }} / {{ count.items.length }} منتج
            </span>
        </div>

        <!-- قائمة المنتجات -->
        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                المنتج
                            </th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الكود
                            </th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                مسجل
                            </th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                فعلي
                            </th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الفرق
                            </th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الحالة
                            </th>
                            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                القيمة
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        <tr v-if="filteredItems.length === 0">
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                لا توجد منتجات تطابق الفلتر
                            </td>
                        </tr>
                        <tr
                            v-for="item in filteredItems"
                            :key="item.id"
                            class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50"
                            :class="{
                                'bg-green-50 dark:bg-green-900/5': item.actual_quantity !== null && item.difference === 0,
                                'bg-blue-50 dark:bg-blue-900/5': item.actual_quantity !== null && item.difference > 0,
                                'bg-red-50 dark:bg-red-900/5': item.actual_quantity !== null && item.difference < 0,
                            }"
                        >
                            <td class="px-3 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ item.product?.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-500 dark:text-gray-400">
                                {{ item.product?.code }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ item.system_quantity }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-3">
                                <input
                                    v-if="count.status !== 'completed' && count.status !== 'cancelled'"
                                    v-model.number="item.actual_quantity"
                                    type="number"
                                    min="0"
                                    class="w-20 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    @change="updateItem(item)"
                                />
                                <span v-else class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ item.actual_quantity ?? '-' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm font-medium">
                                <span
                                    v-if="item.difference !== null && item.difference !== undefined"
                                    :class="{
                                        'text-green-600 dark:text-green-400': item.difference === 0,
                                        'text-blue-600 dark:text-blue-400': item.difference > 0,
                                        'text-red-600 dark:text-red-400': item.difference < 0,
                                    }"
                                >
                                    {{ item.difference > 0 ? '+' : '' }}{{ item.difference }}
                                </span>
                                <span v-else class="text-gray-400">-</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': item.actual_quantity === null,
                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': item.actual_quantity !== null && item.difference === 0,
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': item.actual_quantity !== null && item.difference > 0,
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': item.actual_quantity !== null && item.difference < 0,
                                    }"
                                >
                                    {{ item.status?.label || 'غير معدود' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ item.difference_value ? formatCurrency(item.difference_value) : '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- شريط التقدم -->
        <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <span>
                    <span class="font-medium text-green-600 dark:text-green-400">{{ count.summary?.counted || 0 }}</span>
                    / {{ count.summary?.total || 0 }} معدود
                </span>
                <span v-if="count.summary?.surplus > 0">
                    <span class="font-medium text-blue-600 dark:text-blue-400">زيادة: {{ count.summary.surplus }}</span>
                </span>
                <span v-if="count.summary?.shortage > 0">
                    <span class="font-medium text-red-600 dark:text-red-400">عجز: {{ count.summary.shortage }}</span>
                </span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ count.progress }}%
                </span>
                <div class="h-2 w-32 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div
                        class="h-full rounded-full bg-blue-600 transition-all"
                        :style="{ width: count.progress + '%' }"
                    />
                </div>
            </div>
        </div>

        <!-- Modal تأكيد الاعتماد -->
        <Modal :show="completeModal.show" @close="completeModal.show = false">
            <template #title>اعتماد الجرد</template>
            <template #content>
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        هل أنت متأكد من اعتماد جلسة الجرد
                        <span class="font-semibold text-gray-900 dark:text-white">{{ count.reference_number }}</span>؟
                    </p>

                    <div v-if="count.summary" class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700/50">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">إجمالي المنتجات:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ count.summary.total }}</span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">معدود:</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ count.summary.counted }}</span>
                            </div>
                            <div>
                                <span class="text-blue-600 dark:text-blue-400">الزيادة:</span>
                                <span class="font-medium text-blue-600 dark:text-blue-400">{{ count.summary.surplus }}</span>
                            </div>
                            <div>
                                <span class="text-red-600 dark:text-red-400">العجز:</span>
                                <span class="font-medium text-red-600 dark:text-red-400">{{ count.summary.shortage }}</span>
                            </div>
                            <div class="col-span-2 border-t border-gray-200 pt-2 dark:border-gray-600">
                                <span class="text-gray-500 dark:text-gray-400">صافي الفرق:</span>
                                <span
                                    class="font-bold"
                                    :class="{
                                        'text-green-600 dark:text-green-400': count.summary.net_difference === 0,
                                        'text-blue-600 dark:text-blue-400': count.summary.net_difference > 0,
                                        'text-red-600 dark:text-red-400': count.summary.net_difference < 0,
                                    }"
                                >
                                    {{ formatCurrency(count.summary.net_difference) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-lg bg-yellow-50 p-3 dark:bg-yellow-900/20">
                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                            تنبيه: سيتم تحديث أرصدة المخزون تلقائياً بناءً على الكميات الفعلية.
                        </p>
                    </div>

                    <div class="flex justify-end gap-3">
                        <button
                            @click="completeModal.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            @click="submitComplete"
                            :disabled="completeForm.processing"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-70"
                        >
                            {{ completeForm.processing ? 'جاري الاعتماد...' : 'تأكيد الاعتماد' }}
                        </button>
                    </div>
                </div>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    count: Object,
    statuses: Object,
});

const search = ref('');
const filterStatus = ref('all');

const completeModal = ref({
    show: false,
});

const completeForm = useForm({});

const filteredItems = computed(() => {
    let items = props.count.items || [];

    // فلتر البحث
    if (search.value) {
        const s = search.value.toLowerCase();
        items = items.filter(item =>
            item.product?.name?.toLowerCase().includes(s) ||
            item.product?.code?.toLowerCase().includes(s)
        );
    }

    // فلتر الحالة
    if (filterStatus.value !== 'all') {
        items = items.filter(item => {
            const status = item.status?.label || 'غير معدود';
            if (filterStatus.value === 'uncounted') return status === 'غير معدود';
            if (filterStatus.value === 'match') return status === 'مطابق';
            if (filterStatus.value === 'surplus') return status === 'زيادة';
            if (filterStatus.value === 'shortage') return status === 'عجز';
            return true;
        });
    }

    return items;
});

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};

const updateItem = (item) => {
    if (item.actual_quantity === undefined || item.actual_quantity === null) return;

    router.patch(
        route('inventory-counts.update-item', item.id),
        {
            actual_quantity: item.actual_quantity,
            notes: item.notes,
        },
        {
            preserveScroll: true,
        }
    );
};

const filterItems = () => {
    // التصفية تتم عبر computed
};

const confirmComplete = () => {
    completeModal.value.show = true;
};

const submitComplete = () => {
    completeForm.post(route('inventory-counts.complete', props.count.id), {
        preserveScroll: true,
        onSuccess: () => {
            completeModal.value.show = false;
        },
    });
};
</script>
