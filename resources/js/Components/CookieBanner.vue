<script setup>
import { onMounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';

const STORAGE_KEY = 'velotor_cookie_consent';
const CONSENT_TTL = 180 * 24 * 60 * 60 * 1000;

const visible = ref(false);

onMounted(() => {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        const ts = raw ? parseInt(raw, 10) : 0;
        if (!ts || Date.now() - ts > CONSENT_TTL) {
            visible.value = true;
        }
    } catch (e) {
        visible.value = true;
    }
});

function accept() {
    try {
        localStorage.setItem(STORAGE_KEY, String(Date.now()));
    } catch (e) {
        /* ignore */
    }
    visible.value = false;
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-4 opacity-0"
        leave-active-class="transition duration-200 ease-in"
        leave-to-class="translate-y-4 opacity-0"
    >
        <div
            v-if="visible"
            class="fixed inset-x-3 bottom-3 z-[90] mx-auto max-w-2xl overflow-hidden rounded-3xl border border-white/70 bg-white/95 shadow-[0_24px_70px_-24px_rgba(11,20,68,0.45)] backdrop-blur-xl sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2"
        >
            <div class="h-1 bg-gradient-to-r from-brand-700 via-brand-500 to-gold-400"></div>
            <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:p-6">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-50 text-2xl ring-1 ring-brand-100">🍪</div>
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-brand-950">Трохи cookie — лише для зручності</p>
                    <p class="mt-1 text-sm leading-relaxed text-slate-500">
                        Ми зберігаємо технічні cookie, щоб сайт працював стабільно. Подробиці є в
                        <Link href="/privacy-policy" class="font-semibold text-brand-600 underline decoration-brand-200 underline-offset-3 hover:text-brand-700">політиці конфіденційності</Link>.
                    </p>
                </div>
                <button type="button" class="shrink-0 cursor-pointer rounded-xl bg-brand-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-brand-600/20 transition hover:-translate-y-0.5 hover:bg-brand-700" @click="accept">
                    Добре
                </button>
            </div>
        </div>
    </Transition>
</template>
