<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
import StatCard from '../../Components/StatCard.vue';
import RankingTable from '../../Components/RankingTable.vue';

defineOptions({ layout: AppLayout });

defineProps({
    totalDistance: Number,
    totalTorcoins: Number,
    participantsCount: Number,
    rankings: Array,
});
</script>

<template>
    <div class="space-y-6">
        <h1 class="text-2xl font-extrabold text-brand-950">🌍 Рейтинг за весь час</h1>

        <div data-reveal class="grid grid-cols-3 gap-3 sm:max-w-lg lg:max-w-none">
            <StatCard label="Дистанція" :value="totalDistance" suffix=" км" />
            <StatCard label="Учасників" :value="participantsCount" />
            <StatCard label="Torcoins" :value="totalTorcoins" :decimals="2" />
        </div>

        <div data-reveal>
            <RankingTable :rows="rankings" :show-rides="true" :show-torcoins="true" :show-weeks-active="true" :show-last-activity="true" />
        </div>
    </div>
</template>
