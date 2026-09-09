<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
    >
        <!-- Backdrop -->
        <div
            class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm"
            @click="emitClose"
        />

        <!-- Modal panel -->
        <div
            class="relative max-h-[92vh] w-full transform overflow-hidden rounded-3xl border border-white/10 bg-white shadow-2xl shadow-slate-950/20 transition-all dark:border-slate-800 dark:bg-slate-900"
            :class="widthClass"
        >
            <!--
                Backward-compatible rendering:

                1) Modern/named-slot usage:
                   <template #title>...</template>
                   <template #content>...</template>

                2) Legacy/self-contained usage:
                   <Modal> ...entire modal body... </Modal>

                This is important because Payments/CustomerReceivables,
                Payments/SupplierPayables and some Returns screens use the
                default slot, while many other screens use title/content.
            -->

            <template v-if="hasNamedContent">
                <div
                    class="flex items-center justify-between border-b border-slate-200/80 px-5 py-4 dark:border-slate-800 sm:px-6"
                >
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                        <slot name="title">العنوان</slot>
                    </h3>

                    <button
                        type="button"
                        aria-label="إغلاق"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 dark:hover:bg-slate-800 dark:hover:text-slate-200"
                        @click="emitClose"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            />
                        </svg>
                    </button>
                </div>

                <div class="max-h-[calc(92vh-73px)] overflow-y-auto p-5 sm:p-6">
                    <slot name="content" />
                </div>
            </template>

            <!-- Legacy/default-slot modal. The child owns its internal padding/header. -->
            <template v-else>
                <div class="max-h-[92vh] overflow-y-auto">
                    <slot />
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, useSlots } from 'vue';

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: 'lg',
    },
});

const emit = defineEmits(['close']);
const slots = useSlots();

const hasNamedContent = computed(() => Boolean(slots.content));

const widthClass = computed(() => ({
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
}[props.maxWidth] || 'max-w-lg'));

const emitClose = () => {
    emit('close');
};
</script>
