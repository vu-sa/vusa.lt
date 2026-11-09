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
     * @param  Collection<int, ResolvedLearningStep>  $meetingPrep
     */
    public function __construct(
        public User $user,
        public ?LearningStage $currentStage,
        public ?Carbon $termStart,
        public Collection $steps,
        public Collection $meetingPrep,
    ) {}

    /** @return array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int} */
    public function totals(): array
    {
        $essential = $this->steps->filter(fn (ResolvedLearningStep $item): bool => ! $item->step->importance->isSkippable());

        return [
            'essentialDone' => $essential->where('state', 'done')->count(),
            'essentialTotal' => $essential->count(),
            'allDone' => $this->steps->filter(fn (ResolvedLearningStep $item): bool => in_array($item->state, ['done', 'skipped'], true))->count(),
            'allTotal' => $this->steps->count(),
        ];
    }

    public function contains(string $id): bool
    {
        return $this->steps->contains(fn (ResolvedLearningStep $item): bool => $item->step->id === $id)
            || $this->meetingPrep->contains(fn (ResolvedLearningStep $item): bool => $item->step->id === $id);
    }
}
