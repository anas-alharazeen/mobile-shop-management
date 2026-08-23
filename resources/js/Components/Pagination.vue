<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    links: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <nav
        v-if="links.length > 1"
        class="flex flex-wrap items-center gap-1"
        aria-label="التنقل بين الصفحات"
    >
        <template
            v-for="(link, index) in links"
            :key="`${link.label}-${index}`"
        >
            <Link
                v-if="link.url"
                :href="link.url"
                preserve-scroll
                class="flex h-9 min-w-9 items-center justify-center rounded-lg px-3 text-sm font-bold transition"
                :class="
                    link.active
                        ? 'bg-indigo-600 text-white shadow-sm'
                        : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700'
                "
                v-html="link.label"
            />

            <span
                v-else
                class="flex h-9 min-w-9 cursor-not-allowed items-center justify-center rounded-lg px-3 text-sm text-slate-400 dark:text-slate-600"
                v-html="link.label"
            />
        </template>
    </nav>
</template>
