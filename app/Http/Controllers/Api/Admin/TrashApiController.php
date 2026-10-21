<?php

namespace App\Http\Controllers\Api\Admin;

use App\Contracts\GuardsForceDelete;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\IndexTrashRequest;
use App\Http\Traits\HasTanstackTables;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Services\MeetingAccessService;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;

/**
 * The trash view of the Typesense-backed collections. The search index never holds soft-deleted
 * rows, so their trash reads the database — but it answers with each model's search document,
 * so the page renders trash through the same cells as the live list.
 */
class TrashApiController extends ApiController
{
    use HasTanstackTables;

    /**
     * Allowlisted collections; the route constrains `{collection}` to these keys.
     *
     * @var array<string, array{model: class-string<Model>, tenantRelation: string, permission: string, search: list<string>, blockers: list<string>}>
     */
    public const array COLLECTIONS = [
        'institutions' => [
            'model' => Institution::class,
            'tenantRelation' => 'tenant',
            'permission' => 'institutions.read.padalinys',
            'search' => ['name', 'alias', 'email'],
            'blockers' => ['meetings', 'duties', 'checkIns'],
        ],
        'meetings' => [
            'model' => Meeting::class,
            'tenantRelation' => 'tenants',
            'permission' => 'meetings.read.padalinys',
            'search' => ['title', 'description'],
            'blockers' => [],
        ],
        'news' => [
            'model' => News::class,
            'tenantRelation' => 'tenant',
            'permission' => 'news.read.padalinys',
            'search' => ['title', 'short'],
            'blockers' => [],
        ],
        'pages' => [
            'model' => Page::class,
            'tenantRelation' => 'tenant',
            'permission' => 'pages.read.padalinys',
            'search' => ['title', 'permalink'],
            'blockers' => [],
        ],
    ];

    public function __construct(
        private readonly TanstackTableService $tableService,
        private readonly ModelAuthorizer $authorizer,
    ) {}

    public function index(IndexTrashRequest $request, string $collection): JsonResponse
    {
        $config = self::COLLECTIONS[$collection];

        $this->authorizeApi('viewAny', $config['model']);

        return match ($collection) {
            'institutions' => $this->respond($this->tenantScoped(Institution::onlyTrashed(), $config), $request, $config),
            'meetings' => $this->respond(app(MeetingAccessService::class)->trashedMeetings(Meeting::onlyTrashed(), $request->user()), $request, $config),
            'news' => $this->respond($this->tenantScoped(News::onlyTrashed(), $config), $request, $config),
            'pages' => $this->respond($this->tenantScoped(Page::onlyTrashed(), $config), $request, $config),
            default => abort(404),
        };
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{tenantRelation: string, permission: string}  $config
     * @return Builder<TModel>
     */
    private function tenantScoped(Builder $query, array $config): Builder
    {
        return $this->tableService->applyPermissionFiltering($query, $config['tenantRelation'], $config['permission'], $this->authorizer);
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $trashed
     * @param  array{tenantRelation: string, permission: string, search: list<string>, blockers: list<string>}  $config
     */
    private function respond(Builder $trashed, IndexTrashRequest $request, array $config): JsonResponse
    {
        $query = $this->applyTanstackFilters(
            $trashed,
            $request,
            $this->tableService,
            $config['search'],
        );

        if ($config['blockers'] !== []) {
            $query->withCount($config['blockers']);
        }

        $records = $query->paginate($request->getPerPage());

        return $this->jsonSuccess([
            'items' => $records->getCollection()->map(fn (Model $record): array => $this->row($record))->values(),
            'total' => $records->total(),
            'per_page' => $records->perPage(),
            'current_page' => $records->currentPage(),
            'last_page' => $records->lastPage(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function row(Model $record): array
    {
        $deletedAt = $record->getAttribute('deleted_at');

        $row = [
            ...(method_exists($record, 'toSearchableArray') ? $record->toSearchableArray() : []),
            'id' => (string) $record->getKey(),
            'deleted_at' => $deletedAt instanceof CarbonInterface ? $deletedAt->toIso8601String() : null,
            'force_delete_blocked_reason' => $record instanceof GuardsForceDelete ? $record->forceDeleteBlockedReason() : null,
        ];

        if ($record instanceof Meeting) {
            $row['abilities'] = [
                'restore' => request()->user()->can('restore', $record),
                'forceDelete' => request()->user()->can('forceDelete', $record),
            ];
        }

        return $row;
    }
}
