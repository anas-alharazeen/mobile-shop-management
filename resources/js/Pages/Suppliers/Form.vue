<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('suppliers.index')"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:-translate-x-0.5 hover:border-slate-300 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:text-white"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ pageTitle }}</h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ supplier ? 'حدّث بيانات المورد وجهات التواصل دون التأثير على فواتيره السابقة.' : 'أضف المورد مرة واحدة لتسريع المشتريات والدفعات ومتابعة الذمم.' }}
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
                    {{ form.is_active ? 'مورد نشط' : 'مورد غير نشط' }}
                </span>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-6">
                <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 shadow-xl shadow-slate-950/10 dark:border-slate-800 sm:p-7">
                    <div class="absolute -left-20 -top-20 h-52 w-52 rounded-full bg-indigo-500/20 blur-3xl" />
                    <div class="absolute -bottom-16 right-10 h-36 w-36 rounded-full bg-cyan-400/10 blur-3xl" />
                    <div class="relative flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-indigo-200 ring-1 ring-white/10">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-200/80">Supplier profile</p>
                            <h2 class="mt-2 text-2xl font-black text-white">ملف مورد مرتب وسهل الرجوع إليه</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-300">
                                الاسم والهاتف هما أهم نقطتين للتعامل اليومي، بينما بيانات الشركة والبريد والعنوان تساعدك في التوثيق والفواتير.
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
                                <h3 class="font-black text-slate-900 dark:text-white">هوية المورد</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">الاسم الشخصي والاسم التجاري إن وجد.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                        <div>
                            <label for="supplier-name" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                اسم المورد <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="supplier-name"
                                v-model="form.name"
                                type="text"
                                maxlength="255"
                                autofocus
                                placeholder="اسم المورد أو المسؤول"
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                :class="{ 'border-rose-400 ring-1 ring-rose-300': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <label for="company-name" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">اسم الشركة / المتجر</label>
                            <input
                                id="company-name"
                                v-model="form.company_name"
                                type="text"
                                maxlength="255"
                                placeholder="مثال: شركة ABC للتوريدات"
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                            />
                            <p v-if="form.errors.company_name" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.company_name }}</p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498A1 1 0 0121 15.72V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white">التواصل</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">بيانات التواصل المستخدمة عند الشراء والمتابعة.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 p-5 sm:grid-cols-2 sm:p-6">
                        <div>
                            <label for="supplier-phone" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                رقم الهاتف <span class="text-rose-500">*</span>
                            </label>
                            <input
                                id="supplier-phone"
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
                            <label for="supplier-whatsapp" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">واتساب</label>
                            <input
                                id="supplier-whatsapp"
                                v-model="form.whatsapp"
                                type="tel"
                                maxlength="20"
                                dir="ltr"
                                placeholder="0599123456"
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-semibold text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                            />
                            <p v-if="form.errors.whatsapp" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.whatsapp }}</p>
                        </div>

                        <div>
                            <label for="supplier-email" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">البريد الإلكتروني</label>
                            <input
                                id="supplier-email"
                                v-model="form.email"
                                type="email"
                                maxlength="255"
                                dir="ltr"
                                placeholder="supplier@example.com"
                                class="block w-full rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-left text-sm font-medium text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                :class="{ 'border-rose-400 ring-1 ring-rose-300': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label for="supplier-address" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">العنوان</label>
                            <input
                                id="supplier-address"
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
                        <label for="supplier-notes" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">ملاحظات داخلية</label>
                        <textarea
                            id="supplier-notes"
                            v-model="form.notes"
                            rows="3"
                            maxlength="500"
                            placeholder="شروط دفع، أوقات تواصل، ملاحظات على التوريد..."
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
                                <p class="text-sm font-black text-slate-900 dark:text-white">السماح باستخدام المورد</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">تعطيله يمنع اختياره في عمليات جديدة مع بقاء السجل التاريخي محفوظًا.</p>
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
                    <div class="bg-gradient-to-br from-indigo-600 to-slate-950 p-5 text-white">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 text-xl font-black ring-1 ring-white/10">
                            {{ initials }}
                        </div>
                        <h3 class="mt-4 truncate text-xl font-black">{{ form.name || 'اسم المورد' }}</h3>
                        <p class="mt-1 truncate text-sm text-indigo-100/80">{{ form.company_name || 'بدون اسم شركة' }}</p>
                    </div>
                    <div class="space-y-3 p-5 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">الهاتف</span>
                            <span class="font-bold text-slate-900 dark:text-white" dir="ltr">{{ form.phone || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">الحالة</span>
                            <span class="font-bold" :class="form.is_active ? 'text-emerald-600' : 'text-slate-500'">{{ form.is_active ? 'نشط' : 'غير نشط' }}</span>
                        </div>
                    </div>
                </section>

                <div class="rounded-3xl border border-indigo-100 bg-indigo-50 p-4 text-xs leading-6 text-indigo-800 dark:border-indigo-900/50 dark:bg-indigo-950/20 dark:text-indigo-200">
                    <strong class="block text-sm">للعمل بشكل أسرع</strong>
                    احفظ رقم الهاتف الأساسي بشكل موحد، وأضف اسم الشركة عندما يكون المورد جهة تجارية حتى يسهل البحث عنه لاحقًا.
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
                    {{ form.processing ? 'جاري الحفظ...' : (supplier ? 'حفظ التعديلات' : 'إضافة المورد') }}
                </button>

                <Link
                    :href="route('suppliers.index')"
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
    supplier: {
        type: Object,
        default: null,
    },
    pageTitle: {
        type: String,
        required: true,
    },
});

const form = useForm({
    name: props.supplier?.name || '',
    company_name: props.supplier?.company_name || '',
    phone: props.supplier?.phone || '',
    whatsapp: props.supplier?.whatsapp || '',
    email: props.supplier?.email || '',
    address: props.supplier?.address || '',
    notes: props.supplier?.notes || '',
    is_active: props.supplier?.is_active ?? true,
});

const initials = computed(() => {
    const words = form.name.trim().split(/\s+/).filter(Boolean);
    if (!words.length) return 'م';
    return words.slice(0, 2).map((word) => word.charAt(0)).join('').toUpperCase();
});

const submit = () => {
    if (props.supplier) {
        form.put(route('suppliers.update', props.supplier.id));
        return;
    }

    form.post(route('suppliers.store'));
};
</script>
