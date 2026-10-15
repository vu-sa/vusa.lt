<?php

namespace App\Http\Requests\ActivityRequests;

class StoreInstitutionActivityRequestsRequest extends InstitutionActivityRequestsRequest
{
    /**
     * @return array<string, mixed>
     */
    #[\Override]
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }
}
