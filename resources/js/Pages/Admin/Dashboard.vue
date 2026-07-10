<script setup>
import { router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import StatCard from '../../Components/StatCard.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    activePeriod: Object,
    participantsCount: Number,
    rideResultsCount: Number,
    recentErrorsCount: Number,
});

function recalculate() {
    router.post('/admin/recalculate', {}, { preserveScroll: true });
}
</script>

<template>
    <div class="space-y-6">
        <h1 class="text-2xl font-extrabold text-white">Огляд</h1>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Поточний тиждень</p>
            <p class="mt-1 text-lg font-bold text-white">{{ activePeriod.title }}</p>
            <p class="text-sm text-slate-500">{{ activePeriod.start_date }} – {{ activePeriod.end_date }}</p>
            <p class="mt-2 text-sm text-slate-300">Дистанція: <strong>{{ activePeriod.total_distance }} км</strong></p>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <StatCard label="Учасників" :value="participantsCount" />
            <StatCard label="Результатів" :value="rideResultsCount" />
            <StatCard label="Помилок бота (24г)" :value="recentErrorsCount" />
        </div>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <h2 class="font-semibold text-white">Перерахунок статистики</h2>
            <p class="mt-1 text-sm text-slate-500">
                Перерахувати total_distance для всіх тижнів заново з ride_results — корисно
                після ручного редагування чи видалення результатів.
            </p>
            <button
                type="button"
                class="mt-3 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400"
                @click="recalculate"
            >
                Перерахувати
            </button>
        </div>
    </div>
</template>
