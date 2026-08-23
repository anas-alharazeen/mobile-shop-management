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
    customers: {
        type: Array,
        default: () => [],
    },

    products: {
        type: Array,
        default: () => [],
    },

    financialAccounts: {
        type: Array,
        default: () => [],
    },

    paymentMethods: {
        type: Object,
        default: () => ({}),
    },
});

const nowLocal = () => {
    const date = new Date();
    const offset = date.getTimezoneOffset();

    return new Date(
        date.getTime()
        - offset * 60000
    )
        .toISOString()
        .slice(0, 16);
};

const form = useForm({
    customer_id: '',
    customer_name: '',
    customer_phone: '',
    save_customer: true,

    device_type: '',
    brand: '',
    model: '',
    color: '',
    problem_description: '',
    device_condition: '',

    inspection_result: '',
    fault_cause: '',
    repair_action: '',
    technician_name: '',

    inspection_fee: 0,
    labor_cost: 0,

    parts: [],

    payment_amount: 0,
    payment_method: 'cash',
    financial_account_id: '',
    bank_or_app_name: '',
    transaction_reference: '',
    paid_at: nowLocal(),

    finish_status: 'delivered',
    allow_partial_payment: false,
    received_at: nowLocal(),

    customer_notes: '',
    internal_notes: '',
});

const customerSearch = ref('');
const customerDropdownOpen = ref(false);
const productToAdd = ref('');
const productSearch = ref('');
const pageError = ref('');

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
];

const quickProblems = [
    'الجهاز لا يشحن',
    'الشاشة لا تعمل',
    'مشكلة في البطارية',
    'مشكلة في الصوت',
    'مشكلة في الكاميرا',
    'الجهاز لا يعمل',
    'تنظيف الجهاز',
    'مشكلة برمجية',
];

const normalize = (value) => String(value ?? '')
    .trim()
    .toLowerCase();

const selectedCustomer = computed(() => (
    props.customers.find(
        (customer) =>
            Number(customer.id)
            === Number(form.customer_id)
    ) || null
));

const filteredCustomers = computed(() => {
    const search = normalize(
        customerSearch.value
    );

    if (!search) {
        return props.customers.slice(0, 8);
    }

    return props.customers
        .filter(
            (customer) => [
                customer.name,
                customer.phone,
                customer.code,
            ]
                .filter(Boolean)
                .some(
                    (value) =>
                        normalize(value)
                            .includes(search)
                )
        )
        .slice(0, 10);
});

const filteredProducts = computed(() => {
    const search =
        normalize(
            productSearch.value
        );

    return props.products
        .filter(
            (product) =>
                Number(
                    product.available_quantity
                    || 0
                ) > 0
        )
        .filter(
            (product) => {
                if (!search) {
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
                        (value) =>
                            normalize(value)
                                .includes(search)
                    );
            }
        );
});

const expectedAccountType = (method) => ({
    cash: 'cash',
    bank_transfer: 'bank',
    banking_app: 'banking_app',
}[method] || null);

const compatibleAccounts = computed(() => (
    props.financialAccounts.filter(
        (account) =>
            (
                account.type?.value
                ?? account.type
            )
            === expectedAccountType(
                form.payment_method
            )
    )
));

const selectedAccount = computed(() => (
    props.financialAccounts.find(
        (account) =>
            Number(account.id)
            === Number(
                form.financial_account_id
            )
    ) || null
));

const isElectronic = computed(() => (
    ['bank_transfer', 'banking_app']
        .includes(
            form.payment_method
        )
));

const partsTotal = computed(() => (
    form.parts.reduce(
        (sum, part) =>
            sum
            + (
                Number(part.quantity || 0)
                * Number(part.unit_price || 0)
            ),
        0
    )
));

const totalAmount = computed(() => (
    Math.max(
        0,
        Number(
            form.inspection_fee
            || 0
        )
        + Number(
            form.labor_cost
            || 0
        )
        + partsTotal.value
    )
));

const paymentAmount = computed(() => (
    Math.max(
        0,
        Number(
            form.payment_amount
            || 0
        )
    )
));

const remainingAmount = computed(() => (
    Math.max(
        0,
        totalAmount.value
        - paymentAmount.value
    )
));

const paymentProgress = computed(() => {
    if (totalAmount.value <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round(
            (
                paymentAmount.value
                / totalAmount.value
            )
            * 100
        )
    );
});

const projectedAccountBalance = computed(() => (
    Number(
        selectedAccount.value
            ?.current_balance
        || 0
    )
    + paymentAmount.value
));

const requiredProgress = computed(() => {
    const fields = [
        form.customer_name,
        form.customer_phone,
        form.device_type,
        form.brand,
        form.model,
        form.problem_description,
        form.inspection_result,
        form.repair_action,
    ];

    const completed =
        fields.filter(
            (value) =>
                String(
                    value
                    ?? ''
                ).trim() !== ''
        ).length;

    return Math.round(
        (
            completed
            / fields.length
        )
        * 100
    );
});

const fieldError = (field) =>
    form.errors[field]
    || '';

const formError = computed(() => {
    if (
        !String(
            form.customer_name
            || ''
        ).trim()
    ) {
        return 'أدخل اسم العميل.';
    }

    if (
        !String(
            form.customer_phone
            || ''
        ).trim()
    ) {
        return 'أدخل رقم هاتف العميل.';
    }

    if (
        !String(
            form.device_type
            || ''
        ).trim()
    ) {
        return 'حدد نوع الجهاز.';
    }

    if (
        !String(
            form.brand
            || ''
        ).trim()
    ) {
        return 'أدخل العلامة التجارية.';
    }

    if (
        !String(
            form.model
            || ''
        ).trim()
    ) {
        return 'أدخل موديل الجهاز.';
    }

    if (
        !String(
            form.problem_description
            || ''
        ).trim()
    ) {
        return 'اكتب وصف المشكلة.';
    }

    if (
        !String(
            form.inspection_result
            || ''
        ).trim()
    ) {
        return 'أدخل نتيجة الفحص والتشخيص.';
    }

    if (
        !String(
            form.repair_action
            || ''
        ).trim()
    ) {
        return 'أدخل الصيانة التي تم تنفيذها.';
    }

    const invalidPart =
        form.parts.find(
            (part) =>
                Number(
                    part.quantity
                    || 0
                ) <= 0
                || Number(
                    part.quantity
                    || 0
                )
                > Number(
                    part.available_quantity
                    || 0
                )
                || Number(
                    part.unit_price
                    || 0
                ) < 0
        );

    if (invalidPart) {
        return `راجع كمية وسعر القطعة: ${invalidPart.product_name}.`;
    }

    if (
        paymentAmount.value
        > totalAmount.value
        + 0.00001
    ) {
        return 'المبلغ المدفوع أكبر من إجمالي الصيانة.';
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
        return 'اختر الحساب المالي.';
    }

    if (
        form.finish_status === 'delivered'
        && remainingAmount.value > 0.00001
        && !form.allow_partial_payment
    ) {
        return 'يوجد مبلغ متبقٍ. أكمل الدفع أو فعّل السماح بالتسليم مع بقاء الدين.';
    }

    return '';
});

const selectCustomer = (customer) => {
    form.customer_id =
        customer.id;

    form.customer_name =
        customer.name
        || '';

    form.customer_phone =
        customer.phone
        || '';

    form.save_customer =
        false;

    customerSearch.value =
        customer.name
        || '';

    customerDropdownOpen.value =
        false;
};

const useNewCustomer = () => {
    form.customer_id = '';
    form.customer_name = '';
    form.customer_phone = '';
    form.save_customer = true;
    customerSearch.value = '';
    customerDropdownOpen.value = false;
};

const unlinkCustomer = () => {
    form.customer_id = '';
    form.save_customer = false;
    customerSearch.value = '';
};

const chooseDeviceType = (value) => {
    form.device_type = value;
};

const chooseBrand = (value) => {
    form.brand = value;
};

const chooseProblem = (value) => {
    const current =
        String(
            form.problem_description
            || ''
        ).trim();

    if (
        current.includes(value)
    ) {
        return;
    }

    form.problem_description =
        current
            ? `${current}، ${value}`
            : value;
};

watch(
    () => form.customer_id,
    () => {
        if (
            selectedCustomer.value
        ) {
            form.customer_name =
                selectedCustomer.value
                    .name
                || '';

            form.customer_phone =
                selectedCustomer.value
                    .phone
                || '';
        }
    }
);

watch(
    () => form.payment_method,
    (method) => {
        const accounts =
            props.financialAccounts
                .filter(
                    (account) =>
                        (
                            account.type?.value
                            ?? account.type
                        )
                        === expectedAccountType(
                            method
                        )
                );

        const selectedValid =
            accounts.some(
                (account) =>
                    Number(account.id)
                    === Number(
                        form.financial_account_id
                    )
            );

        if (!selectedValid) {
            form.financial_account_id =
                accounts[0]?.id
                || '';
        }

        if (
            method === 'cash'
        ) {
            form.bank_or_app_name =
                '';

            form.transaction_reference =
                '';
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
            isElectronic.value
            && selectedAccount.value
        ) {
            form.bank_or_app_name =
                selectedAccount.value
                    .name;
        }
    }
);

watch(
    totalAmount,
    (total) => {
        if (
            Number(
                form.payment_amount
                || 0
            ) > total
        ) {
            form.payment_amount =
                total;
        }
    }
);

const addPart = () => {
    const product =
        props.products.find(
            (item) =>
                Number(item.id)
                === Number(
                    productToAdd.value
                )
        );

    if (!product) {
        return;
    }

    if (
        form.parts.some(
            (part) =>
                Number(part.product_id)
                === Number(product.id)
        )
    ) {
        productToAdd.value = '';
        return;
    }

    form.parts.push({
        product_id:
            product.id,

        product_name:
            product.name,

        product_code:
            product.code,

        quantity:
            1,

        unit_price:
            Number(
                product.selling_price
                || 0
            ),

        available_quantity:
            Number(
                product.available_quantity
                || 0
            ),
    });

    productToAdd.value = '';
};

const removePart = (index) => {
    form.parts.splice(
        index,
        1
    );
};

const setPaymentAmount = (type) => {
    if (type === 'none') {
        form.payment_amount = 0;
        return;
    }

    if (type === 'half') {
        form.payment_amount =
            Number(
                (
                    totalAmount.value
                    / 2
                ).toFixed(2)
            );
        return;
    }

    if (type === 'full') {
        form.payment_amount =
            Number(
                totalAmount.value
                    .toFixed(2)
            );
    }
};

const submit = () => {
    pageError.value =
        formError.value;

    if (
        pageError.value
    ) {
        window.scrollTo({
            top: 0,
            behavior: 'smooth',
        });

        return;
    }

    form.transform(
        (data) => ({
            ...data,

            inspection_fee:
                Number(
                    data.inspection_fee
                    || 0
                ),

            labor_cost:
                Number(
                    data.labor_cost
                    || 0
                ),

            payment_amount:
                Number(
                    data.payment_amount
                    || 0
                ),

            parts:
                data.parts.map(
                    (part) => ({
                        product_id:
                            Number(
                                part.product_id
                            ),

                        quantity:
                            Number(
                                part.quantity
                            ),

                        unit_price:
                            Number(
                                part.unit_price
                            ),
                    })
                ),
        })
    ).post(
        route(
            'repairs.quick-store'
        ),
        {
            preserveScroll: true,

            onError: () => {
                pageError.value =
                    'تعذر حفظ الصيانة السريعة. راجع الحقول المحددة.';
            },
        }
    );
};

const money = (value) => (
    `${Number(value || 0).toLocaleString(
        'ar-PS',
        {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }
    )} شيكل`
);
</script>


<template>
    <Head title="صيانة سريعة" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                        <span>الصيانة</span>
                        <span class="text-slate-300 dark:text-slate-600">/</span>
                        <span>صيانة سريعة</span>
                    </div>

                    <h1 class="mt-2 text-2xl font-black text-slate-900 dark:text-white sm:text-3xl">
                        تسجيل طلب صيانة سريع
                    </h1>

                    <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                        سجّل العميل والجهاز، التشخيص والإصلاح، ثم أضف قطع الغيار والدفع وأنهِ الطلب مباشرة.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <div class="hidden min-w-48 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-slate-700 dark:bg-slate-800 sm:block">
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-500 dark:text-slate-400">
                                اكتمال البيانات
                            </span>

                            <span class="font-black text-indigo-600 dark:text-indigo-400">
                                {{ requiredProgress }}٪
                            </span>
                        </div>

                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                            <div
                                class="h-full rounded-full bg-gradient-to-l from-indigo-600 to-cyan-500 transition-all duration-300"
                                :style="{ width: `${requiredProgress}%` }"
                            ></div>
                        </div>
                    </div>

                    <Link
                        :href="route('repairs.index')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>

                        العودة إلى الصيانة
                    </Link>
                </div>
            </div>
        </template>

        <form class="space-y-6" @submit.prevent="submit">
            <div
                v-if="pageError"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m9-1.5a9 9 0 11-18 0 9 9 0 0118 0zM12 16.5h.008v.008H12V16.5z" />
                </svg>

                <span>{{ pageError }}</span>
            </div>

            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
                <div class="min-w-0 space-y-6">
                    <!-- Customer -->
                    <section class="overflow-visible rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 border-b border-slate-200 bg-gradient-to-l from-indigo-50 to-white px-5 py-5 dark:border-slate-700 dark:from-indigo-950/40 dark:to-slate-800 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0" />
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="font-black text-slate-900 dark:text-white">
                                        بيانات العميل
                                    </h2>

                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        ابحث عن عميل موجود أو سجّل عميلاً جديداً.
                                    </p>
                                </div>
                            </div>

                            <button
                                type="button"
                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-indigo-200 bg-white px-3 py-2 text-xs font-bold text-indigo-700 transition hover:bg-indigo-50 dark:border-indigo-800 dark:bg-slate-800 dark:text-indigo-300 dark:hover:bg-indigo-900/20"
                                @click="useNewCustomer"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4" />
                                </svg>

                                عميل جديد
                            </button>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <div class="relative z-30">
                                <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                    البحث في العملاء
                                </label>

                                <div class="relative">
                                    <svg class="pointer-events-none absolute right-3 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" />
                                    </svg>

                                    <input
                                        v-model="customerSearch"
                                        type="search"
                                        autocomplete="off"
                                        placeholder="ابحث بالاسم أو الهاتف أو الكود..."
                                        class="block w-full rounded-xl border-slate-300 bg-white py-3 pr-10 pl-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
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
                                            class="flex w-full items-center justify-between gap-3 rounded-xl px-3 py-3 text-right transition hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                                            @mousedown.prevent="selectCustomer(customer)"
                                        >
                                            <div>
                                                <strong class="block text-sm text-slate-900 dark:text-white">
                                                    {{ customer.name }}
                                                </strong>

                                                <span dir="ltr" class="mt-1 block text-right text-xs text-slate-400">
                                                    {{ customer.phone || customer.code }}
                                                </span>
                                            </div>

                                            <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                                اختيار
                                            </span>
                                        </button>

                                        <div
                                            v-if="!filteredCustomers.length"
                                            class="px-3 py-5 text-center text-xs text-slate-500"
                                        >
                                            لا توجد نتائج.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="selectedCustomer"
                                class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900/50 dark:bg-emerald-950/20"
                            >
                                <div>
                                    <strong class="block text-sm text-emerald-800 dark:text-emerald-200">
                                        {{ selectedCustomer.name }}
                                    </strong>

                                    <span dir="ltr" class="mt-1 block text-right text-xs text-emerald-600 dark:text-emerald-300">
                                        {{ selectedCustomer.phone || selectedCustomer.code }}
                                    </span>
                                </div>

                                <button
                                    type="button"
                                    class="text-xs font-bold text-slate-500 hover:text-rose-600 dark:text-slate-400"
                                    @click="unlinkCustomer"
                                >
                                    إلغاء الربط
                                </button>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        اسم العميل <span class="text-rose-500">*</span>
                                    </label>

                                    <input
                                        v-model.trim="form.customer_name"
                                        type="text"
                                        maxlength="255"
                                        placeholder="الاسم الكامل"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        :class="{ 'border-rose-500': fieldError('customer_name') }"
                                    />

                                    <p v-if="fieldError('customer_name')" class="mt-2 text-xs font-semibold text-rose-600">
                                        {{ fieldError('customer_name') }}
                                    </p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        رقم الهاتف <span class="text-rose-500">*</span>
                                    </label>

                                    <input
                                        v-model.trim="form.customer_phone"
                                        type="tel"
                                        maxlength="20"
                                        dir="ltr"
                                        placeholder="059XXXXXXX"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-right text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        :class="{ 'border-rose-500': fieldError('customer_phone') }"
                                    />

                                    <p v-if="fieldError('customer_phone')" class="mt-2 text-xs font-semibold text-rose-600">
                                        {{ fieldError('customer_phone') }}
                                    </p>
                                </div>
                            </div>

                            <label
                                v-if="!form.customer_id"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40"
                            >
                                <input
                                    v-model="form.save_customer"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900"
                                />

                                <span>
                                    <span class="block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        حفظ العميل في قاعدة البيانات
                                    </span>

                                    <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">
                                        يساعدك ذلك في الوصول إلى سجل العميل وطلباته مستقبلاً.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <!-- Device -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-cyan-600 text-white shadow-lg shadow-cyan-600/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 2.75h10A2.25 2.25 0 0119.25 5v14A2.25 2.25 0 0117 21.25H7A2.25 2.25 0 014.75 19V5A2.25 2.25 0 017 2.75zM10.5 18.25h3" />
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="font-black text-slate-900 dark:text-white">
                                        بيانات الجهاز
                                    </h2>

                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        نوع الجهاز وموديله والمشكلة الظاهرة.
                                    </p>
                                </div>
                            </div>

                            <span class="rounded-full bg-cyan-50 px-3 py-1.5 text-xs font-bold text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300">
                                بيانات إلزامية
                            </span>
                        </div>

                        <div class="space-y-6 p-5 sm:p-6">
                            <div>
                                <p class="mb-2 text-xs font-bold text-slate-500 dark:text-slate-400">
                                    اختيار سريع لنوع الجهاز
                                </p>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="type in quickDeviceTypes"
                                        :key="type"
                                        type="button"
                                        class="rounded-xl border px-3 py-2 text-xs font-bold transition"
                                        :class="
                                            form.device_type === type
                                                ? 'border-cyan-600 bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300'
                                                : 'border-slate-200 text-slate-600 hover:border-cyan-300 hover:bg-cyan-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-cyan-900/20'
                                        "
                                        @click="chooseDeviceType(type)"
                                    >
                                        {{ type }}
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        نوع الجهاز <span class="text-rose-500">*</span>
                                    </label>

                                    <input
                                        v-model.trim="form.device_type"
                                        type="text"
                                        maxlength="255"
                                        placeholder="هاتف محمول"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        العلامة التجارية <span class="text-rose-500">*</span>
                                    </label>

                                    <input
                                        v-model.trim="form.brand"
                                        type="text"
                                        maxlength="255"
                                        placeholder="Samsung"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        الموديل <span class="text-rose-500">*</span>
                                    </label>

                                    <input
                                        v-model.trim="form.model"
                                        type="text"
                                        maxlength="255"
                                        placeholder="S24 Ultra"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        اللون
                                    </label>

                                    <input
                                        v-model.trim="form.color"
                                        type="text"
                                        maxlength="50"
                                        placeholder="أسود"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-bold text-slate-500 dark:text-slate-400">
                                    مشاكل شائعة
                                </p>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="problem in quickProblems"
                                        :key="problem"
                                        type="button"
                                        class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-cyan-300 hover:bg-cyan-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-cyan-900/20"
                                        @click="chooseProblem(problem)"
                                    >
                                        {{ problem }}
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-5 lg:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        وصف المشكلة <span class="text-rose-500">*</span>
                                    </label>

                                    <textarea
                                        v-model.trim="form.problem_description"
                                        rows="4"
                                        maxlength="1000"
                                        placeholder="اكتب المشكلة كما وصفها العميل..."
                                        class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        حالة الجهاز عند الاستلام
                                    </label>

                                    <textarea
                                        v-model.trim="form.device_condition"
                                        rows="4"
                                        maxlength="500"
                                        placeholder="مثال: خدوش بسيطة، الشاشة سليمة..."
                                        class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-bold text-slate-500 dark:text-slate-400">
                                    علامات تجارية سريعة
                                </p>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="brand in quickBrands"
                                        :key="brand"
                                        type="button"
                                        class="rounded-xl border px-3 py-2 text-xs font-bold transition"
                                        :class="
                                            form.brand === brand
                                                ? 'border-cyan-600 bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300'
                                                : 'border-slate-200 text-slate-600 hover:border-cyan-300 hover:bg-cyan-50 dark:border-slate-700 dark:text-slate-300'
                                        "
                                        @click="chooseBrand(brand)"
                                    >
                                        {{ brand }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Diagnosis -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 border-b border-slate-200 bg-gradient-to-l from-violet-50 to-white px-5 py-5 dark:border-slate-700 dark:from-violet-950/40 dark:to-slate-800 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-lg shadow-violet-600/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 3.75h4.5m-6 4.5h7.5m-9 4.5h10.5m-9 4.5h7.5" />
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="font-black text-slate-900 dark:text-white">
                                        الفحص وتنفيذ الصيانة
                                    </h2>

                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        في الصيانة السريعة يتم التشخيص والتنفيذ مباشرة.
                                    </p>
                                </div>
                            </div>

                            <span class="rounded-full bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 dark:bg-violet-900/30 dark:text-violet-300">
                                ⚡ مسار سريع
                            </span>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <div class="grid gap-5 lg:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        نتيجة الفحص / التشخيص <span class="text-rose-500">*</span>
                                    </label>

                                    <textarea
                                        v-model.trim="form.inspection_result"
                                        rows="5"
                                        maxlength="1000"
                                        placeholder="مثال: مدخل الشحن تالف ويحتاج استبدال..."
                                        class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        الصيانة المنفذة <span class="text-rose-500">*</span>
                                    </label>

                                    <textarea
                                        v-model.trim="form.repair_action"
                                        rows="5"
                                        maxlength="1000"
                                        placeholder="مثال: تم تغيير مدخل الشحن وتجربة الجهاز..."
                                        class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    ></textarea>
                                </div>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        سبب العطل
                                    </label>

                                    <input
                                        v-model.trim="form.fault_cause"
                                        type="text"
                                        maxlength="1000"
                                        placeholder="اختياري"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        الفني المسؤول
                                    </label>

                                    <input
                                        v-model.trim="form.technician_name"
                                        type="text"
                                        maxlength="255"
                                        placeholder="اسم الفني"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        رسوم الفحص
                                    </label>

                                    <input
                                        v-model.number="form.inspection_fee"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm font-bold text-slate-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        أجرة الصيانة
                                    </label>

                                    <input
                                        v-model.number="form.labor_cost"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm font-bold text-slate-900 shadow-sm focus:border-violet-500 focus:ring-violet-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Parts -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M14.25 6.087c0-1.397-1.134-2.53-2.53-2.53S9.19 4.69 9.19 6.087s1.134 2.53 2.53 2.53 2.53-1.133 2.53-2.53zm0 0L20.25 12m-6-5.913L3.75 16.5m7.97-7.883L16.5 20.25" />
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="font-black text-slate-900 dark:text-white">
                                        قطع الغيار
                                    </h2>

                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        اختيارية، وسيتم خصمها من مخزن الصيانة عند الحفظ.
                                    </p>
                                </div>
                            </div>

                            <span class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                                إجمالي القطع: {{ money(partsTotal) }}
                            </span>
                        </div>

                        <div class="space-y-5 p-5 sm:p-6">
                            <div class="grid gap-3 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.5fr)_auto]">
                                <input
                                    v-model.trim="productSearch"
                                    type="search"
                                    placeholder="بحث باسم القطعة أو الكود..."
                                    class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                />

                                <select
                                    v-model="productToAdd"
                                    class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                >
                                    <option value="">اختر قطعة من مخزن الصيانة</option>

                                    <option
                                        v-for="product in filteredProducts"
                                        :key="product.id"
                                        :value="product.id"
                                    >
                                        {{ product.name }} — متوفر {{ product.available_quantity }}
                                    </option>
                                </select>

                                <button
                                    type="button"
                                    :disabled="!productToAdd"
                                    class="rounded-xl bg-amber-500 px-5 py-3 text-sm font-black text-white transition hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    @click="addPart"
                                >
                                    إضافة
                                </button>
                            </div>

                            <div
                                v-if="form.parts.length"
                                class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700"
                            >
                                <table class="min-w-[760px] w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                                    <thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-900/60 dark:text-slate-400">
                                        <tr>
                                            <th class="px-4 py-3 text-right">القطعة</th>
                                            <th class="px-4 py-3 text-center">المتوفر</th>
                                            <th class="px-4 py-3 text-center">الكمية</th>
                                            <th class="px-4 py-3 text-center">سعر البيع</th>
                                            <th class="px-4 py-3 text-left">الإجمالي</th>
                                            <th class="px-4 py-3 text-center"></th>
                                        </tr>
                                    </thead>

                                    <tbody class="divide-y divide-slate-100 dark:divide-slate-700/80">
                                        <tr
                                            v-for="(part, index) in form.parts"
                                            :key="part.product_id"
                                        >
                                            <td class="px-4 py-4">
                                                <strong class="block text-slate-900 dark:text-white">
                                                    {{ part.product_name }}
                                                </strong>

                                                <span dir="ltr" class="mt-1 block text-right text-xs text-slate-400">
                                                    {{ part.product_code || '—' }}
                                                </span>
                                            </td>

                                            <td class="px-4 py-4 text-center font-bold text-emerald-600">
                                                {{ part.available_quantity }}
                                            </td>

                                            <td class="px-4 py-4">
                                                <input
                                                    v-model.number="part.quantity"
                                                    type="number"
                                                    min="1"
                                                    :max="part.available_quantity"
                                                    class="mx-auto block w-24 rounded-xl border-slate-300 bg-white text-center text-sm font-bold focus:border-amber-500 focus:ring-amber-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                />
                                            </td>

                                            <td class="px-4 py-4">
                                                <input
                                                    v-model.number="part.unit_price"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="mx-auto block w-32 rounded-xl border-slate-300 bg-white text-center text-sm font-bold focus:border-amber-500 focus:ring-amber-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                                />
                                            </td>

                                            <td dir="ltr" class="px-4 py-4 text-left font-black text-slate-900 dark:text-white">
                                                {{ money(Number(part.quantity || 0) * Number(part.unit_price || 0)) }}
                                            </td>

                                            <td class="px-4 py-4 text-center">
                                                <button
                                                    type="button"
                                                    class="rounded-lg px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/20"
                                                    @click="removePart(index)"
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
                                class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center dark:border-slate-600 dark:bg-slate-900/40"
                            >
                                <p class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                    لا توجد قطع غيار
                                </p>

                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    يمكن حفظ الصيانة بدون قطع إذا كانت الخدمة تعتمد على الأجرة فقط.
                                </p>
                            </div>
                        </div>
                    </section>

                    <!-- Notes -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-800 text-white shadow-lg shadow-slate-800/20 dark:bg-slate-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8.25 6.75h7.5m-7.5 4.5h7.5m-7.5 4.5h4.5" />
                                    </svg>
                                </div>

                                <div>
                                    <h2 class="font-black text-slate-900 dark:text-white">
                                        الملاحظات
                                    </h2>

                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        ملاحظات اختيارية على الطلب السريع.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="grid gap-5 p-5 sm:p-6 lg:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                    ملاحظات تظهر للعميل
                                </label>

                                <textarea
                                    v-model.trim="form.customer_notes"
                                    rows="4"
                                    maxlength="500"
                                    class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                ></textarea>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                    ملاحظات داخلية
                                </label>

                                <textarea
                                    v-model.trim="form.internal_notes"
                                    rows="4"
                                    maxlength="500"
                                    class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                ></textarea>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Summary -->
                <aside class="space-y-5 xl:sticky xl:top-6">
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 bg-gradient-to-l from-indigo-50 to-white px-5 py-5 dark:border-slate-700 dark:from-indigo-950/40 dark:to-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/20">
                                    ⚡
                                </div>

                                <div>
                                    <h2 class="font-black text-slate-900 dark:text-white">
                                        ملخص الصيانة السريعة
                                    </h2>

                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        التكلفة والدفع وحالة التسليم.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5 p-5">
                            <div class="rounded-2xl bg-gradient-to-l from-indigo-700 to-blue-600 p-4 text-white">
                                <p class="text-xs text-indigo-100">
                                    إجمالي الصيانة
                                </p>

                                <p dir="ltr" class="mt-2 text-right text-2xl font-black">
                                    {{ money(totalAmount) }}
                                </p>

                                <div class="mt-4 grid grid-cols-3 gap-2 border-t border-white/15 pt-3 text-center">
                                    <div>
                                        <p class="text-[10px] text-indigo-100">الفحص</p>
                                        <strong dir="ltr" class="mt-1 block text-xs">
                                            {{ money(form.inspection_fee) }}
                                        </strong>
                                    </div>

                                    <div>
                                        <p class="text-[10px] text-indigo-100">الأجرة</p>
                                        <strong dir="ltr" class="mt-1 block text-xs">
                                            {{ money(form.labor_cost) }}
                                        </strong>
                                    </div>

                                    <div>
                                        <p class="text-[10px] text-indigo-100">القطع</p>
                                        <strong dir="ltr" class="mt-1 block text-xs">
                                            {{ money(partsTotal) }}
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-bold text-slate-500 dark:text-slate-400">
                                    اختصار المبلغ المدفوع
                                </p>

                                <div class="grid grid-cols-3 gap-2">
                                    <button
                                        type="button"
                                        class="rounded-xl border px-2 py-2 text-xs font-bold transition"
                                        :class="
                                            paymentAmount === 0
                                                ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'
                                                : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300'
                                        "
                                        @click="setPaymentAmount('none')"
                                    >
                                        بدون
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-xl border border-slate-200 px-2 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:text-slate-300"
                                        :disabled="totalAmount <= 0"
                                        @click="setPaymentAmount('half')"
                                    >
                                        النصف
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-xl border border-slate-200 px-2 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:text-slate-300"
                                        :disabled="totalAmount <= 0"
                                        @click="setPaymentAmount('full')"
                                    >
                                        كامل
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                    المبلغ المدفوع
                                </label>

                                <input
                                    v-model.number="form.payment_amount"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm font-black focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                />
                            </div>

                            <template v-if="paymentAmount > 0">
                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        طريقة الدفع
                                    </label>

                                    <select
                                        v-model="form.payment_method"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
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

                                <div>
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        الحساب المالي
                                    </label>

                                    <select
                                        v-model="form.financial_account_id"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
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
                                        </option>
                                    </select>

                                    <p
                                        v-if="selectedAccount"
                                        class="mt-2 text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        الرصيد الحالي:
                                        <strong>{{ money(selectedAccount.current_balance) }}</strong>
                                        —
                                        بعد الدفعة:
                                        <strong>{{ money(projectedAccountBalance) }}</strong>
                                    </p>
                                </div>

                                <div v-if="isElectronic">
                                    <label class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        مرجع العملية
                                    </label>

                                    <input
                                        v-model.trim="form.transaction_reference"
                                        type="text"
                                        maxlength="255"
                                        placeholder="اختياري"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    />
                                </div>
                            </template>

                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                <div
                                    class="h-full rounded-full bg-emerald-500 transition-all duration-300"
                                    :style="{ width: `${paymentProgress}%` }"
                                ></div>
                            </div>

                            <div class="flex items-center justify-between rounded-xl bg-amber-50 px-4 py-3 dark:bg-amber-900/20">
                                <span class="text-sm font-bold text-amber-900 dark:text-amber-300">
                                    المتبقي
                                </span>

                                <span dir="ltr" class="font-black text-amber-700 dark:text-amber-300">
                                    {{ money(remainingAmount) }}
                                </span>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                            <h2 class="font-black text-slate-900 dark:text-white">
                                إنهاء الطلب
                            </h2>

                            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                حدد وضع الجهاز بعد حفظ الصيانة.
                            </p>
                        </div>

                        <div class="space-y-3 p-5">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition"
                                :class="
                                    form.finish_status === 'delivered'
                                        ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20'
                                        : 'border-slate-200 hover:border-violet-300 dark:border-slate-700'
                                "
                            >
                                <input
                                    v-model="form.finish_status"
                                    type="radio"
                                    value="delivered"
                                    class="mt-1 text-violet-600 focus:ring-violet-500"
                                />

                                <span>
                                    <strong class="block text-sm text-slate-900 dark:text-white">
                                        ⚡ إصلاح وتسليم الآن
                                    </strong>

                                    <span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">
                                        العميل سيستلم الجهاز فور حفظ الطلب.
                                    </span>
                                </span>
                            </label>

                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl border p-4 transition"
                                :class="
                                    form.finish_status === 'ready'
                                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-900/20'
                                        : 'border-slate-200 hover:border-emerald-300 dark:border-slate-700'
                                "
                            >
                                <input
                                    v-model="form.finish_status"
                                    type="radio"
                                    value="ready"
                                    class="mt-1 text-emerald-600 focus:ring-emerald-500"
                                />

                                <span>
                                    <strong class="block text-sm text-slate-900 dark:text-white">
                                        ✓ جاهز للاستلام
                                    </strong>

                                    <span class="mt-1 block text-xs leading-5 text-slate-500 dark:text-slate-400">
                                        الصيانة اكتملت لكن العميل سيستلم لاحقاً.
                                    </span>
                                </span>
                            </label>

                            <label
                                v-if="form.finish_status === 'delivered' && remainingAmount > 0.00001"
                                class="flex cursor-pointer items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/50 dark:bg-amber-900/20"
                            >
                                <input
                                    v-model="form.allow_partial_payment"
                                    type="checkbox"
                                    class="mt-0.5 h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500"
                                />

                                <span>
                                    <span class="block text-sm font-bold text-amber-900 dark:text-amber-300">
                                        السماح بالتسليم مع بقاء دين
                                    </span>

                                    <span class="mt-1 block text-xs text-amber-700 dark:text-amber-300">
                                        سيبقى {{ money(remainingAmount) }} على العميل.
                                    </span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <div class="space-y-3">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-violet-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-violet-600/20 transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-200 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-violet-900/40"
                        >
                            <svg
                                v-if="form.processing"
                                class="h-5 w-5 animate-spin"
                                fill="none"
                                viewBox="0 0 24 24"
                            >
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                            </svg>

                            <svg
                                v-else
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7" />
                            </svg>

                            {{
                                form.processing
                                    ? 'جارٍ حفظ الصيانة...'
                                    : form.finish_status === 'delivered'
                                        ? 'حفظ الصيانة وتسليم الجهاز'
                                        : 'حفظ الصيانة كجاهز للاستلام'
                            }}
                        </button>

                        <Link
                            :href="route('repairs.index')"
                            class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        >
                            إلغاء والعودة
                        </Link>

                        <p class="text-center text-xs leading-5 text-slate-400">
                            سيتم تسجيل الصيانة وقطع الغيار والدفعة والحالة ضمن نفس العملية.
                        </p>
                    </div>
                </aside>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
