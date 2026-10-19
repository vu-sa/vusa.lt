<?php

use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->where('alias', 'mif')->firstOrFail();
});

describe('institution', function (): void {
    test('previews the alias a new institution would get, prefixed when the slug is taken', function (): void {
        Institution::factory()->create(['alias' => 'studiju-kolegija']);
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

        asUser($admin)->getJson(route('api.v1.admin.institutions.aliasPreview', ['title' => 'Studijų kolegija', 'lang' => 'lt', 'tenant_id' => $this->tenant->id]))
            ->assertOk()
            ->assertJsonPath('data.permalink', 'mif-studiju-kolegija')
            ->assertJsonPath('data.url', fn (string $url) => str_ends_with($url, '/kontaktai/mif-studiju-kolegija') && str_contains($url, 'mif.'));
    });

    test('a rename keeps the institution’s own alias available to it', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create(['alias' => 'studiju-kolegija']);
        $admin = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

        asUser($admin)->getJson(route('api.v1.admin.institutions.aliasPreview', ['title' => 'Studijų kolegija', 'lang' => 'lt', 'institution_id' => $institution->id]))
            ->assertOk()
            ->assertJsonPath('data.permalink', 'studiju-kolegija');
    });

    test('someone who cannot update the institution gets no preview', function (): void {
        $institution = Institution::factory()->create();

        asUser(makeUser($this->tenant))->getJson(route('api.v1.admin.institutions.aliasPreview', ['title' => 'Bet kas', 'lang' => 'lt', 'institution_id' => $institution->id]))
            ->assertForbidden();
    });
});
