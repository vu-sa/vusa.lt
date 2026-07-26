<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.umami.script_url', 'https://analytics.example.test/script.js');
    config()->set('services.umami.website_id', 'test-website-id');
});

test('public pages render the umami tracker when it is configured', function () {
    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertOk()
        ->assertSee('https://analytics.example.test/script.js', false)
        ->assertSee('data-website-id="test-website-id"', false);
});

test('the tracker is omitted when no website id is configured', function () {
    config()->set('services.umami.website_id', null);

    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertOk()
        ->assertDontSee('https://analytics.example.test/script.js', false);
});

test('admin pages are never tracked', function () {
    asUser(makeAdminUser())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('https://analytics.example.test/script.js', false);
});
