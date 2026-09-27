<?php

namespace App\Actions;

use App\Enums\Responsibility;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\User;
use App\Services\NotificationRouter;
use App\Services\ResponsibilityResolver;

/**
 * The koordinatoriai (O22) of one or more institutions: whoever holds the studentų atstovų
 * koordinavimas responsibility for them, named by and reachable through that duty.
 */
class GetInstitutionCoordinators
{
    /**
     * @param  iterable<Institution>  $institutions
     * @return list<array{id: string, name: string, email: string|null, profile_photo_path: string|null, duty: string|null, institutions: list<string>}>
     */
    public static function execute(iterable $institutions, ?User $except = null): array
    {
        $resolver = app(ResponsibilityResolver::class);

        /** @var array<string, array{id: string, name: string, email: string|null, profile_photo_path: string|null, duty: string|null, institutions: list<string>}> $coordinators */
        $coordinators = [];

        foreach ($institutions as $institution) {
            $institutionName = (string) $institution->getTranslation('name', app()->getLocale());

            $duties = $resolver->dutiesFor(Responsibility::StudentRepCoordination, $institution)->loadMissing('current_users');

            foreach ($duties as $duty) {
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
