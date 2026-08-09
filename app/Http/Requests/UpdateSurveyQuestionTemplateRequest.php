<?php

namespace App\Http\Requests;

class UpdateSurveyQuestionTemplateRequest extends StoreSurveyQuestionTemplateRequest
{
    #[\Override]
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('survey_question_template'));
    }
}
