<?php

namespace App\Policies;

use App\Actions\Cadences\ResolveCadenceForDuty;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\HandoverNote;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\ResponsibilityResolver;

class HandoverNotePolicy
{
    public function __construct(private ModelAuthorizer $authorizer, private ResponsibilityResolver $responsibilities) {}

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, HandoverNote $note): bool
    {
        if ($user->isSuperAdmin() || $note->user_id === $user->id) {
            return true;
        }
        $duty = $note->duty;
        $ids = $duty->successors()->withTrashed()->pluck('duties.id')->push($duty->id);
        $scope = $this->authorizer->scope($user, 'duties.update.padalinys');

        return $user->duties()->whereIn('duties.id', $ids)
            ->where(fn ($query) => $query->whereNull('dutiables.end_date')->orWhereDate('dutiables.end_date', '>=', today()->subDays(60)))->exists()
            || $this->coordinates($user, $duty)
            || ($scope->granted && ($scope->isAllScope || $scope->tenantIds()->contains($duty->institution?->tenant_id)));
    }

    public function create(User $user, Duty $duty): bool
    {
        return $user->dutiables()->where('duty_id', $duty->id)->whereDate('start_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()->subDays(60)))->exists();
    }

    public function update(User $user, HandoverNote $note): bool
    {
        if ($note->user_id !== $user->id) {
            return false;
        }
        $term = $user->dutiables()->where('duty_id', $note->duty_id)->whereDate('start_date', '<=', today())
            ->where(fn ($query) => $query->whereNull('end_date')->orWhereDate('end_date', '>=', today()->subDays(60)))
            ->latest('start_date')->first();

        return $term !== null && $note->cadence_id === ResolveCadenceForDuty::execute($note->duty, $term->start_date)?->id;
    }

    private function coordinates(User $user, Duty $duty): bool
    {
        return $this->responsibilities->institutionIdsFor($user, ResponsibilityPurpose::StudentRepCoordination)->contains($duty->institution_id);
    }
}
