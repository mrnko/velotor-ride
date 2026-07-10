<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import TorcoinBadge from './TorcoinBadge.vue';
import EmptyState from './EmptyState.vue';
import Avatar from './Avatar.vue';

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

function userHref(row) {
    return `/stat/user/${row.slug ?? row.participant_id}`;
}

function rankBadgeClass(rank) {
    if (rank === 1) return 'bg-gold-400/20 text-gold-500 ring-gold-400/50';
    if (rank === 2) return 'bg-slate-200 text-slate-600 ring-slate-300';
    if (rank === 3) return 'bg-orange-100 text-orange-600 ring-orange-300';
    return 'bg-brand-50 text-brand-600 ring-brand-100';
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
                class="w-full max-w-xs rounded-xl border border-brand-100 bg-white px-3 py-2 text-sm text-slate-700 placeholder-slate-400 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200"
            />
        </div>

        <EmptyState
            v-if="!rows.length"
            title="Ще немає результатів"
            description="Як тільки хтось надішле результат у Telegram-чат, тут зʼявиться рейтинг."
        />

        <EmptyState
            v-else-if="!filtered.length"
            title="Нікого не знайдено"
            :description="`Немає учасників за запитом «${search}»`"
            icon="🔍"
        />

        <template v-else>
            <!-- Desktop / tablet table -->
            <div class="surface-card hidden overflow-hidden rounded-2xl sm:block">
                <table class="w-full text-sm">
                    <thead class="bg-brand-50/60 text-left text-xs uppercase tracking-wide text-slate-400">
                        <tr>
                            <th class="px-4 py-3 font-semibold">#</th>
                            <th class="cursor-pointer select-none px-4 py-3 font-semibold hover:text-brand-600" @click="sortBy('name')">Імʼя</th>
                            <th class="cursor-pointer select-none px-4 py-3 text-right font-semibold hover:text-brand-600" @click="sortBy('distance_km')">Км</th>
                            <th v-if="showRides" class="cursor-pointer select-none px-4 py-3 text-right font-semibold hover:text-brand-600" @click="sortBy('rides_count')">Заїздів</th>
                            <th v-if="showWeeksActive" class="px-4 py-3 text-right font-semibold">Тижнів</th>
                            <th v-if="showTorcoins" class="px-4 py-3 text-right font-semibold">Torcoins</th>
                            <th v-if="showLastActivity" class="px-4 py-3 text-right font-semibold">Активність</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-50">
                        <tr v-for="row in filtered" :key="row.participant_id" class="transition hover:bg-brand-50/50">
                            <td class="px-4 py-3">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold ring-1 ring-inset" :class="rankBadgeClass(row.rank)">
                                    {{ row.rank }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="userHref(row)" class="flex items-center gap-3 font-medium text-brand-950 transition-colors hover:text-brand-600">
                                    <Avatar :src="row.avatar_url" :name="row.name" :initials="row.initials" size="sm" />
                                    <span class="truncate">{{ row.name }}</span>
                                    <span v-if="row.rank === 1" class="text-sm" aria-hidden="true">👑</span>
                                </Link>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold tabular-nums text-slate-700">{{ formatKm(row.distance_km) }}</td>
                            <td v-if="showRides" class="px-4 py-3 text-right tabular-nums text-slate-500">{{ row.rides_count }}</td>
                            <td v-if="showWeeksActive" class="px-4 py-3 text-right tabular-nums text-slate-500">{{ row.weeks_active }}</td>
                            <td v-if="showTorcoins" class="px-4 py-3 text-right"><TorcoinBadge :value="row.torcoins" size="sm" /></td>
                            <td v-if="showLastActivity" class="px-4 py-3 text-right text-xs text-slate-400">{{ formatDate(row.last_activity) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Mobile card list -->
            <ul class="space-y-2 sm:hidden">
                <li v-for="row in filtered" :key="row.participant_id" class="surface-card rounded-xl p-3">
                    <Link :href="userHref(row)" class="flex items-center gap-3">
                        <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold ring-1 ring-inset" :class="rankBadgeClass(row.rank)">
                            {{ row.rank }}
                        </span>
                        <Avatar :src="row.avatar_url" :name="row.name" :initials="row.initials" size="sm" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium text-brand-950">{{ row.name }} <span v-if="row.rank === 1" aria-hidden="true">👑</span></span>
                            <span class="block text-xs text-slate-400">
                                <template v-if="showRides">{{ row.rides_count }} заїздів</template>
                                <template v-if="showWeeksActive"> · {{ row.weeks_active }} тижнів</template>
                            </span>
                        </span>
                        <span class="shrink-0 text-right">
                            <span class="block font-semibold tabular-nums text-brand-950">{{ formatKm(row.distance_km) }} км</span>
                            <TorcoinBadge v-if="showTorcoins" :value="row.torcoins" size="sm" class="mt-1" />
                        </span>
                    </Link>
                </li>
            </ul>
        </template>
    </div>
</template>
