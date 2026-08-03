<script setup>
import { Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    participants: Array,
    periods: Array,
    maxDistanceKm: Number,
});

const form = useForm({
    participant_id: props.participants[0]?.id ?? '',
    weekly_period_id: props.periods[0]?.id ?? '',
    distance_km: '',
});

function submit() {
    form.post('/admin/ride-results');
}
</script>

<template>
    <div class="max-w-2xl space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold text-white">Додати результат</h1>
            <Link href="/admin/ride-results" class="text-sm font-medium text-slate-400 transition hover:text-white">Назад до результатів</Link>
        </div>

        <form class="space-y-5 rounded-2xl border border-slate-800 bg-slate-900/60 p-5" @submit.prevent="submit">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-300" for="weekly_period_id">Тиждень</label>
                <select
                    id="weekly_period_id"
                    v-model="form.weekly_period_id"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                >
                    <option v-for="period in periods" :key="period.id" :value="period.id">
                        {{ period.label }} — {{ period.status === 'active' ? 'поточний' : 'попередній' }}
                    </option>
                </select>
                <p v-if="form.errors.weekly_period_id" class="mt-1 text-xs text-rose-400">{{ form.errors.weekly_period_id }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-300" for="participant_id">Учасник</label>
                <select
                    id="participant_id"
                    v-model="form.participant_id"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                >
                    <option v-for="participant in participants" :key="participant.id" :value="participant.id">
                        {{ participant.display_name }}
                    </option>
                </select>
                <p v-if="form.errors.participant_id" class="mt-1 text-xs text-rose-400">{{ form.errors.participant_id }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-300" for="distance_km">Результат (км)</label>
                <input
                    id="distance_km"
                    v-model="form.distance_km"
                    type="number"
                    step="0.01"
                    min="0.01"
                    :max="maxDistanceKm"
                    inputmode="decimal"
                    placeholder="Наприклад, 25.5"
                    class="w-full rounded-lg border border-slate-700 bg-slate-950 px-3 py-2.5 text-sm text-white placeholder:text-slate-600 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                />
                <p v-if="form.errors.distance_km" class="mt-1 text-xs text-rose-400">{{ form.errors.distance_km }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing || !participants.length || !periods.length"
                class="rounded-lg bg-amber-500 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-400 disabled:opacity-60"
            >
                {{ form.processing ? 'Збереження…' : 'Додати результат' }}
            </button>
        </form>
    </div>
</template>
