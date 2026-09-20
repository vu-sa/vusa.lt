<?php

namespace App\Http\Requests;

use App\Models\Duty;
use App\Models\User;
use App\Policies\DutyPolicy;
use App\Rules\SoftDeleteRules;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDutiableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $duty = Duty::query()->find($this->input('duty_id'));
        $targetUser = $this->filled('user_id')
            ? User::query()->find($this->input('user_id'))
            : null;

        return $duty ? app(DutyPolicy::class)->managePeople($this->user(), $duty, $targetUser) : false;
    }

    #[\Override]
    protected function prepareForValidation(): void
    {
        $data = [];

        if ($this->filled('start_date')) {
            $data['start_date'] = Carbon::parse($this->input('start_date'))->format('Y-m-d');
        } else {
            $data['start_date'] = now()->format('Y-m-d');
        }

        if ($this->filled('end_date')) {
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
            'duty_id' => ['required', 'ulid', SoftDeleteRules::existsLive('duties')],
            'user_id' => ['required', 'ulid', SoftDeleteRules::existsLive('users')],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'study_program_id' => ['nullable', 'ulid', SoftDeleteRules::existsLive('study_programs')],
            'study_program_note' => ['nullable', 'array'],
            'study_program_note.lt' => ['nullable', 'string', 'max:100'],
            'study_program_note.en' => ['nullable', 'string', 'max:100'],
            'additional_email' => ['nullable', 'email'],
            'additional_photo' => ['nullable', 'string'],
            'additional_photo_focal_point' => ['nullable', 'string', 'max:20'],
            'description' => ['nullable', 'array'],
            'use_original_duty_name' => ['nullable', 'boolean'],
        ];
    }
}
