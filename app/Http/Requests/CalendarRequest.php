<?php

namespace App\Http\Requests;

use App\Enums\CalendarHeroStyleEnum;
use App\Http\Requests\Concerns\HasImageValidation;
use App\Http\Requests\Concerns\ValidatesTenantScope;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CalendarRequest extends FormRequest
{
    use HasImageValidation;
    use ValidatesTenantScope;

    /**
     * The permission whose tenant scope constrains `tenant_id`. Store and Update override it
     * so each uses its own scope.
     */
    protected string $tenantScopePermission = 'calendars.update.padalinys';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'title.lt' => 'required|string',
            'title.en' => 'nullable|string',
            'description.lt' => 'nullable|string',
            'description.en' => 'nullable|string',
            'location.lt' => 'nullable|string',
            'location.en' => 'nullable|string',
            'is_remote' => 'boolean',
            'organizer.lt' => 'nullable|string',
            'organizer.en' => 'nullable|string',
            'cto_url.lt' => 'nullable|url',
            'cto_url.en' => 'nullable|url',
            'facebook_url' => 'nullable|url',
            'video_url' => 'nullable',
            'is_draft' => 'boolean',
            'is_all_day' => 'boolean',
            'is_international' => 'boolean',
            'hero_style' => ['nullable', Rule::enum(CalendarHeroStyleEnum::class)],
            'date' => 'required|date',
            'end_date' => 'nullable|date|after:date',
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope($this->tenantScopePermission)],
        ];

        // Skip file validation during precognitive requests
        if (! $this->isPrecognitive()) {
            $rules['main_image'] = $this->singleImageRules(maxMB: 10);
            $rules['images'] = $this->imagesArrayRules(maxFiles: 20);
            $rules['images.*'] = $this->galleryImageRules(maxMB: 5);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    #[\Override]
    public function messages(): array
    {
        return $this->imageValidationMessages();
    }
}
