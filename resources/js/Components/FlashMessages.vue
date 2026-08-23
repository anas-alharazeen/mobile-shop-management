<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const notices = ref([]);
let nextId = 1;
const flash = computed(() => page.props.flash || {});
const palette = {
    success: { title: 'تمت العملية بنجاح', classes: 'border-emerald-200 bg-white text-emerald-700 dark:border-emerald-500/25 dark:bg-slate-900 dark:text-emerald-300' },
    error: { title: 'تعذر إتمام العملية', classes: 'border-rose-200 bg-white text-rose-700 dark:border-rose-500/25 dark:bg-slate-900 dark:text-rose-300' },
    warning: { title: 'تنبيه', classes: 'border-amber-200 bg-white text-amber-700 dark:border-amber-500/25 dark:bg-slate-900 dark:text-amber-300' },
    info: { title: 'معلومة', classes: 'border-indigo-200 bg-white text-indigo-700 dark:border-indigo-500/25 dark:bg-slate-900 dark:text-indigo-300' },
};

const remove = (id) => { notices.value = notices.value.filter((notice) => notice.id !== id); };
watch(flash, (value) => {
    Object.entries(value || {}).forEach(([type, message]) => {
        if (!message) return;
        const id = nextId++;
        notices.value.push({ id, type, message, ...(palette[type] || palette.info) });
        window.setTimeout(() => remove(id), type === 'error' ? 7000 : 4500);
    });
}, { immediate: true, deep: true });
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed left-4 top-20 z-[100] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-3 sm:left-6">
            <TransitionGroup enter-active-class="transition duration-300 ease-out" enter-from-class="-translate-x-4 opacity-0" leave-active-class="transition duration-200 ease-in" leave-to-class="-translate-x-4 opacity-0">
                <article v-for="notice in notices" :key="notice.id" class="pointer-events-auto rounded-2xl border p-4 shadow-xl shadow-slate-950/10 backdrop-blur" :class="notice.classes">
                    <div class="flex items-start gap-3">
                        <div class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-current/10"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="notice.type === 'success' ? 'M5 13l4 4L19 7' : notice.type === 'error' ? 'M6 18L18 6M6 6l12 12' : 'M12 9v2m0 4h.01M5.07 19h13.86A2 2 0 0020.66 16L13.73 4a2 2 0 00-3.46 0L3.34 16A2 2 0 005.07 19z'"/></svg></div>
                        <div class="min-w-0 flex-1"><p class="text-sm font-bold">{{ notice.title }}</p><p class="mt-1 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ notice.message }}</p></div>
                        <button type="button" class="rounded-lg p-1 opacity-60 transition hover:bg-current/10 hover:opacity-100" aria-label="إغلاق الرسالة" @click="remove(notice.id)"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                </article>
            </TransitionGroup>
        </div>
    </Teleport>
</template>
