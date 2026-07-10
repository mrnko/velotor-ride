<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    hint: { type: String, default: '' },
});
</script>

<template>
    <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 sm:p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ label }}</p>
        <p class="mt-1.5 text-2xl font-bold text-white sm:text-3xl">{{ value }}</p>
        <p v-if="hint" class="mt-1 text-xs text-slate-500">{{ hint }}</p>
    </div>
</template>
