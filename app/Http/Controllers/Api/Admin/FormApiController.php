<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildFormIndexQuery;
use App\Actions\SerializeFormsForTable;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexFormRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Form;
use App\Services\FormAccessService;
use App\Services\FormRegistrationVisibilityService;
use App\Services\TanstackTableService;
use App\Support\CollectionFacetCounts;
use Illuminate\Http\JsonResponse;

class FormApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(
        private TanstackTableService $tableService,
        private FormAccessService $formAccess,
        private FormRegistrationVisibilityService $registrationVisibility,
    ) {}

    public function index(IndexFormRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', Form::class);

        $user = $request->user();

        $query = fn (IndexFormRequest $request) => $this->applyTanstackFilters(
            BuildFormIndexQuery::execute($request, $user, $this->formAccess, $this->tableService),
            $request,
            $this->tableService,
            ['name', 'path'],
        );
        $forms = $query($request)->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => SerializeFormsForTable::execute($forms->getCollection(), $user, $this->registrationVisibility)->values(),
            'total' => $forms->total(),
            'per_page' => $forms->perPage(),
            'current_page' => $forms->currentPage(),
            'last_page' => $forms->lastPage(),
            'facets' => CollectionFacetCounts::forRequest($request, ['tenant.id'], $query),
        ]);
    }
}
