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
    router.get(`/stat/weeks/${event.target.value}`, {}, { preserveScroll: true });
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
            <h1 class="text-2xl font-extrabold text-brand-950">Архів тижнів</h1>

            <select
                :value="year"
                class="rounded-xl border border-brand-100 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200"
                @change="goToYear"
            >
                <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
            </select>
        </div>

        <EmptyState v-if="!weeks.length" title="У цьому році ще немає тижнів" icon="🗓️" />

        <ul v-else data-reveal class="space-y-2">
            <li v-for="week in weeks" :key="`${week.year}-${week.week_number}`">
                <Link
                    :href="`/stat/weeks/${week.year}/${week.week_number}`"
                    class="surface-card flex flex-wrap items-center justify-between gap-2 rounded-xl p-4 transition hover:border-brand-300 hover:shadow-md"
                >
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-brand-950">{{ week.title }}</span>
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="week.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                            >
                                {{ week.status === 'active' ? 'активний' : 'завершено' }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-slate-400">{{ formatDate(week.start_date) }} – {{ formatDate(week.end_date) }}</p>
                    </div>
                    <div class="text-right text-sm">
                        <p class="font-semibold text-brand-950">{{ formatKm(week.total_distance) }} км</p>
                        <p class="text-xs text-slate-400">{{ week.participants_count }} учасників</p>
                    </div>
                </Link>
            </li>
        </ul>
    </div>
</template>
