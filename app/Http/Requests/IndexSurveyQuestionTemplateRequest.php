<?php

namespace App\Http\Requests;

class IndexSurveyQuestionTemplateRequest extends BaseIndexRequest
{
    /** @var array<int, array<string, mixed>> */
    #[\Override]
    protected array $defaultSorting = [['id' => 'order', 'desc' => false]];
}
