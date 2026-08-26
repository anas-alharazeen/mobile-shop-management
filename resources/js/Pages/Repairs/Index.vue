<script setup>
import { reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const props = defineProps({
    orders: { type: Object, required: true },
    stats: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    customers: { type: Array, default: () => [] },
    statuses: { type: Object, default: () => ({}) },
});

const filters = reactive({
    search: props.filters.search || '',
    status: props.filters.status || '',
    payment_status: props.filters.payment_status || '',
    customer_id: props.filters.customer_id || '',
    waiting_part: Boolean(props.filters.waiting_part),
    overdue: Boolean(props.filters.overdue),
});

let timer;
const applyFilters = () => {
    router.get(route('repairs.index'), {
        search: filters.search || undefined,
        status: filters.status || undefined,
        payment_status: filters.payment_status || undefined,
        customer_id: filters.customer_id || undefined,
        waiting_part: filters.waiting_part ? 1 : undefined,
        overdue: filters.overdue ? 1 : undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
};
const searchLater = () => { clearTimeout(timer); timer = setTimeout(applyFilters, 350); };
const clearFilters = () => {
    Object.assign(filters, { search: '', status: '', payment_status: '', customer_id: '', waiting_part: false, overdue: false });
    applyFilters();
};

const statusMeta = status => ({
    received: ['مستلم', 'bg-blue-100 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300'],
    in_progress: ['قيد التنفيذ', 'bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300'],
    ready: ['جاهز للاستلام', 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'],
    delivered: ['تم التسليم', 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'],
    cancelled: ['ملغي', 'bg-rose-100 text-rose-700 dark:bg-rose-950/30 dark:text-rose-300'],
}[status] || [status || '—', 'bg-slate-100 text-slate-600']);

const paymentMeta = status => ({
    paid: ['مدفوع', 'text-emerald-600'],
    partially_paid: ['جزئي', 'text-amber-600'],
    partial: ['جزئي', 'text-amber-600'],
    unpaid: ['غير مدفوع', 'text-rose-600'],
}[status] || [status || '—', 'text-slate-500']);

const date = value => value ? new Intl.DateTimeFormat('ar-PS', { dateStyle: 'medium' }).format(new Date(value)) : '—';
const money = value => `${Number(value || 0).toLocaleString('ar-PS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} شيكل`;
</script>

<template>
    <Head title="الصيانة" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div><p class="text-xs font-black text-blue-600">REPAIRS</p><h1 class="mt-1 text-2xl font-black text-slate-950 dark:text-white">مركز الصيانة</h1><p class="mt-1 text-sm text-slate-500">نظام واحد بسيط من استلام الجهاز حتى الدفع والتسليم.</p></div>
                <Link :href="route('repairs.create')" class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-sm hover:bg-blue-700">+ طلب صيانة جديد</Link>
            </div>
        </template>

        <div class="space-y-5">
            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <article class="rounded-2xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/50 dark:bg-blue-950/20"><p class="text-xs font-bold text-blue-700">قيد العمل</p><strong class="mt-2 block text-2xl font-black text-blue-700">{{ stats.active }}</strong></article>
                <article class="rounded-2xl border border-orange-200 bg-orange-50 p-4 dark:border-orange-900/50 dark:bg-orange-950/20"><p class="text-xs font-bold text-orange-700">بانتظار قطعة</p><strong class="mt-2 block text-2xl font-black text-orange-700">{{ stats.waiting_parts }}</strong></article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/20"><p class="text-xs font-bold text-emerald-700">جاهز للاستلام</p><strong class="mt-2 block text-2xl font-black text-emerald-700">{{ stats.ready }}</strong></article>
                <article class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-900"><p class="text-xs font-bold text-slate-500">تم التسليم اليوم</p><strong class="mt-2 block text-2xl font-black dark:text-white">{{ stats.delivered_today }}</strong></article>
                <article class="rounded-2xl bg-slate-950 p-4 text-white"><p class="text-xs font-bold text-slate-300">مستحقات الصيانة</p><strong class="mt-2 block text-xl font-black">{{ money(stats.remaining) }}</strong></article>
            </section>

            <section class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="grid gap-3 lg:grid-cols-[1.5fr_180px_180px_220px_auto]">
                    <input v-model="filters.search" type="search" placeholder="رقم الطلب، العميل، الهاتف، الجهاز..." class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" @input="searchLater" />
                    <select v-model="filters.status" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" @change="applyFilters"><option value="">كل الحالات</option><option v-for="(label, value) in statuses" :key="value" :value="value">{{ label }}</option></select>
                    <select v-model="filters.payment_status" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" @change="applyFilters"><option value="">كل حالات الدفع</option><option value="unpaid">غير مدفوع</option><option value="partially_paid">مدفوع جزئياً</option><option value="paid">مدفوع</option></select>
                    <select v-model="filters.customer_id" class="rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" @change="applyFilters"><option value="">كل العملاء</option><option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }}</option></select>
                    <button type="button" class="rounded-xl border border-slate-300 px-4 text-sm font-black text-slate-600 dark:border-slate-700 dark:text-slate-300" @click="clearFilters">مسح</button>
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-orange-50 px-3 py-2 text-xs font-black text-orange-700 dark:bg-orange-950/20"><input v-model="filters.waiting_part" type="checkbox" class="rounded text-orange-600" @change="applyFilters" /> بانتظار قطعة خارجية</label>
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl bg-rose-50 px-3 py-2 text-xs font-black text-rose-700 dark:bg-rose-950/20"><input v-model="filters.overdue" type="checkbox" class="rounded text-rose-600" @change="applyFilters" /> متأخر</label>
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="min-w-[1100px] w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
                        <thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-950/40"><tr><th class="px-4 py-3 text-right">الطلب</th><th class="px-4 py-3 text-right">العميل والجهاز</th><th class="px-4 py-3 text-left">السعر</th><th class="px-4 py-3 text-center">القطع</th><th class="px-4 py-3 text-center">الدفع</th><th class="px-4 py-3 text-center">الحالة</th><th class="px-4 py-3 text-center">عرض</th></tr></thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40">
                                <td class="px-4 py-4"><strong class="block dark:text-white">{{ order.order_number }}</strong><span class="mt-1 block text-xs text-slate-400">{{ date(order.received_at) }}</span></td>
                                <td class="px-4 py-4"><strong class="block dark:text-white">{{ order.customer_name }}</strong><span class="mt-1 block text-xs text-slate-500">{{ order.brand }} {{ order.model }} · {{ order.customer_phone }}</span></td>
                                <td dir="ltr" class="px-4 py-4 text-left"><strong class="text-base dark:text-white">{{ money(order.agreed_price ?? order.total_amount) }}</strong><span v-if="Number(order.remaining_amount || 0) > 0" class="mt-1 block text-xs text-rose-600">متبقي {{ money(order.remaining_amount) }}</span></td>
                                <td class="px-4 py-4 text-center"><span v-if="order.sub_status === 'waiting_part'" class="rounded-full bg-orange-100 px-2.5 py-1 text-[10px] font-black text-orange-700">بانتظار قطعة</span><span v-else class="text-xs text-slate-400">جاهزة</span></td>
                                <td class="px-4 py-4 text-center"><strong :class="paymentMeta(order.payment_status)[1]">{{ paymentMeta(order.payment_status)[0] }}</strong></td>
                                <td class="px-4 py-4 text-center"><span class="rounded-full px-2.5 py-1 text-[10px] font-black" :class="statusMeta(order.status)[1]">{{ statusMeta(order.status)[0] }}</span></td>
                                <td class="px-4 py-4 text-center"><Link :href="route('repairs.show', order.id)" class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-black text-blue-700 hover:bg-blue-100 dark:bg-blue-950/20 dark:text-blue-300">فتح</Link></td>
                            </tr>
                            <tr v-if="!orders.data.length"><td colspan="7" class="px-6 py-16 text-center text-sm text-slate-500">لا توجد طلبات مطابقة.</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 p-4 dark:border-slate-700"><p class="text-xs text-slate-500">{{ orders.total }} طلب</p><Pagination :links="orders.links" /></div>
            </section>
        </div>
    </AuthenticatedLayout>
</template>
