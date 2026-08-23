<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        المركز المالي
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        إدارة الحسابات المالية والحركات اليومية
                    </p>
                </div>
                <div class="flex gap-2">
                    <button
                        @click="openAccountModal"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        إضافة حساب
                    </button>
                    <button
                        @click="openSyncModal"
                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        مزامنة الدفعات
                    </button>
                </div>
            </div>
        </template>

        <!-- البطاقات الإحصائية -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4 lg:grid-cols-6">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-gray-500 dark:text-gray-400">إجمالي الأرصدة</p>
                <p class="mt-1 text-xl font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(stats.total_balance) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-green-600 dark:text-green-400">صندوق الكاش</p>
                <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">
                    {{ formatCurrency(stats.cash_balance) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-blue-600 dark:text-blue-400">الحسابات البنكية</p>
                <p class="mt-1 text-xl font-bold text-blue-600 dark:text-blue-400">
                    {{ formatCurrency(stats.bank_balance) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-purple-600 dark:text-purple-400">التطبيقات البنكية</p>
                <p class="mt-1 text-xl font-bold text-purple-600 dark:text-purple-400">
                    {{ formatCurrency(stats.app_balance) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-green-600 dark:text-green-400">الوارد اليوم</p>
                <p class="mt-1 text-xl font-bold text-green-600 dark:text-green-400">
                    {{ formatCurrency(stats.today_inflows) }}
                </p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                <p class="text-xs text-red-600 dark:text-red-400">الصادر اليوم</p>
                <p class="mt-1 text-xl font-bold text-red-600 dark:text-red-400">
                    {{ formatCurrency(stats.today_outflows) }}
                </p>
            </div>
        </div>

        <!-- قائمة الحسابات -->
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="account in accounts"
                :key="account.id"
                :class="[
                    'cursor-pointer rounded-2xl border bg-white p-5 transition-all hover:-translate-y-0.5 hover:shadow-lg dark:bg-gray-800',
                    account.is_active
                        ? 'border-gray-200 hover:border-indigo-300 dark:border-gray-700'
                        : 'border-dashed border-gray-300 opacity-70 hover:border-gray-400 dark:border-gray-600'
                ]"
                @click="goToAccount(account.id)"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ account.name }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ account.type_label }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="[
                                account.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300'
                            ]"
                        >
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :class="account.is_active ? 'bg-green-500' : 'bg-gray-400'"
                            ></span>
                            {{ account.is_active ? 'نشط' : 'غير نشط' }}
                        </span>

                        <button
                            type="button"
                            class="rounded-lg border px-2.5 py-1.5 text-xs font-bold transition"
                            :class="account.is_active
                                ? 'border-rose-200 text-rose-600 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/30'
                                : 'border-emerald-200 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-900 dark:text-emerald-300 dark:hover:bg-emerald-950/30'"
                            :title="account.is_active ? 'تعطيل الحساب للعمليات الجديدة' : 'تفعيل الحساب'"
                            @click.stop="toggleAccount(account)"
                        >
                            {{ account.is_active ? 'تعطيل' : 'تفعيل' }}
                        </button>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold text-gray-900 dark:text-white">
                    {{ formatCurrency(account.current_balance) }}
                </p>
                <div class="mt-2 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span class="text-green-600 dark:text-green-400">
                        وارد: {{ formatCurrency(account.today_inflows) }}
                    </span>
                    <span class="text-red-600 dark:text-red-400">
                        صادر: {{ formatCurrency(account.today_outflows) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- العمليات السريعة -->
        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            <Link
                :href="route('finance.expenses')"
                class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">المصروفات</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">إدارة المصروفات اليومية</p>
                </div>
                <svg class="mr-auto h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </Link>

            <Link
                :href="route('finance.closings')"
                class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <svg class="h-6 w-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">الإغلاق اليومي</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">إغلاق اليوم المالي</p>
                </div>
                <svg class="mr-auto h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </Link>

            <button
                @click="openManualTransactionModal"
                class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-5 transition-all hover:border-blue-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-800"
            >
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-100 dark:bg-purple-900/30">
                    <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-gray-900 dark:text-white">إيداع / سحب يدوي</p>
                    <p class="text-sm text-gray-500 dark:text-gray-400">تسجيل حركة يدوية</p>
                </div>
                <svg class="mr-auto h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <!-- Modal إضافة حساب -->
        <Modal :show="accountModal.show" @close="accountModal.show = false">
            <template #title>إضافة حساب مالي</template>
            <template #content>
                <form @submit.prevent="submitAccount" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            اسم الحساب <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="accountForm.name"
                            type="text"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            نوع الحساب <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="accountForm.type"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        >
                            <option value="">اختر النوع</option>
                            <option v-for="(label, value) in accountTypes" :key="value" :value="value">
                                {{ label }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الرصيد الافتتاحي
                        </label>
                        <input
                            v-model.number="accountForm.opening_balance"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الوصف
                        </label>
                        <textarea
                            v-model="accountForm.description"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="وصف الحساب"
                        />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="accountModal.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="accountForm.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-70"
                        >
                            {{ accountForm.processing ? 'جاري الحفظ...' : 'حفظ' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>

        <!-- Modal مزامنة الدفعات -->
        <Modal :show="syncModal.show" @close="syncModal.show = false">
            <template #title>مزامنة الدفعات مع الحسابات المالية</template>
            <template #content>
                <form @submit.prevent="submitSync" class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        سيتم ربط الدفعات السابقة بالحساب المالي المحدد لإنشاء سجل مالي كامل.
                    </p>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الحساب المالي <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="syncForm.account_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        >
                            <option value="">اختر الحساب</option>
                            <option v-for="account in activeAccounts" :key="account.id" :value="account.id">
                                {{ account.name }}
                            </option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="syncModal.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="syncForm.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-70"
                        >
                            {{ syncForm.processing ? 'جاري المزامنة...' : 'مزامنة' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>

        <!-- Modal إيداع/سحب يدوي -->
        <Modal :show="manualModal.show" @close="manualModal.show = false">
            <template #title>إيداع / سحب يدوي</template>
            <template #content>
                <form @submit.prevent="submitManual" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الحساب <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="manualForm.account_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        >
                            <option value="">اختر الحساب</option>
                            <option v-for="account in activeAccounts" :key="account.id" :value="account.id">
                                {{ account.name }}
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            نوع الحركة <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="manualForm.type"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        >
                            <option value="">اختر النوع</option>
                            <option value="deposit">إيداع</option>
                            <option value="withdrawal">سحب</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            المبلغ <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model.number="manualForm.amount"
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
                            v-model="manualForm.date"
                            type="date"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            السبب <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="manualForm.reason"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="سبب الحركة"
                            required
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            ملاحظات
                        </label>
                        <textarea
                            v-model="manualForm.notes"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="ملاحظات إضافية"
                        />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="manualModal.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="manualForm.processing"
                            class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-70"
                        >
                            {{ manualForm.processing ? 'جاري التسجيل...' : 'تسجيل' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    accounts: Array,
    stats: Object,
    accountTypes: Object,
});

const activeAccounts = computed(() => (
    props.accounts?.filter((account) => account.is_active) ?? []
));

const accountModal = ref({ show: false });
const syncModal = ref({ show: false });
const manualModal = ref({ show: false });

const accountForm = useForm({
    name: '',
    type: '',
    opening_balance: 0,
    description: '',
    is_active: true,
});

const syncForm = useForm({
    account_id: '',
});

const manualForm = useForm({
    account_id: '',
    type: '',
    amount: '',
    date: new Date().toISOString().split('T')[0],
    reason: '',
    notes: '',
});

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};

const openAccountModal = () => {
    accountModal.value.show = true;
    accountForm.reset();
};

const openSyncModal = () => {
    syncModal.value.show = true;
    syncForm.reset();
};

const openManualTransactionModal = () => {
    manualModal.value.show = true;
    manualForm.reset();
    manualForm.date = new Date().toISOString().split('T')[0];
};

const goToAccount = (id) => {
    router.get(route('finance.show-account', id));
};

const toggleAccount = (account) => {
    const action = account.is_active ? 'تعطيل' : 'تفعيل';

    if (!window.confirm(`${action} حساب «${account.name}»؟`)) {
        return;
    }

    router.patch(
        route('finance.toggle-account', account.id),
        { is_active: !account.is_active },
        {
            preserveScroll: true,
        }
    );
};

const submitAccount = () => {
    accountForm.post(route('finance.store-account'), {
        preserveScroll: true,
        onSuccess: () => {
            accountModal.value.show = false;
        },
    });
};

const submitSync = () => {
    syncForm.post(route('finance.sync-payments'), {
        preserveScroll: true,
        onSuccess: () => {
            syncModal.value.show = false;
        },
    });
};

const submitManual = () => {
    manualForm.post(route('finance.manual-transaction'), {
        preserveScroll: true,
        onSuccess: () => {
            manualModal.value.show = false;
        },
    });
};
</script>
