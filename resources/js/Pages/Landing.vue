<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import TopProgress from '../Components/TopProgress.vue';
import CookieBanner from '../Components/CookieBanner.vue';
import SiteFooter from '../Components/SiteFooter.vue';
import SeoHead from '../Components/SeoHead.vue';

const props = defineProps({
    stats: { type: Object, default: () => ({ participants: 0, total_distance: 0, total_rides: 0 }) },
});

const page = usePage();
const brand = page.props.brand ?? {};
const scrolled = ref(false);
const animatedStats = ref({ participants: 0, total_distance: 0, total_rides: 0 });
const hasAnimatedStats = ref(false);
let revealObserver;
let statsAnimationFrame;

function onScroll() {
    scrolled.value = window.scrollY > 24;
}
onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    revealObserver = new IntersectionObserver(
        (entries) => entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');

            if (entry.target.id === 'club-statistics') {
                startStatsCounter();
            }
        }),
        { threshold: 0.12 },
    );
    document.querySelectorAll('[data-reveal], [data-reveal-item]').forEach((element) => {
        element.classList.add('reveal-on-scroll');
        revealObserver.observe(element);
    });
    onScroll();
});
onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    revealObserver?.disconnect();
    cancelAnimationFrame(statsAnimationFrame);
});

function scrollToContent() {
    document.querySelector('#club-statistics')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function formatNumber(value) {
    return new Intl.NumberFormat('uk-UA').format(value);
}

function startStatsCounter() {
    if (hasAnimatedStats.value) {
        return;
    }

    hasAnimatedStats.value = true;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        animatedStats.value = { ...props.stats };
        return;
    }

    const start = performance.now();
    const duration = 1300;
    const targets = {
        participants: Number(props.stats.participants) || 0,
        total_distance: Number(props.stats.total_distance) || 0,
        total_rides: Number(props.stats.total_rides) || 0,
    };

    const animate = (now) => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);

        animatedStats.value = {
            participants: Math.round(targets.participants * eased),
            total_distance: Number((targets.total_distance * eased).toFixed(2)),
            total_rides: Math.round(targets.total_rides * eased),
        };

        if (progress < 1) {
            statsAnimationFrame = requestAnimationFrame(animate);
            return;
        }

        animatedStats.value = targets;
    };

    statsAnimationFrame = requestAnimationFrame(animate);
}

const benefits = [
    'Великий дружній колектив однодумців',
    'Пропаганда здорового способу життя',
    'Приємне спілкування та спільні заїзди',
    'Облік статистики заїздів кожного учасника — розумний бот у Telegram зробить усе за вас',
    'Заробляйте спеціальні бали «Torcoins» та отримуйте приємні бонуси',
    'Це безкоштовно — приєднатися може кожен',
];

const steps = [
    {
        n: '01',
        title: 'Приєднуйся до чату',
        text: 'Заходь у Telegram-групу велоклубу та вітайся зі спільнотою.',
    },
    {
        n: '02',
        title: 'Надсилай кілометраж',
        text: 'Після заїзду напиши в чат «результат 25 км» — бот усе запамʼятає.',
    },
    {
        n: '03',
        title: 'Стеж за рейтингом',
        text: 'Дивись тижневі й річні підсумки та збирай Torcoins на сторінці статистики.',
    },
];
</script>

<template>
    <div class="flex min-h-screen flex-col bg-[#f5f7ff]">
        <SeoHead />
        <TopProgress />

        <!-- Header over hero -->
        <header
            class="fixed inset-x-0 top-0 z-40 transition-all duration-300"
            :class="scrolled ? 'border-b border-brand-100 bg-white/90 backdrop-blur' : 'bg-transparent'"
        >
            <div
                class="mx-auto flex max-w-7xl items-center justify-between px-4 transition-all duration-300 sm:px-6 lg:px-8"
                :class="scrolled ? 'py-2 sm:py-3' : 'py-5 sm:py-6'"
            >
                <span class="flex items-center" title="Велоклуб «ВелоТОР»">
                    <img
                        src="/images/logo.png"
                        alt="Велоклуб «ВелоТОР»"
                        class="object-contain drop-shadow-lg transition-all duration-300"
                        :class="scrolled ? 'h-12 w-12 sm:h-14 sm:w-14' : 'h-16 w-16 sm:h-20 sm:w-20'"
                    />
                </span>

                <Link
                    href="/stat"
                    class="inline-flex items-center gap-2 rounded-full border px-5 py-3 text-sm font-bold shadow-sm transition sm:px-6 sm:text-base"
                    :class="scrolled
                        ? 'border-brand-200 bg-white text-brand-700 hover:bg-brand-50'
                        : 'border-white/40 bg-white/10 text-white backdrop-blur hover:bg-white/20'"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 15l3-4 3 2 4-6"/></svg>
                    Сторінка статистики
                </Link>
            </div>
        </header>

        <!-- Hero -->
        <section class="relative isolate flex min-h-[100svh] items-center overflow-hidden">
            <div
                class="absolute inset-0 -z-20 bg-cover bg-center"
                style="background-image: url('/images/hero-bg.jpg')"
            ></div>
            <div class="absolute inset-0 -z-10 bg-gradient-to-br from-brand-950/90 via-brand-800/80 to-brand-600/70"></div>

            <div class="mx-auto w-full max-w-7xl px-4 pb-8 pt-[104px] sm:px-6 sm:pb-24 sm:pt-48 lg:px-8">
                <div data-reveal class="max-w-4xl rounded-[2rem] border border-white/10 bg-brand-950/15 p-4 backdrop-blur-[2px] sm:p-8 lg:p-10">
                    <p data-reveal-item class="inline-flex px-0 py-1 text-[11px] font-bold uppercase tracking-[0.3em] text-brand-100 sm:py-2 sm:text-xs">Привіт, велосипедисте</p>
                    <h1 class="mt-2 text-2xl font-extrabold leading-tight tracking-tight text-white sm:mt-5 sm:text-4xl lg:text-6xl">
                        Приєднуйся до Велоклубу
                        <span class="text-gold-400">«ВелоТОР»</span>
                        у Telegram
                    </h1>

                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-brand-50 sm:mt-6 sm:text-lg">
                        Спільнота, у якій кожен кілометр стає частиною великої клубної історії.
                    </p>
                    <ul class="mt-3 grid gap-1.5 sm:mt-6 sm:gap-2 sm:grid-cols-2">
                        <li
                            v-for="(b, i) in benefits"
                            :key="i"
                            data-reveal-item
                            class="flex items-start gap-2.5 px-0 py-0.5 text-[13px] leading-snug text-brand-50 sm:gap-3 sm:py-1.5 sm:text-base"
                            :style="{ transitionDelay: `${80 + i * 55}ms` }"
                        >
                            <span class="mt-2 h-px w-5 shrink-0 bg-gold-400 sm:mt-2.5 sm:w-6"></span>
                            <span>{{ b }}</span>
                        </li>
                    </ul>

                    <div class="mt-5 flex flex-col gap-2.5 sm:mt-9 sm:flex-row sm:gap-3">
                        <a
                            :href="brand.telegram_url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center justify-center gap-2 rounded-full bg-white px-6 py-3 font-semibold text-brand-700 shadow-lg transition hover:-translate-y-0.5 hover:shadow-xl sm:py-3.5"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor"><path d="M21.94 4.6 18.9 19c-.23 1.02-.84 1.27-1.7.79l-4.7-3.47-2.27 2.18c-.25.25-.46.46-.94.46l.34-4.78 8.7-7.86c.38-.34-.08-.53-.59-.19L6.7 13.02l-4.63-1.45c-1-.31-1.02-1 .21-1.48l18.1-6.98c.84-.31 1.57.19 1.3 1.47Z"/></svg>
                            Наша група у Telegram
                        </a>
                        <a
                            :href="brand.shop_url"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center justify-center gap-2 rounded-full px-6 py-3 font-semibold text-white transition hover:-translate-y-0.5 hover:text-gold-300 sm:py-3.5"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="5.5" cy="17.5" r="3.5"/><circle cx="18.5" cy="17.5" r="3.5"/><path d="M5.5 17.5h6l3-9h4M9 8.5h4"/></svg>
                            Наш магазин велотоварів
                        </a>
                    </div>
                </div>
            </div>

            <button
                type="button"
                class="absolute bottom-5 left-1/2 flex -translate-x-1/2 flex-col items-center gap-1 text-white/80 transition hover:text-white"
                aria-label="Перейти до статистики клубу"
                @click="scrollToContent"
            >
                <span class="flex h-11 w-11 animate-bounce items-center justify-center rounded-full">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                </span>
            </button>
        </section>

        <!-- Stats band -->
        <section id="club-statistics" data-reveal class="scroll-mt-24 border-b border-brand-100 bg-white">
            <div class="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div data-reveal-item class="mb-9 text-center">
                    <p class="text-sm font-bold uppercase tracking-[0.2em] text-brand-500">Статистика велоклубу</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-brand-950">Кожен заїзд має значення</h2>
                </div>
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                <div data-reveal-item class="text-center" style="transition-delay: 90ms">
                    <p class="text-gradient-brand text-4xl font-extrabold sm:text-5xl">{{ formatNumber(animatedStats.participants) }}</p>
                    <p class="mt-1 text-sm font-medium text-slate-500">учасників клубу</p>
                </div>
                <div data-reveal-item class="text-center" style="transition-delay: 170ms">
                    <p class="text-gradient-brand text-4xl font-extrabold sm:text-5xl">{{ formatNumber(animatedStats.total_distance) }}</p>
                    <p class="mt-1 text-sm font-medium text-slate-500">кілометрів накатано</p>
                </div>
                <div data-reveal-item class="text-center" style="transition-delay: 250ms">
                    <p class="text-gradient-brand text-4xl font-extrabold sm:text-5xl">{{ formatNumber(animatedStats.total_rides) }}</p>
                    <p class="mt-1 text-sm font-medium text-slate-500">заїздів зараховано</p>
                </div>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section data-reveal class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div data-reveal-item class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-500">Як це працює</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-brand-950">Три прості кроки</h2>
                <p class="mt-3 text-slate-500">Жодних застосунків і таблиць — усе відбувається у звичному Telegram-чаті.</p>
            </div>

            <div class="mt-10 grid gap-5 md:grid-cols-3">
                <div
                    v-for="(step, i) in steps"
                    :key="step.n"
                    data-reveal-item
                    class="surface-card rounded-3xl p-7 transition duration-300 hover:-translate-y-2 hover:shadow-xl"
                    :style="{ transitionDelay: `${90 + i * 80}ms` }"
                >
                    <span class="text-gradient-brand text-3xl font-black">{{ step.n }}</span>
                    <h3 class="mt-3 text-lg font-bold text-brand-950">{{ step.title }}</h3>
                    <p class="mt-2 text-sm text-slate-500">{{ step.text }}</p>
                </div>
            </div>
        </section>

        <!-- Torcoins -->
        <section data-reveal class="mx-auto w-full max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
            <div class="bg-brand-gradient relative overflow-hidden rounded-3xl px-6 py-12 sm:px-12">
                <div class="relative z-10 grid items-center gap-8 md:grid-cols-2">
                    <div data-reveal-item>
                        <span class="inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-sm font-semibold text-white backdrop-blur">
                            🪙 Torcoins
                        </span>
                        <h2 class="mt-4 text-3xl font-extrabold tracking-tight text-white">Катайся — заробляй бонуси</h2>
                        <p class="mt-3 max-w-md text-brand-50">
                            За кожні повні 100&nbsp;км ти отримуєш 1&nbsp;Torcoin. Накопичуй внутрішню
                            валюту клубу та обмінюй на приємні бонуси.
                        </p>
                        <Link href="/stat" class="mt-6 inline-flex items-center gap-2 rounded-full bg-white px-5 py-3 font-semibold text-brand-700 shadow-lg transition hover:-translate-y-0.5">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 19V5m0 14h16M8 15l3-4 3 2 4-6"/></svg>
                            Наша сторінка статистики
                        </Link>
                    </div>
                    <div data-reveal-item class="hidden justify-center md:flex" style="transition-delay: 120ms">
                        <div class="flex h-40 w-40 items-center justify-center rounded-full bg-white/10 text-8xl backdrop-blur">🚴</div>
                    </div>
                </div>
                <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-white/10"></div>
                <div class="absolute -bottom-20 -left-10 h-56 w-56 rounded-full bg-white/5"></div>
            </div>
        </section>

        <SiteFooter />
        <CookieBanner />
    </div>
</template>
