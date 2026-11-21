<?php

namespace App\Http\Requests\Api\Admin;

use App\Services\AtstovavimasDashboardService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class AtstovavimasMeetingAgendaRequest extends AtstovavimasTenantRequest
{
    /** @var array{id: string, projection: string} */
    private array $institutionAccess;

    public function authorize(): bool
    {
        $actor = $this->user();
        if ($actor === null) {
            return false;
        }

        $requestedId = $this->input('institution_id');
        $tenantIds = $this->input('tenant_ids', []);
        if (! Str::isUlid($requestedId) || ! is_array($tenantIds) || ! array_is_list($tenantIds) || count($tenantIds) > 100
            || ($tenantIds === [] && $this->has('tenant_ids'))
            || array_any($tenantIds, fn (mixed $id): bool => filter_var($id, FILTER_VALIDATE_INT) === false)) {
            return true;
        }

        $dashboard = app(AtstovavimasDashboardService::class);
        $access = $tenantIds === []
            ? $dashboard->representativeInstitutionAccess($actor)
            : $dashboard->cachedGanttInstitutionAccess($actor, array_values(array_map(intval(...), $tenantIds)), $this->boolean('refresh'));
        $institutionId = $access->keys()->first(fn (string $id): bool => strcasecmp($id, $requestedId) === 0);
        if ($institutionId === null) {
            return false;
        }

        $this->institutionAccess = ['id' => $institutionId, 'projection' => $access[$institutionId]];

        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'tenant_ids' => ['sometimes', 'required', 'array', 'list', 'min:1', 'max:100'],
            'institution_id' => ['required', 'string', 'ulid'],
        ];
    }

    /** @return array{id: string, projection: string} */
    public function institutionAccess(): array
    {
        return $this->institutionAccess;
    }
}
