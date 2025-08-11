<?php

namespace App\Http\Requests;

use App\Models\Form;
use App\Models\FormField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRegistrationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'data.*' => 'required|array', // Each field response must be an array
            'data.*.value' => 'present', // Each response must have a 'value' key (can be null)
            'user_id' => 'nullable|exists:users,id',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $form = $this->route('form');
            if (! $form instanceof Form) {
                return;
            }

            /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\FormField> $formFields */
            $formFields = $form->formFields()->get();
            $data = $this->input('data', []);

            foreach ($formFields as $field) {
                $fieldId = (string) $field->id;
                $fieldData = $data[$fieldId] ?? null;
                $value = $fieldData['value'] ?? null;

                // Check required fields
                if ($field->is_required && empty($value)) {
                    $validator->errors()->add("data.{$fieldId}.value", "The {$field->label} field is required.");
                }

                // Validate based on field type
                if (! empty($value)) {
                    $this->validateFieldByType($validator, $field, $value, $fieldId);
                }
            }
        });
    }

    /**
     * Validate field based on its type
     */
    private function validateFieldByType(Validator $validator, FormField $field, $value, string $fieldId): void
    {
        // Handle email validation - check both type and subtype
        if ($field->type === 'email' || ($field->type === 'string' && $field->subtype === 'email')) {
            if (! filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $validator->errors()->add("data.{$fieldId}.value", "The {$field->label} must be a valid email address.");
            }

            return;
        }

        switch ($field->type) {
            case 'number':
                if (! is_numeric($value)) {
                    $validator->errors()->add("data.{$fieldId}.value", "The {$field->label} must be a number.");
                }
                break;

            case 'enum':
            case 'select':
                if ($field->options) {
                    // Extract values from option objects for validation
                    $validValues = collect($field->options)->pluck('value')->filter()->toArray();

                    // Fallback to direct array values if no 'value' properties exist
                    if (empty($validValues)) {
                        $validValues = $field->options;
                    }

                    if (! in_array($value, $validValues)) {
                        $validator->errors()->add("data.{$fieldId}.value", "The selected {$field->label} is invalid.");
                    }
                }
                break;

            case 'date':
                if (! strtotime($value)) {
                    $validator->errors()->add("data.{$fieldId}.value", "The {$field->label} must be a valid date.");
                }
                break;
        }
    }
}
