<script setup>
import {
    computed,
    reactive,
    ref,
    watch,
} from 'vue';

import {
    Head,
    Link,
    router,
    useForm,
} from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    products: {
        type: Object,
        required: true,
    },

    stats: {
        type: Object,
        default: () => ({}),
    },

    filters: {
        type: Object,
        default: () => ({}),
    },

    warehouses: {
        type: Array,
        default: () => [],
    },

    selectedWarehouse: {
        type: Object,
        default: null,
    },

    warehouseSummary: {
        type: Array,
        default: () => [],
    },

    recentMovements: {
        type: Array,
        default: () => [],
    },

    operationProducts: {
        type: Array,
        default: () => [],
    },

    transferProducts: {
        type: Array,
        default: () => [],
    },

    categories: {
        type: Array,
        default: () => [],
    },
});

const filters = reactive({
    search:
        props.filters?.search || '',

    category_id:
        props.filters?.category_id || '',

    warehouse_id:
        props.filters?.warehouse_id || '',

    stock_status:
        props.filters?.stock_status || '',
});

const modals = reactive({
    add: {
        show: false,
        product: null,
    },

    deduct: {
        show: false,
        product: null,
    },

    transfer: {
        show: false,
        product: null,
    },
});

const addForm = useForm({
    product_id: null,
    warehouse_id: '',
    quantity: 1,
    reason: '',
    notes: '',
});

const deductForm = useForm({
    product_id: null,
    warehouse_id: '',
    type: 'manual_deduction',
    quantity: 1,
    reason: '',
    notes: '',
});

const transferForm = useForm({
    product_id: null,
    source_warehouse_id: '',
    destination_warehouse_id: '',
    quantity: 1,
    reason: '',
    notes: '',
});

const addProductSearch = ref('');
const transferProductSearch = ref('');

const quickAddReasons = [
    'تصحيح رصيد بعد التحقق',
    'إضافة مخزون يدوية',
    'تسوية فرق مخزني',
];

const quickDeductReasons = [
    'تصحيح رصيد بعد التحقق',
    'استخدام داخلي موثق',
    'تسوية فرق مخزني',
];

const quickTransferReasons = [
    'تغذية مخزن المبيعات',
    'تجهيز مخزن الصيانة',
    'إعادة توزيع المخزون',
];

const enumValue = (value) => {
    if (
        value
        && typeof value === 'object'
    ) {
        return value.value
            ?? value.name
            ?? '';
    }

    return String(value ?? '');
};

const number = (value) =>
    Number(value || 0)
        .toLocaleString('en-US', {
            maximumFractionDigits: 0,
        });

const money = (value) =>
    `${Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`;

const dateTime = (value) => {
    if (!value) {
        return '—';
    }

    const parsed =
        new Date(value);

    if (
        Number.isNaN(
            parsed.getTime()
        )
    ) {
        return value;
    }

    return new Intl.DateTimeFormat(
        'ar-PS-u-nu-latn',
        {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }
    ).format(parsed);
};

const normalizeSearch = (value) =>
    String(value ?? '')
        .trim()
        .toLocaleLowerCase('ar');

const warehouseTypeLabel = (type) => ({
    sales:
        'مبيعات',

    maintenance:
        'صيانة',
}[enumValue(type)] || 'مخزن');

const stockStatusClass = (status) => ({
    green:
        'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:ring-emerald-900/50',

    orange:
        'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-900/50',

    red:
        'bg-rose-50 text-rose-700 ring-rose-200 dark:bg-rose-950/30 dark:text-rose-300 dark:ring-rose-900/50',
}[status?.color] || 'bg-slate-100 text-slate-700 ring-slate-200');

const movementClass = (movement) =>
    movement.is_addition
        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
        : 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300';

const hasFilters = computed(() => Boolean(
    filters.search
    || filters.category_id
    || filters.warehouse_id
    || filters.stock_status
));

const statsCards = computed(() => [
    {
        label:
            'قيمة المخزون',

        value:
            money(
                props.stats.total_value
            ),

        hint:
            `${number(props.stats.total_pieces)} قطعة إجمالاً`,

        tone:
            'from-slate-950 to-slate-800 text-white',
    },
    {
        label:
            'مخزون المبيعات',

        value:
            money(
                props.stats.sales_value
            ),

        hint:
            'القيمة بسعر التكلفة',

        tone:
            'from-blue-600 to-indigo-600 text-white',
    },
    {
        label:
            'مخزون الصيانة',

        value:
            money(
                props.stats.maintenance_value
            ),

        hint:
            'القيمة بسعر التكلفة',

        tone:
            'from-violet-600 to-purple-600 text-white',
    },
    {
        label:
            'منتجات بها رصيد',

        value:
            number(
                props.stats.products_with_stock
            ),

        hint:
            'أصناف متوفرة حالياً',

        tone:
            'from-emerald-500 to-teal-600 text-white',
    },
    {
        label:
            'مخزون منخفض',

        value:
            number(
                props.stats.low_stock
            ),

        hint:
            'يحتاج متابعة',

        tone:
            'from-amber-500 to-orange-500 text-white',
    },
    {
        label:
            'نافد',

        value:
            number(
                props.stats.out_of_stock
            ),

        hint:
            `${number(props.stats.today_movements)} حركة اليوم`,

        tone:
            'from-rose-500 to-red-600 text-white',
    },
]);

const applyFilters = () => {
    router.get(
        route('inventory.index'),
        {
            search:
                filters.search || undefined,

            category_id:
                filters.category_id || undefined,

            warehouse_id:
                filters.warehouse_id || undefined,

            stock_status:
                filters.stock_status || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const clearFilters = () => {
    Object.assign(
        filters,
        {
            search: '',
            category_id: '',
            warehouse_id: '',
            stock_status: '',
        }
    );

    applyFilters();
};

/* =========================
 * Add stock
 * ========================= */

const selectedAddProduct = computed(() => {
    const id =
        Number(
            addForm.product_id || 0
        );

    return props.operationProducts.find(
        (product) =>
            Number(product.id) === id
    )
        || modals.add.product
        || null;
});

const filteredAddProducts = computed(() => {
    const needle =
        normalizeSearch(
            addProductSearch.value
        );

    const products =
        props.operationProducts;

    if (!needle) {
        return products.slice(0, 30);
    }

    return products
        .filter((product) =>
            normalizeSearch(
                [
                    product.name,
                    product.code,
                    product.category?.name,
                ]
                    .filter(Boolean)
                    .join(' ')
            ).includes(needle)
        )
        .slice(0, 30);
});

const selectedAddWarehouse = computed(() =>
    props.warehouses.find(
        (warehouse) =>
            Number(warehouse.id)
            === Number(
                addForm.warehouse_id || 0
            )
    ) || null
);

const addCurrentWarehouseStock = computed(() => {
    const product =
        selectedAddProduct.value;

    const warehouseId =
        Number(
            addForm.warehouse_id || 0
        );

    if (
        !product
        || !warehouseId
    ) {
        return 0;
    }

    return Number(
        product.stocks?.find(
            (stock) =>
                Number(
                    stock.warehouse_id
                    || stock.warehouse?.id
                )
                === warehouseId
        )?.quantity || 0
    );
});

const addQuantity = computed(() =>
    Math.max(
        0,
        Math.trunc(
            Number(
                addForm.quantity || 0
            )
        )
    )
);

const addAfter = computed(() =>
    addCurrentWarehouseStock.value
    + addQuantity.value
);

const addValidationError = computed(() => {
    if (!addForm.product_id) {
        return 'اختر المنتج المراد إضافة الكمية له.';
    }

    if (!addForm.warehouse_id) {
        return 'اختر المخزن الذي ستضاف إليه الكمية.';
    }

    if (addQuantity.value < 1) {
        return 'الكمية يجب أن تكون قطعة واحدة على الأقل.';
    }

    if (
        !String(
            addForm.reason || ''
        ).trim()
    ) {
        return 'اكتب سبب الإضافة اليدوية.';
    }

    return '';
});

const selectAddProduct = (product) => {
    addForm.product_id =
        product.id;

    modals.add.product =
        product;

    addForm.warehouse_id =
        '';

    addProductSearch.value =
        '';
};

const resetAddForm = () => {
    addForm.reset();
    addForm.clearErrors();

    addForm.product_id = null;
    addForm.warehouse_id = '';
    addForm.quantity = 1;
    addForm.reason = '';
    addForm.notes = '';

    addProductSearch.value = '';
};

const openAddModal = (product = null) => {
    resetAddForm();

    if (product) {
        const fullProduct =
            props.operationProducts.find(
                (item) =>
                    Number(item.id)
                    === Number(product.id)
            )
            || product;

        selectAddProduct(
            fullProduct
        );
    }

    modals.add.show = true;
};

const closeAddModal = () => {
    modals.add.show = false;
    modals.add.product = null;

    resetAddForm();
};

const submitAddStock = () => {
    if (addValidationError.value) {
        return;
    }

    addForm.quantity =
        addQuantity.value;

    addForm.post(
        route(
            'inventory.add-stock'
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                closeAddModal();
            },
        }
    );
};

/* =========================
 * Deduct stock
 * ========================= */

const selectedDeductProduct = computed(() => {
    const id =
        Number(
            deductForm.product_id || 0
        );

    return props.operationProducts.find(
        (product) =>
            Number(product.id) === id
    )
        || modals.deduct.product
        || null;
});

const deductWarehouseOptions = computed(() => {
    const product =
        selectedDeductProduct.value;

    if (!product) {
        return [];
    }

    return (
        product.stocks || []
    )
        .filter(
            (stock) =>
                Number(
                    stock.quantity || 0
                ) > 0
                && stock.warehouse
                && stock.warehouse.is_active !== false
        )
        .map(
            (stock) => ({
                warehouse:
                    stock.warehouse,

                quantity:
                    Number(
                        stock.quantity || 0
                    ),
            })
        );
});

const selectedDeductRow = computed(() =>
    deductWarehouseOptions.value.find(
        (row) =>
            Number(row.warehouse.id)
            === Number(
                deductForm.warehouse_id || 0
            )
    ) || null
);

const deductAvailable = computed(() =>
    Number(
        selectedDeductRow.value?.quantity || 0
    )
);

const deductQuantity = computed(() =>
    Math.max(
        0,
        Math.trunc(
            Number(
                deductForm.quantity || 0
            )
        )
    )
);

const deductAfter = computed(() =>
    Math.max(
        0,
        deductAvailable.value
        - deductQuantity.value
    )
);

const deductValidationError = computed(() => {
    if (!deductForm.product_id) {
        return 'المنتج غير محدد.';
    }

    if (!deductForm.warehouse_id) {
        return 'اختر المخزن الذي ستخصم منه الكمية.';
    }

    if (!deductForm.type) {
        return 'اختر نوع الخصم.';
    }

    if (deductQuantity.value < 1) {
        return 'الكمية يجب أن تكون قطعة واحدة على الأقل.';
    }

    if (
        deductQuantity.value
        > deductAvailable.value
    ) {
        return `الكمية المطلوبة أكبر من الرصيد المتوفر (${number(
            deductAvailable.value
        )}).`;
    }

    if (
        !String(
            deductForm.reason || ''
        ).trim()
    ) {
        return 'اكتب سبب الخصم.';
    }

    return '';
});

const resetDeductForm = () => {
    deductForm.reset();
    deductForm.clearErrors();

    deductForm.product_id = null;
    deductForm.warehouse_id = '';
    deductForm.type = 'manual_deduction';
    deductForm.quantity = 1;
    deductForm.reason = '';
    deductForm.notes = '';
};

const openDeductModal = (product) => {
    resetDeductForm();

    const fullProduct =
        props.operationProducts.find(
            (item) =>
                Number(item.id)
                === Number(product.id)
        )
        || product;

    modals.deduct.product =
        fullProduct;

    deductForm.product_id =
        fullProduct.id;

    const availableStocks =
        (fullProduct.stocks || [])
            .filter(
                (stock) =>
                    Number(
                        stock.quantity || 0
                    ) > 0
                    && stock.warehouse
                    && stock.warehouse.is_active !== false
            );

    if (
        availableStocks.length === 1
    ) {
        deductForm.warehouse_id =
            availableStocks[0]
                .warehouse_id
            || availableStocks[0]
                .warehouse?.id
            || '';
    }

    modals.deduct.show = true;
};

const closeDeductModal = () => {
    modals.deduct.show = false;
    modals.deduct.product = null;

    resetDeductForm();
};

const setDeductQuantity = (type) => {
    const available =
        deductAvailable.value;

    if (available <= 0) {
        deductForm.quantity = 1;
        return;
    }

    if (type === 'half') {
        deductForm.quantity =
            Math.max(
                1,
                Math.floor(
                    available * 0.5
                )
            );
        return;
    }

    deductForm.quantity =
        available;
};

const submitDeductStock = () => {
    if (deductValidationError.value) {
        return;
    }

    deductForm.quantity =
        deductQuantity.value;

    deductForm.post(
        route(
            'inventory.deduct-stock'
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                closeDeductModal();
            },
        }
    );
};

/* =========================
 * Transfer stock
 * ========================= */

const selectedTransferProduct = computed(() => {
    const id =
        Number(
            transferForm.product_id || 0
        );

    return props.transferProducts.find(
        (product) =>
            Number(product.id) === id
    )
        || modals.transfer.product
        || null;
});

const filteredTransferProducts = computed(() => {
    const needle =
        normalizeSearch(
            transferProductSearch.value
        );

    const products =
        props.transferProducts;

    if (!needle) {
        return products.slice(0, 30);
    }

    return products
        .filter((product) =>
            normalizeSearch(
                [
                    product.name,
                    product.code,
                    product.category?.name,
                ]
                    .filter(Boolean)
                    .join(' ')
            ).includes(needle)
        )
        .slice(0, 30);
});

const sourceWarehouseOptions = computed(() => {
    const product =
        selectedTransferProduct.value;

    if (!product) {
        return [];
    }

    return (
        product.stocks || []
    )
        .filter(
            (stock) =>
                Number(
                    stock.quantity || 0
                ) > 0
                && stock.warehouse
                && stock.warehouse.is_active !== false
        )
        .map(
            (stock) => ({
                warehouse:
                    stock.warehouse,

                quantity:
                    Number(
                        stock.quantity || 0
                    ),
            })
        );
});

const selectedSourceRow = computed(() =>
    sourceWarehouseOptions.value.find(
        (row) =>
            Number(row.warehouse.id)
            === Number(
                transferForm.source_warehouse_id || 0
            )
    ) || null
);

const selectedSourceWarehouse = computed(() =>
    selectedSourceRow.value?.warehouse
    || null
);

const sourceStockQuantity = computed(() =>
    Number(
        selectedSourceRow.value?.quantity || 0
    )
);

const destinationWarehouseOptions = computed(() =>
    props.warehouses.filter(
        (warehouse) =>
            warehouse.is_active !== false
            && Number(warehouse.id)
                !== Number(
                    transferForm.source_warehouse_id || 0
                )
    )
);

const selectedDestinationWarehouse = computed(() =>
    props.warehouses.find(
        (warehouse) =>
            Number(warehouse.id)
            === Number(
                transferForm.destination_warehouse_id || 0
            )
    ) || null
);

const destinationStockQuantity = computed(() => {
    const product =
        selectedTransferProduct.value;

    const warehouseId =
        Number(
            transferForm.destination_warehouse_id || 0
        );

    if (
        !product
        || !warehouseId
    ) {
        return 0;
    }

    return Number(
        product.stocks?.find(
            (stock) =>
                Number(
                    stock.warehouse_id
                    || stock.warehouse?.id
                )
                === warehouseId
        )?.quantity || 0
    );
});

const transferQuantity = computed(() =>
    Math.max(
        0,
        Math.trunc(
            Number(
                transferForm.quantity || 0
            )
        )
    )
);

const sourceStockAfterTransfer = computed(() =>
    Math.max(
        0,
        sourceStockQuantity.value
        - transferQuantity.value
    )
);

const destinationStockAfterTransfer = computed(() =>
    destinationStockQuantity.value
    + transferQuantity.value
);

const transferValidationError = computed(() => {
    if (!transferForm.product_id) {
        return 'اختر المنتج المراد نقله.';
    }

    if (!transferForm.source_warehouse_id) {
        return 'اختر المخزن المصدر.';
    }

    if (sourceStockQuantity.value <= 0) {
        return 'لا يوجد رصيد متاح في المخزن المصدر.';
    }

    if (!transferForm.destination_warehouse_id) {
        return 'اختر المخزن المستلم.';
    }

    if (
        Number(
            transferForm.destination_warehouse_id
        )
        === Number(
            transferForm.source_warehouse_id
        )
    ) {
        return 'المخزن المستلم يجب أن يكون مختلفاً عن المصدر.';
    }

    if (transferQuantity.value < 1) {
        return 'الكمية يجب أن تكون قطعة واحدة على الأقل.';
    }

    if (
        transferQuantity.value
        > sourceStockQuantity.value
    ) {
        return `الكمية المطلوبة أكبر من الرصيد المتوفر (${number(
            sourceStockQuantity.value
        )}).`;
    }

    if (
        !String(
            transferForm.reason || ''
        ).trim()
    ) {
        return 'اكتب سبب نقل المخزون.';
    }

    return '';
});

const resetTransferForm = () => {
    transferForm.reset();
    transferForm.clearErrors();

    transferForm.product_id = null;
    transferForm.source_warehouse_id = '';
    transferForm.destination_warehouse_id = '';
    transferForm.quantity = 1;
    transferForm.reason = '';
    transferForm.notes = '';

    transferProductSearch.value = '';
};

const selectTransferProduct = (product) => {
    transferForm.product_id =
        product.id;

    modals.transfer.product =
        product;

    transferForm.source_warehouse_id = '';
    transferForm.destination_warehouse_id = '';
    transferForm.quantity = 1;

    const available =
        (product.stocks || [])
            .filter(
                (stock) =>
                    Number(
                        stock.quantity || 0
                    ) > 0
                    && stock.warehouse
                    && stock.warehouse.is_active !== false
            );

    if (available.length === 1) {
        transferForm.source_warehouse_id =
            available[0].warehouse_id
            || available[0].warehouse?.id
            || '';
    }

    transferProductSearch.value = '';
};

const openTransferModal = (product = null) => {
    resetTransferForm();

    if (product) {
        const fullProduct =
            props.transferProducts.find(
                (item) =>
                    Number(item.id)
                    === Number(product.id)
            )
            || product;

        selectTransferProduct(
            fullProduct
        );
    }

    modals.transfer.show = true;
};

const closeTransferModal = () => {
    modals.transfer.show = false;
    modals.transfer.product = null;

    resetTransferForm();
};

const setTransferQuantity = (type) => {
    const available =
        sourceStockQuantity.value;

    if (available <= 0) {
        transferForm.quantity = 1;
        return;
    }

    if (type === 'quarter') {
        transferForm.quantity =
            Math.max(
                1,
                Math.floor(
                    available * 0.25
                )
            );
        return;
    }

    if (type === 'half') {
        transferForm.quantity =
            Math.max(
                1,
                Math.floor(
                    available * 0.5
                )
            );
        return;
    }

    transferForm.quantity =
        available;
};

const submitTransferStock = () => {
    if (transferValidationError.value) {
        return;
    }

    transferForm.quantity =
        transferQuantity.value;

    transferForm.post(
        route(
            'inventory.transfer-stock'
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                closeTransferModal();
            },
        }
    );
};

watch(
    () =>
        deductForm.warehouse_id,

    () => {
        if (
            deductAvailable.value > 0
            && deductQuantity.value
                > deductAvailable.value
        ) {
            deductForm.quantity =
                deductAvailable.value;
        }
    }
);

watch(
    () =>
        transferForm.source_warehouse_id,

    () => {
        if (
            Number(
                transferForm.destination_warehouse_id
            )
            === Number(
                transferForm.source_warehouse_id
            )
        ) {
            transferForm.destination_warehouse_id =
                '';
        }

        if (
            sourceStockQuantity.value > 0
            && transferQuantity.value
                > sourceStockQuantity.value
        ) {
            transferForm.quantity =
                sourceStockQuantity.value;
        }

        if (
            destinationWarehouseOptions.value.length
            === 1
        ) {
            transferForm.destination_warehouse_id =
                destinationWarehouseOptions.value[0].id;
        }
    }
);
</script>

<template>
    <Head title="إدارة المخزون" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                        >
                            Inventory Center
                        </span>

                        <span
                            class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
                        >
                            أرصدة مباشرة
                        </span>
                    </div>

                    <h1
                        class="mt-2 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl"
                    >
                        إدارة المخزون
                    </h1>

                    <p
                        class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                    >
                        متابعة أرصدة المبيعات والصيانة، النقص والنفاد، والتحكم
                        بالإضافات والخصومات والتحويلات بين المخازن بصورة مترابطة.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700"
                        @click="openAddModal()"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M12 4v16m8-8H4"
                            />
                        </svg>

                        إضافة كمية
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700"
                        @click="openTransferModal()"
                    >
                        <svg
                            class="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4"
                            />
                        </svg>

                        نقل بين المخازن
                    </button>

                    <Link
                        :href="route('inventory.movements')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        سجل الحركات
                    </Link>

                    <Link
                        :href="route('inventory-counts.index')"
                        class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        الجرد
                    </Link>
                </div>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats -->
            <section
                class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6"
            >
                <article
                    v-for="card in statsCards"
                    :key="card.label"
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="h-full bg-gradient-to-br p-4"
                        :class="card.tone"
                    >
                        <p class="text-[11px] font-black opacity-80">
                            {{ card.label }}
                        </p>

                        <p
                            dir="ltr"
                            class="mt-3 text-right text-lg font-black"
                        >
                            {{ card.value }}
                        </p>

                        <p class="mt-1 text-[10px] opacity-70">
                            {{ card.hint }}
                        </p>
                    </div>
                </article>
            </section>

            <!-- Warehouse distribution -->
            <section
                v-if="warehouseSummary.length"
                class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2
                            class="font-black text-slate-950 dark:text-white"
                        >
                            توزيع المخزون حسب المخزن
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            ملخص سريع للكميات والقيمة وعدد الأصناف الموجودة فعلياً.
                        </p>
                    </div>

                    <Link
                        :href="route('reports.inventory')"
                        class="text-xs font-black text-indigo-600 hover:underline dark:text-indigo-300"
                    >
                        فتح تقرير المخزون
                    </Link>
                </div>

                <div
                    class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3"
                >
                    <button
                        v-for="warehouse in warehouseSummary"
                        :key="warehouse.id"
                        type="button"
                        class="rounded-2xl border p-4 text-right transition"
                        :class="
                            Number(filters.warehouse_id) === Number(warehouse.id)
                                ? 'border-blue-400 bg-blue-50 ring-2 ring-blue-500/10 dark:border-blue-700 dark:bg-blue-950/20'
                                : 'border-slate-200 bg-slate-50 hover:border-blue-200 hover:bg-white dark:border-slate-700 dark:bg-slate-900/50'
                        "
                        @click="
                            filters.warehouse_id = warehouse.id;
                            applyFilters();
                        "
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span
                                    class="rounded-lg bg-white px-2 py-1 text-[10px] font-black text-slate-500 shadow-sm dark:bg-slate-800"
                                >
                                    {{ warehouse.type_label }}
                                </span>

                                <h3
                                    class="mt-2 font-black text-slate-950 dark:text-white"
                                >
                                    {{ warehouse.name }}
                                </h3>
                            </div>

                            <strong
                                dir="ltr"
                                class="text-sm text-blue-600 dark:text-blue-300"
                            >
                                {{ number(warehouse.pieces) }}
                            </strong>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-2">
                            <div
                                class="rounded-xl bg-white p-3 dark:bg-slate-800"
                            >
                                <p class="text-[10px] text-slate-400">
                                    القيمة
                                </p>
                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-xs"
                                >
                                    {{ money(warehouse.value) }}
                                </strong>
                            </div>

                            <div
                                class="rounded-xl bg-white p-3 dark:bg-slate-800"
                            >
                                <p class="text-[10px] text-slate-400">
                                    الأصناف
                                </p>
                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-xs"
                                >
                                    {{ number(warehouse.sku_count) }}
                                </strong>
                            </div>
                        </div>
                    </button>
                </div>
            </section>

            <!-- Filters -->
            <section
                class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800"
            >
                <div
                    class="grid gap-3 lg:grid-cols-2 2xl:grid-cols-[1.7fr_repeat(3,minmax(0,1fr))_auto]"
                >
                    <div class="relative">
                        <svg
                            class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                            />
                        </svg>

                        <input
                            v-model.trim="filters.search"
                            type="search"
                            placeholder="ابحث بالاسم، الكود أو الباركود..."
                            class="w-full rounded-xl border-slate-300 bg-white py-2.5 pr-10 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            @keyup.enter="applyFilters"
                        />
                    </div>

                    <select
                        v-model="filters.category_id"
                        class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    >
                        <option value="">
                            جميع الفئات
                        </option>

                        <option
                            v-for="category in categories"
                            :key="category.id"
                            :value="category.id"
                        >
                            {{ category.name }}
                        </option>
                    </select>

                    <select
                        v-model="filters.warehouse_id"
                        class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    >
                        <option value="">
                            جميع المخازن
                        </option>

                        <option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :value="warehouse.id"
                        >
                            {{ warehouse.name }}
                        </option>
                    </select>

                    <select
                        v-model="filters.stock_status"
                        class="rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                    >
                        <option value="">
                            جميع الحالات
                        </option>
                        <option value="available">
                            متوفر
                        </option>
                        <option value="low">
                            منخفض
                        </option>
                        <option value="out">
                            نافد
                        </option>
                    </select>

                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="rounded-xl bg-slate-950 px-4 py-2.5 text-xs font-black text-white dark:bg-white dark:text-slate-950"
                            @click="applyFilters"
                        >
                            تطبيق
                        </button>

                        <button
                            v-if="hasFilters"
                            type="button"
                            class="rounded-xl border border-slate-300 px-3 py-2.5 text-xs font-black text-slate-600 dark:border-slate-600 dark:text-slate-300"
                            @click="clearFilters"
                        >
                            مسح
                        </button>
                    </div>
                </div>

                <div
                    v-if="selectedWarehouse"
                    class="mt-3 flex items-center gap-2 rounded-xl bg-blue-50 px-3 py-2 text-xs text-blue-700 dark:bg-blue-950/20 dark:text-blue-300"
                >
                    <span class="font-black">
                        تنبيه:
                    </span>
                    حالة المخزون المعروضة الآن محسوبة بالنسبة إلى
                    <strong>
                        {{ selectedWarehouse.name }}
                    </strong>
                    فقط.
                </div>
            </section>

            <div
                class="grid items-start gap-6 2xl:grid-cols-[minmax(0,1fr)_330px]"
            >
                <!-- Inventory table -->
                <section
                    class="overflow-hidden rounded-[28px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <h2
                                class="font-black text-slate-950 dark:text-white"
                            >
                                أرصدة المنتجات
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ number(products.total) }} منتج مطابق للفلاتر
                            </p>
                        </div>

                        <span
                            v-if="selectedWarehouse"
                            class="rounded-xl bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                        >
                            {{ selectedWarehouse.name }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[1180px] w-full text-sm">
                            <thead class="bg-slate-950 text-white">
                                <tr>
                                    <th class="px-4 py-3 text-right">
                                        المنتج
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        الفئة
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        سعر التكلفة
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        المبيعات
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        الصيانة
                                    </th>
                                    <th
                                        v-if="selectedWarehouse"
                                        class="px-4 py-3 text-right"
                                    >
                                        المخزن المحدد
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        الإجمالي
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        قيمة المخزون
                                    </th>
                                    <th class="px-4 py-3 text-right">
                                        الحالة
                                    </th>
                                    <th class="px-4 py-3 text-center">
                                        الإجراءات
                                    </th>
                                </tr>
                            </thead>

                            <tbody
                                class="divide-y divide-slate-100 dark:divide-slate-700"
                            >
                                <tr
                                    v-for="product in products.data"
                                    :key="product.id"
                                    class="transition hover:bg-slate-50 dark:hover:bg-slate-700/30"
                                >
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-slate-100 dark:bg-slate-700"
                                            >
                                                <img
                                                    v-if="product.image_path"
                                                    :src="`/storage/${product.image_path}`"
                                                    :alt="product.name"
                                                    class="h-full w-full object-cover"
                                                />

                                                <svg
                                                    v-else
                                                    class="h-5 w-5 text-slate-400"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.8"
                                                        d="M6 3h12l3 5-3 5H6L3 8l3-5Zm0 10v8h12v-8"
                                                    />
                                                </svg>
                                            </div>

                                            <div class="min-w-0">
                                                <div class="flex items-center gap-2">
                                                    <Link
                                                        :href="route('products.show', product.id)"
                                                        class="truncate font-black text-slate-950 hover:text-blue-600 dark:text-white dark:hover:text-blue-300"
                                                    >
                                                        {{ product.name }}
                                                    </Link>

                                                    <span
                                                        v-if="!product.is_active"
                                                        class="rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-black text-slate-500 dark:bg-slate-700"
                                                    >
                                                        غير نشط
                                                    </span>
                                                </div>

                                                <p
                                                    dir="ltr"
                                                    class="mt-1 text-right text-[10px] text-slate-400"
                                                >
                                                    {{ product.code || 'بدون كود' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 text-slate-600 dark:text-slate-300">
                                        {{ product.category?.name || '—' }}
                                    </td>

                                    <td
                                        dir="ltr"
                                        class="px-4 py-3 text-right font-bold text-slate-700 dark:text-slate-200"
                                    >
                                        {{ money(product.purchase_price) }}
                                    </td>

                                    <td
                                        dir="ltr"
                                        class="px-4 py-3 text-right font-black text-blue-600 dark:text-blue-300"
                                    >
                                        {{ number(product.sales_stock) }}
                                    </td>

                                    <td
                                        dir="ltr"
                                        class="px-4 py-3 text-right font-black text-violet-600 dark:text-violet-300"
                                    >
                                        {{ number(product.maintenance_stock) }}
                                    </td>

                                    <td
                                        v-if="selectedWarehouse"
                                        dir="ltr"
                                        class="px-4 py-3 text-right font-black text-cyan-600 dark:text-cyan-300"
                                    >
                                        {{ number(product.filtered_warehouse_stock) }}
                                    </td>

                                    <td
                                        dir="ltr"
                                        class="px-4 py-3 text-right font-black text-slate-950 dark:text-white"
                                    >
                                        {{ number(product.total_stock) }}
                                    </td>

                                    <td
                                        dir="ltr"
                                        class="px-4 py-3 text-right font-black text-emerald-600"
                                    >
                                        {{ money(product.inventory_value) }}
                                    </td>

                                    <td class="px-4 py-3">
                                        <div>
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black ring-1 ring-inset"
                                                :class="stockStatusClass(product.display_stock_status)"
                                            >
                                                {{ product.display_stock_status?.label || '—' }}
                                            </span>

                                            <p
                                                class="mt-1 text-[9px] text-slate-400"
                                            >
                                                الحد الأدنى:
                                                <span dir="ltr">
                                                    {{ number(product.low_stock_threshold || 5) }}
                                                </span>
                                            </p>
                                        </div>
                                    </td>

                                    <td class="px-4 py-3">
                                        <div
                                            class="flex justify-center gap-1.5"
                                        >
                                            <button
                                                type="button"
                                                :disabled="!product.is_active"
                                                title="إضافة كمية"
                                                class="rounded-lg bg-emerald-50 p-2 text-emerald-700 transition hover:bg-emerald-100 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-emerald-950/30 dark:text-emerald-300"
                                                @click="openAddModal(product)"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 4v16m8-8H4"
                                                    />
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                :disabled="
                                                    !product.is_active
                                                    || Number(product.total_stock || 0) <= 0
                                                "
                                                title="خصم كمية"
                                                class="rounded-lg bg-rose-50 p-2 text-rose-700 transition hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-rose-950/30 dark:text-rose-300"
                                                @click="openDeductModal(product)"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M20 12H4"
                                                    />
                                                </svg>
                                            </button>

                                            <button
                                                type="button"
                                                :disabled="
                                                    !product.is_active
                                                    || Number(product.total_stock || 0) <= 0
                                                "
                                                title="نقل بين المخازن"
                                                class="rounded-lg bg-blue-50 p-2 text-blue-700 transition hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-blue-950/30 dark:text-blue-300"
                                                @click="openTransferModal(product)"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M8 7h12m0 0-4-4m4 4-4 4M16 17H4m0 0 4 4m-4-4 4-4"
                                                    />
                                                </svg>
                                            </button>

                                            <Link
                                                :href="route('inventory.movements', { product_id: product.id })"
                                                title="سجل المنتج"
                                                class="rounded-lg bg-slate-100 p-2 text-slate-600 transition hover:bg-slate-200 dark:bg-slate-700 dark:text-slate-200"
                                            >
                                                <svg
                                                    class="h-4 w-4"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                                                    />
                                                </svg>
                                            </Link>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="!products.data.length">
                                    <td
                                        :colspan="selectedWarehouse ? 10 : 9"
                                        class="px-6 py-16 text-center"
                                    >
                                        <div class="mx-auto max-w-md">
                                            <div
                                                class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400 dark:bg-slate-700"
                                            >
                                                <svg
                                                    class="h-7 w-7"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="1.8"
                                                        d="M6 3h12l3 5-3 5H6L3 8l3-5Zm0 10v8h12v-8"
                                                    />
                                                </svg>
                                            </div>

                                            <h3
                                                class="mt-4 font-black text-slate-800 dark:text-slate-100"
                                            >
                                                لا توجد منتجات مطابقة
                                            </h3>

                                            <p class="mt-2 text-sm text-slate-500">
                                                جرّب تعديل البحث أو الفلاتر الحالية.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="flex flex-col gap-3 border-t border-slate-200 p-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <p class="text-xs text-slate-500">
                            عرض
                            <span dir="ltr">
                                {{ number(products.from || 0) }}
                            </span>
                            -
                            <span dir="ltr">
                                {{ number(products.to || 0) }}
                            </span>
                            من
                            <span dir="ltr">
                                {{ number(products.total || 0) }}
                            </span>
                        </p>

                        <Pagination :links="products.links" />
                    </div>
                </section>

                <!-- Recent movement -->
                <aside
                    class="rounded-[28px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2
                                class="font-black text-slate-950 dark:text-white"
                            >
                                آخر الحركات
                            </h2>
                            <p class="mt-1 text-[10px] text-slate-500">
                                أحدث تغييرات المخزون
                            </p>
                        </div>

                        <Link
                            :href="route('inventory.movements')"
                            class="text-[10px] font-black text-blue-600 hover:underline dark:text-blue-300"
                        >
                            عرض الكل
                        </Link>
                    </div>

                    <div class="mt-4 space-y-2">
                        <article
                            v-for="movement in recentMovements"
                            :key="movement.id"
                            class="rounded-2xl border border-slate-100 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900/60"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <strong
                                        class="block truncate text-xs text-slate-900 dark:text-white"
                                    >
                                        {{ movement.product?.name || 'منتج' }}
                                    </strong>

                                    <p
                                        class="mt-1 truncate text-[10px] text-slate-400"
                                    >
                                        {{ movement.warehouse?.name || '—' }}
                                    </p>
                                </div>

                                <span
                                    class="rounded-lg px-2 py-1 text-[9px] font-black"
                                    :class="movementClass(movement)"
                                >
                                    {{ movement.type_label }}
                                </span>
                            </div>

                            <div class="mt-3 flex items-end justify-between gap-3">
                                <span
                                    dir="ltr"
                                    class="text-sm font-black"
                                    :class="
                                        movement.is_addition
                                            ? 'text-emerald-600'
                                            : 'text-rose-600'
                                    "
                                >
                                    {{ movement.is_addition ? '+' : '-' }}{{ number(movement.quantity) }}
                                </span>

                                <span class="text-[9px] text-slate-400">
                                    {{ dateTime(movement.created_at) }}
                                </span>
                            </div>
                        </article>

                        <div
                            v-if="!recentMovements.length"
                            class="rounded-2xl border-2 border-dashed border-slate-200 py-10 text-center text-xs text-slate-500 dark:border-slate-700"
                        >
                            لا توجد حركات حتى الآن.
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <!-- =====================
             Add Stock Modal
        ====================== -->
        <Modal
            :show="modals.add.show"
            @close="closeAddModal"
        >
            <template #title>
                إضافة كمية للمخزون
            </template>

            <template #content>
                <div
                    class="inventory-modal-scroll max-h-[calc(100dvh-8.5rem)] overflow-y-auto overscroll-contain px-1 pb-1"
                >
                    <form
                        class="space-y-4 pr-1"
                        @submit.prevent="submitAddStock"
                    >
                        <div
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-xs leading-6 text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/20 dark:text-emerald-200"
                        >
                            الإضافة اليدوية مخصصة للتصحيح أو التسوية فقط.
                            المشتريات والمرتجعات والجرد يفضل تنفيذها من مساراتها المخصصة حتى يبقى سجل المخزون واضحاً.
                        </div>

                        <section
                            v-if="!modals.add.product"
                            class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700"
                        >
                            <label
                                class="text-sm font-black text-slate-900 dark:text-white"
                            >
                                المنتج
                            </label>

                            <input
                                v-model.trim="addProductSearch"
                                type="search"
                                placeholder="ابحث باسم المنتج أو الكود..."
                                class="mt-3 w-full rounded-xl border-slate-300 bg-slate-50 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />

                            <div
                                class="mt-2 max-h-44 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-1 dark:border-slate-700"
                            >
                                <button
                                    v-for="product in filteredAddProducts"
                                    :key="product.id"
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-right hover:bg-emerald-50 dark:hover:bg-emerald-950/20"
                                    @click="selectAddProduct(product)"
                                >
                                    <div>
                                        <strong
                                            class="block text-xs text-slate-900 dark:text-white"
                                        >
                                            {{ product.name }}
                                        </strong>
                                        <span
                                            dir="ltr"
                                            class="mt-1 block text-right text-[9px] text-slate-400"
                                        >
                                            {{ product.code || '—' }}
                                        </span>
                                    </div>

                                    <span
                                        dir="ltr"
                                        class="text-[10px] font-black text-slate-500"
                                    >
                                        {{ number(product.total_stock) }}
                                    </span>
                                </button>
                            </div>
                        </section>

                        <section
                            v-else
                            class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900"
                        >
                            <p class="text-[10px] text-slate-400">
                                المنتج
                            </p>
                            <strong
                                class="mt-1 block text-sm text-slate-950 dark:text-white"
                            >
                                {{ selectedAddProduct?.name }}
                            </strong>
                        </section>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300"
                                >
                                    المخزن
                                </label>

                                <select
                                    v-model="addForm.warehouse_id"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
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
                                    class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300"
                                >
                                    الكمية
                                </label>

                                <input
                                    v-model.number="addForm.quantity"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                />
                            </div>
                        </div>

                        <div
                            v-if="selectedAddWarehouse"
                            class="grid grid-cols-2 gap-3"
                        >
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900"
                            >
                                <p class="text-[10px] text-slate-400">
                                    الرصيد الحالي
                                </p>
                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-lg"
                                >
                                    {{ number(addCurrentWarehouseStock) }}
                                </strong>
                            </div>

                            <div
                                class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950/20"
                            >
                                <p class="text-[10px] text-emerald-600">
                                    بعد الإضافة
                                </p>
                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-lg text-emerald-700 dark:text-emerald-300"
                                >
                                    {{ number(addAfter) }}
                                </strong>
                            </div>
                        </div>

                        <section>
                            <label
                                class="text-sm font-black text-slate-900 dark:text-white"
                            >
                                سبب الإضافة
                            </label>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    v-for="reason in quickAddReasons"
                                    :key="reason"
                                    type="button"
                                    class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10px] font-bold text-slate-600 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-slate-700 dark:text-slate-300"
                                    @click="addForm.reason = reason"
                                >
                                    {{ reason }}
                                </button>
                            </div>

                            <input
                                v-model.trim="addForm.reason"
                                type="text"
                                maxlength="500"
                                class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                placeholder="سبب واضح للعملية"
                            />
                        </section>

                        <textarea
                            v-model.trim="addForm.notes"
                            rows="2"
                            maxlength="500"
                            class="w-full resize-none rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            placeholder="ملاحظات إضافية - اختياري"
                        ></textarea>

                        <div
                            v-if="
                                addValidationError
                                || Object.keys(addForm.errors).length
                            "
                            class="rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                        >
                            <p v-if="addValidationError" class="font-black">
                                {{ addValidationError }}
                            </p>

                            <p
                                v-for="(error, key) in addForm.errors"
                                :key="key"
                                class="mt-1"
                            >
                                {{ error }}
                            </p>
                        </div>

                        <div
                            class="sticky bottom-0 z-20 -mx-1 flex justify-end gap-2 rounded-2xl bg-slate-950 p-4"
                        >
                            <button
                                type="button"
                                class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-black text-slate-200"
                                @click="closeAddModal"
                            >
                                إلغاء
                            </button>

                            <button
                                type="submit"
                                :disabled="
                                    addForm.processing
                                    || Boolean(addValidationError)
                                "
                                class="rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-black text-slate-950 disabled:opacity-50"
                            >
                                {{ addForm.processing ? 'جاري الإضافة...' : 'تأكيد الإضافة' }}
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </Modal>

        <!-- =====================
             Deduct Stock Modal
        ====================== -->
        <Modal
            :show="modals.deduct.show"
            @close="closeDeductModal"
        >
            <template #title>
                خصم كمية من المخزون
            </template>

            <template #content>
                <div
                    class="inventory-modal-scroll max-h-[calc(100dvh-8.5rem)] overflow-y-auto overscroll-contain px-1 pb-1"
                >
                    <form
                        class="space-y-4 pr-1"
                        @submit.prevent="submitDeductStock"
                    >
                        <section
                            class="rounded-2xl bg-slate-950 p-4 text-white"
                        >
                            <p class="text-[10px] text-slate-400">
                                المنتج
                            </p>
                            <strong class="mt-1 block text-base">
                                {{ selectedDeductProduct?.name }}
                            </strong>

                            <p
                                dir="ltr"
                                class="mt-1 text-right text-[10px] text-slate-400"
                            >
                                {{ selectedDeductProduct?.code || '—' }}
                            </p>
                        </section>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300"
                                >
                                    المخزن
                                </label>

                                <select
                                    v-model="deductForm.warehouse_id"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                >
                                    <option value="">
                                        اختر المخزن
                                    </option>

                                    <option
                                        v-for="row in deductWarehouseOptions"
                                        :key="row.warehouse.id"
                                        :value="row.warehouse.id"
                                    >
                                        {{ row.warehouse.name }}
                                        — {{ number(row.quantity) }} قطعة
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-bold text-slate-600 dark:text-slate-300"
                                >
                                    نوع الخصم
                                </label>

                                <select
                                    v-model="deductForm.type"
                                    class="w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                >
                                    <option value="manual_deduction">
                                        خصم يدوي
                                    </option>
                                    <option value="damaged">
                                        تالف
                                    </option>
                                    <option value="lost">
                                        مفقود
                                    </option>
                                </select>
                            </div>
                        </div>

                        <section>
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <label
                                        class="text-sm font-black text-slate-900 dark:text-white"
                                    >
                                        الكمية
                                    </label>
                                    <p class="mt-1 text-[10px] text-slate-500">
                                        لا يمكن تجاوز رصيد المخزن المحدد.
                                    </p>
                                </div>

                                <span
                                    dir="ltr"
                                    class="text-[10px] font-black text-slate-400"
                                >
                                    Max: {{ number(deductAvailable) }}
                                </span>
                            </div>

                            <input
                                v-model.number="deductForm.quantity"
                                type="number"
                                min="1"
                                :max="deductAvailable || 1"
                                step="1"
                                class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-lg font-black dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />

                            <div class="mt-2 grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 py-2 text-xs font-black dark:border-slate-700 dark:text-slate-200"
                                    @click="setDeductQuantity('half')"
                                >
                                    50%
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl bg-rose-50 py-2 text-xs font-black text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                                    @click="setDeductQuantity('all')"
                                >
                                    خصم الكمية كاملة
                                </button>
                            </div>
                        </section>

                        <div
                            v-if="selectedDeductRow"
                            class="grid grid-cols-2 gap-3"
                        >
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900"
                            >
                                <p class="text-[10px] text-slate-400">
                                    قبل الخصم
                                </p>
                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-lg"
                                >
                                    {{ number(deductAvailable) }}
                                </strong>
                            </div>

                            <div
                                class="rounded-xl bg-rose-50 p-3 dark:bg-rose-950/20"
                            >
                                <p class="text-[10px] text-rose-600">
                                    بعد الخصم
                                </p>
                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-lg text-rose-700 dark:text-rose-300"
                                >
                                    {{ number(deductAfter) }}
                                </strong>
                            </div>
                        </div>

                        <section>
                            <label
                                class="text-sm font-black text-slate-900 dark:text-white"
                            >
                                سبب الخصم
                            </label>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    v-for="reason in quickDeductReasons"
                                    :key="reason"
                                    type="button"
                                    class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10px] font-bold text-slate-600 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:text-slate-300"
                                    @click="deductForm.reason = reason"
                                >
                                    {{ reason }}
                                </button>
                            </div>

                            <input
                                v-model.trim="deductForm.reason"
                                type="text"
                                maxlength="500"
                                class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                placeholder="سبب واضح للعملية"
                            />
                        </section>

                        <textarea
                            v-model.trim="deductForm.notes"
                            rows="2"
                            maxlength="500"
                            class="w-full resize-none rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            placeholder="ملاحظات إضافية - اختياري"
                        ></textarea>

                        <div
                            v-if="
                                deductValidationError
                                || Object.keys(deductForm.errors).length
                            "
                            class="rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                        >
                            <p
                                v-if="deductValidationError"
                                class="font-black"
                            >
                                {{ deductValidationError }}
                            </p>

                            <p
                                v-for="(error, key) in deductForm.errors"
                                :key="key"
                                class="mt-1"
                            >
                                {{ error }}
                            </p>
                        </div>

                        <div
                            class="sticky bottom-0 z-20 -mx-1 flex justify-end gap-2 rounded-2xl bg-slate-950 p-4"
                        >
                            <button
                                type="button"
                                class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-black text-slate-200"
                                @click="closeDeductModal"
                            >
                                إلغاء
                            </button>

                            <button
                                type="submit"
                                :disabled="
                                    deductForm.processing
                                    || Boolean(deductValidationError)
                                "
                                class="rounded-xl bg-rose-500 px-5 py-2.5 text-sm font-black text-white disabled:opacity-50"
                            >
                                {{ deductForm.processing ? 'جاري الخصم...' : 'تأكيد الخصم' }}
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </Modal>

        <!-- =====================
             Transfer Modal
        ====================== -->
        <Modal
            :show="modals.transfer.show"
            @close="closeTransferModal"
        >
            <template #title>
                نقل كمية بين المخازن
            </template>

            <template #content>
                <div
                    class="inventory-modal-scroll max-h-[calc(100dvh-8.5rem)] overflow-y-auto overscroll-contain px-1 pb-1"
                >
                    <form
                        class="space-y-4 pr-1"
                        @submit.prevent="submitTransferStock"
                    >
                        <section
                            class="overflow-hidden rounded-[24px] bg-gradient-to-l from-slate-950 via-blue-950 to-indigo-700 p-4 text-white"
                        >
                            <span
                                class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-black text-blue-100"
                            >
                                STOCK TRANSFER
                            </span>

                            <h3 class="mt-3 text-lg font-black">
                                إعادة توزيع المخزون
                            </h3>

                            <p class="mt-1 text-xs leading-6 text-blue-100">
                                عملية النقل تغير مكان الكمية فقط ولا تغير إجمالي رصيد المنتج.
                            </p>
                        </section>

                        <section
                            v-if="!modals.transfer.product"
                            class="rounded-2xl border border-slate-200 p-4 dark:border-slate-700"
                        >
                            <label
                                class="text-sm font-black text-slate-900 dark:text-white"
                            >
                                المنتج
                            </label>

                            <input
                                v-model.trim="transferProductSearch"
                                type="search"
                                placeholder="ابحث باسم المنتج أو الكود..."
                                class="mt-3 w-full rounded-xl border-slate-300 bg-slate-50 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />

                            <div
                                class="mt-2 max-h-44 space-y-1 overflow-y-auto rounded-xl border border-slate-200 p-1 dark:border-slate-700"
                            >
                                <button
                                    v-for="product in filteredTransferProducts"
                                    :key="product.id"
                                    type="button"
                                    class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-right hover:bg-blue-50 dark:hover:bg-blue-950/20"
                                    @click="selectTransferProduct(product)"
                                >
                                    <div>
                                        <strong
                                            class="block text-xs text-slate-900 dark:text-white"
                                        >
                                            {{ product.name }}
                                        </strong>
                                        <span
                                            dir="ltr"
                                            class="mt-1 block text-right text-[9px] text-slate-400"
                                        >
                                            {{ product.code || '—' }}
                                        </span>
                                    </div>

                                    <span
                                        dir="ltr"
                                        class="text-[10px] font-black text-blue-600"
                                    >
                                        {{ number(product.total_stock) }}
                                    </span>
                                </button>
                            </div>
                        </section>

                        <section
                            v-else
                            class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900"
                        >
                            <p class="text-[10px] text-slate-400">
                                المنتج
                            </p>
                            <strong
                                class="mt-1 block text-sm text-slate-950 dark:text-white"
                            >
                                {{ selectedTransferProduct?.name }}
                            </strong>

                            <span
                                dir="ltr"
                                class="mt-1 block text-right text-xs font-black text-blue-600"
                            >
                                {{ number(selectedTransferProduct?.total_stock) }} قطعة
                            </span>
                        </section>

                        <div
                            class="grid gap-3 sm:grid-cols-[1fr_auto_1fr] sm:items-center"
                        >
                            <div
                                class="rounded-2xl border border-slate-200 p-3 dark:border-slate-700"
                            >
                                <p class="text-[10px] font-black text-rose-500">
                                    من المخزن
                                </p>

                                <select
                                    v-model="transferForm.source_warehouse_id"
                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                >
                                    <option value="">
                                        اختر المصدر
                                    </option>

                                    <option
                                        v-for="row in sourceWarehouseOptions"
                                        :key="row.warehouse.id"
                                        :value="row.warehouse.id"
                                    >
                                        {{ row.warehouse.name }}
                                        — {{ number(row.quantity) }}
                                    </option>
                                </select>

                                <p
                                    v-if="selectedSourceWarehouse"
                                    dir="ltr"
                                    class="mt-2 text-right text-sm font-black text-rose-600"
                                >
                                    {{ number(sourceStockQuantity) }} قطعة
                                </p>
                            </div>

                            <div
                                class="flex h-10 w-10 rotate-90 items-center justify-center rounded-full bg-blue-600 text-white sm:rotate-0"
                            >
                                →
                            </div>

                            <div
                                class="rounded-2xl border border-slate-200 p-3 dark:border-slate-700"
                            >
                                <p class="text-[10px] font-black text-emerald-600">
                                    إلى المخزن
                                </p>

                                <select
                                    v-model="transferForm.destination_warehouse_id"
                                    class="mt-2 w-full rounded-xl border-slate-300 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                >
                                    <option value="">
                                        اختر المستلم
                                    </option>

                                    <option
                                        v-for="warehouse in destinationWarehouseOptions"
                                        :key="warehouse.id"
                                        :value="warehouse.id"
                                    >
                                        {{ warehouse.name }}
                                    </option>
                                </select>

                                <p
                                    v-if="selectedDestinationWarehouse"
                                    dir="ltr"
                                    class="mt-2 text-right text-sm font-black text-emerald-600"
                                >
                                    {{ number(destinationStockQuantity) }} قطعة
                                </p>
                            </div>
                        </div>

                        <section>
                            <div class="flex items-end justify-between gap-3">
                                <label
                                    class="text-sm font-black text-slate-900 dark:text-white"
                                >
                                    الكمية
                                </label>

                                <span
                                    dir="ltr"
                                    class="text-[10px] font-black text-slate-400"
                                >
                                    Max: {{ number(sourceStockQuantity) }}
                                </span>
                            </div>

                            <input
                                v-model.number="transferForm.quantity"
                                type="number"
                                min="1"
                                :max="sourceStockQuantity || 1"
                                step="1"
                                class="mt-2 w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-3 text-lg font-black dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />

                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 py-2 text-xs font-black dark:border-slate-700 dark:text-slate-200"
                                    @click="setTransferQuantity('quarter')"
                                >
                                    25%
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 py-2 text-xs font-black dark:border-slate-700 dark:text-slate-200"
                                    @click="setTransferQuantity('half')"
                                >
                                    50%
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl bg-blue-50 py-2 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                                    @click="setTransferQuantity('all')"
                                >
                                    نقل الكل
                                </button>
                            </div>
                        </section>

                        <div
                            v-if="
                                selectedSourceWarehouse
                                && selectedDestinationWarehouse
                                && transferQuantity > 0
                                && transferQuantity <= sourceStockQuantity
                            "
                            class="grid grid-cols-2 gap-3"
                        >
                            <div
                                class="rounded-xl bg-rose-50 p-3 dark:bg-rose-950/20"
                            >
                                <p class="text-[10px] text-rose-600">
                                    المصدر بعد النقل
                                </p>
                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-lg text-rose-700 dark:text-rose-300"
                                >
                                    {{ number(sourceStockAfterTransfer) }}
                                </strong>
                            </div>

                            <div
                                class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950/20"
                            >
                                <p class="text-[10px] text-emerald-600">
                                    المستلم بعد النقل
                                </p>
                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-lg text-emerald-700 dark:text-emerald-300"
                                >
                                    {{ number(destinationStockAfterTransfer) }}
                                </strong>
                            </div>
                        </div>

                        <section>
                            <label
                                class="text-sm font-black text-slate-900 dark:text-white"
                            >
                                سبب النقل
                            </label>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <button
                                    v-for="reason in quickTransferReasons"
                                    :key="reason"
                                    type="button"
                                    class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-[10px] font-bold text-slate-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:text-slate-300"
                                    @click="transferForm.reason = reason"
                                >
                                    {{ reason }}
                                </button>
                            </div>

                            <input
                                v-model.trim="transferForm.reason"
                                type="text"
                                maxlength="500"
                                class="mt-2 w-full rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                placeholder="سبب واضح للنقل"
                            />
                        </section>

                        <textarea
                            v-model.trim="transferForm.notes"
                            rows="2"
                            maxlength="500"
                            class="w-full resize-none rounded-xl border-slate-300 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            placeholder="ملاحظات إضافية - اختياري"
                        ></textarea>

                        <div
                            v-if="
                                transferValidationError
                                || Object.keys(transferForm.errors).length
                            "
                            class="rounded-xl bg-rose-50 p-3 text-xs text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                        >
                            <p
                                v-if="transferValidationError"
                                class="font-black"
                            >
                                {{ transferValidationError }}
                            </p>

                            <p
                                v-for="(error, key) in transferForm.errors"
                                :key="key"
                                class="mt-1"
                            >
                                {{ error }}
                            </p>
                        </div>

                        <div
                            class="sticky bottom-0 z-20 -mx-1 flex justify-end gap-2 rounded-2xl bg-slate-950 p-4"
                        >
                            <button
                                type="button"
                                class="rounded-xl border border-slate-700 px-4 py-2.5 text-sm font-black text-slate-200"
                                @click="closeTransferModal"
                            >
                                إلغاء
                            </button>

                            <button
                                type="submit"
                                :disabled="
                                    transferForm.processing
                                    || Boolean(transferValidationError)
                                "
                                class="rounded-xl bg-blue-500 px-5 py-2.5 text-sm font-black text-white disabled:opacity-50"
                            >
                                {{ transferForm.processing ? 'جاري النقل...' : 'تأكيد النقل' }}
                            </button>
                        </div>
                    </form>
                </div>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>

<style scoped>
.inventory-modal-scroll {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
    -webkit-overflow-scrolling: touch;
}

.inventory-modal-scroll::-webkit-scrollbar {
    width: 6px;
}

.inventory-modal-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.inventory-modal-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}

:global(.dark) .inventory-modal-scroll {
    scrollbar-color: #475569 transparent;
}

:global(.dark) .inventory-modal-scroll::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
