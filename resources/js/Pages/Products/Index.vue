<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        إدارة المنتجات
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        إدارة مخزون المنتجات في المعرض
                    </p>
                </div>
                <Link
                    :href="route('products.create')"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    إضافة منتج جديد
                </Link>
            </div>
        </template>

        <!-- البطاقات الإحصائية -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
            <div class="group rounded-xl border border-gray-200 bg-white p-4 transition-all hover:border-blue-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">إجمالي المنتجات</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    </div>
                </div>
            </div>

            <div class="group rounded-xl border border-gray-200 bg-white p-4 transition-all hover:border-green-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/30">
                        <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">نشطة</p>
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ stats.active }}</p>
                    </div>
                </div>
            </div>

            <div class="group rounded-xl border border-gray-200 bg-white p-4 transition-all hover:border-orange-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-50 dark:bg-orange-900/30">
                        <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">منخفضة المخزون</p>
                        <p class="text-xl font-bold text-orange-600 dark:text-orange-400">{{ stats.low_stock }}</p>
                    </div>
                </div>
            </div>

            <div class="group rounded-xl border border-gray-200 bg-white p-4 transition-all hover:border-red-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-50 dark:bg-red-900/30">
                        <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">نافدة</p>
                        <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ stats.out_of_stock }}</p>
                    </div>
                </div>
            </div>

            <div class="group rounded-xl border border-gray-200 bg-white p-4 transition-all hover:border-purple-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/30">
                        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">قيمة المخزون</p>
                        <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ formatCurrency(stats.inventory_value) }}</p>
                    </div>
                </div>
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
                        placeholder="ابحث بالاسم، الكود، أو الباركود..."
                        class="w-full rounded-lg border-gray-300 pr-10 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:text-sm"
                        @input="applyFilters"
                    />
                </div>

                <select
                    v-model="filters.category_id"
                    class="w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:w-40 sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع الفئات</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </select>

                <select
                    v-model="filters.is_active"
                    class="w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:w-36 sm:text-sm"
                    @change="applyFilters"
                >
                    <option :value="null">جميع الحالات</option>
                    <option :value="true">نشطة</option>
                    <option :value="false">غير نشطة</option>
                </select>

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
                                المنتج
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الفئة
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                السعر
                            </th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                المخزون
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
                        <!-- حالة فارغة -->
                        <tr v-if="products.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mb-4 rounded-full bg-gray-100 p-4 dark:bg-gray-700">
                                        <svg class="h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        لا توجد منتجات مسجلة
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        أضف أول منتج الآن
                                    </p>
                                    <Link
                                        :href="route('products.create')"
                                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        إضافة منتج جديد
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- صفوف البيانات -->
                        <tr v-for="product in products.data" :key="product.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-700">
                                        <img
                                            v-if="product.image_path"
                                            :src="'/storage/' + product.image_path"
                                            :alt="product.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full w-full items-center justify-center text-gray-400">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ product.name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ product.code }}
                                            <span v-if="product.barcode" class="mr-2">| {{ product.barcode }}</span>
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ product.category?.name || '-' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="text-sm">
                                    <p class="text-gray-900 dark:text-white">
                                        {{ formatCurrency(product.selling_price) }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        شراء: {{ formatCurrency(product.purchase_price) }}
                                    </p>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="text-sm">
                                    <p class="text-gray-900 dark:text-white">
                                        {{ product.total_stock || 0 }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        مبيعات: {{ product.sales_stock?.quantity || 0 }} | صيانة: {{ product.maintenance_stock?.quantity || 0 }}
                                    </p>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex flex-col gap-1">
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
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <!-- عرض التفاصيل -->
                                    <Link
                                        :href="route('products.show', product.id)"
                                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/30 dark:hover:text-blue-400"
                                        title="عرض التفاصيل"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </Link>

                                    <!-- تعديل -->
                                    <Link
                                        :href="route('products.edit', product.id)"
                                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/30 dark:hover:text-blue-400"
                                        title="تعديل"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>

                                    <!-- تبديل الحالة -->
                                    <button
                                        @click="toggleStatus(product.id)"
                                        class="rounded-lg p-2 transition-colors"
                                        :class="[
                                            product.is_active
                                                ? 'text-gray-400 hover:bg-yellow-50 hover:text-yellow-600 dark:hover:bg-yellow-900/30 dark:hover:text-yellow-400'
                                                : 'text-gray-400 hover:bg-green-50 hover:text-green-600 dark:hover:bg-green-900/30 dark:hover:text-green-400'
                                        ]"
                                        :title="product.is_active ? 'تعطيل' : 'تفعيل'"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    <!-- حذف -->
                                    <button
                                        @click="openDeleteModal(product)"
                                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/30 dark:hover:text-red-400"
                                        title="حذف"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
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
                عرض {{ products.from || 0 }} - {{ products.to || 0 }} من {{ products.total }} منتج
            </p>
            <Pagination :links="products.links" />
        </div>

        <!-- Modal تأكيد الحذف -->
        <div v-if="deleteModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteModal.show = false"></div>
            <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-2xl transition-all dark:bg-gray-800">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="h-7 w-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-center text-lg font-bold text-gray-900 dark:text-white">
                    حذف المنتج
                </h3>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    هل أنت متأكد من حذف المنتج
                    <span class="font-semibold text-gray-900 dark:text-white">"{{ deleteModal.product?.name }}"</span>؟
                </p>
                <p class="mt-1 text-center text-xs text-gray-500 dark:text-gray-500">
                    هذا الإجراء قابل للتراجع
                </p>
                <div class="mt-6 flex justify-center gap-3">
                    <button
                        @click="deleteModal.show = false"
                        class="rounded-lg px-6 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        إلغاء
                    </button>
                    <button
                        @click="deleteProduct"
                        class="flex items-center gap-2 rounded-lg bg-red-600 px-6 py-2.5 text-sm font-medium text-white transition-colors hover:bg-red-700"
                        :disabled="deleteModal.loading"
                    >
                        <svg v-if="deleteModal.loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                        </svg>
                        {{ deleteModal.loading ? 'جاري الحذف...' : 'تأكيد الحذف' }}
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    products: Object,
    stats: Object,
    filters: Object,
    categories: Array,
});

const filters = reactive({
    search: props.filters.search || '',
    category_id: props.filters.category_id || '',
    is_active: props.filters.is_active,
});

const deleteModal = ref({
    show: false,
    product: null,
    loading: false,
});

const formatCurrency = (value) => {
    return Number(value).toFixed(2) + ' شيكل';
};

const applyFilters = () => {
    router.get(
        route('products.index'),
        filters,
        { preserveState: true, replace: true }
    );
};

const clearFilters = () => {
    filters.search = '';
    filters.category_id = '';
    filters.is_active = null;
    applyFilters();
};

const toggleStatus = (id) => {
    router.patch(route('products.toggle-status', id), {}, {
        preserveState: true,
    });
};

const openDeleteModal = (product) => {
    deleteModal.value = {
        show: true,
        product: product,
        loading: false,
    };
};

const deleteProduct = () => {
    deleteModal.value.loading = true;
    router.delete(route('products.destroy', deleteModal.value.product.id), {
        preserveState: true,
        onSuccess: () => {
            deleteModal.value.show = false;
            deleteModal.value.loading = false;
        },
        onError: () => {
            deleteModal.value.loading = false;
        },
    });
};
</script>
