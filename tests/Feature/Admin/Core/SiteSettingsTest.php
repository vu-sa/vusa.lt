<?php

use App\Models\Page;
use App\Models\Tenant;
use App\Settings\SiteSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

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
    test('resolves the public URL of the configured page in its own locale', function (): void {
        $lt = Page::factory()->for($this->tenant)->create([
            'lang' => 'lt',
            'permalink' => 'privatumo-politika',
        ]);
        $en = Page::factory()->for($this->tenant)->create([
            'lang' => 'en',
            'permalink' => 'privacy-policy',
        ]);

        $settings = app(SiteSettings::class);
        $settings->privacy_page_id_lt = (string) $lt->id;
        $settings->privacy_page_id_en = (string) $en->id;
        $settings->save();

        expect(app(SiteSettings::class)->privacyPageUrl('lt'))->toContain('privatumo-politika')
            ->and(app(SiteSettings::class)->privacyPageUrl('en'))->toContain('privacy-policy');
    });

    test('falls back to the other locale page when only one language is configured', function (): void {
        $lt = Page::factory()->for($this->tenant)->create([
            'lang' => 'lt',
            'permalink' => 'privatumo-politika',
        ]);

        $settings = app(SiteSettings::class);
        $settings->privacy_page_id_lt = (string) $lt->id;
        $settings->save();

        expect(app(SiteSettings::class)->privacyPageUrl('en'))->toContain('privatumo-politika')
            ->and(app(SiteSettings::class)->privacyPageUrl('lt'))->toContain('privatumo-politika');
    });

    test('returns null when no page is configured, so the link can be hidden', function (): void {
        expect(app(SiteSettings::class)->privacyPageUrl())->toBeNull();
    });
});

describe('site settings page authorization', function (): void {
    test('a settings manager can open the page and save both language slots', function (): void {
        $lt = Page::factory()->for($this->tenant)->create(['lang' => 'lt', 'is_active' => true]);
        $en = Page::factory()->for($this->tenant)->create(['lang' => 'en', 'is_active' => true]);

        asUser($this->admin)->get(route('settings.site.edit'))->assertOk();

        asUser($this->admin)
            ->post(route('settings.site.update'), [
                'privacy_page_id_lt' => (string) $lt->id,
                'privacy_page_id_en' => (string) $en->id,
            ])
            ->assertRedirect();

        $settings = app(SiteSettings::class);
        expect($settings->privacy_page_id_lt)->toBe((string) $lt->id)
            ->and($settings->privacy_page_id_en)->toBe((string) $en->id);
    });

    test('the edit page receives per-locale summaries of the selected pages', function (): void {
        $settings = app(SiteSettings::class);
        $settings->privacy_page_id_lt = (string) Page::factory()->for($this->tenant)
            ->create(['lang' => 'lt', 'title' => 'Privatumo politika', 'is_active' => true])->id;
        $settings->privacy_page_id_en = (string) Page::factory()->for($this->tenant)
            ->create(['lang' => 'en', 'title' => 'Privacy policy', 'is_active' => true])->id;
        $settings->save();

        asUser($this->admin)->get(route('settings.site.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Settings/EditSiteSettings')
                ->where('selectedPages.lt.title', 'Privatumo politika')
                ->where('selectedPages.en.title', 'Privacy policy')
            );
    });

    test('an inactive configured page is not offered as selected', function (): void {
        $settings = app(SiteSettings::class);
        $settings->privacy_page_id_lt = (string) Page::factory()->for($this->tenant)
            ->create(['lang' => 'lt', 'is_active' => false])->id;
        $settings->save();

        asUser($this->admin)->get(route('settings.site.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('selectedPages.lt', null)
            );
    });

    test('a user without settings access is refused', function (): void {
        asUser($this->user)->get(route('settings.site.edit'))->assertStatus(403);
        asUser($this->user)->post(route('settings.site.update'), ['privacy_page_id_lt' => null])->assertStatus(403);
    });

    test('a page in the wrong language is rejected for a slot', function (): void {
        $en = Page::factory()->for($this->tenant)->create(['lang' => 'en']);

        asUser($this->admin)
            ->post(route('settings.site.update'), ['privacy_page_id_lt' => (string) $en->id])
            ->assertSessionHasErrors('privacy_page_id_lt');
    });

    test('a soft-deleted page is rejected', function (): void {
        $lt = Page::factory()->for($this->tenant)->create(['lang' => 'lt']);
        $lt->delete();

        asUser($this->admin)
            ->post(route('settings.site.update'), ['privacy_page_id_lt' => (string) $lt->id])
            ->assertSessionHasErrors('privacy_page_id_lt');
    });

    test('an unknown page id is rejected', function (): void {
        asUser($this->admin)
            ->post(route('settings.site.update'), ['privacy_page_id_lt' => '99999999'])
            ->assertSessionHasErrors('privacy_page_id_lt');
    });
});
