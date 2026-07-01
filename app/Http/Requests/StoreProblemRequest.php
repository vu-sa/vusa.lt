<?php

namespace App\Http\Requests;

use App\Models\Problem;

class StoreProblemRequest extends ProblemRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Problem::class);
    }
}
