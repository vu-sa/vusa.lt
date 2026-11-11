<?php

namespace App\Http\Requests;

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Http\Requests\Concerns\ValidatesLearningAudiences;
use App\Models\LearningStep;
use App\Policies\LearningStepPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LearningStepRequest extends FormRequest
{
    use ValidatesLearningAudiences;

    public function authorize(): bool
    {
        $step = $this->route('learningStep');

        return $this->user()->can($step instanceof LearningStep ? 'update' : 'create', $step instanceof LearningStep ? $step : LearningStep::class);
    }

    public function rules(): array
    {
        if ($this->route('learningStep')?->is_system) {
            return [
                'sources' => ['prohibited'], 'learning_section_id' => ['prohibited'], 'knowledge_section_id' => ['prohibited'],
                'order' => ['sometimes', 'integer', 'min:0'], 'is_active' => ['sometimes', 'boolean'],
                'audiences' => ['sometimes', 'array', 'min:1'],
                'audiences.*' => ['array:id,stage,order'],
                'audiences.*.id' => ['required', 'string'],
                'audiences.*.stage' => ['sometimes', 'required', Rule::enum(LearningStage::class)],
                'audiences.*.order' => ['sometimes', 'integer', 'min:0'],
            ];
        }

        return [
            'kind' => ['required', Rule::enum(LearningStepKind::class)],
            'learning_section_id' => ['prohibited'],
            'knowledge_section_id' => ['prohibited'],
            'sources' => ['prohibited'],
            'audiences' => ['required', 'array', 'min:1'],
            'audiences.*' => ['array:id,type,source_id,tenant_id,importance,stage,description,order'],
            'audiences.*.id' => ['nullable', 'string'],
            'audiences.*.type' => ['required', Rule::enum(LearningAudienceType::class)],
            'audiences.*.source_id' => ['nullable', 'string', 'max:26', 'required_unless:audiences.*.type,everyone'],
            'audiences.*.tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'audiences.*.importance' => ['required', Rule::enum(LearningImportance::class)],
            'audiences.*.stage' => ['nullable', Rule::enum(LearningStage::class)],
            'audiences.*.description' => ['nullable', 'array:lt,en'],
            'audiences.*.description.*' => ['nullable', 'string', 'max:20000'],
            'audiences.*.order' => ['sometimes', 'integer', 'min:0'],
            'title' => ['required', 'array:lt,en'],
            'title.lt' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array:lt,en'],
            'description.*' => ['nullable', 'string', 'max:20000'],
            'action_label' => ['exclude_unless:kind,action', 'nullable', 'array:lt,en'],
            'action_label.*' => ['nullable', 'string', 'max:60'],
            'action_url' => ['exclude_unless:kind,action', 'nullable', 'array:lt,en'],
            // A page inside the platform ("/mano/…") or a full web address.
            'action_url.*' => ['nullable', 'string', 'max:2048', 'regex:#^(https?://[^\s]+|/(?!/)[^\s]*)$#i'],
            'per_term' => ['sometimes', 'boolean'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'reviewed_at' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->route('learningStep')?->is_system) {
                return;
            }
            $data = $validator->validated();
            abort_unless(app(LearningStepPolicy::class)->manageAudiences($this->user(), $data['audiences'], $this->route('learningStep') ? 'update' : 'create'), 403);
            $meetingPrep = $data['kind'] === LearningStepKind::MeetingPrep->value;
            foreach ($data['audiences'] as $index => $audience) {
                $type = LearningAudienceType::from($audience['type']);
                $hasStage = filled($audience['stage'] ?? null);
                $wrongPrepAudience = $meetingPrep && ! in_array($type, [LearningAudienceType::InstitutionType, LearningAudienceType::Institution], true);
                if ($wrongPrepAudience || $hasStage !== ($data['kind'] === LearningStepKind::Action->value)) {
                    $validator->errors()->add("audiences.{$index}.stage", __('learning.validation.stage'));
                }
            }
            $this->validateAudienceSources($validator, $data);
        }];
    }
}
