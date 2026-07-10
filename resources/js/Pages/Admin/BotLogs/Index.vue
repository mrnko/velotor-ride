<script setup>
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    logs: Object,
});

function statusClass(status) {
    if (status === 'error') return 'bg-rose-400/10 text-rose-300';
    if (status === 'ignored') return 'bg-slate-800 text-slate-400';
    return 'bg-emerald-400/10 text-emerald-300';
}
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-extrabold text-white">Логи бота</h1>

        <div class="overflow-x-auto rounded-2xl border border-slate-800">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-slate-900/80 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Дата</th>
                        <th class="px-4 py-3">Статус</th>
                        <th class="px-4 py-3">Обробник</th>
                        <th class="px-4 py-3">Повідомлення</th>
                        <th class="px-4 py-3">Помилка</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-900/50">
                        <td class="px-4 py-3 text-slate-500">{{ log.created_at }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold" :class="statusClass(log.status)">
                                {{ log.status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-400">{{ log.handler ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-300">{{ log.message_text ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-rose-400">{{ log.error_message ?? '' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
