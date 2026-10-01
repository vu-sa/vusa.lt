<?php

namespace App\Http\Requests;

use App\Actions\GetTenantsForUpserts;
use App\Enums\ContentPartEnum;
use App\Models\News;
use App\Models\Page;
use App\Services\ModelAuthorizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates a batch admin-preview request for unsaved rich-content blocks. The
 * endpoint only returns already-public data (the same resolvers public rendering
 * uses), so the authorization bar is "an admin who can edit pages at all" rather than
 * per-page authorization — but `tenant_id` still has to be one the user can actually
 * act for, so previewing can't be used to probe another tenant's content-part ids.
 */
class PreviewContentPartsRequest extends FormRequest
{
    /**
     * A missing/non-numeric `tenant_id` is left to `rules()` to reject with a 422 —
     * this only rejects a *well-formed* tenant_id the user can't act for, which is
     * the actual authorization boundary (probing another tenant's content).
     */
    public function authorize(ModelAuthorizer $authorizer): bool
    {
        if (! $this->user()) {
            return false;
        }

        $tenantId = $this->input('tenant_id');
        if (! is_numeric($tenantId)) {
            return true;
        }

        $allowedTenantIds = $this->previewTenantIds($authorizer);

        return in_array((int) $tenantId, $allowedTenantIds, true);
    }

    /**
     * @return array<string, mixed>
     */
    private function previewTenantIds(ModelAuthorizer $authorizer): array
    {
        $kind = $this->input('kind', 'pages');
        abort_unless(in_array($kind, ['pages', 'news'], true), 403);
        if ($this->filled('record_id')) {
            $model = $kind === 'pages' ? Page::class : News::class;
            $record = $model::findOrFail($this->input('record_id'));
            abort_unless($this->user()->can('update', $record), 403);

            return [$record->tenant_id];
        }
        $action = $this->has('kind') ? 'create' : 'update';

        return GetTenantsForUpserts::execute($kind.'.'.$action.'.padalinys', $authorizer)->pluck('id')->all();
    }

    public function rules(ModelAuthorizer $authorizer): array
    {
        $allowedTenantIds = $this->previewTenantIds($authorizer);

        return [
            'kind' => ['nullable', 'in:pages,news'],
            'record_id' => ['nullable', 'integer'],
            'tenant_id' => ['required', 'integer', Rule::in($allowedTenantIds)],
            'locale' => ['nullable', 'string', Rule::in(['lt', 'en'])],
            'parts' => ['required', 'array', 'max:20'],
            'parts.*.key' => ['required', 'string'],
            'parts.*.type' => ['required', 'string', Rule::in(ContentPartEnum::toArray())],
            'parts.*.json_content' => ['present'],
            'parts.*.options' => ['nullable', 'array'],
        ];
    }
}
