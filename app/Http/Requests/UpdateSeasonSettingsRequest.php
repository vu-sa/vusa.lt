<?php

namespace App\Http\Requests;

use App\Settings\SettingsSettings;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSeasonSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(SettingsSettings::class)->canUserManageSettings($this->user());
    }

    public function rules(): array
    {
        $monthDay = [
            'bail',
            'required',
            'string',
            'regex:/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/',
            function (string $attribute, mixed $value, Closure $fail): void {
                [$month, $day] = array_map(intval(...), explode('-', $value));

                if (! checkdate($month, $day, 2000)) {
                    $fail('validation.date')->translate(['attribute' => $attribute]);
                }
            },
        ];

        return [
            'enabled' => ['required', 'boolean'],
            'starts_on' => $monthDay,
            'tree_locks_on' => $monthDay,
            'ends_on' => $monthDay,
            'ornament_adjust_minutes' => ['required', 'integer', 'min:0'],
            'ornament_cooldown_hours' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * The lock comes after the start and the end no earlier than the lock, counting on from the start
     * across New Year — so 12-01 → 12-25 → 01-06 passes, while a lock before the start no longer
     * slips silently into the next year.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->hasAny(['starts_on', 'tree_locks_on', 'ends_on'])) {
                return;
            }

            $start = $this->dayOfYear($this->input('starts_on'));
            $since = fn (string $monthDay): int => ($this->dayOfYear($monthDay) - $start + 366) % 366;
            $lock = $since($this->input('tree_locks_on'));

            if ($lock === 0) {
                $validator->errors()->add('tree_locks_on', __('season-tree.dates_out_of_order'));
            } elseif ($since($this->input('ends_on')) < $lock) {
                // Either could be the wrong one, so both fields say so.
                $validator->errors()->add('tree_locks_on', __('season-tree.dates_out_of_order'));
                $validator->errors()->add('ends_on', __('season-tree.dates_out_of_order'));
            }
        });
    }

    private function dayOfYear(string $monthDay): int
    {
        [$month, $day] = array_map(intval(...), explode('-', $monthDay));

        // A leap year, so 02-29 has a place.
        return (int) date('z', (int) mktime(0, 0, 0, $month, $day, 2000));
    }
}
