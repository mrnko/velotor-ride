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
        <h1 class="text-2xl font-extrabold text-white">Правила та довідка</h1>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <h2 class="text-lg font-bold text-white">📩 Як надіслати результат</h2>
            <p class="mt-2 text-sm text-slate-400">Просто напиши в чат клубу одне з повідомлень:</p>
            <ul class="mt-3 space-y-1.5 font-mono text-sm text-amber-300">
                <li>результат 25</li>
                <li>результат 25 км</li>
                <li>результат 25.5</li>
                <li>результат 25,5</li>
                <li>result 25</li>
                <li>+25 км</li>
            </ul>
            <p class="mt-3 text-sm text-slate-400">
                Максимум за одне повідомлення — <strong class="text-slate-200">{{ maxDistanceKm }} км</strong>.
                Усі інші повідомлення в чаті бот просто ігнорує.
            </p>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <h2 class="text-lg font-bold text-white">📅 Як рахуються тижні</h2>
            <p class="mt-2 text-sm text-slate-400">
                Тиждень клубу триває з неділі 00:00 до наступної неділі 00:00 за часовим поясом
                {{ timezone }}. Кожної неділі опівночі бот підбиває підсумки, надсилає звіт у чат
                і одразу відкриває новий тиждень — тому дані тижня завжди прив'язані до конкретних
                дат, а не просто до номера.
            </p>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <h2 class="text-lg font-bold text-white">🪙 Як рахуються Torcoins</h2>
            <p class="mt-2 text-sm text-slate-400">
                1 Torcoin за кожні {{ torcoinKmPerCoin }} км (округлення вниз). Наприклад, 99 км —
                0 Torcoins, 100 км — 1 Torcoin, 250 км — 2 Torcoins.
            </p>
        </section>

        <section class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <h2 class="text-lg font-bold text-white">⚠️ Захист від дублів</h2>
            <p class="mt-2 text-sm text-slate-400">
                Якщо надіслати майже однаковий результат повторно протягом {{ duplicateWindowMinutes }}
                хвилин, бот попередить, що це схоже на дубль, і не зарахує його вдруге.
            </p>
        </section>
    </div>
</template>
