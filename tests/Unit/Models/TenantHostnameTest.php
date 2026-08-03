<?php

use App\Models\Tenant;

test('the main tenant is served from the www subdomain', function (): void {
    $tenant = new Tenant(['alias' => 'vusa']);

    expect($tenant->subdomain())->toBe('www');
});

test('other tenants are served from their alias', function (): void {
    $tenant = new Tenant(['alias' => 'mif']);

    expect($tenant->subdomain())->toBe('mif');
});

test('the public hostname combines the subdomain with the app apex', function (): void {
    config()->set('app.url', 'https://www.vusa.lt');

    expect(new Tenant(['alias' => 'mif'])->publicHostname())->toBe('mif.vusa.lt')
        ->and(new Tenant(['alias' => 'vusa'])->publicHostname())->toBe('www.vusa.lt');
});

test('the public hostname follows the configured apex across environments', function (): void {
    config()->set('app.url', 'https://www.vusa.test');

    expect(new Tenant(['alias' => 'evaf'])->publicHostname())->toBe('evaf.vusa.test');
});
