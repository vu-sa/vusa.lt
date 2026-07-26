<?php

use App\Exceptions\Handler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpKernel\Exception\HttpException;

uses(RefreshDatabase::class);

/**
 * Always leave maintenance mode, even when an expectation above fails — a leftover
 * storage/framework/down file would break every subsequent test and local dev.
 */
afterEach(function () {
    Artisan::call('up');
});

it('shows the maintenance page instead of a bare 503 while the site is down', function () {
    Artisan::call('down');

    $this->get('/')
        ->assertStatus(503)
        ->assertSee('Tinklalapis atnaujinamas')
        ->assertSee('Šiuo metu atliekami techninės priežiūros darbai. Netrukus grįšime!')
        ->assertDontSee('503');
});

it('shows the English copy alongside the Lithuanian one', function () {
    Artisan::call('down');

    $this->get('/')
        ->assertSee('Site under maintenance')
        ->assertSee('We are performing scheduled maintenance and will be back shortly.');
});

it('keeps the Retry-After header on the maintenance page', function () {
    Artisan::call('down', ['--retry' => 60]);

    $this->get('/')->assertHeader('Retry-After', '60');
});

it('still answers API clients with JSON while the site is down', function () {
    Artisan::call('down');

    $response = $this->getJson('/');

    $response->assertStatus(503);
    expect($response->headers->get('Content-Type'))->toContain('json');
});

it('renders standalone, without a Vite stylesheet that would 404 mid-deploy', function () {
    // `down --render` bakes this view into storage/framework/maintenance.php, which is served
    // while deployment:deploy-assets swaps public/build — so it must not link a hashed asset.
    $html = view('errors.maintenance')->render();

    expect($html)->toContain('<style>')
        ->and($html)->not->toContain('/build/')
        ->and($html)->toContain('Tinklalapis atnaujinamas')
        ->and($html)->toContain('Site under maintenance');
});

it('leaves a genuine 503 on the standard error page', function () {
    // Rendered through the handler directly: a public catch-all permalink route would
    // swallow any ad-hoc test route before it could throw.
    $response = app(Handler::class)->render(
        Request::create('/lt/something'),
        new HttpException(503)
    );

    expect($response->getStatusCode())->toBe(503)
        ->and($response->getContent())->toContain('503')
        ->and($response->getContent())->toContain('Service Unavailable')
        ->and($response->getContent())->not->toContain('Tinklalapis atnaujinamas');
});

it('leaves other error pages untouched', function () {
    $this->get('/lt/this-page-does-not-exist')
        ->assertStatus(404)
        ->assertSee('404')
        ->assertSee('Puslapis nerastas');
});
