<?php

namespace App\Console\Commands;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Stats\StatsRecalculationService;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ImportLegacyResults extends Command
{
    private const PARTICIPANT_SOURCE = 'legacy_ride_users';

    private const RESULT_SOURCE = 'legacy_ride_results';

    private const SYNTHETIC_TELEGRAM_ID_BASE = 9_000_000_000_000_000;

    protected $signature = 'legacy:import-results
        {path : Absolute path to the legacy phpMyAdmin SQL dump}
        {--year=2026 : Import results whose created_at falls in this calendar year}
        {--dry-run : Parse and summarize without writing anything}
        {--remove-demo-data : Remove deterministic ClubDataSeeder participants and rides before importing}';

    protected $description = 'Import legacy ride_results into participants, ISO weekly periods, and ride results';

    public function handle(WeekResolverService $weeks, StatsRecalculationService $stats): int
    {
        $path = (string) $this->argument('path');
        $year = (int) $this->option('year');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("SQL dump is not readable: {$path}");

            return self::FAILURE;
        }

        $sql = file_get_contents($path);

        if ($sql === false) {
            $this->error("Could not read SQL dump: {$path}");

            return self::FAILURE;
        }

        try {
            $legacyUsers = $this->parseUsers($sql);
            $allResults = $this->parseResults($sql);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $results = $allResults
            ->filter(fn (array $row) => Carbon::parse($row['created_at'], config('velotor.timezone'))->year === $year)
            ->values();
        $validResults = $results->filter(fn (array $row) => $row['distance'] > 0)->values();
        $skippedZero = $results->count() - $validResults->count();
        $participants = $validResults->pluck('login')->unique()->sort()->values();
        $totalDistance = round((float) $validResults->sum('distance'), 2);
        $yearStart = Carbon::create($year, 1, 1, 0, 0, 0, config('velotor.timezone'));
        $yearEnd = $yearStart->copy()->addYear();
        $currentYearResults = RideResult::where('created_at', '>=', $yearStart)
            ->where('created_at', '<', $yearEnd)
            ->count();
        $demoParticipantIds = Participant::whereBetween('telegram_user_id', [100_000_001, 100_000_014])->pluck('id');
        $demoResults = RideResult::whereIn('participant_id', $demoParticipantIds)->count();

        $this->table(
            ['Показник', 'Значення'],
            [
                ['Календарний рік created_at', $year],
                ['Результатів у дампі', $results->count()],
                ['Валідних результатів (> 0 км)', $validResults->count()],
                ['Пропущено нульових', $skippedZero],
                ['Учасників', $participants->count()],
                ['Загальна дистанція', number_format($totalDistance, 2, '.', ' ').' км'],
                ['Період', $validResults->min('created_at').' — '.$validResults->max('created_at')],
                ['Зараз результатів у новій БД за рік', $currentYearResults],
                ['Знайдено демонстраційних результатів', $demoResults],
            ],
        );

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->info('Dry run: database was not changed.');

            return self::SUCCESS;
        }

        $removeDemoData = (bool) $this->option('remove-demo-data');

        $counters = DB::transaction(function () use ($validResults, $legacyUsers, $weeks, $stats, $removeDemoData) {
            $counters = [
                'participants_created' => 0,
                'results_created' => 0,
                'results_existing' => 0,
                'demo_participants_removed' => 0,
                'demo_results_removed' => 0,
            ];
            $participantCache = [];

            if ($removeDemoData) {
                $demoIds = Participant::whereBetween('telegram_user_id', [100_000_001, 100_000_014])->pluck('id');
                $counters['demo_results_removed'] = RideResult::whereIn('participant_id', $demoIds)->delete();
                $counters['demo_participants_removed'] = Participant::whereIn('id', $demoIds)->delete();
            }

            foreach ($validResults as $legacyResult) {
                $loginKey = $this->loginKey($legacyResult['login']);
                $legacyUser = $legacyUsers->get($loginKey);

                if (! isset($participantCache[$loginKey])) {
                    [$participant, $created] = $this->resolveParticipant($legacyResult, $legacyUser);
                    $participantCache[$loginKey] = $participant;
                    $counters['participants_created'] += $created ? 1 : 0;
                }

                $participant = $participantCache[$loginKey];
                $createdAt = Carbon::parse($legacyResult['created_at'], config('velotor.timezone'));
                $period = $this->resolvePeriod($createdAt, $weeks);

                $existing = RideResult::where('legacy_source', self::RESULT_SOURCE)
                    ->where('legacy_id', $legacyResult['id'])
                    ->first();

                if (! $existing) {
                    $existing = RideResult::where('participant_id', $participant->id)
                        ->where('distance_km', $legacyResult['distance'])
                        ->where('created_at', $createdAt->format('Y-m-d H:i:s'))
                        ->first();
                }

                if ($existing) {
                    if (! $existing->legacy_source) {
                        $existing->update([
                            'legacy_source' => self::RESULT_SOURCE,
                            'legacy_id' => $legacyResult['id'],
                        ]);
                    }
                    $counters['results_existing']++;

                    continue;
                }

                $result = new RideResult([
                    'legacy_source' => self::RESULT_SOURCE,
                    'legacy_id' => $legacyResult['id'],
                    'participant_id' => $participant->id,
                    'weekly_period_id' => $period->id,
                    'distance_km' => $legacyResult['distance'],
                    'raw_message' => "Імпорт зі старого сайту · legacy #{$legacyResult['id']}",
                    'source' => 'admin',
                ]);
                $result->created_at = $createdAt;
                $result->updated_at = Carbon::parse($legacyResult['updated_at'], config('velotor.timezone'));
                $result->save();
                $counters['results_created']++;
            }

            $stats->recalculateAll();

            return $counters;
        });

        $this->newLine();
        $this->info("Created participants: {$counters['participants_created']}");
        $this->info("Imported results: {$counters['results_created']}");
        $this->info("Already present: {$counters['results_existing']}");
        if ($removeDemoData) {
            $this->info("Removed demo results: {$counters['demo_results_removed']}");
            $this->info("Removed demo participants: {$counters['demo_participants_removed']}");
        }

        $importedForYear = RideResult::where('legacy_source', self::RESULT_SOURCE)
            ->where('created_at', '>=', $yearStart)
            ->where('created_at', '<', $yearEnd);
        $this->info('Legacy results in database for year: '.$importedForYear->count());
        $this->info('Legacy distance in database for year: '.number_format((float) $importedForYear->sum('distance_km'), 2, '.', ' ').' km');

        return self::SUCCESS;
    }

    /** @return Collection<string, array<string, mixed>> */
    private function parseUsers(string $sql): Collection
    {
        $block = $this->insertBlock($sql, 'ride_users');
        preg_match_all(
            "/\\((\\d+), '((?:\\\\.|[^'])*)', (-?[0-9.]+), (-?[0-9.]+), (\\d+), (\\d+), '([^']+)', '([^']+)'\\)/u",
            $block,
            $matches,
            PREG_SET_ORDER,
        );

        return collect($matches)->mapWithKeys(function (array $match) {
            $login = $this->normalizeLogin($match[2]);

            return [$this->loginKey($login) => [
                'id' => (int) $match[1],
                'login' => $login,
                'created_at' => $match[7],
                'updated_at' => $match[8],
            ]];
        });
    }

    /** @return Collection<int, array<string, mixed>> */
    private function parseResults(string $sql): Collection
    {
        $block = $this->insertBlock($sql, 'ride_results');
        preg_match_all(
            "/\\((\\d+), '((?:\\\\.|[^'])*)', (-?[0-9.]+), (\\d+), (\\d+), '([^']+)', '([^']+)'\\)/u",
            $block,
            $matches,
            PREG_SET_ORDER,
        );

        if ($matches === []) {
            throw new RuntimeException('No legacy ride_results rows were found in the SQL dump.');
        }

        return collect($matches)->map(fn (array $match) => [
            'id' => (int) $match[1],
            'login' => $this->normalizeLogin($match[2]),
            'distance' => round((float) $match[3], 2),
            'legacy_week' => (int) $match[4],
            'legacy_year' => (int) $match[5],
            'created_at' => $match[6],
            'updated_at' => $match[7],
        ]);
    }

    private function insertBlock(string $sql, string $table): string
    {
        if (! preg_match("/INSERT INTO `{$table}` .*? VALUES\\s*(.*?);/su", $sql, $match)) {
            throw new RuntimeException("INSERT block for `{$table}` was not found in the SQL dump.");
        }

        return $match[1];
    }

    /** @return array{Participant, bool} */
    private function resolveParticipant(array $result, ?array $legacyUser): array
    {
        $legacyId = $legacyUser['id'] ?? (1_000_000 + (int) sprintf('%u', crc32($this->loginKey($result['login']))));
        $participant = Participant::where('legacy_source', self::PARTICIPANT_SOURCE)
            ->where('legacy_id', $legacyId)
            ->first();

        if (! $participant) {
            $participant = Participant::where('display_name', $result['login'])->first();
        }

        if ($participant) {
            if (! $participant->legacy_source) {
                $participant->update([
                    'legacy_source' => self::PARTICIPANT_SOURCE,
                    'legacy_id' => $legacyId,
                ]);
            }

            return [$participant, false];
        }

        $createdAt = $legacyUser['created_at'] ?? $result['created_at'];
        $updatedAt = $legacyUser['updated_at'] ?? $result['updated_at'];

        return [Participant::create([
            'telegram_user_id' => self::SYNTHETIC_TELEGRAM_ID_BASE + $legacyId,
            'legacy_source' => self::PARTICIPANT_SOURCE,
            'legacy_id' => $legacyId,
            'display_name' => $result['login'],
            'is_active' => true,
            'first_seen_at' => $createdAt,
            'last_seen_at' => $updatedAt,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]), true];
    }

    private function resolvePeriod(Carbon $date, WeekResolverService $weeks): WeeklyPeriod
    {
        if ($period = $weeks->periodForDate($date)) {
            return $period;
        }

        [$start, $end] = $weeks->computeWindowForDate($date);
        $year = (int) $start->isoWeekYear;
        $weekNumber = (int) $start->isoWeek;

        return WeeklyPeriod::firstOrCreate(
            ['year' => $year, 'week_number' => $weekNumber],
            [
                'title' => "Тиждень {$weekNumber} / {$year}",
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'status' => $end->isPast() ? 'closed' : 'active',
                'total_distance' => 0,
            ],
        );
    }

    private function normalizeLogin(string $login): string
    {
        $login = stripslashes($login);
        $login = preg_replace('/[\\s\\x{00A0}]+/u', ' ', $login) ?? $login;

        return trim($login);
    }

    private function loginKey(string $login): string
    {
        return mb_strtolower($this->normalizeLogin($login), 'UTF-8');
    }
}
