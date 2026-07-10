<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ChangelogModal from './ChangelogModal.vue';
import { changelog } from '../data/changelog.js';

const page = usePage();
const brand = page.props.brand ?? {};
const showChangelog = ref(false);
const year = new Date().getFullYear();
const currentRelease = changelog.find((entry) => entry.current) ?? changelog[0];

const statLinks = [
    { label: 'Статистика', href: '/stat' },
    { label: 'Архів', href: '/stat/weeks' },
    { label: 'Рік', href: '/stat/year' },
    { label: 'Рейтинг', href: '/stat/all-time' },
    { label: 'Правила', href: '/stat/rules' },
];
</script>

<template>
    <footer class="mt-16 border-t border-brand-100 bg-white">
        <div class="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:px-8 md:grid-cols-3">
            <div>
                <div class="flex items-center gap-2.5">
                    <img src="/images/logo.png" alt="Велоклуб «ВелоТОР»" class="h-11 w-11 object-contain" />
                    <div>
                        <p class="font-extrabold leading-tight text-brand-950">{{ brand.full_name }}</p>
                        <p class="text-xs text-slate-500">{{ brand.tagline }}</p>
                    </div>
                </div>
                <p class="mt-4 max-w-xs text-sm text-slate-500">
                    Крутимо педалі разом. Надсилай кілометраж у Telegram — бот усе порахує.
                </p>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Статистика</p>
                <ul class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2">
                    <li v-for="l in statLinks" :key="l.href">
                        <Link :href="l.href" class="text-sm text-slate-600 transition hover:text-brand-600">{{ l.label }}</Link>
                    </li>
                </ul>
            </div>

            <div>
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">Спільнота</p>
                <ul class="mt-3 space-y-2">
                    <li>
                        <a :href="brand.telegram_url" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-slate-600 transition hover:text-brand-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M21.94 4.6 18.9 19c-.23 1.02-.84 1.27-1.7.79l-4.7-3.47-2.27 2.18c-.25.25-.46.46-.94.46l.34-4.78 8.7-7.86c.38-.34-.08-.53-.59-.19L6.7 13.02l-4.63-1.45c-1-.31-1.02-1 .21-1.48l18.1-6.98c.84-.31 1.57.19 1.3 1.47Z"/></svg>
                            Наш Telegram-бот
                        </a>
                    </li>
                    <li>
                        <a :href="brand.shop_url" target="_blank" rel="noopener" class="inline-flex items-center gap-2 text-sm text-slate-600 transition hover:text-brand-600">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M5.5 17.5h6l3-9h4M9 8.5h4"/></svg>
                            Магазин велотоварів
                        </a>
                    </li>
                    <li>
                        <Link href="/privacy-policy" class="text-sm text-slate-600 transition hover:text-brand-600">Політика конфіденційності</Link>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-brand-100">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 py-5 sm:flex-row sm:px-6 lg:px-8">
                <p class="text-center text-xs text-slate-500 sm:text-left">
                    © {{ year }} {{ brand.full_name }}. Усі права захищені.
                    <span class="ml-1">Версія {{ currentRelease.version }} ·</span>
                    <button
                        type="button"
                        class="cursor-pointer font-semibold text-brand-600 underline decoration-brand-200 underline-offset-4 transition hover:text-brand-700"
                        @click="showChangelog = true"
                    >
                        Що нового (?)
                    </button>
                </p>

                <p class="text-xs text-slate-500">
                    Розробник
                    <a :href="brand.developer_url" target="_blank" rel="noopener" class="font-semibold text-brand-600 transition hover:text-brand-700">
                        {{ brand.developer_name }}
                    </a>
                </p>
            </div>
        </div>

        <ChangelogModal :open="showChangelog" @close="showChangelog = false" />
    </footer>
</template>
