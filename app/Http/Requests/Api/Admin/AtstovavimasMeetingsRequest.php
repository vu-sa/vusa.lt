<?php

namespace App\Http\Requests\Api\Admin;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class AtstovavimasMeetingsRequest extends FormRequest
{
    /**
     * Maximum window span, so a single request cannot pull years of meetings.
     */
    private const int MAX_SPAN_MONTHS = 24;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tenant_ids' => ['required', 'array', 'min:1', 'max:100'],
            'tenant_ids.*' => ['required', 'integer', 'distinct', 'exists:tenants,id'],
            'from' => ['required', 'date'],
            'until' => ['required', 'date', 'after_or_equal:from'],
            'refresh' => ['nullable', 'boolean'],
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
