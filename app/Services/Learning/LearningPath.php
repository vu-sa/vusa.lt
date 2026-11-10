<?php

namespace App\Services\Learning;

use App\Enums\LearningStage;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class LearningPath
{
    /**
     * @param  Collection<int, ResolvedLearningStep>  $steps
     * @param  Collection<int, ResolvedLearningStep>  $documents
     * @param  Collection<int, ResolvedLearningStep>  $meetingPrep
     */
    public function __construct(
        public User $user,
        public ?LearningStage $currentStage,
        public ?Carbon $termStart,
        public Collection $steps,
        public Collection $documents,
        public Collection $meetingPrep,
    ) {}

    /** @return array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int} */
    public function totals(): array
    {
        $essential = $this->steps->filter(fn (ResolvedLearningStep $item): bool => ! $item->importance->isSkippable());

        return [
            'essentialDone' => $essential->where('state', 'done')->count(),
            'essentialTotal' => $essential->count(),
            'allDone' => $this->steps->filter(fn (ResolvedLearningStep $item): bool => in_array($item->state, ['done', 'skipped'], true))->count(),
            'allTotal' => $this->steps->count(),
        ];
    }

    /** @return array{essentialDone: int, essentialTotal: int, done: int, total: int} */
    public function documentTotals(): array
    {
        $essential = $this->documents->filter(fn (ResolvedLearningStep $item): bool => ! $item->importance->isSkippable());

        return [
            'essentialDone' => $essential->where('state', 'done')->count(),
            'essentialTotal' => $essential->count(),
            'done' => $this->documents->filter(fn (ResolvedLearningStep $item): bool => in_array($item->state, ['done', 'skipped'], true))->count(),
            'total' => $this->documents->count(),
        ];
    }

    public function contains(string $id): bool
    {
        return $this->documents->contains(fn (ResolvedLearningStep $item): bool => $item->step->id === $id)
            || $this->steps->contains(fn (ResolvedLearningStep $item): bool => $item->step->id === $id)
            || $this->meetingPrep->contains(fn (ResolvedLearningStep $item): bool => $item->step->id === $id);
    }
}
