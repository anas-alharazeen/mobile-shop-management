<script setup>
import {
    computed,
    watch,
} from 'vue';

import {
    Head,
    Link,
    useForm,
} from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },

    accounts: {
        type: Array,
        default: () => [],
    },

    warehouses: {
        type: Array,
        default: () => [],
    },
});

const localDate = () => {
    const now = new Date();
    const offset =
        now.getTimezoneOffset();

    return new Date(
        now.getTime()
        - offset * 60 * 1000
    )
        .toISOString()
        .slice(0, 10);
};

const form = useForm({
    sales_invoice_id:
        props.invoice.id,

    return_date:
        localDate(),

    return_type:
        'partial',

    approve_now:
        true,

    reason:
        '',

    notes:
        '',

    refund_method:
        'cash',

    financial_account_id:
        '',

    items:
        props.invoice.items.map(
            (item) => ({
                selected:
                    false,

                sales_invoice_item_id:
                    item.id,

                product_name:
                    item.product?.name
                    || item.product_name,

                product_code:
                    item.product?.code
                    || item.product_code,

                available_quantity:
                    Number(
                        item.available_quantity
                        || 0
                    ),

                quantity:
                    Number(
                        item.available_quantity
                        || 0
                    ) > 0
                        ? 1
                        : 0,

                unit_price:
                    Number(
                        item.net_unit_price
                        || 0
                    ),

                source_line_total:
                    Number(
                        item.source_line_total
                        ?? item.line_total
                        ?? 0
                    ),

                source_quantity:
                    Number(
                        item.source_quantity
                        ?? item.quantity
                        ?? 0
                    ),

                returned_amount:
                    Number(
                        item.returned_amount
                        || 0
                    ),

                available_return_amount:
                    Number(
                        item.available_return_amount
                        || 0
                    ),

                product_condition:
                    'sellable',

                restock:
                    true,

                warehouse_id:
                    item.warehouse_id
                    || item.warehouse?.id
                    || '',

                notes:
                    '',
            })
        ),
});

const money = (value) => (
    `${Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`
);

const number = (value) => (
    Number(value || 0)
        .toLocaleString('en-US')
);

const date = (value) => {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat(
        'ar-PS-u-nu-latn',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }
    ).format(
        new Date(value)
    );
};

const selectedItems = computed(() => (
    form.items.filter(
        (item) =>
            item.selected
            && Number(item.quantity || 0) > 0
    )
));

const roundMoney = (value) => (
    Math.round(
        (
            Number(value || 0)
            + Number.EPSILON
        ) * 100
    ) / 100
);

const itemReturnAmount = (item) => {
    const quantity =
        Math.min(
            Math.max(
                0,
                Number(
                    item.quantity
                    || 0
                )
            ),
            Number(
                item.available_quantity
                || 0
            )
        );

    if (quantity <= 0) {
        return 0;
    }

    const availableQuantity =
        Number(
            item.available_quantity
            || 0
        );

    const availableAmount =
        roundMoney(
            item.available_return_amount
            || 0
        );

    /*
     * إذا تم اختيار كل الكمية المتبقية نستخدم القيمة
     * المتبقية كاملة حتى لا نخسر أو نزيد قرشاً بسبب التقريب.
     */
    if (
        quantity
        >= availableQuantity
    ) {
        return availableAmount;
    }

    const sourceQuantity =
        Math.max(
            1,
            Number(
                item.source_quantity
                || 1
            )
        );

    const sourceLineTotal =
        roundMoney(
            item.source_line_total
            || 0
        );

    return Math.min(
        availableAmount,
        roundMoney(
            sourceLineTotal
            * (
                quantity
                / sourceQuantity
            )
        )
    );
};

const totalReturnAmount = computed(() => (
    roundMoney(
        selectedItems.value.reduce(
            (sum, item) =>
                sum
                + itemReturnAmount(
                    item
                ),
            0
        )
    )
));

const totalAvailableQuantity = computed(() => (
    form.items.reduce(
        (sum, item) =>
            sum
            + Number(
                item.available_quantity
                || 0
            ),
        0
    )
));

const selectedQuantity = computed(() => (
    selectedItems.value.reduce(
        (sum, item) =>
            sum
            + Number(
                item.quantity || 0
            ),
        0
    )
));

const calculatedReturnType = computed(() => (
    selectedQuantity.value > 0
    && selectedQuantity.value
        === totalAvailableQuantity.value
        ? 'full'
        : 'partial'
));

const invoiceDebt = computed(() => (
    Number(
        props.invoice.remaining_amount
        || 0
    )
));

const projectedDebtReduction = computed(() => (
    Math.min(
        totalReturnAmount.value,
        invoiceDebt.value
    )
));

const projectedRefund = computed(() => (
    Math.max(
        0,
        totalReturnAmount.value
        - projectedDebtReduction.value
    )
));

const expectedAccountType = computed(() => ({
    cash:
        'cash',

    bank_transfer:
        'bank',

    banking_app:
        'banking_app',
}[form.refund_method] || null));

const compatibleAccounts = computed(() => (
    props.accounts.filter(
        (account) =>
            account.is_active !== false
            && (
                ! expectedAccountType.value
                || account.type
                    === expectedAccountType.value
            )
    )
));

const needsFinancialAccount = computed(() => (
    projectedRefund.value
    > 0.00001
));

const canSubmit = computed(() => (
    selectedItems.value.length > 0
    && form.reason.trim()
    && !form.processing
    && (
        !needsFinancialAccount.value
        || (
            form.refund_method
            !== 'debt'
            && form.financial_account_id
        )
    )
));

const selectAll = () => {
    form.items.forEach(
        (item) => {
            if (
                Number(
                    item.available_quantity
                ) > 0
            ) {
                item.selected = true;
                item.quantity =
                    Number(
                        item.available_quantity
                    );
            }
        }
    );
};

const clearSelection = () => {
    form.items.forEach(
        (item) => {
            item.selected = false;
            item.quantity =
                Number(
                    item.available_quantity
                ) > 0
                    ? 1
                    : 0;
        }
    );
};

const normalizeItem = (item) => {
    item.quantity =
        Math.min(
            Math.max(
                1,
                Number(
                    item.quantity
                    || 1
                )
            ),
            Number(
                item.available_quantity
                || 1
            )
        );

    if (
        item.product_condition
        !== 'sellable'
    ) {
        item.restock = false;
    }
};

const submit = () => {
    form.return_type =
        calculatedReturnType.value;

    const payload = {
        sales_invoice_id:
            form.sales_invoice_id,

        return_date:
            form.return_date,

        return_type:
            calculatedReturnType.value,

        approve_now:
            form.approve_now,

        reason:
            form.reason,

        notes:
            form.notes,

        refund_method:
            projectedRefund.value
            > 0.00001
                ? form.refund_method
                : 'debt',

        financial_account_id:
            projectedRefund.value
            > 0.00001
                ? form.financial_account_id
                : null,

        items:
            selectedItems.value.map(
                (item) => ({
                    sales_invoice_item_id:
                        item.sales_invoice_item_id,

                    quantity:
                        Number(
                            item.quantity
                        ),

                    product_condition:
                        item.product_condition,

                    restock:
                        Boolean(
                            item.restock
                        ),

                    warehouse_id:
                        item.restock
                            ? item.warehouse_id
                            : null,

                    notes:
                        item.notes || null,
                })
            ),
    };

    form.transform(
        () => payload
    ).post(
        route(
            'returns.store-sales'
        ),
        {
            preserveScroll: true,
        }
    );
};

watch(
    () => form.refund_method,
    () => {
        const first =
            compatibleAccounts.value[0];

        if (
            ! compatibleAccounts.value
                .some(
                    (account) =>
                        Number(account.id)
                        === Number(
                            form
                                .financial_account_id
                        )
                )
        ) {
            form.financial_account_id =
                first?.id || '';
        }
    },
    {
        immediate: true,
    }
);
</script>

<template>
    <Head title="مرتجع مبيعات جديد" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black text-rose-600 dark:text-rose-400">
                        خطوة 2 من 2
                    </p>

                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                        مرتجع مبيعات جديد
                    </h1>

                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        اختر المنتجات والكميات، ثم راجع التسوية قبل الحفظ أو الاعتماد.
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link
                        :href="route('returns.create')"
                        class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black dark:border-slate-700 dark:text-slate-200"
                    >
                        تغيير الفاتورة
                    </Link>

                    <Link
                        :href="route('sales.show', invoice.id)"
                        class="rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-black text-white dark:bg-white dark:text-slate-950"
                    >
                        فتح الفاتورة
                    </Link>
                </div>
            </div>
        </template>

        <form
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_380px]"
            @submit.prevent="submit"
        >
            <div class="space-y-6">
                <section class="rounded-[28px] border border-slate-200 bg-gradient-to-l from-rose-50 via-white to-white p-5 shadow-sm dark:border-slate-700 dark:from-rose-950/20 dark:via-slate-800 dark:to-slate-800">
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <p class="text-xs text-slate-500">الفاتورة</p>
                            <strong dir="ltr" class="mt-1 block text-right text-lg text-rose-700 dark:text-rose-300">
                                {{ invoice.invoice_number }}
                            </strong>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">العميل</p>
                            <strong class="mt-1 block text-slate-950 dark:text-white">
                                {{ invoice.customer?.name || invoice.customer_name || 'عميل نقدي' }}
                            </strong>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">تاريخ البيع</p>
                            <strong class="mt-1 block text-slate-950 dark:text-white">
                                {{ date(invoice.sale_date) }}
                            </strong>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">المتبقي على الفاتورة</p>
                            <strong dir="ltr" class="mt-1 block text-right text-amber-600">
                                {{ money(invoice.remaining_amount) }}
                            </strong>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="flex flex-col gap-3 border-b border-slate-200 p-5 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="font-black text-slate-950 dark:text-white">
                                المنتجات القابلة للإرجاع
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">
                                تظهر الكمية المتبقية بعد أي مرتجعات سابقة.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                                @click="selectAll"
                            >
                                إرجاع الكل
                            </button>

                            <button
                                type="button"
                                class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-black dark:border-slate-600 dark:text-slate-300"
                                @click="clearSelection"
                            >
                                مسح التحديد
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3 p-5">
                        <label
                            v-for="(item, index) in form.items"
                            :key="item.sales_invoice_item_id"
                            class="block rounded-2xl border p-4 transition"
                            :class="item.selected
                                ? 'border-rose-300 bg-rose-50/50 dark:border-rose-800 dark:bg-rose-950/20'
                                : 'border-slate-200 dark:border-slate-700'"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    v-model="item.selected"
                                    type="checkbox"
                                    :disabled="item.available_quantity <= 0"
                                    class="mt-1 rounded border-slate-300 text-rose-600 focus:ring-rose-500"
                                />

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <strong class="text-slate-950 dark:text-white">
                                                {{ item.product_name }}
                                            </strong>

                                            <p dir="ltr" class="mt-1 text-right text-xs text-slate-400">
                                                {{ item.product_code || 'بدون كود' }}
                                                · المتاح للإرجاع
                                                {{ number(item.available_quantity) }}
                                            </p>
                                        </div>

                                        <strong dir="ltr" class="text-rose-600">
                                            {{ money(item.unit_price) }}
                                        </strong>
                                    </div>

                                    <div
                                        v-if="item.selected"
                                        class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5"
                                    >
                                        <div>
                                            <span class="mb-1 block text-xs font-bold text-slate-500">
                                                الكمية
                                            </span>

                                            <input
                                                v-model.number="item.quantity"
                                                type="number"
                                                min="1"
                                                :max="item.available_quantity"
                                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                @change="normalizeItem(item)"
                                            />

                                            <p class="mt-1 text-[10px] text-slate-400">
                                                المتاح:
                                                {{ number(item.available_quantity) }}
                                                قطعة ·
                                                {{ money(item.available_return_amount) }}
                                            </p>
                                        </div>

                                        <div>
                                            <span class="mb-1 block text-xs font-bold text-slate-500">
                                                حالة المنتج
                                            </span>

                                            <select
                                                v-model="item.product_condition"
                                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                @change="normalizeItem(item)"
                                            >
                                                <option value="sellable">سليم وقابل للبيع</option>
                                                <option value="damaged">تالف</option>
                                                <option value="needs_inspection">يحتاج فحص</option>
                                            </select>
                                        </div>

                                        <div>
                                            <span class="mb-1 block text-xs font-bold text-slate-500">
                                                إعادة للمخزون
                                            </span>

                                            <label class="flex h-[42px] items-center gap-2 rounded-xl bg-slate-50 px-3 dark:bg-slate-900">
                                                <input
                                                    v-model="item.restock"
                                                    type="checkbox"
                                                    :disabled="item.product_condition !== 'sellable'"
                                                    class="rounded border-slate-300 text-emerald-600"
                                                />
                                                <span class="text-xs font-bold">
                                                    نعم
                                                </span>
                                            </label>
                                        </div>

                                        <div>
                                            <span class="mb-1 block text-xs font-bold text-slate-500">
                                                مخزن الاستلام
                                            </span>

                                            <select
                                                v-model="item.warehouse_id"
                                                :disabled="!item.restock"
                                                class="w-full rounded-xl border-slate-300 disabled:opacity-50 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                            >
                                                <option
                                                    v-for="warehouse in warehouses"
                                                    :key="warehouse.id"
                                                    :value="warehouse.id"
                                                >
                                                    {{ warehouse.name }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <span class="mb-1 block text-xs font-bold text-slate-500">
                                                قيمة السطر
                                            </span>

                                            <div dir="ltr" class="flex h-[42px] items-center justify-end rounded-xl bg-slate-950 px-3 text-sm font-black text-white dark:bg-white dark:text-slate-950">
                                                {{ money(itemReturnAmount(item)) }}
                                            </div>
                                        </div>
                                    </div>

                                    <p
                                        v-if="form.errors[`items.${index}.quantity`]"
                                        class="mt-2 text-xs font-bold text-rose-600"
                                    >
                                        {{ form.errors[`items.${index}.quantity`] }}
                                    </p>
                                </div>
                            </div>
                        </label>
                    </div>
                </section>

                <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <h2 class="font-black text-slate-950 dark:text-white">
                        بيانات المرتجع
                    </h2>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300">
                                تاريخ المرتجع
                            </label>

                            <input
                                v-model="form.return_date"
                                type="date"
                                :min="String(invoice.sale_date || '').slice(0, 10)"
                                :max="localDate()"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300">
                                نوع المرتجع
                            </label>

                            <div class="flex h-[42px] items-center rounded-xl bg-slate-100 px-3 text-sm font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                {{ calculatedReturnType === 'full' ? 'كامل' : 'جزئي' }}
                                <span class="mr-2 text-xs font-normal text-slate-400">
                                    يُحدد تلقائياً
                                </span>
                            </div>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300">
                                سبب الإرجاع
                                <span class="text-rose-500">*</span>
                            </label>

                            <textarea
                                v-model.trim="form.reason"
                                rows="3"
                                maxlength="500"
                                placeholder="مثال: المنتج غير مناسب، عيب، خطأ في الطلب..."
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            ></textarea>

                            <p
                                v-if="form.errors.reason"
                                class="mt-1 text-xs font-bold text-rose-600"
                            >
                                {{ form.errors.reason }}
                            </p>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300">
                                ملاحظات
                            </label>

                            <textarea
                                v-model.trim="form.notes"
                                rows="2"
                                maxlength="500"
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            ></textarea>
                        </div>
                    </div>
                </section>
            </div>

            <aside class="space-y-5 xl:sticky xl:top-6">
                <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <div class="bg-gradient-to-l from-slate-950 via-rose-950 to-rose-700 p-5 text-white">
                        <p class="text-xs font-bold text-rose-100">
                            معاينة التسوية
                        </p>

                        <p dir="ltr" class="mt-2 text-right text-2xl font-black">
                            {{ money(totalReturnAmount) }}
                        </p>

                        <p class="mt-1 text-xs text-rose-100">
                            قيمة المنتجات المحددة
                        </p>
                    </div>

                    <div class="space-y-3 p-5 text-sm">
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">
                                عدد البنود
                            </span>
                            <strong>{{ number(selectedItems.length) }}</strong>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">
                                الكمية
                            </span>
                            <strong>{{ number(selectedQuantity) }}</strong>
                        </div>

                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">
                                تخفيض دين الفاتورة
                            </span>
                            <strong dir="ltr" class="text-amber-600">
                                {{ money(projectedDebtReduction) }}
                            </strong>
                        </div>

                        <div class="flex justify-between gap-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                            <span class="font-black text-slate-950 dark:text-white">
                                مبلغ يُرد للعميل
                            </span>
                            <strong dir="ltr" class="text-lg text-rose-600">
                                {{ money(projectedRefund) }}
                            </strong>
                        </div>
                    </div>
                </section>

                <section
                    v-if="projectedRefund > 0"
                    class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <h3 class="font-black text-slate-950 dark:text-white">
                        طريقة رد المبلغ
                    </h3>

                    <div class="mt-4 space-y-4">
                        <select
                            v-model="form.refund_method"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                            <option value="cash">كاش</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="banking_app">تطبيق بنكي</option>
                        </select>

                        <select
                            v-model="form.financial_account_id"
                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                        >
                            <option value="">اختر الحساب المالي</option>
                            <option
                                v-for="account in compatibleAccounts"
                                :key="account.id"
                                :value="account.id"
                            >
                                {{ account.name }}
                                — {{ money(account.current_balance) }}
                            </option>
                        </select>
                    </div>
                </section>

                <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input
                            v-model="form.approve_now"
                            type="checkbox"
                            class="mt-1 rounded border-slate-300 text-emerald-600"
                        />

                        <span>
                            <strong class="block text-sm text-slate-950 dark:text-white">
                                اعتماد المرتجع مباشرة
                            </strong>

                            <span class="mt-1 block text-xs leading-5 text-slate-500">
                                عند التفعيل سيتم تحديث المخزون والديون والحساب المالي فوراً.
                                عند إلغائه سيُحفظ كمسودة فقط، لكن يجب تحديد حساب الاسترداد من الآن إذا كان هناك مبلغ سيُرد للعميل حتى تبقى المسودة قابلة للاعتماد لاحقاً.
                            </span>
                        </span>
                    </label>

                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-rose-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-rose-600/20 transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing
                            ? 'جاري الحفظ...'
                            : form.approve_now
                                ? 'إنشاء واعتماد المرتجع'
                                : 'حفظ كمسودة' }}
                    </button>

                    <p
                        v-if="needsFinancialAccount && !form.financial_account_id"
                        class="mt-3 text-center text-xs font-bold text-rose-600"
                    >
                        اختر الحساب المالي الذي سيخرج منه مبلغ الاسترداد.
                    </p>

                    <p
                        v-if="Object.keys(form.errors).length"
                        class="mt-3 rounded-xl bg-rose-50 px-3 py-2 text-center text-xs font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                    >
                        {{ Object.values(form.errors)[0] }}
                    </p>
                </section>
            </aside>
        </form>
    </AuthenticatedLayout>
</template>
