<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Vite;

pest()->use(RefreshDatabase::class);

// The helper only acts on the built manifest; a running dev server would skip it entirely.
beforeEach(fn () => app(Vite::class)->useHotFile(storage_path('framework/testing/vite-hot-disabled')));

function renderAppView(string $component): string
{
    return (string) view('app', ['page' => ['component' => $component, 'props' => []]]);
}

it('links the page chunk CSS in the head without adding entry scripts', function (string $component): void {
    $html = renderAppView($component);
    $appStylesheet = app(Vite::class)->asset('resources/css/app.css');

    preg_match_all('/<link rel="stylesheet" href="([^"]+\/build\/assets\/[^"]+)"/', $html, $stylesheets);

    expect($stylesheets[1])->toContain($appStylesheet)
        ->and(array_count_values($stylesheets[1])[$appStylesheet])->toBe(1)
        ->and(count($stylesheets[1]))->toBeGreaterThan(1);

    // Extra surface/page entry scripts raced app.ts's dynamic import and left admin unmounted.
    expect(substr_count($html, '<script type="module"'))->toBe(1);
})->with(['Public/HomePage', 'Admin/ShowAdminHome']);

it('still renders the page when the component is missing from the manifest', function (): void {
    $html = renderAppView('Public/DoesNotExist');

    expect($html)->toContain(app(Vite::class)->asset('resources/css/app.css'));
});
