<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ pageTitle }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ category ? 'تعديل بيانات الفئة الحالية' : 'إضافة فئة جديدة للمنتجات في المعرض' }}
                    </p>
                </div>
                <Link
                    :href="route('categories.index')"
                    class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    العودة إلى القائمة
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-3xl">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <!-- رأس النموذج -->
                <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ category ? 'تعديل الفئة' : 'بيانات الفئة الجديدة' }}
                            </h2>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ category ? 'قم بتحديث معلومات الفئة' : 'أدخل معلومات الفئة الجديدة' }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- النموذج -->
                <form @submit.prevent="submit" class="p-6">
                    <div class="space-y-5">
                        <!-- اسم الفئة -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                اسم الفئة <span class="text-red-500">*</span>
                            </label>
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </div>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    class="block w-full rounded-lg border-gray-300 pr-10 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:text-sm"
                                    :class="{ 'border-red-500 dark:border-red-500': form.errors.name }"
                                    placeholder="مثال: الهواتف الذكية"
                                    dir="rtl"
                                />
                            </div>
                            <p v-if="form.errors.name" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <!-- نوع الفئة -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                نوع الفئة <span class="text-red-500">*</span>
                            </label>
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <select
                                    id="type"
                                    v-model="form.type"
                                    class="block w-full rounded-lg border-gray-300 pr-10 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:text-sm"
                                    :class="{ 'border-red-500 dark:border-red-500': form.errors.type }"
                                >
                                    <option value="">اختر النوع</option>
                                    <option v-for="(label, value) in types" :key="value" :value="value">
                                        {{ label }}
                                    </option>
                                </select>
                            </div>
                            <p v-if="form.errors.type" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.type }}
                            </p>
                        </div>

                        <!-- الوصف -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                الوصف
                                <span class="text-xs text-gray-400 dark:text-gray-500">(اختياري)</span>
                            </label>
                            <div class="relative mt-1">
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-start pr-3 pt-3">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                    </svg>
                                </div>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="block w-full rounded-lg border-gray-300 pr-10 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:text-sm"
                                    placeholder="وصف مختصر للفئة..."
                                    dir="rtl"
                                />
                            </div>
                            <p v-if="form.errors.description" class="mt-1 flex items-center gap-1 text-sm text-red-500">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ form.errors.description }}
                            </p>
                            <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                {{ (form.description?.length || 0) }}/1000 حرف
                            </p>
                        </div>

                        <!-- الحالة - تصميم Switch -->
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-700/50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        حالة الفئة
                                    </label>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ form.is_active ? 'الفئة نشطة ومتاحة' : 'الفئة غير نشطة' }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="form.is_active = !form.is_active"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    :class="[
                                        form.is_active ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600',
                                    ]"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                        :class="[
                                            form.is_active ? 'translate-x-5' : 'translate-x-0',
                                        ]"
                                    />
                                </button>
                            </div>
                            <p v-if="form.errors.is_active" class="mt-1 text-sm text-red-500">
                                {{ form.errors.is_active }}
                            </p>
                        </div>
                    </div>

                    <!-- أزرار الإجراء -->
                    <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <Link
                            :href="route('categories.index')"
                            class="flex w-full items-center justify-center rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 sm:w-auto"
                        >
                            إلغاء
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-800 sm:w-auto"
                        >
                            <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            <span>{{ form.processing ? 'جاري الحفظ...' : (category ? 'تحديث الفئة' : 'إضافة الفئة') }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- مساعدة جانبية -->
            <div class="mt-4 rounded-lg border border-blue-100 bg-blue-50 p-4 dark:border-blue-900/30 dark:bg-blue-900/20">
                <div class="flex items-start gap-3">
                    <svg class="mt-0.5 h-4 w-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                            ملاحظات مهمة
                        </p>
                        <ul class="mt-1 space-y-1 text-xs text-blue-700 dark:text-blue-400">
                            <li>• اسم الفئة يجب أن يكون فريداً وغير مكرر</li>
                            <li>• اختر النوع المناسب لتسهيل تصنيف المنتجات</li>
                            <li>• يمكنك تعطيل الفئة بدلاً من حذفها للحفاظ على البيانات</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    category: {
        type: Object,
        default: null,
    },
    types: {
        type: Object,
        required: true,
    },
    pageTitle: {
        type: String,
        required: true,
    },
});

const form = useForm({
    name: props.category?.name || '',
    type: props.category?.type || '',
    description: props.category?.description || '',
    is_active: props.category?.is_active ?? true,
});

const formAction = props.category
    ? route('categories.update', props.category.id)
    : route('categories.store');

const submit = () => {
    if (props.category) {
        form.put(formAction);
    } else {
        form.post(formAction);
    }
};
</script>
