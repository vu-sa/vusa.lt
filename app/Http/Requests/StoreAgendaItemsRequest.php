<?php

namespace App\Http\Requests;

use App\Models\Pivots\AgendaItem;

class StoreAgendaItemsRequest extends ResourceRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('create', [AgendaItem::class, $this->authorizer]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'moreAgendaItemsUndefined' => 'nullable|boolean',
            'agendaItemTitles' => 'nullable|array',
            'meeting_id' => 'required|ulid|exists:meetings,id',
        ];
    }
}
