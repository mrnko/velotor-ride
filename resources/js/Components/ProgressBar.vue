<script setup>
const props = defineProps({
    percent: { type: Number, required: true },
    label: { type: String, default: '' },
});
</script>

<template>
    <div>
        <div v-if="label" class="mb-1.5 flex justify-between text-xs text-slate-400">
            <span>{{ label }}</span>
            <span>{{ Math.round(percent) }}%</span>
        </div>
        <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-800">
            <div
                class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-300 transition-all duration-500"
                :style="{ width: `${Math.min(100, Math.max(0, props.percent))}%` }"
            />
        </div>
    </div>
</template>
