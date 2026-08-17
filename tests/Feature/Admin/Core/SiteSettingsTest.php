<?php

use App\Models\Page;
use App\Models\Tenant;
use App\Settings\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeUser($this->tenant);
    $this->admin->assignRole(config('permission.super_admin_role_name'));
    $this->user = makeUser($this->tenant);
});

/**
 * ConsentCard.vue used to hardcode `${app.url}/privatumas` — a Lithuanian permalink with no
 * locale prefix, so English visitors landed on the Lithuanian page, and renaming the page
 * broke the cookie banner's only link with nothing to catch it.
 */
describe('privacy page setting', function (): void {
    test('resolves the public URL of the configured page', function (): void {
        $page = Page::factory()->for($this->tenant)->create([
            'lang' => 'lt',
            'permalink' => 'privatumo-politika',
        ]);

        $settings = app(SiteSettings::class);
        $settings->privacy_page_id = (string) $page->id;
        $settings->save();

        expect(app(SiteSettings::class)->privacyPageUrl('lt'))
            ->toContain('privatumo-politika');
    });

    test('follows the counterpart record when the visitor is on the other locale', function (): void {
        $lt = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'permalink' => 'privatumo-politika']);
        $en = Page::factory()->for($this->tenant)->create([
            'lang' => 'en',
            'permalink' => 'privacy-policy',
            'other_lang_id' => $lt->id,
        ]);
        $lt->update(['other_lang_id' => $en->id]);

        $settings = app(SiteSettings::class);
        $settings->privacy_page_id = (string) $lt->id;
        $settings->save();

        expect(app(SiteSettings::class)->privacyPageUrl('en'))->toContain('privacy-policy')
            ->and(app(SiteSettings::class)->privacyPageUrl('lt'))->toContain('privatumo-politika');
    });

    test('returns null when no page is configured, so the link can be hidden', function (): void {
        expect(app(SiteSettings::class)->privacyPageUrl())->toBeNull();
    });
});

describe('site settings page authorization', function (): void {
    test('a settings manager can open and save the page', function (): void {
        $page = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);

        asUser($this->admin)->get(route('settings.site.edit'))->assertOk();

        asUser($this->admin)
            ->post(route('settings.site.update'), ['privacy_page_id' => (string) $page->id])
            ->assertRedirect();

        expect(app(SiteSettings::class)->privacy_page_id)->toBe((string) $page->id);
    });

    test('a user without settings access is refused', function (): void {
        asUser($this->user)->get(route('settings.site.edit'))->assertStatus(403);
        asUser($this->user)->post(route('settings.site.update'), ['privacy_page_id' => null])->assertStatus(403);
    });

    test('an unknown page id is rejected', function (): void {
        asUser($this->admin)
            ->post(route('settings.site.update'), ['privacy_page_id' => '99999999'])
            ->assertSessionHasErrors('privacy_page_id');
    });
});
