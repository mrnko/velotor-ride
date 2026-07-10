<script setup>
import { Link, router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import EmptyState from '../../Components/EmptyState.vue';

defineOptions({ layout: AppLayout });

const props = defineProps({
    year: Number,
    availableYears: Array,
    weeks: Array,
});

function goToYear(event) {
    router.get(`/weeks/${event.target.value}`, {}, { preserveScroll: true });
}

function formatKm(value) {
    return new Intl.NumberFormat('uk-UA', { maximumFractionDigits: 0 }).format(value);
}

function formatDate(value) {
    return new Date(value).toLocaleDateString('uk-UA', { day: 'numeric', month: 'short' });
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold text-white">Архів тижнів</h1>

            <select
                :value="year"
                class="rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2 text-sm text-slate-100 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                @change="goToYear"
            >
                <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <EmptyState v-if="!weeks.length" title="У цьому році ще немає тижнів" icon="🗓️" />

        <ul v-else class="space-y-2">
            <li v-for="week in weeks" :key="`${week.year}-${week.week_number}`">
                <Link
                    :href="`/weeks/${week.year}/${week.week_number}`"
                    class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-800 bg-slate-900/50 p-4 transition hover:border-amber-400/40 hover:bg-slate-900"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-slate-100">{{ week.title }}</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="week.status === 'active' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-slate-800 text-slate-400'"
                            >
                                {{ week.status === 'active' ? 'активний' : 'завершено' }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-500">{{ formatDate(week.start_date) }} – {{ formatDate(week.end_date) }}</p>
                    </div>
                    <div class="text-right text-sm">
                        <p class="font-semibold text-slate-100">{{ formatKm(week.total_distance) }} км</p>
                        <p class="text-xs text-slate-500">{{ week.participants_count }} учасників</p>
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>
