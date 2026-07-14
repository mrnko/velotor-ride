<?php

namespace App\Services\Telegram;

use App\Models\BotMessageLog;
use App\Models\Participant;
use App\Models\RideResult;
use App\Models\Setting;
use App\Models\TorcoinBonus;
use App\Models\WeeklyPeriod;
use App\Services\Parsing\ResultParser;
use App\Services\Stats\DuplicateGuard;
use App\Services\Torcoins\TorcoinCalculator;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class UpdateHandler
{
    public function __construct(
        private readonly TelegramClient $telegram,
        private readonly BotCommandRouter $router,
        private readonly ResultParser $parser,
        private readonly DuplicateGuard $duplicateGuard,
        private readonly WeekResolverService $resolver,
    ) {}

    /**
     * Never throws: any unexpected/malformed update is caught, logged with
     * status "error", and swallowed so the webhook can still answer 200.
     */
    public function handle(array $update): void
    {
        try {
            $this->process($update);
        } catch (Throwable $e) {
            Log::error('Telegram update handling failed', ['message' => $e->getMessage()]);

            BotMessageLog::create([
                'telegram_update_id' => $update['update_id'] ?? null,
                'message_text' => $update['message']['text'] ?? null,
                'status' => 'error',
                'error_message' => $e->getMessage(),
                'raw_payload' => $update,
            ]);
        }
    }

    private function process(array $update): void
    {
        $message = $update['message'] ?? null;

        if (! is_array($message) || ! isset($message['text'], $message['from'], $message['chat'])) {
            // A Telegram update type we don't act on (edited message, channel
            // post, callback query, ...) — valid traffic, nothing to do.
            BotMessageLog::create([
                'telegram_update_id' => $update['update_id'] ?? null,
                'status' => 'ignored',
                'raw_payload' => $update,
            ]);

            return;
        }

        $text = (string) $message['text'];
        $chatId = (string) $message['chat']['id'];
        $participant = $this->resolveParticipant($message['from']);

        $log = BotMessageLog::create([
            'telegram_update_id' => $update['update_id'] ?? null,
            'chat_id' => $chatId,
            'telegram_user_id' => $participant->telegram_user_id,
            'message_text' => $text,
            'direction' => 'incoming',
            'status' => 'ok',
        ]);

        $commandName = BotCommandRouter::commandNameFromText($text);

        if ($commandName !== null) {
            $this->handleCommand($commandName, $participant, $chatId, $log);

            return;
        }

        $this->handleResultMessage($message, $participant, $chatId, $log);
    }

    private function resolveParticipant(array $from): Participant
    {
        $participant = Participant::firstOrNew(['telegram_user_id' => $from['id']]);

        $participant->fill([
            'telegram_username' => $from['username'] ?? null,
            'first_name' => $from['first_name'] ?? null,
            'last_name' => $from['last_name'] ?? null,
            'display_name' => Participant::resolveDisplayName(
                $from['first_name'] ?? null,
                $from['last_name'] ?? null,
                $from['username'] ?? null,
            ),
            'last_seen_at' => now(),
        ]);

        if (! $participant->exists) {
            $participant->first_seen_at = now();
            $participant->is_active = true;
        }

        $participant->save();

        return $participant;
    }

    private function handleCommand(string $commandName, Participant $participant, string $chatId, BotMessageLog $log): void
    {
        $command = $this->router->resolve($commandName);

        if (! $command) {
            $log->update(['status' => 'ignored']);

            return;
        }

        $reply = $command->handle($participant);
        $this->telegram->sendMessage($chatId, $reply);
        $log->update(['handler' => $commandName]);
    }

    private function handleResultMessage(array $message, Participant $participant, string $chatId, BotMessageLog $log): void
    {
        $text = (string) $message['text'];
        $parsed = $this->parser->parse($text);

        if (! $parsed->matched) {
            $log->update(['status' => 'ignored', 'handler' => 'result_parser']);

            return;
        }

        if (! $parsed->valid) {
            $reply = match ($parsed->invalidReason) {
                'too_high' => '⚠️ Занадто великий результат. Максимум — '
                    .(int) Setting::get('max_distance_km', config('velotor.max_distance_km')).' км за одне повідомлення.',
                default => '⚠️ Не вдалося розпізнати результат. Напиши, наприклад: результат 25 км',
            };

            $this->telegram->sendMessage($chatId, $reply);
            $log->update(['status' => 'ignored', 'handler' => 'result_parser', 'error_message' => $parsed->invalidReason]);

            return;
        }

        if ($this->duplicateGuard->isDuplicate($participant, $parsed->distanceKm)) {
            $this->telegram->sendMessage(
                $chatId,
                '⚠️ Схожий результат уже було додано щойно. Якщо це новий заїзд — спробуй ще раз через кілька хвилин.'
            );
            $log->update(['status' => 'ignored', 'handler' => 'duplicate_guard']);

            return;
        }

        $period = $this->resolver->activePeriod();

        $firstResultBonus = DB::transaction(function () use ($period, $participant, $parsed, $text, $message): ?TorcoinBonus {
            $lockedPeriod = WeeklyPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();
            $isFirstResult = ! RideResult::where('weekly_period_id', $lockedPeriod->id)->exists();
            $bonusAlreadyAwarded = TorcoinBonus::where('weekly_period_id', $lockedPeriod->id)
                ->where('reason', TorcoinBonus::REASON_FIRST_WEEKLY_RESULT)
                ->exists();

            RideResult::create([
                'participant_id' => $participant->id,
                'weekly_period_id' => $lockedPeriod->id,
                'distance_km' => $parsed->distanceKm,
                'raw_message' => $text,
                'telegram_message_id' => $message['message_id'] ?? null,
                'source' => 'telegram',
            ]);

            if (! $isFirstResult || $bonusAlreadyAwarded) {
                return null;
            }

            return TorcoinBonus::create([
                'participant_id' => $participant->id,
                'weekly_period_id' => $lockedPeriod->id,
                'amount' => config('velotor.weekly_first_result_bonus', 0.1),
                'reason' => TorcoinBonus::REASON_FIRST_WEEKLY_RESULT,
            ]);
        });

        $this->syncAvatar($participant);

        $weekTotal = (float) RideResult::where('participant_id', $participant->id)
            ->where('weekly_period_id', $period->id)
            ->sum('distance_km');

        $allTimeTotal = (float) RideResult::where('participant_id', $participant->id)->sum('distance_km');
        $torcoins = TorcoinCalculator::balanceForParticipant($participant, $allTimeTotal);
        $kmToNext = TorcoinCalculator::kmToNextCoin($allTimeTotal);
        $statUrl = route('stat.home');

        $reply = implode("\n", [
            "{$participant->display_name}, супер! ✌️",
            '',
            '✅ Твій результат — '.number_format($parsed->distanceKm, 2, '.', ' ').' км успішно зараховано у загальному заліку!',
            '',
            "-= ТИЖДЕНЬ {$period->week_number} | {$period->year} рік =-",
            '',
            '🚴 За цей тиждень: '.number_format($weekTotal, 2, '.', ' ').' км',
            '🌍 Усього за весь час: '.number_format($allTimeTotal, 2, '.', ' ').' км',
            '💰 Баланс Torcoins: '.number_format($torcoins, 2, '.', ' ')." (ще {$kmToNext} км до наступного)",
            '',
            "➡️ Статистика за тиждень — {$statUrl} ⬅️",
        ]);

        $this->telegram->sendMessage($chatId, $reply);

        if ($firstResultBonus) {
            $bonus = rtrim(rtrim(number_format((float) $firstResultBonus->amount, 2, '.', ''), '0'), '.');
            $name = e($participant->display_name);
            $balance = number_format($torcoins, 2, '.', ' ');

            $this->telegram->sendMessage($chatId, implode("\n", [
                "🏆 <b>{$name}</b>, вітаємо Вас! Ви стали першим учасником, який завантажив свої результати цього тижня і отримуєте за це бонус!",
                '',
                "✅ <b>{$bonus} TOR.COINS</b>",
                '',
                "💰 Ваш баланс TOR.COINS: <b>{$balance}</b>",
                '',
                'Гарного дня!',
            ]));
        }

        $log->update(['status' => 'ok', 'handler' => 'result_saved']);
    }

    /**
     * When a participant who has no avatar yet submits a result, try to adopt
     * their current Telegram profile photo. If they have none (or it's hidden),
     * nothing changes and the UI keeps showing the initials placeholder.
     */
    private function syncAvatar(Participant $participant): void
    {
        if (filled($participant->avatar_url)) {
            return;
        }

        $bytes = $this->telegram->fetchUserProfilePhoto($participant->telegram_user_id);

        if ($bytes === null) {
            return;
        }

        $path = "avatars/{$participant->id}.jpg";
        Storage::disk('public')->put($path, $bytes);

        $participant->forceFill(['avatar_url' => '/storage/'.$path])->save();
    }
}
