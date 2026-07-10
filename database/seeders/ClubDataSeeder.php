<?php

namespace Database\Seeders;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ClubDataSeeder extends Seeder
{
    public function run(): void
    {
        $participants = $this->seedParticipants();
        $this->seedWeeklyHistory($participants);
    }

    /**
     * @return Collection<int, Participant>
     */
    private function seedParticipants(): Collection
    {
        $names = [
            ['Олексій', 'Мироненко'],
            ['Ірина', 'Ковальчук'],
            ['Андрій', 'Бондаренко'],
            ['Марина', 'Ткаченко'],
            ['Сергій', 'Шевченко'],
            ['Оксана', 'Мельник'],
            ['Дмитро', 'Кравець'],
            ['Наталія', 'Поліщук'],
            ['Віталій', 'Гончар'],
            ['Юлія', 'Савченко'],
            ['Максим', 'Лисенко'],
            ['Катерина', 'Руденко'],
            ['Богдан', 'Іваненко'],
            ['Софія', 'Павленко'],
        ];

        return collect($names)->map(function (array $pair, int $index) {
            [$first, $last] = $pair;

            return Participant::create([
                'telegram_user_id' => 100000001 + $index,
                'telegram_username' => fake()->boolean(70) ? Str::lower($last).$index : null,
                'first_name' => $first,
                'last_name' => $last,
                'display_name' => "{$first} {$last}",
                'is_active' => true,
                'first_seen_at' => now()->subMonths(14),
                'last_seen_at' => now(),
            ]);
        });
    }

    /**
     * Builds a continuous ~60-week chain of periods (spanning a full year
     * boundary) ending at the real current week, using the exact same
     * WeekResolverService the app uses in production — so seeded data and
     * live data always follow identical (year, week_number) rules.
     *
     * @param  Collection<int, Participant>  $participants
     */
    private function seedWeeklyHistory(Collection $participants): void
    {
        $resolver = app(WeekResolverService::class);
        $timezone = config('velotor.timezone');
        $now = Carbon::now($timezone);

        [$currentStart] = $resolver->computeWindowForDate($now);
        $firstStart = $currentStart->copy()->subWeeks(60);

        $period = WeeklyPeriod::create([
            'year' => $firstStart->year,
            'week_number' => 1,
            'title' => "Тиждень 1 / {$firstStart->year}",
            'start_date' => $firstStart,
            'end_date' => $firstStart->copy()->addDays(7),
            'status' => 'active',
            'total_distance' => 0,
        ]);

        $profiles = $participants->mapWithKeys(fn (Participant $p) => [
            $p->id => [
                'chance' => fake()->numberBetween(45, 95),
                'min' => fake()->numberBetween(5, 15),
                'max' => fake()->numberBetween(25, 60),
            ],
        ]);

        $safetyLimit = 80;

        while ($safetyLimit-- > 0) {
            $isCurrentWeek = $period->start_date->equalTo($currentStart);

            foreach ($participants as $participant) {
                $profile = $profiles[$participant->id];

                if (! fake()->boolean($profile['chance'])) {
                    continue;
                }

                $ridesCount = $isCurrentWeek ? random_int(0, 2) : random_int(1, 3);

                for ($r = 0; $r < $ridesCount; $r++) {
                    $distance = fake()->randomFloat(2, $profile['min'], $profile['max']);
                    $createdAt = $period->start_date->copy()->addHours(fake()->numberBetween(6, 160));

                    if ($isCurrentWeek) {
                        $latestAllowed = $now->copy()->subMinutes(random_int(5, 240));
                        $createdAt = $createdAt->greaterThan($latestAllowed) ? $latestAllowed : $createdAt;
                    }

                    RideResult::create([
                        'participant_id' => $participant->id,
                        'weekly_period_id' => $period->id,
                        'distance_km' => $distance,
                        'raw_message' => "результат {$distance} км",
                        'source' => 'telegram',
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }
            }

            if ($isCurrentWeek) {
                break;
            }

            $total = (float) RideResult::where('weekly_period_id', $period->id)->sum('distance_km');

            $period->update([
                'status' => 'closed',
                'total_distance' => $total,
                'report_sent_at' => $period->end_date,
                'closed_at' => $period->end_date,
            ]);

            $period = $resolver->ensureNextPeriodExists($period);
        }
    }
}
