<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';
import StatCard from '../Components/StatCard.vue';
import Avatar from '../Components/Avatar.vue';
import CountUp from '../Components/CountUp.vue';
import WeeklyChart from '../Components/WeeklyChart.vue';
import RankingTable from '../Components/RankingTable.vue';

defineOptions({ layout: AppLayout });

defineProps({
    period: Object,
    previous: { type: Object, default: null },
    next: { type: Object, default: null },
    weekTotalDistance: Number,
    weekTorcoins: Number,
    yearTotalDistance: Number,
    activeParticipants: Number,
    leader: Object,
    weekRankings: { type: Array, default: () => [] },
    allTimeTop10: { type: Array, default: () => [] },
    totalParticipants: Number,
    clubStats: Object,
    weeklyChart: { type: Array, default: () => [] },
});

function formatNumber(value, digits = 0) {
    return new Intl.NumberFormat('uk-UA', { maximumFractionDigits: digits }).format(value ?? 0);
}

function formatDate(value) {
    return new Date(`${value}T12:00:00`).toLocaleDateString('uk-UA', { day: 'numeric', month: 'long' });
}

function goToWeek(week) {
    if (!week) return;
    router.get(`/stat/weeks/${week.year}/${week.week_number}`, {}, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-10 sm:space-y-14">
        <section class="relative overflow-hidden rounded-[2rem] bg-brand-gradient py-6 text-white shadow-xl shadow-brand-900/10 sm:py-9 lg:py-12">
            <button
                v-if="previous"
                type="button"
                class="group absolute left-2 top-1/2 z-20 flex cursor-pointer h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 backdrop-blur transition hover:scale-110 hover:bg-white/25 sm:left-3 sm:h-11 sm:w-11"
                aria-label="Попередній тиждень"
                @click="goToWeek(previous)"
            >
                <svg class="h-5 w-5 text-white transition group-hover:-translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </button>
            <button
                v-if="next"
                type="button"
                class="group absolute right-2 top-1/2 z-20 flex cursor-pointer h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 backdrop-blur transition hover:scale-110 hover:bg-white/25 sm:right-3 sm:h-11 sm:w-11"
                aria-label="Наступний тиждень"
                @click="goToWeek(next)"
            >
                <svg class="h-5 w-5 text-white transition group-hover:translate-x-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
            </button>

            <div class="relative z-10 grid items-center gap-8 px-12 sm:px-16 lg:grid-cols-[1.35fr_0.65fr] lg:px-20">
                <div>
                    <div class="flex flex-col items-start gap-2">
                        <span class="rounded-full bg-gold-400 px-3 py-1 text-xs font-extrabold text-brand-950">Тиждень №{{ period.week_number }}</span>
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-bold uppercase tracking-wider backdrop-blur">{{ period.year }} рік</span>
                    </div>
                    <h1 class="mt-5 max-w-2xl text-2xl font-extrabold tracking-tight sm:text-3xl lg:text-4xl">Статистика велоклубу «ВелоТОР»</h1>
                    <p class="mt-3 text-sm text-brand-100 sm:text-base">
                        {{ formatDate(period.start_date) }} — {{ formatDate(period.end_date) }}
                    </p>
                </div>

                <div class="rounded-3xl border border-white/15 bg-white/10 p-5 backdrop-blur-sm sm:p-6">
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-100">Лідер тижня</p>
                    <div v-if="leader" class="mt-4 flex items-center gap-4">
                        <div class="relative">
                            <Avatar :src="leader.avatar_url" :name="leader.name" :initials="leader.initials" size="lg" />
                            <span class="absolute -right-2 -top-3 rotate-6 text-2xl drop-shadow-lg" aria-hidden="true">👑</span>
                        </div>
                        <div class="min-w-0">
                            <Link :href="`/stat/user/${leader.slug}`" class="block truncate text-lg font-extrabold transition-colors hover:text-gold-300">{{ leader.name }}</Link>
                            <p class="mt-1 text-sm text-brand-100">{{ formatNumber(leader.distance_km, 1) }} км · {{ leader.rides_count }} заїздів</p>
                        </div>
                    </div>
                    <p v-else class="mt-4 text-sm text-brand-100">Перший результат тижня ще попереду.</p>
                </div>
            </div>
            <div class="absolute -right-20 -top-24 h-72 w-72 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-28 left-1/3 h-64 w-64 rounded-full bg-gold-400/10"></div>
        </section>

        <section data-reveal>
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-500">Поточний тиждень</p>
                <h2 class="mt-1 text-2xl font-extrabold text-brand-950">Усі учасники</h2>
                <p class="mt-1 text-sm text-slate-500">Хто скільки накатав і скільки заїздів уже додав.</p>
            </div>
            <RankingTable :rows="weekRankings" :show-rides="true" :show-torcoins="true" />
        </section>

        <section data-reveal>
            <div class="mb-5">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-500">Поточний зріз</p>
                <h2 class="mt-1 text-2xl font-extrabold text-brand-950">Тиждень у цифрах</h2>
            </div>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <StatCard label="Кілометрів накатано" :value="weekTotalDistance" :decimals="1" suffix=" км" />
                <StatCard label="Torcoins за тиждень" :value="weekTorcoins" hint="зароблено учасниками" />
                <StatCard label="Активних учасників" :value="activeParticipants" :hint="`із ${totalParticipants} у клубі`" />
                <StatCard label="Кілометрів за рік" :value="yearTotalDistance" :decimals="1" suffix=" км" :hint="String(period.year)" />
            </div>
        </section>

        <section data-reveal>
            <div class="rounded-[2rem] border border-brand-100 bg-white p-5 shadow-sm sm:p-7">
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Уся дистанція</p>
                        <p class="mt-2 text-3xl font-extrabold text-brand-950"><CountUp :value="clubStats.total_distance" :decimals="1" /> <span class="text-base text-brand-500">км</span></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Усього заїздів</p>
                        <p class="mt-2 text-3xl font-extrabold text-brand-950"><CountUp :value="clubStats.total_rides" /></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Torcoins клубу</p>
                        <p class="mt-2 text-3xl font-extrabold text-brand-950"><CountUp :value="clubStats.total_torcoins" /></p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Тижнів в історії</p>
                        <p class="mt-2 text-3xl font-extrabold text-brand-950"><CountUp :value="clubStats.weeks_count" /></p>
                    </div>
                </div>
                <div class="mt-6 flex flex-wrap gap-3 border-t border-brand-50 pt-5">
                    <Link href="/stat/year" class="rounded-xl border border-brand-200 px-4 py-2.5 text-sm font-bold text-brand-700 transition hover:bg-brand-50">Статистика за рік</Link>
                    <Link href="/stat/all-time" class="rounded-xl border border-brand-200 px-4 py-2.5 text-sm font-bold text-brand-700 transition hover:bg-brand-50">Рейтинг за весь час</Link>
                    <Link href="/stat/weeks" class="rounded-xl border border-brand-200 px-4 py-2.5 text-sm font-bold text-brand-700 transition hover:bg-brand-50">Архів тижнів</Link>
                </div>
            </div>

            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                <WeeklyChart :data="weeklyChart" title="Кілометраж клубу за 12 тижнів" />
                <WeeklyChart :data="weeklyChart" title="Активні учасники за 12 тижнів" value-key="active_participants" unit="уч." />
            </div>
        </section>

        <section data-reveal>
            <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-brand-500">Клубна легенда</p>
                    <h2 class="mt-1 text-2xl font-extrabold text-brand-950">Топ-10 активних за весь час</h2>
                </div>
                <Link href="/stat/all-time" class="text-sm font-bold text-brand-600 hover:text-brand-700">Повний рейтинг →</Link>
            </div>
            <RankingTable :rows="allTimeTop10" :show-rides="true" :show-torcoins="true" :searchable="false" />
        </section>
    </div>
</template>
