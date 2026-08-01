<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('services.umami.script_url', 'https://analytics.example.test/script.js');
    config()->set('services.umami.website_id', 'test-website-id');
});

test('public pages render the umami tracker when it is configured', function (): void {
    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertOk()
        ->assertSee('https://analytics.example.test/script.js', false)
        ->assertSee('data-website-id="test-website-id"', false);
});

test('the tracker opts into web vitals collection', function (): void {
    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertOk()
        ->assertSee('data-performance="true"', false);
});

test('the tracker is omitted when no website id is configured', function (): void {
    config()->set('services.umami.website_id', null);

    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertOk()
        ->assertDontSee('https://analytics.example.test/script.js', false);
});

test('admin pages are never tracked', function (): void {
    asUser(makeAdminUser())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('https://analytics.example.test/script.js', false);
});
