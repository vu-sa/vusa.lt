<?php

namespace App\Services;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\PublicInstitution;
use App\Models\User;

class ContactSearchIndexSynchronizer
{
    public function assignmentChanged(Duty $duty, ?User $user): void
    {
        $duty->searchable();
        $user?->searchable();
        $this->refreshInstitution($duty);
    }

    public function dutyChanged(Duty $duty): void
    {
        $duty->users()->get()->each->searchable();
        $this->refreshInstitution($duty);

        $previousInstitutionId = $duty->getPrevious()['institution_id'] ?? null;

        if ($previousInstitutionId !== null && $previousInstitutionId !== $duty->institution_id) {
            $this->refreshInstitutionById($previousInstitutionId);
        }
    }

    public function userChanged(User $user): void
    {
        $duties = $user->duties()->get();

        $duties->each->searchable();
        $duties->unique('institution_id')->each(fn (Duty $duty) => $this->refreshInstitution($duty));
    }

    public function refreshInstitution(Duty $duty): void
    {
        $this->refreshInstitutionById($duty->institution_id);
    }

    private function refreshInstitutionById(string $institutionId): void
    {
        $institution = Institution::query()->find($institutionId);

        if ($institution === null) {
            $this->unsearchPublicInstitutionById($institutionId);

            return;
        }

        $institution->searchable();

        $publicInstitution = PublicInstitution::query()->find($institution->id);

        if ($publicInstitution?->shouldBeSearchable()) {
            $publicInstitution->searchable();
        } else {
            $this->unsearchPublicInstitutionById($institution->id);
        }
    }

    private function unsearchPublicInstitutionById(string $institutionId): void
    {
        $publicInstitution = new PublicInstitution;
        $publicInstitution->setAttribute($publicInstitution->getKeyName(), $institutionId);
        $publicInstitution->unsearchable();
    }
}
