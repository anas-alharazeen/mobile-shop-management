<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        الإغلاق اليومي
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        إغلاق اليوم المالي ومتابعة الأرصدة
                    </p>
                </div>
                <button
                    @click="openClosingModal"
                    class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    إغلاق يوم جديد
                </button>
            </div>
        </template>

        <!-- الفلاتر -->
        <div class="mt-4 flex flex-wrap gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <select
                v-model="filters.account_id"
                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                @change="applyFilters"
            >
                <option value="">جميع الحسابات</option>
                <option v-for="account in accounts" :key="account.id" :value="account.id">
                    {{ account.name }}
                </option>
            </select>

            <input
                v-model="filters.start_date"
                type="date"
                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                @change="applyFilters"
            />

            <input
                v-model="filters.end_date"
                type="date"
                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                @change="applyFilters"
            />

            <button
                @click="clearFilters"
                class="flex items-center gap-1 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                مسح
            </button>
        </div>

        <!-- الجدول -->
        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                        <tr>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">التاريخ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الحساب</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الرصيد الافتتاحي</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الوارد</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الصادر</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">المتوقع</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الفعلي</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الفرق</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الحالة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        <tr v-if="closings.data.length === 0">
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                لا توجد إغلاقات يومية
                            </td>
                        </tr>
                        <tr v-for="closing in closings.data" :key="closing.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ formatDate(closing.closing_date) }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ closing.account?.name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ formatCurrency(closing.opening_balance) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-green-600 dark:text-green-400">
                                {{ formatCurrency(closing.total_inflows) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">
                                {{ formatCurrency(closing.total_outflows) }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ formatCurrency(closing.expected_balance) }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ formatCurrency(closing.actual_balance) }}
                            </td>
                            <td class="px-4 py-3 text-sm font-bold">
                                <span
                                    :class="{
                                        'text-green-600 dark:text-green-400': closing.difference === 0,
                                        'text-blue-600 dark:text-blue-400': closing.difference > 0,
                                        'text-red-600 dark:text-red-400': closing.difference < 0
                                    }"
                                >
                                    {{ formatCurrency(closing.difference) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="{
                                        'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': closing.difference === 0,
                                        'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': closing.difference > 0,
                                        'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': closing.difference < 0
                                    }"
                                >
                                    {{ closing.difference === 0 ? 'مطابق' : closing.difference > 0 ? 'زيادة' : 'عجز' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                <Pagination :links="closings.links" />
            </div>
        </div>

        <!-- Modal إغلاق يوم جديد -->
        <Modal :show="closingModal.show" @close="closingModal.show = false">
            <template #title>إغلاق يوم جديد</template>
            <template #content>
                <form @submit.prevent="submitClosing" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الحساب <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="closingForm.financial_account_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        >
                            <option value="">اختر الحساب</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            تاريخ الإغلاق <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="closingForm.closing_date"
                            type="date"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الرصيد الفعلي <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model.number="closingForm.actual_balance"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            ملاحظات
                        </label>
                        <textarea
                            v-model="closingForm.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="ملاحظات الإغلاق"
                        />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="closingModal.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="closingForm.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-70"
                        >
                            {{ closingForm.processing ? 'جاري الإغلاق...' : 'تأكيد الإغلاق' }}
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
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    closings: Object,
    accounts: Array,
    filters: Object,
});

const filters = reactive({
    account_id: props.filters.account_id || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const closingModal = ref({ show: false });

const closingForm = useForm({
    financial_account_id: '',
    closing_date: new Date().toISOString().split('T')[0],
    actual_balance: '',
    notes: '',
});

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ar-EG');
};

const applyFilters = () => {
    router.get(route('finance.closings'), filters, { preserveState: true, replace: true });
};

const clearFilters = () => {
    filters.account_id = '';
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const openClosingModal = () => {
    closingModal.value.show = true;
    closingForm.reset();
    closingForm.closing_date = new Date().toISOString().split('T')[0];
};

const submitClosing = () => {
    closingForm.post(route('finance.closing'), {
        preserveScroll: true,
        onSuccess: () => {
            closingModal.value.show = false;
        },
    });
};
</script>
