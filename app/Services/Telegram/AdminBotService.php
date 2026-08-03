<?php

namespace App\Services\Telegram;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\Setting;
use App\Models\WeeklyPeriod;
use App\Services\Stats\StatsRecalculationService;
use App\Services\Weeks\WeeklyCloseAction;
use App\Services\Weeks\WeeklyRollbackAction;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminBotService
{
    private const PARTICIPANTS_PER_PAGE = 8;

    public function __construct(
        private readonly TelegramClient $telegram,
        private readonly WeekResolverService $resolver,
        private readonly WeeklyCloseAction $close,
        private readonly WeeklyRollbackAction $rollback,
        private readonly StatsRecalculationService $recalculation,
    ) {}

    public function showMenu(string $chatId, int|string $userId): void
    {
        if (! $this->telegram->isChatAdmin($chatId, $userId)) {
            $this->telegram->sendMessage($chatId, '⛔ Ця команда доступна лише адміністраторам чату.');

            return;
        }

        $this->telegram->sendMessage($chatId, '⚙️ <b>Адмін-дії</b>', $this->keyboard([
            [['text' => '➕ Додати результат', 'callback_data' => 'admin:add']],
            [['text' => '🔒 Зафіксувати поточний тиждень', 'callback_data' => 'admin:close']],
            [['text' => '↩️ Відкотити останню фіксацію', 'callback_data' => 'admin:rollback']],
        ]));
    }

    public function handleCallback(array $callback): void
    {
        $callbackId = (string) ($callback['id'] ?? '');
        $data = (string) ($callback['data'] ?? '');
        $chatId = (string) ($callback['message']['chat']['id'] ?? '');
        $userId = $callback['from']['id'] ?? null;

        if ($callbackId === '' || $chatId === '' || $userId === null) {
            return;
        }

        if (! $this->telegram->isChatAdmin($chatId, $userId)) {
            $this->telegram->answerCallbackQuery($callbackId, 'Недостатньо прав');

            return;
        }

        $this->telegram->answerCallbackQuery($callbackId);

        if ($data === 'admin:add') {
            $this->showPeriodPicker($chatId);

            return;
        }

        if (preg_match('/^admin:period:(\d+)$/', $data, $matches)) {
            $this->selectPeriod($chatId, (int) $matches[1]);

            return;
        }

        if (preg_match('/^admin:participants:(\d+):(\d+)$/', $data, $matches)) {
            $this->showParticipantPicker($chatId, (int) $matches[1], (int) $matches[2]);

            return;
        }

        if (preg_match('/^admin:participant:(\d+):(\d+)$/', $data, $matches)) {
            $this->selectParticipant($chatId, $userId, (int) $matches[1], (int) $matches[2]);

            return;
        }

        if ($data === 'admin:rollback') {
            $this->showRollbackConfirmation($chatId);

            return;
        }

        if ($data === 'admin:close') {
            $this->showCloseConfirmation($chatId);

            return;
        }

        if (preg_match('/^admin:close_confirm:(\d+)$/', $data, $matches)) {
            $this->confirmClose($chatId, (int) $matches[1]);

            return;
        }

        if (preg_match('/^admin:rollback_confirm:(\d+):(\d+)$/', $data, $matches)) {
            $this->confirmRollback($chatId, (int) $matches[1], (int) $matches[2]);

            return;
        }

        if ($data === 'admin:cancel') {
            Cache::forget($this->pendingKey($chatId, $userId));
            $this->telegram->sendMessage($chatId, 'Скасовано.');

            return;
        }

        $this->telegram->sendMessage($chatId, 'Ця кнопка вже неактуальна. Відкрийте /admin ще раз.');
    }

    public function handlePendingResult(string $text, string $chatId, int|string $userId): bool
    {
        $key = $this->pendingKey($chatId, $userId);
        $pending = Cache::get($key);

        if (! is_array($pending)) {
            return false;
        }

        if (! $this->telegram->isChatAdmin($chatId, $userId)) {
            Cache::forget($key);
            $this->telegram->sendMessage($chatId, '⛔ Недостатньо прав для додавання результату.');

            return true;
        }

        $distance = $this->parseDistance($text);
        $max = (float) Setting::get('max_distance_km', config('velotor.max_distance_km', 1000));

        if ($distance === null || $distance <= 0 || $distance > $max) {
            $this->telegram->sendMessage(
                $chatId,
                "⚠️ Введіть число від 0 до {$max}, наприклад: <b>25,5</b>."
            );

            return true;
        }

        $period = WeeklyPeriod::find($pending['period_id'] ?? null);
        $participant = Participant::find($pending['participant_id'] ?? null);

        if (! $period || ! $participant || ! $this->isSelectablePeriod($period)) {
            Cache::forget($key);
            $this->telegram->sendMessage($chatId, 'Тиждень або учасника вже не знайдено. Почніть з /admin ще раз.');

            return true;
        }

        DB::transaction(function () use ($period, $participant, $distance, $text, $userId): void {
            $lockedPeriod = WeeklyPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();

            RideResult::create([
                'participant_id' => $participant->id,
                'weekly_period_id' => $lockedPeriod->id,
                'distance_km' => $distance,
                'raw_message' => "Manual Telegram entry by {$userId}: {$text}",
                'source' => 'admin',
            ]);
        });

        $period = $this->recalculation->recalculatePeriod($period);
        Cache::forget($key);

        $distanceLabel = number_format($distance, 2, '.', ' ');
        $weekTotal = number_format((float) $period->total_distance, 2, '.', ' ');
        $closedNote = $period->status === 'closed'
            ? "\nℹ️ Тиждень закритий. Для повторної фіксації скористайтеся відкатом."
            : '';

        $this->telegram->sendMessage($chatId, implode("\n", [
            '✅ <b>Результат додано вручну</b>',
            'Учасник: <b>'.e($participant->display_name).'</b>',
            "Тиждень: <b>{$period->week_number}/{$period->year}</b>",
            "Додано: <b>{$distanceLabel} км</b>",
            "Разом за тиждень: <b>{$weekTotal} км</b>{$closedNote}",
        ]));

        return true;
    }

    private function showPeriodPicker(string $chatId): void
    {
        $active = $this->resolver->activePeriod();
        $previous = $this->resolver->previousPeriod($active);
        $rows = [[[
            'text' => "Поточний: {$active->week_number}/{$active->year}",
            'callback_data' => "admin:period:{$active->id}",
        ]]];

        if ($previous) {
            $rows[] = [[
                'text' => "Попередній: {$previous->week_number}/{$previous->year}",
                'callback_data' => "admin:period:{$previous->id}",
            ]];
        }

        $rows[] = [['text' => 'Скасувати', 'callback_data' => 'admin:cancel']];
        $this->telegram->sendMessage($chatId, 'Оберіть тиждень:', $this->keyboard($rows));
    }

    private function selectPeriod(string $chatId, int $periodId): void
    {
        $active = $this->resolver->activePeriod();
        $previous = $this->resolver->previousPeriod($active);
        $allowedIds = array_filter([$active->id, $previous?->id]);
        $period = in_array($periodId, $allowedIds, true) ? WeeklyPeriod::find($periodId) : null;

        if (! $period) {
            $this->telegram->sendMessage($chatId, 'Ця кнопка вже неактуальна. Оберіть тиждень ще раз.');

            return;
        }

        $this->showParticipantPicker($chatId, $period->id, 1);
    }

    private function showParticipantPicker(string $chatId, int $periodId, int $page): void
    {
        $period = WeeklyPeriod::find($periodId);

        if (! $period || ! $this->isSelectablePeriod($period)) {
            $this->telegram->sendMessage($chatId, 'Тиждень більше не існує.');

            return;
        }

        $query = Participant::where('is_active', true)->orderBy('display_name');
        $lastPage = max(1, (int) ceil($query->count() / self::PARTICIPANTS_PER_PAGE));
        $page = min(max(1, $page), $lastPage);
        $participants = $query->forPage($page, self::PARTICIPANTS_PER_PAGE)->get();

        if ($participants->isEmpty()) {
            $this->telegram->sendMessage($chatId, 'Немає зареєстрованих активних учасників.');

            return;
        }

        $rows = $participants->map(fn (Participant $participant) => [[
            'text' => $participant->display_name,
            'callback_data' => "admin:participant:{$period->id}:{$participant->id}",
        ]])->all();

        if ($lastPage > 1) {
            $navigation = [];
            if ($page > 1) {
                $navigation[] = ['text' => '←', 'callback_data' => "admin:participants:{$period->id}:".($page - 1)];
            }
            if ($page < $lastPage) {
                $navigation[] = ['text' => '→', 'callback_data' => "admin:participants:{$period->id}:".($page + 1)];
            }
            $rows[] = $navigation;
        }

        $rows[] = [['text' => 'Скасувати', 'callback_data' => 'admin:cancel']];
        $this->telegram->sendMessage(
            $chatId,
            "Оберіть учасника для тижня <b>{$period->week_number}/{$period->year}</b>:",
            $this->keyboard($rows)
        );
    }

    private function selectParticipant(string $chatId, int|string $userId, int $periodId, int $participantId): void
    {
        $period = WeeklyPeriod::find($periodId);
        $participant = Participant::whereKey($participantId)->where('is_active', true)->first();

        if (! $period || ! $participant || ! $this->isSelectablePeriod($period)) {
            $this->telegram->sendMessage($chatId, 'Тиждень або учасника не знайдено.');

            return;
        }

        Cache::put($this->pendingKey($chatId, $userId), [
            'period_id' => $period->id,
            'participant_id' => $participant->id,
        ], now()->addMinutes(15));

        $this->telegram->sendMessage(
            $chatId,
            'Введіть кілометраж для <b>'.e($participant->display_name).'</b> одним числом, наприклад <b>25,5</b>.',
            $this->keyboard([[['text' => 'Скасувати', 'callback_data' => 'admin:cancel']]])
        );
    }

    private function showRollbackConfirmation(string $chatId): void
    {
        $active = $this->resolver->activePeriod();
        $previous = $this->resolver->previousPeriod($active);

        if (! $previous || $previous->status !== 'closed') {
            $this->telegram->sendMessage($chatId, 'Немає закритого тижня, який можна відкотити.');

            return;
        }

        $this->telegram->sendMessage($chatId, implode("\n", [
            '⚠️ <b>Підтвердьте відкат</b>',
            "Тиждень {$previous->week_number}/{$previous->year} знову стане поточним.",
            "Порожній тиждень {$active->week_number}/{$active->year} буде прибрано.",
            'Після змін зафіксуйте тиждень повторно.',
        ]), $this->keyboard([
            [['text' => 'Так, відкотити', 'callback_data' => "admin:rollback_confirm:{$active->id}:{$previous->id}"]],
            [['text' => 'Скасувати', 'callback_data' => 'admin:cancel']],
        ]));
    }

    private function showCloseConfirmation(string $chatId): void
    {
        $period = $this->resolver->activePeriod();

        $this->telegram->sendMessage($chatId, implode("\n", [
            '⚠️ <b>Підтвердьте фіксацію</b>',
            "Тиждень {$period->week_number}/{$period->year} буде закрито.",
            'Бот опублікує підсумковий звіт і відкриє наступний тиждень.',
        ]), $this->keyboard([
            [['text' => 'Так, зафіксувати', 'callback_data' => "admin:close_confirm:{$period->id}"]],
            [['text' => 'Скасувати', 'callback_data' => 'admin:cancel']],
        ]));
    }

    private function confirmClose(string $chatId, int $expectedPeriodId): void
    {
        $active = WeeklyPeriod::where('status', 'active')->first();

        if (! $active || $active->id !== $expectedPeriodId) {
            $this->telegram->sendMessage($chatId, 'Це підтвердження вже неактуальне. Відкрийте /admin ще раз.');

            return;
        }

        $period = $this->close->execute(force: true);

        if (! $period) {
            $this->telegram->sendMessage($chatId, 'Не вдалося зафіксувати тиждень: він уже закритий або не знайдений.');

            return;
        }

        $this->telegram->sendMessage(
            $chatId,
            "✅ Тиждень <b>{$period->week_number}/{$period->year}</b> зафіксовано. Наступний тиждень відкрито."
        );
    }

    private function confirmRollback(string $chatId, int $expectedActivePeriodId, int $expectedPreviousPeriodId): void
    {
        $result = $this->rollback->execute($expectedActivePeriodId, $expectedPreviousPeriodId);

        if ($result['period']) {
            $period = $result['period'];
            $this->telegram->sendMessage($chatId, implode("\n", [
                "✅ Тиждень <b>{$period->week_number}/{$period->year}</b> знову відкрито.",
                'Тепер можна додати результат через /admin і повторно зафіксувати тиждень.',
            ]));

            return;
        }

        $message = match ($result['reason']) {
            'active_period_has_data' => '⛔ Відкат скасовано: у новому тижні вже є дані. Спочатку перенесіть або видаліть їх через адмін-панель.',
            'stale_periods' => 'Це підтвердження вже неактуальне. Відкрийте /admin ще раз.',
            default => 'Не знайдено поточного тижня для відкату.',
        };

        $this->telegram->sendMessage($chatId, $message);
    }

    private function parseDistance(string $text): ?float
    {
        $normalized = trim($text);
        $normalized = preg_replace('/^(?:результат|result)\s*[:\-]?\s*/iu', '', $normalized);
        $normalized = preg_replace('/\s*(?:км|km)\s*$/iu', '', (string) $normalized);
        $normalized = str_replace(',', '.', trim((string) $normalized));

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    /** @param array<int, array<int, array{text: string, callback_data: string}>> $rows */
    private function keyboard(array $rows): array
    {
        return ['reply_markup' => ['inline_keyboard' => $rows]];
    }

    private function pendingKey(string $chatId, int|string $userId): string
    {
        return "telegram-admin-result:{$chatId}:{$userId}";
    }

    private function isSelectablePeriod(WeeklyPeriod $period): bool
    {
        $active = $this->resolver->activePeriod();
        $previous = $this->resolver->previousPeriod($active);

        return $period->id === $active->id || $period->id === $previous?->id;
    }
}
