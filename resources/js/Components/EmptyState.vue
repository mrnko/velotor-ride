<script setup>
defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: String, default: '🚲' },
});
</script>

<template>
    <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-slate-800 px-6 py-16 text-center">
        <div class="mb-3 text-4xl" aria-hidden="true">{{ icon }}</div>
        <p class="text-base font-medium text-slate-200">{{ title }}</p>
        <p v-if="description" class="mt-1 max-w-sm text-sm text-slate-500">{{ description }}</p>
    </div>
</template>
