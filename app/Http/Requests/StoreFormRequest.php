<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\Form;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class StoreFormRequest extends FormRequest
{
    use ValidatesTenantScope;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Form::class);
    }

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
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope('forms.create.padalinys')],
            'form_fields' => 'array',
            'form_fields.*.type' => 'required|string',
            'form_fields.*.label' => 'required|array',
            'form_fields.*.is_required' => 'boolean',
            'form_fields.*.order' => 'integer',
            'form_fields.*.options' => 'nullable|array',
            // Everything below is persisted through FormController::FORM_FIELD_ATTRIBUTES.
            // Without a rule, safe() strips the key — which for `id` silently disabled the
            // "field belongs to this form" check in syncFormFields().
            'form_fields.*.id' => 'nullable', // int for persisted rows, 'new-*' string for unsaved ones
            'form_fields.*.description' => 'nullable|array',
            'form_fields.*.subtype' => 'nullable|string',
            'form_fields.*.default_value' => 'nullable',
            'form_fields.*.placeholder' => 'nullable|array',
            'form_fields.*.use_model_options' => 'nullable|boolean',
            'form_fields.*.options_model' => 'nullable|string',
            'form_fields.*.options_model_field' => 'nullable|string',
            'publish_time' => 'nullable|date',
        ];
    }
}
