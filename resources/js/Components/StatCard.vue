<script setup>
import CountUp from './CountUp.vue';

defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    decimals: { type: Number, default: 0 },
    suffix: { type: String, default: '' },
    hint: { type: String, default: '' },
});
</script>

<template>
    <div class="surface-card rounded-2xl p-4 sm:p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ label }}</p>
        <p class="mt-1.5 text-2xl font-extrabold text-brand-950 sm:text-3xl">
            <CountUp v-if="typeof value === 'number'" :value="value" :decimals="decimals" :suffix="suffix" />
            <template v-else>{{ value }}</template>
        </p>
        <p v-if="hint" class="mt-1 text-xs text-slate-400">{{ hint }}</p>
    </div>
</template>
