<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
const props = defineProps({ compact: Boolean, light: Boolean });
const page = usePage();
const app = computed(() => page.props.app || {});
</script>

<template>
    <div class="flex min-w-0 items-center gap-3">
        <div class="relative flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 to-blue-500 text-white shadow-lg shadow-indigo-600/20">
            <img v-if="app.logo" :src="`/storage/${app.logo}`" :alt="app.name" class="h-full w-full object-cover" />
            <svg v-else class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 2h8a2 2 0 012 2v16a2 2 0 01-2 2H8a2 2 0 01-2-2V4a2 2 0 012-2zm4 17h.01M9 6h6"/></svg>
            <span class="absolute inset-x-1 bottom-1 h-0.5 rounded-full bg-white/70"></span>
        </div>
        <div v-if="!compact" class="min-w-0">
            <p class="truncate text-base font-black tracking-tight" :class="light ? 'text-white' : 'text-slate-950 dark:text-white'">{{ app.name || 'فنانة فون' }}</p>
            <p class="truncate text-[11px] font-medium" :class="light ? 'text-white/70' : 'text-slate-500 dark:text-slate-400'">إدارة المبيعات والمخزون والصيانة</p>
        </div>
    </div>
</template>
