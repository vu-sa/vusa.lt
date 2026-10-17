<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasImageValidation;
use App\Models\Pivots\Dutiable;
use App\Rules\SoftDeleteRules;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDutiableRequest extends FormRequest
{
    use HasImageValidation;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('manageDutiable', $this->route('dutiable'));
    }

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
        return [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'description' => 'nullable|array',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'study_program_id' => ['nullable', 'ulid', SoftDeleteRules::existsLive('study_programs')],
            'study_program_note' => 'nullable|array',
            'study_program_note.lt' => 'nullable|string|max:100',
            'study_program_note.en' => 'nullable|string|max:100',
            'additional_email' => 'nullable|email',
            ...$this->imageMediaRules('photo_media', $this->route('dutiable') instanceof Dutiable ? $this->route('dutiable') : new Dutiable, 'photo'),
            'use_original_duty_name' => 'nullable|boolean',
        ];
    }
}
