<script setup>
import {
    computed,
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
    invoices: {
        type: Array,
        default: () => [],
    },

    products: {
        type: Array,
        default: () => [],
    },

    accounts: {
        type: Array,
        default: () => [],
    },

    salesWarehouse: {
        type: Object,
        default: null,
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

const invoiceSearch = ref('');
const productSearch = ref('');
const invoicePickerOpen = ref(true);
const selectionError = ref('');

const minimumPriceApprovalOpen = ref(false);
const minimumPriceApprovalReason = ref('');
const minimumPriceApprovalError = ref('');

const form = useForm({
    original_invoice_id:
        '',

    exchange_date:
        localDate(),

    reason:
        'استبدال منتج',

    notes:
        '',

    financial_account_id:
        '',

    invoice_discount:
        0,

    return_items:
        [],

    new_items:
        [],

    minimum_price_approved:
        false,

    minimum_price_reason:
        '',
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

const normalize = (value) => (
    String(value ?? '')
        .trim()
        .toLowerCase()
);

const selectedInvoice = computed(() => (
    props.invoices.find(
        (invoice) =>
            Number(invoice.id)
            === Number(
                form.original_invoice_id
            )
    ) || null
));

const filteredInvoices = computed(() => {
    const term =
        normalize(
            invoiceSearch.value
        );

    if (!term) {
        return props.invoices;
    }

    return props.invoices.filter(
        (invoice) =>
            [
                invoice.invoice_number,
                invoice.customer?.name,
                invoice.customer_name,
                invoice.customer?.phone,
                invoice.customer_phone,
                ...invoice.items.map(
                    (item) =>
                        item.product?.name
                        || item.product_name
                ),
            ]
                .filter(Boolean)
                .some(
                    (value) =>
                        normalize(value)
                            .includes(term)
                )
    );
});

const selectedReturnItems = computed(() => (
    form.return_items.filter(
        (item) =>
            item.selected
            && Number(
                item.quantity
                || 0
            ) > 0
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

const returnItemAmount = (item) => {
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

const returnTotal = computed(() => (
    roundMoney(
        selectedReturnItems.value.reduce(
            (sum, item) =>
                sum
                + returnItemAmount(
                    item
                ),
            0
        )
    )
));

/*
 * هذه هي النقطة المنطقية المهمة:
 * قيمة المرتجع لا تصبح كلها رصيد استبدال.
 * أولاً نخفض ما تبقى على الفاتورة الأصلية.
 */
const originalDebt = computed(() => (
    Number(
        selectedInvoice.value
            ?.remaining_amount
        || 0
    )
));

const projectedDebtReduction = computed(() => (
    Math.min(
        returnTotal.value,
        originalDebt.value
    )
));

const exchangeCredit = computed(() => (
    Math.max(
        0,
        returnTotal.value
        - projectedDebtReduction.value
    )
));

const newSubtotal = computed(() => (
    form.new_items.reduce(
        (sum, item) => {
            const lineSubtotal =
                Number(
                    item.quantity
                    || 0
                )
                * Number(
                    item.unit_selling_price
                    || 0
                );

            const discount =
                Math.min(
                    Math.max(
                        0,
                        Number(
                            item.line_discount
                            || 0
                        )
                    ),
                    lineSubtotal
                );

            return sum
                + (
                    lineSubtotal
                    - discount
                );
        },
        0
    )
));

const safeInvoiceDiscount = computed(() => (
    Math.min(
        Math.max(
            0,
            Number(
                form.invoice_discount
                || 0
            )
        ),
        newSubtotal.value
    )
));

const newTotal = computed(() => (
    Math.max(
        0,
        newSubtotal.value
        - safeInvoiceDiscount.value
    )
));

const invoiceDiscountFactor = computed(() => {
    if (
        newSubtotal.value
        <= 0
    ) {
        return 1;
    }

    return Math.max(
        0,
        (
            newSubtotal.value
            - safeInvoiceDiscount.value
        )
        / newSubtotal.value
    );
});

const newItemNetUnitPrice = (item) => {
    const quantity =
        Math.max(
            1,
            Number(
                item.quantity
                || 1
            )
        );

    const lineSubtotal =
        quantity
        * Number(
            item.unit_selling_price
            || 0
        );

    const lineDiscount =
        Math.min(
            Math.max(
                0,
                Number(
                    item.line_discount
                    || 0
                )
            ),
            lineSubtotal
        );

    const lineAfterDiscount =
        Math.max(
            0,
            lineSubtotal
            - lineDiscount
        );

    return (
        lineAfterDiscount
        * invoiceDiscountFactor.value
        / quantity
    );
};

const minimumPriceViolations = computed(() => (
    form.new_items
        .map(
            (item) => {
                const minimum =
                    Math.max(
                        0,
                        Number(
                            item.minimum_selling_price
                            || 0
                        )
                    );

                const actual =
                    newItemNetUnitPrice(
                        item
                    );

                return {
                    ...item,
                    minimum_price:
                        minimum,
                    actual_price:
                        actual,
                    difference:
                        Math.max(
                            0,
                            minimum - actual
                        ),
                };
            }
        )
        .filter(
            (item) =>
                item.minimum_price
                    > 0
                && item.actual_price
                    + 0.005
                    < item.minimum_price
        )
));

const requiresMinimumPriceApproval = computed(() => (
    minimumPriceViolations.value.length
    > 0
));

const priceDifference = computed(() => (
    newTotal.value
    - exchangeCredit.value
));

const settlementType = computed(() => {
    if (
        priceDifference.value
        > 0.00001
    ) {
        return 'customer_pays';
    }

    if (
        priceDifference.value
        < -0.00001
    ) {
        return 'customer_gets_refund';
    }

    return 'no_difference';
});

const settlementLabel = computed(() => ({
    customer_pays:
        'العميل يدفع الفرق',

    customer_gets_refund:
        'رد فرق للعميل',

    no_difference:
        'لا يوجد فرق',
}[settlementType.value]));

const settlementMessage = computed(() => ({
    customer_pays:
        `سيتم تحصيل ${money(priceDifference.value)} من العميل في الحساب المحدد.`,

    customer_gets_refund:
        `سيتم رد ${money(Math.abs(priceDifference.value))} للعميل من الحساب المحدد.`,

    no_difference:
        'رصيد الاستبدال يساوي تماماً قيمة المنتجات البديلة.',
}[settlementType.value]));

const accountRequired = computed(() => (
    settlementType.value
    !== 'no_difference'
));

const filteredProducts = computed(() => {
    const term =
        normalize(
            productSearch.value
        );

    const selectedIds =
        new Set(
            form.new_items.map(
                (item) =>
                    Number(
                        item.product_id
                    )
            )
        );

    return props.products
        .filter(
            (product) =>
                ! selectedIds.has(
                    Number(
                        product.id
                    )
                )
                && Number(
                    product
                        .available_quantity
                    || 0
                ) > 0
        )
        .filter(
            (product) =>
                !term
                || [
                    product.name,
                    product.code,
                    product.barcode,
                    product.brand,
                    product.category?.name,
                ]
                    .filter(Boolean)
                    .some(
                        (value) =>
                            normalize(value)
                                .includes(term)
                    )
        )
        .slice(0, 10);
});

const exchangeDateValid = computed(() => {
    if (
        !selectedInvoice.value
        || !form.exchange_date
    ) {
        return false;
    }

    const invoiceDate =
        String(
            selectedInvoice.value
                .sale_date
            || selectedInvoice.value
                .created_at
            || ''
        ).slice(
            0,
            10
        );

    return (
        form.exchange_date
            >= invoiceDate
        && form.exchange_date
            <= localDate()
    );
});

const returnItemsValid = computed(() => (
    selectedReturnItems.value.length
        > 0
    && selectedReturnItems.value.every(
        (item) =>
            Number(item.quantity || 0) > 0
            && Number(item.quantity || 0)
                <= Number(
                    item.available_quantity
                    || 0
                )
    )
));

const newItemsValid = computed(() => (
    form.new_items.length > 0
    && form.new_items.every(
        (item) => {
            const quantity =
                Number(
                    item.quantity
                    || 0
                );

            const price =
                Number(
                    item.unit_selling_price
                    || 0
                );

            const lineSubtotal =
                quantity * price;

            const discount =
                Number(
                    item.line_discount
                    || 0
                );

            return quantity > 0
                && quantity <= Number(
                    item.available_quantity
                    || 0
                )
                && price >= 0
                && discount >= 0
                && discount
                    <= lineSubtotal;
        }
    )
));

const canSubmit = computed(() => (
    Boolean(
        selectedInvoice.value
    )
    && exchangeDateValid.value
    && returnItemsValid.value
    && newItemsValid.value
    && (
        !accountRequired.value
        || form.financial_account_id
    )
    && !form.processing
));

const selectInvoice = (invoice) => {
    form.original_invoice_id =
        invoice.id;

    form.exchange_date =
        localDate();

    form.return_items =
        invoice.items
            .filter(
                (item) =>
                    Number(
                        item
                            .available_quantity
                        || 0
                    ) > 0
            )
            .map(
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
                            item
                                .available_quantity
                            || 0
                        ),

                    quantity:
                        1,

                    unit_price:
                        Number(
                            item
                                .net_unit_price
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
                        || null,

                    notes:
                        '',
                })
            );

    form.new_items = [];
    form.invoice_discount = 0;
    form.financial_account_id = '';
    form.minimum_price_approved = false;
    form.minimum_price_reason = '';
    minimumPriceApprovalReason.value = '';
    minimumPriceApprovalError.value = '';
    productSearch.value = '';
    selectionError.value = '';
    invoicePickerOpen.value = false;
};

const resetInvoice = () => {
    form.original_invoice_id = '';
    form.return_items = [];
    form.new_items = [];
    form.invoice_discount = 0;
    form.financial_account_id = '';
    form.minimum_price_approved = false;
    form.minimum_price_reason = '';
    minimumPriceApprovalReason.value = '';
    minimumPriceApprovalError.value = '';
    invoicePickerOpen.value = true;
};

const normalizeReturnItem = (item) => {
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

const addProduct = (product) => {
    selectionError.value = '';

    if (
        form.new_items.some(
            (item) =>
                Number(
                    item.product_id
                )
                === Number(
                    product.id
                )
        )
    ) {
        selectionError.value =
            'المنتج موجود بالفعل ضمن المنتجات البديلة.';
        return;
    }

    form.new_items.push({
        product_id:
            product.id,

        name:
            product.name,

        code:
            product.code,

        category_name:
            product.category?.name
            || '',

        available_quantity:
            Number(
                product
                    .available_quantity
                || 0
            ),

        quantity:
            1,

        unit_selling_price:
            Number(
                product
                    .unit_selling_price
                || product
                    .selling_price
                || 0
            ),

        minimum_selling_price:
            Number(
                product
                    .minimum_selling_price
                || 0
            ),

        line_discount:
            0,
    });

    productSearch.value = '';
};

const normalizeNewItem = (item) => {
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

    const subtotal =
        item.quantity
        * Number(
            item.unit_selling_price
            || 0
        );

    item.line_discount =
        Math.min(
            Math.max(
                0,
                Number(
                    item.line_discount
                    || 0
                )
            ),
            subtotal
        );
};

const removeNewItem = (index) => {
    form.new_items.splice(
        index,
        1
    );
};

const performSubmit = () => {
    const payload = {
        original_invoice_id:
            form.original_invoice_id,

        exchange_date:
            form.exchange_date,

        reason:
            form.reason
            || 'استبدال منتج',

        notes:
            form.notes
            || null,

        financial_account_id:
            accountRequired.value
                ? form
                    .financial_account_id
                : null,

        invoice_discount:
            safeInvoiceDiscount.value,

        minimum_price_approved:
            Boolean(
                form
                    .minimum_price_approved
            ),

        minimum_price_reason:
            form
                .minimum_price_approved
                ? String(
                    form
                        .minimum_price_reason
                    || ''
                ).trim()
                : null,

        return_items:
            selectedReturnItems.value.map(
                (item) => ({
                    sales_invoice_item_id:
                        item
                            .sales_invoice_item_id,

                    quantity:
                        Number(
                            item.quantity
                        ),

                    product_condition:
                        item
                            .product_condition,

                    restock:
                        Boolean(
                            item.restock
                        ),

                    warehouse_id:
                        item.restock
                            ? item
                                .warehouse_id
                            : null,

                    notes:
                        item.notes
                        || null,
                })
            ),

        new_items:
            form.new_items.map(
                (item) => ({
                    product_id:
                        item.product_id,

                    quantity:
                        Number(
                            item.quantity
                        ),

                    unit_selling_price:
                        Number(
                            item
                                .unit_selling_price
                        ),

                    line_discount:
                        Number(
                            item.line_discount
                            || 0
                        ),
                })
            ),
    };

    form.transform(
        () => payload
    ).post(
        route(
            'returns.store-exchange'
        ),
        {
            preserveScroll:
                true,

            onSuccess:
                () => {
                    minimumPriceApprovalOpen.value =
                        false;
                },
        }
    );
};

const submit = () => {
    if (!canSubmit.value) {
        return;
    }

    if (
        requiresMinimumPriceApproval.value
        && !form
            .minimum_price_approved
    ) {
        minimumPriceApprovalReason.value =
            '';

        minimumPriceApprovalError.value =
            '';

        minimumPriceApprovalOpen.value =
            true;

        return;
    }

    performSubmit();
};

const closeMinimumPriceApproval = () => {
    if (form.processing) {
        return;
    }

    minimumPriceApprovalOpen.value =
        false;

    minimumPriceApprovalError.value =
        '';
};

const confirmMinimumPriceApproval = () => {
    const reason =
        String(
            minimumPriceApprovalReason.value
            || ''
        ).trim();

    if (
        reason.length < 3
    ) {
        minimumPriceApprovalError.value =
            'اكتب سبباً واضحاً لموافقة صاحب المحل.';
        return;
    }

    form.minimum_price_approved =
        true;

    form.minimum_price_reason =
        reason;

    minimumPriceApprovalOpen.value =
        false;

    performSubmit();
};

watch(
    () => [
        form.invoice_discount,
        ...form.new_items.flatMap(
            (item) => [
                item.product_id,
                item.quantity,
                item.unit_selling_price,
                item.line_discount,
            ]
        ),
    ],
    () => {
        /*
         * أي تغيير في تسعير البدائل بعد الموافقة
         * يلغي الموافقة السابقة تلقائياً.
         */
        if (
            form.minimum_price_approved
        ) {
            form.minimum_price_approved =
                false;

            form.minimum_price_reason =
                '';
        }
    },
    {
        deep: true,
    }
);

watch(
    () => priceDifference.value,
    () => {
        if (
            !accountRequired.value
        ) {
            form.financial_account_id = '';
        }
    }
);
</script>

<template>
    <Head title="استبدال جديد" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                            Smart Exchange
                        </span>

                        <span class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                            تسوية تلقائية
                        </span>
                    </div>

                    <h1 class="mt-2 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl">
                        استبدال منتج
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        أعد منتجاً من فاتورة سابقة، اختر البديل، وسيحسب النظام
                        دين الفاتورة ورصيد الاستبدال وفرق السعر والحساب المالي تلقائياً.
                    </p>
                </div>

                <Link
                    :href="route('returns.index')"
                    class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black dark:border-slate-700 dark:text-slate-200"
                >
                    العودة للمركز
                </Link>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Invoice selection -->
            <section
                v-if="invoicePickerOpen || !selectedInvoice"
                class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-black text-blue-600">
                            الخطوة 1
                        </p>

                        <h2 class="mt-1 font-black text-slate-950 dark:text-white">
                            اختر الفاتورة الأصلية
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            لا تظهر إلا الفواتير المعتمدة التي تحتوي كمية قابلة للإرجاع.
                        </p>
                    </div>

                    <input
                        v-model.trim="invoiceSearch"
                        type="search"
                        placeholder="رقم فاتورة، عميل، هاتف أو منتج..."
                        class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white sm:w-80"
                    />
                </div>

                <div class="mt-5 grid gap-3 lg:grid-cols-2 2xl:grid-cols-3">
                    <button
                        v-for="invoice in filteredInvoices"
                        :key="invoice.id"
                        type="button"
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-right transition hover:-translate-y-0.5 hover:border-blue-300 hover:bg-white hover:shadow-md dark:border-slate-700 dark:bg-slate-900/50 dark:hover:border-blue-700 dark:hover:bg-slate-900"
                        @click="selectInvoice(invoice)"
                    >
                        <div class="flex justify-between gap-3">
                            <div>
                                <strong dir="ltr" class="block text-right text-blue-700 dark:text-blue-300">
                                    {{ invoice.invoice_number }}
                                </strong>

                                <p class="mt-1 font-black text-slate-950 dark:text-white">
                                    {{ invoice.customer?.name || invoice.customer_name || 'عميل نقدي' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ date(invoice.sale_date) }}
                                </p>
                            </div>

                            <span class="rounded-xl bg-blue-100 px-2.5 py-1 text-[10px] font-black text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                {{ number(invoice.returnable_quantity) }} قطعة
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div class="rounded-xl bg-white p-3 dark:bg-slate-800">
                                <p class="text-[10px] text-slate-400">
                                    قابل للإرجاع
                                </p>
                                <strong dir="ltr" class="mt-1 block text-right text-sm">
                                    {{ money(invoice.returnable_value) }}
                                </strong>
                            </div>

                            <div class="rounded-xl bg-white p-3 dark:bg-slate-800">
                                <p class="text-[10px] text-slate-400">
                                    الدين الحالي
                                </p>
                                <strong dir="ltr" class="mt-1 block text-right text-sm text-amber-600">
                                    {{ money(invoice.remaining_amount) }}
                                </strong>
                            </div>
                        </div>
                    </button>

                    <div
                        v-if="!filteredInvoices.length"
                        class="col-span-full rounded-2xl border-2 border-dashed border-slate-200 py-14 text-center text-slate-500 dark:border-slate-700"
                    >
                        لا توجد فاتورة مطابقة قابلة للاستبدال.
                    </div>
                </div>
            </section>

            <template v-else>
                <section class="rounded-[28px] border border-blue-200 bg-gradient-to-l from-blue-50 via-white to-white p-5 shadow-sm dark:border-blue-900/50 dark:from-blue-950/20 dark:via-slate-800 dark:to-slate-800">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="grid flex-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <p class="text-xs text-slate-500">الفاتورة</p>
                                <strong dir="ltr" class="mt-1 block text-right text-lg text-blue-700 dark:text-blue-300">
                                    {{ selectedInvoice.invoice_number }}
                                </strong>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500">العميل</p>
                                <strong class="mt-1 block text-slate-950 dark:text-white">
                                    {{ selectedInvoice.customer?.name || selectedInvoice.customer_name || 'عميل نقدي' }}
                                </strong>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500">تاريخ البيع</p>
                                <strong class="mt-1 block text-slate-950 dark:text-white">
                                    {{ date(selectedInvoice.sale_date) }}
                                </strong>
                            </div>

                            <div>
                                <p class="text-xs text-slate-500">الدين على الفاتورة</p>
                                <strong dir="ltr" class="mt-1 block text-right text-amber-600">
                                    {{ money(originalDebt) }}
                                </strong>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-xl border border-violet-300 px-4 py-2.5 text-xs font-black text-blue-700 dark:border-violet-800 dark:text-blue-300"
                            @click="resetInvoice"
                        >
                            تغيير الفاتورة
                        </button>
                    </div>
                </section>

                <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
                    <div class="space-y-6">
                        <!-- Return items -->
                        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <div class="border-b border-slate-200 p-5 dark:border-slate-700">
                                <p class="text-xs font-black text-rose-600">الخطوة 2</p>
                                <h2 class="mt-1 font-black text-slate-950 dark:text-white">
                                    ماذا سيعيد العميل؟
                                </h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    قيمة الصنف محسوبة من صافي السعر الفعلي بعد خصومات الفاتورة الأصلية.
                                </p>
                            </div>

                            <div class="space-y-3 p-5">
                                <label
                                    v-for="item in form.return_items"
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
                                            class="mt-1 rounded border-slate-300 text-rose-600"
                                        />

                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap justify-between gap-3">
                                                <div>
                                                    <strong class="text-slate-950 dark:text-white">
                                                        {{ item.product_name }}
                                                    </strong>

                                                    <p dir="ltr" class="mt-1 text-right text-xs text-slate-400">
                                                        {{ item.product_code || 'بدون كود' }}
                                                        · المتاح {{ number(item.available_quantity) }}
                                                    </p>
                                                </div>

                                                <strong dir="ltr" class="text-rose-600">
                                                    {{ money(item.unit_price) }}
                                                </strong>
                                            </div>

                                            <div
                                                v-if="item.selected"
                                                class="mt-4 grid gap-3 md:grid-cols-3"
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
                                                        @change="normalizeReturnItem(item)"
                                                    />
                                                </div>

                                                <div>
                                                    <span class="mb-1 block text-xs font-bold text-slate-500">
                                                        الحالة
                                                    </span>

                                                    <select
                                                        v-model="item.product_condition"
                                                        class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                        @change="normalizeReturnItem(item)"
                                                    >
                                                        <option value="sellable">سليم وقابل للبيع</option>
                                                        <option value="damaged">تالف</option>
                                                        <option value="needs_inspection">يحتاج فحص</option>
                                                    </select>
                                                </div>

                                                <div class="flex items-end">
                                                    <label class="flex h-[42px] w-full items-center gap-2 rounded-xl bg-slate-50 px-3 dark:bg-slate-900">
                                                        <input
                                                            v-model="item.restock"
                                                            type="checkbox"
                                                            :disabled="item.product_condition !== 'sellable'"
                                                            class="rounded border-slate-300 text-emerald-600"
                                                        />
                                                        <span class="text-xs font-bold">
                                                            إعادة للمخزون
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </section>

                        <!-- New products -->
                        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <div class="border-b border-slate-200 p-5 dark:border-slate-700">
                                <p class="text-xs font-black text-emerald-600">الخطوة 3</p>
                                <h2 class="mt-1 font-black text-slate-950 dark:text-white">
                                    المنتجات البديلة
                                </h2>
                                <p class="mt-1 text-xs text-slate-500">
                                    اختر من مخزون المبيعات الحالي، ويمكن تعديل السعر والخصم قبل التأكيد.
                                </p>
                            </div>

                            <div class="p-5">
                                <div class="relative">
                                    <input
                                        v-model.trim="productSearch"
                                        type="search"
                                        placeholder="ابحث باسم المنتج أو الكود..."
                                        class="w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-sm focus:border-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />

                                    <div
                                        v-if="productSearch"
                                        class="absolute inset-x-0 top-full z-30 mt-2 max-h-80 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                                    >
                                        <button
                                            v-for="product in filteredProducts"
                                            :key="product.id"
                                            type="button"
                                            class="flex w-full items-center justify-between gap-4 rounded-xl p-3 text-right hover:bg-emerald-50 dark:hover:bg-emerald-950/20"
                                            @click="addProduct(product)"
                                        >
                                            <div>
                                                <strong class="block text-sm text-slate-950 dark:text-white">
                                                    {{ product.name }}
                                                </strong>

                                                <span dir="ltr" class="mt-1 block text-right text-xs text-slate-400">
                                                    {{ product.code || 'بدون كود' }}
                                                    · متوفر {{ number(product.available_quantity) }}
                                                </span>
                                            </div>

                                            <div class="text-left">
                                                <strong
                                                    dir="ltr"
                                                    class="block text-emerald-600"
                                                >
                                                    {{ money(product.unit_selling_price || product.selling_price) }}
                                                </strong>

                                                <span
                                                    v-if="Number(product.minimum_selling_price || 0) > 0"
                                                    dir="ltr"
                                                    class="mt-1 block text-[10px] font-bold text-amber-600"
                                                >
                                                    Min {{ money(product.minimum_selling_price) }}
                                                </span>
                                            </div>
                                        </button>

                                        <p
                                            v-if="!filteredProducts.length"
                                            class="p-6 text-center text-xs text-slate-500"
                                        >
                                            لا يوجد منتج مطابق أو المنتج مضاف بالفعل.
                                        </p>
                                    </div>
                                </div>

                                <p
                                    v-if="selectionError"
                                    class="mt-2 text-xs font-bold text-rose-600"
                                >
                                    {{ selectionError }}
                                </p>

                                <div
                                    v-if="form.new_items.length"
                                    class="mt-5 overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700"
                                >
                                    <table class="min-w-[850px] w-full text-sm">
                                        <thead class="bg-slate-950 text-white">
                                            <tr>
                                                <th class="px-3 py-3 text-right">المنتج</th>
                                                <th class="px-3 py-3 text-right">الكمية</th>
                                                <th class="px-3 py-3 text-right">سعر البيع</th>
                                                <th class="px-3 py-3 text-right">خصم السطر</th>
                                                <th class="px-3 py-3 text-right">الإجمالي</th>
                                                <th class="px-3 py-3"></th>
                                            </tr>
                                        </thead>

                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                            <tr
                                                v-for="(item, index) in form.new_items"
                                                :key="item.product_id"
                                            >
                                                <td class="px-3 py-3">
                                                    <strong class="block text-slate-950 dark:text-white">
                                                        {{ item.name }}
                                                    </strong>
                                                    <span dir="ltr" class="block text-right text-xs text-slate-400">
                                                        {{ item.code || '—' }}
                                                        · متوفر {{ number(item.available_quantity) }}
                                                    </span>

                                                    <span
                                                        v-if="Number(item.minimum_selling_price || 0) > 0"
                                                        dir="ltr"
                                                        class="mt-1 block text-right text-[10px] font-bold text-amber-600"
                                                    >
                                                        أقل سعر: {{ money(item.minimum_selling_price) }}
                                                    </span>
                                                </td>

                                                <td class="px-3 py-3">
                                                    <input
                                                        v-model.number="item.quantity"
                                                        type="number"
                                                        min="1"
                                                        :max="item.available_quantity"
                                                        class="w-24 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                        @change="normalizeNewItem(item)"
                                                    />
                                                </td>

                                                <td class="px-3 py-3">
                                                    <input
                                                        v-model.number="item.unit_selling_price"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="w-32 rounded-lg dark:bg-slate-900 dark:text-white"
                                                        :class="
                                                            Number(item.minimum_selling_price || 0) > 0
                                                            && newItemNetUnitPrice(item) + 0.005 < Number(item.minimum_selling_price)
                                                                ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500/20 dark:border-rose-800'
                                                                : 'border-slate-300 dark:border-slate-600'
                                                        "
                                                        @change="normalizeNewItem(item)"
                                                    />
                                                </td>

                                                <td class="px-3 py-3">
                                                    <input
                                                        v-model.number="item.line_discount"
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        class="w-28 rounded-lg border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                        @change="normalizeNewItem(item)"
                                                    />
                                                </td>

                                                <td dir="ltr" class="px-3 py-3 text-right font-black text-emerald-600">
                                                    {{ money(
                                                        item.quantity * item.unit_selling_price
                                                        - item.line_discount
                                                    ) }}
                                                </td>

                                                <td class="px-3 py-3">
                                                    <button
                                                        type="button"
                                                        class="rounded-lg bg-rose-50 px-2.5 py-1.5 text-xs font-black text-rose-600 dark:bg-rose-950/30"
                                                        @click="removeNewItem(index)"
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
                                    class="mt-5 rounded-2xl border-2 border-dashed border-slate-200 py-10 text-center text-sm text-slate-500 dark:border-slate-700"
                                >
                                    ابحث عن منتج وأضفه كبديل.
                                </div>

                                <div
                                    v-if="requiresMinimumPriceApproval"
                                    class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200"
                                >
                                    <strong class="font-black">
                                        تنبيه أقل سعر بيع
                                    </strong>

                                    <p class="mt-1">
                                        يوجد منتج بديل أصبح صافي سعره بعد الخصومات أقل من الحد الأدنى.
                                        العملية مسموحة، لكنها ستطلب موافقة صاحب المحل قبل التنفيذ.
                                    </p>
                                </div>

                                <div class="mt-5 grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300">
                                            خصم الفاتورة الجديدة
                                        </label>

                                        <input
                                            v-model.number="form.invoice_discount"
                                            type="number"
                                            min="0"
                                            :max="newSubtotal"
                                            step="0.01"
                                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        />
                                    </div>

                                    <div>
                                        <label class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300">
                                            تاريخ الاستبدال
                                        </label>

                                        <input
                                            v-model="form.exchange_date"
                                            type="date"
                                            :min="String(selectedInvoice.sale_date || selectedInvoice.created_at || '').slice(0, 10)"
                                            :max="localDate()"
                                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        />
                                    </div>

                                    <div class="md:col-span-2">
                                        <label class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300">
                                            سبب الاستبدال
                                        </label>

                                        <input
                                            v-model.trim="form.reason"
                                            type="text"
                                            maxlength="500"
                                            class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        />
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
                            </div>
                        </section>
                    </div>

                    <!-- Settlement -->
                    <aside class="space-y-5 xl:sticky xl:top-6">
                        <section class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <div class="bg-gradient-to-l from-slate-950 via-violet-950 to-violet-700 p-5 text-white">
                                <p class="text-xs font-bold text-violet-100">
                                    ملخص الاستبدال
                                </p>

                                <h3 class="mt-2 text-xl font-black">
                                    {{ settlementLabel }}
                                </h3>

                                <p class="mt-2 text-xs leading-5 text-violet-100">
                                    {{ settlementMessage }}
                                </p>
                            </div>

                            <div class="space-y-3 p-5 text-sm">
                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">قيمة المرتجع</span>
                                    <strong dir="ltr">{{ money(returnTotal) }}</strong>
                                </div>

                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">يُستخدم لخفض الدين</span>
                                    <strong dir="ltr" class="text-amber-600">
                                        {{ money(projectedDebtReduction) }}
                                    </strong>
                                </div>

                                <div class="flex justify-between gap-3 rounded-xl bg-blue-50 p-3 dark:bg-violet-950/25">
                                    <span class="font-black text-blue-700 dark:text-blue-300">
                                        رصيد الاستبدال الحقيقي
                                    </span>
                                    <strong dir="ltr" class="text-blue-700 dark:text-blue-300">
                                        {{ money(exchangeCredit) }}
                                    </strong>
                                </div>

                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">قيمة البدائل قبل خصم الفاتورة</span>
                                    <strong dir="ltr">{{ money(newSubtotal) }}</strong>
                                </div>

                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">خصم الفاتورة الجديدة</span>
                                    <strong dir="ltr">{{ money(safeInvoiceDiscount) }}</strong>
                                </div>

                                <div class="flex justify-between gap-3">
                                    <span class="text-slate-500">صافي البدائل</span>
                                    <strong dir="ltr">{{ money(newTotal) }}</strong>
                                </div>

                                <div class="flex justify-between gap-3 border-t border-slate-100 pt-3 dark:border-slate-700">
                                    <span class="font-black text-slate-950 dark:text-white">
                                        فرق السعر
                                    </span>

                                    <strong
                                        dir="ltr"
                                        class="text-lg"
                                        :class="priceDifference > 0
                                            ? 'text-emerald-600'
                                            : priceDifference < 0
                                                ? 'text-rose-600'
                                                : 'text-slate-500'"
                                    >
                                        {{ money(priceDifference) }}
                                    </strong>
                                </div>
                            </div>
                        </section>

                        <section
                            v-if="accountRequired"
                            class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                        >
                            <h3 class="font-black text-slate-950 dark:text-white">
                                الحساب المالي للتسوية
                            </h3>

                            <p class="mt-1 text-xs leading-5 text-slate-500">
                                {{ settlementType === 'customer_pays'
                                    ? 'الحساب الذي سيستقبل فرق السعر من العميل.'
                                    : 'الحساب الذي سيخرج منه فرق السعر للعميل.' }}
                            </p>

                            <select
                                v-model="form.financial_account_id"
                                class="mt-4 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            >
                                <option value="">اختر الحساب</option>

                                <option
                                    v-for="account in accounts"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.name }}
                                    — {{ account.type_label }}
                                    — {{ money(account.current_balance) }}
                                </option>
                            </select>
                        </section>

                        <section class="rounded-[28px] border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                            <div class="space-y-2 text-xs text-slate-500">
                                <p>
                                    ✓ العميل ثابت من الفاتورة الأصلية ولا يمكن تغييره.
                                </p>
                                <p>
                                    ✓ المخزون يُفحص مرة أخرى داخل Transaction عند الاعتماد.
                                </p>
                                <p>
                                    ✓ المرتجع والفاتورة الجديدة وفرق السعر تُنفذ كعملية مترابطة واحدة.
                                </p>
                            </div>

                            <button
                                type="button"
                                :disabled="!canSubmit"
                                class="mt-5 inline-flex w-full items-center justify-center rounded-2xl bg-blue-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                @click="submit"
                            >
                                {{ form.processing ? 'جاري تنفيذ الاستبدال...' : 'تأكيد وتنفيذ الاستبدال' }}
                            </button>

                            <div
                                v-if="Object.keys(form.errors).length"
                                class="mt-4 rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                            >
                                <p class="font-black">راجع البيانات:</p>
                                <p
                                    v-for="(error, key) in form.errors"
                                    :key="key"
                                    class="mt-1"
                                >
                                    {{ error }}
                                </p>
                            </div>
                        </section>
                    </aside>
                </div>
            </template>
        </div>
        <Teleport to="body">
            <div
                v-if="minimumPriceApprovalOpen"
                class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
                @mousedown.self="closeMinimumPriceApproval"
            >
                <section
                    class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-3xl bg-white shadow-2xl dark:bg-slate-900"
                >
                    <header
                        class="bg-gradient-to-l from-slate-950 via-amber-950 to-amber-500 p-5 text-white"
                    >
                        <span
                            class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-black text-amber-100"
                        >
                            OWNER APPROVAL
                        </span>

                        <h2 class="mt-3 text-xl font-black">
                            موافقة على استبدال أقل من الحد الأدنى
                        </h2>

                        <p class="mt-1 text-sm leading-6 text-amber-100">
                            بعض المنتجات البديلة أصبحت تحت أقل سعر بيع بعد السعر والخصومات الحالية.
                        </p>
                    </header>

                    <div class="min-h-0 flex-1 space-y-3 overflow-y-auto p-5">
                        <article
                            v-for="item in minimumPriceViolations"
                            :key="item.product_id"
                            class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20"
                        >
                            <strong class="text-sm text-slate-950 dark:text-white">
                                {{ item.name }}
                            </strong>

                            <div class="mt-3 grid grid-cols-3 gap-2 text-center">
                                <div class="rounded-xl bg-white p-2 dark:bg-slate-800">
                                    <p class="text-[9px] text-slate-400">أقل سعر</p>
                                    <strong dir="ltr" class="mt-1 block text-xs text-amber-600">
                                        {{ money(item.minimum_price) }}
                                    </strong>
                                </div>

                                <div class="rounded-xl bg-white p-2 dark:bg-slate-800">
                                    <p class="text-[9px] text-slate-400">الصافي الفعلي</p>
                                    <strong dir="ltr" class="mt-1 block text-xs text-rose-600">
                                        {{ money(item.actual_price) }}
                                    </strong>
                                </div>

                                <div class="rounded-xl bg-white p-2 dark:bg-slate-800">
                                    <p class="text-[9px] text-slate-400">أقل بـ</p>
                                    <strong dir="ltr" class="mt-1 block text-xs text-rose-600">
                                        {{ money(item.difference) }}
                                    </strong>
                                </div>
                            </div>
                        </article>

                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800"
                        >
                            <label class="text-sm font-black text-slate-950 dark:text-white">
                                سبب موافقة صاحب المحل
                            </label>

                            <textarea
                                v-model.trim="minimumPriceApprovalReason"
                                rows="3"
                                maxlength="200"
                                class="mt-2 w-full resize-none rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                placeholder="مثال: عميل دائم، تصفية منتج، عرض خاص..."
                            ></textarea>

                            <p
                                v-if="minimumPriceApprovalError"
                                class="mt-2 text-xs font-bold text-rose-600"
                            >
                                {{ minimumPriceApprovalError }}
                            </p>
                        </div>
                    </div>

                    <footer
                        class="grid gap-2 border-t border-slate-200 p-4 dark:border-slate-700 sm:grid-cols-2"
                    >
                        <button
                            type="button"
                            :disabled="form.processing"
                            class="rounded-xl border border-slate-300 px-4 py-3 text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200"
                            @click="closeMinimumPriceApproval"
                        >
                            العودة وتعديل السعر
                        </button>

                        <button
                            type="button"
                            :disabled="
                                form.processing
                                || minimumPriceApprovalReason.trim().length < 3
                            "
                            class="rounded-xl bg-amber-500 px-4 py-3 text-sm font-black text-slate-950 disabled:opacity-50"
                            @click="confirmMinimumPriceApproval"
                        >
                            {{
                                form.processing
                                    ? 'جاري التنفيذ...'
                                    : 'أوافق وأكمل الاستبدال'
                            }}
                        </button>
                    </footer>
                </section>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>
