<?php

use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Page;
use App\Models\PublicUrl;
use App\Models\Tenant;
use App\Services\PublicUrlService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;

/**
 * Replaces the retired `pirmakursiuStovyklos` route (Public/SummerCamps.vue) with content pages:
 * each past year becomes a child of the current camps page, cloned from it with the event-list
 * year pinned, and the old `/{slug}/{year}` URLs are recorded as legacy URLs so they 301.
 */
return new class extends Migration
{
    private const array YEARS = [2025, 2024, 2023, 2022];

    private const array SOURCE_PERMALINKS = ['lt' => 'pirmakursiu-stovyklos', 'en' => 'freshmen-camps'];

    /** The EN archive links were built with the LT slug, so both old EN forms must redirect. */
    private const array LEGACY_SLUGS = ['lt' => ['pirmakursiu-stovyklos'], 'en' => ['freshmen-camps', 'pirmakursiu-stovyklos']];

    /** The content-grid and news link-list talk about the current year's camp only. */
    private const array CLONED_PART_TYPES = ['hero', 'event-list', 'link-list'];

    public function up(): void
    {
        $sources = $this->sourcePages();

        /** @var array<int, array<string, Page>> $yearPages */
        $yearPages = [];

        foreach ($sources as $lang => $source) {
            foreach (self::YEARS as $index => $year) {
                $yearPages[$year][$lang] = $this->yearPage($source, $lang, $year, $index);
            }
        }

        foreach ($yearPages as $pages) {
            if (isset($pages['lt'], $pages['en'])) {
                $pages['lt']->update(['other_lang_id' => $pages['en']->id]);
                $pages['en']->update(['other_lang_id' => $pages['lt']->id]);
            }
        }

        foreach ($sources as $lang => $source) {
            $family = [$source, ...array_map(fn (array $pages) => $pages[$lang], $yearPages)];

            foreach ($family as $page) {
                $this->rewriteArchiveLinks($page, $family, $lang);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->sourcePages() as $source) {
            $permalinks = array_map(fn (int $year) => "{$source->permalink}-{$year}", self::YEARS);

            Page::withTrashed()
                ->where('tenant_id', $source->tenant_id)
                ->whereIn('permalink', $permalinks)
                ->get()
                ->each(function (Page $page): void {
                    PublicUrl::query()->where('urlable_type', $page->getMorphClass())->where('urlable_id', $page->id)->delete();
                    $contentId = $page->content_id;
                    $page->forceDelete();
                    ContentPart::query()->where('content_id', $contentId)->delete();
                    Content::query()->whereKey($contentId)->delete();
                });
        }
    }

    /** @return array<string, Page> */
    private function sourcePages(): array
    {
        $tenant = Tenant::main();

        if ($tenant === null) {
            return [];
        }

        $sources = [];

        foreach (self::SOURCE_PERMALINKS as $lang => $permalink) {
            $page = Page::query()
                ->where('tenant_id', $tenant->id)
                ->where('lang', $lang)
                ->where('permalink', $permalink)
                ->first();

            if ($page !== null) {
                $sources[$lang] = $page;
            }
        }

        return $sources;
    }

    private function yearPage(Page $source, string $lang, int $year, int $sortOrder): Page
    {
        $permalink = "{$source->permalink}-{$year}";

        $existing = Page::query()
            ->where('tenant_id', $source->tenant_id)
            ->where('permalink', $permalink)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $title = $this->yearTitle($lang, $year);
        $content = Content::query()->create();

        $page = Page::query()->create([
            'title' => $title,
            'permalink' => $permalink,
            'lang' => $lang,
            'tenant_id' => $source->tenant_id,
            'content_id' => $content->id,
            'parent_id' => $source->id,
            'sort_order' => $sortOrder,
            'is_active' => true,
            'layout' => $source->layout,
            'show_title' => $source->show_title,
            'show_breadcrumbs' => $source->show_breadcrumbs,
            'show_table_of_contents' => $source->show_table_of_contents,
            'meta_description' => $source->meta_description,
        ]);

        $order = 0;

        foreach ($this->partsOf($source) as $sourcePart) {
            if (! in_array($sourcePart->type, self::CLONED_PART_TYPES, true)) {
                continue;
            }

            $json = $this->decode($sourcePart->getRawOriginal('json_content'));
            $options = $this->decode($sourcePart->getRawOriginal('options'));

            if ($sourcePart->type === 'link-list' && ($options['source'] ?? null) !== 'manual') {
                continue;
            }

            if ($sourcePart->type === 'hero') {
                $json['title'] = $title;
            }

            if ($sourcePart->type === 'event-list') {
                $options['year'] = $year;
            }

            $part = new ContentPart([
                'type' => $sourcePart->type,
                'json_content' => $json,
                'options' => $options,
                'order' => $order++,
            ]);
            $part->content_id = $content->id;
            $part->save();
        }

        $publicUrls = app(PublicUrlService::class);

        foreach (self::LEGACY_SLUGS[$lang] as $slug) {
            $publicUrls->recordLegacyUrl($page, $lang, route('page', [
                'subdomain' => 'www',
                'lang' => $lang,
                'permalink' => "{$slug}/{$year}",
            ]));
        }

        return $page;
    }

    /**
     * Every page in the family lists every other year, so a visitor can move between any two.
     *
     * @param  array<int, Page>  $family
     */
    private function rewriteArchiveLinks(Page $page, array $family, string $lang): void
    {
        $archive = $this->partsOf($page)->first(
            fn (ContentPart $part) => $part->type === 'link-list'
                && ($this->decode($part->getRawOriginal('options'))['source'] ?? null) === 'manual'
        );

        if ($archive === null) {
            return;
        }

        $links = collect($family)
            ->reject(fn (Page $other) => $other->is($page))
            ->map(fn (Page $other) => [
                'title' => $this->yearTitle($lang, $this->pinnedYear($other)),
                'url' => $other->publicUrl(),
            ])
            ->sortByDesc('title')
            ->values()
            ->all();

        $json = $this->decode($archive->getRawOriginal('json_content'));
        $json['links'] = $links;
        $archive->update(['json_content' => $json]);
    }

    private function pinnedYear(Page $page): int
    {
        $eventList = $this->partsOf($page)->firstWhere('type', 'event-list');
        $options = $eventList === null ? [] : $this->decode($eventList->getRawOriginal('options'));

        return (int) ($options['year'] ?? date('Y'));
    }

    /** @return Collection<int, ContentPart> */
    private function partsOf(Page $page)
    {
        return ContentPart::query()->where('content_id', $page->content_id)->orderBy('order')->get();
    }

    private function yearTitle(string $lang, int $year): string
    {
        return $lang === 'en' ? "Freshmen camps {$year}" : "{$year} m. pirmakursių stovyklos";
    }

    /** @return array<string, mixed> */
    private function decode(?string $json): array
    {
        $decoded = json_decode($json ?? '', true);

        return is_array($decoded) ? $decoded : [];
    }
};
