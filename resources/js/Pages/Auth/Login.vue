<script setup>
import { useForm } from '@inertiajs/vue3';

const form = useForm({
    login: '',
    password: '',
    remember: false,
});

function submit() {
    form.post('/admin/login', { onFinish: () => form.reset('password') });
}
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-[#f5f7ff] px-4">
        <div class="surface-card w-full max-w-sm rounded-3xl p-7">
            <div class="flex flex-col items-center">
                <img src="/images/logo.png" alt="ВелоТОР" class="h-14 w-14 object-contain" />
                <h1 class="mt-3 text-xl font-extrabold text-brand-950">Вхід в адмінку</h1>
                <p class="text-sm text-slate-400">Велоклуб «ВелоТОР»</p>
            </div>

            <form class="mt-6 space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600" for="login">Email або логін</label>
                    <input
                        id="login"
                        v-model="form.login"
                        type="text"
                        required
                        autofocus
                        autocomplete="username"
                        class="w-full rounded-xl border border-brand-100 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200"
                    />
                    <p v-if="form.errors.login" class="mt-1 text-xs text-rose-500">{{ form.errors.login }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-600" for="password">Пароль</label>
                    <input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        autocomplete="current-password"
                        class="w-full rounded-xl border border-brand-100 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-400 focus:outline-none focus:ring-2 focus:ring-brand-200"
                    />
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-500">
                    <input v-model="form.remember" type="checkbox" class="rounded border-brand-200 text-brand-600 focus:ring-brand-200" />
                    Запамʼятати мене
                </label>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-xl bg-brand-600 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-60"
                >
                    Увійти
                </button>
            </form>
        </div>
    </div>
</template>
