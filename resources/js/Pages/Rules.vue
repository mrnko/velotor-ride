<script setup>
import AppLayout from '../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    maxDistanceKm: Number,
    duplicateWindowMinutes: Number,
    torcoinKmPerCoin: Number,
    timezone: String,
});
</script>

<template>
    <div class="space-y-8">
        <h1 class="text-2xl font-extrabold text-brand-950">Правила та довідка</h1>

        <section class="surface-card rounded-2xl p-5">
            <h2 class="text-lg font-bold text-brand-950">📩 Як надіслати результат</h2>
            <p class="mt-2 text-sm text-slate-500">Просто напиши в чат клубу одне з повідомлень:</p>
            <ul class="mt-3 space-y-1.5 font-mono text-sm text-brand-600">
                <li>результат 25</li>
                <li>результат 25 км</li>
                <li>результат 25.5</li>
                <li>результат 25,5</li>
                <li>result 25</li>
                <li>+25 км</li>
            </ul>
            <p class="mt-3 text-sm text-slate-500">
                Максимум за одне повідомлення — <strong class="text-brand-950">{{ maxDistanceKm }} км</strong>.
                Усі інші повідомлення в чаті бот просто ігнорує.
            </p>
        </section>

        <section class="surface-card rounded-2xl p-5">
            <h2 class="text-lg font-bold text-brand-950">📅 Як рахуються тижні</h2>
            <p class="mt-2 text-sm text-slate-500">
                Тиждень клубу триває з понеділка 00:00 до наступного понеділка 00:00 за часовим поясом
                {{ timezone }}. Щопонеділка опівночі бот підбиває підсумки, надсилає звіт у чат
                і одразу відкриває новий тиждень — тому дані тижня завжди привʼязані до конкретних
                дат, а не просто до номера.
            </p>
        </section>

        <section class="surface-card rounded-2xl p-5">
            <h2 class="text-lg font-bold text-brand-950">🪙 Як рахуються Torcoins</h2>
            <p class="mt-2 text-sm text-slate-500">
                1 Torcoin за кожні {{ torcoinKmPerCoin }} км (округлення вниз). Наприклад, 99 км —
                0 Torcoins, 100 км — 1 Torcoin, 250 км — 2 Torcoins.
            </p>
        </section>

        <section class="surface-card rounded-2xl p-5">
            <h2 class="text-lg font-bold text-brand-950">⚠️ Захист від дублів</h2>
            <p class="mt-2 text-sm text-slate-500">
                Якщо надіслати майже однаковий результат повторно протягом {{ duplicateWindowMinutes }}
                хвилин, бот попередить, що це схоже на дубль, і не зарахує його вдруге.
            </p>
        </section>
    </div>
</template>
