<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium"
        :style="{
            backgroundColor: `${color}15`,
            color: color
        }"
    >
        <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: color }" />
        {{ label }}
    </span>
</template>

<script setup>
defineProps({
    label: String,
    color: String
});
</script>
