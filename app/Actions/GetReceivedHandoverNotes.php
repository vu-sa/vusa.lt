<?php

namespace App\Actions;

use App\Models\HandoverNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class GetReceivedHandoverNotes
{
    /** @return Collection<int, HandoverNote> */
    public static function execute(User $user): Collection
    {
        return self::forUsers(collect([$user]))->get($user->id, collect());
    }

    /**
     * @param  Collection<int, User>  $users
     * @return Collection<string, Collection<int, HandoverNote>>
     */
    public static function forUsers(Collection $users): Collection
    {
        (new EloquentCollection($users->all()))->load([
            'duties' => fn ($query) => $query->where(fn ($dates) => $dates->whereNull('dutiables.end_date')->orWhereDate('dutiables.end_date', '>=', today()->subDays(60))),
            'duties.predecessors' => fn ($query) => $query->withTrashed(),
        ]);
        $ids = $users->flatMap(fn (User $user) => $user->duties)->flatMap(fn ($duty) => $duty->predecessors->pluck('id')->push($duty->id))->unique();
        $notes = HandoverNote::query()->whereIn('duty_id', $ids)->with(['author', 'duty.institution', 'dutiable', 'cadence'])->get()
            ->sortByDesc(fn (HandoverNote $note) => $note->cadence?->start_date ?? $note->dutiable?->start_date ?? $note->created_at)->values();
        $result = collect();
        foreach ($users as $user) {
            $visibleIds = $user->duties->flatMap(fn ($duty) => $duty->predecessors->pluck('id')->push($duty->id));
            $result->put($user->id, $notes->filter(fn (HandoverNote $note): bool => $note->user_id !== $user->id && $visibleIds->contains($note->duty_id))->values());
        }

        return $result;
    }
}
