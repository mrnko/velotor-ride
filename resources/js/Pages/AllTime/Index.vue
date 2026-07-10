<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
import StatCard from '../../Components/StatCard.vue';
import RankingTable from '../../Components/RankingTable.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    totalDistance: Number,
    totalTorcoins: Number,
    participantsCount: Number,
    rankings: Array,
});

function formatKm(value) {
    return new Intl.NumberFormat('uk-UA', { maximumFractionDigits: 0 }).format(value);
}
</script>

<template>
    <div class="space-y-6">
        <h1 class="text-2xl font-extrabold text-white">🌍 Рейтинг за весь час</h1>

        <div class="grid grid-cols-3 gap-3 sm:max-w-lg">
            <StatCard label="Дистанція" :value="`${formatKm(totalDistance)} км`" />
            <StatCard label="Учасників" :value="participantsCount" />
            <StatCard label="Torcoins" :value="totalTorcoins" />
        </div>

        <RankingTable :rows="rankings" :show-rides="true" :show-torcoins="true" :show-weeks-active="true" :show-last-activity="true" />
    </div>
</template>
