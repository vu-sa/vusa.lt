<?php

namespace App\Http\Requests;

use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Document;
use App\Models\LearningSection;
use App\Models\LearningStep;
use App\Policies\LearningSectionPolicy;
use App\Policies\LearningStepPolicy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LearningStepRequest extends FormRequest
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

    public function authorize(): bool
    {
        $step = $this->route('learningStep');

        return $this->user()->can($step instanceof LearningStep ? 'update' : 'create', $step instanceof LearningStep ? $step : LearningStep::class);
    }

    public function rules(): array
    {
        if ($this->route('learningStep')?->is_system) {
            return [
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
            'learning_section_id' => ['exclude_unless:kind,knowledge', 'nullable', 'exists:learning_sections,id'],
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
            abort_unless(app(LearningStepPolicy::class)->manageAudiences($this->user(), $data['audiences'], $this->route('learningStep') ? 'update' : 'create'), 403);
            if (filled($data['learning_section_id'] ?? null)) {
                $section = LearningSection::findOrFail($data['learning_section_id']);
                abort_unless(app(LearningSectionPolicy::class)->target($this->user(), $section->tenant_id, $this->route('learningStep') ? 'update' : 'create'), 403);
            }
            $meetingPrep = $data['kind'] === LearningStepKind::MeetingPrep->value;
            $tables = ['duty_type' => 'duty_types', 'institution_type' => 'institution_types', 'responsibility' => 'roles', 'duty' => 'duties', 'institution' => 'institutions'];
            foreach ($data['audiences'] as $index => $audience) {
                $type = LearningAudienceType::from($audience['type']);
                // Only actions sit on the term timeline; documents and meeting prep have no stage.
                $hasStage = filled($audience['stage'] ?? null);
                $wrongPrepAudience = $meetingPrep && ! in_array($type, [LearningAudienceType::InstitutionType, LearningAudienceType::Institution], true);
                if ($wrongPrepAudience || $hasStage !== ($data['kind'] === LearningStepKind::Action->value)) {
                    $validator->errors()->add("audiences.{$index}.stage", __('learning.validation.stage'));
                }
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
