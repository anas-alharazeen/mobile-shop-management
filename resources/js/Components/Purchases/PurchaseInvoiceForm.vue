<script setup>
import {
    computed,
    nextTick,
    ref,
    watch,
} from 'vue';

import {
    Link,
    useForm,
} from '@inertiajs/vue3';

const props = defineProps({
    mode: {
        type: String,
        default: 'create',
    },

    invoice: {
        type: Object,
        default: null,
    },

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

    categories: {
        type: Array,
        default: () => [],
    },

    paymentMethods: {
        type: Object,
        default: () => ({}),
    },

    financialAccounts: {
        type: Array,
        default: () => [],
    },
});

const isEdit =
    computed(
        () =>
            props.mode === 'edit'
            && Boolean(props.invoice)
    );

const localDate = (
    date = new Date()
) => {
    const offset =
        date.getTimezoneOffset();

    return new Date(
        date.getTime()
        - offset * 60000
    )
        .toISOString()
        .slice(0, 10);
};

const today =
    localDate();

const makeExistingItem =
    (
        product,
        sourceItem = null
    ) => ({
        local_key:
            `existing-${product.id}-${Date.now()}-${Math.random()}`,

        product_mode:
            'existing',

        product_id:
            product.id,

        product_name:
            product.name,

        product_code:
            product.code,

        category_name:
            product.category?.name
            || '',

        warehouse_id:
            sourceItem?.warehouse_id
            || preferredWarehouseId(),

        quantity:
            Number(
                sourceItem?.quantity
                ?? 1
            ),

        unit_purchase_price:
            Number(
                sourceItem?.unit_purchase_price
                ?? product.purchase_price
                ?? 0
            ),

        line_discount:
            Number(
                sourceItem?.line_discount
                ?? 0
            ),

        new_product:
            null,
    });

const preferredWarehouseId =
    () => {
        const sales =
            props.warehouses.find(
                warehouse =>
                    String(
                        warehouse.type?.value
                        ?? warehouse.type
                        ?? ''
                    ) === 'sales'
            );

        return sales?.id
            ?? props.warehouses[0]?.id
            ?? '';
    };

const initialItems =
    () => {
        if (
            !isEdit.value
            || !Array.isArray(
                props.invoice?.items
            )
        ) {
            return [];
        }

        return props.invoice.items
            .filter(
                item =>
                    item.product
            )
            .map(
                item =>
                    makeExistingItem(
                        item.product,
                        item
                    )
            );
    };

const form = useForm({
    supplier_id:
        props.invoice?.supplier_id
        || '',

    supplier_invoice_number:
        props.invoice
            ?.supplier_invoice_number
        || '',

    purchase_date:
        props.invoice?.purchase_date
        || today,

    due_date:
        props.invoice?.due_date
        || '',

    notes:
        props.invoice?.notes
        || '',

    items:
        initialItems(),

    discount_amount:
        Number(
            props.invoice
                ?.discount_amount
            || 0
        ),

    shipping_cost:
        Number(
            props.invoice
                ?.shipping_cost
            || 0
        ),

    additional_expenses:
        Number(
            props.invoice
                ?.additional_expenses
            || 0
        ),

    payment_amount:
        0,

    payment_method:
        'cash',

    financial_account_id:
        '',

    bank_or_app_name:
        '',

    transaction_reference:
        '',
});

const productSearch =
    ref('');

const productSearchOpen =
    ref(false);

const newProductModal =
    ref(false);

const pageError =
    ref('');

const newProduct = ref(
    freshNewProduct()
);

function freshNewProduct() {
    return {
        category_id: '',
        name: '',
        code: '',
        barcode: '',
        brand: '',
        model: '',
        selling_price: '',
        minimum_selling_price: '',
        low_stock_threshold: 5,
        location: '',
        description: '',
        notes: '',

        warehouse_id:
            preferredWarehouseId(),

        quantity: 1,
        unit_purchase_price: '',
        line_discount: 0,
    };
}

const brandShortcuts = [
    'Samsung',
    'Apple',
    'Xiaomi',
    'Oppo',
    'Realme',
    'Huawei',
    'Honor',
    'Infinix',
];

const normalize =
    value =>
        String(value ?? '')
            .trim()
            .toLowerCase();

const enumValue =
    value =>
        value
        && typeof value
            === 'object'
            ? (
                value.value
                ?? value.name
                ?? ''
            )
            : String(
                value
                ?? ''
            );

const selectedSupplier =
    computed(
        () =>
            props.suppliers.find(
                supplier =>
                    Number(
                        supplier.id
                    )
                    === Number(
                        form.supplier_id
                    )
            )
            || null
    );

const filteredProducts =
    computed(() => {
        const search =
            normalize(
                productSearch.value
            );

        const alreadyAdded =
            new Set(
                form.items
                    .filter(
                        item =>
                            item.product_mode
                            === 'existing'
                    )
                    .map(
                        item =>
                            Number(
                                item.product_id
                            )
                    )
            );

        const source =
            props.products.filter(
                product =>
                    !alreadyAdded.has(
                        Number(product.id)
                    )
            );

        if (!search) {
            return source
                .slice(0, 10);
        }

        return source
            .filter(
                product =>
                    [
                        product.name,
                        product.code,
                        product.barcode,
                        product.brand,
                        product.model,
                        product.category?.name,
                    ]
                        .filter(Boolean)
                        .some(
                            value =>
                                normalize(value)
                                    .includes(search)
                        )
            )
            .slice(0, 12);
    });

const subtotal =
    computed(
        () =>
            form.items.reduce(
                (
                    sum,
                    item
                ) =>
                    sum
                    + lineTotal(item),
                0
            )
    );

const discountAmount =
    computed(
        () =>
            Math.max(
                0,
                Number(
                    form.discount_amount
                    || 0
                )
            )
    );

const shippingCost =
    computed(
        () =>
            Math.max(
                0,
                Number(
                    form.shipping_cost
                    || 0
                )
            )
    );

const additionalExpenses =
    computed(
        () =>
            Math.max(
                0,
                Number(
                    form.additional_expenses
                    || 0
                )
            )
    );

const total =
    computed(
        () =>
            Math.max(
                0,
                subtotal.value
                - discountAmount.value
                + shippingCost.value
                + additionalExpenses.value
            )
    );

const paymentAmount =
    computed(
        () =>
            Math.max(
                0,
                Number(
                    form.payment_amount
                    || 0
                )
            )
    );

const remaining =
    computed(
        () =>
            Math.max(
                0,
                total.value
                - paymentAmount.value
            )
    );

const newProductsCount =
    computed(
        () =>
            form.items.filter(
                item =>
                    item.product_mode
                    === 'new'
            ).length
    );

const totalPieces =
    computed(
        () =>
            form.items.reduce(
                (
                    sum,
                    item
                ) =>
                    sum
                    + Math.max(
                        0,
                        Number(
                            item.quantity
                            || 0
                        )
                    ),
                0
            )
    );

const expectedAccountType =
    computed(
        () => ({
            cash:
                'cash',

            bank_transfer:
                'bank',

            banking_app:
                'banking_app',
        }[
            form.payment_method
        ] || null)
    );

const compatibleAccounts =
    computed(() => {
        if (
            !expectedAccountType
                .value
        ) {
            return [];
        }

        return props.financialAccounts
            .filter(
                account =>
                    account.is_active
                    !== false
                    && enumValue(
                        account.type
                    )
                    === expectedAccountType
                        .value
            );
    });

const selectedFinancialAccount =
    computed(
        () =>
            props.financialAccounts.find(
                account =>
                    Number(
                        account.id
                    )
                    === Number(
                        form
                            .financial_account_id
                    )
            )
            || null
    );

watch(
    () =>
        form.payment_method,
    () => {
        const accounts =
            compatibleAccounts
                .value;

        if (
            !accounts.some(
                account =>
                    Number(
                        account.id
                    )
                    === Number(
                        form
                            .financial_account_id
                    )
            )
        ) {
            form.financial_account_id =
                accounts[0]?.id
                || '';
        }

        if (
            form.payment_method
            === 'cash'
        ) {
            form.bank_or_app_name =
                '';

            form.transaction_reference =
                '';
        }
    },
    {
        immediate:
            true,
    }
);

watch(
    () =>
        form.financial_account_id,
    () => {
        if (
            [
                'bank_transfer',
                'banking_app',
            ].includes(
                form.payment_method
            )
            && selectedFinancialAccount
                .value
        ) {
            form.bank_or_app_name =
                selectedFinancialAccount
                    .value.name;
        }
    }
);

const invoiceError =
    computed(() => {
        if (!form.supplier_id) {
            return 'اختر المورد.';
        }

        if (!form.purchase_date) {
            return 'حدد تاريخ الشراء.';
        }

        if (
            !form.items.length
        ) {
            return 'أضف منتجاً واحداً على الأقل.';
        }

        for (
            const item
            of form.items
        ) {
            if (
                !item.warehouse_id
            ) {
                return `اختر المخزن للمنتج ${item.product_name}.`;
            }

            if (
                Number(
                    item.quantity
                    || 0
                ) < 1
            ) {
                return `كمية ${item.product_name} يجب أن تكون 1 على الأقل.`;
            }

            if (
                Number(
                    item
                        .unit_purchase_price
                    || 0
                ) < 0
            ) {
                return `سعر شراء ${item.product_name} غير صالح.`;
            }

            const beforeDiscount =
                Number(
                    item.quantity
                    || 0
                )
                * Number(
                    item
                        .unit_purchase_price
                    || 0
                );

            if (
                Number(
                    item.line_discount
                    || 0
                ) < 0
                || Number(
                    item.line_discount
                    || 0
                )
                    > beforeDiscount
            ) {
                return `راجع خصم المنتج ${item.product_name}.`;
            }
        }

        if (
            discountAmount.value
            > subtotal.value
        ) {
            return 'خصم الفاتورة أكبر من مجموع البنود.';
        }

        if (
            !isEdit.value
            && paymentAmount.value
                > total.value
        ) {
            return 'الدفعة الأولية أكبر من إجمالي الفاتورة.';
        }

        if (
            !isEdit.value
            && paymentAmount.value > 0
            && !form
                .financial_account_id
        ) {
            return 'اختر الحساب المالي للدفعة.';
        }

        return '';
    });

const newProductError =
    computed(() => {
        const data =
            newProduct.value;

        if (
            !data.category_id
        ) {
            return 'اختر فئة المنتج.';
        }

        if (
            normalize(
                data.name
            ).length < 2
        ) {
            return 'أدخل اسم المنتج بوضوح.';
        }

        if (
            data.selling_price
                === ''
            || Number(
                data.selling_price
            ) < 0
        ) {
            return 'أدخل سعر البيع.';
        }

        if (
            data
                .minimum_selling_price
            !== ''
            && Number(
                data
                    .minimum_selling_price
            )
                > Number(
                    data.selling_price
                    || 0
                )
        ) {
            return 'أقل سعر بيع لا يمكن أن يتجاوز سعر البيع.';
        }

        if (!data.warehouse_id) {
            return 'اختر المخزن الذي ستدخل إليه الكمية.';
        }

        if (
            Number(
                data.quantity
                || 0
            ) < 1
        ) {
            return 'كمية الشراء يجب أن تكون 1 على الأقل.';
        }

        if (
            data
                .unit_purchase_price
            === ''
            || Number(
                data
                    .unit_purchase_price
            ) < 0
        ) {
            return 'أدخل سعر شراء الوحدة.';
        }

        const lineSubtotal =
            Number(
                data.quantity
                || 0
            )
            * Number(
                data
                    .unit_purchase_price
                || 0
            );

        if (
            Number(
                data.line_discount
                || 0
            ) > lineSubtotal
        ) {
            return 'خصم البند أكبر من قيمته.';
        }

        const code =
            normalize(
                data.code
            );

        const barcode =
            normalize(
                data.barcode
            );

        const duplicateLocal =
            form.items.some(
                item => {
                    if (
                        item.product_mode
                        !== 'new'
                    ) {
                        return false;
                    }

                    const product =
                        item.new_product
                        || {};

                    return (
                        code
                        && normalize(
                            product.code
                        ) === code
                    )
                    || (
                        barcode
                        && normalize(
                            product.barcode
                        ) === barcode
                    );
                }
            );

        if (duplicateLocal) {
            return 'الكود أو الباركود مستخدم في منتج جديد آخر داخل الفاتورة.';
        }

        return '';
    });

const lineTotal =
    item =>
        Math.max(
            0,
            Number(
                item.quantity
                || 0
            )
            * Number(
                item
                    .unit_purchase_price
                || 0
            )
            - Number(
                item.line_discount
                || 0
            )
        );

const formatCurrency =
    value =>
        `${Number(value || 0).toLocaleString(
            'ar-PS',
            {
                minimumFractionDigits:
                    2,

                maximumFractionDigits:
                    2,
            }
        )} شيكل`;

const categoryName =
    id =>
        props.categories.find(
            category =>
                Number(
                    category.id
                )
                === Number(id)
        )?.name
        || 'بدون فئة';

const warehouseName =
    id =>
        props.warehouses.find(
            warehouse =>
                Number(
                    warehouse.id
                )
                === Number(id)
        )?.name
        || 'غير محدد';

const addExistingProduct =
    product => {
        if (
            form.items.some(
                item =>
                    item.product_mode
                    === 'existing'
                    && Number(
                        item.product_id
                    )
                    === Number(
                        product.id
                    )
            )
        ) {
            pageError.value =
                'المنتج موجود بالفعل في الفاتورة. عدّل الكمية في البند الحالي.';

            return;
        }

        form.items.push(
            makeExistingItem(
                product
            )
        );

        productSearch.value =
            '';

        productSearchOpen.value =
            false;

        pageError.value =
            '';

        nextTick(
            () => {
                document
                    .querySelector(
                        '[data-last-purchase-item="true"]'
                    )
                    ?.scrollIntoView({
                        behavior:
                            'smooth',

                        block:
                            'center',
                    });
            }
        );
    };

const openNewProduct =
    () => {
        newProduct.value =
            freshNewProduct();

        newProductModal.value =
            true;

        pageError.value =
            '';
    };

const addNewProductLine =
    () => {
        if (
            newProductError.value
        ) {
            pageError.value =
                newProductError.value;

            return;
        }

        const data =
            JSON.parse(
                JSON.stringify(
                    newProduct.value
                )
            );

        form.items.push({
            local_key:
                `new-${Date.now()}-${Math.random()}`,

            product_mode:
                'new',

            product_id:
                null,

            product_name:
                data.name,

            product_code:
                data.code
                || 'سيولد تلقائياً',

            category_name:
                categoryName(
                    data.category_id
                ),

            warehouse_id:
                data.warehouse_id,

            quantity:
                Number(
                    data.quantity
                ),

            unit_purchase_price:
                Number(
                    data
                        .unit_purchase_price
                ),

            line_discount:
                Number(
                    data.line_discount
                    || 0
                ),

            new_product: {
                category_id:
                    data.category_id,

                name:
                    data.name,

                code:
                    data.code
                    || null,

                barcode:
                    data.barcode
                    || null,

                brand:
                    data.brand
                    || null,

                model:
                    data.model
                    || null,

                selling_price:
                    Number(
                        data.selling_price
                    ),

                minimum_selling_price:
                    data
                        .minimum_selling_price
                    === ''
                        ? null
                        : Number(
                            data
                                .minimum_selling_price
                        ),

                low_stock_threshold:
                    Number(
                        data
                            .low_stock_threshold
                        || 0
                    ),

                location:
                    data.location
                    || null,

                description:
                    data.description
                    || null,

                notes:
                    data.notes
                    || null,
            },
        });

        newProductModal.value =
            false;

        newProduct.value =
            freshNewProduct();

        pageError.value =
            '';
    };

const removeItem =
    index => {
        form.items.splice(
            index,
            1
        );
    };

const setPayment =
    mode => {
        if (
            mode === 'none'
        ) {
            form.payment_amount =
                0;

            return;
        }

        if (
            mode === 'half'
        ) {
            form.payment_amount =
                Number(
                    (
                        total.value
                        / 2
                    ).toFixed(2)
                );

            return;
        }

        form.payment_amount =
            Number(
                total.value
                    .toFixed(2)
            );
    };

const transformPayload =
    data => ({
        ...data,

        items:
            data.items.map(
                item => ({
                    product_mode:
                        item.product_mode,

                    product_id:
                        item.product_mode
                        === 'existing'
                            ? Number(
                                item.product_id
                            )
                            : null,

                    new_product:
                        item.product_mode
                        === 'new'
                            ? item.new_product
                            : null,

                    warehouse_id:
                        Number(
                            item.warehouse_id
                        ),

                    quantity:
                        Number(
                            item.quantity
                        ),

                    unit_purchase_price:
                        Number(
                            item
                                .unit_purchase_price
                        ),

                    line_discount:
                        Number(
                            item.line_discount
                            || 0
                        ),
                })
            ),
    });

const submit =
    () => {
        pageError.value =
            '';

        if (
            invoiceError.value
        ) {
            pageError.value =
                invoiceError.value;

            window.scrollTo({
                top: 0,
                behavior: 'smooth',
            });

            return;
        }

        form.clearErrors();

        form.transform(
            transformPayload
        );

        if (
            isEdit.value
        ) {
            form.put(
                route(
                    'purchases.update',
                    props.invoice.id
                ),
                {
                    preserveScroll:
                        true,

                    onError: () => {
                        pageError.value =
                            'راجع الحقول المحددة ثم أعد المحاولة.';
                    },
                }
            );

            return;
        }

        form.post(
            route(
                'purchases.store'
            ),
            {
                preserveScroll:
                    true,

                onError: () => {
                    pageError.value =
                        'راجع الحقول المحددة ثم أعد المحاولة.';
                },
            }
        );
    };

const field =
    'block w-full rounded-2xl border-slate-300 bg-white px-4 py-3.5 text-base text-slate-950 shadow-sm transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500';

const label =
    'mb-2 block text-sm font-black text-slate-700 dark:text-slate-200';
</script>

<template>
    <form
        class="space-y-6"
        @submit.prevent="submit"
    >
        <div
            v-if="
                pageError
                || form.errors.purchase
                || Object.keys(form.errors).length
            "
            class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold leading-6 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300"
        >
            {{
                form.errors.purchase
                || pageError
                || Object.values(form.errors)[0]
            }}
        </div>

        <div
            class="grid items-start gap-6 2xl:grid-cols-[minmax(0,1fr)_380px]"
        >
            <div
                class="min-w-0 space-y-6"
            >
                <!-- Invoice data -->
                <section
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="border-b border-slate-100 bg-gradient-to-l from-blue-50/80 to-white px-5 py-5 dark:border-slate-800 dark:from-blue-950/20 dark:to-slate-900"
                    >
                        <div
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-600 font-black text-white shadow-lg shadow-blue-600/20"
                            >
                                1
                            </div>

                            <div>
                                <h2
                                    class="font-black text-slate-950 dark:text-white"
                                >
                                    بيانات فاتورة الشراء
                                </h2>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    المورد وتاريخ الفاتورة والاستحقاق.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid gap-4 p-5 sm:p-6 md:grid-cols-2"
                    >
                        <div>
                            <label
                                :class="label"
                            >
                                المورد
                                <span class="text-rose-500">*</span>
                            </label>

                            <select
                                v-model="form.supplier_id"
                                :class="field"
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
                                    {{
                                        supplier.company_name
                                            ? `— ${supplier.company_name}`
                                            : ''
                                    }}
                                </option>
                            </select>

                            <p
                                v-if="selectedSupplier"
                                class="mt-2 text-xs text-slate-500"
                            >
                                {{
                                    selectedSupplier.phone
                                    || selectedSupplier.email
                                    || 'مورد مسجل'
                                }}
                            </p>
                        </div>

                        <div>
                            <label
                                :class="label"
                            >
                                رقم فاتورة المورد
                            </label>

                            <input
                                v-model.trim="form.supplier_invoice_number"
                                :class="field"
                                placeholder="اختياري"
                            />
                        </div>

                        <div>
                            <label
                                :class="label"
                            >
                                تاريخ الشراء
                                <span class="text-rose-500">*</span>
                            </label>

                            <input
                                v-model="form.purchase_date"
                                type="date"
                                :max="today"
                                :class="field"
                            />
                        </div>

                        <div>
                            <label
                                :class="label"
                            >
                                تاريخ الاستحقاق
                            </label>

                            <input
                                v-model="form.due_date"
                                type="date"
                                :min="form.purchase_date || today"
                                :class="field"
                            />
                        </div>
                    </div>
                </section>

                <!-- Products -->
                <section
                    class="overflow-visible rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="flex flex-col gap-4 border-b border-slate-100 bg-gradient-to-l from-violet-50/80 to-white px-5 py-5 dark:border-slate-800 dark:from-violet-950/20 dark:to-slate-900 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-600 font-black text-white shadow-lg shadow-violet-600/20"
                            >
                                2
                            </div>

                            <div>
                                <h2
                                    class="font-black text-slate-950 dark:text-white"
                                >
                                    المنتجات المشتراة
                                </h2>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    اختر منتجاً موجوداً أو أنشئ منتجاً جديداً أثناء الشراء.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-2 rounded-2xl bg-violet-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-violet-600/20 transition hover:bg-violet-700"
                            @click="openNewProduct"
                        >
                            <span class="text-lg">＋</span>
                            منتج غير موجود؟ أضفه الآن
                        </button>
                    </div>

                    <div
                        class="space-y-5 p-5 sm:p-6"
                    >
                        <div
                            class="relative z-30"
                        >
                            <label
                                :class="label"
                            >
                                البحث في المنتجات الموجودة
                            </label>

                            <input
                                v-model.trim="productSearch"
                                type="search"
                                autocomplete="off"
                                :class="field"
                                placeholder="ابحث بالاسم، الكود، الباركود، الماركة أو الموديل..."
                                @focus="productSearchOpen = true"
                                @input="productSearchOpen = true"
                                @blur="setTimeout(() => productSearchOpen = false, 150)"
                            />

                            <div
                                v-if="productSearchOpen"
                                class="absolute inset-x-0 top-full z-50 mt-2 max-h-80 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                            >
                                <button
                                    v-for="product in filteredProducts"
                                    :key="product.id"
                                    type="button"
                                    class="flex w-full items-center justify-between gap-4 rounded-xl px-3 py-3 text-right transition hover:bg-blue-50 dark:hover:bg-blue-950/20"
                                    @mousedown.prevent="addExistingProduct(product)"
                                >
                                    <div
                                        class="min-w-0"
                                    >
                                        <strong
                                            class="block truncate text-sm text-slate-950 dark:text-white"
                                        >
                                            {{ product.name }}
                                        </strong>

                                        <p
                                            class="mt-1 truncate text-xs text-slate-500"
                                        >
                                            {{ product.category?.name || 'بدون فئة' }}
                                            · {{ product.code }}
                                            <template v-if="product.brand">
                                                · {{ product.brand }}
                                            </template>
                                            <template v-if="product.model">
                                                {{ product.model }}
                                            </template>
                                        </p>
                                    </div>

                                    <div
                                        class="shrink-0 text-left"
                                    >
                                        <span
                                            class="block text-[10px] font-bold text-slate-400"
                                        >
                                            تكلفة حالية
                                        </span>

                                        <strong
                                            class="text-xs text-blue-700 dark:text-blue-300"
                                        >
                                            {{ formatCurrency(product.purchase_price) }}
                                        </strong>
                                    </div>
                                </button>

                                <div
                                    v-if="!filteredProducts.length"
                                    class="px-4 py-6 text-center"
                                >
                                    <strong
                                        class="block text-sm text-slate-700 dark:text-slate-200"
                                    >
                                        المنتج غير موجود
                                    </strong>

                                    <button
                                        type="button"
                                        class="mt-2 text-xs font-black text-violet-600"
                                        @mousedown.prevent="openNewProduct"
                                    >
                                        أضفه كمنتج جديد داخل الفاتورة
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="form.items.length"
                            class="space-y-3"
                        >
                            <article
                                v-for="(item, index) in form.items"
                                :key="item.local_key"
                                :data-last-purchase-item="
                                    index === form.items.length - 1
                                        ? 'true'
                                        : null
                                "
                                class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-800/40"
                            >
                                <div
                                    class="flex flex-col gap-4 xl:flex-row xl:items-start"
                                >
                                    <div
                                        class="min-w-0 flex-1"
                                    >
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <span
                                                class="rounded-lg px-2 py-1 text-[10px] font-black"
                                                :class="
                                                    item.product_mode === 'new'
                                                        ? 'bg-violet-600 text-white'
                                                        : 'bg-blue-100 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300'
                                                "
                                            >
                                                {{
                                                    item.product_mode === 'new'
                                                        ? 'منتج جديد'
                                                        : 'منتج موجود'
                                                }}
                                            </span>

                                            <strong
                                                class="text-sm text-slate-950 dark:text-white"
                                            >
                                                {{ item.product_name }}
                                            </strong>
                                        </div>

                                        <p
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            {{ item.category_name || 'بدون فئة' }}
                                            · {{ item.product_code || 'سيولد الكود تلقائياً' }}
                                        </p>

                                        <p
                                            v-if="item.product_mode === 'new'"
                                            class="mt-2 rounded-xl bg-violet-50 px-3 py-2 text-[11px] font-bold leading-5 text-violet-700 dark:bg-violet-950/20 dark:text-violet-300"
                                        >
                                            سيُنشأ هذا المنتج برصيد 0. الكمية أدناه لن تدخل المخزون إلا عند اعتماد الفاتورة.
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="self-start rounded-xl px-3 py-2 text-xs font-black text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20"
                                        @click="removeItem(index)"
                                    >
                                        حذف
                                    </button>
                                </div>

                                <div
                                    class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-[1.2fr_.6fr_.8fr_.8fr_.8fr]"
                                >
                                    <div>
                                        <label
                                            class="mb-1 block text-[11px] font-black text-slate-500"
                                        >
                                            المخزن
                                        </label>

                                        <select
                                            v-model="item.warehouse_id"
                                            :class="field"
                                        >
                                            <option value="">
                                                اختر المخزن
                                            </option>

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
                                        <label
                                            class="mb-1 block text-[11px] font-black text-slate-500"
                                        >
                                            الكمية
                                        </label>

                                        <input
                                            v-model.number="item.quantity"
                                            type="number"
                                            min="1"
                                            step="1"
                                            :class="field"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="mb-1 block text-[11px] font-black text-slate-500"
                                        >
                                            سعر شراء الوحدة
                                        </label>

                                        <input
                                            v-model.number="item.unit_purchase_price"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :class="field"
                                        />
                                    </div>

                                    <div>
                                        <label
                                            class="mb-1 block text-[11px] font-black text-slate-500"
                                        >
                                            خصم البند
                                        </label>

                                        <input
                                            v-model.number="item.line_discount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            :class="field"
                                        />
                                    </div>

                                    <div
                                        class="rounded-2xl bg-slate-950 px-4 py-3 text-white"
                                    >
                                        <p
                                            class="text-[10px] font-bold text-slate-400"
                                        >
                                            إجمالي البند
                                        </p>

                                        <strong
                                            class="mt-1 block text-sm"
                                        >
                                            {{ formatCurrency(lineTotal(item)) }}
                                        </strong>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div
                            v-else
                            class="rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50/50 px-6 py-12 text-center dark:border-slate-700 dark:bg-slate-800/30"
                        >
                            <div
                                class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-violet-100 text-xl dark:bg-violet-950/30"
                            >
                                🛒
                            </div>

                            <strong
                                class="mt-3 block text-sm text-slate-800 dark:text-slate-200"
                            >
                                لم تضف أي منتج بعد
                            </strong>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                ابحث عن منتج موجود، أو أنشئ المنتج الجديد من هنا مباشرة.
                            </p>
                        </div>
                    </div>
                </section>

                <!-- Notes -->
                <section
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900"
                >
                    <label
                        :class="label"
                    >
                        ملاحظات الفاتورة
                    </label>

                    <textarea
                        v-model.trim="form.notes"
                        rows="4"
                        maxlength="500"
                        :class="`${field} resize-none leading-7`"
                        placeholder="أي ملاحظات تخص المورد أو الفاتورة..."
                    />
                </section>
            </div>

            <!-- Summary -->
            <aside
                class="space-y-4 2xl:sticky 2xl:top-6"
            >
                <section
                    class="overflow-hidden rounded-3xl border border-slate-900 bg-slate-950 text-white shadow-xl dark:border-slate-700"
                >
                    <div
                        class="p-5"
                    >
                        <p
                            class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-300"
                        >
                            PURCHASE SUMMARY
                        </p>

                        <h2
                            class="mt-1 text-lg font-black"
                        >
                            ملخص الفاتورة
                        </h2>

                        <div
                            class="mt-5 grid grid-cols-3 gap-2"
                        >
                            <div
                                class="rounded-xl bg-white/5 p-3 text-center"
                            >
                                <p
                                    class="text-[10px] text-slate-400"
                                >
                                    البنود
                                </p>

                                <strong
                                    class="mt-1 block"
                                >
                                    {{ form.items.length }}
                                </strong>
                            </div>

                            <div
                                class="rounded-xl bg-white/5 p-3 text-center"
                            >
                                <p
                                    class="text-[10px] text-slate-400"
                                >
                                    القطع
                                </p>

                                <strong
                                    class="mt-1 block"
                                >
                                    {{ totalPieces }}
                                </strong>
                            </div>

                            <div
                                class="rounded-xl bg-violet-500/10 p-3 text-center"
                            >
                                <p
                                    class="text-[10px] text-violet-300"
                                >
                                    جديد
                                </p>

                                <strong
                                    class="mt-1 block text-violet-200"
                                >
                                    {{ newProductsCount }}
                                </strong>
                            </div>
                        </div>

                        <div
                            class="mt-5 space-y-3 text-sm"
                        >
                            <div
                                class="flex items-center justify-between"
                            >
                                <span class="text-slate-400">
                                    مجموع البنود
                                </span>

                                <strong>
                                    {{ formatCurrency(subtotal) }}
                                </strong>
                            </div>

                            <div
                                class="grid grid-cols-[1fr_130px] items-center gap-3"
                            >
                                <span class="text-slate-400">
                                    خصم الفاتورة
                                </span>

                                <input
                                    v-model.number="form.discount_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="rounded-xl border-white/10 bg-white/10 px-3 py-2 text-left text-sm font-black text-white focus:border-blue-400 focus:ring-blue-400"
                                />
                            </div>

                            <div
                                class="grid grid-cols-[1fr_130px] items-center gap-3"
                            >
                                <span class="text-slate-400">
                                    الشحن
                                </span>

                                <input
                                    v-model.number="form.shipping_cost"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="rounded-xl border-white/10 bg-white/10 px-3 py-2 text-left text-sm font-black text-white focus:border-blue-400 focus:ring-blue-400"
                                />
                            </div>

                            <div
                                class="grid grid-cols-[1fr_130px] items-center gap-3"
                            >
                                <span class="text-slate-400">
                                    مصاريف إضافية
                                </span>

                                <input
                                    v-model.number="form.additional_expenses"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="rounded-xl border-white/10 bg-white/10 px-3 py-2 text-left text-sm font-black text-white focus:border-blue-400 focus:ring-blue-400"
                                />
                            </div>
                        </div>

                        <div
                            class="my-5 border-t border-white/10"
                        ></div>

                        <div>
                            <p
                                class="text-xs text-slate-400"
                            >
                                الإجمالي النهائي
                            </p>

                            <strong
                                class="mt-1 block text-3xl font-black"
                            >
                                {{ formatCurrency(total) }}
                            </strong>
                        </div>
                    </div>
                </section>

                <!-- Initial payment create only -->
                <section
                    v-if="!isEdit"
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div>
                        <h3
                            class="text-sm font-black text-slate-950 dark:text-white"
                        >
                            الدفعة الأولية
                        </h3>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            اختيارية. ترحيلها المالي يتم عند اعتماد الفاتورة.
                        </p>
                    </div>

                    <div
                        class="mt-4 grid grid-cols-3 gap-2"
                    >
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 py-2 text-xs font-black dark:border-slate-700"
                            @click="setPayment('none')"
                        >
                            بدون
                        </button>

                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 py-2 text-xs font-black dark:border-slate-700"
                            @click="setPayment('half')"
                        >
                            النصف
                        </button>

                        <button
                            type="button"
                            class="rounded-xl bg-emerald-100 py-2 text-xs font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
                            @click="setPayment('full')"
                        >
                            كامل
                        </button>
                    </div>

                    <div
                        class="mt-4 space-y-3"
                    >
                        <input
                            v-model.number="form.payment_amount"
                            type="number"
                            min="0"
                            :max="total"
                            step="0.01"
                            :class="field"
                            placeholder="المبلغ المدفوع"
                        />

                        <template
                            v-if="paymentAmount > 0"
                        >
                            <select
                                v-model="form.payment_method"
                                :class="field"
                            >
                                <option
                                    v-for="(paymentLabel, value) in paymentMethods"
                                    :key="value"
                                    :value="value"
                                >
                                    {{ paymentLabel }}
                                </option>
                            </select>

                            <select
                                v-model="form.financial_account_id"
                                :class="field"
                            >
                                <option value="">
                                    اختر الحساب المالي
                                </option>

                                <option
                                    v-for="account in compatibleAccounts"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.name }}
                                    — {{ formatCurrency(account.current_balance) }}
                                </option>
                            </select>

                            <input
                                v-if="['bank_transfer', 'banking_app'].includes(form.payment_method)"
                                v-model.trim="form.transaction_reference"
                                :class="field"
                                placeholder="مرجع العملية — اختياري"
                            />
                        </template>

                        <div
                            class="flex items-center justify-between rounded-xl bg-amber-50 p-3 text-sm dark:bg-amber-950/20"
                        >
                            <span
                                class="font-bold text-amber-800 dark:text-amber-300"
                            >
                                المتبقي
                            </span>

                            <strong
                                class="text-amber-700 dark:text-amber-300"
                            >
                                {{ formatCurrency(remaining) }}
                            </strong>
                        </div>
                    </div>
                </section>

                <section
                    v-else
                    class="rounded-3xl border border-blue-200 bg-blue-50 p-4 text-xs leading-6 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-300"
                >
                    تعديل المسودة لا يغيّر الدفعات المسجلة سابقاً. الدفعات تُدار من صفحة تفاصيل الفاتورة.
                </section>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{
                        form.processing
                            ? 'جاري الحفظ...'
                            : isEdit
                                ? 'حفظ تعديلات الفاتورة'
                                : 'حفظ فاتورة الشراء كمسودة'
                    }}
                </button>

                <Link
                    :href="
                        isEdit
                            ? route('purchases.show', invoice.id)
                            : route('purchases.index')
                    "
                    class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                >
                    إلغاء
                </Link>
            </aside>
        </div>

        <!-- Inline new product modal -->
        <div
            v-if="newProductModal"
            class="fixed inset-0 z-[160] flex items-center justify-center bg-slate-950/80 p-3 backdrop-blur-md sm:p-5"
            @mousedown.self="newProductModal = false"
        >
            <div
                class="flex max-h-[95vh] w-full max-w-4xl flex-col overflow-hidden rounded-[30px] bg-white shadow-2xl dark:bg-slate-900"
            >
                <div
                    class="shrink-0 border-b border-slate-200 bg-gradient-to-l from-violet-50 via-white to-blue-50 px-5 py-5 dark:border-slate-800 dark:from-violet-950/20 dark:via-slate-900 dark:to-blue-950/20 sm:px-6"
                >
                    <div
                        class="flex items-start justify-between gap-4"
                    >
                        <div
                            class="flex items-start gap-3"
                        >
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-violet-600 text-xl text-white shadow-lg shadow-violet-600/20"
                            >
                                ＋
                            </div>

                            <div>
                                <div
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <h2
                                        class="text-xl font-black text-slate-950 dark:text-white"
                                    >
                                        إضافة منتج جديد أثناء الشراء
                                    </h2>

                                    <span
                                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
                                    >
                                        رصيد البداية = 0
                                    </span>
                                </div>

                                <p
                                    class="mt-1 text-sm leading-6 text-slate-500"
                                >
                                    أنشئ بطاقة المنتج وبند الشراء معاً. الكمية ستدخل المخزون فقط بعد اعتماد الفاتورة.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-xl text-slate-400 dark:border-slate-700 dark:bg-slate-800"
                            @click="newProductModal = false"
                        >
                            ×
                        </button>
                    </div>
                </div>

                <div
                    class="min-h-0 flex-1 space-y-6 overflow-y-auto p-5 sm:p-6"
                >
                    <div
                        v-if="newProductError"
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm font-bold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-300"
                    >
                        {{ newProductError }}
                    </div>

                    <section>
                        <div
                            class="mb-4"
                        >
                            <h3
                                class="text-sm font-black text-slate-950 dark:text-white"
                            >
                                بيانات بطاقة المنتج
                            </h3>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                هذه البيانات ستظهر تلقائياً في قسم المنتجات بعد حفظ الفاتورة.
                            </p>
                        </div>

                        <div
                            class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                        >
                            <div>
                                <label :class="label">
                                    اسم المنتج
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.trim="newProduct.name"
                                    :class="field"
                                    placeholder="مثال: Samsung A54 128GB"
                                />
                            </div>

                            <div>
                                <label :class="label">
                                    الفئة
                                    <span class="text-rose-500">*</span>
                                </label>

                                <select
                                    v-model="newProduct.category_id"
                                    :class="field"
                                >
                                    <option value="">
                                        اختر الفئة
                                    </option>

                                    <option
                                        v-for="category in categories"
                                        :key="category.id"
                                        :value="category.id"
                                    >
                                        {{ category.name }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label :class="label">
                                    الكود
                                    <span class="font-medium text-slate-400">
                                        اختياري
                                    </span>
                                </label>

                                <input
                                    v-model.trim="newProduct.code"
                                    :class="field"
                                    placeholder="اتركه فارغاً للتوليد التلقائي"
                                />
                            </div>

                            <div>
                                <label :class="label">
                                    الباركود
                                </label>

                                <input
                                    v-model.trim="newProduct.barcode"
                                    :class="field"
                                    placeholder="اختياري"
                                />
                            </div>

                            <div>
                                <label :class="label">
                                    الماركة
                                </label>

                                <input
                                    v-model.trim="newProduct.brand"
                                    :class="field"
                                    placeholder="Samsung"
                                />
                            </div>

                            <div>
                                <label :class="label">
                                    الموديل
                                </label>

                                <input
                                    v-model.trim="newProduct.model"
                                    :class="field"
                                    placeholder="A54"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-3 flex flex-wrap gap-2"
                        >
                            <button
                                v-for="brand in brandShortcuts"
                                :key="brand"
                                type="button"
                                class="rounded-full border px-3 py-1.5 text-xs font-bold transition"
                                :class="
                                    newProduct.brand === brand
                                        ? 'border-violet-400 bg-violet-50 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300'
                                        : 'border-slate-200 text-slate-500 dark:border-slate-700 dark:text-slate-300'
                                "
                                @click="newProduct.brand = brand"
                            >
                                {{ brand }}
                            </button>
                        </div>

                        <div
                            class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                        >
                            <div>
                                <label :class="label">
                                    سعر البيع
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.number="newProduct.selling_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :class="field"
                                />
                            </div>

                            <div>
                                <label :class="label">
                                    أقل سعر بيع
                                </label>

                                <input
                                    v-model.number="newProduct.minimum_selling_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :class="field"
                                    placeholder="اختياري"
                                />
                            </div>

                            <div>
                                <label :class="label">
                                    تنبيه المخزون المنخفض
                                </label>

                                <input
                                    v-model.number="newProduct.low_stock_threshold"
                                    type="number"
                                    min="0"
                                    step="1"
                                    :class="field"
                                />
                            </div>

                            <div>
                                <label :class="label">
                                    الموقع
                                </label>

                                <input
                                    v-model.trim="newProduct.location"
                                    :class="field"
                                    placeholder="رف / درج — اختياري"
                                />
                            </div>
                        </div>
                    </section>

                    <section
                        class="rounded-3xl border border-blue-200 bg-blue-50/60 p-5 dark:border-blue-900/50 dark:bg-blue-950/10"
                    >
                        <div
                            class="mb-4"
                        >
                            <h3
                                class="text-sm font-black text-slate-950 dark:text-white"
                            >
                                تفاصيل الشراء الحالية
                            </h3>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                هذه هي الكمية التي ستدخل المخزون عند اعتماد الفاتورة.
                            </p>
                        </div>

                        <div
                            class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                        >
                            <div>
                                <label :class="label">
                                    المخزن
                                    <span class="text-rose-500">*</span>
                                </label>

                                <select
                                    v-model="newProduct.warehouse_id"
                                    :class="field"
                                >
                                    <option value="">
                                        اختر المخزن
                                    </option>

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
                                <label :class="label">
                                    الكمية المشتراة
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.number="newProduct.quantity"
                                    type="number"
                                    min="1"
                                    step="1"
                                    :class="field"
                                />
                            </div>

                            <div>
                                <label :class="label">
                                    سعر شراء الوحدة
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.number="newProduct.unit_purchase_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :class="field"
                                />
                            </div>

                            <div>
                                <label :class="label">
                                    خصم البند
                                </label>

                                <input
                                    v-model.number="newProduct.line_discount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :class="field"
                                />
                            </div>
                        </div>

                        <div
                            class="mt-4 grid gap-3 sm:grid-cols-3"
                        >
                            <div
                                class="rounded-2xl bg-white p-4 dark:bg-slate-900"
                            >
                                <p class="text-[10px] font-black text-slate-400">
                                    رصيد المنتج قبل الاعتماد
                                </p>

                                <strong class="mt-1 block text-lg text-slate-900 dark:text-white">
                                    0
                                </strong>
                            </div>

                            <div
                                class="rounded-2xl bg-white p-4 dark:bg-slate-900"
                            >
                                <p class="text-[10px] font-black text-slate-400">
                                    رصيده بعد الاعتماد
                                </p>

                                <strong class="mt-1 block text-lg text-blue-700 dark:text-blue-300">
                                    {{ Number(newProduct.quantity || 0) }}
                                </strong>
                            </div>

                            <div
                                class="rounded-2xl bg-slate-950 p-4 text-white"
                            >
                                <p class="text-[10px] font-black text-slate-400">
                                    إجمالي البند
                                </p>

                                <strong class="mt-1 block text-lg">
                                    {{
                                        formatCurrency(
                                            Math.max(
                                                0,
                                                Number(newProduct.quantity || 0)
                                                * Number(newProduct.unit_purchase_price || 0)
                                                - Number(newProduct.line_discount || 0)
                                            )
                                        )
                                    }}
                                </strong>
                            </div>
                        </div>
                    </section>

                    <section
                        class="grid gap-4 md:grid-cols-2"
                    >
                        <div>
                            <label :class="label">
                                وصف المنتج
                            </label>

                            <textarea
                                v-model.trim="newProduct.description"
                                rows="3"
                                maxlength="1000"
                                :class="`${field} resize-none`"
                                placeholder="اختياري"
                            />
                        </div>

                        <div>
                            <label :class="label">
                                ملاحظات داخلية
                            </label>

                            <textarea
                                v-model.trim="newProduct.notes"
                                rows="3"
                                maxlength="1000"
                                :class="`${field} resize-none`"
                                placeholder="اختياري"
                            />
                        </div>
                    </section>
                </div>

                <div
                    class="grid shrink-0 grid-cols-2 gap-3 border-t border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/50"
                >
                    <button
                        type="button"
                        class="rounded-2xl border border-slate-300 bg-white py-3.5 text-sm font-black text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                        @click="newProductModal = false"
                    >
                        إلغاء
                    </button>

                    <button
                        type="button"
                        class="rounded-2xl bg-violet-600 py-3.5 text-sm font-black text-white shadow-lg shadow-violet-600/20 transition hover:bg-violet-700"
                        @click="addNewProductLine"
                    >
                        إضافة المنتج إلى الفاتورة
                    </button>
                </div>
            </div>
        </div>
    </form>
</template>
