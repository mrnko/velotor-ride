<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    value: { type: Number, required: true },
    decimals: { type: Number, default: 0 },
    suffix: { type: String, default: '' },
    duration: { type: Number, default: 900 },
});

const el = ref(null);
const display = ref(0);
let observer;
let frame;
let animated = false;

function formatNumber(v) {
    return new Intl.NumberFormat('uk-UA', {
        minimumFractionDigits: props.decimals,
        maximumFractionDigits: props.decimals,
    }).format(v);
}

function animate() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        display.value = props.value;
        return;
    }

    cancelAnimationFrame(frame);
    const start = performance.now();
    const from = display.value;
    const to = props.value;

    const step = (now) => {
        const progress = Math.min((now - start) / props.duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        display.value = from + (to - from) * eased;

        if (progress < 1) {
            frame = requestAnimationFrame(step);
        } else {
            display.value = to;
        }
    };

    frame = requestAnimationFrame(step);
}

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting && !animated) {
                    animated = true;
                    animate();
                    observer.disconnect();
                }
            });
        },
        { threshold: 0.3 },
    );

    if (el.value) observer.observe(el.value);
});

onUnmounted(() => {
    observer?.disconnect();
    cancelAnimationFrame(frame);
});

watch(
    () => props.value,
    () => {
        if (animated) animate();
    },
);
</script>

<template>
    <span ref="el">{{ formatNumber(display) }}{{ suffix }}</span>
</template>
