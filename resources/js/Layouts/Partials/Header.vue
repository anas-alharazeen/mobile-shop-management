<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';

const props = defineProps({ user: { type: Object, required: true }, isCollapsed: Boolean });
const emit = defineEmits(['toggle-sidebar']);
const showDropdown = ref(false);
const dropdown = ref(null);
const closeOnOutside = (event) => { if (showDropdown.value && dropdown.value && !dropdown.value.contains(event.target)) showDropdown.value = false; };
const closeOnEscape = (event) => { if (event.key === 'Escape') showDropdown.value = false; };
onMounted(() => { document.addEventListener('pointerdown', closeOnOutside); document.addEventListener('keydown', closeOnEscape); });
onUnmounted(() => { document.removeEventListener('pointerdown', closeOnOutside); document.removeEventListener('keydown', closeOnEscape); });
</script>

<template>
    <header class="fixed inset-x-0 top-0 z-50 border-b border-slate-200/80 bg-white/90 shadow-sm shadow-slate-950/[0.03] backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/85">
        <div class="flex h-16 items-center justify-between gap-3 px-4 sm:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:border-indigo-500/50 dark:hover:bg-indigo-500/10" :aria-label="isCollapsed ? 'توسيع القائمة الجانبية' : 'طي القائمة الجانبية'" @click="emit('toggle-sidebar')">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="isCollapsed ? 'M4 6h16M4 12h8m-8 6h16' : 'M4 6h16M4 12h16M4 18h16'"/></svg>
                </button>
                <ApplicationLogo class="hidden sm:flex" />
            </div>

            <div class="flex items-center gap-2">
                <Link :href="route('pos')" class="hidden items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white shadow-lg shadow-indigo-600/20 transition hover:bg-indigo-700 md:inline-flex">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h2m-9 5h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    بيع جديد
                </Link>
                <ThemeToggle />
                <div ref="dropdown" class="relative">
                    <button type="button" class="flex items-center gap-2 rounded-xl border border-transparent px-2 py-1.5 transition hover:border-slate-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 dark:hover:border-slate-700 dark:hover:bg-slate-900" :aria-expanded="showDropdown" @click="showDropdown = !showDropdown">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-slate-900 to-slate-700 text-sm font-black text-white dark:from-indigo-500 dark:to-blue-500">{{ user.name?.slice(0, 1) }}</span>
                        <span class="hidden max-w-36 text-right sm:block"><span class="block truncate text-sm font-bold text-slate-900 dark:text-white">{{ user.name }}</span><span class="block truncate text-[10px] text-slate-500 dark:text-slate-400">مالك النظام</span></span>
                        <svg class="hidden h-4 w-4 text-slate-400 sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <Transition enter-active-class="transition duration-150 ease-out" enter-from-class="translate-y-1 opacity-0" leave-active-class="transition duration-100 ease-in" leave-to-class="translate-y-1 opacity-0">
                        <div v-if="showDropdown" class="absolute left-0 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl shadow-slate-950/15 dark:border-slate-700 dark:bg-slate-900">
                            <div class="rounded-xl bg-slate-50 px-3 py-3 dark:bg-slate-800"><p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ user.name }}</p><p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ user.email }}</p></div>
                            <Link :href="route('profile.edit')" class="mt-2 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800" @click="showDropdown = false"><svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>إعدادات الحساب</Link>
                            <Link :href="route('settings.index')" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800" @click="showDropdown = false"><svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>إعدادات المنصة</Link>
                            <div class="my-2 h-px bg-slate-100 dark:bg-slate-800"></div>
                            <Link :href="route('logout')" method="post" as="button" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-right text-sm font-bold text-rose-600 transition hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-500/10"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>تسجيل الخروج</Link>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </header>
</template>
