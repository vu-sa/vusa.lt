<?php

use App\Exceptions\Handler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpKernel\Exception\HttpException;

pest()->use(RefreshDatabase::class);

/**
 * Always leave maintenance mode, even when an expectation above fails — a leftover
 * storage/framework/down file would break every subsequent test and local dev.
 */
afterEach(function (): void {
    Artisan::call('up');
});

it('shows the maintenance page instead of a bare 503 while the site is down', function (): void {
    Artisan::call('down');

    $this->get('/')
        ->assertStatus(503)
        ->assertSee('Tinklalapis atnaujinamas')
        ->assertSee('Šiuo metu atliekami techninės priežiūros darbai. Netrukus grįšime!')
        ->assertDontSee('503');
});

it('shows the English copy alongside the Lithuanian one', function (): void {
    Artisan::call('down');

    $this->get('/')
        ->assertSee('Site under maintenance')
        ->assertSee('We are performing scheduled maintenance and will be back shortly.');
});

it('keeps the Retry-After header on the maintenance page', function (): void {
    Artisan::call('down', ['--retry' => 60]);

    $this->get('/')->assertHeader('Retry-After', '60');
});

it('still answers API clients with JSON while the site is down', function (): void {
    Artisan::call('down');

    $response = $this->getJson('/');

    $response->assertStatus(503);
    expect($response->headers->get('Content-Type'))->toContain('json');
});

it('renders standalone, without a Vite stylesheet that would 404 mid-deploy', function (): void {
    // `down --render` bakes this view into storage/framework/maintenance.php, which is served
    // while deployment:deploy-assets swaps public/build — so it must not link a hashed asset.
    $html = view('errors.maintenance')->render();

    expect($html)->toContain('<style>')
        ->and($html)->not->toContain('/build/')
        ->and($html)->toContain('Tinklalapis atnaujinamas')
        ->and($html)->toContain('Site under maintenance');
});

it('leaves a genuine 503 on the standard error page', function (): void {
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

it('leaves other error pages untouched', function (): void {
    $this->get('/lt/this-page-does-not-exist')
        ->assertStatus(404)
        ->assertSee('404')
        ->assertSee('Puslapis nerastas');
});

it('ships a standalone maintenance fallback that matches the rendered page', function (): void {
    // deployment/maintenance.php is scp'd straight to storage/framework/maintenance.php by
    // the deploy workflow, before vendor/ is even swapped in — see .github/workflows/deploy.yml
    // and app/Console/Commands/DeploymentDeployAssets.php. It cannot depend on Composer or the
    // framework, so it can't be exercised through the test kernel (which never runs
    // public/index.php's pre-autoloader require). Only its source is asserted here: that it
    // sets the same status/headers artisan's `down --render` would, and mirrors the same
    // dependency-free copy as resources/views/errors/maintenance.blade.php so visitors see one
    // consistent page throughout a deploy, whichever fallback happens to be serving it.
    $source = file_get_contents(base_path('deployment/maintenance.php'));

    expect($source)->toContain('http_response_code(503)')
        ->and($source)->toContain("header('Retry-After: 60')")
        ->and($source)->toContain('exit;')
        ->and($source)->not->toContain('/build/')
        ->and($source)->toContain('Tinklalapis atnaujinamas')
        ->and($source)->toContain('Šiuo metu atliekami techninės priežiūros darbai. Netrukus grįšime!')
        ->and($source)->toContain('Site under maintenance')
        ->and($source)->toContain('We are performing scheduled maintenance and will be back shortly.');
});
