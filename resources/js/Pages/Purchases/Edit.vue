<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PurchaseInvoiceForm from '@/Components/Purchases/PurchaseInvoiceForm.vue';

defineProps({
    invoice: { type: Object, required: true },
    suppliers: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    paymentMethods: { type: Object, default: () => ({}) },
    financialAccounts: { type: Array, default: () => [] },
});
</script>

<template>
    <Head :title="`تعديل ${invoice.invoice_number}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-black text-amber-700 dark:bg-amber-950/30 dark:text-amber-300">
                            مسودة قابلة للتعديل
                        </span>

                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-black text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                            {{ invoice.invoice_number }}
                        </span>
                    </div>

                    <h1 class="mt-3 text-2xl font-black text-slate-950 sm:text-3xl dark:text-white">
                        تعديل فاتورة الشراء
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        يمكنك تعديل البنود أو إضافة منتج جديد من نفس الشاشة ما دامت الفاتورة مسودة.
                    </p>
                </div>

                <Link
                    :href="route('purchases.show', invoice.id)"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                >
                    العودة للتفاصيل
                </Link>
            </div>
        </template>

        <PurchaseInvoiceForm
            mode="edit"
            :invoice="invoice"
            :suppliers="suppliers"
            :warehouses="warehouses"
            :products="products"
            :categories="categories"
            :payment-methods="paymentMethods"
            :financial-accounts="financialAccounts"
        />
    </AuthenticatedLayout>
</template>
