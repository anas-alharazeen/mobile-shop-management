<template>
    <div class="min-h-screen bg-gray-100 p-8 print:bg-white print:p-4">
        <div class="mx-auto max-w-4xl rounded-2xl bg-white p-8 shadow-lg print:shadow-none">
            <!-- رأس الفاتورة -->
            <div class="flex items-center justify-between border-b pb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">فنانة فون</h1>
                    <p class="text-sm text-gray-500">إدارة معرض الهواتف</p>
                    <p class="text-sm text-gray-500">هاتف: 0599-123456</p>
                </div>
                <div class="text-left">
                    <p class="text-sm font-bold text-gray-900">فاتورة صيانة</p>
                    <p class="text-sm text-gray-600">{{ order.order_number }}</p>
                    <p class="text-sm text-gray-500">التاريخ: {{ formatDate(order.received_at) }}</p>
                </div>
            </div>

            <!-- بيانات العميل والجهاز -->
            <div class="mt-6 grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-bold text-gray-700">بيانات العميل</h3>
                    <p class="text-sm text-gray-600">{{ order.customer_name }}</p>
                    <p class="text-sm text-gray-600">{{ order.customer_phone }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-700">بيانات الجهاز</h3>
                    <p class="text-sm text-gray-600">{{ order.brand }} {{ order.model }}</p>
                    <p class="text-sm text-gray-600">{{ order.device_type }}</p>
                    <p class="text-sm text-gray-600" v-if="order.color">اللون: {{ order.color }}</p>
                </div>
            </div>

            <!-- المشكلة والإصلاح -->
            <div class="mt-4 border-t pt-4">
                <h3 class="text-sm font-bold text-gray-700">المشكلة</h3>
                <p class="text-sm text-gray-600">{{ order.problem_description }}</p>
                <h3 class="mt-2 text-sm font-bold text-gray-700">الإجراء المنفذ</h3>
                <p class="text-sm text-gray-600">{{ order.repair_action || '-' }}</p>
            </div>

            <!-- قطع الغيار -->
            <div v-if="order.parts && order.parts.length > 0" class="mt-4 border-t pt-4">
                <h3 class="text-sm font-bold text-gray-700">قطع الغيار المستخدمة</h3>
                <table class="mt-2 w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-right py-1">القطعة</th>
                            <th class="text-center py-1">الكمية</th>
                            <th class="text-left py-1">السعر</th>
                            <th class="text-left py-1">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="part in order.parts" :key="part.id" class="border-b">
                            <td class="py-1">{{ part.product_name }}</td>
                            <td class="text-center py-1">{{ part.quantity }}</td>
                            <td class="py-1">{{ formatCurrency(part.unit_price) }}</td>
                            <td class="py-1">{{ formatCurrency(part.total_price) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- التكاليف -->
            <div class="mt-4 border-t pt-4">
                <div class="flex justify-between text-sm">
                    <span>تكلفة الفحص</span>
                    <span>{{ formatCurrency(order.inspection_fee) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>أجرة الصيانة</span>
                    <span>{{ formatCurrency(order.labor_cost) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span>قطع الغيار</span>
                    <span>{{ formatCurrency(order.parts_cost) }}</span>
                </div>
                <div class="flex justify-between text-lg font-bold border-t pt-2 mt-2">
                    <span>الإجمالي</span>
                    <span>{{ formatCurrency(order.total_amount) }}</span>
                </div>
                <div class="flex justify-between text-sm mt-2">
                    <span>المدفوع</span>
                    <span>{{ formatCurrency(order.paid_amount) }}</span>
                </div>
                <div class="flex justify-between text-sm font-bold text-red-600">
                    <span>المتبقي</span>
                    <span>{{ formatCurrency(order.remaining_amount) }}</span>
                </div>
            </div>

            <!-- ملاحظات -->
            <div v-if="order.customer_notes" class="mt-4 border-t pt-4 text-sm">
                <p class="font-bold">ملاحظات</p>
                <p>{{ order.customer_notes }}</p>
            </div>

            <!-- التذييل -->
            <div class="mt-6 border-t pt-4 text-center text-sm text-gray-500">
                <p>شكراً لثقتكم بفنانة فون</p>
                <p>هذه الفاتورة صادرة آلياً وتعتبر سنداً قانونياً</p>
            </div>

            <!-- زر الطباعة -->
            <div class="mt-6 text-center print:hidden">
                <button
    type="button"
    @click="printPage"
    class="rounded-lg bg-blue-600 px-6 py-3 text-white transition hover:bg-blue-700"
>
    طباعة الفاتورة
</button>
                <Link
                    :href="route('repairs.show', order.id)"
                    class="mr-3 rounded-lg border border-gray-300 px-6 py-3 text-gray-700 hover:bg-gray-50"
                >
                    العودة
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { usePrint } from '@/composables/usePrint';

const { printPage } = usePrint();

const props = defineProps({
    order: Object,
});

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-EG');
};
</script>

<style>
@page {
    size: A4;
    margin: 10mm;
}

@media print {
    html,
    body {
        margin: 0 !important;
        padding: 0 !important;
        background: #ffffff !important;
    }

    body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .print\:bg-white {
        background: #ffffff !important;
    }

    .print\:shadow-none {
        box-shadow: none !important;
    }

    .print\:p-4 {
        padding: 0 !important;
    }

    .print\:hidden {
        display: none !important;
    }

    a {
        text-decoration: none !important;
    }

    a[href]::after {
        content: none !important;
    }
}
</style>
