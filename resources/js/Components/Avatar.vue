<script setup>
defineProps({
    src: { type: String, default: null },
    name: { type: String, default: '' },
    initials: { type: String, default: '?' },
    size: { type: String, default: 'md' }, // sm | md | lg | xl
});

const sizeClasses = {
    sm: 'h-8 w-8 text-[11px]',
    md: 'h-10 w-10 text-sm',
    lg: 'h-14 w-14 text-lg',
    xl: 'h-24 w-24 text-3xl',
};
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full ring-1 ring-brand-200"
        :class="sizeClasses[size]"
    >
        <img v-if="src" :src="src" :alt="name" class="h-full w-full object-cover" />
        <span v-else class="bg-brand-gradient flex h-full w-full items-center justify-center font-extrabold tracking-tight text-white">
            {{ initials || '?' }}
        </span>
    </span>
</template>
