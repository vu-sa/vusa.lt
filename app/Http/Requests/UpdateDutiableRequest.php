<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasImageValidation;
use App\Http\Requests\Concerns\ValidatesQuotaTenant;
use App\Models\Pivots\Dutiable;
use App\Rules\SoftDeleteRules;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDutiableRequest extends FormRequest
{
    use HasImageValidation;
    use ValidatesQuotaTenant;

    #[\Override]
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->input('start_date') !== null) {
            $data['start_date'] = Carbon::parse($this->input('start_date'))->format('Y-m-d');
        }

        if ($this->input('end_date') !== null) {
            $data['end_date'] = Carbon::parse($this->input('end_date'))->format('Y-m-d');
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $term = $this->route('dutiable');
        $term = $term instanceof Dutiable ? $term : null;
        $storedStart = $term?->start_date?->toDateString();
        $storedEnd = $term?->end_date?->toDateString();
        $start = $this->filled('start_date') ? 'start_date' : $storedStart;
        $datesChanged = ($this->input('start_date') ?? $storedStart) !== $storedStart
            || ($this->has('end_date') && $this->input('end_date') !== $storedEnd);

        return [
            'start_date' => ['nullable', 'date', Rule::when($datesChanged && $this->missing('end_date') && $storedEnd !== null, 'before_or_equal:'.$storedEnd)],
            'end_date' => ['nullable', 'date', Rule::when($datesChanged && $start !== null, 'after_or_equal:'.$start)],
            'description' => 'nullable|array',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'study_program_id' => ['nullable', 'ulid', SoftDeleteRules::existsLive('study_programs')],
            'study_program_note' => 'nullable|array',
            'study_program_note.lt' => 'nullable|string|max:100',
            'study_program_note.en' => 'nullable|string|max:100',
            'additional_email' => 'nullable|email',
            ...$this->imageMediaRules('photo_media', $term ?? new Dutiable, 'photo'),
            'use_original_duty_name' => 'nullable|boolean',
            'tenant_id' => $this->quotaTenantRules($this->route('dutiable')?->duty),
        ];
    }
}
