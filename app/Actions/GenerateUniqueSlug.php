<?php

namespace App\Actions;

use App\Models\News;
use App\Models\Page;
use App\Services\PublicUrlService;
use Illuminate\Support\Str;

class GenerateUniqueSlug
{
    /**
     * `permalink` column width, both `news` and `pages`
     * (`database/migrations/2026_09_09_124357_widen_news_and_pages_permalink_columns.php`) — not
     * a design choice, a hard technical ceiling. Uncapped titles are otherwise fine, but a slug
     * this long overflows the column and `save()` throws a `QueryException` instead of writing
     * anything.
     */
    private const int MAX_LENGTH = 255;

    /**
     * A `Str::slug($title)` permalink, scoped unique per tenant by appending `-2`, `-3`, ...
     * on collision, truncated to fit the column.
     *
     * @param  class-string<News>|class-string<Page>  $modelClass  Must have `permalink` +
     *                                                             `tenant_id` columns and use SoftDeletes.
     * @param  int|null  $excludeId  Omit this record's own id from the collision check (renaming).
     */
    public static function execute(string $modelClass, string $title, int $tenantId, ?int $excludeId = null): string
    {
        $base = Str::slug($title);

        if ($base === '') {
            $base = Str::random(8);
        }

        $base = self::truncate($base, self::MAX_LENGTH);
        $slug = $base;
        $suffix = 1;

        while (self::isTaken($modelClass, $slug, $tenantId, $excludeId)) {
            $suffix++;
            $suffixText = "-{$suffix}";
            $slug = self::truncate($base, self::MAX_LENGTH - mb_strlen($suffixText)).$suffixText;
        }

        return $slug;
    }

    private static function truncate(string $slug, int $maxLength): string
    {
        return rtrim(mb_substr($slug, 0, $maxLength), '-');
    }

    /**
     * True when $slug can't be assigned: either a live-or-trashed record in this tenant already
     * holds it, or {@see isRetiredByAnother()} says a different record has claimed it in history.
     * Checks `withTrashed()` — a trashed row still occupies the DB's unique index, matching
     * `UniqueAmongTrashed`'s validation semantics.
     *
     * @param  class-string<News>|class-string<Page>  $modelClass
     */
    private static function isTaken(string $modelClass, string $slug, int $tenantId, ?int $excludeId): bool
    {
        $liveCollision = $modelClass::withTrashed()
            ->where('permalink', $slug)
            ->where('tenant_id', $tenantId)
            ->when($excludeId !== null, fn ($query) => $query->where('id', '!=', $excludeId))
            ->exists();

        return $liveCollision || self::isRetiredByAnother($modelClass, $slug, $tenantId, $excludeId);
    }

    /**
     * Whether a *different* record of this type has ever retired $slug as an old permalink —
     * checked for News/Page's create flow and reused directly by their update validation.
     * public_urls.url is globally unique, so silently allowing the reuse would either steal that
     * other record's redirect immediately, or — once this new owner itself moves on — leave
     * nothing to redirect its own old link, since recordLegacyUrl() would find the url already
     * taken and skip it.
     *
     * permalink uniqueness (permalink, tenant_id) doesn't include lang, so the same text is
     * unique per tenant regardless of language — both variants are checked, since either could be
     * what an existing retired row belongs to.
     *
     * @param  class-string<News>|class-string<Page>  $modelClass
     */
    public static function isRetiredByAnother(string $modelClass, string $slug, int $tenantId, ?int $excludeId): bool
    {
        $publicUrls = app(PublicUrlService::class);
        $morphClass = (new $modelClass)->getMorphClass();

        foreach (['lt', 'en'] as $lang) {
            $preview = (new $modelClass)->setRawAttributes([
                'permalink' => $slug,
                'tenant_id' => $tenantId,
                'lang' => $lang,
            ], true);

            $url = $preview->publicUrl();

            if ($url !== null && $publicUrls->isRetiredByAnother($url, $morphClass, $excludeId)) {
                return true;
            }
        }

        return false;
    }
}
