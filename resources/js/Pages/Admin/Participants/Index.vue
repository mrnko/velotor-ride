<script setup>
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

defineProps({
    participants: Array,
});
</script>

<template>
    <div class="space-y-4">
        <h1 class="text-2xl font-extrabold text-white">Учасники</h1>

        <div class="overflow-x-auto rounded-2xl border border-slate-800">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-slate-900/80 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Ім'я</th>
                        <th class="px-4 py-3">Telegram</th>
                        <th class="px-4 py-3 text-right">Км (усього)</th>
                        <th class="px-4 py-3 text-right">Заїздів</th>
                        <th class="px-4 py-3 text-right">Torcoins</th>
                        <th class="px-4 py-3">Остання активність</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/70">
                    <tr v-for="p in participants" :key="p.id" class="hover:bg-slate-900/50">
                        <td class="px-4 py-3 font-medium text-slate-100">{{ p.display_name }}</td>
                        <td class="px-4 py-3 text-slate-400">{{ p.telegram_username ? `@${p.telegram_username}` : '—' }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ p.total_distance }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ p.rides_count }}</td>
                        <td class="px-4 py-3 text-right tabular-nums">{{ p.torcoins_all_time }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ p.last_seen_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
