<template>
    <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
        <!-- خلفية -->
        <div
            class="absolute inset-0 bg-black/50 backdrop-blur-sm"
            @click="$emit('close')"
        />

        <!-- النموذج -->
        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-800">
            <div class="mb-4 flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ title }}
                </h3>
            </div>

            <p class="text-sm text-gray-600 dark:text-gray-400">
                {{ message }}
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <button
                    @click="$emit('close')"
                    class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                    :disabled="loading"
                >
                    إلغاء
                </button>
                <button
                    @click="$emit('confirm')"
                    class="flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-70"
                    :disabled="loading"
                >
                    <svg v-if="loading" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                    </svg>
                    <span>{{ loading ? 'جاري الحذف...' : 'تأكيد الحذف' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: Boolean,
    title: String,
    message: String,
    loading: Boolean,
});

defineEmits(['close', 'confirm']);
</script>
