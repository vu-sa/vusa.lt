<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\PublicController;
use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends PublicController
{
    /**
     * Display the meeting search page.
     */
    public function index(): Response
    {
        // Get shared public data
        $this->getBanners();
        $this->getTenantLinks();
        $this->shareOtherLangURL('publicMeetings.index');

        // Create SEO metadata
        $seo = $this->shareAndReturnSEOObject(
            title: __('search.meeting_page_title'),
            description: __('search.meeting_page_description')
        );

        // Render the meeting search page
        // Frontend handles all search via Typesense client-side
        return Inertia::render('Public/Meetings/ShowMeetings', [])
            ->withViewData([
                'SEOData' => $seo,
            ]);
    }
}
