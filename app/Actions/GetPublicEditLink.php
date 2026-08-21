<?php

namespace App\Actions;

use App\Models\Calendar;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Resolves the admin edit link for a record shown on a public page, so an
 * authorized editor can jump straight to it instead of hunting through /mano.
 */
class GetPublicEditLink
{
    /**
     * Calendar's admin resource is registered singular ('calendar'), unlike the rest.
     */
    private const EDIT_ROUTES = [
        Page::class => 'pages.edit',
        News::class => 'news.edit',
        Calendar::class => 'calendar.edit',
        Institution::class => 'institutions.edit',
        Meeting::class => 'meetings.edit',
    ];

    /**
     * @return array{url: string, type: string, id: int}|null Null when there is no editable
     *                                                        record or the user may not edit it.
     */
    public static function execute(Model $model): ?array
    {
        $user = Auth::user();

        // Guests bail out before any policy or tenant resolution runs — anonymous
        // traffic must not pay for this.
        if ($user === null) {
            return null;
        }

        if ($model instanceof Tenant) {
            if (! $user->can('updateMainPage', $model)) {
                return null;
            }

            return [
                'url' => route('tenants.editMainPage', $model),
                'type' => 'homepage',
                'id' => $model->id,
            ];
        }

        $editRoute = self::EDIT_ROUTES[$model::class] ?? null;

        if ($editRoute === null || ! $user->can('update', $model)) {
            return null;
        }

        return [
            'url' => route($editRoute, $model),
            'type' => strtolower(class_basename($model)),
            'id' => $model->getKey(),
        ];
    }
}
