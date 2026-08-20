<?php

namespace App\Http\Requests;

use App\Models\News;
use App\Models\Tenant;
use App\Rules\UniqueAmongTrashed;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Carbon;

class StoreNewsRequest extends NewsRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', News::class);
    }

    #[\Override]
    protected function prepareForValidation()
    {
        $publishTime = $this->input('publish_time');

        if ($publishTime !== null) {
            $this->merge([
                'publish_time' => is_string($publishTime)
                    ? Carbon::createFromTimestamp(strtotime($publishTime), 'Europe/Vilnius')
                    : Carbon::createFromTimestampMs($publishTime, 'Europe/Vilnius'),
            ]);
        }
    }

    /**
     * Get the tenant the new news item will be created for.
     *
     * Mirrors the tenant resolution in NewsController::store so the permalink
     * uniqueness check is scoped to the same tenant the record will belong to.
     */
    protected function getTargetTenantId(): ?int
    {
        if ($this->user()->isSuperAdmin()) {
            return Tenant::main()?->id;
        }

        $authorizer = app(ModelAuthorizer::class)->forUser($this->user());
        $authorizer->check('news.create.padalinys');

        return $authorizer->getPermissableDuties()->first()?->getAttribute('tenants')->first()?->id;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    #[\Override]
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            // The global single-column unique index on news.permalink has been removed;
            // uniqueness is now enforced per tenant by the composite index and this rule.
            'permalink' => ['required', UniqueAmongTrashed::of('news', 'permalink')->where('tenant_id', $this->getTargetTenantId())],
            'image' => 'required',
            'short' => 'required',
        ]);
    }
}
