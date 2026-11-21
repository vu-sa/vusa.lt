<?php

namespace App\Actions\Duties;

use App\Actions\GetPermissableTenants;
use App\Actions\Responsibilities\AuthorizeDutyAssignment;
use App\Models\Duty;
use App\Models\Pivots\Dutiable;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Support\MorphMap;
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

        foreach ($removedDutyIds as $dutyId) {
            $rows = Dutiable::query()->where('duty_id', $dutyId)
                ->where('dutiable_type', MorphMap::alias(User::class))->where('dutiable_id', $user->id)->current()->get();
            if ($rows->isNotEmpty() && $rows->whereNull('via_dutiable_id')->isEmpty()) {
                throw ValidationException::withMessages(['current_duties' => __('users.derived_duty_removal')]);
            }
        }

        // Log duty changes as a single relation_updated activity on the user.
        DB::transaction(fn () => $user->auditRelationChange('current_duties', function () use ($newDutyIds, $removedDutyIds, $duties, $user): void {
            foreach ($newDutyIds as $dutyId) {
                // Prevent duplicate active duty rows from concurrent saves.
                $alreadyActive = Dutiable::query()
                    ->where('duty_id', $dutyId)
                    ->where('dutiable_type', MorphMap::alias(User::class))
                    ->where('dutiable_id', $user->id)
                    ->whereNull('via_dutiable_id')
                    ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', today()))
                    ->exists();

                if (! $alreadyActive) {
                    $user->duties()->attach($duties->get($dutyId), ['start_date' => now()->subDay()]);
                }
            }

            foreach ($removedDutyIds as $dutyId) {
                // updateExistingPivot() can match a historical row instead of the active term.
                Dutiable::where('duty_id', $dutyId)
                    ->where('dutiable_type', MorphMap::alias(User::class))
                    ->where('dutiable_id', $user->id)
                    ->whereNull('via_dutiable_id')
                    ->where(function ($query): void {
                        $query->whereNull('end_date')
                            ->orWhereDate('end_date', '>=', today());
                    })
                    ->get()
                    ->each(fn (Dutiable $dutiable) => $dutiable->update(['end_date' => now()->subDay()]));
            }
        }));
    }
}
