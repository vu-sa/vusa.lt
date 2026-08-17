<?php

namespace App\Settings;

use App\Models\Page;
use Spatie\LaravelSettings\Settings;

/**
 * Site-wide pointers to content that lives in the database.
 *
 * The first of these is the privacy policy. `ConsentCard.vue` used to hardcode
 * `${app.url}/privatumas` — a Lithuanian permalink with no locale prefix, so English visitors
 * were sent to the Lithuanian page, and renaming the page silently broke the cookie banner's
 * only link. Storing the page id instead means the link follows the page.
 *
 * Ids rather than slugs, matching how every other setting in this directory stores its target
 * (see FormSettings::$member_registration_form_id).
 */
class SiteSettings extends Settings
{
    /**
     * Id of the Page holding the privacy policy. Only one id is stored — the counterpart in
     * the other language is resolved through Page::otherLanguagePage().
     */
    public ?string $privacy_page_id = null;

    public static function group(): string
    {
        return 'site';
    }

    /**
     * The public URL of the privacy policy page in the current locale.
     *
     * Falls back to the configured page's own locale when no translated counterpart exists,
     * and to null when nothing is configured — callers hide the link rather than render a
     * broken one.
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
        if ($this->privacy_page_id === null) {
            return null;
        }

        $page = Page::query()->with('tenant')->find($this->privacy_page_id);

        if ($page === null) {
            return null;
        }

        if ($page->lang === $locale) {
            return $page;
        }

        $counterpart = $page->otherLanguagePage()->with('tenant')->first();

        return $counterpart instanceof Page ? $counterpart : $page;
    }
}
