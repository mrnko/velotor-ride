<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { router } from '@inertiajs/vue3';

// YouTube-style top loading bar that fills left→right during Inertia
// navigation, plus a small spinning circle in the top-right corner.
const width = ref(0);
const visible = ref(false);
let hideTimer = null;
let trickle = null;

function start() {
    clearTimeout(hideTimer);
    visible.value = true;
    width.value = 8;
    trickle = setInterval(() => {
        if (width.value < 90) {
            width.value = Math.min(90, width.value + Math.random() * 6 + 1);
        }
    }, 300);
}

function onProgress(event) {
    const pct = event?.detail?.progress?.percentage;
    if (pct) {
        width.value = Math.max(width.value, Math.floor(pct * 0.9));
    }
}

function finish() {
    clearInterval(trickle);
    width.value = 100;
    hideTimer = setTimeout(() => {
        visible.value = false;
        width.value = 0;
    }, 400);
}

let cleanups = [];

onMounted(() => {
    cleanups = [
        router.on('start', start),
        router.on('progress', onProgress),
        router.on('finish', finish),
    ];

    // Brief flash on the very first page load.
    visible.value = true;
    width.value = 100;
    hideTimer = setTimeout(() => {
        visible.value = false;
        width.value = 0;
    }, 500);
});

onUnmounted(() => {
    cleanups.forEach((off) => off && off());
    clearInterval(trickle);
    clearTimeout(hideTimer);
});
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 top-0 z-[100]">
        <div
            class="bg-brand-gradient h-[3px] shadow-[0_0_10px_rgba(0,50,249,0.6)] transition-[width,opacity] duration-300 ease-out"
            :style="{ width: width + '%', opacity: visible ? 1 : 0 }"
        ></div>
        <div
            class="absolute right-3 top-2 h-5 w-5 rounded-full border-2 border-brand-200 border-t-brand-600 border-r-gold-400 transition-opacity duration-300"
            :class="visible ? 'animate-spin opacity-100' : 'opacity-0'"
        ></div>
    </div>
</template>
