<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import StatCard from '../Components/StatCard.vue';
import EmptyState from '../Components/EmptyState.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    period: Object,
    weekTotalDistance: Number,
    yearTotalDistance: Number,
    activeParticipants: Number,
    top5: Array,
    totalParticipants: Number,
});

function formatKm(value) {
    return new Intl.NumberFormat('uk-UA', { maximumFractionDigits: 0 }).format(value);
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('uk-UA', { day: 'numeric', month: 'long' });
}

const medal = ['🥇', '🥈', '🥉'];
</script>

<template>
    <div class="space-y-10">
        <section class="text-center sm:text-left">
            <p class="text-sm font-semibold uppercase tracking-widest text-amber-400">Велоклуб</p>
            <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                Крутимо педалі разом 🚴
            </h1>
            <p class="mx-auto mt-3 max-w-xl text-slate-400 sm:mx-0">
                Надсилай свій кілометраж прямо в Telegram-чат — бот порахує рейтинг тижня,
                року та Torcoins автоматично.
            </p>
            <div class="mt-6 flex flex-wrap justify-center gap-3 sm:justify-start">
                <Link href="/week" class="rounded-lg bg-amber-500 px-4 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-400">
                    Рейтинг тижня
                </Link>
                <Link href="/weeks" class="rounded-lg border border-slate-700 px-4 py-2.5 text-sm font-semibold text-slate-200 transition hover:bg-slate-900">
                    Архів тижнів
                </Link>
            </div>
        </section>

        <section>
            <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold text-white">{{ period.title }}</h2>
                <span class="text-sm text-slate-500">{{ formatDate(period.start_date) }} – {{ formatDate(period.end_date) }}</span>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <StatCard label="Км за тиждень" :value="formatKm(weekTotalDistance)" />
                <StatCard label="Км за рік" :value="formatKm(yearTotalDistance)" :hint="String(period.year)" />
                <StatCard label="Активні цього тижня" :value="activeParticipants" />
                <StatCard label="Учасників клубу" :value="totalParticipants" />
            </div>
        </section>

        <section>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-lg font-bold text-white">🏆 Топ-5 тижня</h2>
                <Link href="/week" class="text-sm font-medium text-amber-400 hover:text-amber-300">Повний рейтинг →</Link>
            </div>

            <EmptyState
                v-if="!top5.length"
                title="Ще ніхто не здав результат"
                description="Напиши в чат «результат 10 км», щоб стати першим цього тижня."
            />

            <ol v-else class="space-y-2">
                <li
                    v-for="row in top5"
                    :key="row.participant_id"
                    class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-900/50 p-3"
                >
                    <span class="w-7 text-center text-lg">{{ medal[row.rank - 1] ?? row.rank }}</span>
                    <Link :href="`/participants/${row.participant_id}`" class="flex-1 truncate font-medium text-slate-100 hover:text-amber-300">
                        {{ row.name }}
                    </Link>
                    <span class="font-semibold tabular-nums text-slate-200">{{ formatKm(row.distance_km) }} км</span>
                </li>
            </ol>
        </section>
    </div>
</template>
