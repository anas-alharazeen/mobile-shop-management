<script setup>
import {
    computed,
    nextTick,
    ref,
    watch,
} from 'vue';

import {
    Head,
    Link,
    useForm,
} from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    suppliers: {
        type: Array,
        default: () => [],
    },

    warehouses: {
        type: Array,
        default: () => [],
    },

    products: {
        type: Array,
        default: () => [],
    },

    paymentMethods: {
        type: Object,
        default: () => ({}),
    },

    /*
     * اختياري:
     * إذا كان PurchaseInvoiceController يرسل الحسابات المالية
     * ستظهر هنا. وإذا لم يرسلها فلن تتعطل الصفحة.
     */
    financialAccounts: {
        type: Array,
        default: () => [],
    },
});

const localDate = (date = new Date()) => {
    const offset = date.getTimezoneOffset();

    return new Date(
        date.getTime() - offset * 60 * 1000
    )
        .toISOString()
        .slice(0, 10);
};

const today = localDate();

const form = useForm({
    supplier_id: '',
    supplier_invoice_number: '',
    purchase_date: today,
    due_date: '',
    notes: '',

    items: [],

    discount_amount: 0,
    shipping_cost: 0,
    additional_expenses: 0,

    payment_amount: 0,
    payment_method: 'cash',
    financial_account_id: '',
    bank_or_app_name: '',
    transaction_reference: '',
});

const productSearch = ref('');
const productSearchInput = ref(null);
const searchOpen = ref(false);
const pageError = ref('');

const normalize = (value) => String(value ?? '')
    .trim()
    .toLocaleLowerCase();

const enumValue = (value) => (
    value && typeof value === 'object'
        ? (value.value ?? value.name ?? '')
        : String(value ?? '')
);

const filteredProducts = computed(() => {
    const search = normalize(productSearch.value);

    if (!search) {
        return [];
    }

    return props.products
        .filter((product) => [
            product.name,
            product.code,
            product.barcode,
        ].some(
            (value) => normalize(value).includes(search)
        ))
        .slice(0, 12);
});

const selectedSupplier = computed(() => (
    props.suppliers.find(
        (supplier) =>
            Number(supplier.id)
            === Number(form.supplier_id)
    ) ?? null
));

const subtotal = computed(() => (
    form.items.reduce(
        (sum, item) =>
            sum + Number(item.line_total || 0),
        0
    )
));

const discountAmount = computed(() => Math.max(
    0,
    Number(form.discount_amount || 0)
));

const shippingCost = computed(() => Math.max(
    0,
    Number(form.shipping_cost || 0)
));

const additionalExpenses = computed(() => Math.max(
    0,
    Number(form.additional_expenses || 0)
));

const total = computed(() => Math.max(
    0,
    subtotal.value
        - discountAmount.value
        + shippingCost.value
        + additionalExpenses.value
));

const paymentAmount = computed(() => Math.max(
    0,
    Number(form.payment_amount || 0)
));

const remaining = computed(() => Math.max(
    0,
    total.value - paymentAmount.value
));

const expectedAccountType = computed(() => ({
    cash: 'cash',
    bank_transfer: 'bank',
    banking_app: 'banking_app',
}[form.payment_method] ?? null));

const compatibleAccounts = computed(() => {
    if (!expectedAccountType.value) {
        return [];
    }

    return props.financialAccounts.filter(
        (account) =>
            account.is_active !== false
            && enumValue(account.type)
                === expectedAccountType.value
    );
});

const selectedFinancialAccount = computed(() => (
    props.financialAccounts.find(
        (account) =>
            Number(account.id)
            === Number(form.financial_account_id)
    ) ?? null
));

const invoiceDiscountError = computed(() => {
    if (discountAmount.value > subtotal.value) {
        return 'خصم الفاتورة لا يمكن أن يتجاوز مجموع المنتجات.';
    }

    return '';
});

const itemsError = computed(() => {
    for (const item of form.items) {
        const quantity = Number(item.quantity || 0);
        const price = Number(item.unit_purchase_price || 0);
        const discount = Number(item.line_discount || 0);
        const lineSubtotal = quantity * price;

        if (!item.warehouse_id) {
            return `اختر المخزن للمنتج ${item.product_name}.`;
        }

        if (quantity < 1) {
            return `كمية المنتج ${item.product_name} يجب أن تكون 1 على الأقل.`;
        }

        if (price < 0) {
            return `سعر شراء المنتج ${item.product_name} غير صالح.`;
        }

        if (discount < 0 || discount > lineSubtotal) {
            return `خصم المنتج ${item.product_name} لا يمكن أن يتجاوز قيمة البند.`;
        }
    }

    return '';
});

const accountBalanceWarning = computed(() => {
    if (
        paymentAmount.value <= 0
        || !selectedFinancialAccount.value
    ) {
        return '';
    }

    const balance = Number(
        selectedFinancialAccount.value.current_balance || 0
    );

    if (paymentAmount.value > balance) {
        return `رصيد الحساب الحالي ${formatCurrency(balance)} أقل من الدفعة. يمكن حفظ المسودة، لكن الاعتماد سيفشل إذا بقي الرصيد غير كافٍ.`;
    }

    return '';
});

const paymentError = computed(() => {
    if (paymentAmount.value > total.value) {
        return 'المبلغ المدفوع لا يمكن أن يتجاوز إجمالي الفاتورة.';
    }

    if (
        paymentAmount.value > 0
        && !form.payment_method
    ) {
        return 'اختر طريقة الدفع.';
    }

    if (
        paymentAmount.value > 0
        && !form.financial_account_id
    ) {
        return 'اختر الحساب المالي الذي ستسجل عليه الدفعة.';
    }

    return '';
});

const dateError = computed(() => {
    if (
        form.due_date
        && form.purchase_date
        && form.due_date < form.purchase_date
    ) {
        return 'تاريخ الاستحقاق يجب ألا يسبق تاريخ الشراء.';
    }

    return '';
});

const canSubmit = computed(() => Boolean(
    form.supplier_id
    && form.purchase_date
    && form.items.length > 0
    && !paymentError.value
    && !dateError.value
    && !invoiceDiscountError.value
    && !itemsError.value
    && !form.processing
));

const formatCurrency = (value) => (
    `${Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`
);

const fieldError = (field) =>
    form.errors[field] ?? '';

const lineError = (index, field) =>
    form.errors[`items.${index}.${field}`] ?? '';

const calculateItem = (item) => {
    const quantity = Math.max(
        0,
        Number(item.quantity || 0)
    );

    const price = Math.max(
        0,
        Number(item.unit_purchase_price || 0)
    );

    const discount = Math.max(
        0,
        Number(item.line_discount || 0)
    );

    item.line_total = Math.max(
        0,
        (quantity * price) - discount
    );
};

const chooseDefaultWarehouse = () => {
    const activeWarehouse = props.warehouses.find(
        (warehouse) => warehouse.is_active !== false
    );

    return activeWarehouse?.id
        ?? props.warehouses[0]?.id
        ?? '';
};

const selectProduct = (product) => {
    pageError.value = '';

    const exists = form.items.some(
        (item) =>
            Number(item.product_id)
            === Number(product.id)
    );

    if (exists) {
        pageError.value =
            'هذا المنتج موجود بالفعل في الفاتورة. عدّل كميته من الجدول.';

        productSearch.value = '';
        searchOpen.value = false;

        return;
    }

    const item = {
        product_id: product.id,
        product_name: product.name,
        product_code: product.code ?? '',
        warehouse_id: chooseDefaultWarehouse(),
        quantity: 1,
        unit_purchase_price: Number(
            product.purchase_price || 0
        ),
        line_discount: 0,
        line_total: 0,
    };

    calculateItem(item);

    form.items.push(item);

    productSearch.value = '';
    searchOpen.value = false;
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const focusProductSearch = async () => {
    await nextTick();
    productSearchInput.value?.focus();
};

const setPayment = (mode) => {
    if (mode === 'none') {
        form.payment_amount = 0;
        return;
    }

    if (mode === 'half') {
        form.payment_amount = Number(
            (total.value / 2).toFixed(2)
        );
        return;
    }

    if (mode === 'full') {
        form.payment_amount = Number(
            total.value.toFixed(2)
        );
    }
};

const validateBeforeSubmit = () => {
    pageError.value = '';

    if (!form.supplier_id) {
        pageError.value = 'اختر المورد.';
        return false;
    }

    if (!form.purchase_date) {
        pageError.value = 'حدد تاريخ الشراء.';
        return false;
    }

    if (form.items.length === 0) {
        pageError.value =
            'يجب إضافة منتج واحد على الأقل إلى الفاتورة.';
        return false;
    }

    const invalidItem = form.items.find(
        (item) =>
            !item.product_id
            || !item.warehouse_id
            || Number(item.quantity || 0) < 1
            || Number(item.unit_purchase_price || 0) < 0
    );

    if (invalidItem) {
        pageError.value =
            'راجع بيانات المنتجات: المنتج والمخزن والكمية والسعر مطلوبة.';
        return false;
    }

    if (dateError.value) {
        pageError.value = dateError.value;
        return false;
    }

    if (itemsError.value) {
        pageError.value = itemsError.value;
        return false;
    }

    if (invoiceDiscountError.value) {
        pageError.value = invoiceDiscountError.value;
        return false;
    }

    if (paymentError.value) {
        pageError.value = paymentError.value;
        return false;
    }

    return true;
};

const submit = () => {
    if (!validateBeforeSubmit()) {
        return;
    }

    /*
     * هذا هو المسار الصحيح لفاتورة الشراء.
     * الملف الخاطئ السابق كان يرسل إلى repairs.store.
     */
    form.post(
        route('purchases.store'),
        {
            preserveScroll: true,

            onError: () => {
                pageError.value =
                    'تعذر حفظ فاتورة الشراء. راجع الحقول المحددة.';
            },
        }
    );
};

watch(
    () => form.payment_method,
    () => {
        const selectedIsCompatible =
            compatibleAccounts.value.some(
                (account) =>
                    Number(account.id)
                    === Number(
                        form.financial_account_id
                    )
            );

        if (!selectedIsCompatible) {
            form.financial_account_id =
                compatibleAccounts.value[0]?.id
                ?? '';
        }

        if (form.payment_method === 'cash') {
            form.bank_or_app_name = '';
            form.transaction_reference = '';
        } else if (
            selectedFinancialAccount.value
            && !form.bank_or_app_name
        ) {
            form.bank_or_app_name =
                selectedFinancialAccount.value.name
                ?? '';
        }
    },
    {
        immediate: true,
    }
);

watch(
    () => form.financial_account_id,
    () => {
        if (
            form.payment_method !== 'cash'
            && selectedFinancialAccount.value
        ) {
            form.bank_or_app_name =
                selectedFinancialAccount.value.name
                ?? form.bank_or_app_name;
        }
    }
);
</script>

<template>
    <Head title="فاتورة شراء جديدة" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-black text-indigo-600 dark:text-indigo-400">
                        <span>المشتريات</span>
                        <span class="text-slate-300 dark:text-slate-600">/</span>
                        <span>فاتورة جديدة</span>
                    </div>

                    <h1 class="mt-2 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                        إضافة فاتورة شراء جديدة
                    </h1>

                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        أنشئ الفاتورة كمسودة، راجع التكلفة والدفعة، ثم اعتمدها لإضافة المخزون وترحيل الحركة المالية.
                    </p>
                </div>

                <Link
                    :href="route('purchases.index')"
                    class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                >
                    العودة إلى المشتريات
                </Link>
            </div>
        </template>

        <form
            class="space-y-6"
            @submit.prevent="submit"
        >
            <div
                v-if="pageError"
                class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300"
            >
                {{ pageError }}
            </div>

            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
                <div class="min-w-0 space-y-6">
                    <!-- Invoice info -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 bg-gradient-to-l from-indigo-50 to-white px-5 py-5 dark:border-slate-700 dark:from-indigo-950/40 dark:to-slate-800">
                            <h2 class="font-black text-slate-950 dark:text-white">
                                بيانات الفاتورة والمورد
                            </h2>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                المعلومات الأساسية الخاصة بفاتورة الشراء.
                            </p>
                        </div>

                        <div class="grid gap-5 p-5 sm:p-6 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    المورد
                                    <span class="text-rose-500">*</span>
                                </label>

                                <select
                                    v-model="form.supplier_id"
                                    class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    :class="{ 'border-rose-500': fieldError('supplier_id') }"
                                >
                                    <option value="">
                                        اختر المورد
                                    </option>

                                    <option
                                        v-for="supplier in suppliers"
                                        :key="supplier.id"
                                        :value="supplier.id"
                                    >
                                        {{ supplier.name }}
                                        {{ supplier.company_name ? `— ${supplier.company_name}` : '' }}
                                    </option>
                                </select>

                                <p
                                    v-if="fieldError('supplier_id')"
                                    class="mt-2 text-xs font-bold text-rose-600"
                                >
                                    {{ fieldError('supplier_id') }}
                                </p>

                                <div
                                    v-if="selectedSupplier"
                                    class="mt-3 rounded-xl bg-indigo-50 p-3 text-xs text-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-200"
                                >
                                    <strong class="block">
                                        {{ selectedSupplier.name }}
                                    </strong>

                                    <span v-if="selectedSupplier.phone">
                                        {{ selectedSupplier.phone }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    تاريخ الشراء
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model="form.purchase_date"
                                    type="date"
                                    :max="today"
                                    class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                />

                                <p
                                    v-if="fieldError('purchase_date')"
                                    class="mt-2 text-xs font-bold text-rose-600"
                                >
                                    {{ fieldError('purchase_date') }}
                                </p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    رقم فاتورة المورد
                                </label>

                                <input
                                    v-model.trim="form.supplier_invoice_number"
                                    type="text"
                                    maxlength="255"
                                    placeholder="مثال: SUP-4582"
                                    class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    تاريخ الاستحقاق
                                </label>

                                <input
                                    v-model="form.due_date"
                                    type="date"
                                    :min="form.purchase_date"
                                    class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    :class="{ 'border-rose-500': dateError }"
                                />

                                <p
                                    v-if="dateError"
                                    class="mt-2 text-xs font-bold text-rose-600"
                                >
                                    {{ dateError }}
                                </p>
                            </div>

                            <div class="md:col-span-2">
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    ملاحظات
                                </label>

                                <textarea
                                    v-model.trim="form.notes"
                                    rows="3"
                                    maxlength="500"
                                    placeholder="ملاحظات إضافية حول الفاتورة..."
                                    class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                ></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- Items -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="font-black text-slate-950 dark:text-white">
                                    منتجات الفاتورة
                                </h2>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    ابحث عن المنتج ثم حدد الكمية والمخزن وسعر الشراء.
                                </p>
                            </div>

                            <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                {{ form.items.length }} منتج
                            </span>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <div class="relative">
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    البحث عن منتج
                                </label>

                                <input
                                    ref="productSearchInput"
                                    v-model="productSearch"
                                    type="search"
                                    autocomplete="off"
                                    placeholder="ابحث باسم المنتج أو الكود أو الباركود..."
                                    class="block w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3.5 text-sm font-semibold focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100 dark:border-slate-600 dark:bg-slate-900 dark:text-white dark:focus:ring-indigo-900/30"
                                    @focus="searchOpen = true"
                                    @input="searchOpen = true"
                                />

                                <div
                                    v-if="searchOpen && productSearch"
                                    class="absolute inset-x-0 top-full z-40 mt-2 max-h-80 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                                >
                                    <button
                                        v-for="product in filteredProducts"
                                        :key="product.id"
                                        type="button"
                                        class="flex w-full items-center justify-between gap-4 rounded-xl p-3 text-right transition hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                                        @click="selectProduct(product)"
                                    >
                                        <div>
                                            <strong class="block text-sm text-slate-950 dark:text-white">
                                                {{ product.name }}
                                            </strong>
                                            <span
                                                dir="ltr"
                                                class="mt-1 block text-right text-xs text-slate-400"
                                            >
                                                {{ product.code || 'بدون كود' }}
                                            </span>
                                        </div>

                                        <span class="text-xs font-black text-indigo-600 dark:text-indigo-300">
                                            {{ formatCurrency(product.purchase_price) }}
                                        </span>
                                    </button>

                                    <div
                                        v-if="!filteredProducts.length"
                                        class="px-4 py-8 text-center text-sm text-slate-500"
                                    >
                                        لا يوجد منتج مطابق.
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-xs font-black text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-300"
                                @click="focusProductSearch"
                            >
                                + إضافة منتج
                            </button>

                            <div
                                v-if="form.items.length"
                                class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700"
                            >
                                <table class="min-w-[950px] w-full text-sm">
                                    <thead class="bg-slate-950 text-white">
                                        <tr>
                                            <th class="px-3 py-3 text-right">المنتج</th>
                                            <th class="px-3 py-3 text-right">المخزن</th>
                                            <th class="px-3 py-3 text-right">الكمية</th>
                                            <th class="px-3 py-3 text-right">سعر الشراء</th>
                                            <th class="px-3 py-3 text-right">خصم السطر</th>
                                            <th class="px-3 py-3 text-right">الإجمالي</th>
                                            <th class="px-3 py-3"></th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                        <tr
                                            v-for="(item, index) in form.items"
                                            :key="`${item.product_id}-${index}`"
                                        >
                                            <td class="px-3 py-3">
                                                <strong class="block text-slate-950 dark:text-white">
                                                    {{ item.product_name }}
                                                </strong>
                                                <span
                                                    dir="ltr"
                                                    class="block text-right text-xs text-slate-400"
                                                >
                                                    {{ item.product_code || '—' }}
                                                </span>
                                            </td>

                                            <td class="px-3 py-3">
                                                <select
                                                    v-model="item.warehouse_id"
                                                    class="w-44 rounded-lg border-slate-300 text-xs dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                    :class="{ 'border-rose-500': lineError(index, 'warehouse_id') }"
                                                >
                                                    <option value="">اختر المخزن</option>
                                                    <option
                                                        v-for="warehouse in warehouses"
                                                        :key="warehouse.id"
                                                        :value="warehouse.id"
                                                    >
                                                        {{ warehouse.name }}
                                                    </option>
                                                </select>
                                            </td>

                                            <td class="px-3 py-3">
                                                <input
                                                    v-model.number="item.quantity"
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    class="w-24 rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                    @input="calculateItem(item)"
                                                />
                                            </td>

                                            <td class="px-3 py-3">
                                                <input
                                                    v-model.number="item.unit_purchase_price"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="w-32 rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                    @input="calculateItem(item)"
                                                />
                                            </td>

                                            <td class="px-3 py-3">
                                                <input
                                                    v-model.number="item.line_discount"
                                                    type="number"
                                                    min="0"
                                                    :max="Number(item.quantity || 0) * Number(item.unit_purchase_price || 0)"
                                                    step="0.01"
                                                    class="w-28 rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                    @input="calculateItem(item)"
                                                />
                                            </td>

                                            <td class="px-3 py-3 font-black text-indigo-700 dark:text-indigo-300">
                                                {{ formatCurrency(item.line_total) }}
                                            </td>

                                            <td class="px-3 py-3">
                                                <button
                                                    type="button"
                                                    class="rounded-lg bg-rose-50 px-2.5 py-2 text-xs font-black text-rose-600 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-300"
                                                    @click="removeItem(index)"
                                                >
                                                    حذف
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div
                                v-else
                                class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-900/40"
                            >
                                <p class="font-black text-slate-700 dark:text-slate-200">
                                    لم تتم إضافة منتجات بعد
                                </p>
                                <p class="mt-2 text-sm text-slate-500">
                                    استخدم مربع البحث لإضافة أول منتج.
                                </p>
                            </div>

                            <p
                                v-if="itemsError"
                                class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                            >
                                {{ itemsError }}
                            </p>
                        </div>
                    </section>
                </div>

                <!-- Summary -->
                <aside class="space-y-5 xl:sticky xl:top-6">
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="bg-gradient-to-l from-slate-950 via-indigo-950 to-indigo-800 p-5 text-white">
                            <p class="text-xs font-bold text-indigo-200">
                                ملخص فاتورة الشراء
                            </p>

                            <p class="mt-2 text-xl font-black">
                                {{ selectedSupplier?.company_name || selectedSupplier?.name || 'اختر المورد' }}
                            </p>

                            <p class="mt-1 text-sm text-indigo-200">
                                {{ form.items.length }} منتج في الفاتورة
                            </p>
                        </div>

                        <div class="space-y-4 p-5">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-slate-500">مجموع المنتجات</span>
                                <strong>{{ formatCurrency(subtotal) }}</strong>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <label class="text-sm text-slate-500">
                                    خصم الفاتورة
                                </label>

                                <input
                                    v-model.number="form.discount_amount"
                                    type="number"
                                    min="0"
                                    :max="subtotal"
                                    step="0.01"
                                    class="w-32 rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    :class="{ 'border-rose-500': invoiceDiscountError }"
                                />

                                <p
                                    v-if="invoiceDiscountError"
                                    class="mt-1 text-[10px] font-bold text-rose-600"
                                >
                                    {{ invoiceDiscountError }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <label class="text-sm text-slate-500">
                                    تكلفة الشحن
                                </label>

                                <input
                                    v-model.number="form.shipping_cost"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-32 rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                />
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <label class="text-sm text-slate-500">
                                    مصاريف إضافية
                                </label>

                                <input
                                    v-model.number="form.additional_expenses"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="w-32 rounded-lg border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                />
                            </div>

                            <div class="border-t border-slate-200 pt-4 dark:border-slate-700">
                                <div class="flex items-center justify-between">
                                    <span class="font-black text-slate-950 dark:text-white">
                                        الإجمالي النهائي
                                    </span>
                                    <strong class="text-xl text-indigo-600 dark:text-indigo-300">
                                        {{ formatCurrency(total) }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                            <h2 class="font-black text-slate-950 dark:text-white">
                                الدفعة الأولية
                            </h2>
                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                تحفظ مع المسودة، ويحدث الخصم الفعلي من الحساب المالي عند اعتماد الفاتورة.
                            </p>
                        </div>

                        <div class="space-y-4 p-5">
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 px-2 py-2 text-xs font-black dark:border-slate-700"
                                    @click="setPayment('none')"
                                >
                                    بدون
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 px-2 py-2 text-xs font-black dark:border-slate-700"
                                    :disabled="total <= 0"
                                    @click="setPayment('half')"
                                >
                                    النصف
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl border border-indigo-200 bg-indigo-50 px-2 py-2 text-xs font-black text-indigo-700 dark:border-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-300"
                                    :disabled="total <= 0"
                                    @click="setPayment('full')"
                                >
                                    كامل
                                </button>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    المبلغ المدفوع
                                </label>

                                <input
                                    v-model.number="form.payment_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="block w-full rounded-xl border-slate-300 px-3 py-3 text-sm font-black dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    :class="{ 'border-rose-500': paymentError }"
                                />
                            </div>

                            <template v-if="paymentAmount > 0">
                                <div>
                                    <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                        طريقة الدفع
                                    </label>

                                    <select
                                        v-model="form.payment_method"
                                        class="block w-full rounded-xl border-slate-300 px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    >
                                        <option
                                            v-for="(label, value) in paymentMethods"
                                            :key="value"
                                            :value="value"
                                        >
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>

                                <div v-if="financialAccounts.length">
                                    <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                        الحساب المالي
                                    </label>

                                    <select
                                        v-model="form.financial_account_id"
                                        class="block w-full rounded-xl border-slate-300 px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    >
                                        <option value="">
                                            اختيار تلقائي
                                        </option>

                                        <option
                                            v-for="account in compatibleAccounts"
                                            :key="account.id"
                                            :value="account.id"
                                        >
                                            {{ account.name }}
                                        </option>
                                    </select>

                                    <div
                                        v-if="selectedFinancialAccount"
                                        class="mt-2 rounded-xl bg-slate-50 p-3 text-xs dark:bg-slate-900"
                                    >
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-slate-500">الحساب المختار</span>
                                            <strong>{{ selectedFinancialAccount.name }}</strong>
                                        </div>

                                        <div class="mt-1 flex items-center justify-between gap-3">
                                            <span class="text-slate-500">الرصيد الحالي</span>
                                            <strong dir="ltr">{{ formatCurrency(selectedFinancialAccount.current_balance) }}</strong>
                                        </div>
                                    </div>

                                    <p
                                        v-if="accountBalanceWarning"
                                        class="mt-2 rounded-xl bg-amber-50 p-3 text-xs font-bold leading-5 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300"
                                    >
                                        {{ accountBalanceWarning }}
                                    </p>
                                </div>

                                <div
                                    v-if="['bank_transfer', 'banking_app'].includes(form.payment_method)"
                                >
                                    <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                        اسم البنك أو التطبيق
                                    </label>

                                    <input
                                        v-model.trim="form.bank_or_app_name"
                                        type="text"
                                        maxlength="255"
                                        class="block w-full rounded-xl border-slate-300 px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>

                                <div
                                    v-if="['bank_transfer', 'banking_app'].includes(form.payment_method)"
                                >
                                    <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                        مرجع العملية
                                    </label>

                                    <input
                                        v-model.trim="form.transaction_reference"
                                        type="text"
                                        maxlength="255"
                                        placeholder="اختياري"
                                        class="block w-full rounded-xl border-slate-300 px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>
                            </template>

                            <p
                                v-if="paymentError"
                                class="text-xs font-bold text-rose-600"
                            >
                                {{ paymentError }}
                            </p>

                            <div class="rounded-xl bg-amber-50 p-4 dark:bg-amber-900/20">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-amber-900 dark:text-amber-300">
                                        المتبقي
                                    </span>

                                    <strong class="text-amber-700 dark:text-amber-300">
                                        {{ formatCurrency(remaining) }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </section>

                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="inline-flex w-full items-center justify-center rounded-2xl bg-emerald-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'جاري حفظ المسودة...' : 'حفظ فاتورة الشراء كمسودة' }}
                    </button>

                    <Link
                        :href="route('purchases.index')"
                        class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        إلغاء والعودة
                    </Link>
                </aside>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
