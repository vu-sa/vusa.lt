<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\TrackRecentPageRequest;
use App\Http\Requests\UpdateUIPreferencesRequest;
use Illuminate\Http\Response;

class UserPreferencesApiController extends ApiController
{
    /**
     * Update the user's pinned pages.
     */
    public function updateUIPreferences(UpdateUIPreferencesRequest $request): Response
    {
        $user = $this->requireAuth($request);

        $pinnedPages = $request->input('pinned_pages');
        if (is_array($pinnedPages)) {
            $user->setPinnedPages($pinnedPages);
        }

        return response()->noContent();
    }

    /**
     * Record a recently visited admin page.
     */
    public function trackRecentPage(TrackRecentPageRequest $request): Response
    {
        $validated = $request->validated();

        $user = $this->requireAuth($request);

        if ($request->boolean('clear')) {
            $user->clearRecentPages();

            return response()->noContent();
        }

        $user->pushRecentPage(
            $validated['route'],
            $validated['params'] ?? [],
            $validated['title'] ?? null,
            $validated['url'] ?? null,
        );

        return response()->noContent();
    }
}
