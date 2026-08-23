<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { usePrint } from '@/composables/usePrint';

const { printPage } = usePrint();
const props = defineProps({ returnData: { type: Object, required: true } });
const data = props.returnData;
const money = (v) => `${Number(v || 0).toLocaleString('ar', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} شيكل`;
const date = (v) => v ? new Intl.DateTimeFormat('ar-PS', { dateStyle: 'medium' }).format(new Date(v)) : '—';
</script>
<template>
    <Head :title="`مرتجع مشتريات ${data.return_number}`" />
    <main dir="rtl" class="min-h-screen bg-slate-100 p-4 text-slate-900 sm:p-8 print:bg-white print:p-0">
        <article class="mx-auto max-w-4xl overflow-hidden rounded-3xl bg-white shadow-xl print:max-w-none print:rounded-none print:shadow-none">
            <header class="bg-gradient-to-l from-indigo-700 to-blue-600 p-7 text-white">
                <div class="flex items-start justify-between gap-6"><div><h1 class="text-2xl font-black">فنانة فون</h1><p class="mt-1 text-sm text-indigo-100">إدارة معرض الهواتف والصيانة</p></div><div class="text-left"><p class="text-sm text-indigo-100">إشعار مرتجع مشتريات</p><p class="mt-1 text-xl font-bold">{{ data.return_number }}</p><p class="mt-1 text-xs text-indigo-100">{{ date(data.return_date) }}</p></div></div>
            </header>
            <div class="space-y-6 p-7">
                <section class="grid gap-4 rounded-2xl bg-slate-50 p-5 sm:grid-cols-2"><div><p class="text-xs text-slate-500">المورد</p><p class="mt-1 font-bold">{{ data.supplier?.company_name || data.supplier?.name }}</p><p class="mt-1 text-sm text-slate-500">{{ data.supplier?.phone }}</p></div><div><p class="text-xs text-slate-500">فاتورة الشراء الأصلية</p><p class="mt-1 font-bold">{{ data.invoice?.invoice_number }}</p><p class="mt-1 text-sm text-slate-500">{{ date(data.invoice?.purchase_date) }}</p></div></section>
                <section><h2 class="font-bold">المنتجات المرتجعة</h2><div class="mt-3 overflow-hidden rounded-2xl border border-slate-200"><table class="w-full text-sm"><thead class="bg-slate-50 text-slate-500"><tr><th class="px-4 py-3 text-right">المنتج</th><th class="px-4 py-3 text-center">الكمية</th><th class="px-4 py-3 text-left">تكلفة الوحدة</th><th class="px-4 py-3 text-left">الإجمالي</th></tr></thead><tbody class="divide-y divide-slate-100"><tr v-for="item in data.items" :key="item.id"><td class="px-4 py-3 font-semibold">{{ item.product?.name }}<span class="block text-xs font-normal text-slate-400">{{ item.product?.code }}</span></td><td class="px-4 py-3 text-center">{{ item.quantity }}</td><td class="px-4 py-3 text-left">{{ money(item.unit_cost) }}</td><td class="px-4 py-3 text-left font-bold">{{ money(item.total_amount) }}</td></tr></tbody></table></div></section>
                <section class="mr-auto w-full max-w-sm rounded-2xl border border-slate-200 p-5"><div class="flex justify-between"><span class="text-slate-500">إجمالي المرتجع</span><strong>{{ money(data.total_amount) }}</strong></div><div v-if="Number(data.amount_used_for_debt)" class="mt-3 flex justify-between"><span class="text-slate-500">تخفيض مستحق المورد</span><strong class="text-amber-600">{{ money(data.amount_used_for_debt) }}</strong></div><div v-if="Number(data.amount_refunded)" class="mt-3 flex justify-between"><span class="text-slate-500">مبلغ مسترد</span><strong class="text-emerald-600">{{ money(data.amount_refunded) }}</strong></div></section>
                <section v-if="data.reason || data.notes" class="rounded-2xl border border-slate-200 p-5 text-sm"><p><strong>السبب:</strong> {{ data.reason }}</p><p v-if="data.notes" class="mt-2"><strong>ملاحظات:</strong> {{ data.notes }}</p></section>
                <footer class="border-t border-slate-200 pt-5 text-center text-xs text-slate-500">هذا المستند صادر إلكترونياً من نظام فنانة فون.</footer>
                <div class="flex justify-center gap-3 print:hidden"><button @click="printPage" class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white">طباعة</button><Link :href="route('returns.index')" class="rounded-xl border border-slate-300 px-6 py-3 font-semibold text-slate-700">عودة</Link></div>
            </div>
        </article>
    </main>
</template>
