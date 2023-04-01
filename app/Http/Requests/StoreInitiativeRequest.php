<?php

namespace App\Http\Requests;

use App\Models\Initiative;

class StoreInitiativeRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', [Initiative::class, $this->authorizer]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'title.lt' => 'required|string',
            'title.en' => 'nullable|string',
            'description' => 'required|array|min:1',
            'padalinys_id' => 'nullable|id|exists:padaliniai,id',
            'participation_url' => 'nullable|url',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'logo' => 'nullable|string',
            'cover' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ];
    }
}
