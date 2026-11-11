<?php

namespace App\Http\Requests\Concerns;

use App\Enums\LearningAudienceType;
use App\Enums\ResponsibilityPurpose;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Validator;

trait ValidatesLearningAudiences
{
    protected function prepareForValidation(): void
    {
        $audiences = $this->input('audiences');
        if (! is_array($audiences)) {
            return;
        }
        foreach ($audiences as &$audience) {
            if (is_array($audience) && (is_string($audience['source_id'] ?? null) || is_int($audience['source_id'] ?? null))) {
                $audience['source_id'] = strtolower(trim((string) $audience['source_id']));
            }
        }
        $this->merge(['audiences' => $audiences]);
    }

    protected function validateAudienceSources(Validator $validator, array $data): void
    {
        $tables = ['duty_type' => 'duty_types', 'institution_type' => 'institution_types', 'responsibility' => 'roles', 'duty' => 'duties', 'institution' => 'institutions'];
        foreach ($data['audiences'] as $index => $audience) {
            $type = LearningAudienceType::from($audience['type']);
            if ($type === LearningAudienceType::Purpose) {
                if (ResponsibilityPurpose::tryFrom((string) $audience['source_id']) === null) {
                    $validator->errors()->add("audiences.{$index}.source_id", __('learning.validation.audience_source'));
                }

                continue;
            }
            if ($type !== LearningAudienceType::Everyone) {
                if (in_array($type, [LearningAudienceType::DutyType, LearningAudienceType::InstitutionType], true) && ! ctype_digit($audience['source_id'])) {
                    $validator->errors()->add("audiences.{$index}.source_id", __('learning.validation.audience_source'));

                    continue;
                }
                $query = DB::table($tables[$type->value])->where('id', $audience['source_id']);
                if (in_array($type, [LearningAudienceType::DutyType, LearningAudienceType::InstitutionType, LearningAudienceType::Duty, LearningAudienceType::Institution], true)) {
                    $query->whereNull('deleted_at');
                }
                if (! $query->exists()) {
                    $validator->errors()->add("audiences.{$index}.source_id", __('learning.validation.audience_source'));
                }
            }
        }
    }
}
