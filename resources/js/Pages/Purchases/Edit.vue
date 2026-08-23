<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        تعديل فاتورة الشراء
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ invoice.invoice_number }}
                    </p>
                </div>
                <Link
                    :href="route('purchases.show', invoice.id)"
                    class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    العودة للتفاصيل
                </Link>
            </div>
        </template>

        <!-- نفس هيكل Create.vue مع تعبئة البيانات -->
        <form @submit.prevent="submit" class="space-y-6">
            <div v-if="pageError" class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm font-medium text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300">{{ pageError }}</div>
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- الجانب الأيسر -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- معلومات الفاتورة -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/30">
                                    <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">معلومات الفاتورة</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">تعديل بيانات الفاتورة</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 p-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        المورد <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.supplier_id"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        :class="{ 'border-red-500': form.errors.supplier_id }"
                                        required
                                    >
                                        <option value="">اختر المورد</option>
                                        <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                            {{ supplier.name }} {{ supplier.company_name ? '(' + supplier.company_name + ')' : '' }}
                                        </option>
                                    </select>
                                    <p v-if="form.errors.supplier_id" class="mt-1 text-sm text-red-500">{{ form.errors.supplier_id }}</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        تاريخ الشراء <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.purchase_date"
                                        type="date"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        :class="{ 'border-red-500': form.errors.purchase_date }"
                                        required
                                    />
                                    <p v-if="form.errors.purchase_date" class="mt-1 text-sm text-red-500">{{ form.errors.purchase_date }}</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        رقم فاتورة المورد
                                    </label>
                                    <input
                                        v-model="form.supplier_invoice_number"
                                        type="text"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        placeholder="رقم فاتورة المورد"
                                    />
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        تاريخ الاستحقاق
                                    </label>
                                    <input
                                        v-model="form.due_date"
                                        type="date"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    ملاحظات
                                </label>
                                <textarea
                                    v-model="form.notes"
                                    rows="2"
                                    class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    placeholder="ملاحظات إضافية"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- عناصر الفاتورة -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/30">
                                        <svg class="h-5 w-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">عناصر الفاتورة</h3>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">تعديل المنتجات والمخازن</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="addItem"
                                    class="flex items-center gap-1 rounded-lg bg-blue-600 px-3 py-1.5 text-sm text-white hover:bg-blue-700"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    إضافة منتج
                                </button>
                            </div>
                        </div>

                        <div class="p-4">
                            <!-- البحث عن المنتج -->
                            <div class="mb-4 relative">
                                <svg class="absolute right-3 top-2.5 h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    v-model="productSearch"
                                    type="text"
                                    placeholder="ابحث عن منتج..."
                                    class="w-full rounded-lg border-gray-300 pr-10 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                    @input="searchProducts"
                                />
                                <div v-if="searchResults.length > 0 && productSearch" class="absolute z-10 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800">
                                    <div
                                        v-for="product in searchResults"
                                        :key="product.id"
                                        @click="selectProduct(product)"
                                        class="flex cursor-pointer items-center justify-between px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700"
                                    >
                                        <div>
                                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ product.name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ product.code }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- جدول العناصر -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-900/50">
                                        <tr>
                                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">المنتج</th>
                                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">المخزن</th>
                                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الكمية</th>
                                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">سعر الشراء</th>
                                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الخصم</th>
                                            <th class="px-2 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الإجمالي</th>
                                            <th class="px-2 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        <tr v-if="form.items.length === 0">
                                            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                                لا توجد منتجات مضافة.
                                            </td>
                                        </tr>
                                        <tr v-for="(item, index) in form.items" :key="index" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                            <td class="px-2 py-2">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">{{ item.product_name }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.product_code }}</p>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2">
                                                <select
                                                    v-model="item.warehouse_id"
                                                    class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                >
                                                    <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                                        {{ warehouse.name }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model.number="item.quantity"
                                                    type="number"
                                                    min="1"
                                                    class="w-20 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                    @input="calculateItemTotal(index)"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model.number="item.unit_purchase_price"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="w-24 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                    @input="calculateItemTotal(index)"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model.number="item.line_discount"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    class="w-20 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                                    @input="calculateItemTotal(index)"
                                                />
                                            </td>
                                            <td class="px-2 py-2 text-sm font-medium text-gray-900 dark:text-white">
                                                {{ formatCurrency(item.line_total || 0) }}
                                            </td>
                                            <td class="px-2 py-2 text-center">
                                                <button
                                                    type="button"
                                                    @click="removeItem(index)"
                                                    class="rounded-lg p-1 text-red-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                                >
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- الجانب الأيمن: الملخص -->
                <div class="lg:col-span-1">
                    <div class="sticky top-6 space-y-4">
                        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                                <h4 class="font-semibold text-gray-900 dark:text-white">ملخص الفاتورة</h4>
                            </div>
                            <div class="space-y-2 p-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">الإجمالي</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(subtotal) }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">خصم الفاتورة</span>
                                    <input
                                        v-model.number="form.discount_amount"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-24 rounded-lg border-gray-300 text-right shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        @input="calculateTotals"
                                    />
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">الشحن</span>
                                    <input
                                        v-model.number="form.shipping_cost"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-24 rounded-lg border-gray-300 text-right shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        @input="calculateTotals"
                                    />
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">مصاريف إضافية</span>
                                    <input
                                        v-model.number="form.additional_expenses"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        class="w-24 rounded-lg border-gray-300 text-right shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                        @input="calculateTotals"
                                    />
                                </div>
                                <div class="border-t border-gray-200 pt-2 dark:border-gray-700">
                                    <div class="flex justify-between text-lg font-bold">
                                        <span class="text-gray-900 dark:text-white">الإجمالي النهائي</span>
                                        <span class="text-blue-600 dark:text-blue-400">{{ formatCurrency(total) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-6 py-2.5 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md disabled:opacity-70"
                            >
                                <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>
                                <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ form.processing ? 'جاري التحديث...' : 'تحديث الفاتورة' }}
                            </button>
                            <Link
                                :href="route('purchases.show', invoice.id)"
                                class="flex w-full items-center justify-center rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                            >
                                إلغاء
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    invoice: Object,
    suppliers: Array,
    warehouses: Array,
    products: Array,
    paymentMethods: Object,
});

const form = useForm({
    supplier_id: props.invoice.supplier_id,
    supplier_invoice_number: props.invoice.supplier_invoice_number || '',
    purchase_date: props.invoice.purchase_date,
    due_date: props.invoice.due_date || '',
    notes: props.invoice.notes || '',
    items: props.invoice.items.map(item => ({
        product_id: item.product_id,
        product_name: item.product.name,
        product_code: item.product.code,
        warehouse_id: item.warehouse_id,
        quantity: item.quantity,
        unit_purchase_price: item.unit_purchase_price,
        line_discount: item.line_discount || 0,
        line_total: item.line_total,
    })),
    discount_amount: props.invoice.discount_amount || 0,
    shipping_cost: props.invoice.shipping_cost || 0,
    additional_expenses: props.invoice.additional_expenses || 0,
});

const productSearch = ref('');
const pageError = ref('');
const searchResults = ref([]);

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.line_total || 0), 0);
});

const total = computed(() => {
    return subtotal.value - (form.discount_amount || 0) + (form.shipping_cost || 0) + (form.additional_expenses || 0);
});

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};

const searchProducts = () => {
    if (!productSearch.value) {
        searchResults.value = [];
        return;
    }

    const search = productSearch.value.toLowerCase();
    const existingIds = form.items.map(item => item.product_id);
    searchResults.value = props.products
        .filter(p =>
            !existingIds.includes(p.id) &&
            (p.name.toLowerCase().includes(search) ||
            p.code.toLowerCase().includes(search) ||
            (p.barcode && p.barcode.toLowerCase().includes(search)))
        )
        .slice(0, 10);
};

const selectProduct = (product) => {
    pageError.value = '';
    form.items.push({
        product_id: product.id,
        product_name: product.name,
        product_code: product.code,
        warehouse_id: props.warehouses[0]?.id || '',
        quantity: 1,
        unit_purchase_price: product.purchase_price || 0,
        line_discount: 0,
        line_total: product.purchase_price || 0,
    });

    productSearch.value = '';
    searchResults.value = [];
    calculateTotals();
};

const addItem = () => {
    productSearch.value = '';
    searchResults.value = [];
    document.querySelector('input[placeholder*="ابحث عن منتج"]')?.focus();
};

const removeItem = (index) => {
    form.items.splice(index, 1);
    calculateTotals();
};

const calculateItemTotal = (index) => {
    const item = form.items[index];
    const quantity = item.quantity || 0;
    const price = item.unit_purchase_price || 0;
    const discount = item.line_discount || 0;
    item.line_total = (quantity * price) - discount;
    if (item.line_total < 0) item.line_total = 0;
    calculateTotals();
};

const calculateTotals = () => {};

const submit = () => {
    pageError.value = '';
    if (form.items.length === 0) {
        pageError.value = 'يجب إضافة منتج واحد على الأقل';
        return;
    }

    form.put(route('purchases.update', props.invoice.id));
};
</script>
