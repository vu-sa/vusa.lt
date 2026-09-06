<?php

/**
 * The public site and the admin interface share one stylesheet but not one palette: app.css
 * redefines the design tokens (canvas, ink, border, radius) under [data-surface="public"], and
 * app.blade.php is the only thing that turns that scope on. If the attribute stops being
 * emitted the public site silently falls back to the admin palette — a whole-site regression
 * with no error anywhere, which is why it is worth a test of its own.
 */
it('marks public pages with the public design surface', function (): void {
    $html = (string) view('app', ['page' => ['component' => 'Public/HomePage', 'props' => []]]);

    expect($html)
        ->toContain('data-surface="public"')
        ->toContain('bg-background text-foreground')
        ->toContain('font-public');
});

it('leaves admin pages on the default surface', function (): void {
    $html = (string) view('app', ['page' => ['component' => 'Admin/Dashboard/ShowSvetaine', 'props' => []]]);

    expect($html)
        ->not->toContain('data-surface')
        ->toContain('font-sans')
        ->toContain('bg-zinc-50 dark:bg-zinc-900');
});

it('leaves the surface off when the component is unknown', function (): void {
    $html = (string) view('app', ['page' => ['props' => []]]);

    expect($html)->not->toContain('data-surface');
});
