<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] }, // [{ label, distance_km, title }]
    title: { type: String, default: 'Кілометраж по тижнях' },
    valueKey: { type: String, default: 'distance_km' },
    unit: { type: String, default: 'км' },
});

const active = ref(null);

const max = computed(() => Math.max(1, ...props.data.map((d) => Number(d[props.valueKey] ?? 0))));

function heightPct(value) {
    return Math.max(3, Math.round((value / max.value) * 100));
}

function formatKm(value) {
    return new Intl.NumberFormat('uk-UA', { maximumFractionDigits: 1 }).format(value);
}
</script>

<template>
    <div class="surface-card min-w-0 overflow-hidden rounded-2xl p-4 sm:p-5">
        <div class="mb-4 flex items-center justify-between gap-2">
            <h3 class="text-sm font-bold text-brand-950">{{ title }}</h3>
            <span v-if="active !== null" class="text-xs font-medium text-brand-600">
                {{ data[active].title }} · {{ formatKm(data[active][valueKey]) }} {{ unit }}
            </span>
        </div>

        <div v-if="data.length" class="flex h-40 items-end gap-1.5 sm:gap-2">
            <div
                v-for="(item, i) in data"
                :key="i"
                class="group flex h-full min-w-0 flex-1 cursor-default flex-col items-center justify-end"
                @mouseenter="active = i"
                @mouseleave="active = null"
            >
                <span
                    class="mb-1 hidden text-[10px] font-semibold tabular-nums text-brand-600 opacity-0 transition group-hover:opacity-100 sm:block"
                >
                    {{ formatKm(item[valueKey]) }}
                </span>
                <div
                    class="w-full rounded-t-md transition-all duration-200"
                    :class="active === i ? 'bg-brand-gradient' : 'bg-brand-200 group-hover:bg-brand-400'"
                    :style="{ height: heightPct(item[valueKey]) + '%' }"
                ></div>
                <span class="mt-1.5 text-[10px] font-medium text-slate-400">{{ item.label }}</span>
            </div>
        </div>

        <p v-else class="py-8 text-center text-sm text-slate-400">Поки що немає даних для графіка.</p>
    </div>
</template>
