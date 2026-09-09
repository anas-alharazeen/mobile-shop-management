<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('categories.index')"
                        class="flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 shadow-sm transition hover:-translate-x-0.5 hover:border-slate-300 hover:text-slate-900 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 dark:hover:text-white"
                    >
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </Link>
                    <div>
                        <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">{{ pageTitle }}</h1>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            {{ category ? 'حدّث بيانات الفئة مع الحفاظ على المنتجات المرتبطة بها.' : 'أنشئ فئة واضحة لتسريع تنظيم المنتجات والبحث عنها.' }}
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
                    {{ form.is_active ? 'الفئة مفعلة' : 'الفئة غير مفعلة' }}
                </span>
            </div>
        </template>

        <form @submit.prevent="submit" class="mx-auto grid max-w-6xl gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
            <div class="space-y-6">
                <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-950 p-6 shadow-xl shadow-slate-950/10 dark:border-slate-800 sm:p-7">
                    <div class="absolute -left-16 -top-20 h-48 w-48 rounded-full bg-blue-500/20 blur-3xl" />
                    <div class="relative flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/10 text-blue-200 ring-1 ring-white/10">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.18em] text-blue-200/80">Catalog organization</p>
                            <h2 class="mt-2 text-2xl font-black text-white">فئة واضحة = مخزون أسهل في الإدارة</h2>
                            <p class="mt-2 max-w-2xl text-sm leading-7 text-slate-300">
                                استخدم اسمًا مختصرًا ونوعًا دقيقًا. ستظهر الفئة لاحقًا في المنتجات والفلاتر والتقارير ذات الصلة.
                            </p>
                        </div>
                    </div>
                </section>

                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="border-b border-slate-100 px-5 py-5 dark:border-slate-800 sm:px-6">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-black text-slate-900 dark:text-white">البيانات الأساسية</h3>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">المعلومات التي تعرّف الفئة داخل النظام.</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6 p-5 sm:p-6">
                        <div>
                            <label for="category-name" class="mb-2 block text-sm font-bold text-slate-700 dark:text-slate-200">
                                اسم الفئة <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                    </svg>
                                </span>
                                <input
                                    id="category-name"
                                    v-model="form.name"
                                    type="text"
                                    maxlength="255"
                                    autofocus
                                    placeholder="مثال: هواتف Samsung"
                                    class="block w-full rounded-2xl border-slate-200 bg-slate-50 py-3 pl-4 pr-11 text-sm font-semibold text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                                    :class="{ 'border-rose-400 ring-1 ring-rose-300': form.errors.name }"
                                />
                            </div>
                            <p v-if="form.errors.name" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.name }}</p>
                        </div>

                        <div>
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <label class="text-sm font-bold text-slate-700 dark:text-slate-200">
                                    نوع الفئة <span class="text-rose-500">*</span>
                                </label>
                                <span class="text-[11px] font-medium text-slate-400">اختر الاستخدام الأقرب</span>
                            </div>

                            <div class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-3">
                                <button
                                    v-for="(label, value) in types"
                                    :key="value"
                                    type="button"
                                    class="group flex items-center gap-3 rounded-2xl border p-3.5 text-right transition"
                                    :class="form.type === value
                                        ? 'border-blue-400 bg-blue-50 ring-2 ring-blue-100 dark:border-blue-600 dark:bg-blue-950/30 dark:ring-blue-900/30'
                                        : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:bg-slate-800'"
                                    @click="form.type = value"
                                >
                                    <span
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                        :class="form.type === value ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300'"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h10" />
                                        </svg>
                                    </span>
                                    <span class="min-w-0 text-sm font-bold text-slate-800 dark:text-slate-100">{{ label }}</span>
                                </button>
                            </div>
                            <p v-if="form.errors.type" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.type }}</p>
                        </div>

                        <div>
                            <div class="mb-2 flex items-center justify-between gap-3">
                                <label for="category-description" class="text-sm font-bold text-slate-700 dark:text-slate-200">الوصف</label>
                                <span class="text-[11px] font-medium text-slate-400">{{ form.description.length }}/1000</span>
                            </div>
                            <textarea
                                id="category-description"
                                v-model="form.description"
                                rows="4"
                                maxlength="1000"
                                placeholder="وصف مختصر يساعدك على تمييز هذه الفئة..."
                                class="block w-full resize-none rounded-2xl border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-7 text-slate-900 shadow-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-blue-500 dark:border-slate-700 dark:bg-slate-950/60 dark:text-white"
                            />
                            <p v-if="form.errors.description" class="mt-2 text-xs font-semibold text-rose-600">{{ form.errors.description }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <button type="button" class="flex w-full items-center justify-between gap-4 text-right" @click="form.is_active = !form.is_active">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <div>
                                <p class="text-sm font-black text-slate-900 dark:text-white">إتاحة الفئة للاستخدام</p>
                                <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">عند التعطيل تبقى البيانات محفوظة، لكن الفئة لا تظهر للاختيارات الجديدة.</p>
                            </div>
                        </div>

                        <span
                            class="relative inline-flex h-7 w-12 shrink-0 rounded-full p-1 transition"
                            :class="form.is_active ? 'bg-blue-600' : 'bg-slate-300 dark:bg-slate-700'"
                        >
                            <span
                                class="h-5 w-5 rounded-full bg-white shadow transition-transform"
                                :class="form.is_active ? '-translate-x-5' : 'translate-x-0'"
                            />
                        </span>
                    </button>
                </section>
            </div>

            <aside class="space-y-5 lg:sticky lg:top-6 lg:self-start">
                <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="bg-gradient-to-br from-blue-600 to-slate-950 p-5 text-white">
                        <p class="text-xs font-bold text-blue-100">معاينة الفئة</p>
                        <div class="mt-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 ring-1 ring-white/10">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <h3 class="mt-4 truncate text-xl font-black">{{ form.name || 'اسم الفئة' }}</h3>
                        <p class="mt-1 text-sm text-blue-100/80">{{ selectedTypeLabel }}</p>
                    </div>
                    <div class="space-y-3 p-5 text-sm">
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">الحالة</span>
                            <span class="font-bold" :class="form.is_active ? 'text-emerald-600' : 'text-slate-500'">
                                {{ form.is_active ? 'مفعلة' : 'غير مفعلة' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between gap-3">
                            <span class="text-slate-500 dark:text-slate-400">نوع الفئة</span>
                            <span class="font-bold text-slate-900 dark:text-white">{{ selectedTypeLabel }}</span>
                        </div>
                    </div>
                </section>

                <div class="rounded-3xl border border-blue-100 bg-blue-50 p-4 text-xs leading-6 text-blue-800 dark:border-blue-900/50 dark:bg-blue-950/20 dark:text-blue-200">
                    <strong class="block text-sm">نصيحة تنظيمية</strong>
                    استخدم أسماء ثابتة وواضحة، وتجنب إنشاء فئتين مختلفتين لنفس النوع حتى تبقى تقارير المخزون دقيقة وسهلة القراءة.
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
                    {{ form.processing ? 'جاري الحفظ...' : (category ? 'حفظ التعديلات' : 'إضافة الفئة') }}
                </button>

                <Link
                    :href="route('categories.index')"
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
    category: {
        type: Object,
        default: null,
    },
    types: {
        type: Object,
        required: true,
    },
    pageTitle: {
        type: String,
        required: true,
    },
});

const form = useForm({
    name: props.category?.name || '',
    type: props.category?.type || '',
    description: props.category?.description || '',
    is_active: props.category?.is_active ?? true,
});

const selectedTypeLabel = computed(() => props.types[form.type] || 'لم يتم اختيار النوع');

const submit = () => {
    if (props.category) {
        form.put(route('categories.update', props.category.id));
        return;
    }

    form.post(route('categories.store'));
};
</script>
