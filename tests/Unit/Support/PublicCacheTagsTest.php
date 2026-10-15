<?php

use App\Models\Page;
use App\Support\PublicCacheTags;

test('a page moved to another tenant flushes both tenants', function (): void {
    $page = (new Page)->setRawAttributes(['tenant_id' => 1, 'lang' => 'lt'], true);
    $page->tenant_id = 2;

    expect(PublicCacheTags::pagesOf($page))->toEqualCanonicalizing([
        'pages:1:lt', 'pages:1:en', 'sitemap:pages:1',
        'pages:2:lt', 'pages:2:en', 'sitemap:pages:2',
    ]);
});
