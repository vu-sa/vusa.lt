<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class IndexUserTasksRequest extends BaseIndexRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The listing is scoped to the acting user's own tasks, so there is nothing to authorize
     * beyond `auth`; this exists to cap `per_page`, which the method previously read straight
     * from raw input.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'status' => ['nullable', 'string', Rule::in(['incomplete', 'completed', 'all'])],
        ]);
    }
}
