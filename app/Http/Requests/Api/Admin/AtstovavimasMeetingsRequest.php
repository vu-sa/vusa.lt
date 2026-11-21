<?php

namespace App\Http\Requests\Api\Admin;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;

class AtstovavimasMeetingsRequest extends AtstovavimasGanttRequest
{
    /**
     * Maximum window span, so a single request cannot pull years of meetings.
     */
    private const int MAX_SPAN_MONTHS = 24;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->input('from');
            $until = $this->input('until');

            if (! is_string($from) || ! is_string($until)) {
                return;
            }

            if (Carbon::parse($from)->diffInMonths(Carbon::parse($until)) > self::MAX_SPAN_MONTHS) {
                $validator->errors()->add('until', __('The date range may not span more than :months months.', ['months' => self::MAX_SPAN_MONTHS]));
            }
        });
    }
}
