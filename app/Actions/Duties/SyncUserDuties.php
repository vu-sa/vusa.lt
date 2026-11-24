<?php

namespace App\Actions\Duties;

use App\Actions\GetPermissableTenants;
use App\Actions\Responsibilities\AuthorizeDutyAssignment;
use App\Models\Duty;
use App\Models\User;
use App\Services\DutyAssignmentService;
use App\Services\ModelAuthorizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SyncUserDuties
{
    /**
     * @throws ValidationException When the change touches a duty outside the permitted tenants
     */
    public static function execute(
        User $actor,
        Collection $existingDutyIds,
        Collection $currentDutyIds,
        User $user,
        ModelAuthorizer $authorizer,
        string $permission
    ): void {
        $permissableTenantIds = GetPermissableTenants::execute($actor, $authorizer, $permission)->pluck('id');

        $newDutyIds = $existingDutyIds->diff($currentDutyIds)->values();
        $removedDutyIds = $currentDutyIds->diff($existingDutyIds)->values();

        // The form also posts unchanged duties in tenants the actor cannot administer.
        $touchedIds = $newDutyIds->merge($removedDutyIds);

        if ($touchedIds->isEmpty()) {
            return;
        }

        /** @var Collection<string, Duty> $duties */
        $duties = Duty::with('institution.tenant')->whereIn('id', $touchedIds)->get()->keyBy('id');

        $removedRows = $user->dutiables()->whereIn('duty_id', $removedDutyIds)->whereNull('tenant_id')->current()->get()->groupBy('duty_id');
        $refused = $touchedIds
            ->map(fn ($dutyId) => $duties->get($dutyId))
            ->filter(fn (?Duty $duty) => ! $duty || ! $permissableTenantIds->contains($duty->institution?->tenant?->id));

        if ($refused->isNotEmpty()) {
            throw ValidationException::withMessages([
                'current_duties' => __('users.duty_outside_tenant', [
                    'duties' => $refused->filter()->pluck('name')->join(', ') ?: '—',
                ]),
            ]);
        }

        foreach ($newDutyIds as $dutyId) {
            AuthorizeDutyAssignment::execute($actor, $duties->get($dutyId), (string) $user->id);
        }

        foreach ($removedRows as $rows) {
            if ($rows->whereNull('via_dutiable_id')->isEmpty()) {
                throw ValidationException::withMessages(['current_duties' => __('users.derived_duty_removal')]);
            }
        }

        $assignments = app(DutyAssignmentService::class);

        DB::transaction(function () use ($newDutyIds, $removedDutyIds, $duties, $user, $assignments): void {
            $assignments->lockDuties($duties->values());
            $user->auditRelationChange('current_duties', function () use ($newDutyIds, $removedDutyIds, $duties, $user, $assignments): void {
                foreach ($newDutyIds as $dutyId) {
                    $alreadyActive = $assignments->manualTerms($dutyId, $user->id, null)->current()->exists();

                    if (! $alreadyActive) {
                        try {
                            $assignments->startTerm($duties[$dutyId], $user->id, null, audit: false);
                        } catch (ValidationException $exception) {
                            $message = __('forms.helpers.institution_assignment_closed');

                            if (in_array($message, $exception->errors()['end_date'] ?? [], true)) {
                                throw ValidationException::withMessages(['current_duties' => $message]);
                            }

                            throw $exception;
                        }
                    }
                }

                foreach ($removedDutyIds as $dutyId) {
                    $assignments->endTerms($assignments->manualTerms($dutyId, $user->id, null)->current());
                }
            });
        });
    }
}
