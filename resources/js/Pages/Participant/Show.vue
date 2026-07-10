<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import StatCard from '../../Components/StatCard.vue';
import ProgressBar from '../../Components/ProgressBar.vue';
import TorcoinBadge from '../../Components/TorcoinBadge.vue';
import EmptyState from '../../Components/EmptyState.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    participant: Object,
    stats: Object,
    history: Array,
    bestWeeks: Array,
});

function formatKm(value) {
    return new Intl.NumberFormat('uk-UA', { maximumFractionDigits: 1 }).format(value);
}

function formatDate(value) {
    if (!value) return 'ще не було активності';
    return new Date(value).toLocaleString('uk-UA', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <div class="space-y-8">
        <div>
            <h1 class="text-2xl font-extrabold text-white">{{ participant.display_name }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                <span v-if="participant.telegram_username">@{{ participant.telegram_username }} · </span>
                Остання активність: {{ formatDate(participant.last_seen_at) }}
            </p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <StatCard label="Цей тиждень" :value="`${formatKm(stats.current_week_distance)} км`" :hint="stats.rank_week ? `місце #${stats.rank_week}` : ''" />
            <StatCard label="Минулий тиждень" :value="`${formatKm(stats.last_week_distance)} км`" />
            <StatCard label="За рік" :value="`${formatKm(stats.year_distance)} км`" :hint="stats.rank_year ? `місце #${stats.rank_year}` : ''" />
            <StatCard label="Усього" :value="`${formatKm(stats.all_time_distance)} км`" />
        </div>

        <div class="grid gap-3 sm:grid-cols-2">
            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 sm:p-5">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Torcoins</p>
                <div class="mt-2 flex gap-2">
                    <TorcoinBadge :value="stats.torcoins_year" />
                    <span class="self-center text-xs text-slate-500">за рік</span>
                    <TorcoinBadge :value="stats.torcoins_all_time" />
                    <span class="self-center text-xs text-slate-500">за весь час</span>
                </div>
            </div>

            <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-4 sm:p-5">
                <ProgressBar :percent="stats.progress_percent" label="До наступного Torcoin" />
                <p class="mt-2 text-xs text-slate-500">{{ formatKm(stats.km_to_next_coin) }} км до наступного Torcoin</p>
            </div>
        </div>

        <section>
            <h2 class="mb-3 text-lg font-bold text-white">🏅 Найкращі тижні</h2>
            <EmptyState v-if="!bestWeeks.length" title="Ще немає результатів" icon="🏅" />
            <ul v-else class="grid gap-2 sm:grid-cols-2">
                <li v-for="week in bestWeeks" :key="`${week.year}-${week.week_number}`" class="rounded-xl border border-slate-800 bg-slate-900/50 p-3">
                    <Link :href="`/weeks/${week.year}/${week.week_number}`" class="flex items-center justify-between gap-2 hover:text-amber-300">
                        <span class="text-sm font-medium text-slate-100">{{ week.title }}</span>
                        <span class="font-semibold tabular-nums text-slate-200">{{ formatKm(week.distance_km) }} км</span>
                    </Link>
                </li>
            </ul>
        </section>

        <section>
            <h2 class="mb-3 text-lg font-bold text-white">📜 Історія по тижнях</h2>
            <EmptyState v-if="!history.length" title="Історії ще немає" icon="📜" />
            <div v-else class="overflow-hidden rounded-2xl border border-slate-800">
                <table class="w-full text-sm">
                    <thead class="bg-slate-900/80 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">Тиждень</th>
                            <th class="px-4 py-3 text-right font-medium">Км</th>
                            <th class="px-4 py-3 text-right font-medium">Заїздів</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        <tr v-for="week in history" :key="`${week.year}-${week.week_number}`" class="hover:bg-slate-900/50">
                            <td class="px-4 py-3">
                                <Link :href="`/weeks/${week.year}/${week.week_number}`" class="text-slate-100 hover:text-amber-300">{{ week.title }}</Link>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-200">{{ formatKm(week.distance_km) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-400">{{ week.rides_count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
