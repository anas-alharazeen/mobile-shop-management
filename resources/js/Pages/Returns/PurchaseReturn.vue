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

const currentStocks = (invoiceItem) => {
    const stocks =
        invoiceItem.product?.stocks
        || [];

    return stocks.filter(
        (stock) =>
            Number(
                stock.quantity
                || 0
            ) > 0
    );
};

const defaultWarehouse = (invoiceItem) => {
    const stocks =
        currentStocks(invoiceItem);

    const original =
        stocks.find(
            (stock) =>
                Number(
                    stock.warehouse_id
                    || stock.warehouse?.id
                )
                === Number(
                    invoiceItem.warehouse_id
                    || invoiceItem.warehouse?.id
                )
        );

    return original?.warehouse_id
        || original?.warehouse?.id
        || stocks[0]?.warehouse_id
        || stocks[0]?.warehouse?.id
        || invoiceItem.warehouse_id
        || invoiceItem.warehouse?.id
        || '';
};

const form = useForm({
    purchase_invoice_id:
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

                purchase_invoice_item_id:
                    item.id,

                product_name:
                    item.product?.name
                    || 'منتج',

                product_code:
                    item.product?.code
                    || '',

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

                unit_cost:
                    Number(
                        item.return_unit_cost
                        || item.landed_unit_cost
                        || item.unit_purchase_price
                        || 0
                    ),

                source_landed_line_cost:
                    Number(
                        item.source_landed_line_cost
                        ?? item.landed_line_cost
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

                current_average_cost:
                    Number(
                        item.current_average_cost
                        ?? item.product?.purchase_price
                        ?? 0
                    ),

                current_total_stock:
                    Number(
                        item.current_total_stock
                        ?? 0
                    ),

                warehouse_id:
                    defaultWarehouse(item),

                stocks:
                    currentStocks(item),

                reason:
                    '',

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

    const sourceLineCost =
        roundMoney(
            item.source_landed_line_cost
            || 0
        );

    return Math.min(
        availableAmount,
        roundMoney(
            sourceLineCost
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
                + itemReturnAmount(item),
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
            + Number(item.quantity || 0),
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

const warehouseStock = (item) => {
    const stock =
        item.stocks.find(
            (stock) =>
                Number(
                    stock.warehouse_id
                    || stock.warehouse?.id
                )
                === Number(
                    item.warehouse_id
                )
        );

    return Number(
        stock?.quantity
        || 0
    );
};

const itemMaxQuantity = (item) => (
    Math.min(
        Number(
            item.available_quantity
            || 0
        ),
        warehouseStock(item)
    )
);

const projectedAverageAfterReturn = (item) => {
    const currentStock =
        Math.max(
            0,
            Number(
                item.current_total_stock
                || 0
            )
        );

    const quantity =
        Math.min(
            Math.max(
                0,
                Number(
                    item.quantity
                    || 0
                )
            ),
            currentStock
        );

    const newStock =
        currentStock
        - quantity;

    if (newStock <= 0) {
        return 0;
    }

    const currentValue =
        currentStock
        * Number(
            item.current_average_cost
            || 0
        );

    const removedValue =
        itemReturnAmount(item);

    const remainingValue =
        currentValue
        - removedValue;

    if (remainingValue < -0.01) {
        return null;
    }

    return Math.max(
        0,
        remainingValue
        / newStock
    );
};

const needsFinancialAccount = computed(() => (
    projectedRefund.value
    > 0.00001
));

const canSubmit = computed(() => (
    selectedItems.value.length > 0
    && form.reason.trim()
    && selectedItems.value.every(
        (item) =>
            item.warehouse_id
            && Number(item.quantity || 0) > 0
            && Number(item.quantity || 0)
                <= itemMaxQuantity(item)
    )
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
            const max =
                itemMaxQuantity(item);

            if (max > 0) {
                item.selected = true;
                item.quantity =
                    max;
            }
        }
    );
};

const clearSelection = () => {
    form.items.forEach(
        (item) => {
            item.selected = false;
            item.quantity =
                itemMaxQuantity(item)
                > 0
                    ? 1
                    : 0;
        }
    );
};

const normalizeItem = (item) => {
    const max =
        itemMaxQuantity(item);

    item.quantity =
        Math.min(
            Math.max(
                1,
                Number(
                    item.quantity
                    || 1
                )
            ),
            Math.max(
                1,
                max
            )
        );
};

const submit = () => {
    form.return_type =
        calculatedReturnType.value;

    const payload = {
        purchase_invoice_id:
            form.purchase_invoice_id,

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
                    purchase_invoice_item_id:
                        item.purchase_invoice_item_id,

                    quantity:
                        Number(
                            item.quantity
                        ),

                    warehouse_id:
                        item.warehouse_id,

                    reason:
                        item.reason || null,

                    notes:
                        item.notes || null,
                })
            ),
    };

    form.transform(
        () => payload
    ).post(
        route(
            'returns.store-purchase'
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
    <Head title="مرتجع مشتريات جديد" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-black text-blue-600 dark:text-blue-400">
                        خطوة 2 من 2
                    </p>

                    <h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                        مرتجع مشتريات جديد
                    </h1>

                    <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
                        حدد المنتجات التي ستعود إلى المورد والمخزن الذي ستُخصم منه فعلياً.
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
                        :href="route('purchases.show', invoice.id)"
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
                <section class="rounded-[28px] border border-slate-200 bg-gradient-to-l from-blue-50 via-white to-white p-5 shadow-sm dark:border-slate-700 dark:from-blue-950/20 dark:via-slate-800 dark:to-slate-800">
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        <div>
                            <p class="text-xs text-slate-500">فاتورة الشراء</p>
                            <strong dir="ltr" class="mt-1 block text-right text-lg text-blue-700 dark:text-blue-300">
                                {{ invoice.invoice_number }}
                            </strong>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">المورد</p>
                            <strong class="mt-1 block text-slate-950 dark:text-white">
                                {{ invoice.supplier?.company_name || invoice.supplier?.name || '—' }}
                            </strong>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">تاريخ الشراء</p>
                            <strong class="mt-1 block text-slate-950 dark:text-white">
                                {{ date(invoice.purchase_date) }}
                            </strong>
                        </div>

                        <div>
                            <p class="text-xs text-slate-500">المتبقي للمورد</p>
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
                                المنتجات القابلة للإرجاع للمورد
                            </h2>
                            <p class="mt-1 text-xs text-slate-500">
                                الكمية الفعلية محدودة أيضاً بما هو متوفر حالياً في المخزن المختار.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                                @click="selectAll"
                            >
                                تحديد الكل
                            </button>

                            <button
                                type="button"
                                class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-black dark:border-slate-600 dark:text-slate-300"
                                @click="clearSelection"
                            >
                                مسح
                            </button>
                        </div>
                    </div>

                    <div class="space-y-3 p-5">
                        <label
                            v-for="(item, index) in form.items"
                            :key="item.purchase_invoice_item_id"
                            class="block rounded-2xl border p-4 transition"
                            :class="item.selected
                                ? 'border-blue-300 bg-blue-50/50 dark:border-blue-800 dark:bg-blue-950/20'
                                : 'border-slate-200 dark:border-slate-700'"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    v-model="item.selected"
                                    type="checkbox"
                                    :disabled="item.available_quantity <= 0 || item.stocks.length === 0"
                                    class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                />

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-start justify-between gap-3">
                                        <div>
                                            <strong class="text-slate-950 dark:text-white">
                                                {{ item.product_name }}
                                            </strong>

                                            <p dir="ltr" class="mt-1 text-right text-xs text-slate-400">
                                                {{ item.product_code || 'بدون كود' }}
                                                · المتبقي القابل للإرجاع
                                                {{ number(item.available_quantity) }}
                                            </p>

                                            <div
                                                v-if="item.selected"
                                                class="mt-3 grid gap-2 sm:grid-cols-3"
                                            >
                                                <div class="rounded-xl bg-white px-3 py-2 dark:bg-slate-900">
                                                    <p class="text-[9px] text-slate-400">
                                                        متوسط التكلفة الحالي
                                                    </p>
                                                    <strong
                                                        dir="ltr"
                                                        class="mt-1 block text-right text-xs text-slate-700 dark:text-slate-200"
                                                    >
                                                        {{ money(item.current_average_cost) }}
                                                    </strong>
                                                </div>

                                                <div class="rounded-xl bg-white px-3 py-2 dark:bg-slate-900">
                                                    <p class="text-[9px] text-slate-400">
                                                        قيمة المرتجع
                                                    </p>
                                                    <strong
                                                        dir="ltr"
                                                        class="mt-1 block text-right text-xs text-rose-600"
                                                    >
                                                        {{ money(itemReturnAmount(item)) }}
                                                    </strong>
                                                </div>

                                                <div
                                                    class="rounded-xl px-3 py-2"
                                                    :class="
                                                        projectedAverageAfterReturn(item) === null
                                                            ? 'bg-rose-50 dark:bg-rose-950/20'
                                                            : 'bg-blue-50 dark:bg-blue-950/20'
                                                    "
                                                >
                                                    <p class="text-[9px] text-slate-400">
                                                        المتوسط المتوقع بعد الاعتماد
                                                    </p>
                                                    <strong
                                                        v-if="projectedAverageAfterReturn(item) !== null"
                                                        dir="ltr"
                                                        class="mt-1 block text-right text-xs text-blue-700 dark:text-blue-300"
                                                    >
                                                        {{ money(projectedAverageAfterReturn(item)) }}
                                                    </strong>

                                                    <strong
                                                        v-else
                                                        class="mt-1 block text-xs text-rose-600"
                                                    >
                                                        يحتاج مراجعة
                                                    </strong>
                                                </div>
                                            </div>
                                        </div>

                                        <strong dir="ltr" class="text-blue-600">
                                            {{ money(item.unit_cost) }}
                                        </strong>
                                    </div>

                                    <div
                                        v-if="item.selected"
                                        class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-4"
                                    >
                                        <div>
                                            <span class="mb-1 block text-xs font-bold text-slate-500">
                                                المخزن
                                            </span>

                                            <select
                                                v-model="item.warehouse_id"
                                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                @change="normalizeItem(item)"
                                            >
                                                <option
                                                    v-for="stock in item.stocks"
                                                    :key="stock.id"
                                                    :value="stock.warehouse_id || stock.warehouse?.id"
                                                >
                                                    {{ stock.warehouse?.name || 'مخزن' }}
                                                    — متوفر {{ number(stock.quantity) }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <span class="mb-1 block text-xs font-bold text-slate-500">
                                                الكمية
                                            </span>

                                            <input
                                                v-model.number="item.quantity"
                                                type="number"
                                                min="1"
                                                :max="itemMaxQuantity(item)"
                                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                @change="normalizeItem(item)"
                                            />

                                            <span class="mt-1 block text-[10px] text-slate-400">
                                                الحد الحالي: {{ number(itemMaxQuantity(item)) }}
                                                · القيمة المتاحة: {{ money(item.available_return_amount) }}
                                            </span>
                                        </div>

                                        <div>
                                            <span class="mb-1 block text-xs font-bold text-slate-500">
                                                سبب خاص بالصنف
                                            </span>

                                            <input
                                                v-model.trim="item.reason"
                                                type="text"
                                                maxlength="500"
                                                placeholder="اختياري"
                                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                            />
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
                                        v-if="item.selected && itemMaxQuantity(item) <= 0"
                                        class="mt-2 text-xs font-bold text-rose-600"
                                    >
                                        لا توجد كمية متاحة لهذا المنتج في المخزن المختار.
                                    </p>

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
                                :min="String(invoice.purchase_date || '').slice(0, 10)"
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
                                سبب الإرجاع للمورد
                                <span class="text-rose-500">*</span>
                            </label>

                            <textarea
                                v-model.trim="form.reason"
                                rows="3"
                                maxlength="500"
                                placeholder="مثال: بضاعة تالفة، اختلاف مواصفات، خطأ في التوريد..."
                                class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            ></textarea>
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
                    <div class="bg-gradient-to-l from-slate-950 via-blue-950 to-blue-700 p-5 text-white">
                        <p class="text-xs font-bold text-blue-100">
                            معاينة التسوية
                        </p>

                        <p dir="ltr" class="mt-2 text-right text-2xl font-black">
                            {{ money(totalReturnAmount) }}
                        </p>

                        <p class="mt-1 text-xs text-blue-100">
                            قيمة المنتجات المعادة للمورد
                        </p>
                    </div>

                    <div class="space-y-3 p-5 text-sm">
                        <div class="flex justify-between gap-3">
                            <span class="text-slate-500">
                                خفض مستحق المورد
                            </span>
                            <strong dir="ltr" class="text-amber-600">
                                {{ money(projectedDebtReduction) }}
                            </strong>
                        </div>

                        <div class="flex justify-between gap-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                            <span class="font-black text-slate-950 dark:text-white">
                                مبلغ سيعود للمعرض
                            </span>
                            <strong dir="ltr" class="text-lg text-emerald-600">
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
                        استلام المبلغ من المورد
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
                            <option value="">اختر الحساب المستلم</option>
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
                                سيُخصم المخزون وتُسوّى مستحقات المورد فوراً.
                                ألغِ التفعيل إذا أردت مراجعته كمسودة أولاً.
                            </span>
                        </span>
                    </label>

                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
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
                        اختر الحساب المالي الذي سيستلم المبلغ من المورد.
                    </p>
                </section>
            </aside>
        </form>
    </AuthenticatedLayout>
</template>
