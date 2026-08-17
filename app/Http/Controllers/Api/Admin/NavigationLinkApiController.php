<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Admin\ResolveNavigationUrlRequest;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Page;
use App\Support\LocalizedRouteSlugs;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class NavigationLinkApiController extends ApiController
{
    /**
     * Resolve the public URL for a record picked in the navigation link
     * target selector, so the admin form never has to assemble Ziggy routes
     * (and their tenant/subdomain/permalink inputs) itself.
     *
     * @route POST /api/v1/admin/navigation/resolve-url
     *
     * @routeName api.v1.admin.navigation.resolveUrl
     */
    public function resolveUrl(ResolveNavigationUrlRequest $request): JsonResponse
    {
        $data = $request->validated();

        $url = match ($data['collection']) {
            'pages' => $this->resolvePageUrl($data['id']),
            'news' => $this->resolveNewsUrl($data['id']),
            'calendar' => $this->resolveCalendarUrl($data['id']),
            'institutions' => $this->resolveInstitutionUrl($data['id']),
            'documents' => $this->resolveDocumentUrl($data['id']),
            // Category isn't a Typesense collection (see NavigationController::getCategoryOptions)
            // but shares this endpoint since it's still a navigation link target.
            'categories' => $this->resolveCategoryUrl($data['id']),
            // Unreachable: `collection` is already validated against Rule::in() above.
            default => null,
        };

        if ($url === null) {
            return $this->jsonError('Record not found.', 404);
        }

        return $this->jsonSuccess(['url' => $url]);
    }

    private function resolvePageUrl(int|string $id): ?string
    {
        $page = Page::query()->with('tenant:id,alias')->find($id);

        if (! $page) {
            return null;
        }

        return route('page', [
            'lang' => $page->lang,
            'subdomain' => $page->tenant->subdomain(),
            'permalink' => $page->permalink,
        ]);
    }

    private function resolveNewsUrl(int|string $id): ?string
    {
        $news = News::query()->with('tenant:id,alias')->find($id);

        if (! $news) {
            return null;
        }

        return LocalizedRouteSlugs::route('news', [
            'news' => $news->permalink,
            'subdomain' => $news->tenant->subdomain(),
        ], $news->lang);
    }

    private function resolveCalendarUrl(int|string $id): ?string
    {
        $calendar = Calendar::query()->find($id);

        if (! $calendar) {
            return null;
        }

        return route('calendar.event', [
            'lang' => app()->getLocale(),
            'calendar' => $calendar->id,
        ]);
    }

    private function resolveInstitutionUrl(int|string $id): ?string
    {
        $institution = Institution::query()->with('tenant:id,alias')->find($id);

        if (! $institution) {
            return null;
        }

        return route('contacts.institution', [
            'lang' => app()->getLocale(),
            'institution' => $institution->id,
            'subdomain' => $institution->tenant->subdomain(),
        ]);
    }

    private function resolveDocumentUrl(int|string $id): ?string
    {
        $document = Document::query()->find($id);

        if (! $document || ! $document->anonymous_url) {
            return null;
        }

        return $document->anonymous_url;
    }

    private function resolveCategoryUrl(int|string $id): ?string
    {
        $category = Category::query()->find($id);

        if (! $category || ! $category->alias) {
            return null;
        }

        // Categories carry no tenant relation, so — matching the previous client-side
        // resolution this endpoint replaces — they always resolve against `www`.
        return route('category', [
            'lang' => app()->getLocale(),
            'category' => $category->alias,
            'subdomain' => 'www',
        ]);
    }
}
