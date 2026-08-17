<?php

namespace App\Http\Requests;

use App\Models\Calendar;
use App\Rules\SoftDeleteRules;

class StoreCalendarRequest extends CalendarRequest
{
    protected string $tenantScopePermission = 'calendars.create.padalinys';

    /**
     * Determine if the user is authorized to make this request.
     *
     * `can('create', Calendar::class)` is tenant-agnostic, so the `tenant_id` rule inherited
     * from CalendarRequest is what actually confines the event to a padalinys the user may
     * create in.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Calendar::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category_id' => ['nullable', SoftDeleteRules::existsLive('categories')],
        ]);
    }
}
