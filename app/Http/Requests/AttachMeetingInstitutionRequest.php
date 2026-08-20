<?php

namespace App\Http\Requests;

use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttachMeetingInstitutionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->meeting());
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'institution_id' => [
                'required',
                'ulid',
                Rule::exists('institutions', 'id'),
                // Attaching one twice would create a duplicate pivot row.
                Rule::notIn($this->meeting()->institutions()->pluck('institutions.id')->all()),
            ],
        ];
    }

    protected function meeting(): Meeting
    {
        /** @var Meeting $meeting */
        $meeting = $this->route('meeting');

        return $meeting;
    }
}
