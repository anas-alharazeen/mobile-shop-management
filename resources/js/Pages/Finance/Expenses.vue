<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        المصروفات
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        إدارة مصروفات المعرض اليومية
                    </p>
                </div>
                <button
                    @click="openExpenseModal"
                    class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-red-700 hover:shadow-md"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    إضافة مصروف
                </button>
            </div>
        </template>

        <!-- البطاقات الإحصائية -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">مصروفات اليوم</p>
                <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">
                    {{ formatCurrency(stats.today) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">مصروفات الشهر</p>
                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(stats.month) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">أكثر تصنيف</p>
                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                    {{ stats.top_category || '-' }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">عدد المصروفات</p>
                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                    {{ stats.count }}
                </p>
            </div>
        </div>

        <!-- الفلاتر -->
        <div class="mt-4 flex flex-wrap gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
            <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute right-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input
                    v-model="filters.search"
                    type="text"
                    placeholder="بحث برقم المصروف، الوصف، المستفيد..."
                    class="w-full rounded-lg border-gray-300 pr-10 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                    @input="applyFilters"
                />
            </div>

            <select
                v-model="filters.category_id"
                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                @change="applyFilters"
            >
                <option value="">جميع التصنيفات</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                    {{ cat.name }}
                </option>
            </select>

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

            <select
                v-model="filters.status"
                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                @change="applyFilters"
            >
                <option value="">جميع الحالات</option>
                <option value="posted">معتمد</option>
                <option value="cancelled">ملغي</option>
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
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">رقم المصروف</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">التصنيف</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الوصف</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الحساب</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">المبلغ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">التاريخ</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الحالة</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800">
                        <tr v-if="expenses.data.length === 0">
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                لا توجد مصروفات مسجلة
                            </td>
                        </tr>
                        <tr v-for="expense in expenses.data" :key="expense.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ expense.expense_number }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ expense.category?.name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ expense.description }}
                                <span v-if="expense.beneficiary" class="block text-xs text-gray-400">
                                    المستفيد: {{ expense.beneficiary }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ expense.account?.name }}
                            </td>
                            <td class="px-4 py-3 text-sm font-bold text-red-600 dark:text-red-400">
                                {{ formatCurrency(expense.amount) }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                {{ formatDate(expense.expense_date) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="[
                                        expense.status === 'posted'
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                    ]"
                                >
                                    {{ expense.status === 'posted' ? 'معتمد' : 'ملغي' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button
                                        v-if="expense.status === 'posted'"
                                        @click="openCancelModal(expense)"
                                        class="rounded-lg bg-red-100 px-3 py-1 text-xs text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-400"
                                    >
                                        إلغاء
                                    </button>
                                    <Link
                                        v-if="expense.attachment_path"
                                        :href="'/storage/' + expense.attachment_path"
                                        target="_blank"
                                        class="rounded-lg bg-gray-200 px-3 py-1 text-xs text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300"
                                    >
                                        المرفق
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 p-4 dark:border-gray-700">
                <Pagination :links="expenses.links" />
            </div>
        </div>

        <!-- Modal إضافة مصروف -->
        <Modal :show="expenseModal.show" @close="expenseModal.show = false">
            <template #title>تسجيل مصروف جديد</template>
            <template #content>
                <form @submit.prevent="submitExpense" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            التصنيف <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="expenseForm.expense_category_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        >
                            <option value="">اختر التصنيف</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الحساب المالي <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="expenseForm.financial_account_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        >
                            <option value="">اختر الحساب</option>
                            <option v-for="account in accounts" :key="account.id" :value="account.id">
                                {{ account.name }}
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                المبلغ <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model.number="expenseForm.amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                التاريخ <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model="expenseForm.expense_date"
                                type="date"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                required
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            المستفيد
                        </label>
                        <input
                            v-model="expenseForm.beneficiary"
                            type="text"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="اسم المستفيد"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الوصف <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="expenseForm.description"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="وصف المصروف"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            ملاحظات
                        </label>
                        <textarea
                            v-model="expenseForm.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="ملاحظات إضافية"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            مرفق
                        </label>
                        <input
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100 dark:text-gray-400 dark:file:bg-blue-900/30 dark:file:text-blue-400"
                            @change="handleAttachment"
                        />
                        <p class="mt-1 text-xs text-gray-400">JPG, PNG, WebP, PDF - حتى 5MB</p>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="expenseModal.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="expenseForm.processing"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-70"
                        >
                            {{ expenseForm.processing ? 'جاري الحفظ...' : 'تسجيل المصروف' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>

        <!-- Modal إلغاء مصروف -->
        <Modal :show="cancelModal.show" @close="cancelModal.show = false">
            <template #title>إلغاء المصروف</template>
            <template #content>
                <form @submit.prevent="submitCancel" class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        هل أنت متأكد من إلغاء المصروف
                        <span class="font-semibold text-gray-900 dark:text-white">{{ cancelModal.expense?.expense_number }}</span>؟
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            سبب الإلغاء <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="cancelForm.reason"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="سبب إلغاء المصروف"
                            required
                        />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="cancelModal.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="cancelForm.processing"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-70"
                        >
                            {{ cancelForm.processing ? 'جاري الإلغاء...' : 'تأكيد الإلغاء' }}
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
    expenses: Object,
    stats: Object,
    categories: Array,
    accounts: Array,
    filters: Object,
});

const filters = reactive({
    search: props.filters.search || '',
    category_id: props.filters.category_id || '',
    account_id: props.filters.account_id || '',
    status: props.filters.status || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

const expenseModal = ref({ show: false });
const cancelModal = ref({ show: false, expense: null });

const expenseForm = useForm({
    expense_category_id: '',
    financial_account_id: '',
    amount: '',
    expense_date: new Date().toISOString().split('T')[0],
    beneficiary: '',
    description: '',
    notes: '',
    attachment: null,
});

const cancelForm = useForm({
    reason: '',
});

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('ar-EG');
};

const applyFilters = () => {
    router.get(route('finance.expenses'), filters, { preserveState: true, replace: true });
};

const clearFilters = () => {
    filters.search = '';
    filters.category_id = '';
    filters.account_id = '';
    filters.status = '';
    filters.start_date = '';
    filters.end_date = '';
    applyFilters();
};

const openExpenseModal = () => {
    expenseModal.value.show = true;
    expenseForm.reset();
    expenseForm.expense_date = new Date().toISOString().split('T')[0];
};

const handleAttachment = (event) => {
    expenseForm.attachment = event.target.files[0];
};

const submitExpense = () => {
    const formData = new FormData();
    Object.keys(expenseForm.data()).forEach(key => {
        if (key === 'attachment' && expenseForm.attachment) {
            formData.append('attachment', expenseForm.attachment);
        } else {
            formData.append(key, expenseForm[key]);
        }
    });

    expenseForm.post(route('finance.store-expense'), {
        data: formData,
        preserveScroll: true,
        onSuccess: () => {
            expenseModal.value.show = false;
        },
    });
};

const openCancelModal = (expense) => {
    cancelModal.value = {
        show: true,
        expense: expense,
    };
    cancelForm.reason = '';
};

const submitCancel = () => {
    cancelForm.post(route('finance.cancel-expense', cancelModal.value.expense.id), {
        preserveScroll: true,
        onSuccess: () => {
            cancelModal.value.show = false;
        },
    });
};
</script>
