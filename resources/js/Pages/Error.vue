<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    status: { type: Number, default: 404 },
});

const messages = {
    403: {
        title: 'Доступ закрито',
        text: 'У тебе немає прав переглядати цю сторінку.',
    },
    404: {
        title: 'Ой, тут порожньо — такий маршрут ще не прокладено',
        text: 'Сторінка, яку ти шукаєш, не існує або вже переїхала. Перевір адресу або повертайся на статистику клубу.',
    },
    419: {
        title: 'Сесія закінчилась',
        text: 'Сторінка застаріла. Онови сторінку і спробуй ще раз.',
    },
    500: {
        title: 'Щось зламалось на нашій стороні',
        text: 'Уже розбираємось. Спробуй оновити сторінку за хвилину.',
    },
    503: {
        title: 'Технічні роботи',
        text: 'Сайт тимчасово недоступний. Ми скоро повернемось у сідло.',
    },
};

const content = messages[props.status] ?? messages[404];
</script>

<template>
    <div class="flex flex-col items-center justify-center gap-6 px-4 py-16 text-center sm:py-24">
        <div data-reveal class="relative">
            <span class="text-gradient-brand text-7xl font-black leading-none tracking-tight sm:text-8xl lg:text-9xl">{{ status }}</span>
            <span class="absolute -right-6 -top-2 rotate-12 text-4xl sm:-right-8 sm:-top-3 sm:text-5xl" aria-hidden="true">🚴</span>
        </div>

        <div data-reveal class="max-w-md space-y-3">
            <h1 class="text-xl font-extrabold text-brand-950 sm:text-2xl">{{ content.title }}</h1>
            <p class="text-sm text-slate-500 sm:text-base">{{ content.text }}</p>
        </div>

        <div data-reveal class="flex flex-wrap items-center justify-center gap-3">
            <Link href="/stat" class="rounded-xl bg-brand-600 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-700">До статистики клубу</Link>
            <Link href="/" class="rounded-xl border border-brand-200 px-5 py-2.5 text-sm font-bold text-brand-700 transition hover:bg-brand-50">На головну</Link>
        </div>
    </div>
</template>
