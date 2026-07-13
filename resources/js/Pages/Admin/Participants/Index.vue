<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    participants: Array,
});

const mergingId = ref(null);
const targetId = ref('');
const copiedId = ref(null);

async function copyUrl(p) {
    if (! p.profile_url) return;
    try {
        await navigator.clipboard.writeText(p.profile_url);
        copiedId.value = p.id;
        setTimeout(() => {
            if (copiedId.value === p.id) copiedId.value = null;
        }, 1500);
    } catch {
        // Clipboard API unavailable (e.g. non-HTTPS) — the link is still visible to copy manually.
    }
}

function startMerge(p) {
    mergingId.value = p.id;
    targetId.value = p.possible_duplicates.length === 1 ? p.possible_duplicates[0].id : '';
}

function cancelMerge() {
    mergingId.value = null;
    targetId.value = '';
}

function confirmMerge(duplicate) {
    const target = props.participants.find((p) => p.id === Number(targetId.value));
    if (! target) return;

    if (! confirm(`Об'єднати «${duplicate.display_name}» з «${target.display_name}»? Усі заїзди перенесуться на «${target.display_name}», а «${duplicate.display_name}» буде видалено.`)) {
        return;
    }

    router.post(`/admin/participants/${duplicate.id}/merge`, { into_id: target.id }, {
        onSuccess: () => cancelMerge(),
    });
}
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-extrabold text-white">Учасники</h1>

        <div class="overflow-x-auto rounded-2xl border border-slate-800">
            <table class="w-full min-w-[1040px] text-sm">
                <thead class="bg-slate-900/80 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Ім'я</th>
                        <th class="px-4 py-3">Telegram</th>
                        <th class="px-4 py-3">Профіль (URL)</th>
                        <th class="px-4 py-3 text-right">Км (усього)</th>
                        <th class="px-4 py-3 text-right">Заїздів</th>
                        <th class="px-4 py-3 text-right">Torcoins</th>
                        <th class="px-4 py-3">Остання активність</th>
                        <th class="px-4 py-3">Дії</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <template v-for="p in participants" :key="p.id">
                        <tr :class="['hover:bg-slate-900/50', p.possible_duplicates.length ? 'bg-amber-400/5' : '']">
                            <td class="px-4 py-3 font-medium text-slate-100">
                                {{ p.display_name }}
                                <span
                                    v-if="p.possible_duplicates.length"
                                    class="ml-2 inline-flex items-center rounded-full border border-amber-500/30 bg-amber-400/10 px-2 py-0.5 text-[11px] font-medium text-amber-300"
                                    :title="`Схоже на: ${p.possible_duplicates.map((d) => d.display_name).join(', ')}`"
                                >
                                    ⚠ можливий дубль
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-400">{{ p.telegram_username ? `@${p.telegram_username}` : '—' }}</td>
                            <td class="px-4 py-3">
                                <div v-if="p.profile_url" class="flex items-center gap-2">
                                    <a
                                        :href="p.profile_url"
                                        target="_blank"
                                        rel="noopener"
                                        class="block max-w-[240px] truncate text-xs font-medium text-sky-400 hover:text-sky-300 hover:underline"
                                        :title="p.profile_url"
                                    >{{ p.profile_url }}</a>
                                    <button
                                        type="button"
                                        class="shrink-0 rounded border border-slate-700 px-1.5 py-0.5 text-[11px] text-slate-400 hover:bg-slate-800 hover:text-white"
                                        @click="copyUrl(p)"
                                    >
                                        {{ copiedId === p.id ? 'Скопійовано' : 'Копіювати' }}
                                    </button>
                                </div>
                                <span v-else class="text-slate-600">—</span>
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ p.total_distance }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ p.rides_count }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ p.torcoins_all_time }}</td>
                            <td class="px-4 py-3 text-slate-500">{{ p.last_seen_at }}</td>
                            <td class="px-4 py-3">
                                <button
                                    v-if="mergingId !== p.id"
                                    type="button"
                                    class="text-xs font-medium text-amber-400 hover:text-amber-300"
                                    @click="startMerge(p)"
                                >
                                    Об'єднати
                                </button>
                                <button
                                    v-else
                                    type="button"
                                    class="text-xs font-medium text-slate-400 hover:text-white"
                                    @click="cancelMerge"
                                >
                                    Скасувати
                                </button>
                            </td>
                        </tr>
                        <tr v-if="mergingId === p.id" class="bg-slate-900/70">
                            <td colspan="8" class="px-4 py-3">
                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                    <span class="text-slate-400">Перенести заїзди «{{ p.display_name }}» в учасника:</span>
                                    <select
                                        v-model="targetId"
                                        class="rounded-lg border border-slate-800 bg-slate-950 px-2 py-1.5 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                                    >
                                        <option value="" disabled>Обрати учасника…</option>
                                        <option v-for="other in participants.filter((o) => o.id !== p.id)" :key="other.id" :value="other.id">
                                            {{ other.display_name }} ({{ other.total_distance }} км)
                                        </option>
                                    </select>
                                    <button
                                        type="button"
                                        :disabled="! targetId"
                                        class="rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-slate-950 hover:bg-amber-400 disabled:opacity-60"
                                        @click="confirmMerge(p)"
                                    >
                                        Об'єднати
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</template>
