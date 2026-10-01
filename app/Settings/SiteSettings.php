<?php

namespace App\Settings;

use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Spatie\LaravelSettings\Settings;

/**
 * Site-wide pointers to content that lives in the database.
 *
 * The first of these is the privacy policy. `ConsentCard.vue` used to hardcode
 * `${app.url}/privatumas` — a Lithuanian permalink with no locale prefix, so English visitors
 * were sent to the Lithuanian page, and renaming the page silently broke the cookie banner's
 * only link. Storing the page ids instead means the link follows the pages.
 *
 * Ids rather than slugs, matching how every other setting in this directory stores its target
 * (see FormSettings::$member_registration_form_id).
 */
class SiteSettings extends Settings
{
    /**
     * Ids of the Pages holding the privacy policy, one per language. Chosen separately —
     * they need not be each other's translated counterpart.
     */
    public ?string $privacy_page_id_lt = null;

    public ?string $privacy_page_id_en = null;

    public static function group(): string
    {
        return 'site';
    }

    #[\Override]
    public function save(): self
    {
        parent::save();

        self::forgetCachedPrivacyPageUrls();

        return $this;
    }

    /**
     * {@see privacyPageUrl()} cached, for the cookie banner on every page. Loading this
     * settings group and the page costs three queries otherwise.
     */
    public static function cachedPrivacyPageUrl(string $locale): ?string
    {
        // Cached as '' when unset, since the cache treats a stored null as a miss.
        $url = Cache::rememberForever(self::privacyPageUrlCacheKey($locale),
            fn () => app(self::class)->privacyPageUrl($locale) ?? '');

        return $url === '' ? null : $url;
    }

    /**
     * Called when the settings, a page or a tenant subdomain may have changed the URL.
     */
    public static function forgetCachedPrivacyPageUrls(): void
    {
        foreach (['lt', 'en'] as $locale) {
            Cache::forget(self::privacyPageUrlCacheKey($locale));
        }
    }

    private static function privacyPageUrlCacheKey(string $locale): string
    {
        return "privacy-page-url:{$locale}";
    }

    /**
     * The public URL of the privacy policy page in the given locale.
     *
     * Falls back to the other locale's page when this locale has none configured, and to null
     * when neither is set — callers hide the link rather than render a broken one.
     */
    public function privacyPageUrl(?string $locale = null): ?string
    {
        $page = $this->privacyPage($locale ?? app()->getLocale());

        if ($page === null) {
            return null;
        }

        return route('page', [
            'lang' => $page->lang,
            'subdomain' => $page->tenant->subdomain(),
            'permalink' => $page->permalink,
        ]);
    }

    private function privacyPage(string $locale): ?Page
    {
        $id = $locale === 'en'
            ? ($this->privacy_page_id_en ?? $this->privacy_page_id_lt)
            : ($this->privacy_page_id_lt ?? $this->privacy_page_id_en);

        if ($id === null) {
            return null;
        }

        return Page::query()->with('tenant')->find($id);
    }
}
