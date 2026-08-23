<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    invoice: { type: Object, required: true },
    customers: { type: Array, default: () => [] },
    products: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    paymentMethods: { type: Object, default: () => ({}) },
});

const search = ref('');
const categoryId = ref('');
const normalizeDate = (value) => value ? String(value).slice(0, 10) : new Date().toISOString().slice(0, 10);
const form = useForm({
    customer_id: props.invoice.customer_id || '',
    customer_name: props.invoice.customer_name || '',
    customer_phone: props.invoice.customer_phone || '',
    save_customer: false,
    sale_date: normalizeDate(props.invoice.sale_date),
    due_date: props.invoice.due_date ? normalizeDate(props.invoice.due_date) : '',
    invoice_discount: Number(props.invoice.invoice_discount || 0),
    notes: props.invoice.notes || '',
    items: (props.invoice.items || []).map((item) => ({
        product_id: item.product_id,
        product_name: item.product_name || item.product?.name,
        product_code: item.product_code || item.product?.code,
        quantity: Number(item.quantity || 1),
        max_quantity: Number(item.product?.available_quantity ?? item.product?.sales_stock?.quantity ?? 999999),
        unit_selling_price: Number(item.unit_selling_price || 0),
        line_discount: Number(item.line_discount || 0),
    })),
});

const filteredProducts = computed(() => props.products.filter((product) => {
    const term = search.value.trim().toLowerCase();
    const matchesSearch = !term || [product.name, product.code, product.barcode, product.brand].some((v) => String(v || '').toLowerCase().includes(term));
    const matchesCategory = !categoryId.value || String(product.category_id) === String(categoryId.value);
    return matchesSearch && matchesCategory;
}));
const subtotal = computed(() => form.items.reduce((sum, item) => sum + Number(item.quantity || 0) * Number(item.unit_selling_price || 0), 0));
const itemDiscounts = computed(() => form.items.reduce((sum, item) => sum + Number(item.line_discount || 0), 0));
const total = computed(() => Math.max(0, subtotal.value - itemDiscounts.value - Number(form.invoice_discount || 0)));
const estimatedCost = computed(() => form.items.reduce((sum, item) => {
    const product = props.products.find((p) => Number(p.id) === Number(item.product_id));
    return sum + Number(item.quantity || 0) * Number(product?.purchase_price || 0);
}, 0));
const profit = computed(() => total.value - estimatedCost.value);
const money = (v) => `${Number(v || 0).toLocaleString('ar', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} شيكل`;

const addProduct = (product) => {
    const existing = form.items.find((item) => Number(item.product_id) === Number(product.id));
    if (existing) {
        if (existing.quantity < Number(product.available_quantity || 0)) existing.quantity += 1;
        return;
    }
    form.items.push({
        product_id: product.id,
        product_name: product.name,
        product_code: product.code,
        quantity: 1,
        max_quantity: Number(product.available_quantity || 0),
        unit_selling_price: Number(product.selling_price || 0),
        line_discount: 0,
    });
};
const removeItem = (index) => form.items.splice(index, 1);
const selectCustomer = () => {
    const customer = props.customers.find((item) => Number(item.id) === Number(form.customer_id));
    if (customer) { form.customer_name = customer.name; form.customer_phone = customer.phone || ''; }
};
const submit = () => form.put(route('sales.update', props.invoice.id), { preserveScroll: true });
</script>

<template>
    <Head :title="`تعديل ${invoice.invoice_number}`" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">فواتير البيع</p><h1 class="mt-1 text-2xl font-bold text-slate-950 dark:text-white">تعديل الفاتورة {{ invoice.invoice_number }}</h1><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">يمكن تعديل المسودة فقط قبل اعتمادها وخصم المخزون.</p></div>
                <Link :href="route('sales.show', invoice.id)" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-indigo-300 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">إلغاء والعودة</Link>
            </div>
        </template>

        <form class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_370px]" @submit.prevent="submit">
            <div class="space-y-6">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex items-center justify-between"><div><h2 class="font-bold text-slate-950 dark:text-white">العميل والفاتورة</h2><p class="mt-1 text-xs text-slate-500">حدّث بيانات العميل وتاريخ البيع.</p></div><span class="rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">مسودة</span></div>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <div><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">عميل مسجل</label><select v-model="form.customer_id" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" @change="selectCustomer"><option value="">عميل سريع</option><option v-for="customer in customers" :key="customer.id" :value="customer.id">{{ customer.name }} — {{ customer.code }}</option></select></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">تاريخ البيع</label><input v-model="form.sale_date" type="date" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" /></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">تاريخ استحقاق المتبقي</label><input v-model="form.due_date" type="date" :min="form.sale_date" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" /><p class="mt-1 text-xs text-slate-500">اختياري للفاتورة المؤجلة أو الجزئية.</p></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">اسم العميل</label><input v-model="form.customer_name" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" /><p v-if="form.errors.customer_name" class="mt-1 text-xs text-rose-600">{{ form.errors.customer_name }}</p></div>
                        <div><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">رقم الهاتف</label><input v-model="form.customer_phone" type="text" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" /></div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-200 p-5 dark:border-slate-800"><div class="flex flex-col gap-3 md:flex-row md:items-end"><div class="flex-1"><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">البحث عن منتج</label><input v-model="search" type="search" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white" placeholder="الاسم، الكود أو الباركود" /></div><div class="md:w-56"><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">الفئة</label><select v-model="categoryId" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"><option value="">كل الفئات</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></div></div>
                        <div class="mt-4 flex max-h-52 flex-wrap gap-2 overflow-y-auto"><button v-for="product in filteredProducts" :key="product.id" type="button" class="rounded-xl border border-slate-200 px-3 py-2 text-right transition hover:border-indigo-400 hover:bg-indigo-50 dark:border-slate-700 dark:hover:bg-indigo-500/10" @click="addProduct(product)"><span class="block text-sm font-semibold text-slate-900 dark:text-white">{{ product.name }}</span><span class="mt-0.5 block text-xs text-slate-500">{{ product.code }} · {{ product.available_quantity }} متوفر</span></button></div>
                    </div>
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800"><thead class="bg-slate-50 text-xs text-slate-500 dark:bg-slate-950/40"><tr><th class="px-4 py-3 text-right">المنتج</th><th class="px-4 py-3 text-center">الكمية</th><th class="px-4 py-3 text-left">سعر البيع</th><th class="px-4 py-3 text-left">الخصم</th><th class="px-4 py-3 text-left">الإجمالي</th><th class="w-12"></th></tr></thead><tbody class="divide-y divide-slate-100 dark:divide-slate-800"><tr v-if="!form.items.length"><td colspan="6" class="px-5 py-12 text-center text-slate-500">أضف منتجاً واحداً على الأقل.</td></tr><tr v-for="(item, index) in form.items" :key="`${item.product_id}-${index}`"><td class="px-4 py-3"><p class="font-semibold text-slate-900 dark:text-white">{{ item.product_name }}</p><p class="text-xs text-slate-500">{{ item.product_code }}</p></td><td class="px-4 py-3"><input v-model.number="item.quantity" type="number" min="1" :max="item.max_quantity || undefined" class="mx-auto block w-20 rounded-xl border-slate-300 text-center dark:border-slate-700 dark:bg-slate-950 dark:text-white" /></td><td class="px-4 py-3"><input v-model.number="item.unit_selling_price" type="number" min="0" step="0.01" class="mr-auto block w-28 rounded-xl border-slate-300 text-left dark:border-slate-700 dark:bg-slate-950 dark:text-white" /></td><td class="px-4 py-3"><input v-model.number="item.line_discount" type="number" min="0" step="0.01" class="mr-auto block w-24 rounded-xl border-slate-300 text-left dark:border-slate-700 dark:bg-slate-950 dark:text-white" /></td><td class="px-4 py-3 text-left font-bold text-slate-900 dark:text-white">{{ money(item.quantity * item.unit_selling_price - item.line_discount) }}</td><td class="px-3 py-3"><button type="button" class="rounded-lg p-2 text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-500/10" @click="removeItem(index)"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button></td></tr></tbody></table></div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">ملاحظات الفاتورة</label><textarea v-model="form.notes" rows="3" class="w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950 dark:text-white"></textarea></section>
            </div>

            <aside class="xl:sticky xl:top-6 xl:self-start">
                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900"><h2 class="font-bold text-slate-950 dark:text-white">ملخص الفاتورة</h2><div class="mt-5 space-y-3 text-sm"><div class="flex justify-between text-slate-500"><span>الإجمالي قبل الخصم</span><span class="font-semibold text-slate-900 dark:text-white">{{ money(subtotal) }}</span></div><div class="flex justify-between text-slate-500"><span>خصومات البنود</span><span class="font-semibold text-rose-600">-{{ money(itemDiscounts) }}</span></div><div class="flex items-center justify-between gap-4 text-slate-500"><span>خصم الفاتورة</span><input v-model.number="form.invoice_discount" type="number" min="0" step="0.01" class="w-28 rounded-xl border-slate-300 text-left dark:border-slate-700 dark:bg-slate-950 dark:text-white" /></div><div class="border-t border-slate-200 pt-3 dark:border-slate-800"><div class="flex justify-between"><span class="font-bold text-slate-900 dark:text-white">الإجمالي النهائي</span><span class="text-xl font-black text-indigo-600 dark:text-indigo-400">{{ money(total) }}</span></div><div class="mt-3 flex justify-between text-xs"><span class="text-slate-500">الربح المتوقع</span><span class="font-bold" :class="profit >= 0 ? 'text-emerald-600' : 'text-rose-600'">{{ money(profit) }}</span></div></div></div><button type="submit" :disabled="form.processing || !form.items.length" class="mt-6 w-full rounded-2xl bg-indigo-600 px-5 py-3 font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 disabled:opacity-50">{{ form.processing ? 'جارٍ الحفظ...' : 'حفظ التعديلات' }}</button><p v-if="form.hasErrors" class="mt-3 text-center text-xs text-rose-600">راجع الحقول المعلّمة ثم أعد المحاولة.</p></section>
            </aside>
        </form>
    </AuthenticatedLayout>
</template>
