<?php

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('admin pages send guests to the login page', function (string $routeName): void {
    $this->get(route($routeName))->assertRedirect(route('login'));
})->with(['dashboard', 'users.index', 'news.index', 'duties.index', 'institutions.index', 'forms.index', 'reservations.index']);

test('the public site is open to guests and the root redirects to a language', function (): void {
    $this->get('/lt')->assertOk();
    $this->get('/en')->assertOk();
    $this->get('/')->assertStatus(301);
});

test('a communication coordinator opens the dashboard and news', function (): void {
    $coordinator = makeUser(Tenant::query()->first());
    $coordinator->duties()->first()->assignRole('Komunikacijos koordinatorius');

    asUser($coordinator)->get(route('dashboard'))->assertOk();
    asUser($coordinator)->get(route('news.index'))->assertOk();
});
