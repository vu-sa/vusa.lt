<?php

namespace App\Services;

use App\Actions\CollapseOverlappingDutiables;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class UserMergeDutyEmails
{
    /** @param Collection<int, User> $sources
     * @return list<array<string, mixed>>
     */
    public function preview(User $kept, Collection $sources, string $loginEmail, User $actor): array
    {
        return $this->candidates($kept, $sources, $loginEmail, $actor)
            ->map(fn (array $option): array => [
                'id' => $option['period']->id,
                'source_user_id' => $option['source']->id,
                'email' => $option['source']->email,
                'duty' => $option['period']->duty->name,
                'institution' => $option['period']->duty->institution?->name,
                'start_date' => $option['period']->start_date->toDateString(),
                'end_date' => $option['period']->end_date?->toDateString(),
                'overlap_group' => $option['group'],
            ])->values()->all();
    }

    /** @param Collection<int, User> $sources
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    public function preserve(User $kept, Collection $sources, string $loginEmail, User $actor, array $ids): array
    {
        $selected = $sources->flatMap(fn (User $source) => $source->dutiables()->whereKey($ids)->get());
        abort_unless($selected->count() === count($ids), 403);
        foreach ($selected as $period) {
            abort_unless($actor->can('manageDutiable', $period), 403);
        }

        $options = $this->candidates($kept, $sources, $loginEmail, $actor, lock: true);
        $chosenGroups = [];
        $preserved = [];

        foreach ($ids as $id) {
            $option = $options->get($id);
            if ($option === null || isset($chosenGroups[$option['group']])) {
                throw ValidationException::withMessages(['dutiable_email_ids' => __('users.merge.email_selection_changed')]);
            }

            $chosenGroups[$option['group']] = true;
            $option['period']->update(['additional_email' => $option['source']->email]);
            $preserved[$id] = $option['source']->email;
        }

        return $preserved;
    }

    /** @param Collection<int, User> $sources
     * @return Collection<string, array{period: Dutiable, source: User, group: string}>
     */
    private function candidates(User $kept, Collection $sources, string $loginEmail, User $actor, bool $lock = false): Collection
    {
        $sourceMap = $sources->keyBy('id');
        $rows = Dutiable::query()
            ->where('dutiable_type', MorphMap::alias(User::class))
            ->whereIn('dutiable_id', [$kept->id, ...$sources->pluck('id')->all()])
            ->with(['duty.institution', 'user'])
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->get();
        $options = collect();

        foreach ($rows->groupBy(fn (Dutiable $row) => $row->duty_id.'|'.$row->tenant_id) as $group) {
            foreach (CollapseOverlappingDutiables::overlappingGroups($group) as $overlap) {
                if ($overlap->contains(fn (Dutiable $row) => filled($row->additional_email) || ! $actor->can('manageDutiable', $row))) {
                    continue;
                }

                foreach ($overlap as $row) {
                    $source = $sourceMap->get($row->dutiable_id);
                    if ($source !== null && filled($source->email) && strcasecmp($source->email, $loginEmail) !== 0) {
                        $options->put($row->id, ['period' => $row, 'source' => $source, 'group' => $overlap->first()->id]);
                    }
                }
            }
        }

        return $options;
    }
}
