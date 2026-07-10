<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import StatCard from '../../Components/StatCard.vue';
import RankingTable from '../../Components/RankingTable.vue';

defineOptions({ layout: AppLayout });

defineProps({
    year: Number,
    availableYears: Array,
    totalDistance: Number,
    participantsCount: Number,
    totalTorcoins: Number,
    rankings: Array,
});

function goToYear(event) {
    router.get(`/stat/year/${event.target.value}`, {}, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold text-brand-950">Рейтинг {{ year }} року</h1>

            <select
                :value="year"
                class="rounded-xl border border-brand-100 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200"
                @change="goToYear"
            >
                <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <div data-reveal class="grid grid-cols-3 gap-3 sm:max-w-lg lg:max-w-none">
            <StatCard label="Дистанція" :value="totalDistance" suffix=" км" />
            <StatCard label="Учасників" :value="participantsCount" />
            <StatCard label="Torcoins" :value="totalTorcoins" />
        </div>

        <div data-reveal>
            <RankingTable :rows="rankings" :show-rides="true" :show-torcoins="true" />
        </div>
    </div>
</template>
