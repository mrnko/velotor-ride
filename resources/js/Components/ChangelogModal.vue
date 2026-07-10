<script setup>
import { changelog } from '../data/changelog.js';

defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close']);

function formatDate(value) {
    return new Date(value).toLocaleDateString('uk-UA', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-[110] flex items-end justify-center bg-brand-950/50 p-0 backdrop-blur-sm sm:items-center sm:p-4"
                @click.self="emit('close')"
            >
                <div
                    class="flex max-h-[85vh] w-full max-w-lg flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl"
                >
                    <div class="flex items-center justify-between border-b border-brand-100 px-5 py-4">
                        <div>
                            <h2 class="text-lg font-extrabold text-brand-950">Історія змін</h2>
                            <p class="text-xs text-slate-500">Що нового у «ВелоТОР»</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                            aria-label="Закрити"
                            @click="emit('close')"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-6 overflow-y-auto px-5 py-5">
                        <section v-for="entry in changelog" :key="entry.version">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-sm font-bold"
                                    :class="entry.current ? 'bg-brand-600 text-white' : 'bg-brand-50 text-brand-700'"
                                >
                                    v{{ entry.version }}
                                </span>
                                <span v-if="entry.current" class="rounded-full bg-gold-400/20 px-2 py-0.5 text-xs font-semibold text-gold-500">
                                    поточна
                                </span>
                                <span class="text-xs text-slate-400">{{ formatDate(entry.date) }}</span>
                            </div>
                            <h3 class="mt-1.5 font-bold text-brand-950">{{ entry.title }}</h3>
                            <ul class="mt-2 space-y-1.5">
                                <li
                                    v-for="(change, i) in entry.changes"
                                    :key="i"
                                    class="flex gap-2 text-sm text-slate-600"
                                >
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-400"></span>
                                    <span>{{ change }}</span>
                                </li>
                            </ul>
                        </section>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
