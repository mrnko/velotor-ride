<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import StatCard from '../../Components/StatCard.vue';
import RankingTable from '../../Components/RankingTable.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    year: Number,
    availableYears: Array,
    totalDistance: Number,
    participantsCount: Number,
    totalTorcoins: Number,
    rankings: Array,
});

function goToYear(event) {
    router.get(`/year/${event.target.value}`, {}, { preserveScroll: true });
}

function formatKm(value) {
    return new Intl.NumberFormat('uk-UA', { maximumFractionDigits: 0 }).format(value);
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold text-white">Рейтинг {{ year }} року</h1>

            <select
                :value="year"
                class="rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                @change="goToYear"
            >
                <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <div class="grid grid-cols-3 gap-3 sm:max-w-lg">
            <StatCard label="Дистанція" :value="`${formatKm(totalDistance)} км`" />
            <StatCard label="Учасників" :value="participantsCount" />
            <StatCard label="Torcoins" :value="totalTorcoins" />
        </div>

        <RankingTable :rows="rankings" :show-rides="true" :show-torcoins="true" />
    </div>
</template>
