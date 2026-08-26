<script setup>
import {
    computed,
    onBeforeUnmount,
    ref,
} from 'vue';

import {
    Head,
    Link,
    useForm,
} from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    product: {
        type: Object,
        default: null,
    },

    categories: {
        type: Array,
        default: () => [],
    },

    warehouses: {
        type: Array,
        default: () => [],
    },

    purchasePriceLocked: {
        type: Boolean,
        default: false,
    },

    pageTitle: {
        type: String,
        required: true,
    },
});

const isEdit =
    computed(
        () => Boolean(
            props.product
        )
    );

const form = useForm({
    name:
        props.product?.name
        || '',

    code:
        props.product?.code
        || '',

    barcode:
        props.product?.barcode
        || '',

    category_id:
        props.product?.category_id
        || '',

    brand:
        props.product?.brand
        || '',

    model:
        props.product?.model
        || '',

    purchase_price:
        props.product?.purchase_price
        ?? 0,

    selling_price:
        props.product?.selling_price
        ?? 0,

    minimum_selling_price:
        props.product
            ?.minimum_selling_price
        ?? '',

    low_stock_threshold:
        props.product
            ?.low_stock_threshold
        ?? 5,

    location:
        props.product?.location
        || '',

    opening_stocks: [],

    description:
        props.product?.description
        || '',

    notes:
        props.product?.notes
        || '',

    is_active:
        props.product?.is_active
        ?? true,

    image:
        null,
});

const pageError =
    ref('');

const imageInput =
    ref(null);

const imagePreview =
    ref(
        props.product?.image_path
            ? `/storage/${props.product.image_path}`
            : null
    );

const generatedPreviewUrl =
    ref(null);

const money = (value) =>
    `${Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`;

const number = (value) =>
    Number(value || 0)
        .toLocaleString(
            'en-US'
        );

const selectedCategory =
    computed(
        () =>
            props.categories.find(
                (category) =>
                    Number(category.id)
                    === Number(
                        form.category_id
                    )
            )
            || null
    );

const purchasePrice =
    computed(
        () =>
            Math.max(
                0,
                Number(
                    form.purchase_price
                    || 0
                )
            )
    );

const sellingPrice =
    computed(
        () =>
            Math.max(
                0,
                Number(
                    form.selling_price
                    || 0
                )
            )
    );

const minimumSellingPrice =
    computed(() => {
        if (
            form.minimum_selling_price
            === ''
            || form.minimum_selling_price
            === null
        ) {
            return null;
        }

        return Math.max(
            0,
            Number(
                form.minimum_selling_price
                || 0
            )
        );
    });

const profit =
    computed(
        () =>
            sellingPrice.value
            - purchasePrice.value
    );

const profitMargin =
    computed(() => {
        if (
            purchasePrice.value
            <= 0
        ) {
            return 0;
        }

        return (
            profit.value
            / purchasePrice.value
        ) * 100;
    });

const pricingWarning =
    computed(() => {
        if (
            sellingPrice.value
            <= 0
        ) {
            return '';
        }

        if (
            purchasePrice.value
            > 0
            && sellingPrice.value
                < purchasePrice.value
        ) {
            return 'سعر البيع أقل من سعر الشراء، وسيظهر المنتج بخسارة.';
        }

        if (
            minimumSellingPrice.value
            !== null
            && minimumSellingPrice.value
                > sellingPrice.value
        ) {
            return 'أقل سعر بيع لا يمكن أن يكون أكبر من سعر البيع الأساسي.';
        }

        return '';
    });

const openingStockTotal =
    computed(
        () =>
            form.opening_stocks
                .reduce(
                    (
                        total,
                        row
                    ) =>
                        total
                        + Math.max(
                            0,
                            Math.trunc(
                                Number(
                                    row.quantity
                                    || 0
                                )
                            )
                        ),
                    0
                )
    );

const openingInventoryValue =
    computed(
        () =>
            openingStockTotal.value
            * purchasePrice.value
    );

const selectedOpeningWarehouseIds =
    computed(
        () =>
            form.opening_stocks
                .map(
                    (row) =>
                        Number(
                            row.warehouse_id
                            || 0
                        )
                )
                .filter(Boolean)
    );

const availableWarehousesForRow =
    (currentIndex) =>
        props.warehouses.filter(
            (warehouse) => {
                const ownWarehouseId =
                    Number(
                        form
                            .opening_stocks[
                                currentIndex
                            ]
                            ?.warehouse_id
                        || 0
                    );

                return (
                    Number(
                        warehouse.id
                    )
                    === ownWarehouseId
                    || !selectedOpeningWarehouseIds
                        .value
                        .includes(
                            Number(
                                warehouse.id
                            )
                        )
                );
            }
        );

const openingStockError =
    computed(() => {
        const warehouseIds =
            form.opening_stocks
                .map(
                    (row) =>
                        Number(
                            row.warehouse_id
                            || 0
                        )
                )
                .filter(Boolean);

        if (
            new Set(
                warehouseIds
            ).size
            !== warehouseIds.length
        ) {
            return 'لا يمكن تكرار نفس المخزن أكثر من مرة.';
        }

        for (
            const row
            of form.opening_stocks
        ) {
            if (
                !row.warehouse_id
            ) {
                return 'اختر المخزن لكل رصيد افتتاحي.';
            }

            if (
                Math.trunc(
                    Number(
                        row.quantity
                        || 0
                    )
                ) < 0
            ) {
                return 'الكمية الافتتاحية لا يمكن أن تكون سالبة.';
            }
        }

        if (
            openingStockTotal.value
            > 0
            && purchasePrice.value
                <= 0
        ) {
            return 'عند إدخال رصيد افتتاحي يجب أن يكون سعر الشراء أكبر من صفر.';
        }

        return '';
    });

const baseFormError =
    computed(() => {
        if (
            String(
                form.name || ''
            ).trim().length
            < 2
        ) {
            return 'أدخل اسم منتج واضحاً من حرفين على الأقل.';
        }

        if (
            !form.category_id
        ) {
            return 'اختر فئة المنتج.';
        }

        if (
            purchasePrice.value < 0
            || sellingPrice.value < 0
        ) {
            return 'الأسعار لا يمكن أن تكون سالبة.';
        }

        if (
            minimumSellingPrice.value
            !== null
            && minimumSellingPrice.value
                > sellingPrice.value
        ) {
            return 'أقل سعر بيع لا يمكن أن يكون أكبر من سعر البيع.';
        }

        if (
            Number(
                form.low_stock_threshold
                || 0
            ) < 0
        ) {
            return 'حد تنبيه المخزون لا يمكن أن يكون سالباً.';
        }

        if (
            !isEdit.value
            && openingStockError.value
        ) {
            return openingStockError.value;
        }

        return '';
    });

const canSubmit =
    computed(
        () =>
            !form.processing
            && !baseFormError.value
    );

const completionItems =
    computed(
        () => [
            {
                label:
                    'بيانات المنتج',

                done:
                    String(
                        form.name || ''
                    ).trim().length >= 2
                    && Boolean(
                        form.category_id
                    ),
            },
            {
                label:
                    'التسعير',

                done:
                    sellingPrice.value
                    >= 0
                    && purchasePrice.value
                    >= 0
                    && !(
                        minimumSellingPrice.value
                        !== null
                        && minimumSellingPrice.value
                            > sellingPrice.value
                    ),
            },
            {
                label:
                    'الكود',

                done:
                    isEdit.value
                    ? Boolean(
                        String(
                            form.code || ''
                        ).trim()
                    )
                    : true,
            },
            {
                label:
                    'المخزون',

                done:
                    isEdit.value
                    || !openingStockError.value,
            },
        ]
    );

const completionPercent =
    computed(
        () => {
            const done =
                completionItems.value
                    .filter(
                        (item) =>
                            item.done
                    )
                    .length;

            return Math.round(
                (
                    done
                    / completionItems
                        .value.length
                ) * 100
            );
        }
    );

const addOpeningStockRow =
    () => {
        const warehouse =
            props.warehouses.find(
                (item) =>
                    !selectedOpeningWarehouseIds
                        .value
                        .includes(
                            Number(
                                item.id
                            )
                        )
            );

        if (!warehouse) {
            return;
        }

        form.opening_stocks.push({
            warehouse_id:
                warehouse.id,

            /*
             * المنتج الجديد لا يعني وجود قطعة فعلية.
             * يبدأ الرصيد 0 ما لم يكتب المستخدم كمية افتتاحية حقيقية.
             */
            quantity:
                0,
        });
    };

const removeOpeningStockRow =
    (index) => {
        form.opening_stocks.splice(
            index,
            1
        );
    };

const generateCode =
    () => {
        const date =
            new Date();

        const stamp =
            [
                String(
                    date.getFullYear()
                ).slice(-2),

                String(
                    date.getMonth()
                    + 1
                ).padStart(
                    2,
                    '0'
                ),

                String(
                    date.getDate()
                ).padStart(
                    2,
                    '0'
                ),
            ].join('');

        const random =
            Math.random()
                .toString(36)
                .slice(2, 7)
                .toUpperCase();

        form.code =
            `PRD-${stamp}-${random}`;
    };

const handleImageUpload =
    (event) => {
        form.clearErrors(
            'image'
        );

        const file =
            event.target
                .files?.[0];

        if (!file) {
            return;
        }

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        if (
            !allowedTypes.includes(
                file.type
            )
        ) {
            form.setError(
                'image',
                'الصورة يجب أن تكون JPG أو PNG أو WebP.'
            );

            event.target.value =
                '';

            return;
        }

        if (
            file.size
            > 2 * 1024 * 1024
        ) {
            form.setError(
                'image',
                'حجم الصورة لا يتجاوز 2MB.'
            );

            event.target.value =
                '';

            return;
        }

        if (
            generatedPreviewUrl.value
        ) {
            URL.revokeObjectURL(
                generatedPreviewUrl
                    .value
            );
        }

        generatedPreviewUrl.value =
            URL.createObjectURL(
                file
            );

        imagePreview.value =
            generatedPreviewUrl.value;

        form.image =
            file;
    };

const removeImage =
    () => {
        if (
            generatedPreviewUrl.value
        ) {
            URL.revokeObjectURL(
                generatedPreviewUrl
                    .value
            );
        }

        generatedPreviewUrl.value =
            null;

        imagePreview.value =
            props.product?.image_path
                ? `/storage/${props.product.image_path}`
                : null;

        form.image =
            null;

        if (
            imageInput.value
        ) {
            imageInput.value.value =
                '';
        }

        form.clearErrors(
            'image'
        );
    };

const scrollToFirstError =
    () => {
        window.setTimeout(
            () => {
                document
                    .querySelector(
                        '[data-product-error="true"]'
                    )
                    ?.scrollIntoView({
                        behavior:
                            'smooth',

                        block:
                            'center',
                    });
            },
            80
        );
    };

const submit =
    () => {
        pageError.value =
            '';

        if (
            baseFormError.value
        ) {
            pageError.value =
                baseFormError.value;

            scrollToFirstError();

            return;
        }

        form.clearErrors();

        if (
            isEdit.value
        ) {
            /*
             * POST + _method=put أكثر أماناً مع multipart/file
             * من إرسال PUT multipart مباشرة.
             */
            form
                .transform(
                    (data) => ({
                        ...data,
                        _method:
                            'put',
                    })
                )
                .post(
                    route(
                        'products.update',
                        props.product.id
                    ),
                    {
                        forceFormData:
                            true,

                        preserveScroll:
                            true,

                        onError:
                            () => {
                                pageError.value =
                                    'راجع الحقول المحددة ثم أعد المحاولة.';

                                scrollToFirstError();
                            },
                    }
                );

            return;
        }

        form
            .transform(
                (data) => data
            )
            .post(
                route(
                    'products.store'
                ),
                {
                    forceFormData:
                        true,

                    preserveScroll:
                        true,

                    onError:
                        () => {
                            pageError.value =
                                'راجع الحقول المحددة ثم أعد المحاولة.';

                            scrollToFirstError();
                        },
                }
            );
    };

onBeforeUnmount(
    () => {
        if (
            generatedPreviewUrl.value
        ) {
            URL.revokeObjectURL(
                generatedPreviewUrl
                    .value
            );
        }
    }
);
</script>

<template>
    <Head :title="pageTitle" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between"
            >
                <div>
                    <div
                        class="flex flex-wrap items-center gap-2"
                    >
                        <span
                            class="rounded-full bg-blue-50 px-3 py-1 text-[11px] font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300"
                        >
                            Product Master
                        </span>

                        <span
                            class="rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
                        >
                            {{ isEdit ? 'تعديل بيانات' : 'منتج جديد' }}
                        </span>
                    </div>

                    <h1
                        class="mt-2 text-2xl font-black text-slate-950 dark:text-white sm:text-3xl"
                    >
                        {{ pageTitle }}
                    </h1>

                    <p
                        class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400"
                    >
                        {{
                            isEdit
                                ? 'حدّث بيانات المنتج والتسعير دون تعديل الأرصدة المخزنية من هذه الصفحة.'
                                : 'أنشئ بطاقة المنتج أولاً، ثم استخدم المشتريات لإدخال المخزون الجديد. الرصيد الافتتاحي هنا مخصص فقط للبضاعة الموجودة قبل بدء استخدام النظام.'
                        }}
                    </p>
                </div>

                <div
                    class="flex flex-wrap gap-2"
                >
                    <Link
                        :href="route('products.index')"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                    >
                        العودة للمنتجات
                    </Link>

                    <Link
                        v-if="isEdit"
                        :href="route('products.show', product.id)"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-950 px-4 py-2.5 text-sm font-black text-white dark:bg-white dark:text-slate-950"
                    >
                        عرض المنتج
                    </Link>
                </div>
            </div>
        </template>

        <form
            class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_340px]"
            @submit.prevent="submit"
        >
            <main class="space-y-5">
                <!-- General error -->
                <section
                    v-if="
                        pageError
                        || Object.keys(
                            form.errors
                        ).length
                    "
                    data-product-error="true"
                    class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
                >
                    <strong
                        class="block font-black"
                    >
                        تعذر حفظ المنتج
                    </strong>

                    <p
                        v-if="pageError"
                        class="mt-1"
                    >
                        {{ pageError }}
                    </p>

                    <ul
                        v-if="
                            Object.keys(
                                form.errors
                            ).length
                        "
                        class="mt-2 list-inside list-disc space-y-1 text-xs"
                    >
                        <li
                            v-for="(
                                error,
                                key
                            ) in form.errors"
                            :key="key"
                        >
                            {{ error }}
                        </li>
                    </ul>
                </section>

                <!-- Basic information -->
                <section
                    class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="flex items-center gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-300"
                        >
                            <svg
                                class="h-5 w-5"
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

                        <div>
                            <h2
                                class="font-black text-slate-950 dark:text-white"
                            >
                                البيانات الأساسية
                            </h2>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                المعلومات التي تميز المنتج داخل النظام.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid gap-4 p-5 sm:grid-cols-2"
                    >
                        <div
                            class="sm:col-span-2"
                        >
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                اسم المنتج
                                <span
                                    class="text-rose-500"
                                >
                                    *
                                </span>
                            </label>

                            <input
                                v-model.trim="form.name"
                                type="text"
                                maxlength="255"
                                autofocus
                                placeholder="مثال: شاحن Samsung 25W USB-C"
                                class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                :class="{
                                    'border-rose-500':
                                        form.errors.name,
                                }"
                            />

                            <p
                                v-if="form.errors.name"
                                class="mt-1 text-xs font-bold text-rose-600"
                            >
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                الفئة
                                <span
                                    class="text-rose-500"
                                >
                                    *
                                </span>
                            </label>

                            <select
                                v-model="form.category_id"
                                class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                :class="{
                                    'border-rose-500':
                                        form.errors.category_id,
                                }"
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

                            <p
                                v-if="!categories.length"
                                class="mt-2 rounded-xl bg-amber-50 p-2.5 text-xs text-amber-700 dark:bg-amber-950/30 dark:text-amber-300"
                            >
                                لا توجد فئات نشطة. أضف فئة أو فعّل فئة موجودة أولاً.
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                كود المنتج
                                <span
                                    v-if="isEdit"
                                    class="text-rose-500"
                                >
                                    *
                                </span>
                            </label>

                            <div
                                class="flex gap-2"
                            >
                                <input
                                    v-model.trim="form.code"
                                    type="text"
                                    maxlength="50"
                                    dir="ltr"
                                    :placeholder="
                                        isEdit
                                            ? 'PRD-XXXX'
                                            : 'اتركه فارغاً للتوليد التلقائي'
                                    "
                                    class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-left text-sm font-bold uppercase dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    :class="{
                                        'border-rose-500':
                                            form.errors.code,
                                    }"
                                />

                                <button
                                    type="button"
                                    class="shrink-0 rounded-xl bg-slate-100 px-4 text-xs font-black text-slate-700 transition hover:bg-blue-50 hover:text-blue-700 dark:bg-slate-700 dark:text-slate-200"
                                    @click="generateCode"
                                >
                                    توليد
                                </button>
                            </div>

                            <p
                                v-if="!isEdit"
                                class="mt-1 text-[10px] leading-5 text-slate-400"
                            >
                                إذا تركته فارغاً سيولد النظام كوداً فريداً عند الحفظ.
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                الباركود
                            </label>

                            <input
                                v-model.trim="form.barcode"
                                type="text"
                                maxlength="50"
                                dir="ltr"
                                placeholder="امسح أو أدخل الباركود - اختياري"
                                class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-left text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                العلامة التجارية
                            </label>

                            <input
                                v-model.trim="form.brand"
                                type="text"
                                maxlength="100"
                                placeholder="Apple, Samsung, Anker..."
                                class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                الموديل
                            </label>

                            <input
                                v-model.trim="form.model"
                                type="text"
                                maxlength="100"
                                placeholder="اسم أو رقم الموديل"
                                class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />
                        </div>
                    </div>
                </section>

                <!-- Pricing -->
                <section
                    class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="flex items-center gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M12 6V4m0 2c-2 0-3.5 1-3.5 2.5S10 11 12 11s3.5 1 3.5 2.5S14 16 12 16m0 0v2m0-2c-1.5 0-2.8-.5-3.5-1.4M20 12a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                                />
                            </svg>
                        </div>

                        <div>
                            <h2
                                class="font-black text-slate-950 dark:text-white"
                            >
                                التسعير والربحية
                            </h2>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                الأسعار هنا مرجعية للمنتج؛ تكلفة المشتريات اللاحقة تحدث متوسط التكلفة.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid gap-4 p-5 md:grid-cols-3"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                سعر الشراء
                                <span
                                    class="text-rose-500"
                                >
                                    *
                                </span>
                            </label>

                            <div class="relative">
                                <input
                                    v-model.number="form.purchase_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    :readonly="isEdit && purchasePriceLocked"
                                    class="block w-full rounded-xl px-3 py-3 pl-16 text-lg font-black transition"
                                    :class="
                                        isEdit && purchasePriceLocked
                                            ? 'cursor-not-allowed border-slate-200 bg-slate-100 text-slate-600 focus:border-slate-200 focus:ring-0 dark:border-slate-700 dark:bg-slate-900/60 dark:text-slate-300'
                                            : 'border-slate-300 bg-white dark:border-slate-600 dark:bg-slate-900 dark:text-white'
                                    "
                                />

                                <span
                                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400"
                                >
                                    شيكل
                                </span>
                            </div>

                            <div
                                v-if="isEdit && purchasePriceLocked"
                                class="mt-2 rounded-xl border border-blue-100 bg-blue-50 px-3 py-2 text-[10px] leading-5 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-300"
                            >
                                سعر الشراء الحالي هو متوسط تكلفة المخزون، ويتم تحديثه تلقائياً من المشتريات والمرتجعات. لذلك لا يمكن تعديله يدوياً بعد بدء حركات المخزون.
                            </div>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                سعر البيع
                                <span
                                    class="text-rose-500"
                                >
                                    *
                                </span>
                            </label>

                            <div class="relative">
                                <input
                                    v-model.number="form.selling_price"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 pl-16 text-lg font-black dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                />

                                <span
                                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400"
                                >
                                    شيكل
                                </span>
                            </div>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                أقل سعر بيع
                            </label>

                            <div class="relative">
                                <input
                                    v-model.number="form.minimum_selling_price"
                                    type="number"
                                    min="0"
                                    :max="sellingPrice"
                                    step="0.01"
                                    placeholder="اختياري"
                                    class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 pl-16 text-lg font-black dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                />

                                <span
                                    class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[10px] font-black text-slate-400"
                                >
                                    شيكل
                                </span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid gap-3 border-t border-slate-100 p-5 dark:border-slate-700 sm:grid-cols-3"
                    >
                        <div
                            class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900"
                        >
                            <p
                                class="text-[10px] text-slate-400"
                            >
                                الربح المتوقع / قطعة
                            </p>

                            <strong
                                dir="ltr"
                                class="mt-1 block text-right text-lg"
                                :class="
                                    profit >= 0
                                        ? 'text-emerald-600'
                                        : 'text-rose-600'
                                "
                            >
                                {{ money(profit) }}
                            </strong>
                        </div>

                        <div
                            class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900"
                        >
                            <p
                                class="text-[10px] text-slate-400"
                            >
                                هامش الربح
                            </p>

                            <strong
                                dir="ltr"
                                class="mt-1 block text-right text-lg"
                                :class="
                                    profitMargin >= 0
                                        ? 'text-emerald-600'
                                        : 'text-rose-600'
                                "
                            >
                                {{
                                    Number(
                                        profitMargin
                                    ).toLocaleString(
                                        'en-US',
                                        {
                                            maximumFractionDigits:
                                                2,
                                        }
                                    )
                                }}%
                            </strong>
                        </div>

                        <div
                            class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900"
                        >
                            <p
                                class="text-[10px] text-slate-400"
                            >
                                أقل هامش بيع
                            </p>

                            <strong
                                dir="ltr"
                                class="mt-1 block text-right text-lg text-slate-950 dark:text-white"
                            >
                                {{
                                    minimumSellingPrice === null
                                        ? '—'
                                        : money(
                                            minimumSellingPrice
                                            - purchasePrice
                                        )
                                }}
                            </strong>
                        </div>
                    </div>

                    <div
                        v-if="pricingWarning"
                        class="mx-5 mb-5 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs font-bold text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300"
                    >
                        {{ pricingWarning }}
                    </div>
                </section>

                <!-- Opening stock -->
                <section
                    v-if="!isEdit"
                    class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div
                            class="flex items-center gap-3"
                        >
                            <div
                                class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300"
                            >
                                <svg
                                    class="h-5 w-5"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="1.8"
                                        d="M4 7h16M6 3h12l2 4-2 4H6L4 7l2-4Zm0 8v10h12V11"
                                    />
                                </svg>
                            </div>

                            <div>
                                <h2
                                    class="font-black text-slate-950 dark:text-white"
                                >
                                    الرصيد الافتتاحي
                                </h2>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    اختياري — للبضاعة الموجودة قبل بدء استخدام النظام فقط.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            :disabled="
                                form.opening_stocks.length
                                >= warehouses.length
                            "
                            class="rounded-xl bg-amber-50 px-3 py-2 text-xs font-black text-amber-700 transition hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-40 dark:bg-amber-950/30 dark:text-amber-300"
                            @click="addOpeningStockRow"
                        >
                            + إضافة مخزن
                        </button>
                    </div>

                    <div
                        class="space-y-4 p-5"
                    >
                        <div
                            class="rounded-2xl border border-blue-100 bg-blue-50 p-4 text-xs leading-6 text-blue-700 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-300"
                        >
                            إذا كانت الكمية ناتجة عن شراء جديد، اترك هذا القسم فارغاً وأنشئ
                            <strong>
                                فاتورة شراء
                            </strong>
                            بعد حفظ المنتج. هذا يحافظ على التكلفة والحركة المالية والمورد بصورة صحيحة.
                        </div>

                        <div
                            v-if="
                                !form.opening_stocks.length
                            "
                            class="rounded-2xl border-2 border-dashed border-slate-200 px-6 py-8 text-center dark:border-slate-700"
                        >
                            <p
                                class="font-black text-slate-700 dark:text-slate-200"
                            >
                                لا يوجد رصيد افتتاحي
                            </p>

                            <p
                                class="mt-2 text-xs text-slate-500"
                            >
                                سيتم إنشاء المنتج برصيد 0. ويمكنك أيضاً إضافته مباشرة من شاشة فاتورة الشراء بدون الرجوع إلى قسم المنتجات.
                            </p>
                        </div>

                        <div
                            v-for="(
                                row,
                                index
                            ) in form.opening_stocks"
                            :key="index"
                            class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/60 sm:grid-cols-[1fr_180px_auto]"
                        >
                            <div>
                                <label
                                    class="mb-1 block text-xs font-black text-slate-600 dark:text-slate-300"
                                >
                                    المخزن
                                </label>

                                <select
                                    v-model="row.warehouse_id"
                                    class="w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                                >
                                    <option value="">
                                        اختر المخزن
                                    </option>

                                    <option
                                        v-for="warehouse in availableWarehousesForRow(index)"
                                        :key="warehouse.id"
                                        :value="warehouse.id"
                                    >
                                        {{ warehouse.name }}
                                        — {{ warehouse.type_label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label
                                    class="mb-1 block text-xs font-black text-slate-600 dark:text-slate-300"
                                >
                                    الكمية
                                </label>

                                <input
                                    v-model.number="row.quantity"
                                    type="number"
                                    min="0"
                                    step="1"
                                    class="w-full rounded-xl border-slate-300 bg-white text-sm font-black dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                                />
                            </div>

                            <div
                                class="flex items-end"
                            >
                                <button
                                    type="button"
                                    class="w-full rounded-xl bg-rose-50 px-3 py-2.5 text-xs font-black text-rose-700 hover:bg-rose-100 dark:bg-rose-950/30 dark:text-rose-300 sm:w-auto"
                                    @click="removeOpeningStockRow(index)"
                                >
                                    إزالة
                                </button>
                            </div>
                        </div>

                        <div
                            v-if="
                                form.opening_stocks.length
                            "
                            class="grid gap-3 sm:grid-cols-2"
                        >
                            <div
                                class="rounded-2xl bg-slate-50 p-4 dark:bg-slate-900"
                            >
                                <p
                                    class="text-[10px] text-slate-400"
                                >
                                    إجمالي الرصيد الافتتاحي
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-lg text-slate-950 dark:text-white"
                                >
                                    {{ number(openingStockTotal) }} قطعة
                                </strong>
                            </div>

                            <div
                                class="rounded-2xl bg-emerald-50 p-4 dark:bg-emerald-950/20"
                            >
                                <p
                                    class="text-[10px] text-emerald-600"
                                >
                                    القيمة الافتتاحية بالتكلفة
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-lg text-emerald-700 dark:text-emerald-300"
                                >
                                    {{ money(openingInventoryValue) }}
                                </strong>
                            </div>
                        </div>

                        <p
                            v-if="openingStockError"
                            class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                        >
                            {{ openingStockError }}
                        </p>
                    </div>
                </section>

                <section
                    v-else
                    class="rounded-[26px] border border-blue-200 bg-blue-50 p-5 shadow-sm dark:border-blue-900/50 dark:bg-blue-950/20"
                >
                    <h2
                        class="font-black text-blue-900 dark:text-blue-200"
                    >
                        تعديل المخزون يتم من مركز المخزون
                    </h2>

                    <p
                        class="mt-2 text-xs leading-6 text-blue-700 dark:text-blue-300"
                    >
                        لا نسمح بتغيير الرصيد من تعديل المنتج لأن ذلك يتجاوز سجل الحركات.
                        استخدم الإضافة أو الخصم أو النقل أو الجرد من صفحة المخزون.
                    </p>

                    <Link
                        :href="route('inventory.index')"
                        class="mt-3 inline-flex rounded-xl bg-blue-600 px-4 py-2 text-xs font-black text-white"
                    >
                        فتح المخزون
                    </Link>
                </section>

                <!-- Inventory settings -->
                <section
                    class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="flex items-center gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-950/30 dark:text-violet-300"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M12 3v18m9-9H3M5 5l14 14M19 5 5 19"
                                />
                            </svg>
                        </div>

                        <div>
                            <h2
                                class="font-black text-slate-950 dark:text-white"
                            >
                                إعدادات المخزون والعرض
                            </h2>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                التنبيه والموقع والحالة التشغيلية للمنتج.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid gap-4 p-5 sm:grid-cols-2"
                    >
                        <div>
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                حد تنبيه المخزون
                                <span
                                    class="text-rose-500"
                                >
                                    *
                                </span>
                            </label>

                            <input
                                v-model.number="form.low_stock_threshold"
                                type="number"
                                min="0"
                                step="1"
                                class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />

                            <p
                                class="mt-1 text-[10px] leading-5 text-slate-400"
                            >
                                عندما يصبح إجمالي الرصيد أقل من أو يساوي هذا الرقم يظهر المنتج كمخزون منخفض.
                            </p>
                        </div>

                        <div>
                            <label
                                class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                            >
                                الموقع / الرف
                            </label>

                            <input
                                v-model.trim="form.location"
                                type="text"
                                maxlength="255"
                                placeholder="مثال: A-03"
                                class="block w-full rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            />
                        </div>
                    </div>
                </section>

                <!-- Image & description -->
                <section
                    class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="flex items-center gap-3 border-b border-slate-200 px-5 py-4 dark:border-slate-700"
                    >
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl bg-pink-50 text-pink-600 dark:bg-pink-950/30 dark:text-pink-300"
                        >
                            <svg
                                class="h-5 w-5"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="1.8"
                                    d="M4 16l4-4a2 2 0 0 1 3 0l2 2 2-2a2 2 0 0 1 3 0l2 2M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Zm9-11h.01"
                                />
                            </svg>
                        </div>

                        <div>
                            <h2
                                class="font-black text-slate-950 dark:text-white"
                            >
                                الصورة والتفاصيل
                            </h2>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                معلومات إضافية تساعد في التعرف على المنتج.
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid gap-5 p-5 lg:grid-cols-[220px_1fr]"
                    >
                        <div>
                            <div
                                class="overflow-hidden rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900"
                            >
                                <div
                                    class="aspect-square overflow-hidden rounded-xl bg-white dark:bg-slate-800"
                                >
                                    <img
                                        v-if="imagePreview"
                                        :src="imagePreview"
                                        alt="معاينة المنتج"
                                        class="h-full w-full object-cover"
                                    />

                                    <div
                                        v-else
                                        class="flex h-full items-center justify-center text-slate-300"
                                    >
                                        <svg
                                            class="h-14 w-14"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.4"
                                                d="M4 16l4-4a2 2 0 0 1 3 0l2 2 2-2a2 2 0 0 1 3 0l2 2M6 20h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"
                                            />
                                        </svg>
                                    </div>
                                </div>

                                <input
                                    ref="imageInput"
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                                    class="mt-3 block w-full text-xs text-slate-500 file:ml-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-xs file:font-black file:text-blue-700 dark:text-slate-400 dark:file:bg-blue-950/30 dark:file:text-blue-300"
                                    @change="handleImageUpload"
                                />

                                <button
                                    v-if="form.image"
                                    type="button"
                                    class="mt-2 text-xs font-black text-rose-600"
                                    @click="removeImage"
                                >
                                    إلغاء الصورة الجديدة
                                </button>
                            </div>

                            <p
                                class="mt-2 text-[10px] leading-5 text-slate-400"
                            >
                                JPG / PNG / WebP — حتى 2MB.
                            </p>
                        </div>

                        <div
                            class="space-y-4"
                        >
                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                                >
                                    وصف المنتج
                                </label>

                                <textarea
                                    v-model.trim="form.description"
                                    rows="4"
                                    maxlength="1000"
                                    placeholder="المواصفات أو الاستخدام أو أي وصف مهم..."
                                    class="block w-full resize-none rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                ></textarea>
                            </div>

                            <div>
                                <label
                                    class="mb-1.5 block text-sm font-black text-slate-700 dark:text-slate-200"
                                >
                                    ملاحظات داخلية
                                </label>

                                <textarea
                                    v-model.trim="form.notes"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="ملاحظات إدارية لا يلزم أن تظهر في البيع..."
                                    class="block w-full resize-none rounded-xl border-slate-300 bg-white px-3 py-3 text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                ></textarea>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Mobile action -->
                <div
                    class="flex flex-col-reverse gap-2 xl:hidden"
                >
                    <Link
                        :href="route('products.index')"
                        class="rounded-xl border border-slate-300 px-4 py-3 text-center text-sm font-black text-slate-700 dark:border-slate-700 dark:text-slate-200"
                    >
                        إلغاء
                    </Link>

                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="rounded-xl bg-blue-600 px-4 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'جاري الحفظ...'
                                : isEdit
                                    ? 'حفظ التعديلات'
                                    : 'إنشاء المنتج'
                        }}
                    </button>
                </div>
            </main>

            <!-- Sidebar -->
            <aside
                class="sticky top-5 hidden space-y-4 xl:block"
            >
                <section
                    class="overflow-hidden rounded-[26px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="bg-gradient-to-l from-slate-950 via-blue-950 to-blue-700 p-5 text-white"
                    >
                        <div
                            class="flex items-center justify-between gap-3"
                        >
                            <span
                                class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-black"
                            >
                                Product Preview
                            </span>

                            <span
                                class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                :class="
                                    form.is_active
                                        ? 'bg-emerald-400/20 text-emerald-100'
                                        : 'bg-white/10 text-slate-200'
                                "
                            >
                                {{
                                    form.is_active
                                        ? 'نشط'
                                        : 'غير نشط'
                                }}
                            </span>
                        </div>

                        <h3
                            class="mt-4 truncate text-lg font-black"
                        >
                            {{ form.name || 'اسم المنتج' }}
                        </h3>

                        <p
                            dir="ltr"
                            class="mt-1 truncate text-right text-xs text-blue-100"
                        >
                            {{ form.code || 'سيتم توليد الكود تلقائياً' }}
                        </p>
                    </div>

                    <div
                        class="space-y-3 p-4"
                    >
                        <div
                            class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3 dark:border-slate-700"
                        >
                            <span
                                class="text-xs text-slate-500"
                            >
                                الفئة
                            </span>

                            <strong
                                class="text-xs text-slate-900 dark:text-white"
                            >
                                {{ selectedCategory?.name || '—' }}
                            </strong>
                        </div>

                        <div
                            class="grid grid-cols-2 gap-2"
                        >
                            <div
                                class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900"
                            >
                                <p
                                    class="text-[10px] text-slate-400"
                                >
                                    التكلفة
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-xs"
                                >
                                    {{ money(purchasePrice) }}
                                </strong>
                            </div>

                            <div
                                class="rounded-xl bg-blue-50 p-3 dark:bg-blue-950/20"
                            >
                                <p
                                    class="text-[10px] text-blue-500"
                                >
                                    البيع
                                </p>

                                <strong
                                    dir="ltr"
                                    class="mt-1 block text-right text-xs text-blue-700 dark:text-blue-300"
                                >
                                    {{ money(sellingPrice) }}
                                </strong>
                            </div>
                        </div>

                        <div
                            class="rounded-xl p-3"
                            :class="
                                profit >= 0
                                    ? 'bg-emerald-50 dark:bg-emerald-950/20'
                                    : 'bg-rose-50 dark:bg-rose-950/20'
                            "
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <span
                                    class="text-xs"
                                    :class="
                                        profit >= 0
                                            ? 'text-emerald-700 dark:text-emerald-300'
                                            : 'text-rose-700 dark:text-rose-300'
                                    "
                                >
                                    الربح المتوقع
                                </span>

                                <strong
                                    dir="ltr"
                                    :class="
                                        profit >= 0
                                            ? 'text-emerald-700 dark:text-emerald-300'
                                            : 'text-rose-700 dark:text-rose-300'
                                    "
                                >
                                    {{ money(profit) }}
                                </strong>
                            </div>
                        </div>

                        <div
                            v-if="!isEdit"
                            class="rounded-xl bg-amber-50 p-3 dark:bg-amber-950/20"
                        >
                            <p
                                class="text-[10px] text-amber-600"
                            >
                                الرصيد الافتتاحي
                            </p>

                            <strong
                                dir="ltr"
                                class="mt-1 block text-right text-sm text-amber-700 dark:text-amber-300"
                            >
                                {{ number(openingStockTotal) }} قطعة
                            </strong>

                            <p
                                dir="ltr"
                                class="mt-1 text-right text-[10px] text-amber-600"
                            >
                                {{ money(openingInventoryValue) }}
                            </p>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="flex items-center justify-between gap-3"
                    >
                        <div>
                            <h3
                                class="text-sm font-black text-slate-950 dark:text-white"
                            >
                                جاهزية النموذج
                            </h3>

                            <p
                                class="mt-1 text-[10px] text-slate-500"
                            >
                                مراجعة سريعة قبل الحفظ
                            </p>
                        </div>

                        <strong
                            dir="ltr"
                            class="text-sm text-blue-600"
                        >
                            {{ completionPercent }}%
                        </strong>
                    </div>

                    <div
                        class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-700"
                    >
                        <div
                            class="h-full rounded-full bg-blue-600 transition-all duration-300"
                            :style="{
                                width:
                                    `${completionPercent}%`,
                            }"
                        ></div>
                    </div>

                    <div
                        class="mt-4 space-y-2"
                    >
                        <div
                            v-for="item in completionItems"
                            :key="item.label"
                            class="flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 dark:bg-slate-900"
                        >
                            <span
                                class="text-xs text-slate-600 dark:text-slate-300"
                            >
                                {{ item.label }}
                            </span>

                            <span
                                class="text-xs font-black"
                                :class="
                                    item.done
                                        ? 'text-emerald-600'
                                        : 'text-slate-400'
                                "
                            >
                                {{ item.done ? '✓' : '—' }}
                            </span>
                        </div>
                    </div>
                </section>

                <section
                    class="rounded-[26px] border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800"
                >
                    <div
                        class="flex items-center justify-between gap-4"
                    >
                        <div>
                            <h3
                                class="text-sm font-black text-slate-950 dark:text-white"
                            >
                                حالة المنتج
                            </h3>

                            <p
                                class="mt-1 text-[10px] text-slate-500"
                            >
                                المنتج غير النشط لا يظهر في عمليات البيع الجديدة.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="relative h-7 w-12 shrink-0 rounded-full transition"
                            :class="
                                form.is_active
                                    ? 'bg-blue-600'
                                    : 'bg-slate-300 dark:bg-slate-600'
                            "
                            @click="
                                form.is_active =
                                    !form.is_active
                            "
                        >
                            <span
                                class="absolute top-1 h-5 w-5 rounded-full bg-white shadow transition"
                                :class="
                                    form.is_active
                                        ? 'right-6'
                                        : 'right-1'
                                "
                            ></span>
                        </button>
                    </div>
                </section>

                <section
                    class="rounded-[26px] bg-slate-950 p-4 text-white"
                >
                    <div
                        v-if="baseFormError"
                        class="mb-3 rounded-xl bg-rose-500/10 p-3 text-xs leading-5 text-rose-200"
                    >
                        {{ baseFormError }}
                    </div>

                    <button
                        type="submit"
                        :disabled="!canSubmit"
                        class="w-full rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-500 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            form.processing
                                ? 'جاري الحفظ...'
                                : isEdit
                                    ? 'حفظ التعديلات'
                                    : 'إنشاء المنتج'
                        }}
                    </button>

                    <Link
                        :href="route('products.index')"
                        class="mt-2 block w-full rounded-xl border border-slate-700 px-5 py-3 text-center text-xs font-black text-slate-300 transition hover:bg-slate-800"
                    >
                        إلغاء
                    </Link>
                </section>
            </aside>
        </form>
    </AuthenticatedLayout>
</template>
