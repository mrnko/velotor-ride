<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    rideResult: Object,
});

const form = useForm({
    distance_km: props.rideResult.distance_km,
});

function submit() {
    form.put(`/admin/ride-results/${props.rideResult.id}`);
}
</script>

<template>
    <div class="max-w-md space-y-4">
        <h1 class="text-2xl font-extrabold text-white">Редагувати результат</h1>

        <div class="rounded-2xl border border-slate-800 bg-slate-900/60 p-5">
            <p class="text-sm text-slate-400">
                Учасник: <strong class="text-slate-200">{{ rideResult.participant_name }}</strong>
            </p>
            <p class="mt-1 text-xs text-slate-500">Оригінальне повідомлення: «{{ rideResult.raw_message }}»</p>

            <form class="mt-4 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-300" for="distance_km">Дистанція (км)</label>
                    <input
                        id="distance_km"
                        v-model="form.distance_km"
                        type="number"
                        step="0.01"
                        min="0.01"
                        max="1000"
                        class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                    />
                    <p v-if="form.errors.distance_km" class="mt-1 text-xs text-rose-400">{{ form.errors.distance_km }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400 disabled:opacity-60"
                >
                    Зберегти
                </button>
            </form>
        </div>
    </div>
</template>
