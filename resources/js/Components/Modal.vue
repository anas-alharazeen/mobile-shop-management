<template>
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <!-- خلفية -->
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="$emit('close')"></div>

        <!-- المحتوى -->
        <div class="relative w-full max-w-lg transform overflow-hidden rounded-2xl bg-white p-6 shadow-2xl transition-all dark:bg-gray-800">
            <!-- العنوان -->
            <div class="mb-4 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    <slot name="title">العنوان</slot>
                </h3>
                <button
                    @click="$emit('close')"
                    class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- المحتوى -->
            <slot name="content"></slot>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: Boolean,
});

defineEmits(['close']);
</script>
