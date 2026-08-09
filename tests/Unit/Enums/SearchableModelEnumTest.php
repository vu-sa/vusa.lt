<?php

use App\Enums\SearchableModelEnum;
use App\Models\Duty;
use App\Models\News;
use App\Models\Page;
use App\Models\PublicNews;
use App\Models\PublicPage;

describe('SearchableModelEnum', function (): void {
    test('includes Duty in searchable model classes', function (): void {
        expect(SearchableModelEnum::getAllModelClasses())
            ->toContain(Duty::class)
            ->and(SearchableModelEnum::getTypesenseModelClasses())
            ->toContain(Duty::class);
    });

    test('exposes the duty enum label', function (): void {
        expect(SearchableModelEnum::DUTY->label())->toBe('duty');
    });

    test('includes both the admin and public news/page models', function (): void {
        // News/Page (admin index, everything non-trashed) and PublicNews/PublicPage
        // (public index, gated by publish status) are separate Scout models — both
        // must be reindexed by search:reindex.
        expect(SearchableModelEnum::getAllModelClasses())
            ->toContain(News::class)
            ->toContain(Page::class)
            ->toContain(PublicNews::class)
            ->toContain(PublicPage::class);
    });

    test('exposes the public news/page enum labels', function (): void {
        expect(SearchableModelEnum::PUBLIC_NEWS->label())->toBe('public_news')
            ->and(SearchableModelEnum::PUBLIC_PAGE->label())->toBe('public_page');
    });
});
