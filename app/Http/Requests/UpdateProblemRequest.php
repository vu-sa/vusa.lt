<?php

namespace App\Http\Requests;

class UpdateProblemRequest extends ProblemRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('problem'));
    }
}
