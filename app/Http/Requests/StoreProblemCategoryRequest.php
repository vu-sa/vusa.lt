<?php

namespace App\Http\Requests;

use App\Models\ProblemCategory;

class StoreProblemCategoryRequest extends ProblemCategoryRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ProblemCategory::class);
    }
}
