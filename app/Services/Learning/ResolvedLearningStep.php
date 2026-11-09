<?php

namespace App\Services\Learning;

use App\Models\LearningStep;
use Illuminate\Support\Carbon;

final readonly class ResolvedLearningStep
{
    /** @param 'pending'|'done'|'skipped'|'updated' $state */
    public function __construct(
        public LearningStep $step,
        public string $state = 'pending',
        public bool $autoCompleted = false,
        public ?Carbon $completedAt = null,
    ) {}
}
