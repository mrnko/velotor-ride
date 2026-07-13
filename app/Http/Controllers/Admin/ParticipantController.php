<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\RideResult;
use App\Services\Torcoins\TorcoinCalculator;
use App\Support\Transliterate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
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

        $possibleDuplicates = $this->findPossibleDuplicates($participants);

        return Inertia::render('Admin/Participants/Index', [
            'participants' => $participants->map(function (Participant $participant) use ($totals, $possibleDuplicates) {
                $total = $totals->get($participant->id);
                $distance = $total ? (float) $total->total_distance : 0.0;

                return [
                    'id' => $participant->id,
                    'display_name' => $participant->display_name,
                    'slug' => $participant->slug,
                    'profile_url' => $participant->slug
                        ? route('stat.participants.show', $participant->slug)
                        : null,
                    'telegram_username' => $participant->telegram_username,
                    'telegram_user_id' => $participant->telegram_user_id,
                    'is_active' => $participant->is_active,
                    'first_seen_at' => $participant->first_seen_at?->format('d.m.Y'),
                    'last_seen_at' => $participant->last_seen_at?->format('d.m.Y'),
                    'total_distance' => $distance,
                    'rides_count' => $total ? (int) $total->rides_count : 0,
                    'torcoins_all_time' => TorcoinCalculator::fromDistance($distance),
                    'possible_duplicates' => $possibleDuplicates->get($participant->id, []),
                ];
            }),
        ]);
    }

    /**
     * Flag participants whose (transliterated) names look like the same
     * person — e.g. a legacy-imported "Alex Kh" and a later Telegram-linked
     * "Alex Khrumalo". Two names match when they normalize to the same slug
     * or one is a prefix of the other; this is only a hint for the admin to
     * review and merge, never applied automatically.
     *
     * @return Collection<int, array<int, array{id: int, display_name: string}>>
     */
    private function findPossibleDuplicates(Collection $participants): Collection
    {
        $normalized = $participants->mapWithKeys(
            fn (Participant $p) => [$p->id => Transliterate::slug($p->display_name)]
        );

        $matches = [];

        foreach ($normalized as $idA => $slugA) {
            foreach ($normalized as $idB => $slugB) {
                if ($idA === $idB) {
                    continue;
                }

                $shorter = mb_strlen($slugA) <= mb_strlen($slugB) ? $slugA : $slugB;
                $longer = mb_strlen($slugA) <= mb_strlen($slugB) ? $slugB : $slugA;
                $looksLikeSamePerson = $slugA === $slugB
                    || (mb_strlen($shorter) >= 3 && str_starts_with($longer, $shorter));

                if ($looksLikeSamePerson) {
                    $matches[$idA][] = $idB;
                }
            }
        }

        $byId = $participants->keyBy('id');

        return collect($matches)->map(
            fn (array $ids) => collect($ids)->map(fn ($id) => [
                'id' => $id,
                'display_name' => $byId->get($id)->display_name,
            ])->all()
        );
    }

    /**
     * Edit a participant's display name and Telegram username from the admin.
     * Renaming regenerates the slug so the public profile URL follows the new
     * name; uniqueSlug() keeps it unique and excludes this participant on
     * collision, so re-saving an unchanged name is a no-op.
     */
    public function update(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'telegram_username' => ['nullable', 'string', 'max:255'],
        ]);

        $username = $validated['telegram_username'] ?? null;
        if ($username !== null) {
            $username = ltrim(trim($username), '@');
            $username = $username === '' ? null : $username;
        }

        $participant->display_name = $validated['display_name'];
        $participant->telegram_username = $username;

        if ($participant->isDirty('display_name')) {
            $participant->slug = $participant->uniqueSlug($validated['display_name']);
        }

        $participant->save();

        return redirect()->route('admin.participants.index')
            ->with('success', "Дані учасника «{$participant->display_name}» оновлено.");
    }

    /**
     * Merge a duplicate participant into another one, e.g. a legacy-imported
     * record (synthetic telegram_user_id, full ride history) and the same
     * person's real Telegram account created later by the bot. All ride
     * results move to the kept participant, and — if the one being removed
     * is the "real" Telegram-linked side — its telegram identity takes over
     * so future messages resolve straight to the kept participant.
     */
    public function merge(Request $request, Participant $participant): RedirectResponse
    {
        $validated = $request->validate([
            'into_id' => ['required', 'integer', 'exists:participants,id'],
        ]);

        $primary = Participant::findOrFail($validated['into_id']);
        $duplicate = $participant;

        if ($primary->is($duplicate)) {
            return back()->with('error', 'Не можна об\'єднати учасника із самим собою.');
        }

        DB::transaction(function () use ($primary, $duplicate) {
            RideResult::where('participant_id', $duplicate->id)->update(['participant_id' => $primary->id]);

            $transferTelegramIdentity = $duplicate->legacy_source === null && $primary->legacy_source !== null;

            $telegramFields = [
                'telegram_user_id' => $duplicate->telegram_user_id,
                'telegram_username' => $duplicate->telegram_username,
                'first_name' => $duplicate->first_name,
                'last_name' => $duplicate->last_name,
            ];

            $firstSeenAt = min($primary->first_seen_at, $duplicate->first_seen_at);
            $lastSeenAt = max($primary->last_seen_at, $duplicate->last_seen_at);
            $isActive = $primary->is_active || $duplicate->is_active;

            $duplicate->delete();

            $primary->update(array_merge(
                $transferTelegramIdentity ? $telegramFields : [],
                [
                    'first_seen_at' => $firstSeenAt,
                    'last_seen_at' => $lastSeenAt,
                    'is_active' => $isActive,
                ]
            ));
        });

        return redirect()->route('admin.participants.index')
            ->with('success', "Учасника «{$duplicate->display_name}» об'єднано з «{$primary->display_name}».");
    }
}
