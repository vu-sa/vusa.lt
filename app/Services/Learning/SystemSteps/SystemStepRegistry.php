<?php

namespace App\Services\Learning\SystemSteps;

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\ResponsibilityPurpose;

final class SystemStepRegistry
{
    /** @return array<string, array{audience: LearningAudienceType|ResponsibilityPurpose, stage: LearningStage, importance: LearningImportance, skippable: bool, detector: class-string<SystemStepDetector>|null, title: string, description: string, action: string}> */
    public static function definitions(): array
    {
        $definitions = [
            'install_app' => [LearningAudienceType::Everyone, LearningStage::BeforeStart, LearningImportance::Optional, null],
            'browser_push' => [LearningAudienceType::Everyone, LearningStage::BeforeStart, LearningImportance::Optional, BrowserPushDetector::class],
            'meet_coordinator' => [ResponsibilityPurpose::StudentRepresentation, LearningStage::BeforeStart, LearningImportance::Essential, null],
            'record_first_meeting' => [ResponsibilityPurpose::StudentRepresentation, LearningStage::FirstMonths, LearningImportance::Essential, RecordedMeetingDetector::class],
        ];
        $result = [];
        foreach ($definitions as $key => [$audience, $stage, $importance, $detector]) {
            $result[$key] = [
                'audience' => $audience, 'stage' => $stage, 'importance' => $importance,
                'skippable' => $importance->isSkippable(),
                'detector' => $detector,
                'title' => "learning.system_steps.{$key}.title",
                'description' => "learning.system_steps.{$key}.description",
                'action' => "learning.system_steps.{$key}.action",
            ];
        }

        return $result;
    }
}
