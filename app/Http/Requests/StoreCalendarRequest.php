<?php

namespace App\Http\Requests;

use App\Models\Calendar;
use App\Rules\SoftDeleteRules;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class StoreCalendarRequest extends CalendarRequest
{
    #[\Override]
    protected string $tenantScopePermission = 'calendars.create.padalinys';

    #[\Override]
    protected function prepareForValidation(): void
    {
        if (Validator::make($this->only('date'), ['date' => 'required|date'])->fails()) {
            return;
        }

        $year = Carbon::parse($this->input('date'))->format('Y');

        foreach (['lt', 'en'] as $locale) {
            $permalink = $this->input("permalink.{$locale}");

            if (blank($permalink)) {
                $title = $this->input("title.{$locale}");

                if (! is_string($title) || blank($title)) {
                    continue;
                }

                $permalink = Str::substr(Str::slug($title), 0, 255);
                $permalink = $permalink !== '' ? $permalink : 'event';
            }

            if (! is_string($permalink)) {
                continue;
            }

            $permalink = Str::substr($permalink, 0, 255);
            $candidate = $permalink;
            $suffix = 2;

            while (! $this->permalinkAvailable($locale, $candidate, $year)) {
                $ending = '-'.$suffix++;
                $candidate = Str::substr($permalink, 0, 255 - strlen($ending)).$ending;
            }

            $this->merge(['permalink' => array_replace((array) $this->input('permalink', []), [$locale => $candidate])]);
        }
    }

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
            'event_type_id' => ['nullable', 'integer', SoftDeleteRules::existsLive('event_types')],
        ]);
    }
}
