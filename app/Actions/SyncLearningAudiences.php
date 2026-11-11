<?php

namespace App\Actions;

use App\Enums\LearningAudienceType;
use App\Models\KnowledgeItem;
use App\Models\LearningStep;

class SyncLearningAudiences
{
    public static function execute(LearningStep|KnowledgeItem $step, array $audiences): void
    {
        $kept = [];
        foreach ($audiences as $data) {
            $id = $data['id'] ?? null;
            $audience = $id !== null ? $step->audiences()->find($id) : null;
            abort_if($id !== null && $audience === null, 403);
            unset($data['id']);
            if ($step instanceof LearningStep && $step->is_system) {
                abort_if($audience === null, 403);
                $audience->update(collect($data)->only(['stage', 'order'])->all());
            } else {
                $data = array_replace(['tenant_id' => null, 'stage' => null, 'description' => null, 'order' => 0], $data);
                if ($step instanceof KnowledgeItem) {
                    $data['stage'] = null;
                }
                $data['source_id'] = $data['type'] === LearningAudienceType::Everyone->value ? null : $data['source_id'];
                if ($audience !== null) {
                    $audience->update($data);
                } else {
                    $audience = $step->audiences()->create($data);
                }
            }
            $kept[] = $audience->id;
        }
        if (! $step instanceof LearningStep || ! $step->is_system) {
            $step->audiences()->whereNotIn('id', $kept)->delete();
        }
    }
}
