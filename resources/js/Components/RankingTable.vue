<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import TorcoinBadge from './TorcoinBadge.vue';
import EmptyState from './EmptyState.vue';

const props = defineProps({
    rows: { type: Array, required: true },
    showRides: { type: Boolean, default: true },
    showTorcoins: { type: Boolean, default: false },
    showWeeksActive: { type: Boolean, default: false },
    showLastActivity: { type: Boolean, default: false },
    searchable: { type: Boolean, default: true },
});

const search = ref('');
const sortKey = ref('rank');
const sortDir = ref('asc');

function sortBy(key) {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = key === 'name' ? 'asc' : 'desc';
    }
}

const filtered = computed(() => {
    const term = search.value.trim().toLowerCase();
    let rows = term ? props.rows.filter((r) => r.name.toLowerCase().includes(term)) : props.rows.slice();

    const dir = sortDir.value === 'asc' ? 1 : -1;
    rows = rows.slice().sort((a, b) => {
        const va = a[sortKey.value];
        const vb = b[sortKey.value];
        if (typeof va === 'string') return va.localeCompare(vb) * dir;
        return (va - vb) * dir;
    });

    return rows;
});

function rankBadgeClass(rank) {
    if (rank === 1) return 'bg-amber-400/15 text-amber-300 ring-amber-400/40';
    if (rank === 2) return 'bg-slate-300/15 text-slate-200 ring-slate-300/30';
    if (rank === 3) return 'bg-orange-700/20 text-orange-300 ring-orange-600/40';
    return 'bg-slate-800 text-slate-400 ring-slate-700';
}

function formatKm(value) {
    return new Intl.NumberFormat('uk-UA', { maximumFractionDigits: 1 }).format(value);
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('uk-UA');
}
</script>

<template>
    <div>
        <div v-if="searchable && rows.length" class="mb-4">
            <input
                v-model="search"
                type="search"
                placeholder="Пошук учасника…"
                class="w-full max-w-xs rounded-lg border border-slate-800 bg-slate-900/60 px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
            />
        </div>

        <EmptyState
            v-if="!rows.length"
            title="Ще немає результатів"
            description="Як тільки хтось надішле результат у Telegram-чат, тут з'явиться рейтинг."
        />

        <EmptyState
            v-else-if="!filtered.length"
            title="Нікого не знайдено"
            :description="`Немає учасників за запитом «${search}»`"
            icon="🔍"
        />

        <template v-else>
            <!-- Desktop / tablet table -->
            <div class="hidden overflow-hidden rounded-2xl border border-slate-800 sm:block">
                <table class="w-full text-sm">
                    <thead class="bg-slate-900/80 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-4 py-3 font-medium">#</th>
                            <th class="cursor-pointer select-none px-4 py-3 font-medium hover:text-slate-300" @click="sortBy('name')">Ім'я</th>
                            <th class="cursor-pointer select-none px-4 py-3 text-right font-medium hover:text-slate-300" @click="sortBy('distance_km')">Км</th>
                            <th v-if="showRides" class="cursor-pointer select-none px-4 py-3 text-right font-medium hover:text-slate-300" @click="sortBy('rides_count')">Заїздів</th>
                            <th v-if="showWeeksActive" class="px-4 py-3 text-right font-medium">Тижнів</th>
                            <th v-if="showTorcoins" class="px-4 py-3 text-right font-medium">Torcoins</th>
                            <th v-if="showLastActivity" class="px-4 py-3 text-right font-medium">Активність</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/70">
                        <tr v-for="row in filtered" :key="row.participant_id" class="transition hover:bg-slate-900/50">
                            <td class="px-4 py-3">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold ring-1 ring-inset" :class="rankBadgeClass(row.rank)">
                                    {{ row.rank }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-100">
                                <Link :href="`/participants/${row.participant_id}`" class="hover:text-amber-300">{{ row.name }}</Link>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums text-slate-200">{{ formatKm(row.distance_km) }}</td>
                            <td v-if="showRides" class="px-4 py-3 text-right tabular-nums text-slate-400">{{ row.rides_count }}</td>
                            <td v-if="showWeeksActive" class="px-4 py-3 text-right tabular-nums text-slate-400">{{ row.weeks_active }}</td>
                            <td v-if="showTorcoins" class="px-4 py-3 text-right"><TorcoinBadge :value="row.torcoins" size="sm" /></td>
                            <td v-if="showLastActivity" class="px-4 py-3 text-right text-xs text-slate-500">{{ formatDate(row.last_activity) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile card list -->
            <ul class="space-y-2 sm:hidden">
                <li v-for="row in filtered" :key="row.participant_id" class="rounded-xl border border-slate-800 bg-slate-900/50 p-3">
                    <Link :href="`/participants/${row.participant_id}`" class="flex items-center gap-3">
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-1 ring-inset" :class="rankBadgeClass(row.rank)">
                            {{ row.rank }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium text-slate-100">{{ row.name }}</span>
                            <span class="block text-xs text-slate-500">
                                <template v-if="showRides">{{ row.rides_count }} заїздів</template>
                                <template v-if="showWeeksActive"> · {{ row.weeks_active }} тижнів</template>
                            </span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="block font-semibold tabular-nums text-slate-100">{{ formatKm(row.distance_km) }} км</span>
                            <TorcoinBadge v-if="showTorcoins" :value="row.torcoins" size="sm" class="mt-1" />
                        </span>
                    </Link>
                </li>
            </ul>
        </template>
    </div>
</template>
