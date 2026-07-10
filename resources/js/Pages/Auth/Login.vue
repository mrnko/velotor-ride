<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/admin/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-slate-950 px-4 text-slate-100">
        <div class="w-full max-w-sm rounded-2xl border border-slate-800 bg-slate-900/60 p-6">
            <h1 class="text-center text-xl font-bold">🚴 Вхід в адмінку</h1>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-300" for="email">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                    />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-rose-400">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-300" for="password">Пароль</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        class="w-full rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-sm focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                    />
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-400">
                    <input v-model="form.remember" type="checkbox" class="rounded border-slate-700 bg-slate-950" />
                    Запам'ятати мене
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-lg bg-amber-500 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-400 disabled:opacity-60"
                >
                    Увійти
                </button>
            </form>
        </div>
    </div>
</template>
