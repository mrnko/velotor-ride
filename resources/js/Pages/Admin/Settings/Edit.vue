<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    settings: Object,
});

const form = useForm({ ...props.settings });

function submit() {
    form.put('/admin/settings');
}

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function submitPassword() {
    passwordForm.put('/admin/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}
</script>

<template>
    <div class="max-w-xl space-y-4">
        <h1 class="text-2xl font-extrabold text-white">Налаштування</h1>

        <form class="space-y-4 rounded-2xl border border-slate-800 bg-slate-900/60 p-5" @submit.prevent="submit">
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">Telegram chat id (для звітів)</label>
                <input v-model="form.telegram_chat_id" type="text" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400" />
                <p v-if="form.errors.telegram_chat_id" class="mt-1 text-xs text-rose-400">{{ form.errors.telegram_chat_id }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">Посилання на Telegram-чат (для сайту)</label>
                <input v-model="form.telegram_invite_url" type="text" placeholder="https://t.me/..." class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400" />
                <p v-if="form.errors.telegram_invite_url" class="mt-1 text-xs text-rose-400">{{ form.errors.telegram_invite_url }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">Часовий пояс</label>
                <input v-model="form.timezone" type="text" placeholder="Europe/Kyiv" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400" />
                <p v-if="form.errors.timezone" class="mt-1 text-xs text-rose-400">{{ form.errors.timezone }}</p>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-300">Макс. км / повідомлення</label>
                    <input v-model="form.max_distance_km" type="number" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-300">Вікно дубля (хв)</label>
                    <input v-model="form.duplicate_window_minutes" type="number" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-300">Дельта дубля (км)</label>
                    <input v-model="form.duplicate_distance_delta_km" type="number" step="0.1" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400" />
                </div>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400 disabled:opacity-60"
            >
                Зберегти
            </button>
        </form>

        <form class="space-y-4 rounded-2xl border border-slate-800 bg-slate-900/60 p-5" @submit.prevent="submitPassword">
            <h2 class="text-lg font-bold text-white">Зміна пароля</h2>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">Поточний пароль</label>
                <input v-model="passwordForm.current_password" type="password" autocomplete="current-password" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400" />
                <p v-if="passwordForm.errors.current_password" class="mt-1 text-xs text-rose-400">{{ passwordForm.errors.current_password }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">Новий пароль</label>
                <input v-model="passwordForm.password" type="password" autocomplete="new-password" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400" />
                <p v-if="passwordForm.errors.password" class="mt-1 text-xs text-rose-400">{{ passwordForm.errors.password }}</p>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium text-slate-300">Підтвердження нового пароля</label>
                <input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400" />
            </div>

            <button
                type="submit"
                :disabled="passwordForm.processing"
                class="cursor-pointer rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-amber-400 disabled:opacity-60"
            >
                Змінити пароль
            </button>
        </form>
    </div>
</template>
