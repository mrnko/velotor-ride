<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import TopProgress from '../Components/TopProgress.vue';
import CookieBanner from '../Components/CookieBanner.vue';
import SiteFooter from '../Components/SiteFooter.vue';
import SeoHead from '../Components/SeoHead.vue';

const page = usePage();
const mobileOpen = ref(false);
const scrolled = ref(false);

let revealObserver;

function onScroll() {
    scrolled.value = window.scrollY > 24;
}

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();

    revealObserver = new IntersectionObserver(
        (entries) => entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                revealObserver.unobserve(entry.target);
            }
        }),
        { threshold: 0.12 },
    );
    document.querySelectorAll('[data-reveal], [data-reveal-item]').forEach((element) => {
        element.classList.add('reveal-on-scroll');
        revealObserver.observe(element);
    });
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    revealObserver?.disconnect();
});

const nav = [
    { label: 'Статистика', href: '/stat' },
    { label: 'Архів', href: '/stat/weeks' },
    { label: 'Рік', href: '/stat/year' },
    { label: 'Рейтинг', href: '/stat/all-time' },
    { label: 'Правила', href: '/stat/rules' },
];
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#f5f7ff] text-slate-700">
        <SeoHead />
        <TopProgress />

        <header class="sticky top-0 z-40 border-b border-brand-100 bg-white/85 backdrop-blur">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div
                    class="flex items-center justify-between transition-all duration-300"
                    :class="scrolled ? 'h-16 sm:h-18' : 'h-20 sm:h-24'"
                >
                    <Link href="/" class="flex items-center" title="Велоклуб «ВелоТОР»" aria-label="Велоклуб «ВелоТОР»">
                        <img
                            src="/images/logo.png"
                            alt="Велоклуб «ВелоТОР»"
                            class="object-contain transition-all duration-300"
                            :class="scrolled ? 'h-11 w-11 sm:h-12 sm:w-12' : 'h-14 w-14 sm:h-16 sm:w-16'"
                        />
                    </Link>

                    <nav class="hidden items-center gap-0.5 lg:flex">
                        <Link
                            v-for="item in nav"
                            :key="item.href"
                            :href="item.href"
                            class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-brand-50 hover:text-brand-700"
                        >
                            {{ item.label }}
                        </Link>
                        <a
                            v-if="page.props.telegramInviteUrl"
                            :href="page.props.telegramInviteUrl"
                            target="_blank"
                            rel="noopener"
                            class="ml-2 inline-flex items-center gap-1.5 rounded-xl bg-brand-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700"
                        >
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M21.94 4.6 18.9 19c-.23 1.02-.84 1.27-1.7.79l-4.7-3.47-2.27 2.18c-.25.25-.46.46-.94.46l.34-4.78 8.7-7.86c.38-.34-.08-.53-.59-.19L6.7 13.02l-4.63-1.45c-1-.31-1.02-1 .21-1.48l18.1-6.98c.84-.31 1.57.19 1.3 1.47Z"/></svg>
                            Telegram
                        </a>
                    </nav>

                    <button
                        type="button"
                        class="cursor-pointer rounded-lg p-2 text-slate-600 hover:bg-brand-50 lg:hidden"
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

                <nav v-if="mobileOpen" class="flex flex-col gap-1 pb-4 lg:hidden">
                    <Link
                        v-for="item in nav"
                        :key="item.href"
                        :href="item.href"
                        class="rounded-lg px-3 py-2 text-sm font-medium text-slate-600 hover:bg-brand-50 hover:text-brand-700"
                        @click="mobileOpen = false"
                    >
                        {{ item.label }}
                    </Link>
                    <a
                        v-if="page.props.telegramInviteUrl"
                        :href="page.props.telegramInviteUrl"
                        target="_blank"
                        rel="noopener"
                        class="mt-1 rounded-xl bg-brand-600 px-3 py-2 text-center text-sm font-semibold text-white"
                    >
                        Telegram-чат
                    </a>
                </nav>
            </div>
        </header>

        <main class="mx-auto w-full max-w-7xl flex-1 px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
            <slot />
        </main>

        <SiteFooter />
        <CookieBanner />
    </div>
</template>
