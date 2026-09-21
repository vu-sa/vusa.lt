<?php

namespace App\Actions;

use App\Models\Form;
use App\Models\User;
use App\Services\FormRegistrationVisibilityService;
use Illuminate\Support\Collection;

final class SerializeFormsForTable
{
    /**
     * @param Collection<int, Form> $forms
     * @return Collection<int, array<string, mixed>>
     */
    public static function execute(Collection $forms, User $user, FormRegistrationVisibilityService $registrationVisibility): Collection
    {
        return $forms->map(function (Form $form) use ($user, $registrationVisibility): array {
            $registrationsCount = $registrationVisibility->isSharedRegistrationForm($form)
                ? $registrationVisibility->count($form, $user)
                : $form->registrations_count;

            return [
                ...$form->toFullArray(),
                'registrations_count' => $registrationsCount,
                'tenant' => [
                    'id' => $form->tenant->id,
                    'shortname' => $form->tenant->shortname,
                ],
                'can' => [
                    'view' => $user->can('view', $form),
                    'update' => $user->can('update', $form),
                    'delete' => $user->can('delete', $form),
                ],
            ];
        });
    }
}
