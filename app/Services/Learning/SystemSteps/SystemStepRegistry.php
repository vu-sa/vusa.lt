<?php

namespace App\Services\Learning\SystemSteps;

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\ResponsibilityPurpose;

final class SystemStepRegistry
{
    /** @return array<string, array{audience: LearningAudienceType|ResponsibilityPurpose, stage: LearningStage, importance: LearningImportance, per_term: bool, skippable: bool, actionRoute: string|null, detector: class-string<SystemStepDetector>|null, title: string, description: string, action: string}> */
    public static function definitions(): array
    {
        $definitions = [
            'check_access' => [LearningAudienceType::Everyone, LearningStage::BeforeStart, LearningImportance::Essential, false, 'profile.roles', null],
            'review_contacts' => [LearningAudienceType::Everyone, LearningStage::BeforeStart, LearningImportance::Recommended, false, 'profile', null],
            'notification_settings' => [LearningAudienceType::Everyone, LearningStage::BeforeStart, LearningImportance::Recommended, false, 'profile.notifications', NotificationSettingsDetector::class],
            'profile_photo' => [LearningAudienceType::Everyone, LearningStage::BeforeStart, LearningImportance::Optional, false, 'profile', ProfilePhotoDetector::class],
            'install_app' => [LearningAudienceType::Everyone, LearningStage::BeforeStart, LearningImportance::Optional, false, null, null],
            'browser_push' => [LearningAudienceType::Everyone, LearningStage::BeforeStart, LearningImportance::Optional, false, null, BrowserPushDetector::class],
            'orientation' => [LearningAudienceType::Everyone, LearningStage::BeforeStart, LearningImportance::Recommended, false, 'tasks.index', null],
            'meet_coordinator' => [ResponsibilityPurpose::StudentRepresentation, LearningStage::BeforeStart, LearningImportance::Essential, true, null, null],
            'follow_institution' => [ResponsibilityPurpose::StudentRepresentation, LearningStage::BeforeStart, LearningImportance::Recommended, false, null, InstitutionFollowDetector::class],
            'meet_institution_contacts' => [ResponsibilityPurpose::StudentRepresentation, LearningStage::FirstMonths, LearningImportance::Recommended, true, null, null],
            'record_first_meeting' => [ResponsibilityPurpose::StudentRepresentation, LearningStage::FirstMonths, LearningImportance::Essential, false, null, RecordedMeetingDetector::class],
            'read_handover' => [ResponsibilityPurpose::StudentRepresentation, LearningStage::BeforeStart, LearningImportance::Essential, true, null, null],
            'write_handover' => [ResponsibilityPurpose::StudentRepresentation, LearningStage::TermEnd, LearningImportance::Essential, true, null, WriteHandoverDetector::class],
        ];
        $result = [];
        foreach ($definitions as $key => [$audience, $stage, $importance, $perTerm, $route, $detector]) {
            $result[$key] = [
                'audience' => $audience, 'stage' => $stage, 'importance' => $importance,
                'per_term' => $perTerm, 'skippable' => $importance->isSkippable(),
                'actionRoute' => $route, 'detector' => $detector,
                'title' => "learning.system_steps.{$key}.title",
                'description' => "learning.system_steps.{$key}.description",
                'action' => "learning.system_steps.{$key}.action",
            ];
        }

        return $result;
    }
}
