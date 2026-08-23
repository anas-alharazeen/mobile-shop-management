<template>
    <form @submit.prevent="submit" class="space-y-6">
        <!-- الاسم -->
        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                اسم الفئة <span class="text-red-500">*</span>
            </label>
            <input
                id="name"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:text-sm"
                :class="{ 'border-red-500 dark:border-red-500': form.errors.name }"
                placeholder="مثال: الهواتف الذكية"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">
                {{ form.errors.name }}
            </p>
        </div>

        <!-- النوع -->
        <div>
            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                نوع الفئة <span class="text-red-500">*</span>
            </label>
            <select
                id="type"
                v-model="form.type"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:text-sm"
                :class="{ 'border-red-500 dark:border-red-500': form.errors.type }"
            >
                <option value="">اختر النوع</option>
                <option v-for="(label, value) in types" :key="value" :value="value">
                    {{ label }}
                </option>
            </select>
            <p v-if="form.errors.type" class="mt-1 text-sm text-red-500">
                {{ form.errors.type }}
            </p>
        </div>

        <!-- الوصف -->
        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                الوصف
            </label>
            <textarea
                id="description"
                v-model="form.description"
                rows="3"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm transition-colors focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-blue-400 sm:text-sm"
                placeholder="وصف مختصر للفئة (اختياري)"
            />
            <p v-if="form.errors.description" class="mt-1 text-sm text-red-500">
                {{ form.errors.description }}
            </p>
        </div>

        <!-- الحالة -->
        <div class="flex items-center gap-3">
            <input
                id="is_active"
                v-model="form.is_active"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300 text-blue-600 shadow-sm transition-colors focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700"
            />
            <label for="is_active" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                الفئة نشطة
            </label>
        </div>

        <!-- الأزرار -->
        <div class="flex items-center gap-3 pt-4">
            <button
                type="submit"
                :disabled="form.processing"
                class="flex items-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-70 dark:focus:ring-offset-gray-800"
            >
                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                </svg>
                <span>{{ form.processing ? 'جاري الحفظ...' : buttonText }}</span>
            </button>
            <Link
                :href="route('categories.index')"
                class="rounded-lg px-6 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                إلغاء
            </Link>
        </div>
    </form>
</template>

<script setup>
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    category: {
        type: Object,
        default: null,
    },
    types: {
        type: Object,
        required: true,
    },
    formAction: {
        type: String,
        required: true,
    },
    buttonText: {
        type: String,
        default: 'حفظ',
    },
});

const form = useForm({
    name: props.category?.name || '',
    type: props.category?.type || '',
    description: props.category?.description || '',
    is_active: props.category?.is_active ?? true,
});

const submit = () => {
    if (props.category) {
        form.put(props.formAction);
    } else {
        form.post(props.formAction);
    }
};
</script>
