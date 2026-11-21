<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ValidatesTenantScope;
use App\Models\News;

class StoreNewsRequest extends NewsRequest
{
    use ValidatesTenantScope;

    /**
     * Get the validation rules that apply to the request.
     *
     * `#[Authorize('create', News::class)]` is tenant-agnostic, so the `tenant_id` rule is what
     * confines the article to a padalinys the user may create in.
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'short' => 'required|string',
            'tenant_id' => ['required', 'integer', 'exists:tenants,id', $this->tenantIdInAuthorizedScope('news.create.padalinys')],
        ]);
    }
}
