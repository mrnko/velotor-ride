<script setup>
const props = defineProps({
    percent: { type: Number, required: true },
    label: { type: String, default: '' },
});
</script>

<template>
    <div>
        <div v-if="label" class="mb-1.5 flex justify-between text-xs text-slate-500">
            <span>{{ label }}</span>
            <span>{{ Math.round(percent) }}%</span>
        </div>
        <div class="h-2.5 w-full overflow-hidden rounded-full bg-brand-100">
            <div
                class="bg-brand-gradient h-full rounded-full transition-all duration-500"
                :style="{ width: `${Math.min(100, Math.max(0, props.percent))}%` }"
            />
        </div>
    </div>
</template>
