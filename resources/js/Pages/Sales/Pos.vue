<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
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
    products: {
        type: Array,
        default: () => [],
    },

    categories: {
        type: Array,
        default: () => [],
    },

    customers: {
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

    salesWarehouse: {
        type: Object,
        default: () => ({}),
    },
});

const localToday = new Date(
    Date.now() - new Date().getTimezoneOffset() * 60_000
).toISOString().slice(0, 10);

let paymentRowKey = 1;

const createPaymentRow = (overrides = {}) => ({
    key: paymentRowKey++,
    amount: 0,
    payment_method: 'cash',
    financial_account_id: '',
    bank_or_app_name: '',
    transaction_reference: '',
    ...overrides,
});

const form = useForm({
    customer_id: '',
    customer_name: 'عميل نقدي',
    customer_phone: '',
    save_customer: false,
    sale_date: localToday,
    due_date: '',
    items: [],
    invoice_discount: 0,
    notes: '',
    payments: [createPaymentRow()],
    complete_sale: true,

    /*
     * تستخدم فقط عندما يكون صافي البيع أقل من أقل سعر بيع.
     * الموافقة صريحة من صاحب المحل قبل اعتماد الفاتورة.
     */
    minimum_price_approved: false,
    minimum_price_reason: '',
});

const searchInput = ref(null);
const cartPanel = ref(null);
const customerSearchInput = ref(null);

const search = ref('');
const selectedCategory = ref('');
const stockFilter = ref('available');
const checkoutOpen = ref(false);
const customerPickerOpen = ref(false);
const customerSearch = ref('');
const pageError = ref('');
const showClearCartConfirm = ref(false);

const minimumPriceApprovalOpen = ref(false);
const minimumPriceApprovalReason = ref('');
const minimumPriceApprovalError = ref('');

const normalizeText = (value) => String(value ?? '')
    .trim()
    .toLocaleLowerCase('ar');

const productCategoryId = (product) => Number(
    product.category_id ?? product.category?.id ?? 0
);

const productAvailable = (product) => Number(
    product.available_quantity ?? 0
);

const productThreshold = (product) => Number(
    product.low_stock_threshold ?? 5
);

const filteredProducts = computed(() => {
    const term = normalizeText(search.value);

    return props.products.filter((product) => {
        const matchesSearch = !term || [
            product.name,
            product.code,
            product.barcode,
            product.category?.name,
        ].some((value) => normalizeText(value).includes(term));

        const matchesCategory = !selectedCategory.value
            || productCategoryId(product) === Number(selectedCategory.value);

        const available = productAvailable(product);
        const threshold = productThreshold(product);

        const matchesStock = stockFilter.value === 'all'
            || (stockFilter.value === 'available' && available > 0)
            || (stockFilter.value === 'low' && available > 0 && available <= threshold)
            || (stockFilter.value === 'out' && available <= 0);

        return matchesSearch && matchesCategory && matchesStock;
    });
});

const filteredCustomers = computed(() => {
    const term = normalizeText(customerSearch.value);

    if (!term) {
        return props.customers.slice(0, 12);
    }

    return props.customers
        .filter((customer) => [
            customer.name,
            customer.phone,
            customer.code,
        ].some((value) => normalizeText(value).includes(term)))
        .slice(0, 20);
});

const selectedCustomer = computed(() => props.customers.find(
    (customer) => Number(customer.id) === Number(form.customer_id)
) ?? null);

const itemCount = computed(() => form.items.length);

const totalPieces = computed(() => form.items.reduce(
    (sum, item) => sum + Number(item.quantity || 0),
    0
));

const cartSubtotal = computed(() => form.items.reduce(
    (sum, item) => sum + Number(item.line_total || 0),
    0
));

const invoiceDiscount = computed(() => Math.max(
    0,
    Number(form.invoice_discount || 0)
));

const finalTotal = computed(() => Math.max(
    0,
    cartSubtotal.value - invoiceDiscount.value
));

const totalPaid = computed(() => form.payments.reduce(
    (sum, payment) => sum + Number(payment.amount || 0),
    0
));

const remaining = computed(() => Math.max(
    0,
    finalTotal.value - totalPaid.value
));

const paymentProgress = computed(() => {
    if (finalTotal.value <= 0) {
        return 0;
    }

    return Math.min(
        100,
        Math.round((totalPaid.value / finalTotal.value) * 100)
    );
});

const invoiceDiscountError = computed(() => {
    if (invoiceDiscount.value > cartSubtotal.value) {
        return 'خصم الفاتورة لا يمكن أن يتجاوز إجمالي السلة.';
    }

    return '';
});

/*
 * =========================
 * Minimum selling price
 * =========================
 *
 * الحد الأدنى يراقب صافي سعر القطعة بعد خصم الصنف.
 * كما نتحقق من خصم الفاتورة حتى لا يجعل إجمالي البيع
 * أقل من مجموع الحدود الدنيا للمنتجات.
 */
const minimumSellingPrice = (value) => Math.max(
    0,
    Number(value || 0)
);

const itemNetUnitPrice = (item) => {
    const quantity = Math.max(
        1,
        Number(item.quantity || 1)
    );

    const gross = quantity * Number(
        item.unit_selling_price || 0
    );

    const lineDiscount = Math.max(
        0,
        Number(item.line_discount || 0)
    );

    return Math.max(
        0,
        (gross - lineDiscount) / quantity
    );
};

const itemMinimumPriceError = (item) => {
    const minimum = minimumSellingPrice(
        item.minimum_selling_price
    );

    if (minimum <= 0) {
        return '';
    }

    const netUnit = itemNetUnitPrice(item);

    if (netUnit + 0.00001 < minimum) {
        return `صافي سعر القطعة ${formatCurrency(netUnit)} أقل من أقل سعر بيع ${formatCurrency(minimum)}.`;
    }

    return '';
};

const itemsBelowMinimum = computed(() => form.items.filter(
    (item) => Boolean(itemMinimumPriceError(item))
));

const minimumAllowedCartTotal = computed(() => form.items.reduce(
    (sum, item) => sum + (
        minimumSellingPrice(item.minimum_selling_price)
        * Math.max(1, Number(item.quantity || 1))
    ),
    0
));

const maximumSafeInvoiceDiscount = computed(() => Math.max(
    0,
    cartSubtotal.value - minimumAllowedCartTotal.value
));

const minimumPriceInvoiceDiscountError = computed(() => {
    if (itemsBelowMinimum.value.length > 0) {
        return '';
    }

    if (
        minimumAllowedCartTotal.value > 0
        && invoiceDiscount.value > maximumSafeInvoiceDiscount.value + 0.00001
    ) {
        return `خصم الفاتورة كبير جداً وسيجعل صافي البيع أقل من الحدود الدنيا للمنتجات. الحد الآمن حالياً ${formatCurrency(maximumSafeInvoiceDiscount.value)}.`;
    }

    return '';
});

const minimumPriceError = computed(() => {
    if (itemsBelowMinimum.value.length > 0) {
        const item = itemsBelowMinimum.value[0];

        return `المنتج «${item.product_name}»: ${itemMinimumPriceError(item)}`;
    }

    return minimumPriceInvoiceDiscountError.value;
});

const requiresMinimumPriceApproval = computed(() => Boolean(
    minimumPriceError.value
));

const minimumPriceApprovalRows = computed(() => (
    form.items
        .filter((item) => Boolean(itemMinimumPriceError(item)))
        .map((item) => ({
            product_name: item.product_name,
            quantity: Number(item.quantity || 0),
            minimum_price: minimumSellingPrice(item.minimum_selling_price),
            net_unit_price: itemNetUnitPrice(item),
            difference: Math.max(
                0,
                minimumSellingPrice(item.minimum_selling_price)
                - itemNetUnitPrice(item)
            ),
        }))
));

const resetMinimumPriceApproval = () => {
    form.minimum_price_approved = false;
    form.minimum_price_reason = '';
    minimumPriceApprovalReason.value = '';
    minimumPriceApprovalError.value = '';
};

const openMinimumPriceApproval = () => {
    minimumPriceApprovalReason.value = '';
    minimumPriceApprovalError.value = '';
    minimumPriceApprovalOpen.value = true;
};

const closeMinimumPriceApproval = () => {
    if (form.processing) {
        return;
    }

    minimumPriceApprovalOpen.value = false;
    minimumPriceApprovalError.value = '';
};

const paymentError = computed(() => {
    if (totalPaid.value > finalTotal.value + 0.001) {
        return 'إجمالي الدفعات لا يمكن أن يتجاوز إجمالي الفاتورة.';
    }

    for (const payment of form.payments) {
        const amount = Number(payment.amount || 0);

        if (amount < 0) {
            return 'قيمة الدفعة لا يمكن أن تكون سالبة.';
        }

        if (amount <= 0) {
            continue;
        }

        if (!payment.payment_method) {
            return 'اختر طريقة الدفع لكل دفعة مسجلة.';
        }

        const compatibleAccounts = accountsForMethod(
            payment.payment_method
        );

        if (compatibleAccounts.length === 0) {
            return `لا يوجد حساب مالي نشط متوافق مع طريقة «${methodLabel(payment.payment_method)}».`;
        }

        if (!payment.financial_account_id) {
            return `اختر الحساب المالي لدفعة «${methodLabel(payment.payment_method)}».`;
        }

        const selectedAccountIsCompatible = compatibleAccounts.some(
            (account) => Number(account.id) === Number(payment.financial_account_id)
        );

        if (!selectedAccountIsCompatible) {
            return 'الحساب المالي المختار لا يتوافق مع طريقة الدفع.';
        }
    }

    const hasElectronicPayment = form.payments.some((payment) => (
        Number(payment.amount || 0) > 0
        && ['bank_transfer', 'banking_app'].includes(payment.payment_method)
    ));

    if (hasElectronicPayment && !String(form.customer_phone || '').trim()) {
        return 'رقم هاتف العميل مطلوب عند استخدام دفع إلكتروني.';
    }

    return '';
});

const dueDateError = computed(() => {
    if (
        form.due_date
        && form.sale_date
        && form.due_date < form.sale_date
    ) {
        return 'تاريخ الاستحقاق يجب ألا يسبق تاريخ البيع.';
    }

    return '';
});

const canOpenCheckout = computed(() => (
    form.items.length > 0
    && !invoiceDiscountError.value
));

const canCompleteSale = computed(() => (
    canOpenCheckout.value
    && String(form.customer_name || '').trim().length > 0
    && !paymentError.value
    && !dueDateError.value
    && !form.processing
));

const formatCurrency = (value) => `${Number(value || 0).toLocaleString('en-US', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
})} شيكل`;

const methodLabel = (value) => props.paymentMethods?.[value]
    ?? ({
        cash: 'كاش',
        bank_transfer: 'تحويل بنكي',
        banking_app: 'تطبيق بنكي',
    }[value] ?? value ?? 'غير محدد');

const enumValue = (value) => (
    typeof value === 'object' && value !== null
        ? (value.value ?? value.name ?? '')
        : value
);

const accountTypeForMethod = (method) => ({
    cash: 'cash',
    bank_transfer: 'bank',
    banking_app: 'banking_app',
}[method] ?? null);

const accountsForMethod = (method) => {
    const expectedType = accountTypeForMethod(method);

    if (!expectedType) {
        return [];
    }

    return props.financialAccounts.filter((account) => (
        account.is_active !== false
        && enumValue(account.type) === expectedType
    ));
};

const accountById = (accountId) => props.financialAccounts.find(
    (account) => Number(account.id) === Number(accountId)
) ?? null;

const selectDefaultAccount = (payment) => {
    const compatibleAccounts = accountsForMethod(payment.payment_method);
    const currentIsCompatible = compatibleAccounts.some(
        (account) => Number(account.id) === Number(payment.financial_account_id)
    );

    if (!currentIsCompatible) {
        payment.financial_account_id = compatibleAccounts[0]?.id ?? '';
    }

    const selectedAccount = accountById(payment.financial_account_id);
    payment.bank_or_app_name = selectedAccount?.name ?? '';

    if (payment.payment_method === 'cash') {
        payment.transaction_reference = '';
    }
};

const paymentAccountLabel = (payment) => (
    accountById(payment.financial_account_id)?.name
    ?? 'لم يتم اختيار حساب'
);

const paymentComposition = computed(() => {
    const rows = form.payments.filter(
        (payment) => Number(payment.amount || 0) > 0
    );

    if (rows.length === 0) {
        return {
            label: 'بيع آجل / بدون دفعة',
            details: 'لن يتم تسجيل حركة مالية عند الحفظ.',
            classes: 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
        };
    }

    const totalsByMethod = rows.reduce((totals, payment) => {
        const method = payment.payment_method;
        totals[method] = (totals[method] || 0) + Number(payment.amount || 0);
        return totals;
    }, {});

    const activeMethods = Object.entries(totalsByMethod)
        .filter(([, amount]) => amount > 0);

    const details = activeMethods.map(([method, amount]) => {
        const percentage = finalTotal.value > 0
            ? Math.round((amount / finalTotal.value) * 100)
            : 0;

        return `${methodLabel(method)} ${percentage}% (${formatCurrency(amount)})`;
    }).join(' + ');

    if (activeMethods.length === 1) {
        const [method, amount] = activeMethods[0];
        const isFull = Math.abs(amount - finalTotal.value) < 0.01;

        return {
            label: isFull
                ? `${methodLabel(method)} كامل`
                : `${methodLabel(method)} جزئي`,
            details,
            classes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        };
    }

    const cashAmount = totalsByMethod.cash || 0;
    const bankAmount = totalsByMethod.bank_transfer || 0;
    const isHalfCashHalfBank = activeMethods.length === 2
        && cashAmount > 0
        && bankAmount > 0
        && Math.abs(cashAmount - bankAmount) < 0.01;

    return {
        label: isHalfCashHalfBank
            ? 'نصف كاش + نصف بنكي'
            : 'دفع مختلط',
        details,
        classes: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
    };
});

const cartQuantity = (productId) => form.items.find(
    (item) => Number(item.product_id) === Number(productId)
)?.quantity ?? 0;

const productImage = (product) => product.image_path
    ? `/storage/${product.image_path}`
    : null;

const stockStatus = (product) => {
    const available = productAvailable(product);
    const threshold = productThreshold(product);

    if (available <= 0) {
        return {
            label: 'نافد',
            classes: 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
        };
    }

    if (available <= threshold) {
        return {
            label: 'منخفض',
            classes: 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
        };
    }

    return {
        label: `${available} متوفر`,
        classes: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
    };
};

const recalculateItem = (item) => {
    item.quantity = Math.max(
        1,
        Math.min(
            Number(item.max_quantity || 1),
            Math.floor(Number(item.quantity || 1))
        )
    );

    item.unit_selling_price = Math.max(
        0,
        Number(item.unit_selling_price || 0)
    );

    const beforeDiscount = item.quantity * item.unit_selling_price;

    item.line_discount = Math.max(
        0,
        Math.min(
            beforeDiscount,
            Number(item.line_discount || 0)
        )
    );

    item.line_total = Number(
        (beforeDiscount - item.line_discount).toFixed(2)
    );
};

const addToCart = (product) => {
    pageError.value = '';

    const available = productAvailable(product);

    if (available <= 0) {
        pageError.value = `المنتج «${product.name}» غير متوفر في مخزون المبيعات.`;
        return;
    }

    const existing = form.items.find(
        (item) => Number(item.product_id) === Number(product.id)
    );

    if (existing) {
        if (Number(existing.quantity) >= available) {
            pageError.value = `لا يمكن تجاوز الكمية المتوفرة من «${product.name}» (${available}).`;
            return;
        }

        existing.quantity = Number(existing.quantity || 0) + 1;
        recalculateItem(existing);
        return;
    }

    const price = Number(product.selling_price || 0);

    form.items.push({
        product_id: product.id,
        product_name: product.name || 'منتج',
        product_code: product.code || '',
        image_path: product.image_path || null,
        quantity: 1,
        max_quantity: available,
        unit_selling_price: price,
        minimum_selling_price: Number(product.minimum_selling_price || 0),
        line_discount: 0,
        line_total: price,
    });
};

const removeFromCart = (index) => {
    form.items.splice(index, 1);
};

const changeQuantity = (item, delta) => {
    const nextQuantity = Number(item.quantity || 0) + delta;

    if (nextQuantity < 1) {
        return;
    }

    if (nextQuantity > Number(item.max_quantity || 0)) {
        pageError.value = `الكمية القصوى المتوفرة من «${item.product_name}» هي ${item.max_quantity}.`;
        return;
    }

    item.quantity = nextQuantity;
    recalculateItem(item);
};

const clearCart = () => {
    form.items = [];
    form.invoice_discount = 0;
    form.payments = [createPaymentRow()];
    form.due_date = '';
    pageError.value = '';
    showClearCartConfirm.value = false;
};

const handleSearchEnter = () => {
    const term = normalizeText(search.value);

    if (!term) {
        return;
    }

    const exactMatch = props.products.find((product) => (
        normalizeText(product.code) === term
        || normalizeText(product.barcode) === term
    ));

    if (exactMatch) {
        addToCart(exactMatch);
        search.value = '';
        return;
    }

    if (filteredProducts.value.length === 1) {
        addToCart(filteredProducts.value[0]);
        search.value = '';
    }
};

const focusSearch = async () => {
    await nextTick();
    searchInput.value?.focus();
    searchInput.value?.select();
};

const scrollToCart = async () => {
    await nextTick();
    cartPanel.value?.scrollIntoView({
        behavior: 'smooth',
        block: 'start',
    });
};

const chooseCustomer = (customer) => {
    form.customer_id = customer.id;
    form.customer_name = customer.name || '';
    form.customer_phone = customer.phone || '';
    form.save_customer = false;
    customerSearch.value = customer.name || '';
    customerPickerOpen.value = false;
};

const chooseCashCustomer = () => {
    form.customer_id = '';
    form.customer_name = 'عميل نقدي';
    form.customer_phone = '';
    form.save_customer = false;
    customerSearch.value = '';
    customerPickerOpen.value = false;
};

const clearSelectedCustomer = () => {
    form.customer_id = '';
    form.customer_name = '';
    form.customer_phone = '';
    customerSearch.value = '';
    form.save_customer = true;

    nextTick(() => customerSearchInput.value?.focus());
};

const addPayment = () => {
    const payment = createPaymentRow();
    selectDefaultAccount(payment);
    form.payments.push(payment);
};

const removePayment = (index) => {
    if (form.payments.length === 1) {
        form.payments[0] = createPaymentRow();
        return;
    }

    form.payments.splice(index, 1);
};

const paymentRowForMethod = (method, amount) => {
    const payment = createPaymentRow({
        amount: Number(amount.toFixed(2)),
        payment_method: method,
    });

    selectDefaultAccount(payment);
    return payment;
};

const setNoPayment = () => {
    const payment = createPaymentRow();
    selectDefaultAccount(payment);
    form.payments = [payment];
    form.due_date = '';
};

const setFullCashPayment = () => {
    form.payments = [paymentRowForMethod('cash', finalTotal.value)];
    form.due_date = '';
};

const setFullBankPayment = () => {
    form.payments = [paymentRowForMethod('bank_transfer', finalTotal.value)];
    form.due_date = '';
};

const setHalfCashHalfBankPayment = () => {
    const firstHalf = Number((finalTotal.value / 2).toFixed(2));
    const secondHalf = Number((finalTotal.value - firstHalf).toFixed(2));

    form.payments = [
        paymentRowForMethod('cash', firstHalf),
        paymentRowForMethod('bank_transfer', secondHalf),
    ];

    form.due_date = '';
};

const setHalfCashPayment = () => {
    form.payments = [paymentRowForMethod('cash', finalTotal.value / 2)];
};

const openCheckout = async () => {
    if (!canOpenCheckout.value) {
        pageError.value = invoiceDiscountError.value
            || 'أضف منتجاً واحداً على الأقل قبل إتمام البيع.';
        return;
    }

    pageError.value = minimumPriceError.value || '';
    checkoutOpen.value = true;

    await nextTick();
    customerSearchInput.value?.focus();
};

const closeCheckout = () => {
    if (form.processing) {
        return;
    }

    checkoutOpen.value = false;
    customerPickerOpen.value = false;
};

const validateBeforeSubmit = (
    completeSale,
    allowApprovedMinimumPrice = false
) => {
    pageError.value = '';

    if (form.items.length === 0) {
        pageError.value = 'أضف منتجاً واحداً على الأقل.';
        return false;
    }

    for (const item of form.items) {
        recalculateItem(item);

        if (Number(item.quantity) > Number(item.max_quantity)) {
            pageError.value = `الكمية المطلوبة من «${item.product_name}» تتجاوز المخزون المتوفر.`;
            return false;
        }
    }

    if (invoiceDiscountError.value) {
        pageError.value = invoiceDiscountError.value;
        return false;
    }

    if (
        completeSale
        && minimumPriceError.value
        && !allowApprovedMinimumPrice
        && !form.minimum_price_approved
    ) {
        pageError.value = minimumPriceError.value;
        return false;
    }

    if (!String(form.customer_name || '').trim()) {
        pageError.value = 'أدخل اسم العميل أو اختر «عميل نقدي».';
        return false;
    }

    if (dueDateError.value) {
        pageError.value = dueDateError.value;
        return false;
    }

    if (paymentError.value) {
        pageError.value = paymentError.value;
        return false;
    }

    if (completeSale && remaining.value > 0 && form.due_date && form.due_date < form.sale_date) {
        pageError.value = 'راجع تاريخ استحقاق المبلغ المتبقي.';
        return false;
    }

    return true;
};

const submitInvoice = (
    completeSale,
    allowApprovedMinimumPrice = false
) => {
    if (
        !validateBeforeSubmit(
            completeSale,
            allowApprovedMinimumPrice
        )
    ) {
        if (!checkoutOpen.value) {
            openCheckout();
        }
        return;
    }

    form.complete_sale = completeSale;

    form.transform((data) => ({
        ...data,
        customer_id: data.customer_id || null,
        customer_name: String(data.customer_name || '').trim() || 'عميل نقدي',
        customer_phone: String(data.customer_phone || '').trim() || null,
        due_date: remaining.value > 0
            ? (data.due_date || null)
            : null,
        invoice_discount: Number(data.invoice_discount || 0),
        items: data.items.map((item) => ({
            product_id: item.product_id,
            quantity: Number(item.quantity),
            unit_selling_price: Number(item.unit_selling_price || 0),
            line_discount: Number(item.line_discount || 0),
        })),
        payments: data.payments
            .filter((payment) => Number(payment.amount || 0) > 0)
            .map((payment) => ({
                amount: Number(payment.amount),
                payment_method: payment.payment_method,
                financial_account_id: payment.financial_account_id
                    ? Number(payment.financial_account_id)
                    : null,
                bank_or_app_name: accountById(payment.financial_account_id)?.name
                    ?? String(payment.bank_or_app_name || '').trim()
                    ?? null,
                transaction_reference: String(payment.transaction_reference || '').trim() || null,
                paid_at: new Date().toISOString(),
            })),
        complete_sale: completeSale,

        minimum_price_approved:
            completeSale
            && Boolean(
                data.minimum_price_approved
            ),

        minimum_price_reason:
            completeSale
            && data.minimum_price_approved
                ? String(
                    data.minimum_price_reason
                    || ''
                ).trim()
                : null,
    })).post(route('sales.store'), {
        preserveScroll: true,

        onSuccess: () => {
            minimumPriceApprovalOpen.value = false;
            resetMinimumPriceApproval();
        },

        onError: (errors) => {
            pageError.value = Object.values(errors || {})[0]
                || 'تعذر حفظ الفاتورة. راجع البيانات وحاول مرة أخرى.';
        },
    });
};

const saveDraft = () => {
    /*
     * المسودة لا تحتاج موافقة، لأن المخزون والحسابات لم تعتمد بعد.
     */
    resetMinimumPriceApproval();
    submitInvoice(false);
};

const completeSale = () => {
    /*
     * أولاً نفحص كل شيء ما عدا حد السعر.
     * إذا كان هناك خطأ آخر، يظهر للمستخدم مباشرة.
     */
    if (
        !validateBeforeSubmit(
            true,
            true
        )
    ) {
        return;
    }

    if (
        requiresMinimumPriceApproval.value
        && !form.minimum_price_approved
    ) {
        openMinimumPriceApproval();
        return;
    }

    submitInvoice(
        true,
        true
    );
};

const confirmMinimumPriceApproval = () => {
    const reason = String(
        minimumPriceApprovalReason.value
        || ''
    ).trim();

    if (reason.length < 3) {
        minimumPriceApprovalError.value =
            'اكتب سبباً واضحاً للموافقة على البيع تحت الحد الأدنى.';
        return;
    }

    minimumPriceApprovalError.value = '';

    form.minimum_price_approved = true;
    form.minimum_price_reason = reason;

    minimumPriceApprovalOpen.value = false;

    submitInvoice(
        true,
        true
    );
};

const handleGlobalKeydown = (event) => {
    if (event.key === 'F2') {
        event.preventDefault();
        focusSearch();
        return;
    }

    if (event.key === 'F4') {
        event.preventDefault();
        openCheckout();
        return;
    }

    if (event.key === 'Escape' && checkoutOpen.value) {
        event.preventDefault();
        closeCheckout();
        return;
    }

    if (
        event.ctrlKey
        && event.key === 'Enter'
        && checkoutOpen.value
    ) {
        event.preventDefault();
        completeSale();
    }
};

watch(
    () => [
        form.invoice_discount,
        ...form.items.flatMap((item) => [
            item.product_id,
            item.quantity,
            item.unit_selling_price,
            item.line_discount,
        ]),
    ],
    () => {
        /*
         * أي تغيير في السعر/الكمية/الخصم بعد الموافقة
         * يلغي الموافقة السابقة ويطلب موافقة جديدة.
         */
        if (form.minimum_price_approved) {
            resetMinimumPriceApproval();
        }
    },
    {
        deep: true,
    }
);

watch(
    () => form.invoice_discount,
    (value) => {
        const normalized = Math.max(0, Number(value || 0));

        if (normalized > cartSubtotal.value) {
            form.invoice_discount = Number(cartSubtotal.value.toFixed(2));
        }
    }
);

watch(finalTotal, (value) => {
    if (totalPaid.value > value) {
        const firstPayment = form.payments[0];

        form.payments = [createPaymentRow({
            amount: Number(value.toFixed(2)),
            payment_method: firstPayment?.payment_method || 'cash',
            financial_account_id: firstPayment?.financial_account_id || '',
            bank_or_app_name: firstPayment?.bank_or_app_name || '',
            transaction_reference: firstPayment?.transaction_reference || '',
        })];
    }
});

watch(
    () => form.payments,
    (payments) => {
        for (const payment of payments) {
            selectDefaultAccount(payment);
        }
    },
    { deep: true }
);

watch(
    [
        checkoutOpen,
        minimumPriceApprovalOpen,
    ],
    ([checkoutIsOpen, approvalIsOpen]) => {
        document.body.style.overflow =
            checkoutIsOpen || approvalIsOpen
                ? 'hidden'
                : '';
    }
);

onMounted(() => {
    window.addEventListener('keydown', handleGlobalKeydown);

    form.payments.forEach((payment) => {
        selectDefaultAccount(payment);
    });

    focusSearch();
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleGlobalKeydown);
    document.body.style.overflow = '';
});
</script><template>
    <Head title="نقطة البيع" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-slate-400">
                        <span class="text-indigo-600 dark:text-indigo-400">المبيعات</span>
                        <span>/</span>
                        <span>نقطة البيع</span>
                        <span
                            v-if="salesWarehouse?.name"
                            class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-black text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300"
                        >
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            {{ salesWarehouse.name }}
                        </span>
                    </div>

                    <div class="mt-1 flex items-center gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-600 text-white shadow-lg shadow-indigo-600/20">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-2.3 2.3c-.63.63-.18 1.7.71 1.7H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white sm:text-3xl">
                                نقطة البيع
                            </h1>
                            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                بيع أسرع، خطوات أقل، ومراجعة واضحة قبل اعتماد الفاتورة.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="hidden items-center gap-2 rounded-2xl border border-slate-200 bg-white/90 px-3 py-2 text-xs font-semibold text-slate-500 shadow-sm backdrop-blur dark:border-slate-700 dark:bg-slate-800/90 dark:text-slate-400 lg:flex">
                        <span class="rounded-lg bg-slate-100 px-2 py-1 font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200">F2</span>
                        بحث
                        <span class="h-4 w-px bg-slate-200 dark:bg-slate-700"></span>
                        <span class="rounded-lg bg-slate-100 px-2 py-1 font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200">F4</span>
                        دفع
                        <span class="h-4 w-px bg-slate-200 dark:bg-slate-700"></span>
                        <span class="rounded-lg bg-slate-100 px-2 py-1 font-black text-slate-700 dark:bg-slate-700 dark:text-slate-200">Ctrl + Enter</span>
                        اعتماد
                    </div>

                    <Link
                        :href="route('sales.index')"
                        class="inline-flex items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-black text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:border-indigo-200 hover:text-indigo-700 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:border-indigo-700 dark:hover:text-indigo-300"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h10.5" />
                        </svg>
                        فواتير البيع
                    </Link>
                </div>
            </div>
        </template>

        <div class="relative pb-24 xl:pb-0">
            <div class="pointer-events-none absolute inset-x-0 -top-10 -z-10 h-72 overflow-hidden">
                <div class="absolute right-1/4 top-0 h-40 w-40 rounded-full bg-indigo-500/5 blur-3xl dark:bg-indigo-400/5"></div>
                <div class="absolute left-1/4 top-16 h-40 w-40 rounded-full bg-blue-500/5 blur-3xl dark:bg-blue-400/5"></div>
            </div>

            <div
                v-if="pageError"
                class="mb-4 flex items-start justify-between gap-4 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-sm font-semibold text-rose-700 shadow-sm backdrop-blur dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 9v3.75m9-1.5a9 9 0 11-18 0 9 9 0 0118 0zM12 16.5h.008v.008H12V16.5z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-black">راجع العملية</p>
                        <p class="mt-0.5 leading-6">{{ pageError }}</p>
                    </div>
                </div>

                <button
                    type="button"
                    class="rounded-xl p-2 transition hover:bg-rose-100 dark:hover:bg-rose-900/40"
                    @click="pageError = ''"
                >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_430px] 2xl:grid-cols-[minmax(0,1fr)_460px]">
                <!-- كتالوج المنتجات -->
                <section class="min-w-0 space-y-4">
                    <div class="overflow-hidden rounded-[28px] border border-slate-200/80 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
                        <div class="relative overflow-hidden border-b border-slate-200/80 bg-white p-4 dark:border-slate-700 dark:bg-slate-800 sm:p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-center">
                                <div class="relative min-w-0 flex-1">
                                    <svg class="pointer-events-none absolute right-4 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" />
                                    </svg>

                                    <input
                                        ref="searchInput"
                                        v-model="search"
                                        type="search"
                                        autocomplete="off"
                                        placeholder="ابحث بالاسم، الكود، أو امسح الباركود..."
                                        class="block w-full rounded-2xl border-slate-200 bg-slate-50 py-3.5 pr-12 pl-20 text-sm font-bold text-slate-900 shadow-inner shadow-slate-100/30 transition placeholder:font-medium placeholder:text-slate-400 focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:shadow-none dark:focus:border-indigo-600 dark:focus:bg-slate-900"
                                        @keydown.enter.prevent="handleSearchEnter"
                                    />

                                    <div class="pointer-events-none absolute left-3 top-1/2 flex -translate-y-1/2 items-center gap-1.5">
                                        <span class="rounded-lg border border-slate-200 bg-white px-2 py-1 text-[9px] font-black text-slate-400 shadow-sm dark:border-slate-700 dark:bg-slate-800">ENTER</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <div class="inline-flex items-center gap-2 rounded-2xl bg-slate-100 p-1 dark:bg-slate-900">
                                        <button
                                            v-for="option in [
                                                { value: 'available', label: 'متوفر' },
                                                { value: 'low', label: 'منخفض' },
                                                { value: 'out', label: 'نافد' },
                                                { value: 'all', label: 'الكل' },
                                            ]"
                                            :key="option.value"
                                            type="button"
                                            class="rounded-xl px-3 py-2 text-[11px] font-black transition"
                                            :class="stockFilter === option.value
                                                ? 'bg-white text-slate-950 shadow-sm dark:bg-slate-700 dark:text-white'
                                                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'"
                                            @click="stockFilter = option.value"
                                        >
                                            {{ option.label }}
                                        </button>
                                    </div>

                                    <button
                                        v-if="search || selectedCategory || stockFilter !== 'available'"
                                        type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 transition hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-indigo-700 dark:hover:text-indigo-300"
                                        title="إعادة ضبط الفلاتر"
                                        @click="search = ''; selectedCategory = ''; stockFilter = 'available'; focusSearch()"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4.5 12a7.5 7.5 0 101.9-5M4.5 4.5v4.75h4.75" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-4 flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide">
                                <button
                                    type="button"
                                    class="shrink-0 rounded-xl border px-4 py-2 text-xs font-black transition"
                                    :class="selectedCategory === ''
                                        ? 'border-indigo-600 bg-indigo-600 text-white shadow-md shadow-indigo-600/15'
                                        : 'border-slate-200 bg-white text-slate-500 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-300'"
                                    @click="selectedCategory = ''"
                                >
                                    كل المنتجات
                                </button>

                                <button
                                    v-for="category in categories"
                                    :key="category.id"
                                    type="button"
                                    class="shrink-0 rounded-xl border px-4 py-2 text-xs font-black transition"
                                    :class="Number(selectedCategory) === Number(category.id)
                                        ? 'border-indigo-600 bg-indigo-600 text-white shadow-md shadow-indigo-600/15'
                                        : 'border-slate-200 bg-white text-slate-500 hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-300'"
                                    @click="selectedCategory = category.id"
                                >
                                    {{ category.name }}
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-4 px-4 pt-4 sm:px-5">
                            <div>
                                <h2 class="text-sm font-black text-slate-950 dark:text-white">المنتجات المتاحة</h2>
                                <p class="mt-0.5 text-xs text-slate-400">اضغط على المنتج لإضافته مباشرة إلى السلة.</p>
                            </div>

                            <div class="flex items-center gap-2 text-[11px] font-bold text-slate-500 dark:text-slate-400">
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 dark:bg-slate-700">{{ filteredProducts.length }} نتيجة</span>
                                <span v-if="totalPieces" class="rounded-full bg-indigo-50 px-2.5 py-1 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300">{{ totalPieces }} في السلة</span>
                            </div>
                        </div>

                        <div
                            v-if="filteredProducts.length"
                            class="grid grid-cols-2 gap-3 p-4 sm:grid-cols-3 sm:p-5 lg:grid-cols-4 2xl:grid-cols-5"
                        >
                            <button
                                v-for="product in filteredProducts"
                                :key="product.id"
                                type="button"
                                class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white text-right transition duration-200 hover:-translate-y-1 hover:border-indigo-200 hover:shadow-xl hover:shadow-slate-200/60 focus:outline-none focus:ring-4 focus:ring-indigo-500/10 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-800 dark:hover:border-indigo-700 dark:hover:shadow-none"
                                :disabled="productAvailable(product) <= 0"
                                @click="addToCart(product)"
                            >
                                <div class="relative aspect-[4/3] overflow-hidden bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900">
                                    <img
                                        v-if="productImage(product)"
                                        :src="productImage(product)"
                                        :alt="product.name"
                                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.04]"
                                    />

                                    <div v-else class="flex h-full items-center justify-center text-slate-300 dark:text-slate-600">
                                        <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.4" d="M7 2h10a2 2 0 012 2v16a2 2 0 01-2 2H7a2 2 0 01-2-2V4a2 2 0 012-2zm5 17h.01" />
                                        </svg>
                                    </div>

                                    <div class="absolute inset-x-2 top-2 flex items-start justify-between gap-2">
                                        <span
                                            class="rounded-lg px-2 py-1 text-[9px] font-black shadow-sm backdrop-blur"
                                            :class="stockStatus(product).classes"
                                        >
                                            {{ stockStatus(product).label }}
                                        </span>

                                        <span
                                            v-if="cartQuantity(product.id)"
                                            class="flex h-7 min-w-7 items-center justify-center rounded-full bg-indigo-600 px-2 text-[10px] font-black text-white shadow-lg shadow-indigo-600/30"
                                        >
                                            {{ cartQuantity(product.id) }}
                                        </span>
                                    </div>

                                    <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-slate-950/35 to-transparent opacity-0 transition group-hover:opacity-100"></div>
                                    <div class="absolute bottom-2 left-2 flex h-8 w-8 translate-y-2 items-center justify-center rounded-xl bg-white text-indigo-600 opacity-0 shadow-lg transition group-hover:translate-y-0 group-hover:opacity-100 dark:bg-slate-800 dark:text-indigo-300">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                    </div>
                                </div>

                                <div class="p-3">
                                    <p class="truncate text-[10px] font-bold text-indigo-600 dark:text-indigo-400">
                                        {{ product.category?.name || 'بدون فئة' }}
                                    </p>
                                    <h3 class="mt-1 line-clamp-2 min-h-[38px] text-sm font-black leading-5 text-slate-900 dark:text-white">
                                        {{ product.name }}
                                    </h3>
                                    <p dir="ltr" class="mt-1 truncate text-right text-[10px] text-slate-400">
                                        {{ product.code || product.barcode || '—' }}
                                    </p>

                                    <div class="mt-3 flex items-end justify-between gap-2 border-t border-slate-100 pt-3 dark:border-slate-700">
                                        <div>
                                            <p class="text-[9px] font-bold text-slate-400">سعر البيع</p>
                                            <strong dir="ltr" class="mt-0.5 block text-sm text-slate-950 dark:text-white">
                                                {{ formatCurrency(product.selling_price) }}
                                            </strong>
                                        </div>

                                        <span
                                            v-if="Number(product.minimum_selling_price || 0) > 0"
                                            class="rounded-lg bg-amber-50 px-2 py-1 text-[9px] font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300"
                                            :title="`أقل سعر بيع: ${formatCurrency(product.minimum_selling_price)}`"
                                        >
                                            حد أدنى
                                        </span>
                                    </div>
                                </div>
                            </button>
                        </div>

                        <div
                            v-else
                            class="m-4 rounded-3xl border-2 border-dashed border-slate-200 bg-slate-50 px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900/50 sm:m-5"
                        >
                            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-white text-slate-300 shadow-sm dark:bg-slate-800 dark:text-slate-600">
                                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M21 21l-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" />
                                </svg>
                            </div>
                            <h3 class="mt-4 font-black text-slate-900 dark:text-white">لا توجد منتجات مطابقة</h3>
                            <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">غيّر البحث أو الفئة أو حالة المخزون.</p>
                            <button
                                type="button"
                                class="mt-5 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white transition hover:bg-indigo-700"
                                @click="search = ''; selectedCategory = ''; stockFilter = 'available'; focusSearch()"
                            >
                                إعادة ضبط العرض
                            </button>
                        </div>
                    </div>
                </section>

                <!-- السلة -->
                <aside ref="cartPanel" id="pos-cart" class="min-w-0 xl:sticky xl:top-5">
                    <div class="overflow-hidden rounded-[28px] border border-slate-200/90 bg-white shadow-xl shadow-slate-200/40 dark:border-slate-700 dark:bg-slate-800 dark:shadow-none">
                        <div class="relative overflow-hidden border-b border-slate-200 bg-gradient-to-l from-slate-950 via-slate-900 to-indigo-950 p-5 text-white dark:border-slate-700">
                            <div class="pointer-events-none absolute -left-10 -top-16 h-36 w-36 rounded-full bg-blue-500/15 blur-3xl"></div>
                            <div class="pointer-events-none absolute -bottom-16 right-1/3 h-32 w-32 rounded-full bg-indigo-400/10 blur-3xl"></div>

                            <div class="relative flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-300">Current sale</p>
                                    <div class="mt-1 flex items-center gap-2">
                                        <h2 class="text-xl font-black">سلة البيع</h2>
                                        <span class="rounded-full border border-white/10 bg-white/10 px-2.5 py-1 text-[10px] font-black text-slate-200">
                                            {{ itemCount }} صنف
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-slate-400">{{ totalPieces }} قطعة في العملية الحالية</p>
                                </div>

                                <button
                                    v-if="form.items.length"
                                    type="button"
                                    class="rounded-xl border border-white/10 bg-white/5 p-2.5 text-slate-300 transition hover:border-rose-400/30 hover:bg-rose-500/10 hover:text-rose-300"
                                    title="تفريغ السلة"
                                    @click="showClearCartConfirm = true"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12m-10 0V5.5A1.5 1.5 0 019.5 4h5A1.5 1.5 0 0116 5.5V7m-8 0 .7 12a1.5 1.5 0 001.5 1.4h3.6a1.5 1.5 0 001.5-1.4L16 7M10 11v5m4-5v5" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="pos-scroll max-h-[48vh] space-y-3 overflow-y-auto p-3.5 xl:max-h-[calc(100vh-500px)]">
                            <div v-if="form.items.length === 0" class="px-4 py-14 text-center">
                                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-3xl bg-indigo-50 text-indigo-400 dark:bg-indigo-950/30 dark:text-indigo-500">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-2.3 2.3c-.63.63-.18 1.7.71 1.7H17" />
                                    </svg>
                                </div>
                                <h3 class="mt-4 font-black text-slate-950 dark:text-white">ابدأ بإضافة أول منتج</h3>
                                <p class="mx-auto mt-2 max-w-xs text-sm leading-6 text-slate-500 dark:text-slate-400">
                                    اختر منتجاً من الكتالوج أو استخدم البحث والباركود.
                                </p>
                                <button
                                    type="button"
                                    class="mt-5 inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-black text-white transition hover:bg-indigo-700"
                                    @click="focusSearch"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" />
                                    </svg>
                                    البحث عن منتج
                                </button>
                            </div>

                            <article
                                v-for="(item, index) in form.items"
                                :key="item.product_id"
                                class="group rounded-2xl border border-slate-200 bg-white p-3.5 transition hover:border-indigo-200 hover:shadow-md dark:border-slate-700 dark:bg-slate-800 dark:hover:border-indigo-700"
                            >
                                <div class="flex items-start gap-3">
                                    <div class="h-14 w-14 shrink-0 overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-700">
                                        <img
                                            v-if="item.image_path"
                                            :src="`/storage/${item.image_path}`"
                                            :alt="item.product_name"
                                            class="h-full w-full object-cover"
                                        />
                                        <div v-else class="flex h-full items-center justify-center text-slate-300 dark:text-slate-600">
                                            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 2h10a2 2 0 012 2v16a2 2 0 01-2 2H7a2 2 0 01-2-2V4a2 2 0 012-2zm5 17h.01" />
                                            </svg>
                                        </div>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-black text-slate-950 dark:text-white">{{ item.product_name }}</p>
                                                <p dir="ltr" class="mt-0.5 truncate text-right text-[10px] text-slate-400">{{ item.product_code || '—' }}</p>
                                            </div>

                                            <button
                                                type="button"
                                                class="shrink-0 rounded-lg p-1.5 text-slate-300 transition hover:bg-rose-50 hover:text-rose-600 dark:text-slate-500 dark:hover:bg-rose-950/30 dark:hover:text-rose-300"
                                                title="حذف المنتج"
                                                @click="removeFromCart(index)"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        </div>

                                        <div class="mt-3 flex items-center justify-between gap-3">
                                            <div class="inline-flex items-center rounded-xl border border-slate-200 bg-slate-50 p-1 dark:border-slate-700 dark:bg-slate-900">
                                                <button
                                                    type="button"
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 transition hover:bg-white hover:text-indigo-600 dark:hover:bg-slate-700 dark:hover:text-indigo-300"
                                                    @click="changeQuantity(item, -1)"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M5 12h14" /></svg>
                                                </button>
                                                <input
                                                    v-model.number="item.quantity"
                                                    type="number"
                                                    min="1"
                                                    :max="item.max_quantity"
                                                    class="w-12 border-0 bg-transparent p-0 text-center text-xs font-black text-slate-900 focus:ring-0 dark:text-white"
                                                    @input="recalculateItem(item)"
                                                />
                                                <button
                                                    type="button"
                                                    class="flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 transition hover:bg-white hover:text-indigo-600 dark:hover:bg-slate-700 dark:hover:text-indigo-300"
                                                    @click="changeQuantity(item, 1)"
                                                >
                                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M12 5v14m-7-7h14" /></svg>
                                                </button>
                                            </div>

                                            <div class="text-left">
                                                <p class="text-[9px] font-bold text-slate-400">إجمالي الصنف</p>
                                                <strong dir="ltr" class="mt-0.5 block text-sm text-slate-950 dark:text-white">{{ formatCurrency(item.line_total) }}</strong>
                                            </div>
                                        </div>

                                        <div class="mt-3 grid grid-cols-2 gap-2">
                                            <label class="block">
                                                <span class="mb-1 block text-[9px] font-bold text-slate-400">سعر الوحدة</span>
                                                <input
                                                    v-model.number="item.unit_selling_price"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="block w-full rounded-xl border-slate-200 bg-slate-50 px-2.5 py-2 text-xs font-black text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                                    :class="itemMinimumPriceError(item) ? 'border-rose-300 dark:border-rose-800' : ''"
                                                    @input="recalculateItem(item)"
                                                />
                                            </label>

                                            <label class="block">
                                                <span class="mb-1 block text-[9px] font-bold text-slate-400">خصم الصنف</span>
                                                <input
                                                    v-model.number="item.line_discount"
                                                    type="number"
                                                    min="0"
                                                    :max="Number(item.quantity) * Number(item.unit_selling_price)"
                                                    step="0.01"
                                                    class="block w-full rounded-xl border-slate-200 bg-slate-50 px-2.5 py-2 text-xs font-black text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                                    @input="recalculateItem(item)"
                                                />
                                            </label>
                                        </div>

                                        <div
                                            v-if="Number(item.minimum_selling_price || 0) > 0"
                                            class="mt-2 flex items-center justify-between gap-2 rounded-xl px-2.5 py-2 text-[10px] font-bold"
                                            :class="itemMinimumPriceError(item)
                                                ? 'bg-rose-50 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300'
                                                : 'bg-amber-50 text-amber-700 dark:bg-amber-950/20 dark:text-amber-300'"
                                        >
                                            <span>{{ itemMinimumPriceError(item) ? 'أقل من الحد المسموح' : 'ضمن الحد الأدنى' }}</span>
                                            <span dir="ltr">{{ formatCurrency(item.minimum_selling_price) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div class="border-t border-slate-200 bg-slate-50/70 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                            <div class="space-y-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-semibold text-slate-500 dark:text-slate-400">إجمالي المنتجات</span>
                                    <span dir="ltr" class="font-black text-slate-950 dark:text-white">{{ formatCurrency(cartSubtotal) }}</span>
                                </div>

                                <div class="grid grid-cols-[1fr_128px] items-center gap-3">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-500 dark:text-slate-400">خصم الفاتورة</p>
                                        <p v-if="minimumAllowedCartTotal > 0" class="mt-0.5 text-[9px] text-slate-400">الحد الآمن: {{ formatCurrency(maximumSafeInvoiceDiscount) }}</p>
                                    </div>
                                    <input
                                        v-model.number="form.invoice_discount"
                                        type="number"
                                        min="0"
                                        :max="cartSubtotal"
                                        step="0.01"
                                        class="w-full rounded-xl border-slate-200 bg-white px-3 py-2.5 text-left text-sm font-black text-slate-900 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                        :class="minimumPriceInvoiceDiscountError ? 'border-rose-300 dark:border-rose-800' : ''"
                                    />
                                </div>

                                <p v-if="invoiceDiscountError" class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">
                                    {{ invoiceDiscountError }}
                                </p>

                                <p v-if="minimumPriceInvoiceDiscountError" class="rounded-xl bg-amber-50 px-3 py-2 text-[10px] font-bold leading-5 text-amber-700 dark:bg-amber-950/25 dark:text-amber-300">
                                    {{ minimumPriceInvoiceDiscountError }}
                                </p>
                            </div>

                            <div class="mt-4 overflow-hidden rounded-2xl bg-gradient-to-br from-slate-950 via-indigo-950 to-indigo-900 p-4 text-white shadow-lg shadow-indigo-950/10">
                                <div class="flex items-end justify-between gap-4">
                                    <div>
                                        <p class="text-[10px] font-bold text-indigo-300">الإجمالي المستحق</p>
                                        <p dir="ltr" class="mt-1 text-right text-2xl font-black tracking-tight">{{ formatCurrency(finalTotal) }}</p>
                                    </div>
                                    <div class="text-left">
                                        <p class="text-[9px] text-slate-400">عدد القطع</p>
                                        <p class="mt-1 text-lg font-black">{{ totalPieces }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 grid grid-cols-[1fr_auto] gap-2">
                                <button
                                    type="button"
                                    :disabled="!canOpenCheckout"
                                    class="group inline-flex items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3.5 text-sm font-black text-white shadow-lg shadow-emerald-600/20 transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0"
                                    @click="openCheckout"
                                >
                                    <span>متابعة إلى الدفع</span>
                                    <svg class="h-4 w-4 transition group-hover:-translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                    </svg>
                                </button>

                                <button
                                    type="button"
                                    :disabled="form.items.length === 0 || form.processing"
                                    class="inline-flex h-full w-12 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 disabled:opacity-40 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-300"
                                    title="حفظ كمسودة"
                                    @click="saveDraft"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.5 3.75h8.25L20.25 8.25v12H3.75v-16.5H7.5zm0 0v5.25h9V3.75M8.25 20.25v-6h7.5v6" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>

        <!-- شريط الهاتف -->
        <div
            v-if="form.items.length"
            class="fixed inset-x-3 bottom-3 z-30 rounded-2xl border border-white/10 bg-slate-950/95 p-2.5 text-white shadow-2xl backdrop-blur-xl xl:hidden"
        >
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="flex min-w-0 flex-1 items-center justify-between gap-3 rounded-xl px-2 py-1.5 text-right"
                    @click="scrollToCart"
                >
                    <span class="flex min-w-0 items-center gap-3">
                        <span class="flex h-9 min-w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 px-2 text-xs font-black">{{ totalPieces }}</span>
                        <span class="min-w-0">
                            <span class="block text-[10px] text-slate-400">سلة البيع</span>
                            <strong dir="ltr" class="block truncate text-sm">{{ formatCurrency(finalTotal) }}</strong>
                        </span>
                    </span>
                </button>

                <button
                    type="button"
                    :disabled="!canOpenCheckout"
                    class="rounded-xl bg-emerald-600 px-4 py-3 text-sm font-black transition hover:bg-emerald-700 disabled:opacity-50"
                    @click="openCheckout"
                >
                    الدفع
                </button>
            </div>
        </div>

        <!-- Checkout -->
        <Teleport to="body">
            <div
                v-if="checkoutOpen"
                class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-950/70 p-0 backdrop-blur-md sm:items-center sm:p-4"
                @mousedown.self="closeCheckout"
            >
                <section class="flex max-h-[97vh] w-full max-w-7xl flex-col overflow-hidden rounded-t-[30px] bg-slate-50 shadow-2xl dark:bg-slate-950 sm:max-h-[94vh] sm:rounded-[30px]">
                    <header class="relative overflow-hidden border-b border-white/10 bg-gradient-to-l from-slate-950 via-indigo-950 to-slate-900 px-5 py-5 text-white sm:px-7">
                        <div class="pointer-events-none absolute -left-20 -top-24 h-56 w-56 rounded-full bg-blue-500/10 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-24 right-1/3 h-48 w-48 rounded-full bg-indigo-400/10 blur-3xl"></div>

                        <div class="relative flex items-center justify-between gap-5">
                            <div class="flex min-w-0 items-center gap-4">
                                <div class="hidden h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-white/10 bg-white/10 text-emerald-300 sm:flex">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125V18m-1.5 0h.75a.75.75 0 00.75-.75v-.75m0 1.5h-.375a1.125 1.125 0 01-1.125-1.125V16.5m0 1.5H3.75m0 0H3m.75 0v-.75A.75.75 0 003 16.5h-.75m1.5 1.5v-.375c0-.621-.504-1.125-1.125-1.125H2.25m19.5 0v-9M15 11.25a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18v-.008zm-12 0h.008v.008H6v-.008z" />
                                    </svg>
                                </div>

                                <div class="min-w-0">
                                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-300">Checkout</p>
                                    <h2 class="mt-1 truncate text-xl font-black sm:text-2xl">إتمام عملية البيع</h2>
                                    <p class="mt-1 text-xs text-slate-400 sm:text-sm">حدد العميل وطريقة الدفع ثم راجع الملخص قبل الاعتماد.</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="hidden text-left sm:block">
                                    <p class="text-[10px] text-slate-400">المبلغ النهائي</p>
                                    <strong dir="ltr" class="mt-0.5 block text-lg">{{ formatCurrency(finalTotal) }}</strong>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-xl border border-white/10 bg-white/5 p-2.5 text-slate-300 transition hover:bg-white/10 hover:text-white"
                                    @click="closeCheckout"
                                >
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>
                    </header>

                    <div class="min-h-0 flex-1 overflow-y-auto p-4 sm:p-6">
                        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_390px]">
                            <main class="space-y-5">
                                <!-- العميل -->
                                <section class="overflow-visible rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a7.5 7.5 0 0115 0" /></svg>
                                            </div>
                                            <div>
                                                <h3 class="font-black text-slate-950 dark:text-white">العميل</h3>
                                                <p class="mt-0.5 text-xs text-slate-400">اختر عميلاً محفوظاً أو سجل الاسم مباشرة.</p>
                                            </div>
                                        </div>

                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-black text-slate-600 transition hover:border-indigo-200 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/30 dark:hover:text-indigo-300"
                                            @click="chooseCashCustomer"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 6v6h4.5" /></svg>
                                            عميل نقدي سريع
                                        </button>
                                    </div>

                                    <div class="relative mt-4">
                                        <svg class="pointer-events-none absolute right-3.5 top-1/2 h-5 w-5 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.35-4.35m1.35-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" /></svg>
                                        <input
                                            ref="customerSearchInput"
                                            v-model="customerSearch"
                                            type="search"
                                            autocomplete="off"
                                            placeholder="ابحث باسم العميل، الهاتف أو الكود..."
                                            class="block w-full rounded-2xl border-slate-200 bg-slate-50 py-3.5 pr-11 text-sm font-bold text-slate-900 focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white dark:focus:border-indigo-600"
                                            @focus="customerPickerOpen = true"
                                            @input="customerPickerOpen = true"
                                        />

                                        <div
                                            v-if="customerPickerOpen"
                                            class="pos-scroll absolute right-0 left-0 top-full z-30 mt-2 max-h-64 overflow-y-auto rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-800"
                                        >
                                            <button
                                                v-for="customer in filteredCustomers"
                                                :key="customer.id"
                                                type="button"
                                                class="flex w-full items-center justify-between gap-4 rounded-xl p-3 text-right transition hover:bg-indigo-50 dark:hover:bg-indigo-950/30"
                                                @click="chooseCustomer(customer)"
                                            >
                                                <div class="flex min-w-0 items-center gap-3">
                                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-black text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                                                        {{ String(customer.name || '?').slice(0, 1) }}
                                                    </div>
                                                    <div class="min-w-0">
                                                        <p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ customer.name }}</p>
                                                        <p dir="ltr" class="mt-0.5 truncate text-right text-[11px] text-slate-400">{{ customer.phone || customer.code || '—' }}</p>
                                                    </div>
                                                </div>
                                                <svg class="h-4 w-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5l7 7-7 7" /></svg>
                                            </button>

                                            <div v-if="filteredCustomers.length === 0" class="px-4 py-8 text-center text-sm text-slate-500">لا يوجد عميل مطابق.</div>
                                        </div>
                                    </div>

                                    <div v-if="selectedCustomer" class="mt-4 flex items-center justify-between gap-4 rounded-2xl border border-indigo-100 bg-indigo-50 p-3.5 dark:border-indigo-900/60 dark:bg-indigo-950/25">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-sm font-black text-white">{{ String(selectedCustomer.name || '?').slice(0, 1) }}</div>
                                            <div class="min-w-0">
                                                <p class="truncate text-sm font-black text-slate-900 dark:text-white">{{ selectedCustomer.name }}</p>
                                                <p dir="ltr" class="mt-0.5 truncate text-right text-[11px] text-slate-500 dark:text-slate-400">{{ selectedCustomer.phone || 'بدون رقم هاتف' }}</p>
                                            </div>
                                        </div>
                                        <button type="button" class="rounded-xl px-3 py-2 text-xs font-black text-indigo-700 transition hover:bg-indigo-100 dark:text-indigo-300 dark:hover:bg-indigo-900/40" @click="clearSelectedCustomer">تغيير</button>
                                    </div>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <label class="block">
                                            <span class="mb-1.5 block text-xs font-black text-slate-600 dark:text-slate-300">اسم العميل <span class="text-rose-500">*</span></span>
                                            <input v-model.trim="form.customer_name" type="text" maxlength="255" class="block w-full rounded-xl border-slate-200 bg-white px-3 py-3 text-sm font-bold focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white" placeholder="اسم العميل" />
                                        </label>

                                        <label class="block">
                                            <span class="mb-1.5 block text-xs font-black text-slate-600 dark:text-slate-300">رقم الهاتف</span>
                                            <input v-model.trim="form.customer_phone" dir="ltr" type="tel" maxlength="20" class="block w-full rounded-xl border-slate-200 bg-white px-3 py-3 text-right text-sm font-bold focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white" placeholder="05xxxxxxxx" />
                                        </label>
                                    </div>

                                    <label v-if="!form.customer_id && form.customer_name !== 'عميل نقدي'" class="mt-3 flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-900">
                                        <input v-model="form.save_customer" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                                        <span class="text-xs font-bold text-slate-600 dark:text-slate-300">حفظ هذا العميل في قائمة العملاء</span>
                                    </label>
                                </section>

                                <!-- الدفع -->
                                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300">
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18M3.75 4.5h16.5M3.75 9.75h16.5" /></svg>
                                            </div>
                                            <div>
                                                <h3 class="font-black text-slate-950 dark:text-white">طريقة الدفع</h3>
                                                <p class="mt-0.5 text-xs text-slate-400">استخدم اختياراً سريعاً أو قسّم المبلغ كما تريد.</p>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap">
                                            <button type="button" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] font-black text-slate-600 transition hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-700" @click="setNoPayment">آجل</button>
                                            <button type="button" class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[11px] font-black text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300" @click="setFullCashPayment">كاش كامل</button>
                                            <button type="button" class="rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-[11px] font-black text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/60 dark:bg-blue-950/30 dark:text-blue-300" @click="setFullBankPayment">بنكي كامل</button>
                                            <button type="button" class="rounded-xl bg-indigo-600 px-3 py-2 text-[11px] font-black text-white shadow-sm transition hover:bg-indigo-700" @click="setHalfCashHalfBankPayment">50% كاش + 50% بنكي</button>
                                        </div>
                                    </div>

                                    <div class="mt-4 space-y-3">
                                        <article
                                            v-for="(payment, index) in form.payments"
                                            :key="payment.key"
                                            class="rounded-2xl border border-slate-200 bg-slate-50/60 p-4 dark:border-slate-700 dark:bg-slate-900/50"
                                        >
                                            <div class="mb-3 flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-white text-[10px] font-black text-slate-500 shadow-sm dark:bg-slate-800 dark:text-slate-300">{{ index + 1 }}</span>
                                                    <span class="text-xs font-black text-slate-600 dark:text-slate-300">دفعة {{ methodLabel(payment.payment_method) }}</span>
                                                </div>
                                                <button type="button" class="rounded-lg p-1.5 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/30" title="حذف الدفعة" @click="removePayment(index)">
                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                                </button>
                                            </div>

                                            <div class="grid gap-3 md:grid-cols-3">
                                                <label class="block">
                                                    <span class="mb-1.5 block text-[10px] font-black text-slate-400">المبلغ</span>
                                                    <input v-model.number="payment.amount" type="number" min="0" :max="finalTotal" step="0.01" class="block w-full rounded-xl border-slate-200 bg-white px-3 py-3 text-sm font-black text-slate-950 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white" />
                                                </label>

                                                <label class="block">
                                                    <span class="mb-1.5 block text-[10px] font-black text-slate-400">الطريقة</span>
                                                    <select v-model="payment.payment_method" class="block w-full rounded-xl border-slate-200 bg-white px-3 py-3 text-sm font-bold focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                                        <option v-for="(label, value) in paymentMethods" :key="value" :value="value">{{ label }}</option>
                                                    </select>
                                                </label>

                                                <label class="block">
                                                    <span class="mb-1.5 block text-[10px] font-black text-slate-400">الحساب المالي</span>
                                                    <select v-model="payment.financial_account_id" class="block w-full rounded-xl border-slate-200 bg-white px-3 py-3 text-sm font-bold focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                                        <option value="">اختر الحساب</option>
                                                        <option v-for="account in accountsForMethod(payment.payment_method)" :key="account.id" :value="account.id">
                                                            {{ account.name }} — {{ formatCurrency(account.current_balance) }}
                                                        </option>
                                                    </select>
                                                </label>
                                            </div>

                                            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                                <div class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-[11px] text-slate-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                                    الحساب:
                                                    <strong class="truncate text-slate-900 dark:text-white">{{ paymentAccountLabel(payment) }}</strong>
                                                </div>
                                                <input
                                                    v-if="['bank_transfer', 'banking_app'].includes(payment.payment_method)"
                                                    v-model.trim="payment.transaction_reference"
                                                    dir="ltr"
                                                    type="text"
                                                    class="block w-full rounded-xl border-slate-200 bg-white px-3 py-2.5 text-right text-xs font-bold focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
                                                    placeholder="مرجع العملية (اختياري)"
                                                />
                                            </div>
                                        </article>

                                        <button
                                            type="button"
                                            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-dashed border-indigo-300 bg-indigo-50/40 px-4 py-3 text-xs font-black text-indigo-700 transition hover:bg-indigo-50 dark:border-indigo-800 dark:bg-indigo-950/10 dark:text-indigo-300 dark:hover:bg-indigo-950/30"
                                            @click="addPayment"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4v16m8-8H4" /></svg>
                                            إضافة دفعة أخرى
                                        </button>
                                    </div>

                                    <p v-if="paymentError" class="mt-3 rounded-xl bg-rose-50 p-3 text-xs font-bold leading-5 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300">{{ paymentError }}</p>

                                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                        <label class="block">
                                            <span class="mb-1.5 block text-xs font-black text-slate-600 dark:text-slate-300">تاريخ البيع</span>
                                            <input v-model="form.sale_date" type="date" class="block w-full rounded-xl border-slate-200 bg-white px-3 py-3 text-sm font-bold focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                                        </label>

                                        <label v-if="remaining > 0" class="block">
                                            <span class="mb-1.5 block text-xs font-black text-slate-600 dark:text-slate-300">استحقاق المتبقي</span>
                                            <input v-model="form.due_date" type="date" :min="form.sale_date" class="block w-full rounded-xl border-slate-200 bg-white px-3 py-3 text-sm font-bold focus:border-indigo-400 focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white" />
                                            <span v-if="dueDateError" class="mt-1.5 block text-xs font-bold text-rose-600">{{ dueDateError }}</span>
                                        </label>
                                    </div>
                                </section>

                                <!-- الملاحظات -->
                                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-300">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H8.25m0 12.75h4.5m-4.5 3h4.5M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.625A9.375 9.375 0 0010.5 2.25z" /></svg>
                                        </div>
                                        <div>
                                            <h3 class="font-black text-slate-950 dark:text-white">ملاحظات الفاتورة</h3>
                                            <p class="mt-0.5 text-xs text-slate-400">اختياري — تظهر مع بيانات الفاتورة.</p>
                                        </div>
                                    </div>
                                    <textarea v-model.trim="form.notes" rows="3" maxlength="500" class="mt-4 block w-full resize-none rounded-2xl border-slate-200 bg-slate-50 px-3 py-3 text-sm focus:border-indigo-400 focus:bg-white focus:ring-4 focus:ring-indigo-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white" placeholder="أضف أي ملاحظة مهمة حول هذه العملية..." />
                                    <span class="mt-1.5 block text-left text-[10px] text-slate-400">{{ form.notes.length }}/500</span>
                                </section>
                            </main>

                            <!-- الملخص -->
                            <aside class="space-y-4 xl:sticky xl:top-0">
                                <section class="overflow-hidden rounded-3xl bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-white shadow-2xl shadow-slate-950/20">
                                    <div class="border-b border-white/10 p-5">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-indigo-300">Order summary</p>
                                                <h3 class="mt-1 text-lg font-black">ملخص البيع</h3>
                                            </div>
                                            <span class="rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-xs font-black text-slate-300">{{ itemCount }} صنف</span>
                                        </div>

                                        <p dir="ltr" class="mt-5 text-right text-3xl font-black tracking-tight sm:text-4xl">{{ formatCurrency(finalTotal) }}</p>
                                        <p class="mt-1 text-xs text-slate-400">الإجمالي النهائي بعد الخصومات</p>
                                    </div>

                                    <div class="space-y-3 p-5 text-sm">
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-slate-400">إجمالي المنتجات</span>
                                            <span dir="ltr" class="font-black">{{ formatCurrency(cartSubtotal) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-slate-400">الخصم</span>
                                            <span dir="ltr" class="font-black text-rose-300">-{{ formatCurrency(invoiceDiscount) }}</span>
                                        </div>
                                        <div class="my-1 h-px bg-white/10"></div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-slate-400">المدفوع</span>
                                            <span dir="ltr" class="font-black text-emerald-300">{{ formatCurrency(totalPaid) }}</span>
                                        </div>
                                        <div class="flex items-center justify-between gap-3">
                                            <span class="text-slate-400">المتبقي</span>
                                            <span dir="ltr" class="font-black" :class="remaining > 0 ? 'text-amber-300' : 'text-emerald-300'">{{ formatCurrency(remaining) }}</span>
                                        </div>

                                        <div class="mt-4 rounded-2xl border border-white/10 bg-white/5 p-3.5">
                                            <div class="flex items-center justify-between gap-3">
                                                <span class="text-[10px] text-slate-400">توزيع الدفع</span>
                                                <span class="rounded-full px-2.5 py-1 text-[10px] font-black" :class="paymentComposition.classes">{{ paymentComposition.label }}</span>
                                            </div>
                                            <p class="mt-2 text-xs leading-5 text-slate-300">{{ paymentComposition.details }}</p>

                                            <div class="mt-3 overflow-hidden rounded-full bg-white/10">
                                                <div class="h-1.5 rounded-full bg-emerald-400 transition-all duration-300" :style="{ width: `${paymentProgress}%` }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <div v-if="minimumPriceError" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                                    <div class="flex items-start gap-3">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-sm font-black text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">!</div>
                                        <div>
                                            <p class="text-xs font-black">يتطلب موافقة سعر</p>
                                            <p class="mt-1 text-[11px] leading-5">{{ minimumPriceError }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="pageError" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs font-bold leading-5 text-rose-700 dark:border-rose-900 dark:bg-rose-950/30 dark:text-rose-300">{{ pageError }}</div>

                                <button
                                    type="button"
                                    :disabled="!canCompleteSale"
                                    class="group inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-6 py-4 text-base font-black text-white shadow-xl shadow-emerald-600/20 transition hover:-translate-y-0.5 hover:bg-emerald-700 hover:shadow-2xl disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:translate-y-0"
                                    @click="completeSale"
                                >
                                    <svg v-if="form.processing" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.37 0 0 5.37 0 12h4z" /></svg>
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M5 13l4 4L19 7" /></svg>
                                    {{ form.processing ? 'جارٍ إتمام البيع...' : requiresMinimumPriceApproval ? 'طلب الموافقة وإتمام البيع' : 'اعتماد وإتمام البيع' }}
                                </button>

                                <button
                                    type="button"
                                    :disabled="form.processing"
                                    class="inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-slate-200 bg-white px-6 py-3.5 text-sm font-black text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                    @click="saveDraft"
                                >
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7.5 3.75h8.25L20.25 8.25v12H3.75v-16.5H7.5zm0 0v5.25h9V3.75" /></svg>
                                    حفظ كمسودة
                                </button>

                                <p class="text-center text-[10px] leading-5 text-slate-400">Ctrl + Enter لاعتماد البيع بسرعة</p>
                            </aside>
                        </div>
                    </div>
                </section>
            </div>
        </Teleport>

        <!-- موافقة الحد الأدنى -->
        <Teleport to="body">
            <div
                v-if="minimumPriceApprovalOpen"
                class="fixed inset-0 z-[120] flex items-center justify-center bg-slate-950/75 p-4 backdrop-blur-md"
                @mousedown.self="closeMinimumPriceApproval"
            >
                <section class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl dark:bg-slate-900">
                    <header class="relative overflow-hidden bg-gradient-to-l from-amber-500 via-amber-500 to-orange-500 p-5 text-slate-950 sm:p-6">
                        <div class="pointer-events-none absolute -left-12 -top-16 h-36 w-36 rounded-full bg-white/20 blur-3xl"></div>
                        <div class="relative flex items-start justify-between gap-4">
                            <div class="flex items-start gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white/30 text-slate-950 backdrop-blur">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.9" d="M12 9v3.75m9-1.5a9 9 0 11-18 0 9 9 0 0118 0zM12 16.5h.008v.008H12V16.5z" /></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-[0.18em] opacity-70">Price approval</p>
                                    <h2 class="mt-1 text-xl font-black">موافقة البيع تحت الحد الأدنى</h2>
                                    <p class="mt-1 text-xs font-semibold leading-5 opacity-80">السعر الحالي يتطلب موافقة صريحة مع تسجيل السبب.</p>
                                </div>
                            </div>
                            <button type="button" class="rounded-xl bg-black/10 p-2 transition hover:bg-black/15" @click="closeMinimumPriceApproval"><svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>
                        </div>
                    </header>

                    <div class="pos-scroll min-h-0 flex-1 space-y-4 overflow-y-auto p-5 sm:p-6">
                        <div v-if="minimumPriceApprovalRows.length" class="space-y-2">
                            <article
                                v-for="row in minimumPriceApprovalRows"
                                :key="`${row.product_name}-${row.minimum_price}`"
                                class="rounded-2xl border border-rose-100 bg-rose-50/70 p-4 dark:border-rose-900/50 dark:bg-rose-950/20"
                            >
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm font-black text-slate-950 dark:text-white">{{ row.product_name }}</p>
                                        <p class="mt-1 text-[10px] text-slate-500 dark:text-slate-400">الكمية: {{ row.quantity }}</p>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2 text-center">
                                        <div class="rounded-xl bg-white px-3 py-2 dark:bg-slate-800"><p class="text-[9px] text-slate-400">أقل سعر</p><strong dir="ltr" class="mt-1 block text-[11px] text-amber-700 dark:text-amber-300">{{ formatCurrency(row.minimum_price) }}</strong></div>
                                        <div class="rounded-xl bg-white px-3 py-2 dark:bg-slate-800"><p class="text-[9px] text-slate-400">السعر الفعلي</p><strong dir="ltr" class="mt-1 block text-[11px] text-rose-600">{{ formatCurrency(row.net_unit_price) }}</strong></div>
                                        <div class="rounded-xl bg-white px-3 py-2 dark:bg-slate-800"><p class="text-[9px] text-slate-400">الفرق</p><strong dir="ltr" class="mt-1 block text-[11px] text-rose-600">{{ formatCurrency(row.difference) }}</strong></div>
                                    </div>
                                </div>
                            </article>
                        </div>

                        <div v-if="minimumPriceInvoiceDiscountError && !minimumPriceApprovalRows.length" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200">{{ minimumPriceInvoiceDiscountError }}</div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800">
                            <label class="block text-sm font-black text-slate-900 dark:text-white">سبب الموافقة <span class="text-rose-500">*</span></label>
                            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">مثال: عميل دائم، تصفية منتج، عرض خاص، أو عيب بسيط في التغليف.</p>
                            <textarea
                                v-model.trim="minimumPriceApprovalReason"
                                rows="3"
                                maxlength="500"
                                autofocus
                                placeholder="اكتب سبب البيع تحت الحد الأدنى..."
                                class="mt-3 w-full resize-none rounded-xl border-slate-200 bg-white px-3 py-3 text-sm focus:border-amber-400 focus:ring-4 focus:ring-amber-500/10 dark:border-slate-700 dark:bg-slate-900 dark:text-white"
                                :class="minimumPriceApprovalError ? 'border-rose-400 dark:border-rose-800' : ''"
                            ></textarea>
                            <div class="mt-2 flex items-center justify-between gap-3">
                                <p v-if="minimumPriceApprovalError" class="text-xs font-bold text-rose-600">{{ minimumPriceApprovalError }}</p>
                                <span dir="ltr" class="mr-auto text-[10px] text-slate-400">{{ minimumPriceApprovalReason.length }}/500</span>
                            </div>
                        </div>
                    </div>

                    <footer class="grid gap-2 border-t border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900 sm:grid-cols-2">
                        <button type="button" :disabled="form.processing" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50 disabled:opacity-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800" @click="closeMinimumPriceApproval">العودة وتعديل السعر</button>
                        <button type="button" :disabled="form.processing || minimumPriceApprovalReason.trim().length < 3" class="rounded-xl bg-amber-500 px-4 py-3 text-sm font-black text-slate-950 shadow-lg shadow-amber-500/20 transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50" @click="confirmMinimumPriceApproval">{{ form.processing ? 'جاري اعتماد البيع...' : 'أوافق وأكمل البيع' }}</button>
                    </footer>
                </section>
            </div>
        </Teleport>

        <!-- تفريغ السلة -->
        <Teleport to="body">
            <div v-if="showClearCartConfirm" class="fixed inset-0 z-[110] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-md" @mousedown.self="showClearCartConfirm = false">
                <section class="w-full max-w-md overflow-hidden rounded-[26px] bg-white shadow-2xl dark:bg-slate-800">
                    <div class="p-6">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 7h12m-10 0V5.5A1.5 1.5 0 019.5 4h5A1.5 1.5 0 0116 5.5V7m-8 0 .7 12a1.5 1.5 0 001.5 1.4h3.6a1.5 1.5 0 001.5-1.4L16 7" /></svg>
                        </div>
                        <h3 class="mt-4 text-xl font-black text-slate-950 dark:text-white">تفريغ سلة البيع؟</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-500 dark:text-slate-400">سيتم حذف جميع المنتجات والخصومات والدفعات الحالية من شاشة البيع.</p>
                    </div>
                    <div class="grid grid-cols-2 gap-2 border-t border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/50">
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-black text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" @click="showClearCartConfirm = false">إلغاء</button>
                        <button type="button" class="rounded-xl bg-rose-600 px-4 py-3 text-sm font-black text-white transition hover:bg-rose-700" @click="clearCart">تفريغ السلة</button>
                    </div>
                </section>
            </div>
        </Teleport>
    </AuthenticatedLayout>
</template>

<style scoped>
.pos-scroll {
    scrollbar-width: thin;
    scrollbar-color: rgb(203 213 225) transparent;
}

.pos-scroll::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}

.pos-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.pos-scroll::-webkit-scrollbar-thumb {
    border-radius: 999px;
    background: rgb(203 213 225);
}

:global(.dark) .pos-scroll {
    scrollbar-color: rgb(71 85 105) transparent;
}

:global(.dark) .pos-scroll::-webkit-scrollbar-thumb {
    background: rgb(71 85 105);
}

.scrollbar-hide {
    scrollbar-width: none;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
</style>
