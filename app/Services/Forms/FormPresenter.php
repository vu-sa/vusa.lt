<?php

namespace App\Services\Forms;

use App\Models\Form;
use App\Models\User;
use App\Services\FormRegistrationVisibilityService;
use Illuminate\Support\Collection;

final class FormPresenter
{
    /**
     * @param  Collection<int, Form>  $forms
     * @return list<array{
     *     id: string, name: array{lt: string, en: string}, description: array{lt: string, en: string},
     *     tenant_id: int, path: array{lt: string, en: string}, publish_time: string|null,
     *     created_at: string, updated_at: string, deleted_at: string|null, registrations_count: int,
     *     force_delete_blocked_reason: string|null, tenant: array{id: int, shortname: string},
     *     can: array{view: bool, update: bool, delete: bool}
     * }>
     */
    public static function tableRows(Collection $forms, User $user, FormRegistrationVisibilityService $registrationVisibility): array
    {
        $rows = [];

        foreach ($forms as $form) {
            $registrationsCount = $registrationVisibility->isSharedRegistrationForm($form)
                ? $registrationVisibility->count($form, $user)
                : $form->registrations_count;

            $attributes = $form->attributesToArray();
            $rows[] = [
                'id' => $form->id,
                'name' => self::translations($form, 'name'),
                'description' => self::translations($form, 'description'),
                'tenant_id' => $form->tenant_id,
                'path' => self::translations($form, 'path'),
                'publish_time' => $attributes['publish_time'],
                'created_at' => $attributes['created_at'],
                'updated_at' => $attributes['updated_at'],
                'deleted_at' => $attributes['deleted_at'],
                'registrations_count' => $registrationsCount,
                // Appended only on trash rows; computing it for every row would query each form.
                'force_delete_blocked_reason' => $form->hasAppended('force_delete_blocked_reason') ? $form->force_delete_blocked_reason : null,
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
        }

        return $rows;
    }

    /** @return array{lt: string, en: string} */
    private static function translations(Form $form, string $field): array
    {
        $translations = $form->getTranslations($field);

        return ['lt' => (string) ($translations['lt'] ?? ''), 'en' => (string) ($translations['en'] ?? '')];
    }
}
