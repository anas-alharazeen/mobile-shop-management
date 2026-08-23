<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ pageTitle }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ supplier ? 'تعديل بيانات المورد' : 'إضافة مورد جديد للمعرض' }}
                    </p>
                </div>
                <Link
                    :href="route('suppliers.index')"
                    class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    العودة للقائمة
                </Link>
            </div>
        </template>

        <div class="mx-auto max-w-3xl">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- المعلومات الأساسية -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                                <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">المعلومات الأساسية</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">بيانات المورد الرئيسية</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    اسم المورد <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    :class="{ 'border-red-500': form.errors.name }"
                                    placeholder="أدخل اسم المورد"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    اسم الشركة
                                </label>
                                <input
                                    v-model="form.company_name"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    placeholder="اسم الشركة أو المحل"
                                />
                            </div>
                        </div>

                        <div v-if="supplier" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    كود المورد
                                </label>
                                <input
                                    :value="supplier.code"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border-gray-300 bg-gray-100 shadow-sm dark:border-gray-600 dark:bg-gray-600 dark:text-gray-400 sm:text-sm"
                                    disabled
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معلومات التواصل -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/30">
                                <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">معلومات التواصل</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">أرقام التواصل والبريد الإلكتروني</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    رقم الهاتف <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.phone"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    :class="{ 'border-red-500': form.errors.phone }"
                                    placeholder="0599123456"
                                />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-500">{{ form.errors.phone }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    رقم واتساب
                                </label>
                                <input
                                    v-model="form.whatsapp"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    placeholder="0599123456"
                                />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    البريد الإلكتروني
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    :class="{ 'border-red-500': form.errors.email }"
                                    placeholder="example@domain.com"
                                />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    العنوان
                                </label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    placeholder="العنوان الكامل"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- معلومات إضافية -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/30">
                                <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">معلومات إضافية</h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400">ملاحظات وحالة المورد</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                ملاحظات
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                placeholder="ملاحظات إضافية عن المورد..."
                            />
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="form.is_active = !form.is_active"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                :class="[form.is_active ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-600']"
                            >
                                <span
                                    class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out"
                                    :class="[form.is_active ? 'translate-x-5' : 'translate-x-0']"
                                />
                            </button>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                المورد نشط
                            </label>
                        </div>
                    </div>
                </div>

                <!-- الأزرار -->
                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link
                        :href="route('suppliers.index')"
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
                        <span>{{ form.processing ? 'جاري الحفظ...' : (supplier ? 'تحديث المورد' : 'إضافة المورد') }}</span>
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
    supplier: {
        type: Object,
        default: null,
    },
    pageTitle: {
        type: String,
        required: true,
    },
});

const form = useForm({
    name: props.supplier?.name || '',
    company_name: props.supplier?.company_name || '',
    phone: props.supplier?.phone || '',
    whatsapp: props.supplier?.whatsapp || '',
    email: props.supplier?.email || '',
    address: props.supplier?.address || '',
    notes: props.supplier?.notes || '',
    is_active: props.supplier?.is_active ?? true,
});

const submit = () => {
    if (props.supplier) {
        form.put(route('suppliers.update', props.supplier.id));
    } else {
        form.post(route('suppliers.store'));
    }
};
</script>
