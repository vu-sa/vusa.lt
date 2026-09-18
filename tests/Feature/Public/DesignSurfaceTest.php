<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The public site, and admin for a user who opted into the redesign's new shell, share one
 * stylesheet but not one palette with legacy admin: app.css redefines the design tokens (canvas,
 * ink, border, radius) under [data-surface="public"] / [data-surface="admin"], and
 * App\Support\DesignSurface (read by app.blade.php) is the only thing that turns either scope on.
 * If the attribute stops being emitted, the surface silently falls back to the legacy palette —
 * a regression with no error anywhere, which is why it is worth a test of its own.
 */
it('marks public pages with the public design surface', function (): void {
    $html = (string) view('app', ['page' => ['component' => 'Public/HomePage', 'props' => []]]);

    expect($html)
        ->toContain('data-surface="public"')
        ->toContain('bg-background text-foreground')
        ->toContain('font-public');
});

it('marks admin pages with the admin design surface when the user opted in', function (): void {
    $user = User::factory()->create();
    $user->setNewAdminShellEnabled(true);
    $this->actingAs($user);

    $html = (string) view('app', ['page' => ['component' => 'Admin/Dashboard/ShowSvetaine', 'props' => []]]);

    expect($html)
        ->toContain('data-surface="admin"')
        ->toContain('bg-background text-foreground')
        ->toContain('font-public');
});

it('leaves admin pages on the legacy surface for a user who has not opted in', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $html = (string) view('app', ['page' => ['component' => 'Admin/Dashboard/ShowSvetaine', 'props' => []]]);

    expect($html)
        ->not->toContain('data-surface')
        ->toContain('font-sans')
        ->toContain('bg-zinc-50 dark:bg-zinc-900');
});

it('leaves admin pages on the legacy surface for a guest', function (): void {
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
