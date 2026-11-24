<?php

namespace App\Http\Requests;

use App\Enums\FormOptionSource;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class UpdateFormRequest extends FormRequest
{
    use ValidatesTenantScope;

    #[\Override]
    protected function prepareForValidation()
    {
        $publishTime = $this->input('publish_time');

        // Only process publish_time if it's not null
        if ($publishTime !== null) {
            $this->merge([
                'publish_time' => is_string($publishTime)
                    ? Carbon::createFromTimestamp(strtotime($publishTime), 'Europe/Vilnius')
                    : Carbon::createFromTimestampMs($publishTime, 'Europe/Vilnius'),
            ]);
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
            'name' => 'required|array',
            'name.lt' => 'nullable|string',
            'name.en' => 'nullable|string',
            'description' => 'array',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'path' => 'required|array',
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope('forms.update.padalinys')],
            'form_fields' => 'array',
            'form_fields.*.type' => 'required|string',
            'form_fields.*.label' => 'required|array',
            'form_fields.*.is_required' => 'boolean',
            'form_fields.*.order' => 'integer',
            'form_fields.*.options' => 'nullable|array',
            // Everything below is persisted through FormController::FORM_FIELD_ATTRIBUTES.
            // Keep the ID in validated input so the controller can check field ownership.
            'form_fields.*.id' => ['nullable', function (string $attribute, mixed $value, Closure $fail): void {
                if (is_string($value) && str_starts_with($value, 'new-')) {
                    return;
                }

                if ((! is_int($value) && ! is_string($value)) || filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                    $fail('validation.integer')->translate();
                }
            }],
            'form_fields.*.description' => 'nullable|array',
            'form_fields.*.subtype' => 'nullable|string',
            'form_fields.*.default_value' => 'nullable',
            'form_fields.*.placeholder' => 'nullable|array',
            'form_fields.*.use_model_options' => 'nullable|boolean',
            'form_fields.*.option_source' => ['nullable', Rule::enum(FormOptionSource::class)],
            'publish_time' => 'nullable|date',
        ];
    }
}
