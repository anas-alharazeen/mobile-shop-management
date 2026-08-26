<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    order: { type: Object, required: true },
    products: { type: Array, default: () => [] },
    suppliers: { type: Array, default: () => [] },
    financialAccounts: { type: Array, default: () => [] },
    paymentMethods: { type: Object, default: () => ({}) },
    maintenanceWarehouse: { type: Object, default: () => ({}) },
});

const nowLocal = () => {
    const d = new Date(); const off = d.getTimezoneOffset();
    return new Date(d.getTime() - off * 60000).toISOString().slice(0, 16);
};

const detailsOpen = ref(false);
const partModal = ref(false);
const partSource = ref('stock');
const partProductSearch = ref('');
const purchaseTarget = ref(null);
const returnExternalTarget = ref(null);
const revertStockTarget = ref(null);
const paymentModal = ref(false);
const deliverModal = ref(false);
const cancelModal = ref(false);

const intakeImageModal = ref(null);
const lockCodeVisible = ref(false);
const lockCodeCopied = ref(false);

const attachmentStage = attachment =>
    attachment?.stage?.value
    ?? attachment?.stage
    ?? '';

const receivedAttachments = computed(() =>
    (props.order.attachments || [])
        .filter(attachment => {
            const stage = attachmentStage(attachment);

            return !stage
                || stage === 'received';
        })
);

const attachmentUrl = attachment => {
    const path = String(
        attachment?.file_path
        || ''
    );

    if (!path) {
        return '';
    }

    if (
        path.startsWith('http://')
        || path.startsWith('https://')
        || path.startsWith('/storage/')
    ) {
        return path;
    }

    return `/storage/${path.replace(/^\/+/, '')}`;
};

const lockCode = computed(() =>
    props.order.lock_code_decrypted
    || ''
);

const copyLockCode = async () => {
    if (!lockCode.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(
            lockCode.value
        );

        lockCodeCopied.value = true;

        window.setTimeout(
            () => {
                lockCodeCopied.value = false;
            },
            1600
        );
    } catch (_) {
        lockCodeCopied.value = false;
    }
};

const detailsForm = useForm({
    inspection_result: props.order.inspection_result || '',
    fault_cause: props.order.fault_cause || '',
    repair_action: props.order.repair_action || '',
    technician_name: props.order.technician_name || '',
    agreed_price: Number(props.order.agreed_price ?? props.order.total_amount ?? 0),
    expected_delivery_date: props.order.expected_delivery_date?.slice(0, 10) || '',
    internal_notes: props.order.internal_notes || '',
});

const stockForm = useForm({ product_id: '', quantity: 1, unit_price: '' });
const externalForm = useForm({ part_name: '', quantity: 1, supplier_id: '', purchase_from: '', supplier_phone: '', customer_unit_price: '', notes: '' });
const purchaseForm = useForm({ supplier_id: '', purchase_from: '', supplier_phone: '', purchase_reference: '', unit_purchase_price: '', customer_unit_price: '', financial_account_id: '', purchased_at: nowLocal(), notes: '' });
const returnExternalForm = useForm({ reason: '' });
const revertStockForm = useForm({ reason: '' });
const readyForm = useForm({ internal_notes: props.order.internal_notes || '' });
const paymentForm = useForm({ amount: Number(props.order.remaining_amount || 0), payment_method: 'cash', financial_account_id: '', bank_or_app_name: '', transaction_reference: '', paid_at: nowLocal(), notes: '' });
const deliverForm = useForm({
    payment_amount: Number(props.order.remaining_amount || 0),
    payment_method: 'cash',
    financial_account_id: '',
    bank_or_app_name: '',
    transaction_reference: '',
    paid_at: nowLocal(),
    payment_notes: '',
    allow_partial_payment: false,
});
const cancelForm = useForm({ reason: '' });

const status = computed(() => props.order.status);
const isEditable = computed(() => ['received', 'in_progress'].includes(status.value));
const isReady = computed(() => status.value === 'ready');
const isFinished = computed(() => ['delivered', 'cancelled'].includes(status.value));
const committedStockParts = computed(() => (props.order.parts || []).filter(part => part.is_committed));
const legacyPendingStockParts = computed(() => (props.order.parts || []).filter(part => !part.is_committed));
const externalParts = computed(() => props.order.external_parts || []);
const pendingExternal = computed(() => externalParts.value.filter(part => part.status === 'draft'));
const purchasedExternal = computed(() => externalParts.value.filter(part => part.status === 'purchased'));
const returnedExternal = computed(() => externalParts.value.filter(part => part.status === 'returned'));

const statusMeta = computed(() => ({
    received: ['مستلم', 'bg-blue-100 text-blue-700'],
    in_progress: ['قيد التنفيذ', 'bg-amber-100 text-amber-700'],
    ready: ['جاهز للاستلام', 'bg-emerald-100 text-emerald-700'],
    delivered: ['تم التسليم', 'bg-slate-100 text-slate-700'],
    cancelled: ['ملغي', 'bg-rose-100 text-rose-700'],
}[status.value] || [status.value, 'bg-slate-100 text-slate-700']));

const accountType = account =>
    account?.type?.value
    ?? account?.type
    ?? '';

const compatibleAccounts = method =>
    props.financialAccounts.filter(
        account =>
            accountType(account)
            === ({
                cash: 'cash',
                bank_transfer: 'bank',
                banking_app: 'banking_app',
            }[method] || '')
    );

const selectedDeliveryAccount = computed(() =>
    props.financialAccounts.find(
        account =>
            Number(account.id)
            === Number(
                deliverForm.financial_account_id
            )
    ) || null
);

const deliveryRemainingAfterPayment = computed(() =>
    Math.max(
        0,
        Number(
            props.order.remaining_amount
            || 0
        )
        - Number(
            deliverForm.payment_amount
            || 0
        )
    )
);

const deliveryClientError = computed(() => {
    const amount =
        Number(
            deliverForm.payment_amount
            || 0
        );

    const remaining =
        Number(
            props.order.remaining_amount
            || 0
        );

    if (amount < 0) {
        return 'المبلغ المدفوع لا يمكن أن يكون سالباً.';
    }

    if (amount > remaining + 0.00001) {
        return 'المبلغ المدفوع أكبر من المبلغ المتبقي.';
    }

    if (
        amount > 0
        && !deliverForm.payment_method
    ) {
        return 'اختر طريقة الدفع.';
    }

    if (
        amount > 0
        && !compatibleAccounts(
            deliverForm.payment_method
        ).length
    ) {
        return 'لا يوجد حساب مالي نشط ومتوافق مع طريقة الدفع المختارة.';
    }

    if (
        amount > 0
        && !deliverForm.financial_account_id
    ) {
        return 'اختر الحساب المالي الذي ستدخل إليه الدفعة.';
    }

    if (
        deliveryRemainingAfterPayment.value > 0.00001
        && !deliverForm.allow_partial_payment
    ) {
        return 'يوجد مبلغ متبقٍ. أكمل الدفع أو فعّل السماح بالتسليم مع بقاء الدين.';
    }

    return '';
});

watch(
    () => paymentForm.payment_method,
    method => {
        const accounts =
            compatibleAccounts(method);

        if (
            !accounts.some(
                account =>
                    Number(account.id)
                    === Number(
                        paymentForm
                            .financial_account_id
                    )
            )
        ) {
            paymentForm.financial_account_id =
                accounts[0]?.id
                || '';
        }
    },
    {
        immediate: true,
    }
);

watch(
    () => purchaseForm.supplier_id,
    () => {
        if (!selectedPurchaseSupplier.value) return;

        purchaseForm.purchase_from =
            selectedPurchaseSupplier.value.company_name
            || selectedPurchaseSupplier.value.name
            || purchaseForm.purchase_from
            || '';

        purchaseForm.supplier_phone =
            selectedPurchaseSupplier.value.phone
            || purchaseForm.supplier_phone
            || '';
    }
);

watch(
    () => paymentForm.financial_account_id,
    () => {
        if (
            ['bank_transfer', 'banking_app'].includes(paymentForm.payment_method)
            && selectedPaymentAccount.value
        ) {
            paymentForm.bank_or_app_name =
                selectedPaymentAccount.value.name;
        } else {
            paymentForm.bank_or_app_name = '';
        }
    }
);

watch(
    () => deliverForm.payment_method,
    method => {
        const accounts =
            compatibleAccounts(method);

        if (
            !accounts.some(
                account =>
                    Number(account.id)
                    === Number(
                        deliverForm
                            .financial_account_id
                    )
            )
        ) {
            deliverForm.financial_account_id =
                accounts[0]?.id
                || '';
        }
    },
    {
        immediate: true,
    }
);

watch(
    () => deliverForm.financial_account_id,
    () => {
        if (
            ['bank_transfer', 'banking_app']
                .includes(
                    deliverForm.payment_method
                )
            && selectedDeliveryAccount.value
        ) {
            deliverForm.bank_or_app_name =
                selectedDeliveryAccount.value
                    .name;
        } else {
            deliverForm.bank_or_app_name =
                '';
        }
    }
);

watch(
    () => stockForm.product_id,
    id => {
        const product =
            props.products.find(
                item =>
                    Number(item.id)
                    === Number(id)
            );

        if (product) {
            stockForm.unit_price =
                Number(
                    product.selling_price
                    || 0
                );
        }
    }
);

const selectedStock = computed(() => props.products.find(p => Number(p.id) === Number(stockForm.product_id)) || null);

const filteredPartProducts = computed(() => {
    const query = String(partProductSearch.value || '')
        .trim()
        .toLowerCase();

    return props.products.filter(product => {
        if (!query) return true;

        return [
            product.name,
            product.code,
            product.barcode,
            product.brand,
            product.model,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()
            .includes(query);
    });
});

const stockPartCustomerTotal = computed(() =>
    Number(stockForm.quantity || 0)
    * Number(stockForm.unit_price || 0)
);

const externalPartCustomerTotal = computed(() =>
    Number(externalForm.quantity || 0)
    * Number(externalForm.customer_unit_price || 0)
);

const selectedPaymentAccount = computed(() =>
    props.financialAccounts.find(
        account =>
            Number(account.id)
            === Number(paymentForm.financial_account_id)
    ) || null
);

const paymentRemainingAfter = computed(() =>
    Math.max(
        0,
        Number(props.order.remaining_amount || 0)
        - Number(paymentForm.amount || 0)
    )
);

const paymentAccountBalanceAfter = computed(() =>
    Number(selectedPaymentAccount.value?.current_balance || 0)
    + Number(paymentForm.amount || 0)
);

const selectedPurchaseSupplier = computed(() =>
    props.suppliers.find(
        supplier =>
            Number(supplier.id)
            === Number(purchaseForm.supplier_id)
    ) || null
);

const selectedPurchaseAccount = computed(() =>
    props.financialAccounts.find(
        account =>
            Number(account.id)
            === Number(purchaseForm.financial_account_id)
    ) || null
);

const purchaseQuantity = computed(() =>
    Number(purchaseTarget.value?.quantity || 0)
);

const purchaseTotalCost = computed(() =>
    Math.max(
        0,
        purchaseQuantity.value
        * Number(purchaseForm.unit_purchase_price || 0)
    )
);

const purchaseCustomerTotal = computed(() =>
    Math.max(
        0,
        purchaseQuantity.value
        * Number(purchaseForm.customer_unit_price || 0)
    )
);

const purchaseAccountBalanceAfter = computed(() =>
    Number(selectedPurchaseAccount.value?.current_balance || 0)
    - purchaseTotalCost.value
);

const purchaseClientError = computed(() => {
    if (!purchaseTarget.value) return '';

    if (
        !purchaseForm.supplier_id
        && !String(purchaseForm.purchase_from || '').trim()
    ) {
        return 'اختر مورداً مسجلاً أو اكتب اسم المحل الذي اشتريت منه القطعة.';
    }

    if (Number(purchaseForm.unit_purchase_price || 0) <= 0) {
        return 'أدخل سعر شراء الوحدة بشكل صحيح.';
    }

    if (!purchaseForm.financial_account_id) {
        return 'اختر الحساب المالي الذي تم الدفع منه.';
    }

    if (!purchaseForm.purchased_at) {
        return 'حدد تاريخ ووقت شراء القطعة.';
    }

    if (
        selectedPurchaseAccount.value
        && purchaseAccountBalanceAfter.value < -0.00001
    ) {
        return 'رصيد الحساب المالي غير كافٍ لتسجيل شراء هذه القطعة.';
    }

    return '';
});

const paymentClientError = computed(() => {
    const amount = Number(paymentForm.amount || 0);
    const remaining = Number(props.order.remaining_amount || 0);

    if (amount <= 0) {
        return 'أدخل مبلغ دفعة أكبر من صفر.';
    }

    if (amount > remaining + 0.00001) {
        return 'المبلغ المدفوع أكبر من المبلغ المتبقي على الطلب.';
    }

    if (!paymentForm.payment_method) {
        return 'اختر طريقة الدفع.';
    }

    if (!compatibleAccounts(paymentForm.payment_method).length) {
        return 'لا يوجد حساب مالي نشط ومتوافق مع طريقة الدفع المختارة.';
    }

    if (!paymentForm.financial_account_id) {
        return 'اختر الحساب المالي الذي ستدخل إليه الدفعة.';
    }

    return '';
});

const orderCost = computed(() => Number(props.order.parts_cost || 0));
const orderProfit = computed(() => Number(props.order.total_amount || 0) - orderCost.value);

const saveDetails = () => detailsForm.patch(route('repairs.update-details', props.order.id), { preserveScroll: true, onSuccess: () => { detailsOpen.value = false; } });

const openPartModal = () => {
    stockForm.clearErrors();
    externalForm.clearErrors();

    partSource.value = 'stock';
    partProductSearch.value = '';

    stockForm.reset();
    stockForm.quantity = 1;

    externalForm.reset();
    externalForm.quantity = 1;

    partModal.value = true;
};

const addStock = () =>
    stockForm.post(
        route('repairs.parts.stock', props.order.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                stockForm.reset();
                stockForm.quantity = 1;
                partProductSearch.value = '';
                partModal.value = false;
            },
        }
    );

const addExternal = () =>
    externalForm.post(
        route('repairs.parts.external', props.order.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                externalForm.reset();
                externalForm.quantity = 1;
                partModal.value = false;
            },
        }
    );

const openPurchase = part => {
    purchaseTarget.value = part;
    purchaseForm.reset();
    purchaseForm.clearErrors();
    purchaseForm.purchased_at = nowLocal();
    purchaseForm.supplier_id = part.supplier_id || '';
    purchaseForm.purchase_from = part.purchase_from || '';
    purchaseForm.supplier_phone = part.supplier_phone || '';
    purchaseForm.customer_unit_price = Number(part.customer_unit_price || 0) || '';
    purchaseForm.unit_purchase_price = '';
    purchaseForm.financial_account_id = '';
    purchaseForm.purchase_reference = '';
};

const completePurchase = () => {
    purchaseForm.clearErrors();

    if (purchaseClientError.value) {
        purchaseForm.setError('purchase', purchaseClientError.value);
        return;
    }

    purchaseForm.post(
        route('repairs.external-parts.purchase', purchaseTarget.value.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                purchaseTarget.value = null;
            },
        }
    );
};
const deleteExternalDraft = part => externalForm.delete(route('repairs.external-parts.destroy', part.id), { preserveScroll: true });
const openReturnExternal = part => { returnExternalTarget.value = part; returnExternalForm.reset(); };
const returnExternal = () => returnExternalForm.post(route('repairs.external-parts.return', returnExternalTarget.value.id), { preserveScroll: true, onSuccess: () => { returnExternalTarget.value = null; } });
const openRevertStock = part => { revertStockTarget.value = part; revertStockForm.reset(); };
const revertStock = () => revertStockForm.post(route('repairs.parts.stock-revert', revertStockTarget.value.id), { preserveScroll: true, onSuccess: () => { revertStockTarget.value = null; } });
const markReady = () => readyForm.post(route('repairs.mark-ready', props.order.id), { preserveScroll: true });
const openPaymentModal = () => {
    paymentForm.clearErrors();
    paymentForm.amount = Number(props.order.remaining_amount || 0);
    paymentForm.paid_at = nowLocal();
    paymentForm.transaction_reference = '';
    paymentForm.notes = '';

    const accounts = compatibleAccounts(paymentForm.payment_method);

    if (
        !accounts.some(
            account =>
                Number(account.id)
                === Number(paymentForm.financial_account_id)
        )
    ) {
        paymentForm.financial_account_id = accounts[0]?.id || '';
    }

    paymentModal.value = true;
};

const setPaymentAmount = mode => {
    const remaining = Number(props.order.remaining_amount || 0);

    if (mode === 'half') {
        paymentForm.amount = Number((remaining / 2).toFixed(2));
        return;
    }

    paymentForm.amount = remaining;
};

const addPayment = () => {
    paymentForm.clearErrors();

    if (paymentClientError.value) {
        paymentForm.setError('payment', paymentClientError.value);
        return;
    }

    paymentForm.post(
        route('repairs.add-payment', props.order.id),
        {
            preserveScroll: true,
            onSuccess: () => {
                paymentModal.value = false;
            },
        }
    );
};
const openDeliverModal = () => {
    deliverForm.clearErrors();

    deliverForm.payment_amount =
        Number(
            props.order.remaining_amount
            || 0
        );

    deliverForm.allow_partial_payment =
        false;

    deliverForm.paid_at =
        nowLocal();

    const accounts =
        compatibleAccounts(
            deliverForm.payment_method
        );

    if (
        !accounts.some(
            account =>
                Number(account.id)
                === Number(
                    deliverForm
                        .financial_account_id
                )
        )
    ) {
        deliverForm.financial_account_id =
            accounts[0]?.id
            || '';
    }

    deliverModal.value =
        true;
};

const deliver = () => {
    deliverForm.clearErrors();

    if (
        deliveryClientError.value
    ) {
        deliverForm.setError(
            'delivery',
            deliveryClientError.value
        );

        return;
    }

    deliverForm.post(
        route(
            'repairs.deliver',
            props.order.id
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                deliverModal.value =
                    false;
            },
        }
    );
};
const cancelOrder = () => cancelForm.post(route('repairs.cancel', props.order.id), { preserveScroll: true, onSuccess: () => { cancelModal.value = false; } });

const money = value => `${Number(value || 0).toLocaleString('ar-PS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} شيكل`;
const date = value => value ? new Intl.DateTimeFormat('ar-PS', { dateStyle: 'medium' }).format(new Date(value)) : '—';
const dateTime = value => value
    ? new Intl.DateTimeFormat(
        'ar-PS',
        {
            dateStyle: 'medium',
            timeStyle: 'short',
        }
    ).format(new Date(value))
    : '—';
</script>

<template>
    <Head :title="order.order_number" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2"><h1 class="text-2xl font-black text-slate-950 dark:text-white">{{ order.order_number }}</h1><span class="rounded-full px-3 py-1 text-xs font-black" :class="statusMeta[1]">{{ statusMeta[0] }}</span><span v-if="order.sub_status === 'waiting_part'" class="rounded-full bg-orange-100 px-3 py-1 text-xs font-black text-orange-700">بانتظار قطعة خارجية</span></div>
                    <p class="mt-1 text-sm text-slate-500">{{ order.customer_name }} · {{ order.brand }} {{ order.model }} · {{ order.customer_phone }}</p>
                </div>
                <div class="flex flex-wrap gap-2"><a :href="`tel:${order.customer_phone}`" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-black text-white">اتصال بالعميل</a><Link :href="route('repairs.print', order.id)" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300">طباعة</Link><Link :href="route('repairs.index')" class="rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300">رجوع</Link></div>
            </div>
        </template>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_350px]">
            <div class="space-y-5">
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="border-b border-slate-100 bg-gradient-to-l from-blue-50/80 to-white px-5 py-5 dark:border-slate-800 dark:from-blue-950/20 dark:to-slate-900 sm:px-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-lg text-white shadow-lg shadow-blue-600/20">
                                    📱
                                </div>

                                <div>
                                    <h2 class="text-base font-black text-slate-950 dark:text-white">
                                        بيانات الجهاز عند الاستلام
                                    </h2>

                                    <p class="mt-1 text-xs text-slate-500">
                                        تفاصيل الجهاز وحالته والملحقات والصور المسجلة عند الاستلام.
                                    </p>
                                </div>
                            </div>

                            <button
                                v-if="isEditable"
                                type="button"
                                class="rounded-xl border border-blue-200 bg-white px-3.5 py-2 text-xs font-black text-blue-700 shadow-sm transition hover:bg-blue-50 dark:border-blue-900/50 dark:bg-slate-900 dark:text-blue-300"
                                @click="detailsOpen = true"
                            >
                                تعديل التشخيص والسعر
                            </button>
                        </div>
                    </div>

                    <div class="space-y-6 p-5 sm:p-6">
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60">
                                <p class="text-[10px] font-black text-slate-400">نوع الجهاز</p>
                                <strong class="mt-1.5 block text-sm text-slate-950 dark:text-white">
                                    {{ order.device_type || '—' }}
                                </strong>
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60">
                                <p class="text-[10px] font-black text-slate-400">الماركة / الموديل</p>
                                <strong class="mt-1.5 block text-sm text-slate-950 dark:text-white">
                                    {{ order.brand || '—' }} {{ order.model || '' }}
                                </strong>
                            </div>

                            <div class="rounded-2xl border border-slate-100 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60">
                                <p class="text-[10px] font-black text-slate-400">اللون</p>
                                <strong class="mt-1.5 block text-sm text-slate-950 dark:text-white">
                                    {{ order.color || '—' }}
                                </strong>
                            </div>

                            <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4 dark:border-violet-900/40 dark:bg-violet-950/20">
                                <p class="text-[10px] font-black text-violet-500">السعر المتفق</p>
                                <strong class="mt-1.5 block text-sm text-violet-800 dark:text-violet-200">
                                    {{ money(order.agreed_price ?? order.total_amount) }}
                                </strong>
                            </div>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <article class="rounded-2xl border border-amber-100 bg-amber-50/60 p-4 dark:border-amber-900/40 dark:bg-amber-950/15">
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-amber-100 text-sm dark:bg-amber-950/40">🛡️</span>
                                    <div>
                                        <p class="text-xs font-black text-amber-900 dark:text-amber-200">الحالة الخارجية عند الاستلام</p>
                                        <p class="mt-0.5 text-[10px] text-amber-700/70 dark:text-amber-300/70">توثيق حالة الجهاز قبل الصيانة</p>
                                    </div>
                                </div>

                                <p class="whitespace-pre-line text-sm leading-7 text-slate-700 dark:text-slate-200">
                                    {{ order.device_condition || 'لم يتم تسجيل ملاحظات على الحالة الخارجية.' }}
                                </p>
                            </article>

                            <article class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/15">
                                <div class="mb-2 flex items-center gap-2">
                                    <span class="flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-100 text-sm dark:bg-emerald-950/40">🎒</span>
                                    <div>
                                        <p class="text-xs font-black text-emerald-900 dark:text-emerald-200">الملحقات المستلمة</p>
                                        <p class="mt-0.5 text-[10px] text-emerald-700/70 dark:text-emerald-300/70">كل ما استلمه المحل مع الجهاز</p>
                                    </div>
                                </div>

                                <p class="whitespace-pre-line text-sm leading-7 text-slate-700 dark:text-slate-200">
                                    {{ order.received_accessories || 'لم يتم تسجيل ملحقات.' }}
                                </p>
                            </article>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-[.8fr_1.2fr]">
                            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-800/50">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-black text-slate-700 dark:text-slate-200">رمز قفل الجهاز</p>
                                        <p class="mt-1 text-[10px] leading-5 text-slate-400">
                                            {{ status === 'delivered' && !lockCode ? 'يتم مسح رمز القفل تلقائياً عند التسليم.' : 'يظهر فقط داخل تفاصيل طلب الصيانة.' }}
                                        </p>
                                    </div>

                                    <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-200 text-sm dark:bg-slate-700">🔐</span>
                                </div>

                                <div
                                    v-if="lockCode"
                                    class="mt-4 flex items-center gap-2"
                                >
                                    <div
                                        dir="ltr"
                                        class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-white px-3 py-3 text-center font-mono text-base font-black tracking-wider text-slate-950 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                                    >
                                        {{ lockCodeVisible ? lockCode : '••••••••' }}
                                    </div>

                                    <button
                                        type="button"
                                        class="rounded-xl border border-slate-200 bg-white px-3 py-3 text-xs font-black text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300"
                                        @click="lockCodeVisible = !lockCodeVisible"
                                    >
                                        {{ lockCodeVisible ? 'إخفاء' : 'إظهار' }}
                                    </button>

                                    <button
                                        type="button"
                                        class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-3 text-xs font-black text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-300"
                                        @click="copyLockCode"
                                    >
                                        {{ lockCodeCopied ? 'تم النسخ' : 'نسخ' }}
                                    </button>
                                </div>

                                <div
                                    v-else
                                    class="mt-4 rounded-xl border border-dashed border-slate-300 px-3 py-4 text-center text-xs font-bold text-slate-400 dark:border-slate-700"
                                >
                                    {{ status === 'delivered' ? 'لا يوجد رمز محفوظ بعد التسليم.' : 'لم يتم تسجيل رمز قفل.' }}
                                </div>
                            </article>

                            <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-xs font-black text-slate-700 dark:text-slate-200">مواعيد الطلب</p>
                                        <p class="mt-1 text-[10px] text-slate-400">الاستلام والموعد المتوقع والإنجاز</p>
                                    </div>
                                </div>

                                <div class="mt-4 grid gap-2 sm:grid-cols-3">
                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <p class="text-[10px] text-slate-400">استلام الجهاز</p>
                                        <strong class="mt-1 block text-xs text-slate-800 dark:text-slate-200">
                                            {{ dateTime(order.received_at) }}
                                        </strong>
                                    </div>

                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <p class="text-[10px] text-slate-400">متوقع الجاهزية</p>
                                        <strong class="mt-1 block text-xs text-slate-800 dark:text-slate-200">
                                            {{ date(order.expected_delivery_date) }}
                                        </strong>
                                    </div>

                                    <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-800">
                                        <p class="text-[10px] text-slate-400">التسليم</p>
                                        <strong class="mt-1 block text-xs text-slate-800 dark:text-slate-200">
                                            {{ dateTime(order.delivered_at) }}
                                        </strong>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <article class="rounded-2xl border border-slate-200 bg-slate-50/50 p-4 dark:border-slate-700 dark:bg-slate-800/30">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-xs font-black text-slate-700 dark:text-slate-200">صور الجهاز عند الاستلام</p>
                                    <p class="mt-1 text-[10px] text-slate-400">
                                        {{ receivedAttachments.length ? `${receivedAttachments.length} صورة مرفقة` : 'لا توجد صور مرفقة لهذا الطلب' }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="receivedAttachments.length"
                                class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4"
                            >
                                <button
                                    v-for="attachment in receivedAttachments"
                                    :key="attachment.id"
                                    type="button"
                                    class="group relative aspect-[4/3] overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg dark:border-slate-700 dark:bg-slate-800"
                                    @click="intakeImageModal = attachment"
                                >
                                    <img
                                        :src="attachmentUrl(attachment)"
                                        alt="صورة الجهاز عند الاستلام"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
                                    />

                                    <span class="absolute inset-x-2 bottom-2 rounded-lg bg-slate-950/70 px-2 py-1.5 text-[10px] font-black text-white opacity-0 backdrop-blur transition group-hover:opacity-100">
                                        فتح الصورة
                                    </span>
                                </button>
                            </div>

                            <div
                                v-else
                                class="mt-4 rounded-2xl border-2 border-dashed border-slate-200 px-5 py-7 text-center text-xs text-slate-400 dark:border-slate-700"
                            >
                                لم يتم إرفاق صور للجهاز عند إنشاء الطلب.
                            </div>
                        </article>

                        <div class="border-t border-slate-100 pt-5 dark:border-slate-800">
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-black text-slate-950 dark:text-white">المشكلة والتشخيص والإصلاح</h3>
                                    <p class="mt-1 text-xs text-slate-500">التفاصيل الفنية الرئيسية للطلب.</p>
                                </div>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-3">
                                <article class="rounded-2xl bg-rose-50/70 p-4 dark:bg-rose-950/15">
                                    <p class="text-xs font-black text-rose-700 dark:text-rose-300">المشكلة التي ذكرها العميل</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700 dark:text-slate-200">
                                        {{ order.problem_description || '—' }}
                                    </p>
                                </article>

                                <article class="rounded-2xl bg-violet-50/70 p-4 dark:bg-violet-950/15">
                                    <p class="text-xs font-black text-violet-700 dark:text-violet-300">التشخيص</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700 dark:text-slate-200">
                                        {{ order.inspection_result || '—' }}
                                    </p>
                                </article>

                                <article class="rounded-2xl bg-emerald-50/70 p-4 dark:bg-emerald-950/15">
                                    <p class="text-xs font-black text-emerald-700 dark:text-emerald-300">الإصلاح المطلوب / المنفذ</p>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700 dark:text-slate-200">
                                        {{ order.repair_action || '—' }}
                                    </p>
                                </article>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                                <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-800">
                                    <p class="text-[10px] text-slate-400">سبب العطل</p>
                                    <strong class="mt-1 block text-sm text-slate-800 dark:text-slate-200">
                                        {{ order.fault_cause || '—' }}
                                    </strong>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-800">
                                    <p class="text-[10px] text-slate-400">الفني</p>
                                    <strong class="mt-1 block text-sm text-slate-800 dark:text-slate-200">
                                        {{ order.technician_name || '—' }}
                                    </strong>
                                </div>

                                <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-800 sm:col-span-2">
                                    <p class="text-[10px] text-slate-400">ملاحظات العميل</p>
                                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700 dark:text-slate-200">
                                        {{ order.customer_notes || '—' }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="order.internal_notes"
                                class="mt-3 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800"
                            >
                                <p class="text-[10px] font-black text-slate-400">ملاحظات داخلية</p>
                                <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700 dark:text-slate-200">
                                    {{ order.internal_notes }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 class="font-black text-slate-950 dark:text-white">قطع الغيار</h2><p class="mt-1 text-xs text-slate-500">المخزن والشراء الخارجي ضمن نفس الطلب.</p></div><button v-if="isEditable" type="button" class="rounded-xl bg-blue-600 px-4 py-2.5 text-xs font-black text-white" @click="openPartModal">+ إضافة قطعة</button></div>

                    <div class="mt-5 space-y-4">
                        <div v-if="committedStockParts.length"><p class="mb-2 text-xs font-black text-blue-600">من مخزن المحل</p><div class="space-y-2"><article v-for="part in committedStockParts" :key="part.id" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900/40 dark:bg-blue-950/20"><div><strong class="text-sm dark:text-white">{{ part.product_name }}</strong><p class="mt-1 text-xs text-slate-500">{{ part.quantity }} قطعة · تكلفة {{ money(part.total_cost) }} · {{ part.warehouse?.name }}</p></div><button v-if="isEditable" type="button" class="text-xs font-black text-rose-600" @click="openRevertStock(part)">إرجاع للمخزن</button></article></div></div>

                        <div v-if="externalParts.length"><p class="mb-2 text-xs font-black text-orange-600">قطع خارجية</p><div class="space-y-2"><article v-for="part in externalParts" :key="part.id" class="rounded-2xl border p-4" :class="part.status === 'draft' ? 'border-orange-200 bg-orange-50 dark:border-orange-900/50 dark:bg-orange-950/20' : part.status === 'purchased' ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/20' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800'"><div class="flex flex-wrap items-start justify-between gap-3"><div><div class="flex items-center gap-2"><strong class="text-sm dark:text-white">{{ part.part_name }}</strong><span class="rounded-full px-2 py-1 text-[10px] font-black" :class="part.status === 'draft' ? 'bg-orange-100 text-orange-700' : part.status === 'purchased' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'">{{ part.status === 'draft' ? 'بانتظار الشراء' : part.status === 'purchased' ? 'تم الشراء' : 'تم الإرجاع' }}</span></div><p class="mt-1 text-xs text-slate-500">الكمية {{ part.quantity }}<template v-if="part.status === 'purchased'"> · من {{ part.purchase_from }} · تكلفة {{ money(part.total_purchase_cost) }} · {{ part.financial_account?.name }}</template></p></div><div v-if="part.status === 'draft' && isEditable" class="flex gap-2"><button type="button" class="rounded-lg bg-orange-600 px-3 py-2 text-xs font-black text-white" @click="openPurchase(part)">تسجيل الشراء</button><button type="button" class="rounded-lg px-3 py-2 text-xs font-black text-rose-600" @click="deleteExternalDraft(part)">حذف</button></div><button v-if="part.status === 'purchased' && !isFinished" type="button" class="text-xs font-black text-rose-600" @click="openReturnExternal(part)">إرجاع للمصدر</button></div></article></div></div>

                        <div v-if="legacyPendingStockParts.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">يوجد {{ legacyPendingStockParts.length }} قطعة قديمة غير معتمدة من النظام السابق. راجعها قبل تحديد الجهاز كجاهز.</div>
                        <div v-if="!committedStockParts.length && !externalParts.length && !legacyPendingStockParts.length" class="rounded-2xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">لا يحتاج هذا الطلب قطع غيار حتى الآن.</div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <div class="flex items-center justify-between"><div><h2 class="font-black text-slate-950 dark:text-white">دفعات العميل</h2><p class="mt-1 text-xs text-slate-500">الدفع غالباً عند الاستلام، ويمكن تسجيل عربون عند الحاجة.</p></div><button v-if="order.can_add_payment" type="button" class="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-black text-white" @click="openPaymentModal">+ تسجيل دفعة</button></div>
                    <div v-if="order.payments?.length" class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-xs text-slate-400"><tr><th class="py-2 text-right">التاريخ</th><th class="py-2 text-right">الطريقة</th><th class="py-2 text-right">الحساب</th><th class="py-2 text-left">المبلغ</th></tr></thead><tbody><tr v-for="payment in order.payments" :key="payment.id" class="border-t border-slate-100 dark:border-slate-800"><td class="py-3">{{ date(payment.paid_at) }}</td><td>{{ paymentMethods[payment.payment_method] || payment.payment_method }}</td><td>{{ payment.financial_account?.name || '—' }}</td><td dir="ltr" class="text-left font-black text-emerald-600">{{ money(payment.amount) }}</td></tr></tbody></table></div><p v-else class="mt-4 text-sm text-slate-500">لم يتم تسجيل أي دفعة بعد.</p>
                </section>
            </div>

            <aside class="space-y-4 xl:sticky xl:top-6 xl:self-start">
                <section class="rounded-3xl bg-slate-950 p-5 text-white shadow-xl"><p class="text-[10px] font-black tracking-widest text-blue-300">FINANCIAL SUMMARY</p><div class="mt-4 rounded-2xl bg-white/5 p-4"><p class="text-xs text-slate-400">على العميل</p><strong dir="ltr" class="mt-1 block text-right text-3xl font-black">{{ money(order.total_amount) }}</strong></div><div class="mt-4 grid grid-cols-2 gap-2"><div class="rounded-xl bg-emerald-500/10 p-3"><p class="text-[10px] text-emerald-300">المدفوع</p><strong class="mt-1 block text-sm text-emerald-200">{{ money(order.paid_amount) }}</strong></div><div class="rounded-xl bg-rose-500/10 p-3"><p class="text-[10px] text-rose-300">المتبقي</p><strong class="mt-1 block text-sm text-rose-200">{{ money(order.remaining_amount) }}</strong></div></div><div class="mt-4 flex justify-between border-t border-white/10 pt-4 text-sm"><span class="text-slate-400">تكلفة القطع الفعلية</span><strong>{{ money(order.parts_cost) }}</strong></div><div class="mt-2 flex justify-between text-sm"><span class="text-slate-400">ربح الطلب</span><strong :class="orderProfit >= 0 ? 'text-emerald-300' : 'text-rose-300'">{{ money(orderProfit) }}</strong></div></section>

                <section v-if="status === 'in_progress'" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"><h2 class="font-black dark:text-white">إنهاء الصيانة</h2><p v-if="pendingExternal.length" class="mt-3 rounded-xl bg-orange-50 p-3 text-xs leading-5 text-orange-700">ما زال لديك {{ pendingExternal.length }} قطعة خارجية بانتظار الشراء. لا يمكن تجهيز الجهاز قبل حسمها.</p><button type="button" :disabled="!order.can_be_marked_ready" class="mt-4 w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white disabled:cursor-not-allowed disabled:opacity-40" @click="markReady">✓ الجهاز جاهز — اتصل بالعميل</button></section>

                <section v-if="isReady" class="rounded-3xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-900/50 dark:bg-emerald-950/20"><h2 class="font-black text-emerald-900 dark:text-emerald-200">الجهاز جاهز للاستلام</h2><p class="mt-2 text-xs text-emerald-700 dark:text-emerald-300">اتصل بالعميل، وعندما يصل سجّل الدفعة ثم سلّم الجهاز.</p><a :href="`tel:${order.customer_phone}`" class="mt-4 block rounded-xl border border-emerald-300 px-4 py-3 text-center text-sm font-black text-emerald-700">اتصال: {{ order.customer_phone }}</a><button type="button" class="mt-2 w-full rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black text-white" @click="openDeliverModal">دفع وتسليم الجهاز</button></section>

                <section v-if="!isFinished" class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900"><button type="button" class="w-full rounded-xl border border-rose-200 px-4 py-3 text-xs font-black text-rose-600" @click="cancelModal = true">إلغاء طلب الصيانة</button><p v-if="purchasedExternal.length" class="mt-2 text-[10px] leading-5 text-slate-400">إلغاء الطلب لا يعيد تلقائياً قيمة القطع الخارجية المشتراة. إذا رجعتها للمحل الخارجي، سجّل «إرجاع للمصدر» أولاً.</p></section>
            </aside>
        </div>

        <!-- Details modal -->
        <div v-if="detailsOpen" class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/70 p-4" @mousedown.self="detailsOpen = false"><form class="w-full max-w-2xl rounded-3xl bg-white p-6 dark:bg-slate-900" @submit.prevent="saveDetails"><h2 class="text-lg font-black dark:text-white">تعديل التشخيص والسعر</h2><div class="mt-5 grid gap-4 md:grid-cols-2"><textarea v-model="detailsForm.inspection_result" rows="4" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="التشخيص"/><textarea v-model="detailsForm.repair_action" rows="4" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="الإصلاح المطلوب"/><input v-model.number="detailsForm.agreed_price" type="number" min="0" step="0.01" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="السعر المتفق"/><input v-model="detailsForm.expected_delivery_date" type="date" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"/><input v-model="detailsForm.technician_name" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="الفني"/><input v-model="detailsForm.fault_cause" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="سبب العطل"/></div><div class="mt-5 grid grid-cols-2 gap-2"><button type="button" class="rounded-xl border border-slate-300 py-3 font-black" @click="detailsOpen = false">إلغاء</button><button class="rounded-xl bg-blue-600 py-3 font-black text-white">حفظ</button></div></form></div>

        <!-- Add part modal -->
        <div
            v-if="partModal"
            class="fixed inset-0 z-[140] flex items-center justify-center bg-slate-950/75 p-3 backdrop-blur-sm sm:p-5"
            @mousedown.self="!stockForm.processing && !externalForm.processing && (partModal = false)"
        >
            <div
                class="flex max-h-[94vh] w-full max-w-3xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-slate-900"
            >
                <div
                    class="shrink-0 border-b border-slate-200 bg-gradient-to-l from-blue-50 via-white to-violet-50 px-5 py-5 dark:border-slate-800 dark:from-blue-950/20 dark:via-slate-900 dark:to-violet-950/20 sm:px-6"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-xl text-white shadow-lg shadow-blue-600/20"
                            >
                                🔧
                            </div>

                            <div>
                                <h2
                                    class="text-xl font-black text-slate-950 dark:text-white"
                                >
                                    إضافة قطعة غيار
                                </h2>

                                <p
                                    class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400"
                                >
                                    حدد مصدر القطعة، ثم أدخل البيانات المطلوبة بشكل واضح وسريع.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            :disabled="stockForm.processing || externalForm.processing"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-xl text-slate-400 transition hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:hover:text-white"
                            @click="partModal = false"
                        >
                            ×
                        </button>
                    </div>

                    <div
                        class="mt-5 grid grid-cols-2 gap-2 rounded-2xl bg-slate-100 p-1.5 dark:bg-slate-800"
                    >
                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-black transition"
                            :class="
                                partSource === 'stock'
                                    ? 'bg-white text-blue-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-700 dark:text-blue-300 dark:ring-slate-600'
                                    : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'
                            "
                            @click="partSource = 'stock'"
                        >
                            <span>📦</span>
                            من مخزن المحل
                        </button>

                        <button
                            type="button"
                            class="flex items-center justify-center gap-2 rounded-xl px-4 py-3 text-sm font-black transition"
                            :class="
                                partSource === 'external'
                                    ? 'bg-white text-violet-700 shadow-sm ring-1 ring-slate-200 dark:bg-slate-700 dark:text-violet-300 dark:ring-slate-600'
                                    : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'
                            "
                            @click="partSource = 'external'"
                        >
                            <span>🛍️</span>
                            شراء من خارج المحل
                        </button>
                    </div>
                </div>

                <div
                    class="min-h-0 flex-1 overflow-y-auto p-5 sm:p-6"
                >
                    <form
                        v-if="partSource === 'stock'"
                        class="space-y-5"
                        @submit.prevent="addStock"
                    >
                        <div
                            v-if="Object.keys(stockForm.errors).length"
                            class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300"
                        >
                            {{ Object.values(stockForm.errors)[0] }}
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                البحث عن القطعة
                            </label>

                            <input
                                v-model.trim="partProductSearch"
                                type="search"
                                class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                placeholder="اكتب اسم القطعة أو الكود..."
                            />
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                قطعة المخزون
                                <span class="text-rose-500">*</span>
                            </label>

                            <select
                                v-model="stockForm.product_id"
                                class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                            >
                                <option value="">اختر القطعة</option>

                                <option
                                    v-for="product in filteredPartProducts"
                                    :key="product.id"
                                    :value="product.id"
                                    :disabled="Number(product.available_quantity || 0) <= 0"
                                >
                                    {{ product.name }} — متوفر {{ product.available_quantity }}
                                </option>
                            </select>
                        </div>

                        <div
                            v-if="selectedStock"
                            class="grid gap-3 rounded-2xl border border-blue-200 bg-blue-50 p-4 sm:grid-cols-3 dark:border-blue-900/50 dark:bg-blue-950/20"
                        >
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wider text-blue-500">
                                    المتوفر
                                </p>

                                <strong class="mt-1 block text-lg text-blue-900 dark:text-blue-200">
                                    {{ selectedStock.available_quantity }}
                                </strong>
                            </div>

                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wider text-blue-500">
                                    تكلفة المخزون
                                </p>

                                <strong class="mt-1 block text-sm text-blue-900 dark:text-blue-200">
                                    {{ money(selectedStock.purchase_price) }}
                                </strong>
                            </div>

                            <div>
                                <p class="text-[10px] font-black uppercase tracking-wider text-blue-500">
                                    سعر البيع الافتراضي
                                </p>

                                <strong class="mt-1 block text-sm text-blue-900 dark:text-blue-200">
                                    {{ money(selectedStock.selling_price) }}
                                </strong>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    الكمية
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.number="stockForm.quantity"
                                    type="number"
                                    min="1"
                                    :max="selectedStock?.available_quantity"
                                    class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-lg font-black text-slate-950 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    سعر الوحدة للعميل
                                    <span class="text-rose-500">*</span>
                                </label>

                                <div class="relative">
                                    <input
                                        v-model.number="stockForm.unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 pl-16 text-lg font-black text-slate-950 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                    />

                                    <span
                                        class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400"
                                    >
                                        شيكل
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center justify-between rounded-2xl bg-slate-950 px-5 py-4 text-white"
                        >
                            <div>
                                <p class="text-xs font-bold text-slate-400">
                                    القيمة على العميل
                                </p>

                                <strong class="mt-1 block text-xl font-black">
                                    {{ money(stockPartCustomerTotal) }}
                                </strong>
                            </div>

                            <div v-if="selectedStock" class="text-left">
                                <p class="text-[10px] text-slate-400">
                                    بعد الخصم من المخزون
                                </p>

                                <strong class="mt-1 block text-sm text-blue-300">
                                    {{
                                        Math.max(
                                            0,
                                            Number(selectedStock.available_quantity || 0)
                                            - Number(stockForm.quantity || 0)
                                        )
                                    }}
                                    متبقي
                                </strong>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <button
                                type="button"
                                :disabled="stockForm.processing"
                                class="rounded-2xl border border-slate-300 bg-white py-3.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                @click="partModal = false"
                            >
                                إلغاء
                            </button>

                            <button
                                type="submit"
                                :disabled="stockForm.processing || !selectedStock"
                                class="rounded-2xl bg-blue-600 py-3.5 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{
                                    stockForm.processing
                                        ? 'جاري الإضافة...'
                                        : 'إضافة وخصم من المخزن'
                                }}
                            </button>
                        </div>
                    </form>

                    <form
                        v-else
                        class="space-y-5"
                        @submit.prevent="addExternal"
                    >
                        <div
                            v-if="Object.keys(externalForm.errors).length"
                            class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300"
                        >
                            {{ Object.values(externalForm.errors)[0] }}
                        </div>

                        <div
                            class="rounded-2xl border border-violet-200 bg-violet-50 p-4 text-sm leading-6 text-violet-800 dark:border-violet-900/50 dark:bg-violet-950/20 dark:text-violet-300"
                        >
                            <strong class="block">
                                هذه القطعة غير موجودة بالمخزن
                            </strong>

                            <span class="mt-1 block text-xs">
                                سيتم حفظها الآن كـ«بانتظار الشراء». عند إحضارها اضغط «تسجيل الشراء» وحدد المحل والسعر والحساب المالي.
                            </span>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                اسم القطعة الخارجية
                                <span class="text-rose-500">*</span>
                            </label>

                            <input
                                v-model.trim="externalForm.part_name"
                                type="text"
                                class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                placeholder="مثال: شاشة Samsung A54 أصلية"
                            />
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    الكمية
                                    <span class="text-rose-500">*</span>
                                </label>

                                <input
                                    v-model.number="externalForm.quantity"
                                    type="number"
                                    min="1"
                                    class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-lg font-black text-slate-950 shadow-sm focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                />
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                    قيمتها للعميل
                                    <span class="font-medium text-slate-400">اختياري</span>
                                </label>

                                <div class="relative">
                                    <input
                                        v-model.number="externalForm.customer_unit_price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 pl-16 text-lg font-black text-slate-950 shadow-sm focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                        placeholder="0.00"
                                    />

                                    <span
                                        class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400"
                                    >
                                        شيكل
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                ملاحظة عن القطعة
                                <span class="font-medium text-slate-400">اختياري</span>
                            </label>

                            <textarea
                                v-model.trim="externalForm.notes"
                                rows="3"
                                maxlength="500"
                                class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-4 text-base leading-7 text-slate-950 shadow-sm focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                placeholder="لون، جودة، نوع مطلوب، ملاحظة للمورد..."
                            />
                        </div>

                        <div
                            class="flex items-center justify-between rounded-2xl bg-slate-950 px-5 py-4 text-white"
                        >
                            <div>
                                <p class="text-xs font-bold text-slate-400">
                                    القيمة المسجلة على العميل
                                </p>

                                <strong class="mt-1 block text-xl font-black">
                                    {{ money(externalPartCustomerTotal) }}
                                </strong>
                            </div>

                            <span
                                class="rounded-full bg-amber-400/10 px-3 py-1.5 text-xs font-black text-amber-300"
                            >
                                بانتظار الشراء
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <button
                                type="button"
                                :disabled="externalForm.processing"
                                class="rounded-2xl border border-slate-300 bg-white py-3.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                @click="partModal = false"
                            >
                                إلغاء
                            </button>

                            <button
                                type="submit"
                                :disabled="externalForm.processing || !String(externalForm.part_name || '').trim()"
                                class="rounded-2xl bg-violet-600 py-3.5 text-sm font-black text-white shadow-lg shadow-violet-600/20 transition hover:bg-violet-700 disabled:cursor-not-allowed disabled:opacity-50"
                            >
                                {{
                                    externalForm.processing
                                        ? 'جاري الحفظ...'
                                        : 'حفظ كقطعة بانتظار الشراء'
                                }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Purchase external modal -->
        <div
            v-if="purchaseTarget"
            class="fixed inset-0 z-[145] flex items-center justify-center bg-slate-950/75 p-3 backdrop-blur-sm sm:p-5"
            @mousedown.self="!purchaseForm.processing && (purchaseTarget = null)"
        >
            <form
                class="flex max-h-[94vh] w-full max-w-3xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-slate-900"
                @submit.prevent="completePurchase"
            >
                <div class="shrink-0 border-b border-slate-200 bg-gradient-to-l from-orange-50 via-white to-amber-50 px-5 py-5 dark:border-slate-800 dark:from-orange-950/20 dark:via-slate-900 dark:to-amber-950/20 sm:px-6">
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-orange-600 text-xl text-white shadow-lg shadow-orange-600/20">🛍️</div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.2em] text-orange-500">EXTERNAL PART PURCHASE</p>
                                <h2 class="mt-1 text-xl font-black text-slate-950 dark:text-white">تسجيل شراء القطعة</h2>
                                <p class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400">
                                    {{ purchaseTarget.part_name }}
                                    <span class="mx-1 text-slate-300">•</span>
                                    الكمية {{ purchaseTarget.quantity }}
                                </p>
                            </div>
                        </div>
                        <button type="button" :disabled="purchaseForm.processing" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-xl text-slate-400 transition hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:hover:text-white" @click="purchaseTarget = null">×</button>
                    </div>

                    <div class="mt-5 grid gap-2 sm:grid-cols-3">
                        <div class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800">
                            <p class="text-[10px] font-black text-slate-400">القطعة</p>
                            <strong class="mt-1 block truncate text-sm text-slate-900 dark:text-white">{{ purchaseTarget.part_name }}</strong>
                        </div>
                        <div class="rounded-2xl border border-orange-200 bg-orange-50 p-3 dark:border-orange-900/50 dark:bg-orange-950/20">
                            <p class="text-[10px] font-black text-orange-500">الكمية المطلوبة</p>
                            <strong class="mt-1 block text-sm text-orange-800 dark:text-orange-300">{{ purchaseTarget.quantity }}</strong>
                        </div>
                        <div class="rounded-2xl border border-violet-200 bg-violet-50 p-3 dark:border-violet-900/50 dark:bg-violet-950/20">
                            <p class="text-[10px] font-black text-violet-500">قيمتها على العميل</p>
                            <strong class="mt-1 block text-sm text-violet-800 dark:text-violet-300">{{ money(purchaseCustomerTotal) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="min-h-0 flex-1 space-y-6 overflow-y-auto p-5 sm:p-6">
                    <div v-if="purchaseForm.errors.purchase" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold leading-6 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300">{{ purchaseForm.errors.purchase }}</div>
                    <div v-else-if="Object.keys(purchaseForm.errors).length" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold leading-6 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300">{{ Object.values(purchaseForm.errors)[0] }}</div>

                    <section>
                        <div class="mb-3 flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-orange-100 text-xs font-black text-orange-700 dark:bg-orange-950/30 dark:text-orange-300">1</span>
                            <div><h3 class="text-sm font-black text-slate-900 dark:text-white">مصدر الشراء</h3><p class="mt-0.5 text-xs text-slate-500">اختر مورداً مسجلاً أو اكتب اسم المحل الخارجي.</p></div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">مورد مسجل <span class="font-medium text-slate-400">اختياري</span></label>
                                <select v-model="purchaseForm.supplier_id" class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white">
                                    <option value="">بدون مورد مسجل</option>
                                    <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">{{ supplier.company_name || supplier.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">اسم المحل / المصدر <span class="text-rose-500">*</span></label>
                                <input v-model.trim="purchaseForm.purchase_from" type="text" maxlength="255" class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white" placeholder="مثال: محل أبو أحمد للموبايلات"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">هاتف المورد <span class="font-medium text-slate-400">اختياري</span></label>
                                <input v-model.trim="purchaseForm.supplier_phone" type="tel" dir="ltr" maxlength="30" class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-right text-base text-slate-950 shadow-sm focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white" placeholder="059XXXXXXX"/>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">رقم الفاتورة / المرجع <span class="font-medium text-slate-400">اختياري</span></label>
                                <input v-model.trim="purchaseForm.purchase_reference" type="text" maxlength="255" class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white" placeholder="INV-001 / رقم الإيصال"/>
                            </div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100 dark:border-slate-800"></div>

                    <section>
                        <div class="mb-3 flex items-center gap-2">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-emerald-100 text-xs font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">2</span>
                            <div><h3 class="text-sm font-black text-slate-900 dark:text-white">السعر والحساب المالي</h3><p class="mt-0.5 text-xs text-slate-500">سيتم خصم إجمالي الشراء مباشرة من الحساب المختار.</p></div>
                        </div>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">سعر شراء الوحدة <span class="text-rose-500">*</span></label>
                                <div class="relative">
                                    <input v-model.number="purchaseForm.unit_purchase_price" type="number" min="0.01" step="0.01" class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 pl-16 text-xl font-black text-slate-950 shadow-sm focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white" placeholder="0.00"/>
                                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">شيكل</span>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">الحساب الذي تم الدفع منه <span class="text-rose-500">*</span></label>
                                <select v-model="purchaseForm.financial_account_id" class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white">
                                    <option value="">اختر الحساب المالي</option>
                                    <option v-for="account in financialAccounts" :key="account.id" :value="account.id">{{ account.name }} — {{ money(account.current_balance) }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">القيمة على العميل للوحدة <span class="font-medium text-slate-400">اختياري</span></label>
                                <div class="relative">
                                    <input v-model.number="purchaseForm.customer_unit_price" type="number" min="0" step="0.01" class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 pl-16 text-base font-black text-slate-950 shadow-sm focus:border-violet-500 focus:ring-4 focus:ring-violet-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white" placeholder="0.00"/>
                                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-xs font-black text-slate-400">شيكل</span>
                                </div>
                            </div>
                            <div>
                                <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">تاريخ ووقت الشراء <span class="text-rose-500">*</span></label>
                                <input v-model="purchaseForm.purchased_at" type="datetime-local" :max="nowLocal()" class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"/>
                            </div>
                        </div>

                        <div v-if="selectedPurchaseAccount" class="mt-4 grid gap-3 rounded-2xl border p-4 sm:grid-cols-3" :class="purchaseAccountBalanceAfter < 0 ? 'border-rose-200 bg-rose-50 dark:border-rose-900/50 dark:bg-rose-950/20' : 'border-emerald-200 bg-emerald-50 dark:border-emerald-900/50 dark:bg-emerald-950/20'">
                            <div><p class="text-[10px] font-black text-slate-400">رصيد الحساب قبل الشراء</p><strong class="mt-1 block text-sm text-slate-900 dark:text-white">{{ money(selectedPurchaseAccount.current_balance) }}</strong></div>
                            <div><p class="text-[10px] font-black text-slate-400">قيمة الشراء</p><strong class="mt-1 block text-sm text-orange-700 dark:text-orange-300">- {{ money(purchaseTotalCost) }}</strong></div>
                            <div><p class="text-[10px] font-black text-slate-400">الرصيد بعد الشراء</p><strong class="mt-1 block text-sm" :class="purchaseAccountBalanceAfter < 0 ? 'text-rose-700 dark:text-rose-300' : 'text-emerald-700 dark:text-emerald-300'">{{ money(purchaseAccountBalanceAfter) }}</strong></div>
                        </div>
                    </section>

                    <div class="border-t border-slate-100 dark:border-slate-800"></div>

                    <section>
                        <div class="mb-3 flex items-center gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">3</span><h3 class="text-sm font-black text-slate-900 dark:text-white">ملاحظات الشراء</h3></div>
                        <textarea v-model.trim="purchaseForm.notes" rows="3" maxlength="500" class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-4 text-base leading-7 text-slate-950 shadow-sm focus:border-orange-500 focus:ring-4 focus:ring-orange-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white" placeholder="مثال: قطعة أصلية، تم فحصها قبل الاستلام، ضمان من المورد..."/>
                    </section>

                    <section class="overflow-hidden rounded-3xl bg-slate-950 p-5 text-white">
                        <div class="flex items-center justify-between gap-4">
                            <div><p class="text-[10px] font-black uppercase tracking-[0.18em] text-orange-300">PURCHASE SUMMARY</p><h3 class="mt-1 text-base font-black">ملخص عملية الشراء</h3></div>
                            <span class="rounded-full bg-orange-400/10 px-3 py-1.5 text-xs font-black text-orange-300">سيتم الخصم فور التأكيد</span>
                        </div>
                        <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <div><p class="text-[10px] text-slate-400">الكمية</p><strong class="mt-1 block text-sm">{{ purchaseQuantity }}</strong></div>
                            <div><p class="text-[10px] text-slate-400">سعر الوحدة</p><strong class="mt-1 block text-sm">{{ money(purchaseForm.unit_purchase_price) }}</strong></div>
                            <div><p class="text-[10px] text-slate-400">إجمالي الشراء</p><strong class="mt-1 block text-sm text-orange-300">{{ money(purchaseTotalCost) }}</strong></div>
                            <div><p class="text-[10px] text-slate-400">على العميل</p><strong class="mt-1 block text-sm text-violet-300">{{ money(purchaseCustomerTotal) }}</strong></div>
                        </div>
                    </section>
                </div>

                <div class="grid shrink-0 grid-cols-2 gap-3 border-t border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/50">
                    <button type="button" :disabled="purchaseForm.processing" class="rounded-2xl border border-slate-300 bg-white py-3.5 text-sm font-black text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200" @click="purchaseTarget = null">إلغاء</button>
                    <button type="submit" :disabled="purchaseForm.processing" class="rounded-2xl bg-orange-600 py-3.5 text-sm font-black text-white shadow-lg shadow-orange-600/20 transition hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50">{{ purchaseForm.processing ? 'جاري تسجيل الشراء...' : 'تأكيد الشراء وخصم المبلغ' }}</button>
                </div>
            </form>
        </div>

        <!-- Simple reason modal reusable-ish -->
        <div v-if="revertStockTarget" class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/70 p-4"><form class="w-full max-w-md rounded-3xl bg-white p-6 dark:bg-slate-900" @submit.prevent="revertStock"><h2 class="font-black dark:text-white">إرجاع {{ revertStockTarget.product_name }} للمخزن</h2><textarea v-model="revertStockForm.reason" required rows="3" class="mt-4 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="سبب الإرجاع"/><button class="mt-3 w-full rounded-xl bg-rose-600 py-3 font-black text-white">تأكيد الإرجاع</button><button type="button" class="mt-2 w-full text-xs text-slate-500" @click="revertStockTarget = null">إلغاء</button></form></div>
        <div v-if="returnExternalTarget" class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/70 p-4"><form class="w-full max-w-md rounded-3xl bg-white p-6 dark:bg-slate-900" @submit.prevent="returnExternal"><h2 class="font-black dark:text-white">إرجاع القطعة للمصدر</h2><p class="mt-2 text-xs text-slate-500">سيتم عكس حركة الشراء وإرجاع قيمتها إلى الحساب المالي الأصلي.</p><textarea v-model="returnExternalForm.reason" required rows="3" class="mt-4 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="سبب الإرجاع"/><button class="mt-3 w-full rounded-xl bg-rose-600 py-3 font-black text-white">تأكيد الإرجاع المالي</button><button type="button" class="mt-2 w-full text-xs text-slate-500" @click="returnExternalTarget = null">إلغاء</button></form></div>

        <!-- payment -->
        <div
            v-if="paymentModal"
            class="fixed inset-0 z-[150] flex items-center justify-center bg-slate-950/75 p-3 backdrop-blur-sm sm:p-5"
            @mousedown.self="!paymentForm.processing && (paymentModal = false)"
        >
            <form
                class="flex max-h-[94vh] w-full max-w-2xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-slate-900"
                @submit.prevent="addPayment"
            >
                <div
                    class="shrink-0 border-b border-slate-200 bg-gradient-to-l from-emerald-50 via-white to-cyan-50 px-5 py-5 dark:border-slate-800 dark:from-emerald-950/20 dark:via-slate-900 dark:to-cyan-950/20 sm:px-6"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-xl text-white shadow-lg shadow-emerald-600/20"
                            >
                                ₪
                            </div>

                            <div>
                                <h2
                                    class="text-xl font-black text-slate-950 dark:text-white"
                                >
                                    تسجيل دفعة صيانة
                                </h2>

                                <p
                                    class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400"
                                >
                                    سجّل عربونًا أو دفعة على طلب الصيانة قبل الاستلام.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            :disabled="paymentForm.processing"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-xl text-slate-400 transition hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:hover:text-white"
                            @click="paymentModal = false"
                        >
                            ×
                        </button>
                    </div>

                    <div class="mt-5 grid grid-cols-3 gap-2">
                        <div
                            class="rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-700 dark:bg-slate-800"
                        >
                            <p class="text-[10px] font-black text-slate-400">
                                إجمالي الطلب
                            </p>

                            <strong class="mt-1 block text-sm text-slate-900 dark:text-white">
                                {{ money(order.total_amount) }}
                            </strong>
                        </div>

                        <div
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-900/50 dark:bg-emerald-950/20"
                        >
                            <p class="text-[10px] font-black text-emerald-500">
                                المدفوع سابقًا
                            </p>

                            <strong class="mt-1 block text-sm text-emerald-700 dark:text-emerald-300">
                                {{ money(order.paid_amount) }}
                            </strong>
                        </div>

                        <div
                            class="rounded-2xl border border-rose-200 bg-rose-50 p-3 dark:border-rose-900/50 dark:bg-rose-950/20"
                        >
                            <p class="text-[10px] font-black text-rose-500">
                                المتبقي
                            </p>

                            <strong class="mt-1 block text-sm text-rose-700 dark:text-rose-300">
                                {{ money(order.remaining_amount) }}
                            </strong>
                        </div>
                    </div>
                </div>

                <div
                    class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5 sm:p-6"
                >
                    <div
                        v-if="paymentForm.errors.payment"
                        class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold leading-6 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300"
                    >
                        {{ paymentForm.errors.payment }}
                    </div>

                    <div
                        v-else-if="Object.keys(paymentForm.errors).length"
                        class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold leading-6 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300"
                    >
                        {{ Object.values(paymentForm.errors)[0] }}
                    </div>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <label class="text-sm font-black text-slate-700 dark:text-slate-200">
                                مبلغ الدفعة
                                <span class="text-rose-500">*</span>
                            </label>

                            <div class="flex gap-1.5">
                                <button
                                    type="button"
                                    class="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[10px] font-black text-slate-600 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300"
                                    @click="setPaymentAmount('half')"
                                >
                                    نصف المتبقي
                                </button>

                                <button
                                    type="button"
                                    class="rounded-lg bg-emerald-100 px-2.5 py-1.5 text-[10px] font-black text-emerald-700 transition hover:bg-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300"
                                    @click="setPaymentAmount('full')"
                                >
                                    كامل المتبقي
                                </button>
                            </div>
                        </div>

                        <div class="relative">
                            <input
                                v-model.number="paymentForm.amount"
                                type="number"
                                min="0.01"
                                :max="order.remaining_amount"
                                step="0.01"
                                class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 pl-20 text-2xl font-black text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                            />

                            <span
                                class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-black text-slate-400"
                            >
                                شيكل
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                طريقة الدفع
                                <span class="text-rose-500">*</span>
                            </label>

                            <select
                                v-model="paymentForm.payment_method"
                                class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
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
                            <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                الحساب المالي
                                <span class="text-rose-500">*</span>
                            </label>

                            <select
                                v-model="paymentForm.financial_account_id"
                                class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                            >
                                <option value="">اختر الحساب</option>

                                <option
                                    v-for="account in compatibleAccounts(paymentForm.payment_method)"
                                    :key="account.id"
                                    :value="account.id"
                                >
                                    {{ account.name }} — {{ money(account.current_balance) }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div
                        v-if="selectedPaymentAccount"
                        class="grid grid-cols-2 gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20"
                    >
                        <div>
                            <p class="text-[10px] font-black text-emerald-500">
                                رصيد الحساب قبل الدفعة
                            </p>

                            <strong class="mt-1 block text-sm text-emerald-800 dark:text-emerald-200">
                                {{ money(selectedPaymentAccount.current_balance) }}
                            </strong>
                        </div>

                        <div>
                            <p class="text-[10px] font-black text-emerald-500">
                                الرصيد بعد الدفعة
                            </p>

                            <strong class="mt-1 block text-sm text-emerald-800 dark:text-emerald-200">
                                {{ money(paymentAccountBalanceAfter) }}
                            </strong>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                تاريخ الدفع
                                <span class="text-rose-500">*</span>
                            </label>

                            <input
                                v-model="paymentForm.paid_at"
                                type="datetime-local"
                                :max="nowLocal()"
                                class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                            />
                        </div>

                        <div
                            v-if="['bank_transfer', 'banking_app'].includes(paymentForm.payment_method)"
                        >
                            <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                                البنك / التطبيق
                            </label>

                            <input
                                v-model.trim="paymentForm.bank_or_app_name"
                                type="text"
                                maxlength="255"
                                class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                placeholder="يتم تعبئته تلقائيًا من الحساب"
                            />
                        </div>
                    </div>

                    <div
                        v-if="['bank_transfer', 'banking_app'].includes(paymentForm.payment_method)"
                    >
                        <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                            رقم / مرجع العملية
                            <span class="font-medium text-slate-400">اختياري</span>
                        </label>

                        <input
                            v-model.trim="paymentForm.transaction_reference"
                            type="text"
                            maxlength="255"
                            class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                            placeholder="رقم التحويل أو العملية"
                        />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200">
                            ملاحظات الدفعة
                            <span class="font-medium text-slate-400">اختياري</span>
                        </label>

                        <textarea
                            v-model.trim="paymentForm.notes"
                            rows="3"
                            maxlength="500"
                            class="block w-full resize-none rounded-2xl border-slate-300 bg-white px-4 py-4 text-base leading-7 text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                            placeholder="مثال: عربون على الصيانة..."
                        />
                    </div>

                    <div
                        class="grid grid-cols-2 gap-3 rounded-2xl bg-slate-950 p-4 text-white"
                    >
                        <div>
                            <p class="text-[10px] font-black text-slate-400">
                                الدفعة الحالية
                            </p>

                            <strong class="mt-1 block text-lg text-emerald-300">
                                {{ money(paymentForm.amount) }}
                            </strong>
                        </div>

                        <div>
                            <p class="text-[10px] font-black text-slate-400">
                                المتبقي بعدها
                            </p>

                            <strong
                                class="mt-1 block text-lg"
                                :class="
                                    paymentRemainingAfter > 0
                                        ? 'text-rose-300'
                                        : 'text-emerald-300'
                                "
                            >
                                {{ money(paymentRemainingAfter) }}
                            </strong>
                        </div>
                    </div>
                </div>

                <div
                    class="grid shrink-0 grid-cols-2 gap-3 border-t border-slate-200 bg-slate-50 p-5 dark:border-slate-800 dark:bg-slate-950/50"
                >
                    <button
                        type="button"
                        :disabled="paymentForm.processing"
                        class="rounded-2xl border border-slate-300 bg-white py-3.5 text-sm font-black text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                        @click="paymentModal = false"
                    >
                        إلغاء
                    </button>

                    <button
                        type="submit"
                        :disabled="paymentForm.processing"
                        class="rounded-2xl bg-emerald-600 py-3.5 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{
                            paymentForm.processing
                                ? 'جاري تسجيل الدفعة...'
                                : 'تأكيد تسجيل الدفعة'
                        }}
                    </button>
                </div>
            </form>
        </div>

        <!-- deliver -->
        <div
            v-if="deliverModal"
            class="fixed inset-0 z-[155] flex items-center justify-center bg-slate-950/80 p-3 backdrop-blur-md sm:p-5"
            @mousedown.self="!deliverForm.processing && (deliverModal = false)"
        >
            <form
                class="flex max-h-[95vh] w-full max-w-3xl flex-col overflow-hidden rounded-[30px] bg-white shadow-2xl dark:bg-slate-900"
                @submit.prevent="deliver"
            >
                <!-- Header -->
                <div
                    class="relative shrink-0 overflow-hidden border-b border-slate-200 bg-gradient-to-l from-emerald-50 via-white to-blue-50 px-5 py-5 dark:border-slate-800 dark:from-emerald-950/20 dark:via-slate-900 dark:to-blue-950/20 sm:px-6"
                >
                    <div
                        class="pointer-events-none absolute -left-12 -top-12 h-36 w-36 rounded-full bg-emerald-400/10 blur-3xl"
                    ></div>

                    <div
                        class="pointer-events-none absolute -bottom-16 right-10 h-40 w-40 rounded-full bg-blue-400/10 blur-3xl"
                    ></div>

                    <div
                        class="relative flex items-start justify-between gap-4"
                    >
                        <div
                            class="flex items-start gap-3"
                        >
                            <div
                                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-600 text-xl text-white shadow-lg shadow-emerald-600/20"
                            >
                                ✓
                            </div>

                            <div>
                                <div
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <h2
                                        class="text-xl font-black text-slate-950 dark:text-white"
                                    >
                                        دفع وتسليم الجهاز
                                    </h2>

                                    <span
                                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
                                    >
                                        المرحلة الأخيرة
                                    </span>
                                </div>

                                <p
                                    class="mt-1 text-sm leading-6 text-slate-500 dark:text-slate-400"
                                >
                                    سجّل آخر دفعة ثم أكد تسليم الجهاز للعميل.
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            :disabled="deliverForm.processing"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-xl text-slate-400 transition hover:bg-slate-50 hover:text-slate-700 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:hover:bg-slate-700 dark:hover:text-white"
                            @click="deliverModal = false"
                        >
                            ×
                        </button>
                    </div>

                    <!-- Device/customer snapshot -->
                    <div
                        class="relative mt-5 grid gap-3 sm:grid-cols-2"
                    >
                        <div
                            class="rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-800/90"
                        >
                            <p
                                class="text-[10px] font-black uppercase tracking-wider text-slate-400"
                            >
                                العميل
                            </p>

                            <strong
                                class="mt-1 block text-sm text-slate-950 dark:text-white"
                            >
                                {{ order.customer_name }}
                            </strong>

                            <span
                                dir="ltr"
                                class="mt-1 block text-right text-xs text-slate-500"
                            >
                                {{ order.customer_phone }}
                            </span>
                        </div>

                        <div
                            class="rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-800/90"
                        >
                            <p
                                class="text-[10px] font-black uppercase tracking-wider text-slate-400"
                            >
                                الجهاز
                            </p>

                            <strong
                                class="mt-1 block text-sm text-slate-950 dark:text-white"
                            >
                                {{ [order.brand, order.model].filter(Boolean).join(' ') || order.device_type }}
                            </strong>

                            <span
                                class="mt-1 block text-xs text-slate-500"
                            >
                                {{ order.order_number }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div
                    class="min-h-0 flex-1 space-y-5 overflow-y-auto p-5 sm:p-6"
                >
                    <!-- Errors -->
                    <div
                        v-if="deliverForm.errors.delivery"
                        class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold leading-6 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-rose-100 font-black text-rose-600 dark:bg-rose-950/40"
                            >
                                !
                            </div>

                            <div>
                                <strong class="block">
                                    تعذر إكمال عملية التسليم
                                </strong>

                                <p class="mt-1 text-xs leading-6">
                                    {{ deliverForm.errors.delivery }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-else-if="Object.keys(deliverForm.errors).length"
                        class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm font-bold leading-6 text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-300"
                    >
                        {{ Object.values(deliverForm.errors)[0] }}
                    </div>

                    <!-- Financial summary -->
                    <section
                        class="grid gap-3 sm:grid-cols-3"
                    >
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/50"
                        >
                            <p
                                class="text-[10px] font-black text-slate-400"
                            >
                                إجمالي الصيانة
                            </p>

                            <strong
                                class="mt-1 block text-base text-slate-950 dark:text-white"
                            >
                                {{ money(order.total_amount) }}
                            </strong>
                        </div>

                        <div
                            class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20"
                        >
                            <p
                                class="text-[10px] font-black text-emerald-500"
                            >
                                المدفوع سابقًا
                            </p>

                            <strong
                                class="mt-1 block text-base text-emerald-700 dark:text-emerald-300"
                            >
                                {{ money(order.paid_amount) }}
                            </strong>
                        </div>

                        <div
                            class="rounded-2xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/50 dark:bg-rose-950/20"
                        >
                            <p
                                class="text-[10px] font-black text-rose-500"
                            >
                                المتبقي قبل التسليم
                            </p>

                            <strong
                                class="mt-1 block text-base text-rose-700 dark:text-rose-300"
                            >
                                {{ money(order.remaining_amount) }}
                            </strong>
                        </div>
                    </section>

                    <!-- Payment amount -->
                    <section
                        class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-900"
                    >
                        <div
                            class="mb-4 flex flex-wrap items-center justify-between gap-3"
                        >
                            <div>
                                <h3
                                    class="text-sm font-black text-slate-950 dark:text-white"
                                >
                                    المبلغ المدفوع الآن
                                </h3>

                                <p
                                    class="mt-1 text-xs text-slate-500"
                                >
                                    غالبًا يكون كامل المبلغ المتبقي عند الاستلام.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-xl bg-emerald-100 px-3 py-2 text-xs font-black text-emerald-700 transition hover:bg-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300"
                                @click="deliverForm.payment_amount = Number(order.remaining_amount || 0)"
                            >
                                دفع كامل المتبقي
                            </button>
                        </div>

                        <div class="relative">
                            <input
                                v-model.number="deliverForm.payment_amount"
                                type="number"
                                min="0"
                                :max="order.remaining_amount"
                                step="0.01"
                                class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 pl-20 text-2xl font-black text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                            />

                            <span
                                class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm font-black text-slate-400"
                            >
                                شيكل
                            </span>
                        </div>
                    </section>

                    <!-- Payment details -->
                    <section
                        v-if="Number(deliverForm.payment_amount || 0) > 0"
                        class="rounded-3xl border border-slate-200 bg-slate-50/70 p-5 dark:border-slate-700 dark:bg-slate-800/40"
                    >
                        <div
                            class="mb-4"
                        >
                            <h3
                                class="text-sm font-black text-slate-950 dark:text-white"
                            >
                                تفاصيل الدفع
                            </h3>

                            <p
                                class="mt-1 text-xs text-slate-500"
                            >
                                حدد الطريقة والحساب الذي ستدخل إليه الدفعة.
                            </p>
                        </div>

                        <div
                            class="grid gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <label
                                    class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200"
                                >
                                    طريقة الدفع
                                    <span class="text-rose-500">*</span>
                                </label>

                                <select
                                    v-model="deliverForm.payment_method"
                                    class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
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
                                <label
                                    class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200"
                                >
                                    الحساب المالي
                                    <span class="text-rose-500">*</span>
                                </label>

                                <select
                                    v-model="deliverForm.financial_account_id"
                                    class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                >
                                    <option value="">
                                        اختر الحساب
                                    </option>

                                    <option
                                        v-for="account in compatibleAccounts(deliverForm.payment_method)"
                                        :key="account.id"
                                        :value="account.id"
                                    >
                                        {{ account.name }}
                                        — {{ money(account.current_balance) }}
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div
                            v-if="selectedDeliveryAccount"
                            class="mt-4 grid grid-cols-2 gap-3 rounded-2xl border border-emerald-200 bg-white p-4 dark:border-emerald-900/50 dark:bg-slate-900"
                        >
                            <div>
                                <p
                                    class="text-[10px] font-black text-emerald-500"
                                >
                                    رصيد الحساب قبل
                                </p>

                                <strong
                                    class="mt-1 block text-sm text-slate-900 dark:text-white"
                                >
                                    {{ money(selectedDeliveryAccount.current_balance) }}
                                </strong>
                            </div>

                            <div>
                                <p
                                    class="text-[10px] font-black text-emerald-500"
                                >
                                    الرصيد بعد الدفعة
                                </p>

                                <strong
                                    class="mt-1 block text-sm text-emerald-700 dark:text-emerald-300"
                                >
                                    {{
                                        money(
                                            Number(
                                                selectedDeliveryAccount.current_balance
                                                || 0
                                            )
                                            + Number(
                                                deliverForm.payment_amount
                                                || 0
                                            )
                                        )
                                    }}
                                </strong>
                            </div>
                        </div>

                        <div
                            class="mt-4 grid gap-4 sm:grid-cols-2"
                        >
                            <div>
                                <label
                                    class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200"
                                >
                                    تاريخ الدفع
                                </label>

                                <input
                                    v-model="deliverForm.paid_at"
                                    type="datetime-local"
                                    :max="nowLocal()"
                                    class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                />
                            </div>

                            <div
                                v-if="['bank_transfer', 'banking_app'].includes(deliverForm.payment_method)"
                            >
                                <label
                                    class="mb-2 block text-sm font-black text-slate-700 dark:text-slate-200"
                                >
                                    مرجع العملية
                                    <span class="font-medium text-slate-400">
                                        اختياري
                                    </span>
                                </label>

                                <input
                                    v-model.trim="deliverForm.transaction_reference"
                                    type="text"
                                    maxlength="255"
                                    class="block w-full rounded-2xl border-slate-300 bg-white px-4 py-4 text-base text-slate-950 shadow-sm focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 dark:border-slate-600 dark:bg-slate-950 dark:text-white"
                                    placeholder="رقم التحويل أو العملية"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Final outcome -->
                    <section
                        class="overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 text-white dark:border-slate-700"
                    >
                        <div
                            class="grid grid-cols-2 gap-px bg-white/10"
                        >
                            <div
                                class="bg-slate-950 p-5"
                            >
                                <p
                                    class="text-[10px] font-black text-slate-400"
                                >
                                    المدفوع الآن
                                </p>

                                <strong
                                    class="mt-1 block text-xl text-emerald-300"
                                >
                                    {{ money(deliverForm.payment_amount) }}
                                </strong>
                            </div>

                            <div
                                class="bg-slate-950 p-5"
                            >
                                <p
                                    class="text-[10px] font-black text-slate-400"
                                >
                                    المتبقي بعد التسليم
                                </p>

                                <strong
                                    class="mt-1 block text-xl"
                                    :class="
                                        deliveryRemainingAfterPayment > 0
                                            ? 'text-rose-300'
                                            : 'text-emerald-300'
                                    "
                                >
                                    {{ money(deliveryRemainingAfterPayment) }}
                                </strong>
                            </div>
                        </div>

                        <div
                            class="border-t border-white/10 px-5 py-4"
                        >
                            <div
                                class="flex items-center gap-3"
                            >
                                <div
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-300"
                                >
                                    ✓
                                </div>

                                <div>
                                    <strong
                                        class="block text-sm"
                                    >
                                        بعد التأكيد سيتم تسليم الجهاز
                                    </strong>

                                    <span
                                        class="mt-1 block text-xs text-slate-400"
                                    >
                                        سيتم تحديث حالة الطلب وتسجيل الدفعة والحركة المالية في نفس العملية.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Debt confirmation -->
                    <label
                        v-if="deliveryRemainingAfterPayment > 0.00001"
                        class="flex cursor-pointer items-start gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-900/50 dark:bg-amber-950/20"
                    >
                        <input
                            v-model="deliverForm.allow_partial_payment"
                            type="checkbox"
                            class="mt-1 h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500"
                        />

                        <span>
                            <strong
                                class="block text-amber-900 dark:text-amber-200"
                            >
                                السماح بالتسليم مع بقاء دين
                            </strong>

                            <span
                                class="mt-1 block text-xs leading-6 text-amber-700 dark:text-amber-300"
                            >
                                سيبقى على العميل
                                <strong>
                                    {{ money(deliveryRemainingAfterPayment) }}
                                </strong>
                                وسيظهر في مستحقات العملاء وكشف حسابه.
                            </span>
                        </span>
                    </label>
                </div>

                <!-- Footer -->
                <div
                    class="grid shrink-0 gap-3 border-t border-slate-200 bg-slate-50 p-5 sm:grid-cols-[1fr_1.4fr] dark:border-slate-800 dark:bg-slate-950/50"
                >
                    <button
                        type="button"
                        :disabled="deliverForm.processing"
                        class="rounded-2xl border border-slate-300 bg-white py-3.5 text-sm font-black text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                        @click="deliverModal = false"
                    >
                        رجوع
                    </button>

                    <button
                        type="submit"
                        :disabled="deliverForm.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 py-3.5 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        <span
                            v-if="deliverForm.processing"
                            class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"
                        ></span>

                        <span v-else>
                            ✓
                        </span>

                        {{
                            deliverForm.processing
                                ? 'جاري تسجيل الدفع والتسليم...'
                                : 'تأكيد الدفع وتسليم الجهاز'
                        }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Intake image preview -->
        <div
            v-if="intakeImageModal"
            class="fixed inset-0 z-[160] flex items-center justify-center bg-slate-950/90 p-4 backdrop-blur-sm"
            @mousedown.self="intakeImageModal = null"
        >
            <div class="relative flex max-h-[92vh] w-full max-w-6xl items-center justify-center">
                <img
                    :src="attachmentUrl(intakeImageModal)"
                    alt="صورة الجهاز عند الاستلام"
                    class="max-h-[88vh] max-w-full rounded-2xl object-contain shadow-2xl"
                />

                <button
                    type="button"
                    class="absolute left-2 top-2 flex h-11 w-11 items-center justify-center rounded-2xl bg-white/95 text-xl font-black text-slate-800 shadow-xl transition hover:bg-white"
                    @click="intakeImageModal = null"
                >
                    ×
                </button>
            </div>
        </div>

        <!-- cancel -->
        <div v-if="cancelModal" class="fixed inset-0 z-[130] flex items-center justify-center bg-slate-950/70 p-4"><form class="w-full max-w-md rounded-3xl bg-white p-6 dark:bg-slate-900" @submit.prevent="cancelOrder"><h2 class="font-black text-rose-700">إلغاء طلب الصيانة</h2><p class="mt-2 text-xs text-slate-500">ستعاد قطع المخزون وستعكس دفعات العميل. القطع الخارجية المشتراة لا تعكس تلقائياً.</p><textarea v-model="cancelForm.reason" required rows="3" class="mt-4 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="سبب الإلغاء"/><button class="mt-4 w-full rounded-xl bg-rose-600 py-3 font-black text-white">تأكيد الإلغاء</button><button type="button" class="mt-2 w-full text-xs text-slate-500" @click="cancelModal = false">رجوع</button></form></div>
    </AuthenticatedLayout>
</template>
