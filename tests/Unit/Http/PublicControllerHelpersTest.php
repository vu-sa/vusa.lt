<?php

use App\Http\Controllers\PublicController;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create main tenant (vusa -> www subdomain)
    $this->mainTenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    // Create a padalinys tenant (mif subdomain)
    $this->mifTenant = Tenant::firstOrCreate(
        ['alias' => 'mif'],
        [
            'shortname' => 'VU SA MIF',
            'shortname_vu' => 'MIF',
            'fullname' => 'VU SA Matematikos ir informatikos fakultetas',
            'type' => 'padalinys',
        ]
    );

    // Create a test controller instance to test protected methods
    $this->controller = new class extends PublicController
    {
        // Make protected methods accessible for testing
        public function __construct()
        {
            // Skip parent constructor for unit testing
        }

        public function setTenant(Tenant $tenant): void
        {
            $this->tenant = $tenant;
            $this->subdomain = $tenant->alias === 'vusa' ? 'www' : $tenant->alias;
        }

        public function publicGetSubdomainForTenant(?Tenant $tenant = null): string
        {
            return $this->getSubdomainForTenant($tenant);
        }

        public function publicTenantRoute(string $routeName, array $parameters = [], ?Tenant $tenant = null): string
        {
            return $this->tenantRoute($routeName, $parameters, $tenant);
        }

        public function publicReplaceSubdomainInUrl(string $url, ?Tenant $tenant = null): string
        {
            return $this->replaceSubdomainInUrl($url, $tenant);
        }
    };
});

describe('getSubdomainForTenant', function () {
    it('returns www for vusa tenant', function () {
        $this->controller->setTenant($this->mainTenant);

        $subdomain = $this->controller->publicGetSubdomainForTenant($this->mainTenant);

        expect($subdomain)->toBe('www');
    });

    it('returns alias for non-vusa tenant', function () {
        $this->controller->setTenant($this->mainTenant);

        $subdomain = $this->controller->publicGetSubdomainForTenant($this->mifTenant);

        expect($subdomain)->toBe('mif');
    });

    it('uses current tenant when no tenant provided', function () {
        $this->controller->setTenant($this->mifTenant);

        $subdomain = $this->controller->publicGetSubdomainForTenant(null);

        expect($subdomain)->toBe('mif');
    });

    it('returns www when current tenant is vusa and no tenant provided', function () {
        $this->controller->setTenant($this->mainTenant);

        $subdomain = $this->controller->publicGetSubdomainForTenant(null);

        expect($subdomain)->toBe('www');
    });
});

describe('tenantRoute', function () {
    it('generates route URL with www subdomain for vusa tenant', function () {
        $this->controller->setTenant($this->mainTenant);

        $url = $this->controller->publicTenantRoute('home', ['lang' => 'lt'], $this->mainTenant);

        expect($url)->toContain('www.');
        expect($url)->toContain('/lt');
    });

    it('generates route URL with alias subdomain for padalinys tenant', function () {
        $this->controller->setTenant($this->mainTenant);

        $url = $this->controller->publicTenantRoute('home', ['lang' => 'lt'], $this->mifTenant);

        expect($url)->toContain('mif.');
        expect($url)->toContain('/lt');
    });

    it('adds extra parameters as query string', function () {
        $this->controller->setTenant($this->mainTenant);

        $url = $this->controller->publicTenantRoute('newsArchive', [
            'lang' => 'lt',
            'newsString' => 'naujienos',
            'page' => 2,
        ], $this->mainTenant);

        expect($url)->toContain('page=2');
    });

    it('uses current tenant when no tenant provided', function () {
        $this->controller->setTenant($this->mifTenant);

        $url = $this->controller->publicTenantRoute('home', ['lang' => 'lt']);

        expect($url)->toContain('mif.');
    });
});

describe('replaceSubdomainInUrl', function () {
    it('replaces www subdomain with tenant alias', function () {
        $this->controller->setTenant($this->mainTenant);

        $originalUrl = 'https://www.vusa.lt/lt/naujienos';
        $newUrl = $this->controller->publicReplaceSubdomainInUrl($originalUrl, $this->mifTenant);

        expect($newUrl)->toContain('mif.');
        expect($newUrl)->not->toContain('www.');
        expect($newUrl)->toContain('/lt/naujienos');
    });

    it('replaces tenant alias with www for vusa tenant', function () {
        $this->controller->setTenant($this->mainTenant);

        $originalUrl = 'https://mif.vusa.lt/lt/naujienos';
        $newUrl = $this->controller->publicReplaceSubdomainInUrl($originalUrl, $this->mainTenant);

        expect($newUrl)->toContain('www.');
        expect($newUrl)->not->toContain('mif.');
        expect($newUrl)->toContain('/lt/naujienos');
    });

    it('preserves query string when replacing subdomain', function () {
        $this->controller->setTenant($this->mainTenant);

        $originalUrl = 'https://www.vusa.lt/lt/naujienos?page=2&tag=test';
        $newUrl = $this->controller->publicReplaceSubdomainInUrl($originalUrl, $this->mifTenant);

        expect($newUrl)->toContain('mif.');
        expect($newUrl)->toContain('page=2');
        expect($newUrl)->toContain('tag=test');
    });

    it('preserves path when replacing subdomain', function () {
        $this->controller->setTenant($this->mainTenant);

        $originalUrl = 'https://www.vusa.lt/lt/kontaktai/id/123';
        $newUrl = $this->controller->publicReplaceSubdomainInUrl($originalUrl, $this->mifTenant);

        expect($newUrl)->toContain('/lt/kontaktai/id/123');
    });

    it('returns original URL if parsing fails', function () {
        $this->controller->setTenant($this->mainTenant);

        $originalUrl = 'not-a-valid-url';
        $newUrl = $this->controller->publicReplaceSubdomainInUrl($originalUrl, $this->mifTenant);

        expect($newUrl)->toBe($originalUrl);
    });

    it('uses current tenant when no tenant provided', function () {
        $this->controller->setTenant($this->mifTenant);

        $originalUrl = 'https://www.vusa.lt/lt/naujienos';
        $newUrl = $this->controller->publicReplaceSubdomainInUrl($originalUrl);

        expect($newUrl)->toContain('mif.');
    });
});

describe('subdomain consistency', function () {
    it('maps vusa alias to www subdomain consistently', function () {
        $this->controller->setTenant($this->mainTenant);

        // Test via getSubdomainForTenant
        $subdomain = $this->controller->publicGetSubdomainForTenant($this->mainTenant);
        expect($subdomain)->toBe('www');

        // Test via tenantRoute - should use www subdomain, not vusa
        $url = $this->controller->publicTenantRoute('home', ['lang' => 'lt'], $this->mainTenant);
        expect($url)->toContain('www.');
        // The URL should start with www. subdomain (not vusa. subdomain)
        expect($url)->toMatch('/^https?:\/\/www\./');
    });

    it('preserves non-vusa aliases as subdomains', function () {
        $this->controller->setTenant($this->mainTenant);

        $ifTenant = Tenant::firstOrCreate(
            ['alias' => 'if'],
            [
                'shortname' => 'VU SA IF',
                'shortname_vu' => 'IF',
                'fullname' => 'VU SA Istorijos fakultetas',
                'type' => 'padalinys',
            ]
        );

        $subdomain = $this->controller->publicGetSubdomainForTenant($ifTenant);
        expect($subdomain)->toBe('if');

        $url = $this->controller->publicTenantRoute('home', ['lang' => 'lt'], $ifTenant);
        expect($url)->toContain('if.');
    });
});
