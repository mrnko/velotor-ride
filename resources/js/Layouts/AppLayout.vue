<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const mobileOpen = ref(false);

const nav = [
    { label: 'Головна', href: '/' },
    { label: 'Тиждень', href: '/week' },
    { label: 'Архів', href: '/weeks' },
    { label: 'Рік', href: '/year' },
    { label: 'Рейтинг', href: '/all-time' },
    { label: 'Правила', href: '/rules' },
];
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col">
        <header class="sticky top-0 z-40 border-b border-slate-800/80 bg-slate-950/90 backdrop-blur">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="flex h-16 items-center justify-between">
                    <Link href="/" class="flex items-center gap-2 font-bold tracking-tight text-lg">
                        <span class="text-xl">🚴</span>
                        <span>{{ page.props.clubName }}</span>
                    </Link>

                    <nav class="hidden md:flex items-center gap-1">
                        <Link
                            v-for="item in nav"
                            :key="item.href"
                            :href="item.href"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-slate-300 transition hover:bg-slate-800/70 hover:text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-amber-400"
                        >
                            {{ item.label }}
                        </Link>
                        <a
                            v-if="page.props.telegramInviteUrl"
                            :href="page.props.telegramInviteUrl"
                            target="_blank"
                            rel="noopener"
                            class="ml-2 rounded-lg bg-amber-500 px-3 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-400"
                        >
                            Telegram-чат
                        </a>
                    </nav>

                    <button
                        type="button"
                        class="md:hidden rounded-lg p-2 text-slate-300 hover:bg-slate-800/70"
                        aria-label="Меню"
                        @click="mobileOpen = !mobileOpen"
                    >
                        <svg v-if="!mobileOpen" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <nav v-if="mobileOpen" class="md:hidden flex flex-col gap-1 pb-4">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-slate-300 hover:bg-slate-800/70 hover:text-white"
                        @click="mobileOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                    <a
                        v-if="page.props.telegramInviteUrl"
                        :href="page.props.telegramInviteUrl"
                        target="_blank"
                        rel="noopener"
                        class="mt-1 rounded-lg bg-amber-500 px-3 py-2 text-center text-sm font-semibold text-slate-950"
                    >
                        Telegram-чат
                    </a>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-5xl flex-1 px-4 py-6 sm:px-6 sm:py-10">
            <slot />
        </main>

        <footer class="border-t border-slate-800/80 py-6 text-center text-sm text-slate-500">
            <p>{{ page.props.clubName }} · <Link href="/rules" class="hover:text-slate-300">Правила</Link></p>
        </footer>
    </div>
</template>
