<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();

const nav = [
    { label: 'Огляд', href: '/admin' },
    { label: 'Учасники', href: '/admin/participants' },
    { label: 'Результати', href: '/admin/ride-results' },
    { label: 'Тижні', href: '/admin/weekly-periods' },
    { label: 'Логи бота', href: '/admin/bot-logs' },
    { label: 'Налаштування', href: '/admin/settings' },
];

function logout() {
    router.post('/admin/logout');
}
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <header class="border-b border-slate-800/80 bg-slate-950/90">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/admin" class="font-bold tracking-tight">🚴 Адмінка</Link>
                    <button type="button" class="text-sm text-slate-400 hover:text-white" @click="logout">Вийти</button>
                </div>
                <nav class="flex flex-wrap gap-1 pb-3">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium text-slate-300 hover:bg-slate-800/70 hover:text-white"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-6 sm:px-6">
            <div
                v-if="page.props.flash?.success"
                class="mb-4 rounded-lg border border-emerald-500/30 bg-emerald-400/10 px-4 py-2.5 text-sm text-emerald-300"
            >
                {{ page.props.flash.success }}
            </div>
            <div
                v-if="page.props.flash?.error"
                class="mb-4 rounded-lg border border-rose-500/30 bg-rose-400/10 px-4 py-2.5 text-sm text-rose-300"
            >
                {{ page.props.flash.error }}
            </div>

            <slot />
        </main>
    </div>
</template>
