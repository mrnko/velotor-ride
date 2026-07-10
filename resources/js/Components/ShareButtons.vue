<script setup>
import { ref } from 'vue';

const props = defineProps({
    url: { type: String, required: true },
    text: { type: String, default: '' },
});

const copied = ref(false);
const sharing = ref(false);

const encodedUrl = () => encodeURIComponent(props.url);
const encodedText = () => encodeURIComponent(props.text);

const links = {
    telegram: () => `https://t.me/share/url?url=${encodedUrl()}&text=${encodedText()}`,
    facebook: () => `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl()}`,
    twitter: () => `https://twitter.com/intent/tweet?url=${encodedUrl()}&text=${encodedText()}`,
    viber: () => `viber://forward?text=${encodedText()}%20${encodedUrl()}`,
};

function share(network) {
    window.open(links[network](), '_blank', 'noopener,noreferrer,width=640,height=560');
}

async function nativeShare() {
    if (navigator.share) {
        try {
            sharing.value = true;
            await navigator.share({ title: props.text, text: props.text, url: props.url });
            return;
        } catch (e) {
            /* user cancelled */
        } finally {
            sharing.value = false;
        }
    }
    share('telegram');
}

async function copyLink() {
    copied.value = true;
    setTimeout(() => (copied.value = false), 2200);

    try {
        await navigator.clipboard.writeText(props.url);
    } catch (e) {
        try {
            const input = document.createElement('input');
            input.value = props.url;
            document.body.appendChild(input);
            input.select();
            document.execCommand('copy');
            input.remove();
        } catch (fallbackError) {
            /* The visible URL remains selectable if clipboard access is blocked. */
        }
    }
}
</script>

<template>
    <div class="relative space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button" class="mr-1 cursor-pointer text-sm font-semibold text-brand-600 transition hover:text-brand-800 hover:underline" @click="nativeShare">
                {{ sharing ? 'Відкриваємо…' : 'Поділитися' }}
            </button>

            <button
            type="button"
            class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-brand-50 text-brand-600 transition hover:bg-brand-600 hover:text-white"
            title="Telegram"
            aria-label="Поділитися в Telegram"
            @click="share('telegram')"
        >
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="currentColor"><path d="M21.94 4.6 18.9 19c-.23 1.02-.84 1.27-1.7.79l-4.7-3.47-2.27 2.18c-.25.25-.46.46-.94.46l.34-4.78 8.7-7.86c.38-.34-.08-.53-.59-.19L6.7 13.02l-4.63-1.45c-1-.31-1.02-1 .21-1.48l18.1-6.98c.84-.31 1.57.19 1.3 1.47Z"/></svg>
            </button>

            <button
            type="button"
            class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-brand-50 text-brand-600 transition hover:bg-brand-600 hover:text-white"
            title="Facebook"
            aria-label="Поділитися у Facebook"
            @click="share('facebook')"
        >
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="currentColor"><path d="M13.5 21v-8h2.7l.4-3.13H13.5V7.87c0-.9.25-1.52 1.55-1.52h1.65V3.56c-.29-.04-1.27-.12-2.41-.12-2.39 0-4.03 1.46-4.03 4.14v2.31H7.5V13h2.76v8h3.24Z"/></svg>
            </button>

            <button
            type="button"
            class="inline-flex h-9 w-9 cursor-pointer items-center justify-center rounded-full bg-brand-50 text-brand-600 transition hover:bg-brand-600 hover:text-white"
            title="X (Twitter)"
            aria-label="Поділитися в X"
            @click="share('twitter')"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-7.1 8.1L23 22h-6.5l-5-6.6-5.8 6.6H2.6l7.6-8.7L1.5 2H8l4.6 6.1L18.9 2Zm-1.1 18h1.7L7.3 3.8H5.5L17.8 20Z"/></svg>
            </button>
        </div>

        <div class="flex max-w-xl items-center gap-2 rounded-2xl border border-brand-100 bg-brand-50/60 p-2">
            <a :href="url" class="min-w-0 flex-1 truncate px-2 text-xs text-slate-500 transition hover:text-brand-600 hover:underline">{{ url }}</a>
            <button type="button" class="inline-flex shrink-0 cursor-pointer items-center gap-1.5 rounded-xl bg-white px-3 py-2 text-xs font-bold text-brand-600 shadow-sm ring-1 ring-brand-100 transition hover:bg-brand-600 hover:text-white" @click="copyLink">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 8h11v11H8zM5 16H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h11a1 1 0 0 1 1 1v1"/></svg>
                Копіювати
            </button>
        </div>

        <Transition enter-active-class="transition duration-200" enter-from-class="translate-y-2 opacity-0" leave-active-class="transition duration-150" leave-to-class="translate-y-2 opacity-0">
            <div v-if="copied" class="fixed bottom-6 left-1/2 z-[120] -translate-x-1/2 rounded-full bg-brand-950 px-4 py-2.5 text-sm font-semibold text-white shadow-xl">
                Посилання скопійовано ✓
            </div>
        </Transition>
    </div>
</template>
