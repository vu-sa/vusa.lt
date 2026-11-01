<?php

namespace App\Http\Controllers\Api\Admin;

use App\Actions\UpdateInstitution;
use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\UpdateRecordActivityRequest;
use App\Http\Requests\StoreInstitutionRequest;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\User;
use App\Services\InstitutionDeactivationService;
use App\Services\InstitutionRelationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class InstitutionApiController extends ApiController
{
    public function status(Institution $institution, InstitutionDeactivationService $deactivation): JsonResponse
    {
        $this->authorizeApi('update', $institution);

        return $this->jsonSuccess([
            'is_active' => (bool) $institution->is_active,
            'deactivation' => $deactivation->summary($institution),
        ]);
    }

    public function updateStatus(UpdateRecordActivityRequest $request, Institution $institution, UpdateInstitution $update): JsonResponse
    {
        $update->execute($institution, $request->validated(), $request->user());

        return $this->jsonSuccess(['is_active' => (bool) $institution->fresh()->is_active], $this->entityMessage('updated', 'institution'));
    }

    public function preview(Institution $institution): JsonResponse
    {
        $this->authorizeApi('view', $institution);

        $institution->load([
            'types',
            'duties.current_users',
        ]);

        $meetings = $institution->meetings()
            ->with(['institutions', 'agendaItems.votes'])
            ->orderByDesc('start_time')
            ->limit(5)
            ->get();
        $viewer = request()->user();

        return $this->jsonSuccess([
            'types' => $institution->types
                ->map(fn ($type) => [
                    'id' => (string) $type->id,
                    'title' => $type->title,
                ])->values(),
            'last_meetings' => $meetings
                ->map(fn ($meeting) => [
                    'id' => (string) $meeting->id,
                    'title' => $meeting->title,
                    'start_time' => $meeting->start_time->timestamp,
                    // Seeing the institution does not grant its meetings' records.
                    'outcome' => Gate::forUser($viewer)->allows('view', $meeting) ? $meeting->outcomeSummary($viewer) : null,
                ])->values(),
            'representatives' => $institution->duties
                ->flatMap->current_users
                ->unique('id')
                ->map(fn ($user) => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                    'profile_photo_path' => $user->profile_photo_path,
                ])->values(),
            'related_institutions' => app(InstitutionRelationService::class)->summaryFor($institution),
        ]);
    }

    /**
     * Who has held a duty here, for the Nariai popover. Gated like the record page, because the
     * collection also lists public institutions the viewer cannot open.
     */
    public function memberTerms(Institution $institution): JsonResponse
    {
        $this->authorizeApi('view', $institution);

        return $this->jsonSuccess($institution->duties()->with('users:id,name')->get()
            ->flatMap(fn (Duty $duty) => $duty->users->map(fn (User $user) => [
                'name' => $user->name,
                'start_date' => $user->pivot->start_date,
                'end_date' => $user->pivot->end_date,
            ]))
            ->values());
    }

    public function store(StoreInstitutionRequest $request): JsonResponse
    {
        $this->authorizeApi('create', Institution::class);

        $institution = new Institution;

        $institution->fill($request->safe()->except('types'))->save();

        $institution->syncAudited('types', $request->types);

        $institution->load('tenant:id,shortname', 'types');

        return $this->jsonCreated([
            'institution' => $institution,
        ], $this->entityMessage('created', 'institution'));
    }
}
