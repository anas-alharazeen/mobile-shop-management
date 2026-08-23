<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        إدارة الفئات
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        إدارة أنواع وأقسام المنتجات في المعرض
                    </p>
                </div>
                <Link
                    :href="route('categories.create')"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    إضافة فئة جديدة
                </Link>
            </div>
        </template>

        <!-- البطاقات الإحصائية -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="group rounded-xl border border-gray-200 bg-white p-5 transition-all hover:border-blue-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                        <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">إجمالي الفئات</p>
                        <p class="text-xl font-bold text-gray-900 dark:text-white">{{ stats.total }}</p>
                    </div>
                </div>
            </div>

            <div class="group rounded-xl border border-gray-200 bg-white p-5 transition-all hover:border-green-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
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

            <div class="group rounded-xl border border-gray-200 bg-white p-5 transition-all hover:border-gray-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-50 dark:bg-gray-700">
                        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">غير نشطة</p>
                        <p class="text-xl font-bold text-gray-500 dark:text-gray-400">{{ stats.inactive }}</p>
                    </div>
                </div>
            </div>

            <div class="group rounded-xl border border-gray-200 bg-white p-5 transition-all hover:border-purple-200 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/30">
                        <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">أنواع مستخدمة</p>
                        <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ stats.types_used }}</p>
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
                        placeholder="ابحث باسم الفئة..."
                        class="w-full rounded-lg border-gray-300 pr-10 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:text-sm"
                        @input="applyFilters"
                    />
                </div>

                <select
                    v-model="filters.type"
                    class="w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:w-40 sm:text-sm"
                    @change="applyFilters"
                >
                    <option value="">جميع الأنواع</option>
                    <option v-for="(label, value) in types" :key="value" :value="value">
                        {{ label }}
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
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                اسم الفئة
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                النوع
                            </th>
                            <th class="hidden px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 md:table-cell">
                                الوصف
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الحالة
                            </th>
                            <th class="hidden px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 lg:table-cell">
                                تاريخ الإنشاء
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                الإجراءات
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        <!-- حالة فارغة -->
                        <tr v-if="categories.data.length === 0">
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <div class="mb-4 rounded-full bg-gray-100 p-4 dark:bg-gray-700">
                                        <svg class="h-12 w-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        لا توجد فئات مسجلة
                                    </p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                        أضف أول فئة الآن
                                    </p>
                                    <Link
                                        :href="route('categories.create')"
                                        class="mt-4 inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        إضافة فئة جديدة
                                    </Link>
                                </div>
                            </td>
                        </tr>

                        <!-- صفوف البيانات -->
                        <tr v-for="category in categories.data" :key="category.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                                        <svg class="h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ category.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ types[category.type] || category.type }}
                                </span>
                            </td>
                            <td class="hidden max-w-xs px-6 py-4 text-sm text-gray-600 dark:text-gray-400 md:table-cell">
                                {{ category.description || '—' }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="[
                                        category.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                                    ]"
                                >
                                    <span
                                        class="h-1.5 w-1.5 rounded-full"
                                        :class="category.is_active ? 'bg-green-600 dark:bg-green-400' : 'bg-gray-400'"
                                    ></span>
                                    {{ category.is_active ? 'نشطة' : 'غير نشطة' }}
                                </span>
                            </td>
                            <td class="hidden whitespace-nowrap px-6 py-4 text-sm text-gray-600 dark:text-gray-400 lg:table-cell">
                                {{ formatDate(category.created_at) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <div class="flex items-center justify-center gap-1">
                                    <!-- تعديل -->
                                    <Link
                                        :href="route('categories.edit', category.id)"
                                        class="rounded-lg p-2 text-gray-400 transition-colors hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/30 dark:hover:text-blue-400"
                                        title="تعديل"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </Link>

                                    <!-- تبديل الحالة -->
                                    <button
                                        @click="toggleStatus(category.id)"
                                        class="rounded-lg p-2 transition-colors"
                                        :class="[
                                            category.is_active
                                                ? 'text-gray-400 hover:bg-yellow-50 hover:text-yellow-600 dark:hover:bg-yellow-900/30 dark:hover:text-yellow-400'
                                                : 'text-gray-400 hover:bg-green-50 hover:text-green-600 dark:hover:bg-green-900/30 dark:hover:text-green-400'
                                        ]"
                                        :title="category.is_active ? 'تعطيل' : 'تفعيل'"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </button>

                                    <!-- حذف -->
                                    <button
                                        @click="openDeleteModal(category)"
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
                عرض {{ categories.from || 0 }} - {{ categories.to || 0 }} من {{ categories.total }} فئة
            </p>
            <Pagination :links="categories.links" />
        </div>

        <!-- Modal تأكيد الحذف -->
        <div v-if="deleteModal.show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- خلفية -->
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="deleteModal.show = false"></div>

            <!-- المحتوى -->
            <div class="relative w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 shadow-2xl transition-all dark:bg-gray-800">
                <!-- أيقونة -->
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="h-7 w-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>

                <h3 class="text-center text-lg font-bold text-gray-900 dark:text-white">
                    حذف الفئة
                </h3>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    هل أنت متأكد من حذف الفئة
                    <span class="font-semibold text-gray-900 dark:text-white">"{{ deleteModal.category?.name }}"</span>؟
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
                        @click="deleteCategory"
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
    categories: Object,
    stats: Object,
    filters: Object,
    types: Object,
});

const filters = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
    is_active: props.filters.is_active,
});

const deleteModal = ref({
    show: false,
    category: null,
    loading: false,
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const applyFilters = () => {
    router.get(
        route('categories.index'),
        filters,
        { preserveState: true, replace: true }
    );
};

const clearFilters = () => {
    filters.search = '';
    filters.type = '';
    filters.is_active = null;
    applyFilters();
};

const toggleStatus = (id) => {
    router.patch(route('categories.toggle-status', id), {}, {
        preserveState: true,
    });
};

const openDeleteModal = (category) => {
    deleteModal.value = {
        show: true,
        category: category,
        loading: false,
    };
};

const deleteCategory = () => {
    deleteModal.value.loading = true;
    router.delete(route('categories.destroy', deleteModal.value.category.id), {
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
