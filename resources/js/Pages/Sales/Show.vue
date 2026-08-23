<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        تفاصيل فاتورة البيع
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ invoice.invoice_number }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('sales.index')"
                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        القائمة
                    </Link>
                    <Link
                        :href="route('sales.print', invoice.id)"
                        target="_blank"
                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        طباعة
                    </Link>
                    <Link
                        v-if="invoice.can_be_edited"
                        :href="route('sales.edit', invoice.id)"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        تعديل
                    </Link>
                    <button
                        v-if="invoice.can_be_approved"
                        @click="confirmApprove"
                        class="flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-green-700 hover:shadow-md"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        اعتماد
                    </button>
                    <button
                        v-if="invoice.can_add_payment"
                        @click="openPaymentModal"
                        class="flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-blue-700 hover:shadow-md"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        دفعة
                    </button>
                    <button
                        v-if="invoice.can_be_cancelled"
                        @click="openCancelModal"
                        class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-all hover:bg-red-700 hover:shadow-md"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        إلغاء
                    </button>
                </div>
            </div>
        </template>

        <!-- حالة الفاتورة -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-4 p-6">
                <div class="flex items-center gap-3">
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium"
                        :class="{
                            'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': invoice.status === 'draft',
                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': invoice.status === 'approved',
                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': invoice.status === 'cancelled'
                        }"
                    >
                        {{ statuses[invoice.status] || invoice.status }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium"
                        :class="{
                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': invoice.payment_status === 'unpaid',
                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': invoice.payment_status === 'partially_paid',
                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': invoice.payment_status === 'paid'
                        }"
                    >
                        {{ paymentStatuses[invoice.payment_status] || invoice.payment_status }}
                    </span>
                </div>
                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                    <p>تاريخ الإنشاء: {{ formatDate(invoice.created_at) }}</p>
                    <p v-if="invoice.approved_at">تاريخ الاعتماد: {{ formatDate(invoice.approved_at) }}</p>
                    <p v-if="invoice.cancelled_at">تاريخ الإلغاء: {{ formatDate(invoice.cancelled_at) }}</p>
                </div>
            </div>
        </div>

        <!-- ملخص الفاتورة -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- معلومات العميل -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">معلومات العميل</h3>
                    </div>
                    <div class="p-6">
                        <p class="font-medium text-gray-900 dark:text-white">{{ invoice.customer_name }}</p>
                        <p v-if="invoice.customer_phone" class="text-sm text-gray-600 dark:text-gray-400">
                            {{ invoice.customer_phone }}
                        </p>
                        <p v-if="invoice.customer" class="text-sm text-gray-500 dark:text-gray-400">
                            كود العميل: {{ invoice.customer.code }}
                        </p>
                        <div v-if="invoice.due_date && Number(invoice.remaining_amount) > 0" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900/50 dark:bg-amber-950/30">
                            <p class="font-semibold text-amber-800 dark:text-amber-300">استحقاق المبلغ المتبقي</p>
                            <p class="mt-1 text-amber-700 dark:text-amber-400">{{ formatDate(invoice.due_date) }}</p>
                        </div>
                    </div>
                </div>

                <!-- عناصر الفاتورة -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">عناصر الفاتورة</h3>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">المنتج</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الكمية</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">سعر الوحدة</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الخصم</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="item in invoice.items" :key="item.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ item.product_name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ item.product_code }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ item.quantity }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatCurrency(item.unit_selling_price) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                        {{ formatCurrency(item.line_discount || 0) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ formatCurrency(item.line_total) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- الملاحظات -->
                <div v-if="invoice.notes" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">ملاحظات</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ invoice.notes }}</p>
                    </div>
                </div>

                <!-- سبب الإلغاء -->
                <div v-if="invoice.cancellation_reason" class="overflow-hidden rounded-2xl border border-red-200 bg-red-50 dark:border-red-900/30 dark:bg-red-900/20">
                    <div class="border-b border-red-200 px-6 py-4 dark:border-red-900/30">
                        <h3 class="font-semibold text-red-800 dark:text-red-300">سبب الإلغاء</h3>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-red-700 dark:text-red-400">{{ invoice.cancellation_reason }}</p>
                    </div>
                </div>
            </div>

            <!-- الشريط الجانبي -->
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-4">
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white">ملخص الفاتورة</h4>
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">الإجمالي</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(invoice.subtotal) }}</span>
                            </div>
                            <div v-if="invoice.items_discount > 0" class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">خصم المنتجات</span>
                                <span class="font-medium text-red-600 dark:text-red-400">-{{ formatCurrency(invoice.items_discount) }}</span>
                            </div>
                            <div v-if="invoice.invoice_discount > 0" class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">خصم الفاتورة</span>
                                <span class="font-medium text-red-600 dark:text-red-400">-{{ formatCurrency(invoice.invoice_discount) }}</span>
                            </div>
                            <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                                <div class="flex justify-between text-lg font-bold">
                                    <span class="text-gray-900 dark:text-white">الإجمالي النهائي</span>
                                    <span class="text-blue-600 dark:text-blue-400">{{ formatCurrency(invoice.total_amount) }}</span>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                                <div class="flex justify-between">
                                    <span class="text-green-600 dark:text-green-400">المدفوع</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">{{ formatCurrency(invoice.paid_amount) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-red-600 dark:text-red-400">المتبقي</span>
                                    <span class="font-medium text-red-600 dark:text-red-400">{{ formatCurrency(invoice.remaining_amount) }}</span>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">إجمالي الربح</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">{{ formatCurrency(invoice.gross_profit) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الدفعات -->
                    <div v-if="invoice.payments && invoice.payments.length > 0" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white">سجل الدفعات</h4>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div v-for="payment in invoice.payments" :key="payment.id" class="p-4">
                                <div class="flex justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(payment.amount) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ paymentMethods[payment.payment_method] || payment.payment_method }}
                                        </p>
                                        <p
                                            v-if="payment.financial_account?.name || payment.financialAccount?.name"
                                            class="mt-0.5 text-[10px] font-bold text-indigo-600 dark:text-indigo-300"
                                        >
                                            {{ payment.financial_account?.name || payment.financialAccount?.name }}
                                        </p>
                                    </div>
                                    <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                                        <p>{{ formatDate(payment.paid_at) }}</p>
                                        <p v-if="payment.transaction_reference" class="text-gray-400">{{ payment.transaction_reference }}</p>
                                    </div>
                                </div>
                                <p v-if="payment.notes" class="mt-1 text-xs text-gray-400">{{ payment.notes }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <!-- Modal اعتماد -->
        <Modal
            :show="approveModal.show"
            @close="closeApproveModal"
        >
            <template #title>
                {{
                    minimumPriceApproval.requires_approval
                        ? 'موافقة واعتماد فاتورة البيع'
                        : 'اعتماد فاتورة البيع'
                }}
            </template>

            <template #content>
                <div class="space-y-4">
                    <div
                        v-if="minimumPriceApproval.requires_approval"
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-950/30"
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 font-black text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                            >
                                !
                            </div>

                            <div>
                                <h3
                                    class="text-sm font-black text-amber-900 dark:text-amber-200"
                                >
                                    الفاتورة تحتوي على بيع تحت أقل سعر
                                </h3>

                                <p
                                    class="mt-1 text-xs leading-6 text-amber-700 dark:text-amber-300"
                                >
                                    يمكن اعتماد الفاتورة، لكن يجب تسجيل موافقة صاحب المحل وسبب الموافقة أولاً.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="minimumPriceApproval.violations?.length"
                        class="space-y-2"
                    >
                        <article
                            v-for="item in minimumPriceApproval.violations"
                            :key="item.product_id"
                            class="rounded-2xl border border-rose-200 bg-rose-50 p-3 dark:border-rose-900/50 dark:bg-rose-950/20"
                        >
                            <div
                                class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                            >
                                <div>
                                    <strong
                                        class="block text-sm text-slate-950 dark:text-white"
                                    >
                                        {{ item.product_name }}
                                    </strong>

                                    <p
                                        class="mt-1 text-[10px] text-slate-500 dark:text-slate-400"
                                    >
                                        الكمية: {{ item.quantity }}
                                    </p>
                                </div>

                                <div class="grid grid-cols-3 gap-2 text-center">
                                    <div
                                        class="rounded-xl bg-white px-2.5 py-2 dark:bg-slate-800"
                                    >
                                        <p class="text-[9px] text-slate-400">
                                            أقل سعر
                                        </p>
                                        <strong
                                            dir="ltr"
                                            class="mt-1 block text-xs text-amber-700 dark:text-amber-300"
                                        >
                                            {{ formatCurrency(item.minimum_price) }}
                                        </strong>
                                    </div>

                                    <div
                                        class="rounded-xl bg-white px-2.5 py-2 dark:bg-slate-800"
                                    >
                                        <p class="text-[9px] text-slate-400">
                                            السعر الفعلي
                                        </p>
                                        <strong
                                            dir="ltr"
                                            class="mt-1 block text-xs text-rose-600"
                                        >
                                            {{ formatCurrency(item.actual_price) }}
                                        </strong>
                                    </div>

                                    <div
                                        class="rounded-xl bg-white px-2.5 py-2 dark:bg-slate-800"
                                    >
                                        <p class="text-[9px] text-slate-400">
                                            أقل بـ
                                        </p>
                                        <strong
                                            dir="ltr"
                                            class="mt-1 block text-xs text-rose-600"
                                        >
                                            {{ formatCurrency(item.difference) }}
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div
                        v-if="minimumPriceApproval.invoice_below_minimum"
                        class="rounded-2xl border border-amber-200 bg-amber-50 p-3 text-xs leading-6 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/20 dark:text-amber-200"
                    >
                        خصم الفاتورة يجعل إجمالي البيع أقل من مجموع الحدود الدنيا المحددة للمنتجات.
                    </div>

                    <label
                        v-if="minimumPriceApproval.requires_approval"
                        class="block rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800"
                    >
                        <span
                            class="text-sm font-black text-slate-900 dark:text-white"
                        >
                            سبب موافقة صاحب المحل
                            <span class="text-rose-500">*</span>
                        </span>

                        <textarea
                            v-model.trim="approveForm.minimum_price_reason"
                            rows="3"
                            maxlength="200"
                            placeholder="مثال: عميل دائم، عرض خاص، تصفية منتج..."
                            class="mt-2 w-full resize-none rounded-xl border-slate-300 bg-white text-sm dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                            :class="{
                                'border-rose-500':
                                    approveForm.errors.minimum_price_reason,
                            }"
                        ></textarea>

                        <p
                            v-if="approveForm.errors.minimum_price_reason"
                            class="mt-1 text-xs font-bold text-rose-600"
                        >
                            {{ approveForm.errors.minimum_price_reason }}
                        </p>
                    </label>

                    <div
                        v-else
                        class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800"
                    >
                        <p
                            class="text-sm text-slate-600 dark:text-slate-300"
                        >
                            هل أنت متأكد من اعتماد فاتورة البيع
                            <span
                                class="font-black text-slate-950 dark:text-white"
                            >
                                {{ invoice.invoice_number }}
                            </span>؟
                        </p>

                        <p
                            class="mt-2 text-xs font-bold text-amber-600 dark:text-amber-300"
                        >
                            سيتم خصم الكميات من المخزون بعد الاعتماد.
                        </p>
                    </div>

                    <div
                        v-if="approveForm.errors.minimum_price_approved"
                        class="rounded-xl bg-rose-50 p-3 text-xs font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                    >
                        {{ approveForm.errors.minimum_price_approved }}
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            :disabled="approveForm.processing"
                            class="rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50 dark:text-slate-300 dark:hover:bg-slate-800"
                            @click="closeApproveModal"
                        >
                            إلغاء
                        </button>

                        <button
                            type="button"
                            :disabled="
                                approveForm.processing
                                || (
                                    minimumPriceApproval.requires_approval
                                    && approveForm.minimum_price_reason.trim().length < 3
                                )
                            "
                            class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-black text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                            @click="submitApprove"
                        >
                            {{
                                approveForm.processing
                                    ? 'جاري الاعتماد...'
                                    : minimumPriceApproval.requires_approval
                                        ? 'أوافق وأعتمد الفاتورة'
                                        : 'تأكيد الاعتماد'
                            }}
                        </button>
                    </div>
                </div>
            </template>
        </Modal>

        <!-- Modal دفعة -->
        <Modal
            :show="paymentModal.show"
            @close="closePaymentModal"
        >
            <template #title>
                تسجيل دفعة على فاتورة البيع
            </template>

            <template #content>
                <div
                    class="sales-payment-scroll max-h-[calc(100dvh-8.5rem)] overflow-y-auto overscroll-contain px-1 pb-1"
                >
                    <form
                        class="space-y-4 pr-1"
                        @submit.prevent="submitPayment"
                    >
                        <!-- ملخص الفاتورة -->
                        <section
                            class="overflow-hidden rounded-[24px] border border-slate-200 bg-white dark:border-slate-700 dark:bg-slate-800"
                        >
                            <div
                                class="bg-gradient-to-l from-slate-950 via-indigo-950 to-indigo-600 p-4 text-white"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <span
                                            class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-black text-indigo-100"
                                        >
                                            SALES PAYMENT
                                        </span>

                                        <h3 class="mt-3 truncate text-lg font-black">
                                            {{ invoice.customer_name || invoice.customer?.name || 'عميل نقدي' }}
                                        </h3>

                                        <p
                                            dir="ltr"
                                            class="mt-1 text-right text-xs text-indigo-100"
                                        >
                                            {{ invoice.invoice_number }}
                                        </p>
                                    </div>

                                    <div
                                        class="shrink-0 rounded-2xl bg-white/10 px-3 py-2 text-left"
                                    >
                                        <p class="text-[10px] text-indigo-100">
                                            المتبقي
                                        </p>

                                        <strong
                                            dir="ltr"
                                            class="mt-1 block text-lg font-black"
                                        >
                                            {{ formatCurrency(currentRemaining) }}
                                        </strong>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div
                                        class="mb-2 flex items-center justify-between text-[10px] text-indigo-100"
                                    >
                                        <span>نسبة السداد الحالية</span>
                                        <span dir="ltr">
                                            {{ formatPercent(currentPaymentProgress) }}
                                        </span>
                                    </div>

                                    <div
                                        class="h-2 overflow-hidden rounded-full bg-white/15"
                                    >
                                        <div
                                            class="h-full rounded-full bg-emerald-400 transition-all duration-300"
                                            :style="{ width: `${currentPaymentProgress}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="grid grid-cols-3 divide-x divide-x-reverse divide-slate-100 dark:divide-slate-700"
                            >
                                <div class="p-3 text-center">
                                    <p class="text-[10px] text-slate-400">
                                        الإجمالي
                                    </p>
                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-sm text-slate-950 dark:text-white"
                                    >
                                        {{ formatCurrency(currentTotal) }}
                                    </strong>
                                </div>

                                <div class="p-3 text-center">
                                    <p class="text-[10px] text-slate-400">
                                        المدفوع
                                    </p>
                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-sm text-emerald-600"
                                    >
                                        {{ formatCurrency(currentPaid) }}
                                    </strong>
                                </div>

                                <div class="p-3 text-center">
                                    <p class="text-[10px] text-slate-400">
                                        المتبقي
                                    </p>
                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-sm text-rose-600"
                                    >
                                        {{ formatCurrency(currentRemaining) }}
                                    </strong>
                                </div>
                            </div>
                        </section>

                        <!-- قيمة الدفعة -->
                        <section>
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <label
                                        class="block text-sm font-black text-slate-800 dark:text-slate-100"
                                    >
                                        قيمة الدفعة
                                    </label>
                                    <p
                                        class="mt-1 text-xs text-slate-500 dark:text-slate-400"
                                    >
                                        أدخل المبلغ أو اختر قيمة سريعة من المتبقي.
                                    </p>
                                </div>

                                <span
                                    dir="ltr"
                                    class="text-[10px] font-black text-slate-400"
                                >
                                    Max: {{ formatCurrency(currentRemaining) }}
                                </span>
                            </div>

                            <div class="relative mt-3">
                                <input
                                    v-model.number="paymentForm.amount"
                                    type="number"
                                    min="0.01"
                                    :max="currentRemaining"
                                    step="0.01"
                                    class="block w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-4 pl-20 text-xl font-black text-slate-950 transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
                                    required
                                />

                                <span
                                    class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 rounded-lg bg-slate-200 px-2 py-1 text-xs font-black text-slate-600 dark:bg-slate-700 dark:text-slate-200"
                                >
                                    شيكل
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                    @click="setQuickPayment('quarter')"
                                >
                                    25%
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                    @click="setQuickPayment('half')"
                                >
                                    50%
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl bg-indigo-50 px-3 py-2.5 text-xs font-black text-indigo-700 transition hover:bg-indigo-100 dark:bg-indigo-950/30 dark:text-indigo-300"
                                    @click="setQuickPayment('full')"
                                >
                                    دفع المتبقي
                                </button>
                            </div>
                        </section>

                        <!-- المعاينة -->
                        <section
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/60"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h4 class="text-sm font-black text-slate-950 dark:text-white">
                                        بعد تسجيل الدفعة
                                    </h4>

                                    <p class="mt-1 text-[10px] text-slate-400">
                                        معاينة قبل الحفظ
                                    </p>
                                </div>

                                <span
                                    class="rounded-full px-2.5 py-1 text-[10px] font-black"
                                    :class="paymentStatusAfter.class"
                                >
                                    {{ paymentStatusAfter.label }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-xl bg-white p-3 dark:bg-slate-800">
                                    <p class="text-[10px] text-slate-400">
                                        المدفوع الجديد
                                    </p>

                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-right text-base text-emerald-600"
                                    >
                                        {{ formatCurrency(paidAfterPayment) }}
                                    </strong>
                                </div>

                                <div class="rounded-xl bg-white p-3 dark:bg-slate-800">
                                    <p class="text-[10px] text-slate-400">
                                        المتبقي الجديد
                                    </p>

                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-right text-base text-amber-600"
                                    >
                                        {{ formatCurrency(remainingAfterPayment) }}
                                    </strong>
                                </div>
                            </div>
                        </section>

                        <!-- طريقة الدفع -->
                        <section>
                            <label
                                class="block text-sm font-black text-slate-800 dark:text-slate-100"
                            >
                                طريقة الدفع
                            </label>

                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                <button
                                    v-for="(label, value) in paymentMethods"
                                    :key="value"
                                    type="button"
                                    class="rounded-2xl border p-3 text-right transition"
                                    :class="
                                        paymentForm.payment_method === value
                                            ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/10 dark:border-indigo-500 dark:bg-indigo-950/30'
                                            : 'border-slate-200 bg-white hover:border-indigo-200 dark:border-slate-700 dark:bg-slate-800'
                                    "
                                    @click="selectPaymentMethod(value)"
                                >
                                    <strong
                                        class="block text-xs text-slate-900 dark:text-white"
                                    >
                                        {{ label }}
                                    </strong>

                                    <span
                                        class="mt-1 block text-[10px] text-slate-400"
                                    >
                                        {{
                                            value === 'cash'
                                                ? 'تحصيل نقدي'
                                                : value === 'bank_transfer'
                                                    ? 'تحويل بنكي'
                                                    : 'تطبيق بنكي'
                                        }}
                                    </span>
                                </button>
                            </div>
                        </section>

                        <!-- الحساب المالي -->
                        <section>
                            <div class="flex items-end justify-between gap-3">
                                <div>
                                    <label
                                        class="block text-sm font-black text-slate-800 dark:text-slate-100"
                                    >
                                        الحساب المالي
                                    </label>

                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        سيتم إضافة مبلغ الدفعة إلى رصيد الحساب المختار.
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="compatibleAccounts.length"
                                class="mt-3 grid gap-2"
                            >
                                <button
                                    v-for="account in compatibleAccounts"
                                    :key="account.id"
                                    type="button"
                                    class="flex items-center justify-between gap-4 rounded-2xl border p-3 text-right transition"
                                    :class="
                                        Number(paymentForm.financial_account_id) === Number(account.id)
                                            ? 'border-indigo-500 bg-indigo-50 ring-2 ring-indigo-500/10 dark:border-indigo-500 dark:bg-indigo-950/30'
                                            : 'border-slate-200 bg-white hover:border-indigo-300 dark:border-slate-700 dark:bg-slate-800'
                                    "
                                    @click="paymentForm.financial_account_id = account.id"
                                >
                                    <div>
                                        <strong
                                            class="block text-sm text-slate-900 dark:text-white"
                                        >
                                            {{ account.name }}
                                        </strong>

                                        <span
                                            class="mt-1 block text-[10px] text-slate-400"
                                        >
                                            {{ account.type_label || accountTypeLabel(account.type) }}
                                        </span>
                                    </div>

                                    <div class="text-left">
                                        <p class="text-[9px] text-slate-400">
                                            الرصيد الحالي
                                        </p>

                                        <strong
                                            dir="ltr"
                                            class="mt-1 block text-xs text-slate-700 dark:text-slate-200"
                                        >
                                            {{ formatCurrency(account.current_balance) }}
                                        </strong>
                                    </div>
                                </button>
                            </div>

                            <div
                                v-else
                                class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
                            >
                                لا يوجد حساب مالي نشط متوافق مع طريقة الدفع المختارة.
                            </div>

                            <div
                                v-if="selectedAccount"
                                class="mt-3 grid grid-cols-2 gap-3"
                            >
                                <div class="rounded-xl bg-slate-50 p-3 dark:bg-slate-900">
                                    <p class="text-[10px] text-slate-400">
                                        رصيد الحساب قبل الدفعة
                                    </p>

                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-right text-sm"
                                    >
                                        {{ formatCurrency(selectedAccount.current_balance) }}
                                    </strong>
                                </div>

                                <div class="rounded-xl bg-emerald-50 p-3 dark:bg-emerald-950/20">
                                    <p class="text-[10px] text-emerald-600">
                                        الرصيد بعد التحصيل
                                    </p>

                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-right text-sm text-emerald-700 dark:text-emerald-300"
                                    >
                                        {{ formatCurrency(accountBalanceAfterPayment) }}
                                    </strong>
                                </div>
                            </div>
                        </section>

                        <!-- بيانات الدفع الإلكتروني -->
                        <section
                            v-if="isElectronic(paymentForm.payment_method)"
                            class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/60 sm:grid-cols-2"
                        >
                            <label class="block">
                                <span
                                    class="text-xs font-bold text-slate-600 dark:text-slate-300"
                                >
                                    البنك أو التطبيق
                                </span>

                                <input
                                    v-model.trim="paymentForm.bank_or_app_name"
                                    type="text"
                                    maxlength="255"
                                    class="mt-1.5 w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                                    placeholder="مثال: بنك فلسطين"
                                    required
                                />
                            </label>

                            <label class="block">
                                <span
                                    class="text-xs font-bold text-slate-600 dark:text-slate-300"
                                >
                                    رقم العملية
                                </span>

                                <input
                                    v-model.trim="paymentForm.transaction_reference"
                                    type="text"
                                    maxlength="255"
                                    class="mt-1.5 w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                                    placeholder="اختياري"
                                />
                            </label>
                        </section>

                        <!-- التاريخ والملاحظات -->
                        <section class="grid gap-4 sm:grid-cols-2">
                            <label class="block">
                                <span
                                    class="text-sm font-bold text-slate-700 dark:text-slate-300"
                                >
                                    تاريخ الدفع
                                </span>

                                <input
                                    v-model="paymentForm.paid_at"
                                    type="date"
                                    :min="invoiceSaleDate"
                                    :max="today"
                                    class="mt-1.5 w-full rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                                    required
                                />
                            </label>

                            <label class="block">
                                <span
                                    class="text-sm font-bold text-slate-700 dark:text-slate-300"
                                >
                                    ملاحظات
                                </span>

                                <textarea
                                    v-model.trim="paymentForm.notes"
                                    rows="2"
                                    maxlength="500"
                                    class="mt-1.5 w-full resize-none rounded-xl border-slate-300 bg-white text-sm dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                                    placeholder="ملاحظات الدفعة..."
                                ></textarea>
                            </label>
                        </section>

                        <!-- errors -->
                        <div
                            v-if="paymentError || Object.keys(paymentForm.errors).length"
                            class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
                        >
                            <p
                                v-if="paymentError"
                                class="font-black"
                            >
                                {{ paymentError }}
                            </p>

                            <p
                                v-for="(error, key) in paymentForm.errors"
                                :key="key"
                                class="mt-1"
                            >
                                {{ error }}
                            </p>
                        </div>

                        <!-- action -->
                        <div
                            class="sticky bottom-0 z-20 -mx-1 rounded-2xl border border-slate-800 bg-slate-950 p-4 text-white shadow-[0_-12px_30px_rgba(15,23,42,0.18)]"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        سيتم تحصيل
                                    </p>

                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-lg"
                                    >
                                        {{ formatCurrency(paymentAmount) }}
                                    </strong>

                                    <p
                                        v-if="selectedAccount"
                                        class="mt-1 text-[10px] text-slate-400"
                                    >
                                        إلى {{ selectedAccount.name }}
                                    </p>
                                </div>

                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-slate-700 px-4 py-3 text-sm font-black text-slate-200 transition hover:bg-slate-800"
                                        @click="closePaymentModal"
                                    >
                                        إلغاء
                                    </button>

                                    <button
                                        type="submit"
                                        :disabled="paymentForm.processing || Boolean(paymentError)"
                                        class="rounded-xl bg-indigo-500 px-5 py-3 text-sm font-black text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-400 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {{
                                            paymentForm.processing
                                                ? 'جاري تسجيل الدفعة...'
                                                : remainingAfterPayment <= 0.00001
                                                    ? 'تسجيل وإغلاق المبلغ المتبقي'
                                                    : 'تسجيل الدفعة'
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </template>
        </Modal>

        <!-- Modal إلغاء -->
        <Modal :show="cancelModal.show" @close="cancelModal.show = false">
            <template #title>إلغاء فاتورة البيع</template>
            <template #content>
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    هل أنت متأكد من إلغاء فاتورة البيع
                    <span class="font-semibold text-gray-900 dark:text-white">{{ invoice.invoice_number }}</span>؟
                </p>
                <p class="mt-2 text-sm text-red-600 dark:text-red-400">
                    تنبيه: سيتم إعادة الكميات إلى المخزون.
                </p>
                <form @submit.prevent="submitCancel" class="mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            سبب الإلغاء <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            v-model="cancelForm.reason"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                            placeholder="سبب إلغاء الفاتورة"
                        />
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            @click="cancelModal.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="cancelForm.processing"
                            class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-70"
                        >
                            {{ cancelForm.processing ? 'جاري الإلغاء...' : 'تأكيد الإلغاء' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import {
    computed,
    ref,
    watch,
} from 'vue';

import {
    Link,
    useForm,
} from '@inertiajs/vue3';

import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    invoice: {
        type: Object,
        required: true,
    },

    statuses: {
        type: Object,
        default: () => ({}),
    },

    paymentStatuses: {
        type: Object,
        default: () => ({}),
    },

    paymentMethods: {
        type: Object,
        default: () => ({}),
    },

    financialAccounts: {
        type: Array,
        default: () => [],
    },

    minimumPriceApproval: {
        type: Object,
        default: () => ({
            requires_approval: false,
            violations: [],
            minimum_total: 0,
            actual_total: 0,
            invoice_below_minimum: false,
        }),
    },
});

const approveModal =
    ref({
        show: false,
    });

const paymentModal =
    ref({
        show: false,
    });

const cancelModal =
    ref({
        show: false,
    });

const approveForm =
    useForm({
        minimum_price_approved: false,
        minimum_price_reason: '',
    });

const paymentForm =
    useForm({
        amount: '',
        payment_method:
            'cash',
        financial_account_id:
            '',
        bank_or_app_name:
            '',
        transaction_reference:
            '',
        paid_at:
            '',
        notes:
            '',
    });

const cancelForm =
    useForm({
        reason: '',
    });

const localDate = (
    date = new Date()
) => {
    const offset =
        date.getTimezoneOffset();

    const local =
        new Date(
            date.getTime()
            - offset * 60 * 1000
        );

    return local
        .toISOString()
        .slice(0, 10);
};

const today =
    localDate();

const invoiceSaleDate =
    computed(
        () =>
            String(
                props.invoice.sale_date
                || props.invoice.created_at
                || ''
            ).slice(
                0,
                10
            )
    );

const enumValue = (
    value
) => {
    if (
        value
        && typeof value
            === 'object'
    ) {
        return value.value
            ?? value.name
            ?? '';
    }

    return String(
        value
        ?? ''
    );
};

const roundMoney = (
    value
) =>
    Math.round(
        (
            Number(
                value
                || 0
            )
            + Number.EPSILON
        ) * 100
    ) / 100;

const formatCurrency = (
    value
) =>
    `${Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`;

const formatPercent = (
    value
) =>
    `${Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 1,
    })}%`;

const formatDate = (
    date
) => {
    if (!date) {
        return '—';
    }

    return new Intl
        .DateTimeFormat(
            'ar-PS-u-nu-latn',
            {
                dateStyle:
                    'medium',
                timeStyle:
                    date.includes?.('T')
                        ? 'short'
                        : undefined,
            }
        )
        .format(
            new Date(
                date
            )
        );
};

const currentTotal =
    computed(
        () =>
            roundMoney(
                props.invoice
                    .total_amount
                || 0
            )
    );

const currentPaid =
    computed(
        () =>
            roundMoney(
                props.invoice
                    .paid_amount
                || 0
            )
    );

const currentRemaining =
    computed(
        () =>
            roundMoney(
                props.invoice
                    .remaining_amount
                || 0
            )
    );

const paymentAmount =
    computed(
        () =>
            roundMoney(
                paymentForm.amount
                || 0
            )
    );

const paidAfterPayment =
    computed(
        () =>
            roundMoney(
                currentPaid.value
                + paymentAmount.value
            )
    );

const remainingAfterPayment =
    computed(
        () =>
            roundMoney(
                Math.max(
                    0,
                    currentRemaining.value
                    - paymentAmount.value
                )
            )
    );

const currentPaymentProgress =
    computed(
        () => {
            if (
                currentTotal.value
                <= 0
            ) {
                return 0;
            }

            return Math.min(
                100,
                Math.max(
                    0,
                    (
                        currentPaid.value
                        / currentTotal.value
                    ) * 100
                )
            );
        }
    );

const paymentStatusAfter =
    computed(
        () =>
            remainingAfterPayment
                .value
            <= 0.00001
                ? {
                    label:
                        'مدفوعة بالكامل',

                    class:
                        'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
                }
                : {
                    label:
                        'مدفوعة جزئياً',

                    class:
                        'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
                }
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
            paymentForm
                .payment_method
        ] || null)
    );

const compatibleAccounts =
    computed(
        () =>
            props
                .financialAccounts
                .filter(
                    (
                        account
                    ) =>
                        account.is_active
                            !== false
                        && (
                            !expectedAccountType
                                .value
                            || enumValue(
                                account.type
                            )
                            === expectedAccountType
                                .value
                        )
                )
    );

const selectedAccount =
    computed(
        () =>
            props
                .financialAccounts
                .find(
                    (
                        account
                    ) =>
                        Number(
                            account.id
                        )
                        === Number(
                            paymentForm
                                .financial_account_id
                        )
                )
            || null
    );

const accountBalanceAfterPayment =
    computed(
        () =>
            selectedAccount.value
                ? roundMoney(
                    Number(
                        selectedAccount
                            .value
                            .current_balance
                        || 0
                    )
                    + paymentAmount
                        .value
                )
                : 0
    );

const isElectronic = (
    method
) =>
    [
        'bank_transfer',
        'banking_app',
    ].includes(
        method
    );

const accountTypeLabel = (
    type
) => ({
    cash:
        'صندوق نقدي',

    bank:
        'حساب بنكي',

    banking_app:
        'تطبيق بنكي',
}[
    enumValue(
        type
    )
] || 'حساب مالي');

const paymentDateError =
    computed(
        () => {
            if (
                !paymentForm
                    .paid_at
            ) {
                return 'حدد تاريخ الدفع.';
            }

            if (
                invoiceSaleDate
                    .value
                && paymentForm
                    .paid_at
                    < invoiceSaleDate
                        .value
            ) {
                return 'تاريخ الدفع لا يمكن أن يسبق تاريخ فاتورة البيع.';
            }

            if (
                paymentForm
                    .paid_at
                > today
            ) {
                return 'تاريخ الدفع لا يمكن أن يكون في المستقبل.';
            }

            return '';
        }
    );

const paymentError =
    computed(
        () => {
            if (
                enumValue(
                    props.invoice
                        .status
                )
                !== 'approved'
            ) {
                return 'يمكن تسجيل دفعة على فاتورة بيع معتمدة فقط.';
            }

            if (
                currentRemaining
                    .value
                <= 0.00001
            ) {
                return 'الفاتورة مدفوعة بالكامل.';
            }

            if (
                paymentAmount
                    .value
                <= 0
            ) {
                return 'أدخل قيمة دفعة أكبر من صفر.';
            }

            if (
                paymentAmount
                    .value
                > currentRemaining
                    .value
            ) {
                return 'قيمة الدفعة أكبر من المبلغ المتبقي على الفاتورة.';
            }

            if (
                !paymentForm
                    .payment_method
            ) {
                return 'اختر طريقة الدفع.';
            }

            if (
                !paymentForm
                    .financial_account_id
            ) {
                return 'اختر الحساب المالي الذي سيتم تحصيل الدفعة إليه.';
            }

            if (
                !selectedAccount
                    .value
            ) {
                return 'الحساب المالي المحدد غير متاح.';
            }

            if (
                !compatibleAccounts
                    .value
                    .some(
                        (
                            account
                        ) =>
                            Number(
                                account.id
                            )
                            === Number(
                                paymentForm
                                    .financial_account_id
                            )
                    )
            ) {
                return 'الحساب المالي لا يتوافق مع طريقة الدفع المحددة.';
            }

            if (
                isElectronic(
                    paymentForm
                        .payment_method
                )
                && !String(
                    paymentForm
                        .bank_or_app_name
                    || ''
                ).trim()
            ) {
                return 'أدخل اسم البنك أو التطبيق.';
            }

            if (
                paymentDateError
                    .value
            ) {
                return paymentDateError
                    .value;
            }

            return '';
        }
    );

const confirmApprove =
    () => {
        approveForm.reset();
        approveForm.clearErrors();

        approveForm.minimum_price_approved =
            Boolean(
                props.minimumPriceApproval
                    ?.requires_approval
            );

        approveModal
            .value
            .show =
                true;
    };

const closeApproveModal =
    () => {
        if (
            approveForm.processing
        ) {
            return;
        }

        approveModal
            .value
            .show =
                false;

        approveForm.reset();
        approveForm.clearErrors();
    };

const submitApprove =
    () => {
        if (
            props.minimumPriceApproval
                ?.requires_approval
            && String(
                approveForm
                    .minimum_price_reason
                || ''
            ).trim().length < 3
        ) {
            approveForm.setError(
                'minimum_price_reason',
                'اكتب سبباً واضحاً للموافقة على البيع تحت أقل سعر.'
            );

            return;
        }

        approveForm.minimum_price_approved =
            Boolean(
                props.minimumPriceApproval
                    ?.requires_approval
            );

        approveForm.post(
            route(
                'sales.approve',
                props.invoice.id
            ),
            {
                preserveScroll:
                    true,

                onSuccess:
                    () => {
                        closeApproveModal();
                    },
            }
        );
    };

const setQuickPayment = (
    type
) => {
    const remaining =
        currentRemaining
            .value;

    if (
        remaining <= 0
    ) {
        paymentForm.amount =
            '';
        return;
    }

    if (
        type
        === 'quarter'
    ) {
        paymentForm.amount =
            roundMoney(
                remaining
                * 0.25
            );
        return;
    }

    if (
        type
        === 'half'
    ) {
        paymentForm.amount =
            roundMoney(
                remaining
                * 0.50
            );
        return;
    }

    paymentForm.amount =
        remaining;
};

const selectPaymentMethod = (
    method
) => {
    paymentForm
        .payment_method =
            method;
};

const openPaymentModal =
    () => {
        paymentForm.reset();
        paymentForm
            .clearErrors();

        paymentForm.amount =
            currentRemaining
                .value;

        paymentForm
            .payment_method =
                'cash';

        paymentForm
            .financial_account_id =
                props
                    .financialAccounts
                    .find(
                        (
                            account
                        ) =>
                            account.is_active
                                !== false
                            && enumValue(
                                account.type
                            )
                            === 'cash'
                    )
                    ?.id
                || '';

        paymentForm
            .bank_or_app_name =
                '';

        paymentForm
            .transaction_reference =
                '';

        paymentForm.paid_at =
            today;

        paymentForm.notes =
            '';

        paymentModal
            .value
            .show =
                true;
    };

const closePaymentModal =
    () => {
        paymentModal
            .value
            .show =
                false;

        paymentForm.reset();
        paymentForm
            .clearErrors();
    };

const submitPayment =
    () => {
        if (
            paymentError.value
        ) {
            return;
        }

        paymentForm.post(
            route(
                'sales.add-payment',
                props.invoice.id
            ),
            {
                preserveScroll:
                    true,

                onSuccess:
                    () => {
                        closePaymentModal();
                    },
            }
        );
    };

const openCancelModal =
    () => {
        cancelModal
            .value
            .show =
                true;

        cancelForm.reset();
        cancelForm
            .clearErrors();
    };

const submitCancel =
    () => {
        cancelForm.post(
            route(
                'sales.cancel',
                props.invoice.id
            ),
            {
                preserveScroll:
                    true,

                onSuccess:
                    () => {
                        cancelModal
                            .value
                            .show =
                                false;
                    },
            }
        );
    };

watch(
    () =>
        paymentForm
            .payment_method,

    () => {
        const currentIsCompatible =
            compatibleAccounts
                .value
                .some(
                    (
                        account
                    ) =>
                        Number(
                            account.id
                        )
                        === Number(
                            paymentForm
                                .financial_account_id
                        )
                );

        if (
            !currentIsCompatible
        ) {
            paymentForm
                .financial_account_id =
                    compatibleAccounts
                        .value[0]
                        ?.id
                    || '';
        }

        if (
            paymentForm
                .payment_method
            === 'cash'
        ) {
            paymentForm
                .bank_or_app_name =
                    '';

            paymentForm
                .transaction_reference =
                    '';
        } else {
            paymentForm
                .bank_or_app_name =
                    selectedAccount
                        .value
                        ?.name
                    || compatibleAccounts
                        .value[0]
                        ?.name
                    || '';
        }
    }
);

watch(
    () =>
        paymentForm
            .financial_account_id,

    () => {
        if (
            paymentForm
                .payment_method
            !== 'cash'
            && selectedAccount
                .value
        ) {
            paymentForm
                .bank_or_app_name =
                    selectedAccount
                        .value
                        .name;
        }
    }
);
</script>

<style scoped>
.sales-payment-scroll {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
    -webkit-overflow-scrolling: touch;
}

.sales-payment-scroll::-webkit-scrollbar {
    width: 6px;
}

.sales-payment-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.sales-payment-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}

:global(.dark) .sales-payment-scroll {
    scrollbar-color: #475569 transparent;
}

:global(.dark) .sales-payment-scroll::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
