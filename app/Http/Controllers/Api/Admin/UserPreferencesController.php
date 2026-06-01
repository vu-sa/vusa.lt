<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\UpdateUIPreferencesRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserPreferencesController extends ApiController
{
    /**
     * Update sidebar customization preferences (which sections are visible,
     * their order, and which quick actions are shown).
     */
    public function updateUIPreferences(UpdateUIPreferencesRequest $request): Response
    {
        $user = $this->requireAuth($request);

        $sections = $request->input('sidebar.sections');
        if (is_array($sections)) {
            $user->setSidebarSectionVisibility($sections);
        }

        $order = $request->input('sidebar.order');
        if (is_array($order)) {
            $user->setSidebarSectionOrder($order);
        }

        $quickActions = $request->input('quick_actions');
        if (is_array($quickActions)) {
            $user->setQuickActionVisibility($quickActions);
        }

        if ($request->has('sidebar.collapsed')) {
            $user->setSidebarCollapsed($request->boolean('sidebar.collapsed'));
        }

        $density = $request->input('appearance.density');
        if (is_string($density)) {
            $user->setDensity($density);
        }

        $pinnedPages = $request->input('pinned_pages');
        if (is_array($pinnedPages)) {
            $user->setPinnedPages($pinnedPages);
        }

        return response()->noContent();
    }

    /**
     * Record a recently visited admin page.
     */
    public function trackRecentPage(Request $request): Response
    {
        $validated = $request->validate([
            'route' => 'required_without:clear|nullable|string',
            'params' => 'nullable|array',
            'title' => 'nullable|string|max:255',
            'url' => 'nullable|string|max:2048',
            'clear' => 'nullable|boolean',
        ]);

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
