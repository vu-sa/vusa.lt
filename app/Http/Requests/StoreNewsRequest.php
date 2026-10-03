<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\News;

class StoreNewsRequest extends NewsRequest
{
    use ValidatesTenantScope;

    /**
     * Determine if the user is authorized to make this request.
     *
     * `can('create', News::class)` is tenant-agnostic, so the `tenant_id` rule below is what
     * actually confines the article to a padalinys the user may create in.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', News::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'image' => 'nullable|string',
            'short' => 'required|string',
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope('news.create.padalinys')],
        ]);
    }
}
