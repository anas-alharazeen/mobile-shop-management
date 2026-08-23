<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { usePrint } from '@/composables/usePrint';


const { printPage } = usePrint();
const props = defineProps({ exchange: { type: Object, required: true } });
const data = props.exchange;
const money = (v) => `${Number(v || 0).toLocaleString('ar', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} شيكل`;
const date = (v) => v ? new Intl.DateTimeFormat('ar-PS', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(v)) : '—';
const settlementLabel = (v) => ({ customer_pays: 'العميل يدفع الفرق', customer_gets_refund: 'يُرد الفرق للعميل', no_difference: 'لا يوجد فرق' }[v] ?? v);
</script>
<template>
    <Head :title="`مستند استبدال ${data.exchange_number}`" />
    <main dir="rtl" class="min-h-screen bg-slate-100 p-4 text-slate-900 sm:p-8 print:bg-white print:p-0">
        <article class="mx-auto max-w-4xl overflow-hidden rounded-3xl bg-white shadow-xl print:max-w-none print:rounded-none print:shadow-none">
            <header class="bg-gradient-to-l from-violet-700 to-indigo-600 p-7 text-white"><div class="flex items-start justify-between"><div><h1 class="text-2xl font-black">فنانة فون</h1><p class="mt-1 text-sm text-violet-100">مستند استبدال منتجات</p></div><div class="text-left"><p class="text-xl font-bold">{{ data.exchange_number }}</p><p class="mt-1 text-xs text-violet-100">{{ date(data.exchanged_at) }}</p></div></div></header>
            <div class="space-y-6 p-7">
                <section class="grid gap-4 md:grid-cols-[1fr_auto_1fr] md:items-stretch">
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 p-5"><p class="text-xs font-semibold text-rose-600">المنتجات المرتجعة</p><p class="mt-2 text-2xl font-black text-rose-700">{{ money(data.return_value) }}</p><p class="mt-2 text-sm text-rose-700/70">مرتجع رقم {{ data.sales_return?.return_number || '—' }}</p></div>
                    <div class="flex items-center justify-center text-slate-400"><svg class="h-8 w-8 rotate-180 md:rotate-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/></svg></div>
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5"><p class="text-xs font-semibold text-indigo-600">المنتجات البديلة</p><p class="mt-2 text-2xl font-black text-indigo-700">{{ money(data.new_items_value) }}</p><p class="mt-2 text-sm text-indigo-700/70">فاتورة رقم {{ data.new_invoice?.invoice_number || '—' }}</p></div>
                </section>
                <section class="rounded-3xl border border-slate-200 p-6"><div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-sm text-slate-500">طريقة التسوية</p><p class="mt-1 text-xl font-bold">{{ settlementLabel(data.settlement_type) }}</p></div><div class="text-left"><p class="text-sm text-slate-500">فرق السعر</p><p class="mt-1 text-3xl font-black" :class="Number(data.price_difference) > 0 ? 'text-amber-600' : Number(data.price_difference) < 0 ? 'text-emerald-600' : 'text-slate-700'">{{ money(Math.abs(Number(data.price_difference || 0))) }}</p></div></div></section>
                <section v-if="data.notes" class="rounded-2xl bg-slate-50 p-5 text-sm"><strong>ملاحظات:</strong> {{ data.notes }}</section>
                <footer class="border-t border-slate-200 pt-5 text-center text-xs text-slate-500">يربط هذا المستند بين المرتجع وفاتورة البيع الجديدة ضمن عملية استبدال واحدة.</footer>
                <div class="flex justify-center gap-3 print:hidden"><button @click="printPage" class="rounded-xl bg-indigo-600 px-6 py-3 font-semibold text-white">طباعة</button><Link :href="route('returns.index')" class="rounded-xl border border-slate-300 px-6 py-3 font-semibold text-slate-700">عودة</Link></div>
            </div>
        </article>
    </main>
</template>
