<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import StatCard from '../../Components/StatCard.vue';
import ProgressBar from '../../Components/ProgressBar.vue';
import TorcoinBadge from '../../Components/TorcoinBadge.vue';
import EmptyState from '../../Components/EmptyState.vue';
import Avatar from '../../Components/Avatar.vue';
import WeeklyChart from '../../Components/WeeklyChart.vue';
import ShareButtons from '../../Components/ShareButtons.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    participant: Object,
    stats: Object,
    history: Array,
    bestWeeks: Array,
    weeklyChart: { type: Array, default: () => [] },
});

function formatKm(value) {
    return new Intl.NumberFormat('uk-UA', { maximumFractionDigits: 1 }).format(value);
}

function formatDate(value) {
    if (!value) return 'ще не було активності';
    return new Date(value).toLocaleString('uk-UA', { day: 'numeric', month: 'long', hour: '2-digit', minute: '2-digit' });
}

const shareUrl = computed(() => props.participant.profile_url ?? (typeof window !== 'undefined' ? window.location.href : ''));
const shareText = computed(
    () => `${props.participant.display_name} у велоклубі «ВелоТОР»: ${formatKm(props.stats.all_time_distance)} км та ${props.stats.torcoins_all_time} Torcoins 🚴`,
);
</script>

<template>
    <div class="space-y-8">
        <div data-reveal class="surface-card flex flex-col gap-4 rounded-3xl p-5 sm:flex-row sm:items-center sm:gap-6 sm:p-7">
            <Avatar :src="participant.avatar_url" :name="participant.display_name" :initials="participant.initials" size="xl" :title="participant.display_name" />
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-extrabold text-brand-950 sm:text-3xl">{{ participant.display_name }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                    <span v-if="participant.telegram_username">@{{ participant.telegram_username }} · </span>
                    Остання активність: {{ formatDate(participant.last_seen_at) }}
                </p>
                <div class="mt-4">
                    <ShareButtons :url="shareUrl" :text="shareText" />
                </div>
            </div>
        </div>

        <div data-reveal class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <StatCard label="Цей тиждень" :value="stats.current_week_distance" :decimals="1" suffix=" км" :hint="stats.rank_week ? `місце #${stats.rank_week}` : ''" />
            <StatCard label="Минулий тиждень" :value="stats.last_week_distance" :decimals="1" suffix=" км" />
            <StatCard label="За рік" :value="stats.year_distance" :decimals="1" suffix=" км" :hint="stats.rank_year ? `місце #${stats.rank_year}` : ''" />
            <StatCard label="Усього" :value="stats.all_time_distance" :decimals="1" suffix=" км" />
        </div>

        <div data-reveal class="grid gap-3 sm:grid-cols-2">
            <div class="surface-card rounded-2xl p-4 sm:p-5">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Torcoins</p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    <TorcoinBadge :value="stats.torcoins_year" />
                    <span class="text-xs text-slate-400">за рік</span>
                    <TorcoinBadge :value="stats.torcoins_all_time" />
                    <span class="text-xs text-slate-400">за весь час</span>
                </div>
            </div>

            <div class="surface-card rounded-2xl p-4 sm:p-5">
                <ProgressBar :percent="stats.progress_percent" label="До наступного Torcoin" />
                <p class="mt-2 text-xs text-slate-400">{{ formatKm(stats.km_to_next_coin) }} км до наступного Torcoin</p>
            </div>
        </div>

        <section data-reveal>
            <WeeklyChart :data="weeklyChart" title="Кілометраж по тижнях" />
        </section>

        <section data-reveal>
            <h2 class="mb-3 text-lg font-bold text-brand-950">🏅 Найкращі тижні</h2>
            <EmptyState v-if="!bestWeeks.length" title="Ще немає результатів" icon="🏅" />
            <ul v-else class="grid gap-2 sm:grid-cols-2">
                <li v-for="week in bestWeeks" :key="`${week.year}-${week.week_number}`" class="surface-card rounded-xl p-3">
                    <Link :href="`/stat/weeks/${week.year}/${week.week_number}`" class="flex items-center justify-between gap-2 text-brand-950 hover:text-brand-600">
                        <span class="text-sm font-medium">{{ week.title }}</span>
                        <span class="font-semibold tabular-nums">{{ formatKm(week.distance_km) }} км</span>
                    </Link>
                </li>
            </ul>
        </section>

        <section data-reveal>
            <h2 class="mb-3 text-lg font-bold text-brand-950">📜 Історія по тижнях</h2>
            <EmptyState v-if="!history.length" title="Історії ще немає" icon="📜" />
            <div v-else class="surface-card overflow-hidden rounded-2xl">
                <table class="w-full text-sm">
                    <thead class="bg-brand-50/60 text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Тиждень</th>
                            <th class="px-4 py-3 text-right font-semibold">Км</th>
                            <th class="px-4 py-3 text-right font-semibold">Заїздів</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-50">
                        <tr v-for="week in history" :key="`${week.year}-${week.week_number}`" class="hover:bg-brand-50/50">
                            <td class="px-4 py-3">
                                <Link :href="`/stat/weeks/${week.year}/${week.week_number}`" class="font-medium text-brand-950 hover:text-brand-600">{{ week.title }}</Link>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-700">{{ formatKm(week.distance_km) }}</td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-400">{{ week.rides_count }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
