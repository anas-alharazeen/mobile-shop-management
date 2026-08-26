<script setup>
import {
    computed,
    onBeforeUnmount,
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
    customers: {
        type: Array,
        default: () => [],
    },

    products: {
        type: Array,
        default: () => [],
    },

    suppliers: {
        type: Array,
        default: () => [],
    },

    financialAccounts: {
        type: Array,
        default: () => [],
    },

    maintenanceWarehouse: {
        type: Object,
        default: () => ({}),
    },
});

const todayLocal = () => {
    const date = new Date();
    const offset = date.getTimezoneOffset();

    return new Date(
        date.getTime() - offset * 60000
    )
        .toISOString()
        .slice(0, 10);
};

const nowLocal = () => {
    const date = new Date();
    const offset = date.getTimezoneOffset();

    return new Date(
        date.getTime() - offset * 60000
    )
        .toISOString()
        .slice(0, 16);
};

const form = useForm({
    customer_id: '',
    customer_name: '',
    customer_phone: '',
    save_customer: true,

    device_type: 'هاتف محمول',
    brand: '',
    model: '',
    color: '',
    problem_description: '',
    device_condition: '',
    received_accessories: '',
    lock_code: '',

    inspection_result: '',
    fault_cause: '',
    repair_action: '',
    technician_name: '',
    agreed_price: 0,
    expected_delivery_date: '',

    customer_notes: '',
    internal_notes: '',
    received_at: nowLocal(),

    stock_parts: [],
    external_parts: [],
    attachments: [],
});

const customerSearch = ref('');
const customerDropdownOpen = ref(false);

const partSource = ref('stock');
const productSearch = ref('');
const stockDraft = ref({
    product_id: '',
    quantity: 1,
    unit_price: '',
});

const externalDraft = ref(newExternalPart());

const imageInput = ref(null);
const imagePreviews = ref([]);
const uploadError = ref('');

const quickDeviceTypes = [
    'هاتف محمول',
    'جهاز لوحي',
    'ساعة ذكية',
    'سماعة',
    'لابتوب',
    'جهاز ألعاب',
];

const quickBrands = [
    'Samsung',
    'Apple',
    'Xiaomi',
    'Huawei',
    'Oppo',
    'Realme',
    'Honor',
    'Infinix',
];

const commonProblems = [
    'الشاشة مكسورة',
    'الشاشة لا تعمل',
    'الجهاز لا يشحن',
    'البطارية تفرغ بسرعة',
    'الجهاز لا يعمل',
    'مشكلة في الصوت',
    'مشكلة في السماعة',
    'مشكلة في الميكروفون',
    'مشكلة في الكاميرا',
    'مشكلة في الشبكة',
    'مشكلة برمجية',
    'تنظيف وصيانة',
];

const conditionOptions = [
    'حالة جيدة',
    'خدوش بسيطة',
    'خدوش واضحة',
    'كسر في الشاشة',
    'كسر في الظهر',
    'كسر في الإطار',
    'آثار ماء / رطوبة',
    'الجهاز لا يعمل',
];

const accessoriesOptions = [
    'بدون ملحقات',
    'شاحن',
    'كيبل شحن',
    'غطاء حماية',
    'شريحة SIM',
    'بطاقة ذاكرة',
    'قلم',
    'علبة الجهاز',
];

const normalize = value => String(value ?? '')
    .trim()
    .toLowerCase();

function newExternalPart() {
    return {
        part_name: '',
        quantity: 1,
        purchase_mode: 'draft',
        supplier_id: '',
        purchase_from: '',
        supplier_phone: '',
        purchase_reference: '',
        unit_purchase_price: '',
        customer_unit_price: '',
        financial_account_id: '',
        purchased_at: '',
        notes: '',
    };
}

const selectedCustomer = computed(() =>
    props.customers.find(
        customer =>
            Number(customer.id)
            === Number(form.customer_id)
    ) || null
);

const filteredCustomers = computed(() => {
    const q = normalize(customerSearch.value);

    if (!q) {
        return props.customers.slice(0, 8);
    }

    return props.customers
        .filter(
            customer =>
                [
                    customer.name,
                    customer.phone,
                    customer.code,
                ]
                    .filter(Boolean)
                    .some(
                        value =>
                            normalize(value)
                                .includes(q)
                    )
        )
        .slice(0, 10);
});

watch(
    () => form.customer_id,
    () => {
        if (!selectedCustomer.value) {
            return;
        }

        form.customer_name =
            selectedCustomer.value.name
            || '';

        form.customer_phone =
            selectedCustomer.value.phone
            || '';

        form.save_customer = false;

        customerSearch.value =
            selectedCustomer.value.name
            || '';
    }
);

const selectCustomer = customer => {
    form.customer_id = customer.id;
    form.customer_name = customer.name || '';
    form.customer_phone = customer.phone || '';
    form.save_customer = false;

    customerSearch.value =
        customer.name
        || '';

    customerDropdownOpen.value = false;
};

const resetCustomer = () => {
    form.customer_id = '';
    form.customer_name = '';
    form.customer_phone = '';
    form.save_customer = true;
    customerSearch.value = '';
    customerDropdownOpen.value = false;
};

const filteredProducts = computed(() => {
    const q = normalize(productSearch.value);

    return props.products
        .filter(
            product =>
                Number(
                    product.available_quantity
                    || 0
                ) > 0
        )
        .filter(
            product => {
                if (!q) {
                    return true;
                }

                return [
                    product.name,
                    product.code,
                    product.barcode,
                    product.brand,
                    product.model,
                ]
                    .filter(Boolean)
                    .some(
                        value =>
                            normalize(value)
                                .includes(q)
                    );
            }
        );
});

const selectedStockProduct = computed(() =>
    props.products.find(
        product =>
            Number(product.id)
            === Number(stockDraft.value.product_id)
    ) || null
);

watch(
    selectedStockProduct,
    product => {
        if (!product) {
            return;
        }

        stockDraft.value.unit_price =
            Number(
                product.selling_price
                || 0
            );
    }
);

const stockCost = computed(() =>
    form.stock_parts.reduce(
        (sum, part) =>
            sum
            + (
                Number(part.unit_cost || 0)
                * Number(part.quantity || 0)
            ),
        0
    )
);

const externalPurchasedCost = computed(() =>
    form.external_parts
        .filter(
            part =>
                part.purchase_mode
                === 'purchased'
        )
        .reduce(
            (sum, part) =>
                sum
                + (
                    Number(
                        part.unit_purchase_price
                        || 0
                    )
                    * Number(
                        part.quantity
                        || 0
                    )
                ),
            0
        )
);

const knownPartsCost = computed(() =>
    stockCost.value
    + externalPurchasedCost.value
);

const estimatedProfit = computed(() =>
    Number(form.agreed_price || 0)
    - knownPartsCost.value
);

const pendingExternalCount = computed(() =>
    form.external_parts.filter(
        part =>
            part.purchase_mode
            === 'draft'
    ).length
);

const totalPartsCount = computed(() =>
    form.stock_parts.length
    + form.external_parts.length
);

const requiredProgress = computed(() => {
    const values = [
        form.customer_name,
        form.customer_phone,
        form.device_type,
        form.brand,
        form.model,
        form.problem_description,
        form.inspection_result,
        form.repair_action,
        String(form.agreed_price ?? ''),
    ];

    const completed =
        values.filter(
            value =>
                String(value ?? '')
                    .trim() !== ''
        ).length;

    return Math.round(
        completed
        / values.length
        * 100
    );
});

const appendOption = (
    field,
    value,
    emptyLabel = ''
) => {
    if (
        field === 'received_accessories'
        && value === 'بدون ملحقات'
    ) {
        form[field] = value;
        return;
    }

    let current =
        String(form[field] || '')
            .trim();

    if (
        current === emptyLabel
    ) {
        current = '';
    }

    const values =
        current
            .split('،')
            .map(item => item.trim())
            .filter(Boolean);

    if (
        values.includes(value)
    ) {
        form[field] =
            values
                .filter(item => item !== value)
                .join('، ');

        return;
    }

    if (
        field === 'received_accessories'
    ) {
        const withoutNone =
            values.filter(
                item =>
                    item !== 'بدون ملحقات'
            );

        form[field] =
            [
                ...withoutNone,
                value,
            ].join('، ');

        return;
    }

    form[field] =
        [
            ...values,
            value,
        ].join('، ');
};

const hasOption = (
    field,
    value
) =>
    String(form[field] || '')
        .split('،')
        .map(item => item.trim())
        .includes(value);

const addStockPart = () => {
    const product =
        selectedStockProduct.value;

    const quantity =
        Number(
            stockDraft.value.quantity
            || 0
        );

    if (
        !product
        || quantity < 1
    ) {
        return;
    }

    if (
        quantity
        > Number(
            product.available_quantity
            || 0
        )
    ) {
        return;
    }

    if (
        form.stock_parts.some(
            part =>
                Number(part.product_id)
                === Number(product.id)
        )
    ) {
        return;
    }

    form.stock_parts.push({
        product_id:
            product.id,

        product_name:
            product.name,

        product_code:
            product.code,

        available_quantity:
            Number(
                product.available_quantity
                || 0
            ),

        quantity,

        unit_cost:
            Number(
                product.purchase_price
                || 0
            ),

        unit_price:
            Number(
                stockDraft.value.unit_price
                || product.selling_price
                || 0
            ),
    });

    stockDraft.value = {
        product_id: '',
        quantity: 1,
        unit_price: '',
    };

    productSearch.value = '';
};

const addExternalPart = () => {
    const draft =
        externalDraft.value;

    if (
        !String(
            draft.part_name
            || ''
        ).trim()
        || Number(
            draft.quantity
            || 0
        ) < 1
    ) {
        return;
    }

    if (
        draft.purchase_mode
        === 'purchased'
    ) {
        if (
            (
                !draft.supplier_id
                && !String(
                    draft.purchase_from
                    || ''
                ).trim()
            )
            || !Number(
                draft.unit_purchase_price
                || 0
            )
            || !draft.financial_account_id
        ) {
            return;
        }
    }

    form.external_parts.push({
        ...draft,
    });

    externalDraft.value =
        newExternalPart();
};

const removeStockPart = index =>
    form.stock_parts.splice(
        index,
        1
    );

const removeExternalPart = index =>
    form.external_parts.splice(
        index,
        1
    );

const triggerImagePicker = () => {
    imageInput.value?.click();
};

const addImages = event => {
    uploadError.value = '';

    const files =
        Array.from(
            event.target.files
            || []
        );

    const remaining =
        8
        - form.attachments.length;

    if (remaining <= 0) {
        uploadError.value =
            'يمكن إضافة 8 صور كحد أقصى.';

        event.target.value = '';
        return;
    }

    const accepted = [];

    for (
        const file of files.slice(
            0,
            remaining
        )
    ) {
        if (
            ![
                'image/jpeg',
                'image/png',
                'image/webp',
            ].includes(file.type)
        ) {
            uploadError.value =
                'الصور المسموحة: JPG وPNG وWEBP فقط.';

            continue;
        }

        if (
            file.size
            > 2 * 1024 * 1024
        ) {
            uploadError.value =
                'حجم الصورة الواحدة يجب ألا يتجاوز 2MB.';

            continue;
        }

        accepted.push(file);
    }

    for (const file of accepted) {
        form.attachments.push(file);

        imagePreviews.value.push({
            file,
            url:
                URL.createObjectURL(
                    file
                ),
        });
    }

    event.target.value = '';
};

const removeImage = index => {
    const preview =
        imagePreviews.value[index];

    if (preview?.url) {
        URL.revokeObjectURL(
            preview.url
        );
    }

    imagePreviews.value.splice(
        index,
        1
    );

    form.attachments.splice(
        index,
        1
    );
};

onBeforeUnmount(() => {
    imagePreviews.value.forEach(
        preview => {
            if (preview.url) {
                URL.revokeObjectURL(
                    preview.url
                );
            }
        }
    );
});

const submit = () => {
    form.post(
        route('repairs.store'),
        {
            forceFormData: true,
            preserveScroll: true,
        }
    );
};

const money = value =>
    `${Number(value || 0).toLocaleString(
        'ar-PS',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }
    )} شيكل`;

const inputClass =
    'block w-full rounded-2xl border-slate-300 bg-white px-4 py-3.5 text-base text-slate-950 shadow-sm transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white dark:placeholder:text-slate-500';

const labelClass =
    'mb-2 block text-sm font-black text-slate-700 dark:text-slate-200';
</script>

<template>
    <Head title="طلب صيانة جديد" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
            >
                <div>
                    <div
                        class="flex flex-wrap items-center gap-2"
                    >
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                        >
                            قسم الصيانة
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700 dark:bg-emerald-950/20 dark:text-emerald-300"
                        >
                            فحص + تسعير + قطع من شاشة واحدة
                        </span>
                    </div>

                    <h1
                        class="mt-3 text-2xl font-black tracking-tight text-slate-950 sm:text-3xl dark:text-white"
                    >
                        طلب صيانة جديد
                    </h1>

                    <p
                        class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                    >
                        سجّل بيانات الجهاز والتشخيص والسعر المتفق عليه وقطع الغيار بشكل سريع وواضح.
                    </p>
                </div>

                <div
                    class="flex flex-wrap items-center gap-3"
                >
                    <div
                        class="hidden min-w-48 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-900 sm:block"
                    >
                        <div
                            class="flex items-center justify-between text-xs"
                        >
                            <span
                                class="font-bold text-slate-500"
                            >
                                اكتمال البيانات
                            </span>

                            <strong
                                class="text-blue-600"
                            >
                                {{ requiredProgress }}٪
                            </strong>
                        </div>

                        <div
                            class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800"
                        >
                            <div
                                class="h-full rounded-full bg-blue-600 transition-all"
                                :style="{ width: `${requiredProgress}%` }"
                            />
                        </div>
                    </div>

                    <Link
                        :href="route('repairs.index')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                    >
                        العودة للصيانة
                    </Link>
                </div>
            </div>
        </template>

        <form
            class="grid items-start gap-6 2xl:grid-cols-[minmax(0,1fr)_380px]"
            @submit.prevent="submit"
        >
            <div
                class="min-w-0 space-y-6"
            >
                <!-- Customer -->
                <section
                    class="overflow-visible rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="flex flex-col gap-3 border-b border-slate-100 bg-gradient-to-l from-blue-50/80 to-white px-5 py-5 dark:border-slate-800 dark:from-blue-950/20 dark:to-slate-900 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-600 font-black text-white shadow-lg shadow-blue-600/20"
                            >
                                1
                            </div>

                            <div>
                                <h2
                                    class="text-base font-black text-slate-950 dark:text-white"
                                >
                                    بيانات العميل
                                </h2>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    اختر عميلاً سابقًا أو أدخل بيانات عميل جديد.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="rounded-xl border border-blue-200 bg-white px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-50 dark:border-blue-900/50 dark:bg-slate-900 dark:text-blue-300"
                            @click="resetCustomer"
                        >
                            + عميل جديد
                        </button>
                    </div>

                    <div
                        class="space-y-5 p-5 sm:p-6"
                    >
                        <div
                            class="relative z-30"
                        >
                            <label
                                :class="labelClass"
                            >
                                البحث في العملاء
                            </label>

                            <div
                                class="relative"
                            >
                                <input
                                    v-model.trim="customerSearch"
                                    type="search"
                                    autocomplete="off"
                                    :class="inputClass"
                                    placeholder="اكتب اسم العميل أو رقم الهاتف..."
                                    @focus="customerDropdownOpen = true"
                                    @input="customerDropdownOpen = true"
                                    @blur="setTimeout(() => customerDropdownOpen = false, 150)"
                                />

                                <div
                                    v-if="customerDropdownOpen"
                                    class="absolute inset-x-0 top-full z-50 mt-2 max-h-72 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
                                >
                                    <button
                                        v-for="customer in filteredCustomers"
                                        :key="customer.id"
                                        type="button"
                                        class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-3 text-right transition hover:bg-blue-50 dark:hover:bg-blue-950/20"
                                        @mousedown.prevent="selectCustomer(customer)"
                                    >
                                        <div>
                                            <strong
                                                class="block text-sm text-slate-950 dark:text-white"
                                            >
                                                {{ customer.name }}
                                            </strong>

                                            <span
                                                dir="ltr"
                                                class="mt-1 block text-right text-xs text-slate-400"
                                            >
                                                {{ customer.phone || customer.code }}
                                            </span>
                                        </div>

                                        <span
                                            class="text-xs font-black text-blue-600"
                                        >
                                            اختيار
                                        </span>
                                    </button>

                                    <p
                                        v-if="!filteredCustomers.length"
                                        class="px-3 py-5 text-center text-xs text-slate-500"
                                    >
                                        لا توجد نتائج.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="selectedCustomer"
                            class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900/50 dark:bg-emerald-950/20"
                        >
                            <div>
                                <strong
                                    class="block text-sm text-emerald-800 dark:text-emerald-200"
                                >
                                    {{ selectedCustomer.name }}
                                </strong>

                                <span
                                    dir="ltr"
                                    class="mt-1 block text-right text-xs text-emerald-600"
                                >
                                    {{ selectedCustomer.phone || selectedCustomer.code }}
                                </span>
                            </div>

                            <button
                                type="button"
                                class="text-xs font-black text-slate-500 hover:text-rose-600"
                                @click="resetCustomer"
                            >
                                تغيير العميل
                            </button>
                        </div>

                        <div
                            class="grid gap-4 md:grid-cols-2"
                        >
                            <div>
                                <label
                                    :class="labelClass"
                                >
                                    اسم العميل
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.trim="form.customer_name"
                                    type="text"
                                    maxlength="255"
                                    :class="inputClass"
                                    placeholder="اسم العميل"
                                />

                                <p
                                    v-if="form.errors.customer_name"
                                    class="mt-2 text-xs font-bold text-rose-600"
                                >
                                    {{ form.errors.customer_name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    :class="labelClass"
                                >
                                    رقم الهاتف
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.trim="form.customer_phone"
                                    type="tel"
                                    dir="ltr"
                                    maxlength="20"
                                    :class="`${inputClass} text-right`"
                                    placeholder="059XXXXXXX"
                                />

                                <p
                                    v-if="form.errors.customer_phone"
                                    class="mt-2 text-xs font-bold text-rose-600"
                                >
                                    {{ form.errors.customer_phone }}
                                </p>
                            </div>
                        </div>

                        <label
                            v-if="!form.customer_id"
                            class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800/50"
                        >
                            <input
                                v-model="form.save_customer"
                                type="checkbox"
                                class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                            />

                            <span>
                                <strong
                                    class="block text-sm text-slate-800 dark:text-slate-200"
                                >
                                    حفظ العميل في قائمة العملاء
                                </strong>

                                <span
                                    class="mt-1 block text-xs text-slate-500"
                                >
                                    لربط طلباته ومدفوعاته وكشف حسابه مستقبلًا.
                                </span>
                            </span>
                        </label>
                    </div>
                </section>

                <!-- Device -->
                <section
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="border-b border-slate-100 bg-gradient-to-l from-cyan-50/80 to-white px-5 py-5 dark:border-slate-800 dark:from-cyan-950/20 dark:to-slate-900"
                    >
                        <div
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-cyan-600 font-black text-white shadow-lg shadow-cyan-600/20"
                            >
                                2
                            </div>

                            <div>
                                <h2
                                    class="text-base font-black text-slate-950 dark:text-white"
                                >
                                    بيانات الجهاز عند الاستلام
                                </h2>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    استخدم الاختيارات الجاهزة لتسجيل الجهاز بسرعة.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="space-y-6 p-5 sm:p-6"
                    >
                        <div>
                            <p
                                class="mb-2 text-xs font-black text-slate-500"
                            >
                                اختيار سريع لنوع الجهاز
                            </p>

                            <div
                                class="flex flex-wrap gap-2"
                            >
                                <button
                                    v-for="type in quickDeviceTypes"
                                    :key="type"
                                    type="button"
                                    class="rounded-xl border px-3.5 py-2.5 text-xs font-black transition"
                                    :class="
                                        form.device_type === type
                                            ? 'border-cyan-600 bg-cyan-50 text-cyan-700 ring-2 ring-cyan-500/10 dark:bg-cyan-950/30 dark:text-cyan-300'
                                            : 'border-slate-200 text-slate-600 hover:border-cyan-300 hover:bg-cyan-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-cyan-950/20'
                                    "
                                    @click="form.device_type = type"
                                >
                                    {{ type }}
                                </button>
                            </div>
                        </div>

                        <div
                            class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                        >
                            <div>
                                <label :class="labelClass">
                                    نوع الجهاز
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.trim="form.device_type"
                                    :class="inputClass"
                                    placeholder="هاتف محمول"
                                />
                            </div>

                            <div>
                                <label :class="labelClass">
                                    العلامة التجارية
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.trim="form.brand"
                                    :class="inputClass"
                                    placeholder="Samsung"
                                />
                            </div>

                            <div>
                                <label :class="labelClass">
                                    الموديل
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.trim="form.model"
                                    :class="inputClass"
                                    placeholder="A54"
                                />
                            </div>

                            <div>
                                <label :class="labelClass">
                                    اللون
                                </label>

                                <input
                                    v-model.trim="form.color"
                                    :class="inputClass"
                                    placeholder="أسود"
                                />
                            </div>
                        </div>

                        <div>
                            <p
                                class="mb-2 text-xs font-black text-slate-500"
                            >
                                علامات تجارية سريعة
                            </p>

                            <div
                                class="flex flex-wrap gap-2"
                            >
                                <button
                                    v-for="brand in quickBrands"
                                    :key="brand"
                                    type="button"
                                    class="rounded-xl border px-3.5 py-2 text-xs font-black transition"
                                    :class="
                                        form.brand === brand
                                            ? 'border-cyan-600 bg-cyan-50 text-cyan-700 dark:bg-cyan-950/30 dark:text-cyan-300'
                                            : 'border-slate-200 text-slate-600 hover:border-cyan-300 dark:border-slate-700 dark:text-slate-300'
                                    "
                                    @click="form.brand = brand"
                                >
                                    {{ brand }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <div
                                class="mb-2 flex flex-wrap items-center justify-between gap-2"
                            >
                                <label :class="`${labelClass} mb-0`">
                                    وصف المشكلة
                                    <span class="text-rose-500">*</span>
                                </label>

                                <span
                                    class="text-[11px] font-bold text-slate-400"
                                >
                                    مشاكل شائعة — اضغط لإضافتها للوصف
                                </span>
                            </div>

                            <div
                                class="mb-3 flex flex-wrap gap-2"
                            >
                                <button
                                    v-for="problem in commonProblems"
                                    :key="problem"
                                    type="button"
                                    class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    :class="
                                        hasOption('problem_description', problem)
                                            ? 'border-blue-300 bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300'
                                            : ''
                                    "
                                    @click="appendOption('problem_description', problem)"
                                >
                                    {{ problem }}
                                </button>
                            </div>

                            <textarea
                                v-model.trim="form.problem_description"
                                rows="4"
                                maxlength="1000"
                                :class="`${inputClass} resize-none leading-7`"
                                placeholder="مثال: الجهاز وقع والشاشة انكسرت ولا يظهر شيء..."
                            />
                        </div>

                        <div
                            class="grid gap-5 xl:grid-cols-2"
                        >
                            <div>
                                <div
                                    class="mb-2 flex items-center justify-between gap-2"
                                >
                                    <label :class="`${labelClass} mb-0`">
                                        الحالة الخارجية عند الاستلام
                                    </label>

                                    <span
                                        class="text-[11px] text-slate-400"
                                    >
                                        اختياري
                                    </span>
                                </div>

                                <div
                                    class="mb-3 flex flex-wrap gap-2"
                                >
                                    <button
                                        v-for="condition in conditionOptions"
                                        :key="condition"
                                        type="button"
                                        class="rounded-xl border px-3 py-2 text-xs font-bold transition"
                                        :class="
                                            hasOption('device_condition', condition)
                                                ? 'border-amber-400 bg-amber-50 text-amber-800 dark:bg-amber-950/30 dark:text-amber-300'
                                                : 'border-slate-200 text-slate-600 hover:border-amber-300 dark:border-slate-700 dark:text-slate-300'
                                        "
                                        @click="appendOption('device_condition', condition)"
                                    >
                                        {{ condition }}
                                    </button>
                                </div>

                                <textarea
                                    v-model.trim="form.device_condition"
                                    rows="3"
                                    maxlength="500"
                                    :class="`${inputClass} resize-none leading-7`"
                                    placeholder="أي ملاحظة إضافية على حالة الجهاز..."
                                />
                            </div>

                            <div>
                                <div
                                    class="mb-2 flex items-center justify-between gap-2"
                                >
                                    <label :class="`${labelClass} mb-0`">
                                        الملحقات المستلمة مع الجهاز
                                    </label>

                                    <span
                                        class="text-[11px] text-slate-400"
                                    >
                                        اختياري
                                    </span>
                                </div>

                                <div
                                    class="mb-3 flex flex-wrap gap-2"
                                >
                                    <button
                                        v-for="accessory in accessoriesOptions"
                                        :key="accessory"
                                        type="button"
                                        class="rounded-xl border px-3 py-2 text-xs font-bold transition"
                                        :class="
                                            hasOption('received_accessories', accessory)
                                                ? 'border-emerald-400 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-300'
                                                : 'border-slate-200 text-slate-600 hover:border-emerald-300 dark:border-slate-700 dark:text-slate-300'
                                        "
                                        @click="appendOption('received_accessories', accessory)"
                                    >
                                        {{ accessory }}
                                    </button>
                                </div>

                                <textarea
                                    v-model.trim="form.received_accessories"
                                    rows="3"
                                    maxlength="500"
                                    :class="`${inputClass} resize-none leading-7`"
                                    placeholder="مثال: شاحن أصلي، غطاء أسود..."
                                />
                            </div>
                        </div>

                        <div
                            class="grid gap-5 lg:grid-cols-[.7fr_1.3fr]"
                        >
                            <div>
                                <label :class="labelClass">
                                    رمز قفل الجهاز
                                    <span class="font-medium text-slate-400">
                                        اختياري
                                    </span>
                                </label>

                                <input
                                    v-model.trim="form.lock_code"
                                    type="text"
                                    maxlength="100"
                                    autocomplete="off"
                                    :class="inputClass"
                                    placeholder="PIN / Pattern / Password"
                                />

                                <p
                                    class="mt-2 text-xs leading-5 text-slate-400"
                                >
                                    يتم حفظه بشكل مشفّر ويُمسح عند تسليم الجهاز.
                                </p>
                            </div>

                            <div>
                                <div
                                    class="mb-2 flex items-center justify-between gap-2"
                                >
                                    <label :class="`${labelClass} mb-0`">
                                        صور الجهاز عند الاستلام
                                        <span class="font-medium text-slate-400">
                                            اختياري
                                        </span>
                                    </label>

                                    <span
                                        class="text-[11px] text-slate-400"
                                    >
                                        حتى 8 صور — 2MB للصورة
                                    </span>
                                </div>

                                <input
                                    ref="imageInput"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    class="hidden"
                                    @change="addImages"
                                />

                                <button
                                    type="button"
                                    class="flex min-h-32 w-full flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 px-5 py-5 text-center transition hover:border-blue-400 hover:bg-blue-50/50 dark:border-slate-700 dark:bg-slate-800/40 dark:hover:border-blue-700"
                                    @click="triggerImagePicker"
                                >
                                    <span
                                        class="flex h-11 w-11 items-center justify-center rounded-2xl bg-blue-100 text-xl text-blue-700 dark:bg-blue-950/40 dark:text-blue-300"
                                    >
                                        ＋
                                    </span>

                                    <strong
                                        class="mt-3 text-sm text-slate-800 dark:text-slate-200"
                                    >
                                        إضافة صور الجهاز
                                    </strong>

                                    <span
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        لتوثيق حالة الجهاز قبل الصيانة
                                    </span>
                                </button>

                                <p
                                    v-if="uploadError"
                                    class="mt-2 text-xs font-bold text-rose-600"
                                >
                                    {{ uploadError }}
                                </p>

                                <p
                                    v-if="form.errors.attachments"
                                    class="mt-2 text-xs font-bold text-rose-600"
                                >
                                    {{ form.errors.attachments }}
                                </p>

                                <div
                                    v-if="imagePreviews.length"
                                    class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-4"
                                >
                                    <div
                                        v-for="(preview, index) in imagePreviews"
                                        :key="preview.url"
                                        class="group relative aspect-square overflow-hidden rounded-xl border border-slate-200 bg-slate-100 dark:border-slate-700"
                                    >
                                        <img
                                            :src="preview.url"
                                            alt=""
                                            class="h-full w-full object-cover"
                                        />

                                        <button
                                            type="button"
                                            class="absolute left-1.5 top-1.5 flex h-7 w-7 items-center justify-center rounded-lg bg-slate-950/75 text-xs font-black text-white opacity-100 backdrop-blur transition sm:opacity-0 sm:group-hover:opacity-100"
                                            @click="removeImage(index)"
                                        >
                                            ×
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Diagnosis -->
                <section
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="border-b border-slate-100 bg-gradient-to-l from-violet-50/80 to-white px-5 py-5 dark:border-slate-800 dark:from-violet-950/20 dark:to-slate-900"
                    >
                        <div
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-violet-600 font-black text-white shadow-lg shadow-violet-600/20"
                            >
                                3
                            </div>

                            <div>
                                <h2
                                    class="text-base font-black text-slate-950 dark:text-white"
                                >
                                    التشخيص والاتفاق
                                </h2>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    ما المشكلة؟ ما الإصلاح؟ وكم اتفقت مع العميل؟
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="space-y-5 p-5 sm:p-6"
                    >
                        <div
                            class="grid gap-5 lg:grid-cols-2"
                        >
                            <div>
                                <label :class="labelClass">
                                    نتيجة الفحص / التشخيص
                                    <span class="text-rose-500">*</span>
                                </label>

                                <textarea
                                    v-model.trim="form.inspection_result"
                                    rows="5"
                                    maxlength="1000"
                                    :class="`${inputClass} resize-none leading-7`"
                                    placeholder="مثال: الشاشة الداخلية متضررة وتحتاج استبدال..."
                                />
                            </div>

                            <div>
                                <label :class="labelClass">
                                    الإصلاح المطلوب / الذي سيتم تنفيذه
                                    <span class="text-rose-500">*</span>
                                </label>

                                <textarea
                                    v-model.trim="form.repair_action"
                                    rows="5"
                                    maxlength="1000"
                                    :class="`${inputClass} resize-none leading-7`"
                                    placeholder="مثال: تغيير شاشة أصلية وتجربة اللمس..."
                                />
                            </div>
                        </div>

                        <div
                            class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                        >
                            <div>
                                <label :class="labelClass">
                                    سبب العطل
                                    <span class="font-medium text-slate-400">
                                        اختياري
                                    </span>
                                </label>

                                <input
                                    v-model.trim="form.fault_cause"
                                    :class="inputClass"
                                    placeholder="سقوط / ماء / تلف..."
                                />
                            </div>

                            <div>
                                <label :class="labelClass">
                                    الفني
                                    <span class="font-medium text-slate-400">
                                        اختياري
                                    </span>
                                </label>

                                <input
                                    v-model.trim="form.technician_name"
                                    :class="inputClass"
                                    placeholder="اسم الفني"
                                />
                            </div>

                            <div>
                                <label :class="labelClass">
                                    السعر المتفق عليه
                                    <span class="text-rose-500">*</span>
                                </label>

                                <div
                                    class="relative"
                                >
                                    <input
                                        v-model.number="form.agreed_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        :class="`${inputClass} pl-16 font-black`"
                                    />

                                    <span
                                        class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400"
                                    >
                                        شيكل
                                    </span>
                                </div>
                            </div>

                            <div>
                                <label :class="labelClass">
                                    التاريخ المتوقع للجاهزية
                                </label>

                                <input
                                    v-model="form.expected_delivery_date"
                                    type="date"
                                    :min="todayLocal()"
                                    :class="inputClass"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Parts -->
                <section
                    class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="border-b border-slate-100 bg-gradient-to-l from-amber-50/80 to-white px-5 py-5 dark:border-slate-800 dark:from-amber-950/20 dark:to-slate-900"
                    >
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div
                                class="flex items-center gap-3"
                            >
                                <div
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-amber-500 font-black text-white shadow-lg shadow-amber-500/20"
                                >
                                    4
                                </div>

                                <div>
                                    <h2
                                        class="text-base font-black text-slate-950 dark:text-white"
                                    >
                                        قطع الغيار
                                    </h2>

                                    <p
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        اختر مصدر القطعة: من مخزن المحل أو شراء خارجي.
                                    </p>
                                </div>
                            </div>

                            <div
                                class="flex rounded-2xl bg-slate-100 p-1 dark:bg-slate-800"
                            >
                                <button
                                    type="button"
                                    class="rounded-xl px-4 py-2.5 text-xs font-black transition"
                                    :class="
                                        partSource === 'stock'
                                            ? 'bg-white text-blue-700 shadow-sm dark:bg-slate-700 dark:text-blue-300'
                                            : 'text-slate-500 dark:text-slate-400'
                                    "
                                    @click="partSource = 'stock'"
                                >
                                    من المخزن
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl px-4 py-2.5 text-xs font-black transition"
                                    :class="
                                        partSource === 'external'
                                            ? 'bg-white text-violet-700 shadow-sm dark:bg-slate-700 dark:text-violet-300'
                                            : 'text-slate-500 dark:text-slate-400'
                                    "
                                    @click="partSource = 'external'"
                                >
                                    شراء خارجي
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        class="space-y-5 p-5 sm:p-6"
                    >
                        <!-- Stock source -->
                        <div
                            v-if="partSource === 'stock'"
                            class="rounded-2xl border border-blue-100 bg-blue-50/40 p-4 sm:p-5 dark:border-blue-900/40 dark:bg-blue-950/10"
                        >
                            <div
                                class="mb-4 flex flex-wrap items-center justify-between gap-2"
                            >
                                <div>
                                    <h3
                                        class="text-sm font-black text-slate-900 dark:text-white"
                                    >
                                        قطعة موجودة في مخزن الصيانة
                                    </h3>

                                    <p
                                        class="mt-1 text-xs text-slate-500"
                                    >
                                        سيتم خصم الكمية مباشرة عند إنشاء الطلب.
                                    </p>
                                </div>

                                <span
                                    class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-500 shadow-sm dark:bg-slate-900"
                                >
                                    {{ maintenanceWarehouse?.name || 'مخزن الصيانة' }}
                                </span>
                            </div>

                            <div
                                class="grid gap-4 xl:grid-cols-[1fr_1.5fr_120px_150px_auto]"
                            >
                                <input
                                    v-model.trim="productSearch"
                                    type="search"
                                    :class="inputClass"
                                    placeholder="بحث عن قطعة..."
                                />

                                <select
                                    v-model="stockDraft.product_id"
                                    :class="inputClass"
                                >
                                    <option value="">
                                        اختر القطعة
                                    </option>

                                    <option
                                        v-for="product in filteredProducts"
                                        :key="product.id"
                                        :value="product.id"
                                    >
                                        {{ product.name }}
                                        — متوفر {{ product.available_quantity }}
                                    </option>
                                </select>

                                <input
                                    v-model.number="stockDraft.quantity"
                                    type="number"
                                    min="1"
                                    :max="selectedStockProduct?.available_quantity || 9999"
                                    :class="inputClass"
                                    placeholder="الكمية"
                                />

                                <input
                                    v-model.number="stockDraft.unit_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :class="inputClass"
                                    placeholder="السعر"
                                />

                                <button
                                    type="button"
                                    :disabled="!selectedStockProduct"
                                    class="rounded-2xl bg-blue-600 px-5 py-3.5 text-sm font-black text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                                    @click="addStockPart"
                                >
                                    إضافة
                                </button>
                            </div>

                            <div
                                v-if="selectedStockProduct"
                                class="mt-3 flex flex-wrap gap-2 text-xs"
                            >
                                <span
                                    class="rounded-full bg-emerald-100 px-3 py-1 font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
                                >
                                    المتوفر:
                                    {{ selectedStockProduct.available_quantity }}
                                </span>

                                <span
                                    class="rounded-full bg-white px-3 py-1 font-bold text-slate-500 dark:bg-slate-900"
                                >
                                    سعر البيع:
                                    {{ money(selectedStockProduct.selling_price) }}
                                </span>
                            </div>
                        </div>

                        <!-- External source -->
                        <div
                            v-else
                            class="rounded-2xl border border-violet-100 bg-violet-50/40 p-4 sm:p-5 dark:border-violet-900/40 dark:bg-violet-950/10"
                        >
                            <div
                                class="mb-4"
                            >
                                <h3
                                    class="text-sm font-black text-slate-900 dark:text-white"
                                >
                                    قطعة من خارج المحل
                                </h3>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    يمكنك حفظها كمسودة الآن أو تسجيل الشراء مباشرة.
                                </p>
                            </div>

                            <div
                                class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                            >
                                <div
                                    class="xl:col-span-2"
                                >
                                    <label :class="labelClass">
                                        اسم القطعة
                                    </label>

                                    <input
                                        v-model.trim="externalDraft.part_name"
                                        :class="inputClass"
                                        placeholder="مثال: شاشة Samsung A54"
                                    />
                                </div>

                                <div>
                                    <label :class="labelClass">
                                        الكمية
                                    </label>

                                    <input
                                        v-model.number="externalDraft.quantity"
                                        type="number"
                                        min="1"
                                        :class="inputClass"
                                    />
                                </div>

                                <div>
                                    <label :class="labelClass">
                                        حالة الشراء
                                    </label>

                                    <select
                                        v-model="externalDraft.purchase_mode"
                                        :class="inputClass"
                                    >
                                        <option value="draft">
                                            سأشتريها لاحقًا — مسودة
                                        </option>

                                        <option value="purchased">
                                            تم شراؤها الآن
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div
                                v-if="externalDraft.purchase_mode === 'purchased'"
                                class="mt-5 rounded-2xl border border-violet-200 bg-white p-4 dark:border-violet-900/50 dark:bg-slate-900"
                            >
                                <div
                                    class="mb-4 flex items-center gap-2"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full bg-violet-500"
                                    />

                                    <strong
                                        class="text-sm text-slate-900 dark:text-white"
                                    >
                                        تفاصيل شراء القطعة
                                    </strong>
                                </div>

                                <div
                                    class="grid gap-4 md:grid-cols-2 xl:grid-cols-3"
                                >
                                    <div>
                                        <label :class="labelClass">
                                            مورد مسجل
                                        </label>

                                        <select
                                            v-model="externalDraft.supplier_id"
                                            :class="inputClass"
                                        >
                                            <option value="">
                                                بدون مورد مسجل
                                            </option>

                                            <option
                                                v-for="supplier in suppliers"
                                                :key="supplier.id"
                                                :value="supplier.id"
                                            >
                                                {{ supplier.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label :class="labelClass">
                                            أو اسم المحل
                                        </label>

                                        <input
                                            v-model.trim="externalDraft.purchase_from"
                                            :class="inputClass"
                                            placeholder="اسم المحل الخارجي"
                                        />
                                    </div>

                                    <div>
                                        <label :class="labelClass">
                                            سعر شراء الوحدة
                                        </label>

                                        <input
                                            v-model.number="externalDraft.unit_purchase_price"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            :class="inputClass"
                                        />
                                    </div>

                                    <div>
                                        <label :class="labelClass">
                                            الحساب المالي
                                        </label>

                                        <select
                                            v-model="externalDraft.financial_account_id"
                                            :class="inputClass"
                                        >
                                            <option value="">
                                                اختر الحساب
                                            </option>

                                            <option
                                                v-for="account in financialAccounts"
                                                :key="account.id"
                                                :value="account.id"
                                            >
                                                {{ account.name }}
                                                — {{ money(account.current_balance) }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <label :class="labelClass">
                                            رقم / مرجع الفاتورة
                                        </label>

                                        <input
                                            v-model.trim="externalDraft.purchase_reference"
                                            :class="inputClass"
                                            placeholder="اختياري"
                                        />
                                    </div>

                                    <div>
                                        <label :class="labelClass">
                                            تاريخ الشراء
                                        </label>

                                        <input
                                            v-model="externalDraft.purchased_at"
                                            type="datetime-local"
                                            :max="nowLocal()"
                                            :class="inputClass"
                                        />
                                    </div>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="mt-4 rounded-2xl bg-violet-600 px-5 py-3.5 text-sm font-black text-white shadow-sm transition hover:bg-violet-700"
                                @click="addExternalPart"
                            >
                                {{
                                    externalDraft.purchase_mode === 'purchased'
                                        ? 'إضافة القطعة وتسجيل الشراء'
                                        : 'إضافة كقطعة بانتظار الشراء'
                                }}
                            </button>
                        </div>

                        <!-- Added parts -->
                        <div
                            v-if="totalPartsCount"
                            class="space-y-3"
                        >
                            <div
                                class="flex items-center justify-between"
                            >
                                <h3
                                    class="text-sm font-black text-slate-900 dark:text-white"
                                >
                                    القطع المضافة للطلب
                                </h3>

                                <span
                                    class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    {{ totalPartsCount }} قطعة
                                </span>
                            </div>

                            <article
                                v-for="(part, index) in form.stock_parts"
                                :key="`stock-${part.product_id}`"
                                class="flex flex-col gap-3 rounded-2xl border border-blue-100 bg-blue-50/50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-blue-900/40 dark:bg-blue-950/10"
                            >
                                <div
                                    class="flex items-start gap-3"
                                >
                                    <span
                                        class="rounded-lg bg-blue-600 px-2 py-1 text-[10px] font-black text-white"
                                    >
                                        مخزن
                                    </span>

                                    <div>
                                        <strong
                                            class="block text-sm text-slate-950 dark:text-white"
                                        >
                                            {{ part.product_name }}
                                        </strong>

                                        <p
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            الكمية {{ part.quantity }}
                                            · سعر الوحدة {{ money(part.unit_price) }}
                                            · المتوفر قبل الخصم {{ part.available_quantity }}
                                        </p>
                                    </div>
                                </div>

                                <div
                                    class="flex items-center gap-3"
                                >
                                    <strong
                                        class="text-sm text-blue-700 dark:text-blue-300"
                                    >
                                        {{ money(Number(part.unit_price || 0) * Number(part.quantity || 0)) }}
                                    </strong>

                                    <button
                                        type="button"
                                        class="rounded-lg px-2.5 py-2 text-xs font-black text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20"
                                        @click="removeStockPart(index)"
                                    >
                                        حذف
                                    </button>
                                </div>
                            </article>

                            <article
                                v-for="(part, index) in form.external_parts"
                                :key="`external-${index}`"
                                class="flex flex-col gap-3 rounded-2xl border border-violet-100 bg-violet-50/50 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-violet-900/40 dark:bg-violet-950/10"
                            >
                                <div
                                    class="flex items-start gap-3"
                                >
                                    <span
                                        class="rounded-lg bg-violet-600 px-2 py-1 text-[10px] font-black text-white"
                                    >
                                        خارجي
                                    </span>

                                    <div>
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <strong
                                                class="text-sm text-slate-950 dark:text-white"
                                            >
                                                {{ part.part_name }}
                                            </strong>

                                            <span
                                                class="rounded-full px-2 py-0.5 text-[10px] font-black"
                                                :class="
                                                    part.purchase_mode === 'purchased'
                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300'
                                                "
                                            >
                                                {{
                                                    part.purchase_mode === 'purchased'
                                                        ? 'تم الشراء'
                                                        : 'بانتظار الشراء'
                                                }}
                                            </span>
                                        </div>

                                        <p
                                            class="mt-1 text-xs text-slate-500"
                                        >
                                            الكمية {{ part.quantity }}

                                            <template
                                                v-if="part.purchase_mode === 'purchased'"
                                            >
                                                · تكلفة الوحدة {{ money(part.unit_purchase_price) }}
                                            </template>
                                        </p>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    class="self-start rounded-lg px-2.5 py-2 text-xs font-black text-rose-600 hover:bg-rose-50 sm:self-auto dark:hover:bg-rose-950/20"
                                    @click="removeExternalPart(index)"
                                >
                                    حذف
                                </button>
                            </article>
                        </div>

                        <div
                            v-else
                            class="rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/60 px-6 py-9 text-center dark:border-slate-700 dark:bg-slate-800/30"
                        >
                            <strong
                                class="block text-sm text-slate-700 dark:text-slate-200"
                            >
                                لا توجد قطع غيار مضافة
                            </strong>

                            <span
                                class="mt-1 block text-xs text-slate-500"
                            >
                                إذا كانت الصيانة لا تحتاج قطعة، اترك القسم فارغًا.
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Notes -->
                <section
                    class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-900"
                >
                    <div
                        class="mb-4"
                    >
                        <h2
                            class="text-base font-black text-slate-950 dark:text-white"
                        >
                            ملاحظات إضافية
                        </h2>

                        <p
                            class="mt-1 text-xs text-slate-500"
                        >
                            اختيارية ولا تؤثر على إنشاء الطلب.
                        </p>
                    </div>

                    <div
                        class="grid gap-4 lg:grid-cols-2"
                    >
                        <div>
                            <label :class="labelClass">
                                ملاحظات للعميل
                            </label>

                            <textarea
                                v-model.trim="form.customer_notes"
                                rows="3"
                                maxlength="500"
                                :class="`${inputClass} resize-none leading-7`"
                            />
                        </div>

                        <div>
                            <label :class="labelClass">
                                ملاحظات داخلية
                            </label>

                            <textarea
                                v-model.trim="form.internal_notes"
                                rows="3"
                                maxlength="500"
                                :class="`${inputClass} resize-none leading-7`"
                            />
                        </div>
                    </div>
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
                        <div
                            class="flex items-center justify-between gap-3"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-300"
                                >
                                    REPAIR SUMMARY
                                </p>

                                <h2
                                    class="mt-1 text-lg font-black"
                                >
                                    ملخص الطلب
                                </h2>
                            </div>

                            <div
                                class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-xl"
                            >
                                🔧
                            </div>
                        </div>

                        <div
                            class="mt-5 rounded-2xl bg-white/5 p-4"
                        >
                            <p
                                class="text-xs text-slate-400"
                            >
                                السعر المتفق عليه
                            </p>

                            <strong
                                dir="ltr"
                                class="mt-2 block text-right text-3xl font-black"
                            >
                                {{ money(form.agreed_price) }}
                            </strong>
                        </div>

                        <div
                            class="mt-4 space-y-3 text-sm"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-slate-400"
                                >
                                    تكلفة قطع المخزن
                                </span>

                                <strong>
                                    {{ money(stockCost) }}
                                </strong>
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-slate-400"
                                >
                                    قطع خارجية مشتراة
                                </span>

                                <strong>
                                    {{ money(externalPurchasedCost) }}
                                </strong>
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-slate-400"
                                >
                                    التكلفة المعروفة
                                </span>

                                <strong>
                                    {{ money(knownPartsCost) }}
                                </strong>
                            </div>

                            <div
                                class="border-t border-white/10 pt-3"
                            >
                                <div
                                    class="flex items-center justify-between gap-3"
                                >
                                    <span
                                        class="font-black"
                                    >
                                        الربح المتوقع
                                    </span>

                                    <strong
                                        class="text-lg"
                                        :class="
                                            estimatedProfit >= 0
                                                ? 'text-emerald-300'
                                                : 'text-rose-300'
                                        "
                                    >
                                        {{ money(estimatedProfit) }}
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    v-if="pendingExternalCount"
                    class="rounded-3xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/20"
                >
                    <strong
                        class="block text-sm text-amber-900 dark:text-amber-200"
                    >
                        {{ pendingExternalCount }} قطعة بانتظار الشراء
                    </strong>

                    <p
                        class="mt-1 text-xs leading-5 text-amber-700 dark:text-amber-300"
                    >
                        يمكنك إنشاء الطلب الآن، ولن يصبح الجهاز جاهزًا حتى يتم تسجيل شراء هذه القطع.
                    </p>
                </section>

                <section
                    v-if="Object.keys(form.errors).length"
                    class="rounded-3xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20"
                >
                    <strong
                        class="block text-sm text-rose-800 dark:text-rose-200"
                    >
                        راجع البيانات
                    </strong>

                    <p
                        class="mt-1 text-xs leading-6 text-rose-700 dark:text-rose-300"
                    >
                        {{ Object.values(form.errors)[0] }}
                    </p>
                </section>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span
                        v-if="form.processing"
                        class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                    />

                    {{
                        form.processing
                            ? 'جاري إنشاء الطلب...'
                            : 'إنشاء طلب الصيانة'
                    }}
                </button>

                <Link
                    :href="route('repairs.index')"
                    class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                >
                    إلغاء
                </Link>

                <p
                    class="px-3 text-center text-[10px] leading-5 text-slate-400"
                >
                    عند الإنشاء سيتم خصم قطع المخزن فورًا، وتسجيل أي شراء خارجي تم إدخاله.
                </p>
            </aside>
        </form>
    </AuthenticatedLayout>
</template>
