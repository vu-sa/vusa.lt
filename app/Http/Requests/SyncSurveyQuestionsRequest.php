<?php

namespace App\Http\Requests;

use App\Enums\SurveyQuestionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Replaces the whole question list of a survey in one submission.
 *
 * The builder UI sends the full ordered list every time, which keeps reordering, editing
 * and deleting a single code path.
 */
class SyncSurveyQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('survey'));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'questions' => ['present', 'array', 'max:200'],

            // Provenance only, and never trusted for content — the payload carries the
            // text, so a template id pointing anywhere cannot inject another tenant's
            // question. Kept validated anyway so the column stays referentially sound.
            'questions.*.survey_question_template_id' => ['nullable', 'string', 'exists:survey_question_templates,id'],

            'questions.*.title' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z][A-Za-z0-9_]*$/'],
            'questions.*.type' => ['required', Rule::enum(SurveyQuestionType::class)],

            'questions.*.group_name' => ['required', 'array'],
            'questions.*.group_name.lt' => ['required', 'string', 'max:255'],
            'questions.*.group_name.en' => ['nullable', 'string', 'max:255'],

            'questions.*.question' => ['required', 'array'],
            'questions.*.question.lt' => ['required', 'string'],
            'questions.*.question.en' => ['nullable', 'string'],

            'questions.*.help' => ['nullable', 'array'],
            'questions.*.help.lt' => ['nullable', 'string'],
            'questions.*.help.en' => ['nullable', 'string'],

            'questions.*.is_required' => ['boolean'],

            'questions.*.options' => ['nullable', 'array', 'max:50'],
            'questions.*.options.*.code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9_]+$/'],
            'questions.*.options.*.label' => ['required', 'array'],
            'questions.*.options.*.label.lt' => ['required', 'string', 'max:255'],
            'questions.*.options.*.label.en' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $titles = array_map(
                fn (array $question): string => strtolower((string) ($question['title'] ?? '')),
                $this->input('questions', []),
            );

            // LimeSurvey uses the question code as a column name in the response table, so
            // duplicates would collide there rather than fail visibly on import.
            if (count($titles) !== count(array_unique($titles))) {
                $validator->errors()->add('questions', __('surveys.validation.duplicate_titles'));
            }

            foreach ($this->input('questions', []) as $index => $question) {
                $type = SurveyQuestionType::tryFrom($question['type'] ?? '');

                if ($type?->hasOptions() && count($question['options'] ?? []) < 1) {
                    $validator->errors()->add("questions.{$index}.options", __('surveys.validation.options_required'));
                }
            }
        });
    }
}
