<?php

namespace App\Http\Requests;

use App\Models\ProblemCategory;

class UpdateProblemCategoryRequest extends ProblemCategoryRequest
{
    public function authorize(): bool
    {
        $problemCategory = $this->route('problemCategory');

        return $problemCategory instanceof ProblemCategory
            && $this->user()->can('update', $problemCategory);
    }
}
