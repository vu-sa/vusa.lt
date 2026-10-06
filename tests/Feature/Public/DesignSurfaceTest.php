<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * Public and admin pages share one stylesheet but not one palette: app.css redefines the design
 * tokens (canvas, ink, border, radius) under [data-surface="public"] / [data-surface="admin"], and
 * App\Support\DesignSurface (read by app.blade.php) is the only thing that turns either scope on.
 * If the attribute stops being emitted, the page silently falls back to the zinc canvas — a
 * regression with no error anywhere, which is why it is worth a test of its own.
 */
it('marks public pages with the public design surface', function (): void {
    $html = (string) view('app', ['page' => ['component' => 'Public/HomePage', 'props' => []]]);

    expect($html)
        ->toContain('data-surface="public"')
        ->toContain('bg-background text-foreground')
        ->toContain('font-public');
});

it('marks every admin page with the admin design surface, signed in or not', function (): void {
    $html = (string) view('app', ['page' => ['component' => 'Admin/Dashboard/ShowSvetaine', 'props' => []]]);

    expect($html)
        ->toContain('data-surface="admin"')
        ->toContain('bg-background text-foreground')
        ->toContain('font-public')
        ->not->toContain('bg-zinc-50');
});

it('leaves the surface off when the component is unknown', function (): void {
    $html = (string) view('app', ['page' => ['props' => []]]);

    expect($html)->not->toContain('data-surface');
});
