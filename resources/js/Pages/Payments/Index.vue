<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        مركز الدفعات
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        إدارة ومتابعة جميع الدفعات والديون
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('payments.all-payments')"
                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        سجل الدفعات
                    </Link>
                </div>
            </div>
        </template>

        <!-- البطاقات الإحصائية -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">مستحقات العملاء</p>
                <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">
                    {{ formatCurrency(stats.customer_receivables) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">مستحقات الموردين</p>
                <p class="mt-1 text-xl font-bold text-orange-600 dark:text-orange-400">
                    {{ formatCurrency(stats.supplier_payables) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-green-600 dark:text-green-400">محصلة اليوم</p>
                <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">
                    {{ formatCurrency(stats.collected_today) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-blue-600 dark:text-blue-400">مدفوع للموردين اليوم</p>
                <p class="mt-1 text-xl font-bold text-blue-600 dark:text-blue-400">
                    {{ formatCurrency(stats.paid_to_suppliers_today) }}
                </p>
            </div>
        </div>

        <!-- بطاقات إضافية -->
        <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-red-600 dark:text-red-400">دفعات عملاء متأخرة</p>
                <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">
                    {{ stats.overdue_customers_count }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatCurrency(stats.overdue_customers_amount) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-orange-600 dark:text-orange-400">دفعات موردين متأخرة</p>
                <p class="mt-1 text-xl font-bold text-orange-600 dark:text-orange-400">
                    {{ stats.overdue_suppliers_count }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    {{ formatCurrency(stats.overdue_suppliers_amount) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">فواتير غير مدفوعة</p>
                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                    {{ stats.unpaid_invoices }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-yellow-600 dark:text-yellow-400">مدفوعة جزئياً</p>
                <p class="mt-1 text-xl font-bold text-yellow-600 dark:text-yellow-400">
                    {{ stats.partially_paid_invoices }}
                </p>
            </div>
        </div>

        <!-- روابط سريعة -->
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Link
                :href="route('payments.customer-receivables')"
                class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">مستحقات العملاء</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">عرض جميع المستحقات</p>
                </div>
                <svg class="mr-auto h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </Link>

            <Link
                :href="route('payments.supplier-payables')"
                class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-orange-100 dark:bg-orange-900/30">
                    <svg class="h-6 w-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">مستحقات الموردين</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">عرض جميع المستحقات</p>
                </div>
                <svg class="mr-auto h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </Link>

            <Link
                :href="route('payments.all-payments')"
                class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">سجل الدفعات</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">عرض جميع الدفعات</p>
                </div>
                <svg class="mr-auto h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </Link>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    stats: Object,
    activeTab: String,
});

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};
</script>
