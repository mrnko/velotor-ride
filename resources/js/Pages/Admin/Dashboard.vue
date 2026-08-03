<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../Layouts/AdminLayout.vue';
import StatCard from '../../Components/StatCard.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    activePeriod: Object,
    participantsCount: Number,
    rideResultsCount: Number,
    recentErrorsCount: Number,
    rollbackPeriod: Object,
});

function recalculate() {
    router.post('/admin/recalculate', {}, { preserveScroll: true });
}

function rollbackWeek() {
    if (!props.rollbackPeriod) return;

    const label = `${props.rollbackPeriod.week_number}/${props.rollbackPeriod.year}`;
    if (confirm(`Відкотити останню фіксацію та знову відкрити тиждень ${label}? Поточний тиждень має бути порожнім.`)) {
        router.post(
            '/admin/weekly-periods/rollback',
            {
                active_period_id: props.rollbackPeriod.active_period_id,
                previous_period_id: props.rollbackPeriod.previous_period_id,
            },
            { preserveScroll: true },
        );
    }
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold text-white">Огляд</h1>
            <Link href="/admin/ride-results/create" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-400">
                Додати результат
            </Link>
        </div>

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

        <div v-if="rollbackPeriod" class="rounded-2xl border border-rose-500/25 bg-rose-500/5 p-5">
            <h2 class="font-semibold text-white">Відкат останньої фіксації</h2>
            <p class="mt-1 text-sm text-slate-400">
                Знову відкрити тиждень {{ rollbackPeriod.week_number }}/{{ rollbackPeriod.year }}, щоб виправити або додати результати й зафіксувати його повторно.
            </p>
            <button
                type="button"
                class="mt-3 rounded-lg border border-rose-500/50 px-4 py-2 text-sm font-semibold text-rose-300 transition hover:bg-rose-500/10 hover:text-rose-200"
                @click="rollbackWeek"
            >
                Відкотити тиждень {{ rollbackPeriod.week_number }}/{{ rollbackPeriod.year }}
            </button>
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
