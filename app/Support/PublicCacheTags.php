<?php

namespace App\Support;

use App\Enums\LocaleEnum;
use Illuminate\Database\Eloquent\Model;

/**
 * Narrow cache tags for public content, one per slice a model save can invalidate.
 *
 * A tagged flush deletes every entry carrying *any* of the listed tags, so a model hook
 * flushes only its narrow tag. Entries also carry their broad type tag ('pages', 'sitemap',
 * …) for SystemMaintenanceService's flush-everything action.
 */
final class PublicCacheTags
{
    public static function pages(int $tenantId, string $locale): string
    {
        return "pages:{$tenantId}:{$locale}";
    }

    public static function quickLinks(int $tenantId, string $locale): string
    {
        return "quick_links:{$tenantId}:{$locale}";
    }

    public static function pagesSitemap(int $tenantId): string
    {
        return "sitemap:pages:{$tenantId}";
    }

    public static function newsSitemap(int $tenantId): string
    {
        return "sitemap:news:{$tenantId}";
    }

    /**
     * Every page slice of the tenants a saved model belongs to, before and after the save.
     * Both locales: a page's cached entry carries its other-language counterpart.
     *
     * @return list<string>
     */
    public static function pagesOf(Model $page): array
    {
        return self::forTenantsOf($page, fn (int $tenantId) => [
            ...array_map(fn (LocaleEnum $locale) => self::pages($tenantId, $locale->value), LocaleEnum::cases()),
            self::pagesSitemap($tenantId),
        ]);
    }

    /**
     * @return list<string>
     */
    public static function newsOf(Model $news): array
    {
        return self::forTenantsOf($news, fn (int $tenantId) => [self::newsSitemap($tenantId)]);
    }

    /**
     * @return list<string>
     */
    public static function quickLinksOf(Model $quickLink): array
    {
        $pairs = [
            [$quickLink->getAttribute('tenant_id'), $quickLink->getAttribute('lang')],
            [$quickLink->getOriginal('tenant_id'), $quickLink->getOriginal('lang')],
        ];

        return array_values(array_unique(array_map(
            fn (array $pair) => self::quickLinks((int) $pair[0], (string) $pair[1]),
            array_filter($pairs, fn (array $pair) => $pair[0] !== null && $pair[1] !== null),
        )));
    }

    /**
     * @param  callable(int): list<string>  $tagsFor
     * @return list<string>
     */
    private static function forTenantsOf(Model $model, callable $tagsFor): array
    {
        $tenantIds = array_unique(array_filter(
            [$model->getAttribute('tenant_id'), $model->getOriginal('tenant_id')],
            fn ($tenantId) => $tenantId !== null,
        ));

        return array_merge(...array_map(fn ($tenantId) => $tagsFor((int) $tenantId), $tenantIds));
    }
}
