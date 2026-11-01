<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\BuildDutyIndexQuery;
use App\Actions\Dutiables\SyncExOfficioTargets;
use App\Actions\SetDutyActive;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\UpdateRecordActivityRequest;
use App\Http\Requests\IndexDutyRequest;
use App\Http\Requests\StoreDutyRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Services\DutyAssignmentService;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use App\Support\CollectionFacetCounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DutyApiController extends ApiController
{
    use HasTanstackTables;

    public function __construct(private TanstackTableService $tableService, private ModelAuthorizer $authorizer) {}

    public function updateStatus(UpdateRecordActivityRequest $request, Duty $duty, SetDutyActive $update): JsonResponse
    {
        $update->execute($duty, $request->boolean('is_active'));

        return $this->jsonSuccess(['is_active' => $duty->fresh()->is_active], $this->entityMessage('updated', 'duty'));
    }

    public function index(IndexDutyRequest $request): JsonResponse
    {
        $this->authorizeApi('viewAny', Duty::class);

        $query = fn (IndexDutyRequest $request) => $this->applyTanstackFilters(
            BuildDutyIndexQuery::execute($request, $this->authorizer),
            $request,
            $this->tableService,
            ['name', 'email'],
        );
        $duties = $query($request)->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $duties->getCollection()->map(fn (Duty $duty): array => [...$duty->append('force_delete_blocked_reason')->toFullArray(), 'can_update' => $request->user()->can('update', $duty), 'can_delete' => $request->user()->can('delete', $duty)])->values(),
            'total' => $duties->total(),
            'per_page' => $duties->perPage(),
            'current_page' => $duties->currentPage(),
            'last_page' => $duties->lastPage(),
            'facets' => CollectionFacetCounts::forRequest($request, ['is_active', 'data_quality', 'duty_type_id', 'history'], $query),
        ]);
    }

    public function store(StoreDutyRequest $request, DutyAssignmentService $assignments): JsonResponse
    {
        $this->authorizeApi('create', Duty::class);

        return DB::transaction(function () use ($request, $assignments) {
            $duty = new Duty;

            $validatedData = $request->safe();
            $duty->fill(collect($validatedData)->except('types', 'roles', 'current_users', 'ex_officio_target_duty_ids', 'assignable_tenants')->toArray())->save();

            $duty->types()->sync($request->types);
            SyncExOfficioTargets::execute($duty, $validatedData['ex_officio_target_duty_ids'] ?? []);
            $duty->assignableTenants()->sync($assignments->buildAssignableTenantsSync($request->validated('assignable_tenants') ?? []));

            $assignments->syncRepresentatives($duty, $request->validated('current_users') ?? []);

            $duty->load('institution', 'types', 'current_users');

            return $this->jsonCreated([
                'duty' => $duty,
            ], $this->entityMessage('created', 'duty'));
        });
    }
}
