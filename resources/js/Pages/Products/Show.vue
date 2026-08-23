<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        تفاصيل المنتج
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        عرض معلومات المنتج بالكامل
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('products.index')"
                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        رجوع
                    </Link>
                    <Link
                        :href="route('products.edit', product.id)"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        تعديل
                    </Link>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- الصورة والمعلومات الأساسية -->
            <div class="lg:col-span-1">
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="aspect-square w-full overflow-hidden bg-gray-100 dark:bg-gray-700">
                        <img
                            v-if="product.image_path"
                            :src="'/storage/' + product.image_path"
                            :alt="product.name"
                            class="h-full w-full object-cover"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center text-gray-400">
                            <svg class="h-32 w-32" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>

                    <div class="p-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ product.name }}
                        </h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ product.code }}
                            <span v-if="product.barcode" class="mr-2">| {{ product.barcode }}</span>
                        </p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="[
                                    product.is_active
                                        ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                        : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                ]"
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full"
                                    :class="product.is_active ? 'bg-green-600 dark:bg-green-400' : 'bg-gray-400'"
                                ></span>
                                {{ product.is_active ? 'نشط' : 'غير نشط' }}
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="{
                                    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': product.stock_status?.color === 'green',
                                    'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': product.stock_status?.color === 'orange',
                                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': product.stock_status?.color === 'red'
                                }"
                            >
                                {{ product.stock_status?.label || 'متوفر' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- التفاصيل -->
            <div class="lg:col-span-2">
                <div class="space-y-6">
                    <!-- الفئة والعلامة التجارية -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                معلومات المنتج
                            </h3>
                        </div>
                        <div class="grid grid-cols-2 gap-4 p-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">الفئة</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ product.category?.name || '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">العلامة التجارية</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ product.brand || '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">الموديل</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ product.model || '-' }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">الموقع</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ product.location || '-' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- الأسعار والأرباح -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                الأسعار والأرباح
                            </h3>
                        </div>
                        <div class="grid grid-cols-2 gap-4 p-4 sm:grid-cols-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">سعر الشراء</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(product.purchase_price) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">سعر البيع</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ formatCurrency(product.selling_price) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">أقل سعر بيع</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ product.minimum_selling_price ? formatCurrency(product.minimum_selling_price) : '-' }}
                                </p>
                            </div>
                            <div class="rounded-lg bg-green-50 p-3 dark:bg-green-900/20">
                                <p class="text-xs text-green-700 dark:text-green-400">الربح</p>
                                <p class="font-bold text-green-600 dark:text-green-400">
                                    {{ formatCurrency(product.profit) }}
                                    <span class="text-sm font-normal">({{ product.profit_margin }}%)</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- المخزون -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                المخزون
                            </h3>
                        </div>
                        <div class="grid grid-cols-2 gap-4 p-4 sm:grid-cols-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">مخزون المبيعات</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ product.sales_stock?.quantity || 0 }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">مخزون الصيانة</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ product.maintenance_stock?.quantity || 0 }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">إجمالي المخزون</p>
                                <p class="font-bold text-gray-900 dark:text-white">
                                    {{ product.total_stock || 0 }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">الحد الأدنى</p>
                                <p class="font-medium text-gray-900 dark:text-white">
                                    {{ product.low_stock_threshold }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- الوصف والملاحظات -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                الوصف والملاحظات
                            </h3>
                        </div>
                        <div class="space-y-4 p-4">
                            <div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">الوصف</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ product.description || 'لا يوجد وصف' }}
                                </p>
                            </div>
                            <div v-if="product.notes">
                                <p class="text-xs text-gray-500 dark:text-gray-400">ملاحظات</p>
                                <p class="mt-1 text-sm text-gray-900 dark:text-white">
                                    {{ product.notes }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- حركات المخزون -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 p-4 dark:border-gray-700">
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                حركات المخزون الأخيرة
                            </h3>
                        </div>
                        <div class="p-4">
                            <div v-if="product.movements && product.movements.length > 0" class="space-y-2">
                                <div
                                    v-for="movement in product.movements"
                                    :key="movement.id"
                                    class="flex items-center justify-between rounded-lg border border-gray-100 p-3 dark:border-gray-700"
                                >
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ movement.type_label || movement.type }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ new Date(movement.created_at).toLocaleString('ar-EG') }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">
                                            +{{ movement.quantity }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ movement.warehouse?.name || 'مخزن' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-center text-sm text-gray-500 dark:text-gray-400">
                                لا توجد حركات مخزون مسجلة
                            </div>
                        </div>
                    </div>

                    <!-- تاريخ الإنشاء والتحديث -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex justify-between p-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">تاريخ الإنشاء:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ new Date(product.created_at).toLocaleString('ar-EG') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">آخر تحديث:</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    {{ new Date(product.updated_at).toLocaleString('ar-EG') }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    product: {
        type: Object,
        required: true,
    },
    warehouses: {
        type: Array,
        required: true,
    },
});

const formatCurrency = (value) => {
    return Number(value).toFixed(2) + ' شيكل';
};
</script>
