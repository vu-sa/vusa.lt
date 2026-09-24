<?php

namespace App\Http\Controllers\Admin;

use App\Actions\AnnounceMeetingInCalendar;
use App\Actions\GetInstitutionCoordinator;
use App\Actions\GetRecentlyChangedMeetings;
use App\Enums\InstitutionScope;
use App\Events\MeetingFullyCreated;
use App\Http\Controllers\AdminController;
use App\Http\Requests\AttachMeetingInstitutionRequest;
use App\Http\Requests\IndexMeetingRequest;
use App\Http\Requests\StoreMeetingRequest;
use App\Http\Requests\UpdateMeetingRequest;
use App\Http\Resources\TaskResource;
use App\Http\Traits\HandlesSoftDeletes;
use App\Http\Traits\HasTanstackTables;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Services\CheckInService;
use App\Services\InstitutionScopeResolver;
use App\Services\MeetingCompletionService;
use App\Services\ModelAuthorizer as Authorizer;
use App\Services\RelationshipService;
use App\Services\ResourceServices\SharepointFileService;
use App\Support\MeetingTitle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class MeetingController extends AdminController
{
    use HandlesSoftDeletes, HasTanstackTables;

    public function __construct(
        public Authorizer $authorizer,
        private CheckInService $checkInService,
        private MeetingCompletionService $meetingCompletionService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(IndexMeetingRequest $request)
    {
        $this->handleAuthorization('viewAny', Meeting::class);

        // Live rows come from Typesense (the scoped key carries the authorization) and the trash
        // from api.v1.admin.trash.index, so the page itself needs no rows.
        return $this->inertiaResponse('Admin/Representation/IndexMeeting', [
            'deletedCount' => $this->scopedTrashedCount(Meeting::query(), 'tenants', 'meetings.read.padalinys'),
            'recentlyChanged' => $request->getShowDeleted() ? [] : GetRecentlyChangedMeetings::execute($request->user())->all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMeetingRequest $request)
    {
        $validatedData = $request->safe();

        DB::beginTransaction();

        try {
            $meetingType = $validatedData['type'] ?? null;
            $title = $this->buildMeetingTitle($validatedData['start_time'], $meetingType);

            // Created from an existing announcement: the meeting takes over the event's
            // timing rather than leaving the pair to drift (Meeting::syncCalendarEventTiming
            // then pushes it back, so both ends have to agree from the start).
            $announcement = isset($validatedData['calendar_id'])
                ? Calendar::query()->whereNull('meeting_id')->find($validatedData['calendar_id'])
                : null;

            $meeting = Meeting::create([
                'start_time' => $validatedData['start_time'],
                'title' => $title,
                'description' => $validatedData['description'] ?? null,
                'type' => $meetingType,
                'end_time' => $announcement?->end_date,
            ]);

            if ($announcement !== null) {
                $announcement->meeting_id = $meeting->id;
                // From here on the meeting owns the timing (Meeting::syncCalendarEventTiming),
                // so the announcement adopts it now rather than drifting until the next edit —
                // an email meeting, for instance, moves its start to a 23:59 deadline.
                $announcement->date = $meeting->start_time;
                $announcement->end_date = $meeting->end_time;
                $announcement->save();
            }

            $meeting->attachAudited('institutions', $validatedData['institution_id']);

            // Adjust any overlapping check-ins for this institution
            $institution = Institution::find($validatedData['institution_id']);
            if ($institution) {
                $meetingDate = Carbon::parse($validatedData['start_time']);
                $this->checkInService->adjustForMeeting($institution, $meetingDate);
            }

            // Create agenda items if provided
            if (isset($validatedData['agendaItems']) && is_array($validatedData['agendaItems'])) {
                foreach ($validatedData['agendaItems'] as $agendaItemData) {
                    AgendaItem::create([
                        'title' => ['lt' => $agendaItemData['title']],
                        'description' => isset($agendaItemData['description'])
                            ? ['lt' => $agendaItemData['description']]
                            : null,
                        'order' => $agendaItemData['order'],
                        'brought_by_students' => $agendaItemData['brought_by_students'] ?? false,
                        'start_time' => $agendaItemData['start_time'] ?? null,
                        'end_time' => $agendaItemData['end_time'] ?? null,
                        'meeting_id' => $meeting->id,
                    ]);
                }
            }

            if ($validatedData['announce_in_calendar'] ?? false) {
                AnnounceMeetingInCalendar::execute($meeting);
            }

            DB::commit();

            // Dispatch event after meeting is fully set up with all relationships
            event(new MeetingFullyCreated($meeting));

            // For Inertia requests (from modal), redirect to meeting show page. The action
            // window can ask to land straight in the bulk agenda dialog, which the show
            // page opens from `?action=add-bulk`.
            $parameters = ['meeting' => $meeting];

            if ($validatedData['open_bulk_agenda'] ?? false) {
                $parameters['action'] = 'add-bulk';
            }

            return redirect()->route('meetings.show', $parameters)->with(['success' => __('messages.meeting.created')]);

        } catch (\Throwable $e) {
            // \Throwable, not \Exception: a \TypeError (or any other \Error) between
            // beginTransaction() and commit() must still roll back, or the transaction depth
            // leaks for the rest of the process.
            DB::rollBack();

            return back()->withErrors(['general' => $e->getMessage()])->with(['error' => __('messages.meeting.create_failed')]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Meeting $meeting)
    {
        $this->handleAuthorization('view', $meeting);

        $meeting->load('institutions.types', 'institutions.tenant', 'fileableFiles', 'comments', 'calendarEvent')->load([
            'agendaItems' => function ($query): void {
                $query->with('votes')->withCount('comments')
                    ->withExists(['note as has_notes' => fn ($note) => $note->whereNotNull('notes_html')])
                    ->orderBy('order');
            },
        ])->loadCount(['comments', 'tasks', 'documents']);

        // Append is_public, is_joint and file status now that relations are loaded (avoids N+1)
        $meeting->append(['is_public', 'is_joint', 'has_protocol', 'has_report']);

        // Get representatives who were active at meeting time
        $representatives = $meeting->getRepresentativesActiveAt();

        // The primary institution determines the canonical public host.
        $primaryInstitution = $meeting->institutions->first();

        $canUpdate = Gate::allows('update', $meeting);
        $canCreateAgendaItems = $canUpdate && Gate::allows('create', AgendaItem::class);
        $agendaItemAbilities = $meeting->agendaItems->mapWithKeys(fn (AgendaItem $item): array => [
            (string) $item->getKey() => [
                'update' => Gate::allows('update', $item),
                'delete' => Gate::allows('delete', $item),
            ],
        ]);
        $missingActions = collect($this->meetingCompletionService->missingActions($meeting))
            ->filter(function (array $action) use ($agendaItemAbilities, $canCreateAgendaItems): bool {
                if ($action['type'] === 'agenda_missing') {
                    return $canCreateAgendaItems;
                }

                return $agendaItemAbilities->get($action['agenda_item_id'], [])['update'] ?? false;
            })
            ->values()
            ->all();
        $publicUrl = $meeting->is_public && $primaryInstitution?->tenant
            ? route('publicMeetings.show', [
                'subdomain' => $primaryInstitution->tenant->subdomain(),
                'lang' => app()->getLocale(),
                'meeting' => $meeting,
            ])
            : null;

        // show meeting
        return $this->inertiaResponse('Admin/Representation/ShowMeeting', [
            'meeting' => [
                ...$meeting->toArray(),
                'agenda_items' => $meeting->agendaItems->map(fn (AgendaItem $item): array => [
                    ...$item->toArray(),
                    'can' => $agendaItemAbilities->get((string) $item->getKey()),
                ])->all(),
                // The edit dialog writes the description, so it needs every locale rather
                // than the current one — the rest of the page reads the localized array above.
                'description' => $meeting->getTranslations('description'),
                'sharepointPath' => $meeting->institutions->isNotEmpty() ? SharepointFileService::pathForFileableDriveItem($meeting) : null,
            ],
            'representatives' => $representatives,
            // Nominated for the term the meeting fell in (O22). When present, these are the
            // people the agenda tasks went to instead of the whole membership.
            'secretaries' => InstitutionSecretaryController::forMeetingPayload($meeting),
            'administrators' => InstitutionSecretaryController::forMeetingPayload($meeting),
            'abilities' => [
                'update' => $canUpdate,
                'delete' => Gate::allows('delete', $meeting),
                'createAgendaItems' => $canCreateAgendaItems,
                'reorderAgendaItems' => $canUpdate,
                'attachInstitution' => $canUpdate,
            ],
            'completion' => [
                'status' => $this->meetingCompletionService->calculate($meeting),
                'missingActions' => $missingActions,
            ],
            'publicUrl' => $publicUrl,
            'availableInstitutionsForAttach' => $canUpdate
                ? $this->getAvailableInstitutionsForAttach($meeting)
                : [],
            'governanceScope' => $this->governanceScopeFor($meeting),
            'tasks' => Inertia::defer(fn () => TaskResource::collection(
                $meeting->tasks()->with('users:id,name,email,profile_photo_path', 'taskable')->get()
            )->resolve(), 'meetingPanels'),
            'documents' => Inertia::defer(fn () => $meeting->documents()
                ->orderBy('document_date')
                ->orderBy('title')
                ->get()
                ->each->append('language_code')
                ->toArray(), 'meetingPanels'),
            // R-g: a rep stuck on a record asks their institution's koordinatorius.
            'coordinator' => Inertia::defer(
                fn () => $primaryInstitution === null ? null : GetInstitutionCoordinator::execute($primaryInstitution, request()->user()),
                'meetingPanels',
            ),
        ]);
    }

    /**
     * The scope that decides which vote fields this meeting asks for.
     *
     * A joint VU/VU SA meeting keeps the student perspective, so an external institution wins.
     */
    private function governanceScopeFor(Meeting $meeting): string
    {
        $scopes = $meeting->institutions->map(fn (Institution $institution) => $institution->governance_scope);

        $external = $scopes->first(fn (InstitutionScope $scope) => $scope->isExternal());

        return ($external ?? $scopes->first() ?? InstitutionScopeResolver::DEFAULT)->value;
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Meeting $meeting)
    {
        $this->handleAuthorization('update', $meeting);

        return $this->inertiaResponse('Admin/Representation/EditMeeting', [
            'meeting' => $meeting,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMeetingRequest $request, Meeting $meeting)
    {
        $this->handleAuthorization('update', $meeting);

        $validated = $request->validated();

        $validated['title'] = $this->buildMeetingTitle(
            $validated['start_time'],
            $validated['type'] ?? $meeting->type?->value
        );

        $meeting->fill($validated);
        $meeting->save();

        return back()->with('success', __('messages.meeting.updated'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Meeting $meeting)
    {
        $this->handleAuthorization('delete', $meeting);

        $redirect_url = request()->redirect_to ?? back()->getTargetUrl();

        $meeting->delete();

        return redirect($redirect_url)->with('success', __('messages.meeting.deleted'));
    }

    public function restore(Meeting $meeting): RedirectResponse
    {
        return $this->restoreModel($meeting, __('messages.meeting.restored'));
    }

    /**
     * Attach an additional institution to a joint meeting.
     */
    public function attachInstitution(AttachMeetingInstitutionRequest $request, Meeting $meeting): RedirectResponse
    {
        $this->handleAuthorization('update', $meeting);

        $validated = $request->validated();

        $meeting->attachAudited('institutions', $validated['institution_id']);

        $institution = Institution::find($validated['institution_id']);
        if ($institution) {
            $this->checkInService->adjustForMeeting($institution, $meeting->start_time);
        }

        return back()->with('success', __('messages.meeting.institution_attached'));
    }

    /**
     * Detach an institution from a meeting (joint meeting must keep at least one).
     */
    public function detachInstitution(Meeting $meeting, Institution $institution): RedirectResponse
    {
        $this->handleAuthorization('update', $meeting);

        if ($meeting->institutions()->count() <= 1) {
            return back()->with('error', __('messages.meeting.institution_required'));
        }

        $meeting->detachAudited('institutions', $institution->id);

        return back()->with('success', __('messages.meeting.institution_detached'));
    }

    /**
     * Build the list of institutions the current user can attach to this meeting.
     * Includes the user's own duty institutions plus institutions related to them
     * via the relationship graph, minus those already attached to the meeting.
     */
    private function getAvailableInstitutionsForAttach(Meeting $meeting): Collection
    {
        $user = auth()->user();
        $userInstitutionIds = $user->loadMissing('current_duties')
            ->current_duties
            ->pluck('institution_id')
            ->filter()
            ->unique();

        $userInstitutions = Institution::whereIn('id', $userInstitutionIds)->get();

        $relatedIds = collect();
        foreach ($userInstitutions as $institution) {
            foreach (RelationshipService::getRelatedInstitutionsCached($institution) as $item) {
                $relatedIds->push($item['institution']->id);
            }
        }

        $attachedIds = $meeting->institutions->pluck('id')->toArray();

        $allAvailableIds = $userInstitutionIds
            ->merge($relatedIds)
            ->unique()
            ->diff($attachedIds)
            ->values();

        if ($allAvailableIds->isEmpty()) {
            return collect();
        }

        return Institution::whereIn('id', $allAvailableIds)
            ->with('tenant:id,shortname')
            ->get()
            ->map(fn (Institution $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'tenant_shortname' => $i->tenant?->shortname,
            ]);
    }

    /**
     * The stored title is always Lithuanian; {@see MeetingTitle} renders the other locale
     * on the public page, so a locale change never needs a data backfill.
     */
    private function buildMeetingTitle(mixed $startTime, mixed $type): string
    {
        return MeetingTitle::build($startTime, $type, 'lt');
    }

    public function forceDelete(Meeting $meeting): RedirectResponse
    {
        return $this->forceDeleteModel($meeting);
    }
}
