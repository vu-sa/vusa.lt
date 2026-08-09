<?php

namespace App\Http\Requests;

class IndexSurveyRequest extends BaseIndexRequest
{
    /** @var array<int, array<string, mixed>> */
    #[\Override]
    protected array $defaultSorting = [['id' => 'created_at', 'desc' => true]];
}
