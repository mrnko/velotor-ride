<script setup>
import { usePage } from '@inertiajs/vue3';
import AppLayout from '../Layouts/AppLayout.vue';

defineOptions({ layout: AppLayout });

defineProps({
    updatedAt: { type: String, default: '' },
});

const page = usePage();
const brand = page.props.brand ?? {};

function formatDate(value) {
    if (!value) return '';
    return new Date(value).toLocaleDateString('uk-UA', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <article class="mx-auto max-w-3xl">
        <header class="mb-8">
            <h1 class="text-3xl font-extrabold tracking-tight text-brand-950 sm:text-4xl">Політика конфіденційності</h1>
            <p v-if="updatedAt" class="mt-2 text-sm text-slate-500">Останнє оновлення: {{ formatDate(updatedAt) }}</p>
        </header>

        <div class="space-y-8 text-slate-600">
            <section>
                <h2 class="text-lg font-bold text-brand-950">1. Загальні положення</h2>
                <p class="mt-2">
                    Ця Політика конфіденційності описує, як велоклуб «ВелоТОР» (далі — «ми») збирає,
                    використовує та захищає інформацію під час користування цим сайтом і нашим
                    Telegram-ботом. Користуючись сайтом, ви погоджуєтеся з умовами цієї Політики.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-brand-950">2. Які дані ми збираємо</h2>
                <ul class="mt-2 list-disc space-y-1.5 pl-5">
                    <li>Публічне імʼя та нікнейм у Telegram, які ви вказали у своєму профілі.</li>
                    <li>Надіслані вами результати заїздів (кілометраж, дата та час повідомлення).</li>
                    <li>Технічні дані: тип браузера, приблизне місцезнаходження за IP та знеособлена статистика відвідувань.</li>
                </ul>
                <p class="mt-2">
                    Ми не збираємо паролі, платіжні дані чи іншу чутливу персональну інформацію.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-brand-950">3. Файли cookie</h2>
                <p class="mt-2">
                    Сайт використовує файли cookie та подібні технології, щоб:
                </p>
                <ul class="mt-2 list-disc space-y-1.5 pl-5">
                    <li>памʼятати, що ви вже переглянули повідомлення про cookie;</li>
                    <li>забезпечувати коректну роботу сесії та безпеку сайту;</li>
                    <li>збирати знеособлену статистику для покращення сервісу.</li>
                </ul>
                <p class="mt-2">
                    Ви можете вимкнути cookie в налаштуваннях свого браузера, проте частина функцій сайту
                    може працювати некоректно.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-brand-950">4. Як ми використовуємо дані</h2>
                <p class="mt-2">
                    Дані використовуються виключно для ведення статистики клубу, формування рейтингів,
                    нарахування Torcoins та покращення роботи сайту. Ми не продаємо і не передаємо ваші
                    дані третім сторонам у комерційних цілях.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-brand-950">5. Зберігання та захист</h2>
                <p class="mt-2">
                    Ми вживаємо розумних технічних та організаційних заходів для захисту інформації від
                    несанкціонованого доступу. Дані зберігаються стільки, скільки це потрібно для роботи
                    клубної статистики.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-brand-950">6. Ваші права</h2>
                <p class="mt-2">
                    Ви маєте право запросити перегляд, виправлення або видалення ваших даних. Для цього
                    звертайтеся до адміністрації клубу через
                    <a :href="brand.telegram_url" target="_blank" rel="noopener" class="font-semibold text-brand-600 hover:text-brand-700">Telegram</a>.
                </p>
            </section>

            <section>
                <h2 class="text-lg font-bold text-brand-950">7. Зміни до Політики</h2>
                <p class="mt-2">
                    Ми можемо час від часу оновлювати цю Політику. Актуальна версія завжди доступна на цій
                    сторінці із зазначенням дати останнього оновлення.
                </p>
            </section>
        </div>
    </article>
</template>
