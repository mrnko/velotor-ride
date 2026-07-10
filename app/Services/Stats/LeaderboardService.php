<?php

namespace App\Services\Stats;

use App\Models\Participant;
use App\Models\RideResult;
use App\Models\WeeklyPeriod;
use App\Services\Torcoins\TorcoinCalculator;
use App\Services\Weeks\WeekResolverService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Shared ranking queries used by both the Telegram bot commands and the
 * public site pages, so "who's in the lead" is computed in exactly one
 * place regardless of where it's displayed.
 */
class LeaderboardService
{
    /**
     * @return Collection<int, array{rank: int, participant: Participant, distance_km: float, rides_count: int}>
     */
    public function forPeriod(WeeklyPeriod $period, ?int $limit = null): Collection
    {
        return $this->aggregate(RideResult::where('weekly_period_id', $period->id), $limit);
    }

    /**
     * @return Collection<int, array{rank: int, participant: Participant, distance_km: float, rides_count: int}>
     */
    public function forYear(int $year, ?int $limit = null): Collection
    {
        $periodIds = WeeklyPeriod::where('year', $year)->pluck('id');

        return $this->aggregate(RideResult::whereIn('weekly_period_id', $periodIds), $limit);
    }

    /**
     * @return Collection<int, array{rank: int, participant: Participant, distance_km: float, rides_count: int}>
     */
    public function allTime(?int $limit = null): Collection
    {
        return $this->aggregate(RideResult::query(), $limit);
    }

    public function rankOf(Collection $leaderboard, int $participantId): ?int
    {
        $row = $leaderboard->first(fn (array $row) => $row['participant']->id === $participantId);

        return $row['rank'] ?? null;
    }

    /**
     * Full stat block for a single participant, as shown in /me and on the
     * participant profile page.
     */
    public function participantSummary(Participant $participant, WeekResolverService $resolver): array
    {
        $activePeriod = $resolver->activePeriod();
        $previousPeriod = $resolver->previousPeriod($activePeriod);
        $currentYear = $activePeriod->year;

        $currentWeekDistance = $this->participantDistanceInPeriod($participant, $activePeriod);
        $lastWeekDistance = $previousPeriod ? $this->participantDistanceInPeriod($participant, $previousPeriod) : 0.0;

        $yearPeriodIds = WeeklyPeriod::where('year', $currentYear)->pluck('id');
        $yearDistance = (float) RideResult::where('participant_id', $participant->id)
            ->whereIn('weekly_period_id', $yearPeriodIds)
            ->sum('distance_km');

        $allTimeDistance = (float) RideResult::where('participant_id', $participant->id)->sum('distance_km');

        $weekLeaderboard = $this->forPeriod($activePeriod);
        $yearLeaderboard = $this->forYear($currentYear);

        return [
            'active_period' => $activePeriod,
            'previous_period' => $previousPeriod,
            'current_week_distance' => $currentWeekDistance,
            'last_week_distance' => $lastWeekDistance,
            'year_distance' => $yearDistance,
            'all_time_distance' => $allTimeDistance,
            'torcoins_year' => TorcoinCalculator::fromDistance($yearDistance),
            'torcoins_all_time' => TorcoinCalculator::fromDistance($allTimeDistance),
            'rank_week' => $this->rankOf($weekLeaderboard, $participant->id),
            'rank_year' => $this->rankOf($yearLeaderboard, $participant->id),
        ];
    }

    public function participantDistanceInPeriod(Participant $participant, WeeklyPeriod $period): float
    {
        return (float) RideResult::where('participant_id', $participant->id)
            ->where('weekly_period_id', $period->id)
            ->sum('distance_km');
    }

    /**
     * @return Collection<int, array{rank: int, participant: Participant, distance_km: float, rides_count: int}>
     */
    private function aggregate(Builder $query, ?int $limit): Collection
    {
        $query = $query->selectRaw('participant_id, SUM(distance_km) as total_distance, COUNT(*) as rides_count')
            ->groupBy('participant_id')
            ->orderByDesc('total_distance');

        if ($limit) {
            $query->limit($limit);
        }

        $rows = $query->get();
        $participants = Participant::whereIn('id', $rows->pluck('participant_id'))->get()->keyBy('id');

        return $rows->values()->map(fn ($row, int $index) => [
            'rank' => $index + 1,
            'participant' => $participants[$row->participant_id],
            'distance_km' => (float) $row->total_distance,
            'rides_count' => (int) $row->rides_count,
        ]);
    }
}
