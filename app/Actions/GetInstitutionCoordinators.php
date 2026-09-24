<?php

namespace App\Actions;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\User;
use App\Services\NotificationRouter;
use App\Settings\AtstovavimasSettings;
use Illuminate\Database\Eloquent\Builder;

/**
 * The koordinatoriai (O22) of one or more institutions, each named by and reachable through the
 * coordinator duty itself — not whichever duty or personal address the user happens to have first.
 */
class GetInstitutionCoordinators
{
    /**
     * @param  iterable<Institution>  $institutions
     * @return list<array{id: string, name: string, email: string|null, profile_photo_path: string|null, duty: string|null, institutions: list<string>}>
     */
    public static function execute(iterable $institutions, ?User $except = null): array
    {
        $managerRoleId = app(AtstovavimasSettings::class)->getInstitutionManagerRoleId();

        if (! $managerRoleId) {
            return [];
        }

        /** @var array<string, array{id: string, name: string, email: string|null, profile_photo_path: string|null, duty: string|null, institutions: list<string>}> $coordinators */
        $coordinators = [];

        foreach ($institutions as $institution) {
            $institutionName = (string) $institution->getTranslation('name', app()->getLocale());

            $managerDuties = Duty::query()
                ->whereHas('institution', fn (Builder $query) => $query->where('tenant_id', $institution->tenant_id))
                ->whereHas('roles', fn (Builder $query) => $query->where('id', $managerRoleId))
                ->with('current_users')
                ->orderBy('order')
                ->get();

            foreach ($managerDuties as $duty) {
                /** @var User $user */
                foreach ($duty->current_users as $user) {
                    if ($user->id === $except?->id) {
                        continue;
                    }

                    $key = (string) $user->id;

                    $coordinators[$key] ??= [
                        'id' => $key,
                        'name' => $user->name,
                        'email' => self::emailFor($duty, $user),
                        'profile_photo_path' => $user->profile_photo_path,
                        'duty' => (string) $duty->getTranslation('name', app()->getLocale()),
                        'institutions' => [],
                    ];

                    if (! in_array($institutionName, $coordinators[$key]['institutions'], true)) {
                        $coordinators[$key]['institutions'][] = $institutionName;
                    }
                }
            }
        }

        return array_values($coordinators);
    }

    /**
     * The role's own address reaches whoever holds the duty next; a personal one does not.
     */
    private static function emailFor(Duty $duty, User $user): string
    {
        if (filled($duty->email)) {
            return $duty->email;
        }

        $additionalEmail = $user->getRelationValue('pivot')?->additional_email;

        if (filled($additionalEmail)) {
            return $additionalEmail;
        }

        return app(NotificationRouter::class)->preferredEmail($user);
    }
}
