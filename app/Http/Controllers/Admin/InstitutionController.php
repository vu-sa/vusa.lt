<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ActivityRequests\GetInstitutionActivityRequestHistory;
use App\Actions\GenerateInstitutionAlias;
use App\Actions\GetTenantsForUpserts;
use App\Actions\GetUserTenantShortnames;
use App\Actions\Media\SyncImageMedia;
use App\Actions\UpdateInstitution;
use App\Http\Controllers\AdminController;
use App\Http\Requests\ActivityRequests\ActivityRequestHistoryRequest;
use App\Http\Requests\IndexInstitutionRequest;
use App\Http\Requests\ReorderDutiesRequest;
use App\Http\Requests\StoreInstitutionRequest;
use App\Http\Requests\UpdateInstitutionRequest;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Duty;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\InstitutionLink;
use App\Models\InstitutionType;
use App\Models\Pivots\AgendaItem;
use App\Models\PublicUrl;
use App\Services\InstitutionActivityStatusService;
use App\Services\InstitutionDeactivationService;
use App\Services\InstitutionPresenter;
use App\Services\KnowledgeBasePresenter;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\PostCreateRedirect;
use App\Settings\MeetingSettings;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Attributes\Controllers\Authorize;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class InstitutionController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(
        public Authorizer $authorizer,
        private readonly InstitutionActivityStatusService $activityStatusService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    #[Authorize('viewAny', Institution::class)]
    public function index(IndexInstitutionRequest $request): Response
    {
        // Live rows come from Typesense (the scoped key carries the authorization) and the trash
        // from api.v1.admin.trash.index, so the page itself needs no rows.
        return Inertia::render('Admin/People/IndexInstitution', [
            'statusUpdateScope' => fn () => [
                'all' => request()->user()->isSuperAdmin() || $this->authorizer->scope(request()->user(), 'institutions.update.padalinys')->isAllScope,
                'tenantIds' => $this->authorizer->tenants(request()->user(), 'institutions.update.padalinys')->pluck('id')->values(),
            ],
            'deletedCount' => $this->scopedTrashedCount(Institution::query(), 'tenant', 'institutions.read.padalinys'),
            // Following is per user, so the index cannot carry it; the page filters Typesense by these ids.
            'followedInstitutionIds' => $request->user()->followedInstitutions()->pluck('institutions.id')->map(fn ($id): string => (string) $id)->values(),
            'defaultTenantShortnames' => GetUserTenantShortnames::execute($request->user()),
            // Compared with each row's type_ids to offer "Sekti" (InstitutionPolicy::follow).
            'publicMeetingTypeIds' => app(MeetingSettings::class)->getPublicMeetingInstitutionTypeIds()->values(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    #[Authorize('create', Institution::class)]
    public function create(Request $request)
    {
        Inertia::share('seo.title', 'Nauja institucija');

        return Inertia::render('Admin/People/CreateInstitution', [
            'assignableTenants' => GetTenantsForUpserts::execute($request->user(), 'institutions.create.padalinys', $this->authorizer),
            'institutionTypes' => InstitutionType::query()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[Authorize('create', Institution::class)]
    public function store(StoreInstitutionRequest $request, SyncImageMedia $syncImage, PostCreateRedirect $redirect)
    {
        $institution = new Institution;

        $institution->fill($request->safe()->except('types', 'image_media', 'logo_media'))->save();
        $this->syncImages($institution, $request->validated(), $syncImage);

        $institution->syncAudited('types', $request->types);

        $institution->load('tenant:id,shortname', 'types');

        return redirect($redirect->url($request->user(), $institution, 'institutions.show', 'viewSummary', 'institutions.index'))
            ->with('success', $this->entityMessage('created', 'institution'));
    }

    /**
     * Display the specified resource.
     */
    // Authorized in ActivityRequestHistoryRequest: the bound institution or user.
    public function show(ActivityRequestHistoryRequest $request, Institution $institution): Response
    {
        // An active institution outside the user's reach opens as its public face: overview,
        // members and (public) meetings — no discussion, files, tasks, relations or management.
        $readOnly = fn (): bool => once(fn () => ! Gate::allows('view', $institution));

        $loadInstitution = fn (): Institution => once(function () use ($institution): Institution {
            $summary = $this->activityStatusService
                ->withSummary(Institution::query()->select('institutions.id')->whereKey($institution->id))
                ->withCount(['duties', 'meetings', 'problems'])
                ->firstOrFail();

            $institution->setRawAttributes([...$institution->getAttributes(), ...$summary->getAttributes()], true);
            $institution->setRelation('types', $summary->types);
            $institution->setRelation('checkIns', $summary->checkIns);
            $institution->loadMissing('tenant:id,shortname', 'duties.current_users:id,name,profile_photo_path,profile_photo_focal_point')
                ->append(['has_public_meetings', 'governance_scope']);

            return $institution;
        });

        $showsMeetings = fn (): bool => once(fn () => ! $readOnly() || $loadInstitution()->has_public_meetings);
        $activityStatus = fn () => once(fn () => $this->activityStatusService->resolve($loadInstitution()));

        $loadMeetings = function (BelongsToMany $query): Collection {
            $meetings = $query->get();

            foreach ($meetings as $meeting) {
                $meetingTenants = $meeting->tenants;
                $meeting->agendaItems->each(fn (AgendaItem $item) => $item->setRelation('tenants', $meetingTenants));
            }

            return $meetings;
        };

        $meetingQuery = fn () => $institution->meetings()
            ->select(['meetings.id', 'title', 'start_time', 'type', 'meetings.created_at'])
            ->withCount('agendaItems')
            ->with([
                'agendaItems' => fn ($query) => $query->select(['id', 'meeting_id', 'order', 'is_private', 'type'])->chaperone('meeting'),
                'agendaItems.votes:id,agenda_item_id,is_main,decision,student_vote,student_benefit,is_consensus',
                'institutions:id',
                'tenants',
                'institutions.types:id',
            ])
            ->withExists([
                'availableFiles as protocol_exists' => fn ($query) => $query->where('file_type', FileableFile::TYPE_PROTOCOL),
                'availableFiles as report_exists' => fn ($query) => $query->where('file_type', FileableFile::TYPE_REPORT),
            ])
            ->orderByDesc('start_time');
        $overviewMeetings = fn () => once(fn () => ! $showsMeetings() ? collect() : $loadMeetings($meetingQuery()->where('start_time', '<=', now())->limit(3)));
        $canViewActivityRequests = app(GetInstitutionActivityRequestHistory::class)->visible($institution, $request->user());

        return Inertia::render('Admin/People/ShowInstitution', [
            'institution' => fn () => InstitutionPresenter::forShow($loadInstitution(), $readOnly()),
            'canViewActivityRequests' => $canViewActivityRequests,
            ...($canViewActivityRequests ? ['activityRequests' => Inertia::optional(fn () => InstitutionPresenter::activityRequests($institution, $request->user(), $request->historyPage()))] : []),
            'readOnly' => fn (): bool => $readOnly(),
            'overview' => fn () => InstitutionPresenter::overview($loadInstitution(), $showsMeetings(), $activityStatus(), $overviewMeetings()),
            'files' => Inertia::optional(fn () => $readOnly() ? [] : InstitutionPresenter::files($institution)),
            'overviewDuties' => fn () => InstitutionPresenter::duties($loadInstitution(), 3),
            'upcomingMeeting' => fn () => $showsMeetings() ? InstitutionPresenter::meetings($loadMeetings($meetingQuery()->where('start_time', '>', now())->reorder('start_time')->limit(1)))[0] ?? null : null,
            'duties' => Inertia::optional(fn () => InstitutionPresenter::duties($institution)),
            'incompleteTasksCount' => fn () => $readOnly() ? 0 : InstitutionPresenter::incompleteTasksCount($institution),
            'tasks' => Inertia::optional(fn () => $readOnly() ? [] : InstitutionPresenter::tasks($institution)),
            'problems' => Inertia::optional(fn () => InstitutionPresenter::problems($institution)),
            'relatedInstitutions' => Inertia::optional(fn () => $readOnly() ? [] : InstitutionPresenter::relatedInstitutions($institution)),
            'institutionLinks' => Inertia::optional(fn () => $readOnly() ? [] : InstitutionPresenter::institutionLinks($institution)),
            'relationKinds' => fn () => InstitutionPresenter::relationKinds($request->user()->can('create', InstitutionLink::class)),
            'can' => fn () => InstitutionPresenter::abilities($institution, $request->user(), $readOnly()),
            'management' => Inertia::optional(fn () => $request->user()->can('update', $institution)
                ? InstitutionPresenter::management($institution) : null),
            'assignmentTerms' => Inertia::optional(fn () => $request->user()->can('update', $institution) ? InstitutionPresenter::assignmentTerms($institution) : []),
            'subscription' => fn () => InstitutionPresenter::subscription($institution, $request->user()),
            'knowledgePages' => Inertia::defer(fn () => KnowledgeBasePresenter::forInstitution($institution, app()->getLocale()), 'knowledge'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    #[Authorize('update', 'institution')]
    public function edit(Request $request, Institution $institution)
    {
        $institution->load('types');

        Inertia::share('seo.title', $institution->name);

        return Inertia::render('Admin/People/EditInstitution', [
            'deactivation' => app(InstitutionDeactivationService::class)->summary($institution),
            'institution' => [
                ...$institution->toFullArray(),
                'image_media' => $institution->imageData('image'),
                'logo_media' => $institution->imageData('logo'),
                'types' => $institution->types->pluck('id'),
                'public_url' => $institution->publicUrl('lt'),
                'alias_pinned' => GenerateInstitutionAlias::isPinned($institution),
                'public_urls' => $institution->publicUrls()->get(['id', 'url', 'locale', 'created_at']),
            ],
            'institutionTypes' => InstitutionType::query()->get(),
            'assignableTenants' => GetTenantsForUpserts::execute($request->user(), 'institutions.update.padalinys', $this->authorizer),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    #[Authorize('update', 'institution')]
    public function update(UpdateInstitutionRequest $request, Institution $institution, UpdateInstitution $update)
    {
        $update->execute($institution, $request->validated(), $request->user());

        return back()->with('success', $this->entityMessage('updated', 'institution'));
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function syncImages(Institution $institution, array $validated, SyncImageMedia $syncImage): void
    {
        $syncImage->fromValidated($institution, 'image', $validated, 'image_media', auth()->user());
        $syncImage->fromValidated($institution, 'logo', $validated, 'logo_media', auth()->user());
    }

    /**
     * Remove the specified resource from storage.
     */
    #[Authorize('delete', 'institution')]
    public function destroy(Institution $institution)
    {
        // check if auth user is from this institution
        if (auth()->user()->institutions->contains($institution)) {
            return back()->with('error', __('messages.institution.cannot_delete_own'));
        }

        $institution->delete();

        return redirect()->route('institutions.index')->with('info', $this->entityMessage('deleted', 'institution'));
    }

    /**
     * Restore the specified resource from storage.
     */
    #[Authorize('restore', 'institution')]
    public function restore(Institution $institution): RedirectResponse
    {
        $response = $this->restoreModel($institution, $this->entityMessage('restored', 'institution'));
        $institution->refresh();

        if ($institution->trashed() || ! request()->user()->can('viewSummary', $institution)) {
            return $response;
        }

        return redirect()->route('institutions.show', $institution);
    }

    /**
     * Remove a legacy public URL (an old alias that still 301-redirects here).
     */
    #[Authorize('update', 'institution')]
    public function destroyPublicUrl(Institution $institution, PublicUrl $publicUrl): RedirectResponse
    {
        $publicUrl->delete();

        return back()->with('info', $this->entityMessage('deleted', 'publicUrl'));
    }

    /**
     * reorderDuties
     * Duties are ordered in the frontend array by the user. The order is saved in the database
     *
     * @return RedirectResponse
     */
    // Authorized in ReorderDutiesRequest: every institution that owns a selected duty.
    public function reorderDuties(ReorderDutiesRequest $request)
    {
        $duties = collect($request->validated('duties'));

        $dutyModels = Duty::whereIn('id', $duties->pluck('id'))->get()->keyBy('id');

        foreach ($duties as $duty) {
            $dutyModel = $dutyModels->get($duty['id']);

            if ($dutyModel) {
                $dutyModel->order = $duty['order'];
                $dutyModel->save();
            }
        }

        return back()->with('success', __('messages.duty.order_updated'));
    }

    #[Authorize('forceDelete', 'institution')]
    public function forceDelete(Institution $institution): RedirectResponse
    {
        return $this->forceDeleteModel($institution);
    }
}
