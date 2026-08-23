<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        بدء جرد جديد
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        إنشاء جلسة جرد لمخزن محدد
                    </p>
                </div>
                <Link
                    :href="route('inventory-counts.index')"
                    class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    العودة للقائمة
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="submit" class="space-y-6">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">إعدادات الجرد</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">تحديد نطاق وبيانات الجلسة</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                المخزن <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.warehouse_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                :class="{ 'border-red-500': form.errors.warehouse_id }"
                                required
                            >
                                <option value="">اختر المخزن</option>
                                <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                    {{ warehouse.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.warehouse_id" class="mt-1 text-sm text-red-500">{{ form.errors.warehouse_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                نطاق الجرد <span class="text-red-500">*</span>
                            </label>
                            <div class="mt-1 space-y-2">
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="form.scope"
                                        type="radio"
                                        value="all"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">جميع المنتجات</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input
                                        v-model="form.scope"
                                        type="radio"
                                        value="category"
                                        class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                                    />
                                    <span class="text-sm text-gray-700 dark:text-gray-300">فئة محددة</span>
                                </label>
                            </div>
                        </div>

                        <div v-if="form.scope === 'category'">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                الفئة <span class="text-red-500">*</span>
                            </label>
                            <select
                                v-model="form.category_id"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                :class="{ 'border-red-500': form.errors.category_id }"
                            >
                                <option value="">اختر الفئة</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">
                                    {{ category.name }}
                                </option>
                            </select>
                            <p v-if="form.errors.category_id" class="mt-1 text-sm text-red-500">{{ form.errors.category_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                تاريخ الجرد <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="form.count_date"
                                type="date"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                :class="{ 'border-red-500': form.errors.count_date }"
                                required
                            />
                            <p v-if="form.errors.count_date" class="mt-1 text-sm text-red-500">{{ form.errors.count_date }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                ملاحظات
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                placeholder="ملاحظات حول الجرد"
                            />
                        </div>
                    </div>
                </div>

                <!-- تنبيه -->
                <div class="overflow-hidden rounded-2xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900/30 dark:bg-yellow-900/20">
                    <div class="flex items-start gap-3">
                        <svg class="mt-0.5 h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                ملاحظة مهمة
                            </p>
                            <p class="text-sm text-yellow-700 dark:text-yellow-400">
                                سيتم تثبيت الكميات المسجلة في النظام عند بدء الجرد.
                                أي تغيير في المخزون أثناء الجرد سيتم اكتشافه عند الاعتماد.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- الأزرار -->
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link
                        :href="route('inventory-counts.index')"
                        class="flex w-full items-center justify-center rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 sm:w-auto"
                    >
                        إلغاء
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md disabled:opacity-70 sm:w-auto"
                    >
                        <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ form.processing ? 'جاري الإنشاء...' : 'بدء الجرد' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    warehouses: Array,
    categories: Array,
});

const form = useForm({
    warehouse_id: '',
    scope: 'all',
    category_id: '',
    count_date: new Date().toISOString().split('T')[0],
    notes: '',
});

const submit = () => {
    form.post(route('inventory-counts.store'));
};
</script>
