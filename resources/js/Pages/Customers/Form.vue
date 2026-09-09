<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('customers.index')"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:-translate-x-0.5 hover:border-slate-300 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:text-white"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ pageTitle }}</h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ customer ? 'حدّث بيانات العميل مع الحفاظ على فواتيره ودفعاته وسجل الصيانة.' : 'أنشئ ملف عميل مرتب ليسهل البيع والمتابعة والصيانة والديون.' }}
                        </p>
                    </div>
                </div>

                <span
                    class="inline-flex w-fit items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-bold"
                    :class="form.is_active
                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300'
                        : 'border-slate-200 bg-slate-100 text-slate-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300'"
                >
                    <span class="h-2 w-2 rounded-full" :class="form.is_active ? 'bg-emerald-500' : 'bg-slate-400'" />
                    {{ form.is_active ? 'عميل نشط' : 'عميل غير نشط' }}
                </span>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-6">
                <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 shadow-xl shadow-slate-950/10 dark:border-slate-800 sm:p-7">
                    <div class="absolute -left-20 -top-20 h-52 w-52 rounded-full bg-blue-500/20 blur-3xl" />
                    <div class="absolute -bottom-16 right-10 h-36 w-36 rounded-full bg-violet-400/10 blur-3xl" />
                    <div class="relative flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-blue-200 ring-1 ring-white/10">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200/80">Customer profile</p>
                            <h2 class="mt-2 text-2xl font-black text-white">بيانات أقل، تجربة أسرع عند كل عملية</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-300">
                                الاسم هو الحقل الأساسي، وبقية بيانات التواصل اختيارية لكنها تساعدك في الفواتير والمتابعة وخدمة ما بعد البيع.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A7 7 0 0112 15a7 7 0 016.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0zM12 3a9 9 0 100 18 9 9 0 000-18z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white">البيانات الأساسية</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">اسم العميل ومعلومات الوصول السريع إليه.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5 p-5 sm:p-6">
                        <div>
                            <label for="customer-name" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                اسم العميل <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="customer-name"
                                v-model="form.name"
                                type="text"
                                maxlength="255"
                                autofocus
                                placeholder="الاسم الكامل للعميل"
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                :class="{ 'border-rose-400 ring-1 ring-rose-300': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label for="customer-phone" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">رقم الهاتف</label>
                                <input
                                    id="customer-phone"
                                    v-model="form.phone"
                                    type="tel"
                                    maxlength="20"
                                    dir="ltr"
                                    placeholder="0599123456"
                                    class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-semibold text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                    :class="{ 'border-rose-400 ring-1 ring-rose-300': form.errors.phone }"
                                />
                                <p v-if="form.errors.phone" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.phone }}</p>
                            </div>

                            <div>
                                <label for="customer-whatsapp" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">واتساب</label>
                                <input
                                    id="customer-whatsapp"
                                    v-model="form.whatsapp"
                                    type="tel"
                                    maxlength="20"
                                    dir="ltr"
                                    placeholder="0599123456"
                                    class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-semibold text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                />
                                <p v-if="form.errors.whatsapp" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.whatsapp }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white">بيانات التواصل الإضافية</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">اختيارية — مفيدة للإيصالات والمتابعة وخدمة العملاء.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                        <div>
                            <label for="customer-email" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">البريد الإلكتروني</label>
                            <input
                                id="customer-email"
                                v-model="form.email"
                                type="email"
                                maxlength="255"
                                dir="ltr"
                                placeholder="customer@example.com"
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-medium text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                :class="{ 'border-rose-400 ring-1 ring-rose-300': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label for="customer-address" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">العنوان</label>
                            <input
                                id="customer-address"
                                v-model="form.address"
                                type="text"
                                maxlength="500"
                                placeholder="المدينة / المنطقة / تفاصيل العنوان"
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-medium text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                            />
                            <p v-if="form.errors.address" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.address }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div class="mb-5">
                        <label for="customer-notes" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">ملاحظات داخلية</label>
                        <textarea
                            id="customer-notes"
                            v-model="form.notes"
                            rows="3"
                            maxlength="500"
                            placeholder="ملاحظات تساعد فريق المبيعات أو الصيانة عند التعامل مع العميل..."
                            class="block w-full resize-none rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-7 text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                        />
                        <p v-if="form.errors.notes" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.notes }}</p>
                    </div>

                    <button type="button" class="flex w-full items-center justify-between gap-4 text-right" @click="form.is_active = !form.is_active">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-black text-slate-900 dark:text-white">السماح باستخدام العميل</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">تعطيله يمنع اختياره في عمليات جديدة مع بقاء السجل السابق محفوظًا.</p>
                            </div>
                        </div>
                        <span class="relative inline-flex h-7 w-12 shrink-0 rounded-full p-1 transition" :class="form.is_active ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'">
                            <span class="h-5 w-5 rounded-full bg-white shadow transition-transform" :class="form.is_active ? '-translate-x-5' : 'translate-x-0'" />
                        </span>
                    </button>
                </section>
            </div>

            <aside class="space-y-5 lg:sticky lg:top-6 lg:self-start">
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="bg-gradient-to-br from-blue-600 to-slate-950 p-5 text-white">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-xl font-black ring-1 ring-white/10">
                            {{ initials }}
                        </div>
                        <h3 class="mt-4 truncate text-xl font-black">{{ form.name || 'اسم العميل' }}</h3>
                        <p class="mt-1 truncate text-sm text-blue-100/80" dir="ltr">{{ form.phone || 'بدون رقم هاتف' }}</p>
                    </div>
                    <div class="space-y-3 p-5 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">البريد</span>
                            <span class="max-w-[170px] truncate font-bold text-slate-900 dark:text-white" dir="ltr">{{ form.email || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">الحالة</span>
                            <span class="font-bold" :class="form.is_active ? 'text-emerald-600' : 'text-slate-500'">{{ form.is_active ? 'نشط' : 'غير نشط' }}</span>
                        </div>
                    </div>
                </section>

                <div class="rounded-3xl border border-blue-100 bg-blue-50 p-4 text-xs leading-6 text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-200">
                    <strong class="block text-sm">إضافة سريعة</strong>
                    لو كنت مستعجلًا يكفي اسم العميل. يمكنك إضافة الهاتف والبريد والعنوان لاحقًا من صفحة التعديل.
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl bg-blue-600 px-5 py-3 text-sm font-black text-white shadow-lg shadow-blue-600/20 transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    {{ form.processing ? 'جاري الحفظ...' : (customer ? 'حفظ التعديلات' : 'إضافة العميل') }}
                </button>

                <Link
                    :href="route('customers.index')"
                    class="inline-flex min-h-11 w-full items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-600 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                >
                    إلغاء
                </Link>
            </aside>
        </form>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    customer: {
        type: Object,
        default: null,
    },
    pageTitle: {
        type: String,
        required: true,
    },
});

const form = useForm({
    name: props.customer?.name || '',
    phone: props.customer?.phone || '',
    whatsapp: props.customer?.whatsapp || '',
    email: props.customer?.email || '',
    address: props.customer?.address || '',
    notes: props.customer?.notes || '',
    is_active: props.customer?.is_active ?? true,
});

const initials = computed(() => {
    const words = form.name.trim().split(/\s+/).filter(Boolean);
    if (!words.length) return 'ع';
    return words.slice(0, 2).map((word) => word.charAt(0)).join('').toUpperCase();
});

const submit = () => {
    if (props.customer) {
        form.put(route('customers.update', props.customer.id));
        return;
    }

    form.post(route('customers.store'));
};
</script>
