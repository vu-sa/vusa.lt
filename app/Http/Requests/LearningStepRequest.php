<?php

namespace App\Http\Requests;

use App\Enums\LearningImportance;
use App\Enums\LearningLayer;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Models\Document;
use App\Models\LearningStep;
use App\Policies\LearningStepPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LearningStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        $step = $this->route('learningStep');

        return $this->user()->can($step instanceof LearningStep ? 'update' : 'create', $step instanceof LearningStep ? $step : LearningStep::class);
    }

    public function rules(): array
    {
        if ($this->route('learningStep')?->is_system) {
            return ['stage' => ['sometimes', 'required', Rule::enum(LearningStage::class)], 'order' => ['sometimes', 'integer', 'min:0'], 'is_active' => ['sometimes', 'boolean']];
        }

        return [
            'kind' => ['required', Rule::enum(LearningStepKind::class)],
            'layer' => ['required', Rule::enum(LearningLayer::class)],
            'institution_type_id' => ['nullable', 'integer', 'exists:institution_types,id,deleted_at,NULL', 'required_if:layer,institution_type'],
            'duty_type_id' => ['nullable', 'integer', 'exists:duty_types,id,deleted_at,NULL', 'required_if:layer,duty_type'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id', 'required_if:layer,tenant'],
            'stage' => ['nullable', Rule::enum(LearningStage::class), 'required_unless:kind,meeting_prep'],
            'importance' => ['required', Rule::enum(LearningImportance::class)],
            'title' => ['required', 'array:lt,en'],
            'title.lt' => ['required', 'string', 'max:255'],
            'title.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array:lt,en'],
            'description.*' => ['nullable', 'string', 'max:20000'],
            'per_term' => ['sometimes', 'boolean'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'reviewed_at' => ['nullable', 'date'],
            'sources' => ['sometimes', 'array:lt,en'],
            'sources.*' => ['array:document_id,url'],
            'sources.*.document_id' => ['nullable', 'integer', 'exists:documents,id'],
            'sources.*.url' => ['nullable', 'url:http,https', 'max:2048'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || $this->route('learningStep')?->is_system) {
                return;
            }
            $data = $validator->validated();
            $layer = LearningLayer::from($data['layer']);
            abort_unless(app(LearningStepPolicy::class)->manageLayer($this->user(), $layer, $data['tenant_id'] ?? null, $this->route('learningStep') ? 'update' : 'create'), 403);
            $meetingPrep = $data['kind'] === LearningStepKind::MeetingPrep->value;
            if (($meetingPrep && ($layer !== LearningLayer::InstitutionType || ($data['stage'] ?? null) !== null))
                || ($layer === LearningLayer::Tenant && ($data['stage'] ?? null) !== LearningStage::BeforeStart->value)) {
                $validator->errors()->add('stage', __('learning.validation.stage'));
            }
            $this->validateSources($validator, $data['sources'] ?? []);
        }];
    }

    /** @param array<string, array{document_id?: int|null, url?: string|null}> $sources */
    private function validateSources(Validator $validator, array $sources): void
    {
        foreach ($sources as $locale => $source) {
            if (filled($source['document_id'] ?? null) === filled($source['url'] ?? null)) {
                $validator->errors()->add("sources.{$locale}", __('learning.validation.source'));
            }
            if (filled($source['document_id'] ?? null)) {
                $document = Document::find($source['document_id']);
                if ($document !== null && ! $this->user()->can('view', $document)) {
                    abort(403);
                }
            }
        }
    }
}
