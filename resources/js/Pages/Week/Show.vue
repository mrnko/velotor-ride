<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import StatCard from '../../Components/StatCard.vue';
import RankingTable from '../../Components/RankingTable.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    period: Object,
    participantsCount: Number,
    rankings: Array,
    previous: Object,
    next: Object,
});

function formatKm(value) {
    return new Intl.NumberFormat('uk-UA', { maximumFractionDigits: 0 }).format(value);
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('uk-UA', { day: 'numeric', month: 'long' });
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Link v-if="previous" :href="`/weeks/${previous.year}/${previous.week_number}`" class="rounded-lg border border-slate-800 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-900">
                ← Тиждень {{ previous.week_number }}
            </Link>
            <span v-else class="text-sm text-slate-600">Це перший тиждень</span>

            <span
                class="rounded-full px-3 py-1 text-xs font-semibold"
                :class="period.status === 'active' ? 'bg-emerald-400/10 text-emerald-300 ring-1 ring-inset ring-emerald-400/30' : 'bg-slate-800 text-slate-400'"
            >
                {{ period.status === 'active' ? 'Поточний тиждень' : 'Завершено' }}
            </span>

            <Link v-if="next" :href="`/weeks/${next.year}/${next.week_number}`" class="rounded-lg border border-slate-800 px-3 py-1.5 text-sm text-slate-300 hover:bg-slate-900">
                Тиждень {{ next.week_number }} →
            </Link>
            <span v-else class="text-sm text-slate-600">Наступного ще немає</span>
        </div>

        <div>
            <h1 class="text-2xl font-extrabold text-white">{{ period.title }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ formatDate(period.start_date) }} – {{ formatDate(period.end_date) }}</p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:max-w-sm">
            <StatCard label="Загальна дистанція" :value="`${formatKm(period.total_distance)} км`" />
            <StatCard label="Учасників" :value="participantsCount" />
        </div>

        <RankingTable :rows="rankings" :show-rides="true" />
    </div>
</template>
