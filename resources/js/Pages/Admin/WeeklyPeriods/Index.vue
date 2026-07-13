<script setup>
import { router } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    periods: Object,
    activePeriod: Object,
});

function closeActive() {
    if (confirm('Закрити поточний активний тиждень зараз і надіслати звіт у Telegram?')) {
        router.post('/admin/weekly-periods/close', {}, { preserveScroll: true });
    }
}

function sendReminder() {
    if (confirm('Надіслати в чат нагадування, що тиждень скоро закінчиться?')) {
        router.post('/admin/weekly-periods/remind', {}, { preserveScroll: true });
    }
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-2xl font-extrabold text-white">Тижні</h1>
            <div class="flex flex-wrap items-center gap-3">
                <span v-if="activePeriod" class="text-sm text-slate-400">
                    До закриття тижня {{ activePeriod.week_number }}: <span class="font-medium text-slate-200">{{ activePeriod.time_remaining }}</span>
                </span>
                <button
                    v-if="activePeriod"
                    type="button"
                    class="rounded-lg border border-amber-500/40 px-4 py-2 text-sm font-semibold text-amber-300 hover:bg-amber-400/10"
                    @click="sendReminder"
                >
                    Надіслати нагадування зараз
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400"
                    @click="closeActive"
                >
                    Закрити активний тиждень зараз
                </button>
            </div>
        </div>

        <div class="overflow-x-auto rounded-2xl border border-slate-800">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-slate-900/80 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Тиждень</th>
                        <th class="px-4 py-3">Статус</th>
                        <th class="px-4 py-3">Дати</th>
                        <th class="px-4 py-3 text-right">Км</th>
                        <th class="px-4 py-3">Звіт надіслано</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <tr v-for="p in periods.data" :key="p.id" class="hover:bg-slate-900/50">
                        <td class="px-4 py-3 font-medium text-slate-100">{{ p.title }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="p.status === 'active' ? 'bg-emerald-400/10 text-emerald-300' : 'bg-slate-800 text-slate-400'"
                            >
                                {{ p.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ p.start_date }} – {{ p.end_date }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ p.total_distance }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ p.report_sent_at ?? '—' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
