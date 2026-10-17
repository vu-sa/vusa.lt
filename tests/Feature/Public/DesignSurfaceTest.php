<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The palette is global, but the surface-specific rules (admin table numerals and progress bar,
 * public prose colour) only apply through the attribute, and nothing errors if it goes missing.
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
        ->toContain('font-public');
});

it('leaves the surface off but keeps the shared palette when the component is unknown', function (): void {
    $html = (string) view('app', ['page' => ['props' => []]]);

    expect($html)
        ->not->toContain('data-surface')
        ->toContain('bg-background text-foreground')
        ->not->toContain('bg-zinc-50');
});
