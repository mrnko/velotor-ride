<script setup>
import { Link, usePage, router } from '@inertiajs/vue3';

const page = usePage();
const currentYear = new Date().getFullYear();

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
    <div class="flex min-h-screen flex-col bg-slate-950 text-slate-100">
        <header class="border-b border-slate-800/80 bg-slate-950/90">
            <div class="mx-auto w-full max-w-[1600px] px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/admin" class="flex items-center gap-2 font-bold tracking-tight">
                        <img src="/images/logo.png" alt="ВелоТОР" class="h-8 w-8 object-contain" />
                        Адміністративна панель
                    </Link>
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

        <main class="mx-auto w-full max-w-[1600px] flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
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

        <footer class="border-t border-slate-800/80 text-xs text-slate-500">
            <div class="mx-auto flex w-full max-w-[1600px] flex-col gap-2 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <p>© {{ currentYear }} {{ page.props.clubName }} · Адміністративна панель</p>
                <Link href="/" class="font-medium text-slate-400 transition hover:text-white">Перейти на сайт</Link>
            </div>
        </footer>
    </div>
</template>
