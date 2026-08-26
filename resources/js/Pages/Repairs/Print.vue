<script setup>
import { onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({ order: { type: Object, required: true } });
const money = value => `${Number(value || 0).toLocaleString('ar-PS', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} شيكل`;
const date = value => value ? new Intl.DateTimeFormat('ar-PS', { dateStyle: 'medium' }).format(new Date(value)) : '—';
onMounted(() => setTimeout(() => window.print(), 250));
</script>

<template>
    <Head :title="`صيانة ${order.order_number}`" />
    <main dir="rtl" class="mx-auto max-w-4xl bg-white p-8 text-slate-900 print:max-w-none print:p-0">
        <header class="flex items-start justify-between border-b-2 border-slate-900 pb-5">
            <div><h1 class="text-2xl font-black">فنانة فون</h1><p class="mt-1 text-sm text-slate-500">إيصال / تفاصيل طلب صيانة</p></div>
            <div class="text-left"><strong dir="ltr" class="text-lg">{{ order.order_number }}</strong><p class="mt-1 text-xs text-slate-500">{{ date(order.received_at) }}</p></div>
        </header>

        <section class="mt-6 grid grid-cols-2 gap-5 text-sm">
            <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">العميل</p><strong class="mt-1 block">{{ order.customer_name }}</strong><p dir="ltr" class="mt-1 text-right">{{ order.customer_phone }}</p></div>
            <div class="rounded-xl border border-slate-200 p-4"><p class="text-xs text-slate-500">الجهاز</p><strong class="mt-1 block">{{ order.brand }} {{ order.model }}</strong><p class="mt-1">{{ order.device_type }} · {{ order.color || '—' }}</p></div>
        </section>

        <section class="mt-5 grid gap-4 text-sm md:grid-cols-3">
            <div><p class="font-black">المشكلة</p><p class="mt-2 leading-6">{{ order.problem_description }}</p></div>
            <div><p class="font-black">التشخيص</p><p class="mt-2 leading-6">{{ order.inspection_result }}</p></div>
            <div><p class="font-black">الإصلاح</p><p class="mt-2 leading-6">{{ order.repair_action }}</p></div>
        </section>

        <section class="mt-6">
            <h2 class="font-black">قطع الغيار</h2>
            <table class="mt-3 w-full border-collapse text-sm">
                <thead><tr class="bg-slate-100"><th class="border p-2 text-right">القطعة</th><th class="border p-2">المصدر</th><th class="border p-2">الكمية</th></tr></thead>
                <tbody>
                    <tr v-for="part in (order.parts || []).filter(p => p.is_committed)" :key="`s-${part.id}`"><td class="border p-2">{{ part.product_name }}</td><td class="border p-2 text-center">مخزن المحل</td><td class="border p-2 text-center">{{ part.quantity }}</td></tr>
                    <tr v-for="part in (order.external_parts || []).filter(p => p.status === 'purchased')" :key="`e-${part.id}`"><td class="border p-2">{{ part.part_name }}</td><td class="border p-2 text-center">خارجي — {{ part.purchase_from }}</td><td class="border p-2 text-center">{{ part.quantity }}</td></tr>
                    <tr v-if="!(order.parts || []).some(p => p.is_committed) && !(order.external_parts || []).some(p => p.status === 'purchased')"><td colspan="3" class="border p-4 text-center text-slate-500">لا توجد قطع غيار</td></tr>
                </tbody>
            </table>
        </section>

        <section class="mt-6 grid grid-cols-3 gap-3 text-sm">
            <div class="rounded-xl bg-slate-100 p-4"><p class="text-xs text-slate-500">الإجمالي</p><strong class="mt-1 block">{{ money(order.total_amount) }}</strong></div>
            <div class="rounded-xl bg-slate-100 p-4"><p class="text-xs text-slate-500">المدفوع</p><strong class="mt-1 block">{{ money(order.paid_amount) }}</strong></div>
            <div class="rounded-xl bg-slate-100 p-4"><p class="text-xs text-slate-500">المتبقي</p><strong class="mt-1 block">{{ money(order.remaining_amount) }}</strong></div>
        </section>

        <footer class="mt-10 border-t pt-5 text-center text-xs text-slate-500">شكراً لتعاملكم مع فنانة فون</footer>
    </main>
</template>
