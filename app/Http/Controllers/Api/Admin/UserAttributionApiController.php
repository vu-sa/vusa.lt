<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Attribution-line suggestions for the `person-quote` rich-content block editor
 * (`Types/PersonQuoteEditor.vue`) — "Koordinatorius, VU SA MIF"-style labels built
 * from a user's current duties, plus the photo the block snapshots. The gate is the
 * same permission `ContentPartPreviewApiController` uses: any admin who can edit at
 * least one tenant's pages may look up attribution suggestions for any user, since
 * the quote itself still requires an explicit pick (this is not a general user
 * lookup — see `UserSearchApiController` for that).
 */
class UserAttributionApiController extends ApiController
{
    public function index(Request $request, User $user, ModelAuthorizer $authorizer): JsonResponse
    {
        abort_unless(
            $authorizer->forUser($this->requireAuth($request))->checkAllRoleables('pages.update.padalinys'),
            403,
        );

        $user->load('current_duties.institution.tenant');

        $attributions = $user->current_duties
            ->map(function ($duty) {
                $tenant = $duty->institution?->tenant;

                return trim(collect([$duty->getTranslation('name', app()->getLocale()), $tenant?->shortname])
                    ->filter()
                    ->implode(', '));
            })
            ->filter()
            ->unique()
            ->values();

        return $this->jsonSuccess([
            'name' => $user->name,
            'photoUrl' => $user->profile_photo_path,
            'attributions' => $attributions,
        ]);
    }
}
