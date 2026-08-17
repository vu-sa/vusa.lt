<?php

/**
 * Localized public URLs: each page has one URL per language, and the slug follows the
 * language prefix rather than being decoration on top of it.
 *
 * Before this, `/en/dokumentai` and `/en/documents` both rendered the English documents page,
 * so search engines saw two URLs for one page and the language toggle could leave a
 * Lithuanian slug under `/en`.
 */

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('a page is served under its own language slug', function (string $url): void {
    $this->get($url)->assertOk();
})->with([
    'lt documents' => 'https://www.vusa.test/lt/dokumentai',
    'en documents' => 'https://www.vusa.test/en/documents',
    'lt search' => 'https://www.vusa.test/lt/paieska',
    'en search' => 'https://www.vusa.test/en/search',
    'lt contacts' => 'https://www.vusa.test/lt/kontaktai',
    'en contacts' => 'https://www.vusa.test/en/contacts',
    'lt student representatives' => 'https://www.vusa.test/lt/kontaktai/studentu-atstovai',
    'en student representatives' => 'https://www.vusa.test/en/contacts/student-representatives',
    'lt meetings' => 'https://www.vusa.test/lt/posedziai',
    'en meetings' => 'https://www.vusa.test/en/meetings',
    'lt news archive' => 'https://www.vusa.test/lt/naujienos',
    'en news archive' => 'https://www.vusa.test/en/news',
]);

test('the other language slug is permanently redirected to the current one', function (string $wrong, string $right): void {
    $this->get($wrong)
        ->assertStatus(301)
        ->assertRedirect($right);
})->with([
    'lt slug under /en' => ['https://www.vusa.test/en/dokumentai', 'https://www.vusa.test/en/documents'],
    'en slug under /lt' => ['https://www.vusa.test/lt/documents', 'https://www.vusa.test/lt/dokumentai'],
    'lt contacts under /en' => ['https://www.vusa.test/en/kontaktai', 'https://www.vusa.test/en/contacts'],
    'nested lt slug under /en' => [
        'https://www.vusa.test/en/kontaktai/studentu-atstovai',
        'https://www.vusa.test/en/contacts/student-representatives',
    ],
]);

test('the redirect keeps the query string', function (): void {
    $this->get('https://www.vusa.test/en/dokumentai?search=statutas')
        ->assertStatus(301)
        ->assertRedirect('https://www.vusa.test/en/documents?search=statutas');
});

test('an Inertia visit gets a client-side redirect instead of a raw 301', function (): void {
    // A 301 followed by fetch() trips origin checks in WebKit in-app browsers, so Inertia
    // requests are answered with 409 + X-Inertia-Location (see SetLocale::redirectTo()).
    $this->get('https://www.vusa.test/en/dokumentai', ['X-Inertia' => 'true'])
        ->assertStatus(409)
        ->assertHeader('X-Inertia-Location', 'https://www.vusa.test/en/documents');
});

test('route() produces the slug of the language being rendered', function (): void {
    $this->get('https://www.vusa.test/en/documents')->assertOk();

    // The middleware set the English defaults for the rest of the request.
    expect(route('documents', ['lang' => 'en']))
        ->toBe('https://www.vusa.test/en/documents');
});
