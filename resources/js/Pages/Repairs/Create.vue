<script setup>
import {
    computed,
    nextTick,
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

    paymentMethods: {
        type: Object,
        default: () => ({}),
    },

    attachmentStages: {
        type: Object,
        default: () => ({}),
    },

    financialAccounts: {
        type: Array,
        default: () => [],
    },
});

const toLocalDate = (date = new Date()) => {
    const offset = date.getTimezoneOffset();
    const localDate = new Date(date.getTime() - offset * 60 * 1000);

    return localDate.toISOString().slice(0, 10);
};

const today = toLocalDate();

const form = useForm({
    customer_id: '',
    customer_name: '',
    customer_phone: '',
    save_customer: false,

    device_type: '',
    brand: '',
    model: '',
    color: '',
    problem_description: '',
    device_condition: '',
    received_accessories: '',
    lock_code: '',
    technician_name: '',

    estimated_cost: '',
    inspection_fee: '',
    payment_amount: 0,
    payment_method: 'cash',
    financial_account_id: '',
    bank_or_app_name: '',
    transaction_reference: '',

    received_at: today,
    expected_delivery_date: '',
    due_date: '',

    customer_notes: '',
    internal_notes: '',
    attachments: [],
});

const customerSearch = ref('');
const customerSearchInput = ref(null);
const customerDropdownOpen = ref(false);
const activeCustomerIndex = ref(0);
const showLockCode = ref(false);
const pageError = ref('');

const fileInput = ref(null);
const dragActive = ref(false);
const imageError = ref('');
const imagePreviews = ref([]);

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
    'الشاشة مكسورة أو لا تعمل',
    'الجهاز لا يشحن',
    'الجهاز لا يعمل',
    'البطارية تفرغ بسرعة',
    'مشكلة في الصوت أو السماعة',
    'مشكلة في الكاميرا',
    'الجهاز تعرض للماء',
    'مشكلة في النظام أو البرامج',
];

const quickConditions = [
    'خدوش بسيطة',
    'كسر في الشاشة',
    'كسر في الغطاء الخلفي',
    'آثار سقوط',
    'آثار رطوبة أو ماء',
    'الجهاز بحالة جيدة ظاهرياً',
];

const accessoryOptions = [
    'شاحن',
    'كابل',
    'غطاء حماية',
    'شريحة اتصال',
    'بطاقة ذاكرة',
    'علبة الجهاز',
    'سماعة',
    'لا يوجد',
];

const normalizeText = (value) => String(value ?? '')
    .trim()
    .toLocaleLowerCase('ar');

const normalizeEnumValue = (value) => {
    if (value && typeof value === 'object') {
        return value.value ?? value.name ?? '';
    }

    return String(value ?? '');
};

const selectedCustomer = computed(() => props.customers.find(
    (customer) => Number(customer.id) === Number(form.customer_id),
) ?? null);

const filteredCustomers = computed(() => {
    const search = normalizeText(customerSearch.value);

    if (!search) {
        return props.customers.slice(0, 8);
    }

    return props.customers
        .filter((customer) => [
            customer.name,
            customer.phone,
            customer.code,
        ].some((value) => normalizeText(value).includes(search)))
        .slice(0, 10);
});

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
        (account) => normalizeEnumValue(account.type) === expectedAccountType.value,
    );
});

const selectedFinancialAccount = computed(() => props.financialAccounts.find(
    (account) => Number(account.id) === Number(form.financial_account_id),
) ?? null);

const estimatedCost = computed(() => Math.max(
    0,
    Number(form.estimated_cost || 0),
));

const inspectionFee = computed(() => Math.max(
    0,
    Number(form.inspection_fee || 0),
));

const paymentAmount = computed(() => Math.max(
    0,
    Number(form.payment_amount || 0),
));

const expectedTotal = computed(() => Math.max(
    estimatedCost.value,
    inspectionFee.value,
));

const remainingAmount = computed(() => Math.max(
    0,
    expectedTotal.value - paymentAmount.value,
));

const paymentProgress = computed(() => {
    if (expectedTotal.value <= 0) {
        return paymentAmount.value > 0 ? 100 : 0;
    }

    return Math.min(
        100,
        Math.round((paymentAmount.value / expectedTotal.value) * 100),
    );
});

const paymentStatus = computed(() => {
    if (paymentAmount.value <= 0) {
        return {
            label: 'بدون عربون',
            classes: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        };
    }

    if (
        expectedTotal.value > 0
        && paymentAmount.value >= expectedTotal.value
    ) {
        return {
            label: 'مدفوع بالكامل',
            classes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        };
    }

    return {
        label: 'عربون مسجل',
        classes: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    };
});

const dateError = computed(() => {
    if (
        form.expected_delivery_date
        && form.received_at
        && form.expected_delivery_date < form.received_at
    ) {
        return 'تاريخ التسليم المتوقع يجب ألا يسبق تاريخ الاستلام.';
    }

    if (
        form.due_date
        && form.received_at
        && form.due_date < form.received_at
    ) {
        return 'تاريخ استحقاق المبلغ يجب ألا يسبق تاريخ الاستلام.';
    }

    return '';
});

const paymentError = computed(() => {
    if (Number(form.payment_amount || 0) < 0) {
        return 'المبلغ المقدم لا يمكن أن يكون سالباً.';
    }

    if (paymentAmount.value > 0 && !form.payment_method) {
        return 'اختر طريقة الدفع للعربون.';
    }

    if (
        paymentAmount.value > 0
        && !form.financial_account_id
    ) {
        return 'اختر الحساب المالي الذي سيتم إيداع العربون فيه.';
    }

    if (
        paymentAmount.value > 0
        && ['bank_transfer', 'banking_app'].includes(form.payment_method)
        && !String(form.bank_or_app_name || '').trim()
    ) {
        return 'أدخل اسم البنك أو التطبيق.';
    }

    return '';
});

const requiredProgress = computed(() => {
    const requiredFields = [
        form.customer_name,
        form.customer_phone,
        form.device_type,
        form.brand,
        form.model,
        form.problem_description,
        form.received_at,
    ];

    const completed = requiredFields.filter(
        (value) => String(value ?? '').trim() !== '',
    ).length;

    return Math.round((completed / requiredFields.length) * 100);
});

const canSubmit = computed(() => Boolean(
    String(form.customer_name || '').trim()
    && String(form.customer_phone || '').trim()
    && String(form.device_type || '').trim()
    && String(form.brand || '').trim()
    && String(form.model || '').trim()
    && String(form.problem_description || '').trim()
    && form.received_at
    && !dateError.value
    && !paymentError.value
    && !form.processing
));

const formatCurrency = (value) => `${Number(value || 0).toLocaleString('ar-PS', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})} شيكل`;

const fieldError = (field) => form.errors[field] ?? '';

const selectCustomer = (customer) => {
    form.customer_id = customer.id;
    form.customer_name = customer.name ?? '';
    form.customer_phone = customer.phone ?? '';
    form.save_customer = false;

    customerSearch.value = customer.name ?? '';
    customerDropdownOpen.value = false;
    activeCustomerIndex.value = 0;
};

const useNewCustomer = async () => {
    form.customer_id = '';
    form.customer_name = '';
    form.customer_phone = '';
    form.save_customer = true;

    customerSearch.value = '';
    customerDropdownOpen.value = false;

    await nextTick();

    document.getElementById('customer_name')?.focus();
};

const unlinkCustomer = () => {
    form.customer_id = '';
    form.save_customer = false;
    customerSearch.value = '';
};

const openCustomerSearch = () => {
    activeCustomerIndex.value = 0;
    customerDropdownOpen.value = true;
};

const closeCustomerSearch = () => {
    window.setTimeout(() => {
        customerDropdownOpen.value = false;
    }, 160);
};

const handleCustomerKeyboard = (event) => {
    if (!customerDropdownOpen.value || filteredCustomers.value.length === 0) {
        if (event.key === 'Escape') {
            customerDropdownOpen.value = false;
        }

        return;
    }

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        activeCustomerIndex.value = (
            activeCustomerIndex.value + 1
        ) % filteredCustomers.value.length;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        activeCustomerIndex.value = activeCustomerIndex.value <= 0
            ? filteredCustomers.value.length - 1
            : activeCustomerIndex.value - 1;
    }

    if (event.key === 'Enter') {
        event.preventDefault();
        selectCustomer(filteredCustomers.value[activeCustomerIndex.value]);
    }

    if (event.key === 'Escape') {
        customerDropdownOpen.value = false;
    }
};

const chooseDeviceType = (value) => {
    form.device_type = value;
};

const chooseBrand = (value) => {
    form.brand = value;
};

const appendUniqueText = (field, value) => {
    const current = String(form[field] ?? '').trim();

    if (current.includes(value)) {
        return;
    }

    form[field] = current
        ? `${current}، ${value}`
        : value;
};

const toggleAccessory = (accessory) => {
    if (accessory === 'لا يوجد') {
        form.received_accessories = 'لا يوجد';
        return;
    }

    const values = String(form.received_accessories || '')
        .split(/[،,]/)
        .map((item) => item.trim())
        .filter(Boolean)
        .filter((item) => item !== 'لا يوجد');

    const index = values.indexOf(accessory);

    if (index >= 0) {
        values.splice(index, 1);
    } else {
        values.push(accessory);
    }

    form.received_accessories = values.join('، ');
};

const accessorySelected = (accessory) => {
    if (accessory === 'لا يوجد') {
        return form.received_accessories === 'لا يوجد';
    }

    return String(form.received_accessories || '')
        .split(/[،,]/)
        .map((item) => item.trim())
        .includes(accessory);
};

const validateAndAddFiles = (fileList) => {
    imageError.value = '';

    const files = Array.from(fileList ?? []);

    for (const file of files) {
        if (imagePreviews.value.length >= 6) {
            imageError.value = 'يمكن إرفاق 6 صور كحد أقصى.';
            break;
        }

        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
            imageError.value = 'يسمح فقط بصور JPG وPNG وWebP.';
            continue;
        }

        if (file.size > 2 * 1024 * 1024) {
            imageError.value = `تم تجاهل ${file.name} لأن حجمه يتجاوز 2MB.`;
            continue;
        }

        const previewUrl = URL.createObjectURL(file);

        imagePreviews.value.push({
            id: `${file.name}-${file.size}-${file.lastModified}-${Math.random()}`,
            file,
            url: previewUrl,
        });
    }

    form.attachments = imagePreviews.value.map((image) => image.file);
};

const handleImageUpload = (event) => {
    validateAndAddFiles(event.target.files);
    event.target.value = '';
};

const handleDrop = (event) => {
    dragActive.value = false;
    validateAndAddFiles(event.dataTransfer?.files);
};

const removeImage = (index) => {
    const image = imagePreviews.value[index];

    if (image?.url) {
        URL.revokeObjectURL(image.url);
    }

    imagePreviews.value.splice(index, 1);
    form.attachments = imagePreviews.value.map((item) => item.file);
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const setPaymentAmount = (type) => {
    if (type === 'none') {
        form.payment_amount = 0;
        return;
    }

    if (type === 'inspection') {
        form.payment_amount = inspectionFee.value;
        return;
    }

    if (type === 'half') {
        form.payment_amount = Number((expectedTotal.value / 2).toFixed(2));
        return;
    }

    if (type === 'full') {
        form.payment_amount = Number(expectedTotal.value.toFixed(2));
    }
};

const scrollToFirstError = async () => {
    await nextTick();

    document.querySelector('[data-form-error="true"]')?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    });
};

const validateBeforeSubmit = () => {
    pageError.value = '';

    const required = [
        ['customer_name', 'أدخل اسم العميل.'],
        ['customer_phone', 'أدخل رقم هاتف العميل.'],
        ['device_type', 'حدد نوع الجهاز.'],
        ['brand', 'أدخل العلامة التجارية.'],
        ['model', 'أدخل موديل الجهاز.'],
        ['problem_description', 'اكتب وصف المشكلة.'],
        ['received_at', 'حدد تاريخ الاستلام.'],
    ];

    const missing = required.find(
        ([field]) => !String(form[field] ?? '').trim(),
    );

    if (missing) {
        pageError.value = missing[1];
        return false;
    }

    if (String(form.lock_code || '').length > 10) {
        pageError.value = 'رمز القفل يجب ألا يتجاوز 10 أحرف.';
        return false;
    }

    if (dateError.value) {
        pageError.value = dateError.value;
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
        scrollToFirstError();
        return;
    }

    form.attachments = imagePreviews.value.map((image) => image.file);

    form.post(route('repairs.store'), {
        preserveScroll: true,
        forceFormData: true,

        onError: () => {
            pageError.value = 'تعذر حفظ طلب الصيانة. راجع الحقول المحددة ثم أعد المحاولة.';
            scrollToFirstError();
        },
    });
};

watch(
    () => form.payment_method,
    () => {
        const selectedIsCompatible = compatibleAccounts.value.some(
            (account) => Number(account.id) === Number(form.financial_account_id),
        );

        if (!selectedIsCompatible) {
            form.financial_account_id = compatibleAccounts.value[0]?.id ?? '';
        }

        if (form.payment_method === 'cash') {
            form.bank_or_app_name = '';
            form.transaction_reference = '';
        }
    },
    {
        immediate: true,
    },
);

watch(
    () => form.received_at,
    (value) => {
        if (
            form.expected_delivery_date
            && value
            && form.expected_delivery_date < value
        ) {
            form.expected_delivery_date = value;
        }

        if (form.due_date && value && form.due_date < value) {
            form.due_date = value;
        }
    },
);

onBeforeUnmount(() => {
    imagePreviews.value.forEach((image) => {
        if (image.url) {
            URL.revokeObjectURL(image.url);
        }
    });
});
</script>

<template>
    <Head title="استقبال جهاز صيانة" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                        <span>الصيانة</span>
                        <span class="text-slate-300 dark:text-slate-600">/</span>
                        <span>استقبال جهاز</span>
                    </div>

                    <h1 class="mt-2 text-2xl font-black text-slate-900 dark:text-white sm:text-3xl">
                        استقبال جهاز صيانة جديد
                    </h1>

                    <p class="mt-1 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                        سجّل بيانات العميل والجهاز بسرعة، وثّق حالته بالصور، ثم أضف العربون وموعد التسليم المتوقع.
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
                data-form-error="true"
                class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-semibold text-rose-700 shadow-sm dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300"
            >
                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3.75m9-1.5a9 9 0 11-18 0 9 9 0 0118 0zM12 16.5h.008v.008H12V16.5z" />
                </svg>

                <span>{{ pageError }}</span>
            </div>

            <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_390px]">
                <div class="min-w-0 space-y-6">
                    <!-- العميل -->
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
                                <label for="customer_search" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                    البحث في العملاء
                                </label>

                                <div class="relative">
                                    <svg class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" />
                                    </svg>

                                    <input
                                        id="customer_search"
                                        ref="customerSearchInput"
                                        v-model="customerSearch"
                                        type="search"
                                        autocomplete="off"
                                        placeholder="اسم العميل أو رقم الهاتف أو الكود..."
                                        class="block w-full rounded-2xl border-2 border-slate-200 bg-slate-50 py-3.5 pr-12 pl-4 text-sm font-semibold text-slate-900 transition placeholder:font-normal focus:border-indigo-500 focus:bg-white focus:ring-4 focus:ring-indigo-100 dark:border-slate-600 dark:bg-slate-900 dark:text-white dark:focus:border-indigo-500 dark:focus:ring-indigo-900/30"
                                        @focus="openCustomerSearch"
                                        @input="openCustomerSearch"
                                        @blur="closeCustomerSearch"
                                        @keydown="handleCustomerKeyboard"
                                    />
                                </div>

                                <div
                                    v-if="customerDropdownOpen"
                                    class="absolute right-0 left-0 top-full z-50 mt-2 max-h-80 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                                >
                                    <button
                                        v-for="(customer, index) in filteredCustomers"
                                        :key="customer.id"
                                        type="button"
                                        class="flex w-full items-center justify-between gap-4 rounded-xl p-3 text-right transition"
                                        :class="index === activeCustomerIndex ? 'bg-indigo-50 dark:bg-indigo-900/30' : 'hover:bg-slate-50 dark:hover:bg-slate-700'"
                                        @mousedown.prevent="selectCustomer(customer)"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-black text-slate-900 dark:text-white">
                                                {{ customer.name }}
                                            </p>
                                            <p dir="ltr" class="mt-1 text-right text-xs text-slate-500 dark:text-slate-400">
                                                {{ customer.phone || 'بدون رقم هاتف' }}
                                            </p>
                                        </div>

                                        <span v-if="customer.code" dir="ltr" class="shrink-0 rounded-lg bg-slate-100 px-2 py-1 text-xs font-bold text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                                            {{ customer.code }}
                                        </span>
                                    </button>

                                    <div v-if="filteredCustomers.length === 0" class="px-5 py-8 text-center">
                                        <p class="font-bold text-slate-700 dark:text-slate-200">
                                            لا يوجد عميل مطابق
                                        </p>
                                        <button type="button" class="mt-3 text-sm font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400" @mousedown.prevent="useNewCustomer">
                                            تسجيل عميل جديد
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="selectedCustomer"
                                class="flex flex-col gap-3 rounded-2xl border border-indigo-100 bg-indigo-50/70 p-4 dark:border-indigo-900/50 dark:bg-indigo-950/20 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-indigo-600 shadow-sm dark:bg-slate-800 dark:text-indigo-300">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-indigo-600 dark:text-indigo-300">
                                            عميل مرتبط بالطلب
                                        </p>
                                        <p class="mt-1 font-black text-slate-900 dark:text-white">
                                            {{ selectedCustomer.name }}
                                        </p>
                                    </div>
                                </div>

                                <button type="button" class="text-xs font-bold text-slate-500 transition hover:text-rose-600 dark:text-slate-400" @click="unlinkCustomer">
                                    إلغاء الربط
                                </button>
                            </div>

                            <div class="grid gap-5 md:grid-cols-2">
                                <div>
                                    <label for="customer_name" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        اسم العميل <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="customer_name"
                                        v-model.trim="form.customer_name"
                                        type="text"
                                        maxlength="255"
                                        placeholder="الاسم الكامل"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        :class="{ 'border-rose-500': fieldError('customer_name') }"
                                    />
                                    <p v-if="fieldError('customer_name')" data-form-error="true" class="mt-2 text-xs font-semibold text-rose-600">
                                        {{ fieldError('customer_name') }}
                                    </p>
                                </div>

                                <div>
                                    <label for="customer_phone" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        رقم الهاتف <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        id="customer_phone"
                                        v-model.trim="form.customer_phone"
                                        type="tel"
                                        maxlength="20"
                                        dir="ltr"
                                        placeholder="059XXXXXXX"
                                        class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-right text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                        :class="{ 'border-rose-500': fieldError('customer_phone') }"
                                    />
                                    <p v-if="fieldError('customer_phone')" data-form-error="true" class="mt-2 text-xs font-semibold text-rose-600">
                                        {{ fieldError('customer_phone') }}
                                    </p>
                                </div>
                            </div>

                            <label v-if="!form.customer_id" class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3 transition hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-700/40">
                                <input v-model="form.save_customer" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900" />
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

                    <!-- الجهاز -->
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
                                        نوع الجهاز وموديله وحالته عند الاستلام.
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
                                        :class="form.device_type === type ? 'border-cyan-600 bg-cyan-50 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-300' : 'border-slate-200 text-slate-600 hover:border-cyan-300 hover:bg-cyan-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-cyan-900/20'"
                                        @click="chooseDeviceType(type)"
                                    >
                                        {{ type }}
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label for="device_type" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        نوع الجهاز <span class="text-rose-500">*</span>
                                    </label>
                                    <input id="device_type" v-model.trim="form.device_type" type="text" maxlength="255" placeholder="مثال: هاتف محمول" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" :class="{ 'border-rose-500': fieldError('device_type') }" />
                                    <p v-if="fieldError('device_type')" data-form-error="true" class="mt-2 text-xs font-semibold text-rose-600">{{ fieldError('device_type') }}</p>
                                </div>

                                <div>
                                    <label for="brand" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        العلامة التجارية <span class="text-rose-500">*</span>
                                    </label>
                                    <input id="brand" v-model.trim="form.brand" type="text" maxlength="255" placeholder="Samsung" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" :class="{ 'border-rose-500': fieldError('brand') }" />
                                    <p v-if="fieldError('brand')" data-form-error="true" class="mt-2 text-xs font-semibold text-rose-600">{{ fieldError('brand') }}</p>
                                </div>

                                <div>
                                    <label for="model" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        الموديل <span class="text-rose-500">*</span>
                                    </label>
                                    <input id="model" v-model.trim="form.model" type="text" maxlength="255" placeholder="مثال: S24 Ultra" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" :class="{ 'border-rose-500': fieldError('model') }" />
                                    <p v-if="fieldError('model')" data-form-error="true" class="mt-2 text-xs font-semibold text-rose-600">{{ fieldError('model') }}</p>
                                </div>

                                <div>
                                    <label for="color" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        اللون
                                    </label>
                                    <input id="color" v-model.trim="form.color" type="text" maxlength="50" placeholder="أسود" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
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
                                        class="rounded-lg border px-3 py-1.5 text-xs font-bold transition"
                                        :class="form.brand === brand ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'border-slate-200 text-slate-500 hover:border-indigo-300 dark:border-slate-700 dark:text-slate-400'"
                                        @click="chooseBrand(brand)"
                                    >
                                        {{ brand }}
                                    </button>
                                </div>
                            </div>

                            <div class="grid gap-5 md:grid-cols-[1fr_240px]">
                                <div>
                                    <label for="problem_description" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        وصف المشكلة <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea id="problem_description" v-model.trim="form.problem_description" rows="5" maxlength="1000" placeholder="اكتب وصف المشكلة كما شرحها العميل، والأعراض الظاهرة..." class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" :class="{ 'border-rose-500': fieldError('problem_description') }"></textarea>
                                    <div class="mt-1 flex items-center justify-between text-xs">
                                        <p v-if="fieldError('problem_description')" data-form-error="true" class="font-semibold text-rose-600">{{ fieldError('problem_description') }}</p>
                                        <span class="mr-auto text-slate-400">{{ form.problem_description.length }}/1000</span>
                                    </div>
                                </div>

                                <div>
                                    <label for="lock_code" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        رمز قفل الجهاز
                                    </label>
                                    <div class="relative">
                                        <input id="lock_code" v-model.trim="form.lock_code" :type="showLockCode ? 'text' : 'password'" maxlength="10" autocomplete="off" placeholder="اختياري" class="block w-full rounded-xl border-slate-300 bg-white py-3 pr-3 pl-11 text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                                        <button type="button" class="absolute left-3 top-1/2 -translate-y-1/2 rounded-lg p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200" :aria-label="showLockCode ? 'إخفاء رمز القفل' : 'إظهار رمز القفل'" @click="showLockCode = !showLockCode">
                                            <svg v-if="showLockCode" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88" /></svg>
                                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178zM15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                        </button>
                                    </div>
                                    <p class="mt-2 text-xs leading-5 text-slate-400">
                                        يحفظ الرمز مشفراً ولا يظهر في قوائم الطلبات.
                                    </p>
                                </div>
                            </div>

                            <div>
                                <p class="mb-2 text-xs font-bold text-slate-500 dark:text-slate-400">
                                    مشاكل شائعة — اضغط لإضافتها للوصف
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="problem in quickProblems" :key="problem" type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-cyan-300 hover:bg-cyan-50 hover:text-cyan-700 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-cyan-900/20 dark:hover:text-cyan-300" @click="appendUniqueText('problem_description', problem)">
                                        {{ problem }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- الحالة والملحقات -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-500 text-white shadow-lg shadow-amber-500/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082M9.75 3.104a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19 14.5m-4.75-11.396c.251.023.501.05.75.082M19 14.5l-2.29 2.29a1.5 1.5 0 01-1.06.44H8.35a1.5 1.5 0 01-1.06-.44L5 14.5m14 0l1.5 1.5M5 14.5L3.5 16" /></svg>
                                </div>
                                <div>
                                    <h2 class="font-black text-slate-900 dark:text-white">
                                        حالة الجهاز والملحقات
                                    </h2>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        وثّق حالة الجهاز بدقة لتجنب أي خلاف عند التسليم.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 p-5 sm:p-6">
                            <div class="grid gap-5 lg:grid-cols-2">
                                <div>
                                    <label for="device_condition" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        الحالة الخارجية عند الاستلام
                                    </label>
                                    <textarea id="device_condition" v-model.trim="form.device_condition" rows="4" maxlength="500" placeholder="مثال: خدوش على الإطار وكسر بسيط في الزاوية..." class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"></textarea>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button v-for="condition in quickConditions" :key="condition" type="button" class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-bold text-amber-800 transition hover:bg-amber-100 dark:border-amber-900/60 dark:bg-amber-900/20 dark:text-amber-300" @click="appendUniqueText('device_condition', condition)">
                                            {{ condition }}
                                        </button>
                                    </div>
                                </div>

                                <div>
                                    <label for="received_accessories" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        الملحقات المستلمة مع الجهاز
                                    </label>
                                    <textarea id="received_accessories" v-model.trim="form.received_accessories" rows="4" maxlength="500" placeholder="اختر من القائمة أو اكتب الملحقات..." class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"></textarea>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <button v-for="accessory in accessoryOptions" :key="accessory" type="button" class="rounded-lg border px-2.5 py-1.5 text-xs font-bold transition" :class="accessorySelected(accessory) ? 'border-amber-500 bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200' : 'border-slate-200 text-slate-600 hover:border-amber-300 dark:border-slate-700 dark:text-slate-300'" @click="toggleAccessory(accessory)">
                                            {{ accessory }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- الصور -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-600 text-white shadow-lg shadow-violet-600/20">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175A2.42 2.42 0 002.25 9.8v7.45a2.25 2.25 0 002.25 2.25h15a2.25 2.25 0 002.25-2.25V9.8a2.42 2.42 0 00-1.802-2.395 48.424 48.424 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316A2.25 2.25 0 0014.52 3.75h-5.04a2.25 2.25 0 00-1.908 1.059l-.745 1.366zM15.75 13.5a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" /></svg>
                                </div>
                                <div>
                                    <h2 class="font-black text-slate-900 dark:text-white">
                                        صور الجهاز عند الاستلام
                                    </h2>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        أرفق صوراً واضحة للشاشة والغطاء والزوايا المتضررة.
                                    </p>
                                </div>
                            </div>

                            <span class="rounded-full bg-violet-50 px-3 py-1.5 text-xs font-bold text-violet-700 dark:bg-violet-900/30 dark:text-violet-300">
                                {{ imagePreviews.length }}/6 صور
                            </span>
                        </div>

                        <div class="p-5 sm:p-6">
                            <div
                                class="rounded-3xl border-2 border-dashed p-6 text-center transition"
                                :class="dragActive ? 'border-violet-500 bg-violet-50 dark:bg-violet-900/20' : 'border-slate-300 bg-slate-50 dark:border-slate-600 dark:bg-slate-900/40'"
                                @dragenter.prevent="dragActive = true"
                                @dragover.prevent="dragActive = true"
                                @dragleave.prevent="dragActive = false"
                                @drop.prevent="handleDrop"
                            >
                                <input ref="fileInput" type="file" multiple accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleImageUpload" />

                                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-violet-600 shadow-sm dark:bg-slate-800 dark:text-violet-300">
                                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 8.25L12 3.75m0 0L7.5 8.25M12 3.75V15" /></svg>
                                </div>

                                <h3 class="mt-4 font-black text-slate-900 dark:text-white">
                                    اسحب الصور هنا أو اخترها من الجهاز
                                </h3>
                                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500 dark:text-slate-400">
                                    JPG أو PNG أو WebP، بحد أقصى 2MB للصورة و6 صور للطلب.
                                </p>
                                <button type="button" class="mt-4 rounded-xl bg-violet-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-50" :disabled="imagePreviews.length >= 6" @click="triggerFileInput">
                                    اختيار الصور
                                </button>
                            </div>

                            <p v-if="imageError" data-form-error="true" class="mt-3 text-sm font-semibold text-rose-600">
                                {{ imageError }}
                            </p>
                            <p v-if="fieldError('attachments')" data-form-error="true" class="mt-3 text-sm font-semibold text-rose-600">
                                {{ fieldError('attachments') }}
                            </p>

                            <div v-if="imagePreviews.length" class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                                <article v-for="(image, index) in imagePreviews" :key="image.id" class="group relative aspect-square overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 dark:border-slate-700 dark:bg-slate-900">
                                    <img :src="image.url" :alt="`صورة الجهاز ${index + 1}`" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-between bg-gradient-to-t from-slate-950/80 to-transparent p-2 pt-8">
                                        <span class="text-xs font-bold text-white">{{ index + 1 }}</span>
                                        <button type="button" class="rounded-lg bg-rose-600 p-1.5 text-white transition hover:bg-rose-700" title="حذف الصورة" @click="removeImage(index)">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    </div>
                                </article>
                            </div>
                        </div>
                    </section>

                    <!-- التواريخ والملاحظات -->
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="border-b border-slate-200 px-5 py-5 dark:border-slate-700">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-slate-800 text-white shadow-lg shadow-slate-800/20 dark:bg-slate-600">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6.75 3v2.25M17.25 3v2.25M3.75 9h16.5m-15 12h13.5a1.5 1.5 0 001.5-1.5V6.75a1.5 1.5 0 00-1.5-1.5H5.25a1.5 1.5 0 00-1.5 1.5V19.5a1.5 1.5 0 001.5 1.5z" /></svg>
                                </div>
                                <div>
                                    <h2 class="font-black text-slate-900 dark:text-white">
                                        المواعيد والملاحظات
                                    </h2>
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        موعد الاستلام والتسليم والملاحظات الداخلية.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 p-5 sm:p-6">
                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                                <div>
                                    <label for="received_at" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        تاريخ الاستلام <span class="text-rose-500">*</span>
                                    </label>
                                    <input id="received_at" v-model="form.received_at" type="date" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                                </div>

                                <div>
                                    <label for="expected_delivery_date" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        التسليم المتوقع
                                    </label>
                                    <input id="expected_delivery_date" v-model="form.expected_delivery_date" type="date" :min="form.received_at" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" :class="{ 'border-rose-500': dateError }" />
                                </div>

                                <div>
                                    <label for="due_date" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        استحقاق المتبقي
                                    </label>
                                    <input id="due_date" v-model="form.due_date" type="date" :min="form.received_at" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" :class="{ 'border-rose-500': dateError }" />
                                </div>

                                <div>
                                    <label for="technician_name" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        الفني المسؤول
                                    </label>
                                    <input id="technician_name" v-model.trim="form.technician_name" type="text" maxlength="255" placeholder="اسم الفني" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                                </div>
                            </div>

                            <p v-if="dateError" data-form-error="true" class="text-xs font-semibold text-rose-600">
                                {{ dateError }}
                            </p>

                            <div class="grid gap-5 lg:grid-cols-2">
                                <div>
                                    <label for="customer_notes" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        ملاحظات تظهر للعميل
                                    </label>
                                    <textarea id="customer_notes" v-model.trim="form.customer_notes" rows="4" maxlength="500" placeholder="تعليمات أو ملاحظات متفق عليها مع العميل..." class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"></textarea>
                                </div>

                                <div>
                                    <label for="internal_notes" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        ملاحظات داخلية
                                    </label>
                                    <textarea id="internal_notes" v-model.trim="form.internal_notes" rows="4" maxlength="500" placeholder="ملاحظات للفني أو الإدارة، لا تظهر للعميل..." class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-3 text-sm leading-7 text-slate-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"></textarea>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- الملخص -->
                <aside class="space-y-5 xl:sticky xl:top-6">
                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="relative overflow-hidden bg-gradient-to-l from-slate-950 via-indigo-950 to-indigo-800 p-5 text-white">
                            <div class="absolute -left-10 -top-12 h-36 w-36 rounded-full bg-cyan-400/10 blur-3xl"></div>
                            <div class="relative">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-bold text-indigo-200">
                                            ملخص طلب الصيانة
                                        </p>
                                        <h2 class="mt-2 text-xl font-black">
                                            {{ form.brand || 'الجهاز' }} {{ form.model || '' }}
                                        </h2>
                                        <p class="mt-1 text-sm text-indigo-100">
                                            {{ form.device_type || 'لم يتم تحديد نوع الجهاز' }}
                                        </p>
                                    </div>

                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/15 bg-white/10 backdrop-blur">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 2.75h10A2.25 2.25 0 0119.25 5v14A2.25 2.25 0 0117 21.25H7A2.25 2.25 0 014.75 19V5A2.25 2.25 0 017 2.75zM10.5 18.25h3" /></svg>
                                    </div>
                                </div>

                                <div class="mt-5 grid grid-cols-2 gap-3 text-xs">
                                    <div class="rounded-xl bg-white/10 p-3">
                                        <p class="text-indigo-200">العميل</p>
                                        <p class="mt-1 truncate font-bold">{{ form.customer_name || 'غير محدد' }}</p>
                                    </div>
                                    <div class="rounded-xl bg-white/10 p-3">
                                        <p class="text-indigo-200">موعد التسليم</p>
                                        <p dir="ltr" class="mt-1 text-right font-bold">{{ form.expected_delivery_date || 'غير محدد' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 p-5">
                            <div class="flex items-start justify-between gap-4">
                                <span class="text-sm text-slate-500 dark:text-slate-400">وصف المشكلة</span>
                                <span class="max-w-52 text-left text-sm font-bold leading-6 text-slate-800 dark:text-slate-200">{{ form.problem_description || 'لم يكتب بعد' }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-slate-500 dark:text-slate-400">الصور المرفقة</span>
                                <span class="font-black text-slate-900 dark:text-white">{{ imagePreviews.length }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <span class="text-sm text-slate-500 dark:text-slate-400">تاريخ الاستلام</span>
                                <span dir="ltr" class="font-bold text-slate-900 dark:text-white">{{ form.received_at || '—' }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-700">
                            <div>
                                <h2 class="font-black text-slate-900 dark:text-white">
                                    التكلفة والعربون
                                </h2>
                                <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    يمكن تعديل التكلفة بعد الفحص لاحقاً.
                                </p>
                            </div>

                            <span class="rounded-full px-3 py-1 text-xs font-bold" :class="paymentStatus.classes">
                                {{ paymentStatus.label }}
                            </span>
                        </div>

                        <div class="space-y-4 p-5">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label for="inspection_fee" class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-300">
                                        تكلفة الفحص
                                    </label>
                                    <input id="inspection_fee" v-model.number="form.inspection_fee" type="number" min="0" step="0.01" class="block w-full rounded-xl border-slate-300 bg-white text-sm font-bold focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                                </div>
                                <div>
                                    <label for="estimated_cost" class="mb-2 block text-xs font-bold text-slate-600 dark:text-slate-300">
                                        التكلفة التقديرية
                                    </label>
                                    <input id="estimated_cost" v-model.number="form.estimated_cost" type="number" min="0" step="0.01" class="block w-full rounded-xl border-slate-300 bg-white text-sm font-bold focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                                </div>
                            </div>

                            <div class="rounded-2xl bg-gradient-to-l from-indigo-700 to-blue-600 p-4 text-white">
                                <p class="text-xs text-indigo-100">القيمة المتوقعة حالياً</p>
                                <p dir="ltr" class="mt-2 text-right text-2xl font-black">{{ formatCurrency(expectedTotal) }}</p>
                            </div>

                            <div class="grid grid-cols-4 gap-2">
                                <button type="button" class="rounded-xl border px-2 py-2 text-xs font-bold transition" :class="paymentAmount === 0 ? 'border-indigo-600 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'border-slate-200 text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700'" @click="setPaymentAmount('none')">بدون</button>
                                <button type="button" class="rounded-xl border border-slate-200 px-2 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700" :disabled="inspectionFee <= 0" @click="setPaymentAmount('inspection')">الفحص</button>
                                <button type="button" class="rounded-xl border border-slate-200 px-2 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700" :disabled="expectedTotal <= 0" @click="setPaymentAmount('half')">النصف</button>
                                <button type="button" class="rounded-xl border border-slate-200 px-2 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50 disabled:opacity-40 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-700" :disabled="expectedTotal <= 0" @click="setPaymentAmount('full')">كامل</button>
                            </div>

                            <div>
                                <label for="payment_amount" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                    المبلغ المقدم
                                </label>
                                <input id="payment_amount" v-model.number="form.payment_amount" type="number" min="0" step="0.01" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm font-black focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" :class="{ 'border-rose-500': paymentError }" />
                            </div>

                            <template v-if="paymentAmount > 0">
                                <div>
                                    <label for="payment_method" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        طريقة الدفع
                                    </label>
                                    <select id="payment_method" v-model="form.payment_method" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                        <option v-for="(label, value) in paymentMethods" :key="value" :value="value">{{ label }}</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="financial_account_id" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        الحساب المالي
                                    </label>
                                    <select id="financial_account_id" v-model="form.financial_account_id" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                                        <option value="">اختر الحساب المالي</option>
                                        <option v-for="account in compatibleAccounts" :key="account.id" :value="account.id">{{ account.name }}</option>
                                    </select>
                                    <p v-if="selectedFinancialAccount" class="mt-2 text-xs text-slate-500 dark:text-slate-400">سيتم الإيداع في: <strong>{{ selectedFinancialAccount.name }}</strong></p>
                                </div>

                                <div v-if="['bank_transfer', 'banking_app'].includes(form.payment_method)">
                                    <label for="bank_or_app_name" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        اسم البنك أو التطبيق
                                    </label>
                                    <input id="bank_or_app_name" v-model.trim="form.bank_or_app_name" type="text" maxlength="255" placeholder="مثال: بنك فلسطين" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" :class="{ 'border-rose-500': paymentError }" />
                                </div>

                                <div v-if="['bank_transfer', 'banking_app'].includes(form.payment_method)">
                                    <label for="transaction_reference" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                        مرجع العملية
                                    </label>
                                    <input id="transaction_reference" v-model.trim="form.transaction_reference" type="text" maxlength="255" placeholder="اختياري" class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white" />
                                </div>
                            </template>

                            <p v-if="paymentError" data-form-error="true" class="text-xs font-semibold text-rose-600">{{ paymentError }}</p>

                            <div class="h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-emerald-500 transition-all duration-300" :style="{ width: `${paymentProgress}%` }"></div>
                            </div>

                            <div v-if="expectedTotal > 0" class="flex items-center justify-between rounded-xl bg-amber-50 px-4 py-3 dark:bg-amber-900/20">
                                <span class="text-sm font-bold text-amber-900 dark:text-amber-300">المتبقي المتوقع</span>
                                <span dir="ltr" class="font-black text-amber-700 dark:text-amber-300">{{ formatCurrency(remainingAmount) }}</span>
                            </div>
                        </div>
                    </section>

                    <div class="space-y-3">
                        <button
                            type="submit"
                            :disabled="!canSubmit"
                            class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-4 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200 disabled:cursor-not-allowed disabled:opacity-50 dark:focus:ring-emerald-900/40"
                        >
                            <svg v-if="form.processing" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M5 13l4 4L19 7" /></svg>

                            {{ form.processing ? 'جارٍ استقبال الجهاز...' : 'استقبال الجهاز وإنشاء الطلب' }}
                        </button>

                        <Link :href="route('repairs.index')" class="inline-flex w-full items-center justify-center rounded-2xl border border-slate-300 bg-white px-6 py-3.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700">
                            إلغاء والعودة
                        </Link>

                        <p class="text-center text-xs leading-5 text-slate-400">
                            سيُنشأ الطلب بحالة «مستلم» و«بانتظار الفحص» ويمكن استكمال الفحص وقطع الغيار لاحقاً.
                        </p>
                    </div>
                </aside>
            </div>
        </form>
    </AuthenticatedLayout>
</template>
