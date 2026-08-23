<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-primary-600 dark:text-primary-400">إدارة النظام</p>
                    <h1 class="mt-1 text-2xl font-bold text-slate-950 dark:text-white">إعدادات فنانة فون</h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">بيانات المعرض، الطباعة، طرق الدفع، وحالة النسخ الاحتياطي.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                        النظام يعمل
                    </span>
                    <Link :href="route('dashboard')" class="app-secondary-button">العودة للوحة التحكم</Link>
                </div>
            </div>
        </template>

        <div class="grid gap-6 xl:grid-cols-[260px_minmax(0,1fr)]">
            <aside class="xl:sticky xl:top-24 xl:self-start">
                <nav class="rounded-2xl border border-slate-200/80 bg-white p-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        class="group flex w-full items-center gap-3 rounded-xl px-3 py-3 text-right text-sm font-semibold transition"
                        :class="activeTab === tab.key
                            ? 'bg-primary-50 text-primary-700 dark:bg-primary-950/50 dark:text-primary-300'
                            : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white'"
                        @click="activeTab = tab.key"
                    >
                        <span class="grid h-9 w-9 place-items-center rounded-lg border border-current/10 bg-white/70 dark:bg-slate-950/30">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="tab.path" />
                            </svg>
                        </span>
                        <span>{{ tab.label }}</span>
                    </button>
                </nav>
            </aside>

            <form class="space-y-6" @submit.prevent="submit">
                <section v-if="activeTab === 'general'" class="settings-card">
                    <SectionHeading title="بيانات المعرض" description="تظهر هذه البيانات في الفواتير والتقارير وصفحات الطباعة." />
                    <div class="grid gap-5 md:grid-cols-2">
                        <Field label="اسم المعرض" :error="form.errors.store_name">
                            <input v-model="form.store_name" class="app-input" type="text" autocomplete="organization" />
                        </Field>
                        <Field label="البريد الإلكتروني" :error="form.errors.store_email">
                            <input v-model="form.store_email" class="app-input" type="email" autocomplete="email" />
                        </Field>
                        <Field label="رقم الهاتف" :error="form.errors.store_phone">
                            <input v-model="form.store_phone" class="app-input" type="text" dir="ltr" autocomplete="tel" />
                        </Field>
                        <Field label="رقم واتساب" :error="form.errors.store_whatsapp">
                            <input v-model="form.store_whatsapp" class="app-input" type="text" dir="ltr" autocomplete="tel" />
                        </Field>
                        <Field class="md:col-span-2" label="العنوان" :error="form.errors.store_address">
                            <textarea v-model="form.store_address" class="app-input min-h-24" rows="3" />
                        </Field>
                    </div>

                    <div class="mt-6 rounded-2xl border border-dashed border-slate-300 p-4 dark:border-slate-700">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                            <div class="grid h-24 w-24 shrink-0 place-items-center overflow-hidden rounded-2xl bg-slate-100 dark:bg-slate-800">
                                <img v-if="logoPreview" :src="logoPreview" alt="معاينة شعار المعرض" class="h-full w-full object-contain p-2" />
                                <ApplicationLogo v-else class="h-16 w-16" />
                            </div>
                            <div class="flex-1">
                                <h3 class="font-bold text-slate-900 dark:text-white">شعار المعرض</h3>
                                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">صورة JPG أو PNG أو WebP، بحد أقصى 2MB.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <label class="app-secondary-button cursor-pointer">
                                        اختيار صورة
                                        <input class="sr-only" type="file" accept="image/jpeg,image/png,image/webp" @change="handleLogoUpload" />
                                    </label>
                                    <button v-if="logoPreview" type="button" class="rounded-xl px-4 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30" @click="removeLogo">
                                        إزالة الشعار
                                    </button>
                                </div>
                                <p v-if="form.errors.store_logo" class="mt-2 text-sm text-rose-600">{{ form.errors.store_logo }}</p>
                            </div>
                        </div>
                    </div>

                    <Field class="mt-5" label="تذييل المستندات" :error="form.errors.footer_text">
                        <textarea v-model="form.footer_text" class="app-input min-h-24" rows="3" placeholder="مثال: شكراً لثقتكم بفنانة فون" />
                    </Field>
                </section>

                <section v-if="activeTab === 'invoice'" class="settings-card">
                    <SectionHeading title="الفواتير والطباعة" description="نصوص موحدة تظهر في مستندات البيع والصيانة وسياسة الاستبدال والاسترجاع." />
                    <div class="space-y-5">
                        <Field label="تذييل الفاتورة" :error="form.errors.invoice_footer">
                            <textarea v-model="form.invoice_footer" class="app-input min-h-24" rows="3" />
                        </Field>
                        <Field label="سياسة الاستبدال والاسترجاع" :error="form.errors.return_policy">
                            <textarea v-model="form.return_policy" class="app-input min-h-28" rows="4" />
                        </Field>
                        <div class="grid gap-5 md:grid-cols-2">
                            <Field label="ملاحظات فاتورة البيع" :error="form.errors.sales_notes">
                                <textarea v-model="form.sales_notes" class="app-input min-h-28" rows="4" />
                            </Field>
                            <Field label="ملاحظات فاتورة الصيانة" :error="form.errors.repair_notes">
                                <textarea v-model="form.repair_notes" class="app-input min-h-28" rows="4" />
                            </Field>
                        </div>
                    </div>
                </section>

                <section v-if="activeTab === 'stock'" class="settings-card">
                    <SectionHeading title="إعدادات المخزون" description="قيمة افتراضية تستخدم عند إنشاء منتجات جديدة ويمكن تعديلها لكل منتج." />
                    <div class="max-w-md">
                        <Field label="الحد الافتراضي للمخزون المنخفض" :error="form.errors.default_low_stock">
                            <input v-model.number="form.default_low_stock" class="app-input" type="number" min="0" step="1" />
                        </Field>
                    </div>
                </section>

                <section v-if="activeTab === 'payment'" class="settings-card">
                    <SectionHeading title="الدفع والحسابات" description="بيانات العرض الافتراضية وطرق الدفع المتاحة في النماذج." />
                    <div class="grid gap-5 md:grid-cols-2">
                        <Field label="اسم البنك" :error="form.errors.bank_name"><input v-model="form.bank_name" class="app-input" type="text" /></Field>
                        <Field label="رقم الحساب" :error="form.errors.bank_account"><input v-model="form.bank_account" class="app-input" type="text" dir="ltr" /></Field>
                        <Field label="رقم IBAN" :error="form.errors.bank_iban"><input v-model="form.bank_iban" class="app-input" type="text" dir="ltr" /></Field>
                        <Field label="اسم التطبيق البنكي" :error="form.errors.app_name"><input v-model="form.app_name" class="app-input" type="text" /></Field>
                        <Field label="حساب التطبيق" :error="form.errors.app_account"><input v-model="form.app_account" class="app-input" type="text" dir="ltr" /></Field>
                        <Field label="صندوق الكاش الافتراضي" :error="form.errors.default_cash_account">
                            <select v-model="form.default_cash_account" class="app-input">
                                <option :value="null">اختيار تلقائي</option>
                                <option v-for="account in cashAccounts" :key="account.id" :value="account.id">{{ account.name }}</option>
                            </select>
                        </Field>
                    </div>
                    <div class="mt-6">
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-200">طرق الدفع المفعلة</p>
                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                            <label v-for="method in paymentMethodOptions" :key="method.value" class="flex cursor-pointer items-center gap-3 rounded-xl border border-slate-200 p-4 transition hover:border-primary-300 dark:border-slate-700 dark:hover:border-primary-700">
                                <input v-model="form.payment_methods" class="rounded border-slate-300 text-primary-600 focus:ring-primary-500" type="checkbox" :value="method.value" />
                                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ method.label }}</span>
                            </label>
                        </div>
                    </div>
                </section>

                <section v-if="activeTab === 'system'" class="settings-card">
                    <SectionHeading title="النظام والأمان" description="معلومات تشخيصية للبيئة الحالية. لا تعرض هذه التفاصيل لغير مالك النظام." />
                    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <InfoCard label="اسم التطبيق" :value="system.app_name" />
                        <InfoCard label="بيئة التشغيل" :value="system.app_env" />
                        <InfoCard label="إصدار Laravel" :value="system.laravel_version" />
                        <InfoCard label="إصدار PHP" :value="system.php_version" />
                        <InfoCard label="المنطقة الزمنية" :value="system.app_timezone" />
                        <InfoCard label="اللغة" :value="system.app_locale" />
                    </div>
                    <div class="mt-5 rounded-xl border p-4" :class="system.app_debug ? 'border-amber-300 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30' : 'border-emerald-300 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30'">
                        <p class="font-bold" :class="system.app_debug ? 'text-amber-800 dark:text-amber-300' : 'text-emerald-800 dark:text-emerald-300'">
                            وضع التصحيح {{ system.app_debug ? 'مفعّل' : 'غير مفعّل' }}
                        </p>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-400">يجب أن يكون APP_DEBUG=false عند النشر الفعلي.</p>
                    </div>
                </section>

                <section v-if="activeTab === 'backup'" class="settings-card">
                    <SectionHeading title="النسخ الاحتياطي" description="إنشاء وحفظ نسخ من قاعدة البيانات والملفات المهمة." />
                    <div v-if="!system.zip_available" class="mb-5 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                        امتداد PHP Zip غير متاح. فعّل الامتداد قبل إنشاء النسخ الاحتياطية.
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <InfoCard label="عدد النسخ" :value="String(backup.backup_count || 0)" />
                        <InfoCard label="آخر نسخة" :value="backup.last_backup ? formatDate(backup.last_backup.modified_at) : 'لا توجد نسخة'" />
                        <InfoCard label="حالة ZIP" :value="system.zip_available ? 'متاح' : 'غير متاح'" />
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <button type="button" class="app-primary-button" :disabled="backupProcessing || !system.zip_available" @click="createBackup">
                            {{ backupProcessing ? 'جارٍ إنشاء النسخة...' : 'إنشاء نسخة الآن' }}
                        </button>
                        <button type="button" class="app-secondary-button" :disabled="backupProcessing || !backup.backups?.length" @click="askCleanBackups">تنظيف النسخ القديمة</button>
                    </div>
                    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 dark:border-slate-800">
                        <div v-if="backup.backups?.length" class="divide-y divide-slate-200 dark:divide-slate-800">
                            <div v-for="file in backup.backups" :key="file.name" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-semibold text-slate-900 dark:text-white">{{ file.name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ file.size_formatted }} · {{ formatDateTime(file.modified_at) }}</p>
                                </div>
                                <div class="flex gap-2">
                                    <a :href="route('settings.backup.download', file.name)" class="app-secondary-button">تنزيل</a>
                                    <button type="button" class="rounded-xl px-3 py-2 text-sm font-semibold text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-950/30" @click="askDeleteBackup(file)">حذف</button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="p-10 text-center text-sm text-slate-500 dark:text-slate-400">لا توجد نسخ احتياطية محفوظة حالياً.</div>
                    </div>
                </section>

                <div v-if="activeTab !== 'system' && activeTab !== 'backup'" class="sticky bottom-4 z-10 flex items-center justify-between rounded-2xl border border-slate-200/90 bg-white/95 p-4 shadow-xl backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
                    <p class="hidden text-sm text-slate-500 sm:block">احفظ التغييرات لتطبيقها على الفواتير والواجهات.</p>
                    <button class="app-primary-button" type="submit" :disabled="form.processing">
                        {{ form.processing ? 'جارٍ الحفظ...' : 'حفظ الإعدادات' }}
                    </button>
                </div>
            </form>
        </div>

        <ConfirmationModal
            :show="Boolean(confirmation.action)"
            :title="confirmation.title"
            :message="confirmation.message"
            :loading="backupProcessing"
            @close="closeConfirmation"
            @confirm="confirmBackupAction"
        />
    </AuthenticatedLayout>
</template>

<script setup>
import { computed, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    store: { type: Object, default: () => ({}) },
    invoice: { type: Object, default: () => ({}) },
    payment: { type: Object, default: () => ({}) },
    financialAccounts: { type: Array, default: () => [] },
    system: { type: Object, default: () => ({}) },
    backup: { type: Object, default: () => ({ backups: [], backup_count: 0, last_backup: null }) },
});

const tabs = [
    { key: 'general', label: 'بيانات المعرض', path: 'M3 21h18M5 21V8l7-5 7 5v13M9 21v-6h6v6' },
    { key: 'invoice', label: 'الفواتير والطباعة', path: 'M7 3h8l4 4v14H7V3zm8 0v5h5M10 13h6M10 17h6M10 9h2' },
    { key: 'stock', label: 'المخزون', path: 'M21 8l-9 5-9-5 9-5 9 5zm-18 0v8l9 5 9-5V8M12 13v8' },
    { key: 'payment', label: 'الدفع والحسابات', path: 'M3 7h18v10H3V7zm0 4h18M7 15h3' },
    { key: 'system', label: 'النظام والأمان', path: 'M12 15a3 3 0 100-6 3 3 0 000 6zm0-12v2m0 14v2M4.22 4.22l1.42 1.42m12.72 12.72l1.42 1.42M3 12h2m14 0h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42' },
    { key: 'backup', label: 'النسخ الاحتياطي', path: 'M5 4h12l2 2v14H5V4zm3 0v6h8V4M8 20v-6h8v6' },
];

const activeTab = ref('general');
const backupProcessing = ref(false);
const confirmation = ref({ action: null, title: '', message: '', file: null });
const logoPreview = ref(props.store.store_logo ? `/storage/${props.store.store_logo}` : null);

const form = useForm({
    store_name: props.store.store_name || 'فنانة فون',
    store_phone: props.store.store_phone || '',
    store_whatsapp: props.store.store_whatsapp || '',
    store_email: props.store.store_email || '',
    store_address: props.store.store_address || '',
    store_logo: null,
    remove_store_logo: false,
    footer_text: props.store.footer_text || '',
    invoice_footer: props.invoice.invoice_footer || '',
    return_policy: props.invoice.return_policy || '',
    sales_notes: props.invoice.sales_notes || '',
    repair_notes: props.invoice.repair_notes || '',
    default_low_stock: Number(props.invoice.default_low_stock ?? 5),
    bank_name: props.payment.bank_name || '',
    bank_account: props.payment.bank_account || '',
    bank_iban: props.payment.bank_iban || '',
    app_name: props.payment.app_name || '',
    app_account: props.payment.app_account || '',
    default_cash_account: props.payment.default_cash_account ? Number(props.payment.default_cash_account) : null,
    payment_methods: Array.isArray(props.payment.payment_methods) ? props.payment.payment_methods : ['cash', 'bank_transfer', 'banking_app'],
});

const cashAccounts = computed(() => props.financialAccounts.filter((account) => String(account.type) === 'cash'));
const paymentMethodOptions = [
    { value: 'cash', label: 'كاش' },
    { value: 'bank_transfer', label: 'تحويل بنكي' },
    { value: 'banking_app', label: 'تطبيق بنكي' },
];

function handleLogoUpload(event) {
    const file = event.target.files?.[0];
    if (!file) return;
    form.store_logo = file;
    form.remove_store_logo = false;
    logoPreview.value = URL.createObjectURL(file);
}

function removeLogo() {
    form.store_logo = null;
    form.remove_store_logo = true;
    logoPreview.value = null;
}

function submit() {
    form.post(route('settings.update'), { preserveScroll: true, forceFormData: true });
}

function createBackup() {
    backupProcessing.value = true;
    router.post(route('settings.backup.create'), {}, {
        preserveScroll: true,
        onFinish: () => { backupProcessing.value = false; },
    });
}

function askDeleteBackup(file) {
    confirmation.value = {
        action: 'delete',
        title: 'حذف النسخة الاحتياطية',
        message: `سيتم حذف النسخة «${file.name}» نهائياً. هل تريد المتابعة؟`,
        file,
    };
}

function askCleanBackups() {
    confirmation.value = {
        action: 'clean',
        title: 'تنظيف النسخ القديمة',
        message: 'سيتم حذف النسخ القديمة وفق سياسة الاحتفاظ الحالية مع إبقاء النسخ الحديثة.',
        file: null,
    };
}

function closeConfirmation() {
    if (!backupProcessing.value) confirmation.value = { action: null, title: '', message: '', file: null };
}

function confirmBackupAction() {
    backupProcessing.value = true;
    const options = {
        preserveScroll: true,
        onFinish: () => {
            backupProcessing.value = false;
            closeConfirmation();
        },
    };
    if (confirmation.value.action === 'delete') {
        router.delete(route('settings.backup.delete', confirmation.value.file.name), options);
    } else {
        router.post(route('settings.backup.clean'), {}, options);
    }
}

function formatDate(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('ar-PS', { dateStyle: 'medium' }).format(new Date(value));
}

function formatDateTime(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat('ar-PS', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value));
}
</script>

<script>
const SectionHeading = {
    props: ['title', 'description'],
    template: `<div class="mb-6 border-b border-slate-200 pb-4 dark:border-slate-800"><h2 class="text-lg font-bold text-slate-950 dark:text-white">{{ title }}</h2><p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ description }}</p></div>`,
};
const Field = {
    props: ['label', 'error'],
    template: `<label class="block"><span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ label }}</span><div class="mt-2"><slot /></div><span v-if="error" class="mt-1 block text-sm text-rose-600">{{ error }}</span></label>`,
};
const InfoCard = {
    props: ['label', 'value'],
    template: `<div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 dark:border-slate-800 dark:bg-slate-950/40"><p class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ label }}</p><p class="mt-2 break-words text-base font-bold text-slate-950 dark:text-white">{{ value || '—' }}</p></div>`,
};
export default { components: { SectionHeading, Field, InfoCard } };
</script>

<style scoped>
.settings-card { @apply rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6; }
.app-input { @apply block w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:border-primary-500 focus:ring-primary-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white; }
.app-primary-button { @apply inline-flex items-center justify-center rounded-xl bg-primary-600 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 dark:focus:ring-offset-slate-900; }
.app-secondary-button { @apply inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800; }
</style>
