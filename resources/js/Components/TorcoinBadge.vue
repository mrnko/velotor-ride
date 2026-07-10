<script setup>
defineProps({
    value: { type: Number, required: true },
    size: { type: String, default: 'md' }, // sm | md
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full bg-amber-400/10 font-semibold text-amber-300 ring-1 ring-inset ring-amber-400/30"
        :class="size === 'sm' ? 'px-2 py-0.5 text-xs' : 'px-2.5 py-1 text-sm'"
    >
        <span aria-hidden="true">🪙</span>
        {{ value }}
    </span>
</template>
