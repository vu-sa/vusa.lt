<?php

namespace App\Http\Requests;

use App\Models\ChangelogItem;
use Carbon\Carbon;

class StoreChangelogItemRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {     
        return $this->user()->can('create', [ChangelogItem::class, $this->authorizer]);
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'date' => Carbon::parse($this->date)->format('Y-m-d H:i'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'date' => 'required|date:Y-m-d H:i',
            'title' => 'required',
            'description' => 'required',
        ];
    }
}
