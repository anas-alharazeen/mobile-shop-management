<script setup>
import { Head } from '@inertiajs/vue3';
import Header from '@/Layouts/Partials/Header.vue';
import Sidebar from '@/Layouts/Partials/Sidebar.vue';
import FlashMessages from '@/Components/FlashMessages.vue';
import { useSidebar } from '@/composables/useSidebar';
const { isCollapsed, isMobileOpen, isMobile, toggleSidebar, closeMobile } = useSidebar();
defineProps({ title: { type: String, default: 'فنانة فون' } });
</script>

<template>
    <Head :title="title" />
    <div class="min-h-screen bg-slate-50 text-slate-900 transition-colors dark:bg-slate-950 dark:text-slate-100">
        <Header :user="$page.props.auth.user" :is-collapsed="isCollapsed" @toggle-sidebar="toggleSidebar" />
        <Sidebar :is-collapsed="isCollapsed" :is-mobile-open="isMobileOpen" :is-mobile="isMobile" @close-mobile="closeMobile" />
        <FlashMessages />
        <main :class="['min-h-screen pt-16 transition-[margin] duration-300 ease-out', !isMobile && !isCollapsed ? 'mr-64' : !isMobile && isCollapsed ? 'mr-20' : 'mr-0']">
            <div class="mx-auto w-full max-w-[1700px] p-4 sm:p-6 lg:p-8">
                <header v-if="$slots.header" class="mb-6 rounded-3xl border border-slate-200/80 bg-white/80 p-5 shadow-sm shadow-slate-950/[0.03] backdrop-blur dark:border-slate-800 dark:bg-slate-900/70 sm:p-6"><slot name="header" /></header>
                <slot />
            </div>
        </main>
    </div>
</template>
