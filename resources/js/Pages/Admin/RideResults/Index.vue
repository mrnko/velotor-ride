<script setup>
import { Link, router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    results: Object,
});

function destroy(id) {
    if (confirm('Видалити цей результат?')) {
        router.delete(`/admin/ride-results/${id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold text-white">Результати заїздів</h1>
            <Link href="/admin/ride-results/create" class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-400">
                Додати результат
            </Link>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-800">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-slate-900/80 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Учасник</th>
                        <th class="px-4 py-3">Тиждень</th>
                        <th class="px-4 py-3 text-right">Км</th>
                        <th class="px-4 py-3">Джерело</th>
                        <th class="px-4 py-3">Дата</th>
                        <th class="px-4 py-3 text-right">Дії</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <tr v-for="r in results.data" :key="r.id" class="hover:bg-slate-900/50">
                        <td class="px-4 py-3 font-medium text-slate-100">{{ r.participant_name }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ r.period_label }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ r.distance_km }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ r.source }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ r.created_at }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-2">
                                <Link :href="`/admin/ride-results/${r.id}/edit`" class="text-amber-400 hover:text-amber-300">Редагувати</Link>
                                <button type="button" class="text-rose-400 hover:text-rose-300" @click="destroy(r.id)">Видалити</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="results.links.length > 3" class="flex flex-wrap gap-1">
            <Link
                v-for="link in results.links"
                :key="link.label"
                :href="link.url ?? '#'"
                v-html="link.label"
                class="rounded-lg px-3 py-1.5 text-sm"
                :class="[
                    link.active ? 'bg-amber-500 text-slate-950 font-semibold' : 'text-slate-400 hover:bg-slate-800',
                    !link.url && 'pointer-events-none opacity-40',
                ]"
            />
        </div>
    </div>
</template>
