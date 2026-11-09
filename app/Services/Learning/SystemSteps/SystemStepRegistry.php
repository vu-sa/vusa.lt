<?php

namespace App\Services\Learning\SystemSteps;

use App\Enums\LearningImportance;
use App\Enums\LearningLayer;
use App\Enums\LearningStage;

final class SystemStepRegistry
{
    /** @return array<string, array{layer: LearningLayer, stage: LearningStage, importance: LearningImportance, per_term: bool, skippable: bool, actionRoute: string|null, detector: class-string<SystemStepDetector>|null, title: string, description: string, action: string}> */
    public static function definitions(): array
    {
        $definitions = [
            'check_access' => [LearningLayer::Platform, LearningStage::BeforeStart, LearningImportance::Essential, false, 'profile.roles', null],
            'review_contacts' => [LearningLayer::Platform, LearningStage::BeforeStart, LearningImportance::Recommended, false, 'profile', null],
            'notification_settings' => [LearningLayer::Platform, LearningStage::BeforeStart, LearningImportance::Recommended, false, 'profile.notifications', NotificationSettingsDetector::class],
            'profile_photo' => [LearningLayer::Platform, LearningStage::BeforeStart, LearningImportance::Optional, false, 'profile', ProfilePhotoDetector::class],
            'install_app' => [LearningLayer::Platform, LearningStage::BeforeStart, LearningImportance::Optional, false, null, null],
            'browser_push' => [LearningLayer::Platform, LearningStage::BeforeStart, LearningImportance::Optional, false, null, BrowserPushDetector::class],
            'orientation' => [LearningLayer::Platform, LearningStage::BeforeStart, LearningImportance::Recommended, false, 'tasks.index', null],
            'meet_coordinator' => [LearningLayer::Foundation, LearningStage::BeforeStart, LearningImportance::Essential, true, null, null],
            'follow_institution' => [LearningLayer::Foundation, LearningStage::BeforeStart, LearningImportance::Recommended, false, null, InstitutionFollowDetector::class],
            'meet_institution_contacts' => [LearningLayer::Foundation, LearningStage::FirstMonths, LearningImportance::Recommended, true, null, null],
            'record_first_meeting' => [LearningLayer::Foundation, LearningStage::FirstMonths, LearningImportance::Essential, false, null, RecordedMeetingDetector::class],
            'read_handover' => [LearningLayer::Foundation, LearningStage::BeforeStart, LearningImportance::Essential, true, null, null],
            'write_handover' => [LearningLayer::Foundation, LearningStage::TermEnd, LearningImportance::Essential, true, null, WriteHandoverDetector::class],
        ];
        $result = [];
        foreach ($definitions as $key => [$layer, $stage, $importance, $perTerm, $route, $detector]) {
            $result[$key] = [
                'layer' => $layer, 'stage' => $stage, 'importance' => $importance,
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
