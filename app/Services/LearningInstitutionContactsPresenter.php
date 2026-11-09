<?php

namespace App\Services;

use App\Actions\Cadences\ResolveCadenceForInstitution;
use App\Enums\ResponsibilityPurpose;
use App\Models\Institution;
use App\Models\User;
use Database\Seeders\TypeSeeder;

class LearningInstitutionContactsPresenter
{
    /** @return list<array{id: string, title: string, contacts: list<array{name: string, role: string, email: string|null}>}> */
    public static function forUser(User $user, string $locale): array
    {
        $institutionIds = $user->authorization_duties()
            ->holdingPurpose(ResponsibilityPurpose::StudentRepresentation)
            ->pluck('institution_id')->filter()->unique()->values();
        if ($institutionIds->isEmpty()) {
            return [];
        }

        $cadences = ResolveCadenceForInstitution::forInstitutions($institutionIds);
        $institutions = Institution::query()->whereIn('id', $institutionIds)->with([
            'duties' => fn ($query) => $query->whereHas('types', fn ($types) => $types->where('duty_types.id', TypeSeeder::CHAIR_DUTY_TYPE_ID))->with('current_users'),
            'secretaryAssignments.user',
        ])->get();
        $result = [];

        foreach ($institutions as $institution) {
            $contacts = [];
            foreach ($institution->duties as $duty) {
                foreach ($duty->current_users as $holder) {
                    $contacts[] = [
                        'name' => $holder->name,
                        'role' => (string) __('learning.path.chair', [], $locale),
                        'email' => filled($duty->email) ? $duty->email : $holder->email,
                    ];
                }
            }
            $cadence = $cadences[$institution->id] ?? null;
            if ($cadence !== null) {
                foreach ($institution->secretaryAssignments as $assignment) {
                    if ($assignment->cadence_id !== $cadence->id || $assignment->user === null) {
                        continue;
                    }
                    $contacts[] = [
                        'name' => $assignment->user->name,
                        'role' => (string) __('learning.path.secretary', [], $locale),
                        'email' => $assignment->user->email,
                    ];
                }
            }
            $result[] = ['id' => $institution->id, 'title' => (string) $institution->getTranslation('name', $locale), 'contacts' => $contacts];
        }

        return $result;
    }
}
