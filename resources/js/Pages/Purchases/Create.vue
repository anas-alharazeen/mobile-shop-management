<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PurchaseInvoiceForm from '@/Components/Purchases/PurchaseInvoiceForm.vue';

defineProps({
    suppliers: { type: Array, default: () => [] },
    warehouses: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    paymentMethods: { type: Object, default: () => ({}) },
    financialAccounts: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="فاتورة شراء جديدة" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-black text-blue-700 dark:bg-blue-950/30 dark:text-blue-300">
                            المشتريات
                        </span>

                        <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-black text-violet-700 dark:bg-violet-950/30 dark:text-violet-300">
                            إضافة منتج أثناء الشراء
                        </span>
                    </div>

                    <h1 class="mt-3 text-2xl font-black text-slate-950 sm:text-3xl dark:text-white">
                        فاتورة شراء جديدة
                    </h1>

                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500 dark:text-slate-400">
                        أضف المنتجات الموجودة أو أنشئ أي منتج جديد مباشرة داخل الفاتورة. لا حاجة للرجوع إلى قسم المنتجات أولاً.
                    </p>
                </div>

                <Link
                    :href="route('purchases.index')"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-black text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                >
                    العودة للمشتريات
                </Link>
            </div>
        </template>

        <PurchaseInvoiceForm
            mode="create"
            :suppliers="suppliers"
            :warehouses="warehouses"
            :products="products"
            :categories="categories"
            :payment-methods="paymentMethods"
            :financial-accounts="financialAccounts"
        />
    </AuthenticatedLayout>
</template>
