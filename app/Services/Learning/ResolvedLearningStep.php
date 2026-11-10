<?php

namespace App\Services\Learning;

use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Models\LearningStep;
use Illuminate\Support\Carbon;

final readonly class ResolvedLearningStep
{
    /**
     * @param  'pending'|'done'|'skipped'|'updated'  $state
     * @param  list<array{label: string, description: string}>  $audienceNotes
     */
    public function __construct(
        public LearningStep $step,
        public string $state = 'pending',
        public bool $autoCompleted = false,
        public ?Carbon $completedAt = null,
        public LearningImportance $importance = LearningImportance::Recommended,
        public ?LearningStage $stage = null,
        public string $audienceLabel = '',
        public array $audienceNotes = [],
        public ?string $audienceId = null,
    ) {}
}
