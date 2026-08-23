<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                        تفاصيل طلب الصيانة
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ order.order_number }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link
                        :href="route('repairs.index')"
                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        القائمة
                    </Link>
                    <Link
                        :href="route('repairs.print', order.id)"
                        target="_blank"
                        class="flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        طباعة
                    </Link>
                </div>
            </div>
        </template>

        <!-- حالة الطلب -->
        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="flex flex-wrap items-center justify-between gap-4 p-6">
                <div class="flex flex-wrap items-center gap-3">
                    <span
                        v-if="order.repair_mode === 'quick'"
                        class="inline-flex items-center gap-1 rounded-full bg-violet-100 px-3 py-1.5 text-sm font-black text-violet-700 dark:bg-violet-950/30 dark:text-violet-300"
                    >
                        ⚡ صيانة سريعة
                    </span>

                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium"
                        :class="{
                            'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400': order.status === 'received',
                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': order.status === 'in_progress',
                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': order.status === 'ready',
                            'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300': order.status === 'delivered',
                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': order.status === 'cancelled'
                        }"
                    >
                        {{ statuses[order.status] || order.status }}
                    </span>
                    <span v-if="order.sub_status !== 'none'" class="text-sm text-gray-500 dark:text-gray-400">
                        {{ subStatuses[order.sub_status] || order.sub_status }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-medium"
                        :class="{
                            'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400': order.payment_status === 'unpaid',
                            'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400': order.payment_status === 'partially_paid',
                            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400': order.payment_status === 'paid'
                        }"
                    >
                        {{ order.payment_status === 'unpaid' ? 'غير مدفوعة' : order.payment_status === 'partially_paid' ? 'مدفوعة جزئياً' : 'مدفوعة بالكامل' }}
                    </span>
                    <span v-if="order.is_overdue" class="inline-block text-sm text-red-600 dark:text-red-400">
                        تنبيه: متأخر
                    </span>
                </div>
                <div class="text-right text-sm text-gray-500 dark:text-gray-400">
                    <p>تاريخ الاستلام: {{ formatDate(order.received_at) }}</p>
                    <p v-if="order.expected_delivery_date">التسليم المتوقع: {{ formatDate(order.expected_delivery_date) }}</p>
                    <p v-if="order.completed_at">تاريخ الإنجاز: {{ formatDate(order.completed_at) }}</p>
                    <p v-if="order.delivered_at">تاريخ التسليم: {{ formatDate(order.delivered_at) }}</p>
                </div>
            </div>
        </div>

        <!-- الأزرار الإجرائية -->
        <div v-if="order.status !== 'cancelled' || order.can_add_payment" class="mt-4 flex flex-wrap gap-2">
            <button
                v-if="order.status !== 'delivered' && order.can_be_edited"
                @click="openInspectionModal"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700"
            >
                <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                تسجيل الفحص
            </button>

            <button
                v-if="order.status !== 'delivered' && order.can_add_parts"
                @click="openPartModal"
                class="rounded-lg bg-purple-600 px-4 py-2 text-sm text-white hover:bg-purple-700"
            >
                <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                إضافة قطعة غيار
            </button>

            <button
                v-if="order.status !== 'delivered' && order.can_be_completed"
                @click="openReadyModal"
                class="rounded-lg bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700"
            >
                <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                تجهيز للاستلام
            </button>

            <button
                v-if="order.status !== 'delivered' && order.can_be_delivered"
                @click="openDeliverModal"
                class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-700"
            >
                <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                </svg>
                تسليم الجهاز
            </button>

            <button
                v-if="order.can_add_payment"
                @click="openPaymentModal"
                class="rounded-lg bg-amber-600 px-4 py-2 text-sm text-white hover:bg-amber-700"
            >
                <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                تسجيل دفعة
            </button>

            <button
                v-if="order.status !== 'delivered' && order.status !== 'cancelled'"
                @click="openCancelModal"
                class="rounded-lg bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700"
            >
                <svg class="inline h-4 w-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                إلغاء الطلب
            </button>
        </div>

        <!-- المحتوى الرئيسي -->
        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <!-- بيانات العميل -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">بيانات العميل</h3>
                    </div>
                    <div class="p-6">
                        <p class="font-medium text-gray-900 dark:text-white">{{ order.customer_name }}</p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">{{ order.customer_phone }}</p>
                        <p v-if="order.customer" class="text-sm text-gray-500 dark:text-gray-400">
                            كود العميل: {{ order.customer.code }}
                        </p>
                    </div>
                </div>

                <!-- بيانات الجهاز -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">بيانات الجهاز</h3>
                    </div>
                    <div class="grid grid-cols-2 gap-4 p-6">
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">نوع الجهاز</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ order.device_type }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">العلامة التجارية</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ order.brand }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">الموديل</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ order.model }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">اللون</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ order.color || '-' }}</p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">وصف المشكلة</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ order.problem_description }}</p>
                        </div>
                        <div v-if="order.device_condition" class="col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">حالة الجهاز عند الاستلام</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ order.device_condition }}</p>
                        </div>
                        <div v-if="order.received_accessories" class="col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">الملحقات المستلمة</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ order.received_accessories }}</p>
                        </div>
                        <div v-if="order.lock_code_decrypted" class="col-span-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400">رمز القفل</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ order.lock_code_decrypted }}</p>
                        </div>
                    </div>
                </div>

                <!-- صور الجهاز -->
                <div v-if="order.attachments && order.attachments.length > 0" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">صور الجهاز</h3>
                    </div>
                    <div class="grid grid-cols-3 gap-4 p-6 sm:grid-cols-4">
                        <div
                            v-for="attachment in order.attachments"
                            :key="attachment.id"
                            class="cursor-pointer overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700"
                            @click="openImage(attachment)"
                        >
                            <img
                                :src="'/storage/' + attachment.file_path"
                                class="h-24 w-full object-cover"
                                :alt="'صورة ' + attachment.stage"
                            />
                            <p class="p-1 text-center text-xs text-gray-500 dark:text-gray-400">
                                {{ attachmentStages[attachment.stage] || attachment.stage }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- قطع الغيار -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">قطع الغيار المستخدمة</h3>
                    </div>
                    <div class="overflow-x-auto p-4">
                        <table v-if="order.parts && order.parts.length > 0" class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900/50">
                                <tr>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">القطعة</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الكمية</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">سعر الوحدة</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الإجمالي</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400">الحالة</th>
                                    <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                <tr v-for="part in order.parts" :key="part.id" class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-3">
                                        <p class="font-medium text-gray-900 dark:text-white">{{ part.product_name }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ part.product?.code }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ part.quantity }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ formatCurrency(part.unit_price) }}</td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ formatCurrency(part.total_price) }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                            :class="[
                                                part.is_committed
                                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                                    : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'
                                            ]"
                                        >
                                            {{ part.is_committed ? 'معتمدة' : 'غير معتمدة' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            v-if="!part.is_committed && order.can_add_parts"
                                            @click="removePart(part.id)"
                                            class="rounded-lg p-1 text-red-400 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </button>
                                        <button
                                            v-if="part.is_committed && order.can_add_parts"
                                            @click="openRevertModal(part)"
                                            class="rounded-lg p-1 text-orange-400 hover:bg-orange-50 hover:text-orange-600 dark:hover:bg-orange-900/20"
                                        >
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div v-else class="py-8 text-center text-gray-500 dark:text-gray-400">
                            لا توجد قطع غيار مسجلة
                        </div>
                    </div>
                    <div v-if="order.can_add_parts && order.parts.some(p => !p.is_committed)" class="border-t border-gray-200 p-4 dark:border-gray-700">
                        <button
                            @click="commitParts"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm text-white hover:bg-green-700"
                        >
                            اعتماد قطع الغيار
                        </button>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 px-6 py-4 dark:border-gray-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white">رحلة الطلب</h3>
                    </div>
                    <div class="p-6">
                        <div class="relative">
                            <div class="absolute right-4 top-0 h-full w-0.5 bg-gray-200 dark:bg-gray-700"></div>
                            <div v-for="(history, index) in order.status_histories" :key="index" class="relative mb-6 pr-10 last:mb-0">
                                <div class="absolute right-0 top-1 h-4 w-4 rounded-full border-2 border-blue-500 bg-white dark:bg-gray-800"></div>
                                <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                    <div class="flex flex-wrap items-center justify-between">
                                        <div>
                                            <span class="font-medium text-gray-900 dark:text-white">
                                                {{ statuses[history.to_status] || history.to_status }}
                                            </span>
                                            <span v-if="history.from_status" class="text-sm text-gray-500 dark:text-gray-400">
                                                (من {{ statuses[history.from_status] || history.from_status }})
                                            </span>
                                        </div>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ formatDateTime(history.created_at) }}
                                        </span>
                                    </div>
                                    <p v-if="history.notes" class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                        {{ history.notes }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الشريط الجانبي -->
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-4">
                    <!-- ملخص التكاليف -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white">ملخص التكاليف</h4>
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">تكلفة الفحص</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(order.inspection_fee) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">أجرة الصيانة</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(order.labor_cost) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">قطع الغيار</span>
                                <span class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(order.parts_cost) }}</span>
                            </div>
                            <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                                <div class="flex justify-between text-lg font-bold">
                                    <span class="text-gray-900 dark:text-white">الإجمالي</span>
                                    <span class="text-blue-600 dark:text-blue-400">{{ formatCurrency(order.total_amount) }}</span>
                                </div>
                            </div>
                            <div class="border-t border-gray-200 pt-3 dark:border-gray-700">
                                <div class="flex justify-between">
                                    <span class="text-green-600 dark:text-green-400">المدفوع</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">{{ formatCurrency(order.paid_amount) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-red-600 dark:text-red-400">المتبقي</span>
                                    <span class="font-medium text-red-600 dark:text-red-400">{{ formatCurrency(order.remaining_amount) }}</span>
                                </div>
                            </div>
                            <div v-if="order.status === 'delivered'" class="border-t border-gray-200 pt-3 dark:border-gray-700">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">الربح</span>
                                    <span class="font-medium text-green-600 dark:text-green-400">{{ formatCurrency(order.profit) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- الدفعات -->
                    <div v-if="order.payments && order.payments.length > 0" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white">سجل الدفعات</h4>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            <div v-for="payment in order.payments" :key="payment.id" class="p-4">
                                <div class="flex justify-between">
                                    <div>
                                        <p class="font-medium text-gray-900 dark:text-white">{{ formatCurrency(payment.amount) }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ paymentMethods[payment.payment_method] || payment.payment_method }}
                                        </p>
                                        <p
                                            v-if="payment.financial_account"
                                            class="mt-1 text-[11px] font-semibold text-blue-600 dark:text-blue-300"
                                        >
                                            {{ payment.financial_account.name }}
                                        </p>
                                    </div>
                                    <div class="text-right text-xs text-gray-500 dark:text-gray-400">
                                        <p>{{ formatDateTime(payment.paid_at) }}</p>
                                        <p v-if="payment.transaction_reference" class="text-gray-400">{{ payment.transaction_reference }}</p>
                                    </div>
                                </div>
                                <p v-if="payment.notes" class="mt-1 text-xs text-gray-400">{{ payment.notes }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- ملاحظات -->
                    <div v-if="order.internal_notes || order.customer_notes" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                            <h4 class="font-semibold text-gray-900 dark:text-white">ملاحظات</h4>
                        </div>
                        <div class="space-y-3 p-4">
                            <div v-if="order.customer_notes">
                                <p class="text-xs text-gray-500 dark:text-gray-400">ملاحظات العميل</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ order.customer_notes }}</p>
                            </div>
                            <div v-if="order.internal_notes">
                                <p class="text-xs text-gray-500 dark:text-gray-400">ملاحظات داخلية</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ order.internal_notes }}</p>
                            </div>
                            <div v-if="order.technician_name">
                                <p class="text-xs text-gray-500 dark:text-gray-400">اسم الفني</p>
                                <p class="text-sm text-gray-900 dark:text-white">{{ order.technician_name }}</p>
                            </div>
                            <div v-if="order.cancellation_reason">
                                <p class="text-xs text-red-500 dark:text-red-400">سبب الإلغاء</p>
                                <p class="text-sm text-red-600 dark:text-red-400">{{ order.cancellation_reason }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal الفحص -->
        <Modal :show="modals.inspection.show" @close="modals.inspection.show = false">
            <template #title>تسجيل الفحص</template>
            <template #content>
                <form @submit.prevent="submitInspection" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            نتيجة الفحص
                        </label>
                        <textarea
                            v-model="inspectionForm.inspection_result"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="نتيجة الفحص..."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            سبب العطل
                        </label>
                        <textarea
                            v-model="inspectionForm.fault_cause"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="سبب العطل..."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الإجراء المنفذ
                        </label>
                        <textarea
                            v-model="inspectionForm.repair_action"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="الإجراء المنفذ..."
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                أجرة الصيانة
                            </label>
                            <input
                                v-model.number="inspectionForm.labor_cost"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                التكلفة التقديرية
                            </label>
                            <input
                                v-model.number="inspectionForm.estimated_cost"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            اسم الفني
                        </label>
                        <input
                            v-model="inspectionForm.technician_name"
                            type="text"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            حالة موافقة العميل
                        </label>
                        <select
                            v-model="inspectionForm.customer_approval_status"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                        >
                            <option value="not_required">لا تحتاج موافقة</option>
                            <option value="pending">بانتظار الموافقة</option>
                            <option value="approved">موافق</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الحالة الفرعية
                        </label>
                        <select
                            v-model="inspectionForm.sub_status"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                        >
                            <option value="waiting_inspection">بانتظار الفحص</option>
                            <option value="waiting_customer_approval">بانتظار موافقة العميل</option>
                            <option value="waiting_part">بانتظار قطعة غيار</option>
                            <option value="under_testing">تحت الاختبار</option>
                            <option value="unrepairable">تعذر الإصلاح</option>
                            <option value="returned_for_repair">مرتجع للصيانة</option>
                            <option value="none">لا توجد حالة فرعية</option>
                        </select>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="modals.inspection.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="inspectionForm.processing"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-70"
                        >
                            {{ inspectionForm.processing ? 'جاري الحفظ...' : 'حفظ' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>

        <!-- Modal إضافة قطعة غيار -->
        <Modal :show="modals.part.show" @close="modals.part.show = false">
            <template #title>إضافة قطعة غيار</template>
            <template #content>
                <form @submit.prevent="submitPart" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            القطعة <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="partForm.product_id"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            required
                        >
                            <option value="">اختر القطعة</option>
                            <option v-for="product in products" :key="product.id" :value="product.id">
                                {{ product.name }} ({{ product.available_quantity }} متوفرة)
                            </option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                الكمية <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model.number="partForm.quantity"
                                type="number"
                                min="1"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                required
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                سعر البيع <span class="text-red-500">*</span>
                            </label>
                            <input
                                v-model.number="partForm.unit_price"
                                type="number"
                                step="0.01"
                                min="0"
                                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                                required
                            />
                        </div>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="modals.part.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="partForm.processing"
                            class="rounded-lg bg-purple-600 px-4 py-2 text-sm font-medium text-white hover:bg-purple-700 disabled:opacity-70"
                        >
                            {{ partForm.processing ? 'جاري الإضافة...' : 'إضافة' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>

        <!-- Modal تجهيز للاستلام -->
        <Modal :show="modals.ready.show" @close="modals.ready.show = false">
            <template #title>تجهيز الجهاز للاستلام</template>
            <template #content>
                <form @submit.prevent="submitReady" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            نتيجة الاختبار النهائي
                        </label>
                        <textarea
                            v-model="readyForm.inspection_result"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="نتيجة الاختبار النهائي..."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            الإجراء المنفذ
                        </label>
                        <textarea
                            v-model="readyForm.repair_action"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="الإجراء النهائي..."
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            أجرة الصيانة النهائية
                        </label>
                        <input
                            v-model.number="readyForm.labor_cost"
                            type="number"
                            step="0.01"
                            min="0"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                        />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            ملاحظات داخلية
                        </label>
                        <textarea
                            v-model="readyForm.internal_notes"
                            rows="2"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white sm:text-sm"
                            placeholder="ملاحظات إضافية..."
                        />
                    </div>
                    <div class="flex justify-end gap-3">
                        <button
                            type="button"
                            @click="modals.ready.show = false"
                            class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        >
                            إلغاء
                        </button>
                        <button
                            type="submit"
                            :disabled="readyForm.processing"
                            class="rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 disabled:opacity-70"
                        >
                            {{ readyForm.processing ? 'جاري التجهيز...' : 'تأكيد التجهيز' }}
                        </button>
                    </div>
                </form>
            </template>
        </Modal>

        <!-- تسجيل دفعة -->
        <Modal
            :show="modals.payment.show"
            @close="closePaymentModal"
        >
            <template #title>
                تسجيل دفعة صيانة
            </template>

            <template #content>
                <div
                    class="repair-payment-scroll max-h-[calc(100dvh-8.5rem)] overflow-y-auto overscroll-contain px-1 pb-1 sm:max-h-[calc(100dvh-9rem)]"
                >
                    <form
                        class="space-y-4 pr-1"
                        @submit.prevent="submitPayment"
                    >
                        <!-- بطاقة الطلب -->
                        <section
                            class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800"
                        >
                            <div
                                class="bg-gradient-to-l from-slate-950 via-amber-950 to-amber-600 p-4 text-white"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span
                                                class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-black text-amber-100 backdrop-blur"
                                            >
                                                Repair Payment
                                            </span>

                                            <span
                                                dir="ltr"
                                                class="rounded-full bg-white/10 px-2.5 py-1 text-[10px] font-black text-white backdrop-blur"
                                            >
                                                {{ order.order_number }}
                                            </span>
                                        </div>

                                        <h3 class="mt-3 truncate text-lg font-black">
                                            {{ order.customer_name || order.customer?.name || 'عميل' }}
                                        </h3>

                                        <p class="mt-1 truncate text-xs text-amber-100">
                                            {{ order.device_type }}
                                            <template v-if="order.brand">
                                                · {{ order.brand }}
                                            </template>
                                            <template v-if="order.model">
                                                {{ order.model }}
                                            </template>
                                        </p>
                                    </div>

                                    <div
                                        class="shrink-0 rounded-2xl bg-white/10 px-3 py-2 text-left backdrop-blur"
                                    >
                                        <p class="text-[10px] text-amber-100">
                                            المتبقي
                                        </p>

                                        <strong
                                            dir="ltr"
                                            class="mt-1 block text-lg font-black"
                                        >
                                            {{ formatCurrency(paymentCurrentRemaining) }}
                                        </strong>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <div
                                        class="mb-2 flex items-center justify-between text-[10px] text-amber-100"
                                    >
                                        <span>نسبة السداد الحالية</span>
                                        <span dir="ltr">
                                            {{ formatPercent(paymentProgressBefore) }}
                                        </span>
                                    </div>

                                    <div
                                        class="h-2 overflow-hidden rounded-full bg-white/15"
                                    >
                                        <div
                                            class="h-full rounded-full bg-emerald-400 transition-all duration-300"
                                            :style="{ width: `${paymentProgressBefore}%` }"
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
                                        {{ formatCurrency(paymentCurrentTotal) }}
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
                                        {{ formatCurrency(paymentCurrentPaid) }}
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
                                        {{ formatCurrency(paymentCurrentRemaining) }}
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
                                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                        أدخل المبلغ أو اختر نسبة سريعة من المتبقي.
                                    </p>
                                </div>

                                <span
                                    dir="ltr"
                                    class="text-[10px] font-bold text-slate-400"
                                >
                                    Max: {{ formatCurrency(paymentCurrentRemaining) }}
                                </span>
                            </div>

                            <div class="relative mt-3">
                                <input
                                    v-model.number="paymentForm.amount"
                                    type="number"
                                    min="0.01"
                                    :max="paymentCurrentRemaining"
                                    step="0.01"
                                    class="block w-full rounded-2xl border-2 border-slate-200 bg-slate-50 px-4 py-4 pl-20 text-xl font-black text-slate-950 shadow-sm transition focus:border-amber-500 focus:bg-white focus:ring-amber-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white"
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
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-amber-950/30"
                                    @click="setRepairQuickPayment('quarter')"
                                >
                                    25%
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-black text-slate-700 transition hover:border-amber-300 hover:bg-amber-50 hover:text-amber-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-amber-950/30"
                                    @click="setRepairQuickPayment('half')"
                                >
                                    50%
                                </button>

                                <button
                                    type="button"
                                    class="rounded-xl bg-amber-50 px-3 py-2.5 text-xs font-black text-amber-700 transition hover:bg-amber-100 dark:bg-amber-950/30 dark:text-amber-300"
                                    @click="setRepairQuickPayment('full')"
                                >
                                    دفع المتبقي كامل
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
                                    :class="repairPaymentStatusAfter.class"
                                >
                                    {{ repairPaymentStatusAfter.label }}
                                </span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div
                                    class="rounded-xl bg-white p-3 dark:bg-slate-800"
                                >
                                    <p class="text-[10px] text-slate-400">
                                        إجمالي المدفوع
                                    </p>
                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-right text-base text-emerald-600"
                                    >
                                        {{ formatCurrency(paymentPaidAfter) }}
                                    </strong>
                                </div>

                                <div
                                    class="rounded-xl bg-white p-3 dark:bg-slate-800"
                                >
                                    <p class="text-[10px] text-slate-400">
                                        المتبقي الجديد
                                    </p>
                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-right text-base text-amber-600"
                                    >
                                        {{ formatCurrency(paymentRemainingAfter) }}
                                    </strong>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div
                                    class="h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700"
                                >
                                    <div
                                        class="h-full rounded-full bg-emerald-500 transition-all duration-300"
                                        :style="{ width: `${paymentProgressAfter}%` }"
                                    ></div>
                                </div>
                            </div>
                        </section>

                        <!-- طريقة الدفع -->
                        <section>
                            <div class="flex items-center justify-between gap-3">
                                <label
                                    class="text-sm font-black text-slate-800 dark:text-slate-100"
                                >
                                    طريقة الدفع
                                </label>
                                <span class="text-[10px] text-slate-400">
                                    {{ selectedRepairPaymentMethodLabel }}
                                </span>
                            </div>

                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                <button
                                    v-for="(label, value) in selectablePaymentMethods"
                                    :key="value"
                                    type="button"
                                    class="rounded-2xl border p-3 text-right transition"
                                    :class="
                                        paymentForm.payment_method === value
                                            ? 'border-amber-500 bg-amber-50 ring-2 ring-amber-500/10 dark:border-amber-500 dark:bg-amber-950/30'
                                            : 'border-slate-200 bg-white hover:border-amber-200 dark:border-slate-700 dark:bg-slate-800'
                                    "
                                    @click="selectRepairPaymentMethod(value)"
                                >
                                    <span
                                        class="block text-xs font-black text-slate-900 dark:text-white"
                                    >
                                        {{ label }}
                                    </span>

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
                            <label
                                class="block text-sm font-black text-slate-800 dark:text-slate-100"
                            >
                                الحساب المالي
                                <span class="mr-1 text-[10px] font-normal text-slate-400">
                                    اختياري إذا كان النظام يختاره تلقائياً
                                </span>
                            </label>

                            <div
                                v-if="accountsFor(paymentForm.payment_method).length"
                                class="mt-3 grid gap-2"
                            >
                                <button
                                    v-for="account in accountsFor(paymentForm.payment_method)"
                                    :key="account.id"
                                    type="button"
                                    class="flex items-center justify-between gap-4 rounded-2xl border p-3 text-right transition"
                                    :class="
                                        Number(paymentForm.financial_account_id) === Number(account.id)
                                            ? 'border-amber-500 bg-amber-50 ring-2 ring-amber-500/10 dark:border-amber-500 dark:bg-amber-950/30'
                                            : 'border-slate-200 bg-white hover:border-amber-300 dark:border-slate-700 dark:bg-slate-800'
                                    "
                                    @click="paymentForm.financial_account_id = account.id"
                                >
                                    <div>
                                        <strong
                                            class="block text-sm text-slate-900 dark:text-white"
                                        >
                                            {{ account.name }}
                                        </strong>

                                        <span class="mt-1 block text-[10px] text-slate-400">
                                            {{ repairAccountTypeLabel(account.type) }}
                                        </span>
                                    </div>

                                    <svg
                                        v-if="Number(paymentForm.financial_account_id) === Number(account.id)"
                                        class="h-5 w-5 text-amber-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </button>
                            </div>

                            <div
                                v-else
                                class="mt-3 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs leading-6 text-amber-800 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-200"
                            >
                                لا يوجد حساب مالي نشط متوافق مع طريقة الدفع الحالية.
                            </div>
                        </section>

                        <!-- تفاصيل الدفع الإلكتروني -->
                        <section
                            v-if="isElectronic(paymentForm.payment_method)"
                            class="grid gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/60 sm:grid-cols-2"
                        >
                            <label class="block">
                                <span
                                    class="text-xs font-bold text-slate-600 dark:text-slate-300"
                                >
                                    اسم البنك أو التطبيق
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
                                    تاريخ ووقت الدفع
                                </span>
                                <input
                                    v-model="paymentForm.paid_at"
                                    type="datetime-local"
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
                                    placeholder="أي ملاحظة مرتبطة بالدفعة..."
                                ></textarea>
                            </label>
                        </section>

                        <div
                            v-if="repairPaymentError || Object.keys(paymentForm.errors).length"
                            class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-700 dark:border-rose-900/50 dark:bg-rose-950/30 dark:text-rose-300"
                        >
                            <p
                                v-if="repairPaymentError"
                                class="font-black"
                            >
                                {{ repairPaymentError }}
                            </p>

                            <p
                                v-for="(error, key) in paymentForm.errors"
                                :key="key"
                                class="mt-1"
                            >
                                {{ error }}
                            </p>
                        </div>

                        <!-- الإجراء النهائي -->
                        <div
                            class="sticky bottom-0 z-20 -mx-1 rounded-2xl border border-slate-800 bg-slate-950 p-4 text-white shadow-[0_-12px_30px_rgba(15,23,42,0.18)] dark:bg-slate-900"
                        >
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-[10px] text-slate-400">
                                        سيتم تسجيل
                                    </p>

                                    <strong
                                        dir="ltr"
                                        class="mt-1 block text-lg"
                                    >
                                        {{ formatCurrency(repairPaymentAmount) }}
                                    </strong>

                                    <p
                                        v-if="selectedRepairAccount"
                                        class="mt-1 text-[10px] text-slate-400"
                                    >
                                        في {{ selectedRepairAccount.name }}
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
                                        :disabled="paymentForm.processing || Boolean(repairPaymentError)"
                                        class="rounded-xl bg-amber-500 px-5 py-3 text-sm font-black text-slate-950 shadow-lg shadow-amber-500/20 transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {{
                                            paymentForm.processing
                                                ? 'جارٍ تسجيل الدفعة...'
                                                : paymentRemainingAfter <= 0.00001
                                                    ? 'تسجيل الدفعة وإغلاق المستحق'
                                                    : 'تأكيد تسجيل الدفعة'
                                        }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </template>
        </Modal>

        <!-- تسليم الجهاز -->
        <Modal :show="modals.deliver.show" @close="modals.deliver.show = false">
            <template #title>تسليم الجهاز للعميل</template>
            <template #content>
                <form class="space-y-4" @submit.prevent="submitDelivery">
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/60 dark:bg-emerald-950/30"><p class="font-bold text-emerald-800 dark:text-emerald-300">{{ order.device_type }} — {{ order.brand }} {{ order.model }}</p><p class="mt-1 text-sm text-emerald-700 dark:text-emerald-400">المتبقي قبل التسليم: {{ formatCurrency(order.remaining_amount) }}</p></div>
                    <div v-if="Number(order.remaining_amount) > 0" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="block"><span class="text-sm font-medium text-slate-700 dark:text-slate-300">دفعة عند التسليم</span><input v-model.number="deliverForm.payment_amount" type="number" min="0" :max="Number(order.remaining_amount)" step="0.01" class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" /></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700 dark:text-slate-300">طريقة الدفع</span><select v-model="deliverForm.payment_method" class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option v-for="(label, value) in selectablePaymentMethods" :key="value" :value="value">{{ label }}</option></select></label>
                            <label class="block"><span class="text-sm font-medium text-slate-700 dark:text-slate-300">الحساب المالي</span><select v-model="deliverForm.financial_account_id" class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="">اختر الحساب المالي</option><option v-for="account in accountsFor(deliverForm.payment_method)" :key="account.id" :value="account.id">{{ account.name }}</option></select></label>
                        </div>
                        <div v-if="isElectronic(deliverForm.payment_method)" class="grid gap-4 sm:grid-cols-2"><input v-model="deliverForm.bank_or_app_name" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="اسم البنك أو التطبيق" /><input v-model="deliverForm.transaction_reference" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="رقم العملية" /></div>
                        <p
                            v-if="deliveryPaymentError"
                            class="rounded-xl bg-rose-50 px-3 py-2 text-xs font-bold text-rose-700 dark:bg-rose-950/30 dark:text-rose-300"
                        >
                            {{ deliveryPaymentError }}
                        </p>
                        <label v-if="deliveryRemaining > 0" class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900/50 dark:bg-amber-950/30"><input v-model="deliverForm.allow_partial_payment" type="checkbox" class="mt-1 rounded border-slate-300 text-amber-600" /><span class="text-sm text-amber-800 dark:text-amber-300">أوافق على تسليم الجهاز مع بقاء {{ formatCurrency(deliveryRemaining) }} مستحقة على العميل.</span></label>
                    </div>
                    <label class="block"><span class="text-sm font-medium text-slate-700 dark:text-slate-300">ملاحظات الدفع</span><textarea v-model="deliverForm.payment_notes" rows="2" class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" /></label>
                    <div class="flex justify-end gap-3"><button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" @click="modals.deliver.show = false">إلغاء</button><button type="submit" :disabled="deliverForm.processing || Boolean(deliveryPaymentError) || (deliveryRemaining > 0 && !deliverForm.allow_partial_payment)" class="rounded-xl bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700 disabled:opacity-60">{{ deliverForm.processing ? 'جارٍ التسليم...' : 'تأكيد التسليم' }}</button></div>
                </form>
            </template>
        </Modal>

        <!-- إلغاء الطلب -->
        <Modal :show="modals.cancel.show" @close="modals.cancel.show = false">
            <template #title>إلغاء طلب الصيانة</template>
            <template #content>
                <form class="space-y-4" @submit.prevent="submitCancel">
                    <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm leading-6 text-rose-800 dark:border-rose-900/50 dark:bg-rose-950/20 dark:text-rose-200">
                        <p>
                            سيبقى الطلب محفوظاً، وستُعاد القطع المعتمدة إلى مخازنها الأصلية.
                        </p>
                        <p
                            v-if="Number(order.paid_amount || 0) > 0"
                            class="mt-2 font-black"
                        >
                            توجد دفعات بقيمة {{ formatCurrency(order.paid_amount) }}.
                            عند الإلغاء سيقوم النظام بإنشاء حركات عكس مالية وإرجاع المبالغ من نفس الحسابات الأصلية.
                        </p>
                    </div><label class="block"><span class="text-sm font-medium text-slate-700 dark:text-slate-300">سبب الإلغاء</span><textarea v-model="cancelForm.reason" rows="3" class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required /></label><div class="flex justify-end gap-3"><button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" @click="modals.cancel.show = false">رجوع</button><button type="submit" :disabled="cancelForm.processing" class="rounded-xl bg-rose-600 px-4 py-2 text-sm font-bold text-white hover:bg-rose-700 disabled:opacity-60">{{ cancelForm.processing ? 'جارٍ الإلغاء...' : 'تأكيد الإلغاء' }}</button></div></form>
            </template>
        </Modal>

        <!-- إعادة قطعة -->
        <Modal :show="modals.revert.show" @close="modals.revert.show = false">
            <template #title>إعادة قطعة إلى مخزون الصيانة</template>
            <template #content>
                <form class="space-y-4" @submit.prevent="submitRevert"><p class="text-sm text-slate-600 dark:text-slate-400">القطعة: <strong class="text-slate-900 dark:text-white">{{ selectedPart?.product_name }}</strong></p><label class="block"><span class="text-sm font-medium text-slate-700 dark:text-slate-300">سبب الإعادة</span><textarea v-model="revertForm.reason" rows="3" class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" required /></label><div class="flex justify-end gap-3"><button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800" @click="modals.revert.show = false">إلغاء</button><button type="submit" :disabled="revertForm.processing" class="rounded-xl bg-orange-600 px-4 py-2 text-sm font-bold text-white hover:bg-orange-700 disabled:opacity-60">{{ revertForm.processing ? 'جارٍ الإعادة...' : 'إعادة القطعة' }}</button></div></form>
            </template>
        </Modal>

        <!-- معاينة الصورة -->
        <Modal :show="modals.image.show" @close="modals.image.show = false">
            <template #title>معاينة صورة الجهاز</template>
            <template #content><img v-if="selectedAttachment" :src="'/storage/' + selectedAttachment.file_path" alt="صورة الجهاز" class="max-h-[70vh] w-full rounded-xl object-contain" /></template>
        </Modal>

        <ConfirmationModal :show="Boolean(confirmation.action)" :title="confirmation.title" :message="confirmation.message" :loading="confirmation.processing" @close="closeConfirmation" @confirm="confirmAction" />
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
    order: Object,
    statuses: Object,
    subStatuses: Object,
    approvalStatuses: Object,
    paymentMethods: Object,
    attachmentStages: { type: Object, default: () => ({}) },
    financialAccounts: { type: Array, default: () => [] },
    products: Array,
});

const modals = ref({
    inspection: { show: false },
    part: { show: false },
    ready: { show: false },
    deliver: { show: false },
    payment: { show: false },
    cancel: { show: false },
    revert: { show: false },
    image: { show: false },
});

const inspectionForm = useForm({
    inspection_result: props.order.inspection_result || '',
    fault_cause: props.order.fault_cause || '',
    repair_action: props.order.repair_action || '',
    labor_cost: props.order.labor_cost || 0,
    estimated_cost: props.order.estimated_cost || 0,
    technician_name: props.order.technician_name || '',
    customer_approval_status: props.order.customer_approval_status || 'not_required',
    sub_status: props.order.sub_status || 'waiting_inspection',
});

const partForm = useForm({
    product_id: '',
    quantity: 1,
    unit_price: 0,
});

const readyForm = useForm({
    inspection_result: props.order.inspection_result || '',
    repair_action: props.order.repair_action || '',
    labor_cost: props.order.labor_cost || 0,
    internal_notes: props.order.internal_notes || '',
});

const nowLocal = () => {
    const date = new Date(Date.now() - new Date().getTimezoneOffset() * 60000);
    return date.toISOString().slice(0, 16);
};

const paymentForm = useForm({
    amount: Number(props.order.remaining_amount || 0),
    payment_method: 'cash',
    financial_account_id: '',
    bank_or_app_name: '',
    transaction_reference: '',
    paid_at: nowLocal(),
    notes: '',
});

const deliverForm = useForm({
    payment_amount: Number(props.order.remaining_amount || 0),
    payment_method: 'cash',
    financial_account_id: '',
    bank_or_app_name: '',
    transaction_reference: '',
    payment_notes: '',
    allow_partial_payment: false,
});

const cancelForm = useForm({ reason: '' });
const revertForm = useForm({ reason: '' });
const selectedPart = ref(null);
const selectedAttachment = ref(null);
const confirmation = ref({ action: null, partId: null, title: '', message: '', processing: false });
const selectablePaymentMethods = computed(() =>
    Object.fromEntries(
        Object.entries(
            props.paymentMethods || {}
        ).filter(
            ([key]) =>
                key !== 'exchange_credit'
        )
    )
);

const deliveryRemaining = computed(() =>
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

const deliveryPaymentError = computed(() => {
    const amount =
        Number(
            deliverForm.payment_amount
            || 0
        );

    if (amount < 0) {
        return 'دفعة التسليم لا يمكن أن تكون سالبة.';
    }

    if (
        amount
        > Number(
            props.order.remaining_amount
            || 0
        )
    ) {
        return 'دفعة التسليم أكبر من المبلغ المتبقي.';
    }

    if (
        amount > 0
        && !deliverForm.payment_method
    ) {
        return 'اختر طريقة دفع دفعة التسليم.';
    }

    if (
        amount > 0
        && !deliverForm
            .financial_account_id
    ) {
        return 'اختر الحساب المالي الذي ستدخل إليه دفعة التسليم.';
    }

    if (
        amount > 0
        && isElectronic(
            deliverForm.payment_method
        )
        && !String(
            deliverForm
                .bank_or_app_name
            || ''
        ).trim()
    ) {
        return 'أدخل اسم البنك أو التطبيق لدفعة التسليم.';
    }

    return '';
});

const isElectronic = (method) =>
    [
        'bank_transfer',
        'banking_app',
    ].includes(method);

const repairEnumValue = (value) => {
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

const accountsFor = (method) => {
    const type =
        method === 'cash'
            ? 'cash'
            : method === 'bank_transfer'
                ? 'bank'
                : method === 'banking_app'
                    ? 'banking_app'
                    : null;

    return type
        ? props.financialAccounts.filter(
            (account) =>
                repairEnumValue(account.type)
                === type
        )
        : props.financialAccounts;
};

const repairAccountTypeLabel = (type) => ({
    cash: 'حساب نقدي',
    bank: 'حساب بنكي',
    banking_app: 'تطبيق بنكي',
}[repairEnumValue(type)] || repairEnumValue(type) || 'حساب مالي');

const roundRepairMoney = (value) =>
    Math.round(
        (
            Number(value || 0)
            + Number.EPSILON
        ) * 100
    ) / 100;

const paymentCurrentTotal = computed(() =>
    roundRepairMoney(
        props.order.total_amount
        || 0
    )
);

const paymentCurrentPaid = computed(() =>
    roundRepairMoney(
        props.order.paid_amount
        || 0
    )
);

const paymentCurrentRemaining = computed(() =>
    roundRepairMoney(
        props.order.remaining_amount
        || 0
    )
);

const repairPaymentAmount = computed(() =>
    roundRepairMoney(
        paymentForm.amount
        || 0
    )
);

const paymentPaidAfter = computed(() =>
    roundRepairMoney(
        paymentCurrentPaid.value
        + repairPaymentAmount.value
    )
);

const paymentRemainingAfter = computed(() =>
    roundRepairMoney(
        Math.max(
            0,
            paymentCurrentRemaining.value
            - repairPaymentAmount.value
        )
    )
);

const paymentProgressBefore = computed(() => {
    if (
        paymentCurrentTotal.value
        <= 0
    ) {
        return 0;
    }

    return Math.min(
        100,
        Math.max(
            0,
            (
                paymentCurrentPaid.value
                / paymentCurrentTotal.value
            ) * 100
        )
    );
});

const paymentProgressAfter = computed(() => {
    if (
        paymentCurrentTotal.value
        <= 0
    ) {
        return 0;
    }

    return Math.min(
        100,
        Math.max(
            0,
            (
                paymentPaidAfter.value
                / paymentCurrentTotal.value
            ) * 100
        )
    );
});

const repairPaymentStatusAfter = computed(() =>
    paymentRemainingAfter.value
    <= 0.00001
        ? {
            label: 'مدفوعة بالكامل',
            class:
                'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
        }
        : {
            label: 'مدفوعة جزئياً',
            class:
                'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
        }
);

const selectedRepairAccount = computed(() =>
    props.financialAccounts.find(
        (account) =>
            Number(account.id)
            === Number(
                paymentForm.financial_account_id
            )
    ) || null
);

const selectedRepairPaymentMethodLabel = computed(() =>
    selectablePaymentMethods.value[
        paymentForm.payment_method
    ]
    || paymentForm.payment_method
    || '—'
);

const repairPaymentError = computed(() => {
    if (
        repairPaymentAmount.value
        <= 0
    ) {
        return 'أدخل قيمة دفعة أكبر من صفر.';
    }

    if (
        repairPaymentAmount.value
        > paymentCurrentRemaining.value
    ) {
        return 'قيمة الدفعة أكبر من المبلغ المتبقي على طلب الصيانة.';
    }

    if (
        !paymentForm.payment_method
    ) {
        return 'اختر طريقة الدفع.';
    }

    if (
        !paymentForm.financial_account_id
    ) {
        return 'اختر الحساب المالي الذي ستدخل إليه الدفعة.';
    }

    if (
        isElectronic(
            paymentForm.payment_method
        )
        && !String(
            paymentForm.bank_or_app_name
            || ''
        ).trim()
    ) {
        return 'أدخل اسم البنك أو التطبيق.';
    }

    if (
        !paymentForm.paid_at
    ) {
        return 'حدد تاريخ ووقت الدفع.';
    }

    return '';
});

const formatCurrency = (value) =>
    `${Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    })} شيكل`;

const formatPercent = (value) =>
    `${Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 1,
    })}%`;

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-EG');
};

const formatDateTime = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleString('ar-EG');
};

const openInspectionModal = () => {
    modals.value.inspection.show = true;
};

const submitInspection = () => {
    inspectionForm.post(route('repairs.inspection', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            modals.value.inspection.show = false;
        },
    });
};

const openPartModal = () => {
    modals.value.part.show = true;
};

const submitPart = () => {
    partForm.post(route('repairs.add-part', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            modals.value.part.show = false;
            partForm.reset();
        },
    });
};

const removePart = (partId) => {
    confirmation.value = {
        action: 'remove-part',
        partId,
        title: 'إزالة قطعة غير معتمدة',
        message: 'سيتم حذف القطعة من طلب الصيانة دون التأثير على المخزون.',
        processing: false,
    };
};

const commitParts = () => {
    confirmation.value = {
        action: 'commit-parts',
        partId: null,
        title: 'اعتماد قطع الغيار',
        message: 'سيتم خصم جميع القطع غير المعتمدة من مخزون الصيانة. لا يمكن تكرار العملية.',
        processing: false,
    };
};

const openReadyModal = () => {
    modals.value.ready.show = true;
};

const submitReady = () => {
    readyForm.post(route('repairs.mark-ready', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            modals.value.ready.show = false;
        },
    });
};

const selectRepairPaymentMethod = (method) => {
    paymentForm.payment_method =
        method;

    const compatible =
        accountsFor(method);

    const selectedStillValid =
        compatible.some(
            (account) =>
                Number(account.id)
                === Number(
                    paymentForm.financial_account_id
                )
        );

    if (!selectedStillValid) {
        paymentForm.financial_account_id =
            compatible[0]?.id
            || '';
    }

    if (
        method === 'cash'
    ) {
        paymentForm.bank_or_app_name =
            '';
        paymentForm.transaction_reference =
            '';
    } else if (
        selectedRepairAccount.value
    ) {
        paymentForm.bank_or_app_name =
            selectedRepairAccount.value.name;
    }
};

const setRepairQuickPayment = (type) => {
    const remaining =
        paymentCurrentRemaining.value;

    if (
        remaining <= 0
    ) {
        paymentForm.amount = 0;
        return;
    }

    if (
        type === 'quarter'
    ) {
        paymentForm.amount =
            roundRepairMoney(
                remaining * 0.25
            );
        return;
    }

    if (
        type === 'half'
    ) {
        paymentForm.amount =
            roundRepairMoney(
                remaining * 0.50
            );
        return;
    }

    paymentForm.amount =
        remaining;
};

const openPaymentModal = () => {
    paymentForm.reset();
    paymentForm.clearErrors();

    paymentForm.amount =
        paymentCurrentRemaining.value;

    paymentForm.payment_method =
        'cash';

    paymentForm.paid_at =
        nowLocal();

    paymentForm.financial_account_id =
        accountsFor('cash')[0]?.id
        || '';

    paymentForm.bank_or_app_name =
        '';

    paymentForm.transaction_reference =
        '';

    paymentForm.notes =
        '';

    modals.value.payment.show =
        true;
};

const closePaymentModal = () => {
    modals.value.payment.show =
        false;

    paymentForm.reset();
    paymentForm.clearErrors();
};

const submitPayment = () => {
    if (repairPaymentError.value) {
        return;
    }

    paymentForm.post(
        route(
            'repairs.add-payment',
            props.order.id
        ),
        {
            preserveScroll: true,

            onSuccess: () => {
                closePaymentModal();
            },
        }
    );
};

const openDeliverModal = () => {
    deliverForm.reset();

    deliverForm.payment_amount =
        Number(
            props.order.remaining_amount
            || 0
        );

    deliverForm.payment_method =
        'cash';

    deliverForm.financial_account_id =
        accountsFor('cash')[0]?.id
        || '';

    deliverForm.bank_or_app_name =
        '';

    deliverForm.transaction_reference =
        '';

    deliverForm.allow_partial_payment =
        false;

    modals.value.deliver.show =
        true;
};

const submitDelivery = () => {
    if (deliveryPaymentError.value) {
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
                modals.value.deliver.show =
                    false;
            },
        }
    );
};

watch(
    () => deliverForm.payment_method,
    (method) => {
        const compatible =
            accountsFor(
                method
            );

        const selectedStillValid =
            compatible.some(
                (account) =>
                    Number(account.id)
                    === Number(
                        deliverForm
                            .financial_account_id
                    )
            );

        if (! selectedStillValid) {
            deliverForm.financial_account_id =
                compatible[0]?.id
                || '';
        }

        if (
            method === 'cash'
        ) {
            deliverForm.bank_or_app_name =
                '';

            deliverForm.transaction_reference =
                '';
        }
    }
);

const openCancelModal = () => {
    cancelForm.reset();
    modals.value.cancel.show = true;
};

const submitCancel = () => {
    cancelForm.post(route('repairs.cancel', props.order.id), {
        preserveScroll: true,
        onSuccess: () => { modals.value.cancel.show = false; },
    });
};

const openRevertModal = (part) => {
    selectedPart.value = part;
    revertForm.reset();
    modals.value.revert.show = true;
};

const submitRevert = () => {
    if (!selectedPart.value) return;
    revertForm.post(route('repairs.revert-part', selectedPart.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            modals.value.revert.show = false;
            selectedPart.value = null;
        },
    });
};

const openImage = (attachment) => {
    selectedAttachment.value = attachment;
    modals.value.image.show = true;
};

const closeConfirmation = () => {
    if (!confirmation.value.processing) confirmation.value = { action: null, partId: null, title: '', message: '', processing: false };
};

const confirmAction = () => {
    confirmation.value.processing = true;
    const options = {
        preserveScroll: true,
        onFinish: () => {
            confirmation.value.processing = false;
            closeConfirmation();
        },
    };

    if (confirmation.value.action === 'remove-part') {
        router.delete(route('repairs.remove-part', confirmation.value.partId), options);
    } else if (confirmation.value.action === 'commit-parts') {
        router.post(route('repairs.commit-parts', props.order.id), {}, options);
    }
};
</script>

<style scoped>
.repair-payment-scroll {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
    -webkit-overflow-scrolling: touch;
}

.repair-payment-scroll::-webkit-scrollbar {
    width: 6px;
}

.repair-payment-scroll::-webkit-scrollbar-track {
    background: transparent;
}

.repair-payment-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 9999px;
}

.repair-payment-scroll::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

:global(.dark) .repair-payment-scroll {
    scrollbar-color: #475569 transparent;
}

:global(.dark) .repair-payment-scroll::-webkit-scrollbar-thumb {
    background: #475569;
}
</style>
