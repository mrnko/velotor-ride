<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\RideResult;
use App\Services\Torcoins\TorcoinCalculator;
use Inertia\Inertia;
use Inertia\Response;

class ParticipantController extends Controller
{
    public function index(): Response
    {
        $participants = Participant::orderBy('display_name')->get();

        $totals = RideResult::selectRaw('participant_id, SUM(distance_km) as total_distance, COUNT(*) as rides_count')
            ->groupBy('participant_id')
            ->get()
            ->keyBy('participant_id');

        return Inertia::render('Admin/Participants/Index', [
            'participants' => $participants->map(function (Participant $participant) use ($totals) {
                $total = $totals->get($participant->id);
                $distance = $total ? (float) $total->total_distance : 0.0;

                return [
                    'id' => $participant->id,
                    'display_name' => $participant->display_name,
                    'telegram_username' => $participant->telegram_username,
                    'telegram_user_id' => $participant->telegram_user_id,
                    'is_active' => $participant->is_active,
                    'first_seen_at' => $participant->first_seen_at?->toDateString(),
                    'last_seen_at' => $participant->last_seen_at?->toDateString(),
                    'total_distance' => $distance,
                    'rides_count' => $total ? (int) $total->rides_count : 0,
                    'torcoins_all_time' => TorcoinCalculator::fromDistance($distance),
                ];
            }),
        ]);
    }
}
