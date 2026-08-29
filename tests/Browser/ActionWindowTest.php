<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

/**
 * The one part of the action window no Vitest run can reach.
 *
 * Its screens are code-split chunks resolved at runtime and its shell picks between a
 * Drawer and a Dialog from a live media query — jsdom models neither, so the component
 * tests stub the presentation and assert the wiring instead. This proves the real bundle
 * actually opens, loads a screen, and offers only what the user may do.
 */
beforeEach(function (): void {
    // The seeded role, not a hand-built one: what the window offers is decided by real
    // permissions, so the test should be wrong if that grant ever changes.
    $this->representative = makeUser(Tenant::query()->first());
    $this->representative->duties()->first()->assignRole('Student Representative');
});

/** Both the sidebar and the dashboard hero carry a trigger; this is the sidebar's. */
const SIDEBAR_TRIGGER = '[data-sidebar="sidebar"] [data-testid="action-window-trigger"]';

/** The visible title of every choice on the current screen. */
function actionWindowChoices($page): array
{
    return $page->script(
        '[...document.querySelectorAll("[data-slot=action-choice-button]")]'
        .'.map(button => button.innerText.split("\n")[0])'
    );
}

it('opens from the sidebar and offers only the actions the user may take', function (): void {
    $page = loginAsAdmin($this->representative);

    waitForInertiaRender($page, SIDEBAR_TRIGGER);
    $page->click(SIDEBAR_TRIGGER);

    waitForInertiaRender($page, '[data-slot="action-window-screen"]');

    // No coordinator persona: a representative can manage neither duties nor settings.
    expect(actionWindowChoices($page))->toBe([
        'Kaip studentų atstovas',
        'Kaip VU SA narys',
    ]);

    $page->click('[data-slot="action-choice-button"]:has-text("Kaip studentų atstovas")');
    waitForInertiaRender($page, '[data-slot="action-choice-button"]:has-text("Pranešti apie posėdį")');

    expect(actionWindowChoices($page))->toBe([
        'Pranešti apie posėdį',
        'Posėdžio kurį laiką nebus',
        'Papildyti posėdį',
        'Pranešti apie problemą',
    ]);
});

it('walks from the institution to the meeting type', function (): void {
    $institution = $this->representative->duties()->first()->institution;

    $page = loginAsAdmin($this->representative);

    waitForInertiaRender($page, SIDEBAR_TRIGGER);
    $page->click(SIDEBAR_TRIGGER);
    waitForInertiaRender($page, '[data-slot="action-window-screen"]');

    $page->click('[data-slot="action-choice-button"]:has-text("Kaip studentų atstovas")');
    waitForInertiaRender($page, '[data-slot="action-choice-button"]:has-text("Pranešti apie posėdį")');
    $page->click('[data-slot="action-choice-button"]:has-text("Pranešti apie posėdį")');

    // The picker is fed by the action-window API, so this also proves the endpoint
    // answers with the caller's own institutions.
    waitForInertiaRender($page, sprintf('[data-slot="action-choice-button"]:has-text("%s")', $institution->name));
    $page->click(sprintf('[data-slot="action-choice-button"]:has-text("%s")', $institution->name));

    waitForInertiaRender($page, '[data-slot="action-choice-button"]:has-text("Gyvas susitikimas")');

    expect(actionWindowChoices($page))->toBe([
        'Gyvas susitikimas',
        'Nuotolinis susitikimas',
        'Elektroninis posėdis (el. laišku)',
        'Kita',
    ]);
});
