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
                    <p class="text-sm font-bold text-gray-900">إشعار مرتجع مبيعات</p>
                    <p class="text-sm text-gray-600">{{ printData.return_number }}</p>
                    <p class="text-sm text-gray-500">التاريخ: {{ formatDate(printData.return_date) }}</p>
                </div>
            </div>

            <!-- معلومات العميل والفاتورة -->
            <div class="mt-6 grid grid-cols-2 gap-6">
                <div>
                    <h3 class="text-sm font-bold text-gray-700">العميل</h3>
                    <p class="text-sm text-gray-600">{{ printData.customer?.name }}</p>
                    <p class="text-sm text-gray-600">{{ printData.customer?.phone }}</p>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-700">الفاتورة الأصلية</h3>
                    <p class="text-sm text-gray-600">{{ printData.invoice?.invoice_number }}</p>
                    <p class="text-sm text-gray-600">التاريخ: {{ formatDate(printData.invoice?.sale_date) }}</p>
                </div>
            </div>

            <!-- سبب المرتجع -->
            <div class="mt-4 border-t pt-4">
                <h3 class="text-sm font-bold text-gray-700">سبب المرتجع</h3>
                <p class="text-sm text-gray-600">{{ printData.reason }}</p>
            </div>

            <!-- المنتجات -->
            <div class="mt-4 border-t pt-4">
                <h3 class="text-sm font-bold text-gray-700">المنتجات المرتجعة</h3>
                <table class="mt-2 w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-right py-1">المنتج</th>
                            <th class="text-center py-1">الكمية</th>
                            <th class="text-left py-1">سعر الوحدة</th>
                            <th class="text-left py-1">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in printData.items" :key="item.id" class="border-b">
                            <td class="py-1">
                                {{ item.product?.name }}
                                <span class="block text-xs text-gray-400">{{ item.product?.code }}</span>
                            </td>
                            <td class="text-center py-1">{{ item.quantity }}</td>
                            <td class="py-1">{{ formatCurrency(item.unit_price) }}</td>
                            <td class="py-1">{{ formatCurrency(item.total_amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- التسوية المالية -->
            <div class="mt-4 border-t pt-4">
                <div class="flex justify-between text-sm">
                    <span>إجمالي قيمة المرتجع</span>
                    <span class="font-bold">{{ formatCurrency(printData.total_amount) }}</span>
                </div>
                <div v-if="printData.amount_used_for_debt > 0" class="flex justify-between text-sm">
                    <span>تم تخفيض الدين</span>
                    <span class="text-orange-600">-{{ formatCurrency(printData.amount_used_for_debt) }}</span>
                </div>
                <div v-if="printData.amount_refunded > 0" class="flex justify-between text-sm">
                    <span>مبلغ مسترد للعميل</span>
                    <span class="text-green-600">{{ formatCurrency(printData.amount_refunded) }}</span>
                </div>
                <div class="flex justify-between text-sm border-t pt-2 mt-2">
                    <span class="font-bold">صافي التسوية</span>
                    <span class="font-bold text-gray-900">
                        {{ printData.amount_refunded > 0 ? 'مسترد' : 'مخصوم من الدين' }}
                        ({{ formatCurrency(printData.amount_refunded || printData.amount_used_for_debt) }})
                    </span>
                </div>
            </div>

            <!-- ملاحظات -->
            <div v-if="printData.notes" class="mt-4 border-t pt-4 text-sm">
                <p class="font-bold">ملاحظات</p>
                <p>{{ printData.notes }}</p>
            </div>

            <!-- تذييل -->
            <div class="mt-6 border-t pt-4 text-center text-sm text-gray-500">
                <p>شكراً لثقتكم بفنانة فون</p>
                <p>هذا الإشعار صادر آلياً ويعتبر سنداً قانونياً</p>
            </div>

            <!-- زر الطباعة -->
            <div class="mt-6 text-center print:hidden">
                <button
                    @click="printPage"
                    class="rounded-lg bg-blue-600 px-6 py-3 text-white hover:bg-blue-700"
                >
                    طباعة
                </button>
                <Link
                    :href="route('returns.index')"
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
    returnData: {
        type: Object,
        required: true,
    },
});

// استخدام اسم مختلف تماماً
const printData = props.returnData;

const formatCurrency = (value) => {
    return Number(value || 0).toFixed(2) + ' شيكل';
};

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('ar-EG');
};
</script>

<style scoped>
@media print {
    body {
        background: white !important;
    }
    .print\:bg-white {
        background: white !important;
    }
    .print\:shadow-none {
        box-shadow: none !important;
    }
    .print\:p-4 {
        padding: 1rem !important;
    }
    .print\:hidden {
        display: none !important;
    }
}
</style>
