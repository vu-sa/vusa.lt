<?php

namespace App\Services\Learning;

use App\Enums\LearningStage;
use App\Models\KnowledgeItem;
use App\Models\LearningStep;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class LearningPath
{
    /**
     * @param  Collection<int, ResolvedLearningItem>  $steps
     * @param  Collection<int, ResolvedLearningItem>  $knowledge
     * @param  Collection<int, ResolvedLearningItem>  $meetingPrep
     */
    public function __construct(
        public User $user,
        public ?LearningStage $currentStage,
        public ?Carbon $termStart,
        public Collection $steps,
        public Collection $knowledge,
        public Collection $meetingPrep,
    ) {}

    /** @return array{essentialDone: int, essentialTotal: int, allDone: int, allTotal: int} */
    public function totals(): array
    {
        $essential = $this->steps->filter(fn (ResolvedLearningItem $item): bool => ! $item->importance->isSkippable());

        return [
            'essentialDone' => $essential->where('state', 'done')->count(),
            'essentialTotal' => $essential->count(),
            'allDone' => $this->steps->filter(fn (ResolvedLearningItem $item): bool => in_array($item->state, ['done', 'skipped'], true))->count(),
            'allTotal' => $this->steps->count(),
        ];
    }

    /** @return array{essentialDone: int, essentialTotal: int, done: int, total: int} */
    public function knowledgeTotals(): array
    {
        $essential = $this->knowledge->filter(fn (ResolvedLearningItem $item): bool => ! $item->importance->isSkippable());

        return [
            'essentialDone' => $essential->where('state', 'done')->count(),
            'essentialTotal' => $essential->count(),
            'done' => $this->knowledge->filter(fn (ResolvedLearningItem $item): bool => in_array($item->state, ['done', 'skipped'], true))->count(),
            'total' => $this->knowledge->count(),
        ];
    }

    public function contains(LearningStep|KnowledgeItem $model): bool
    {
        return $this->steps->concat($this->knowledge)->concat($this->meetingPrep)
            ->contains(fn (ResolvedLearningItem $item): bool => $item->item->is($model));
    }
}
