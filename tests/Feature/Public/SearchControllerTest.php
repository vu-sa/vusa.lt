<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

test('unified search page renders', function (): void {
    $this->get(route('search', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Search')
            ->where('initialQuery', '')
        );
});

test('unified search page receives the initial query from the q parameter', function (): void {
    $this->get(route('search', ['subdomain' => 'www', 'lang' => 'lt', 'q' => 'studentai']))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Search')
            ->where('initialQuery', 'studentai')
        );
});

test('unified search truncates long queries without redirecting', function (): void {
    $query = str_repeat('ą', 201);

    $this->get(route('search', ['subdomain' => 'www', 'lang' => 'lt', 'q' => $query]))
        ->assertOk()
        ->assertSessionHasNoErrors()
        ->assertInertia(fn (Assert $page) => $page
            ->where('initialQuery', str_repeat('ą', 200))
        );
});

test('unified search ignores an array query without redirecting', function (): void {
    $url = route('search', ['subdomain' => 'www', 'lang' => 'lt', 'q' => ['x']]);

    $this->withHeader('Referer', $url)->get($url)
        ->assertOk()
        ->assertSessionHasNoErrors()
        ->assertInertia(fn (Assert $page) => $page
            ->where('initialQuery', '')
        );
});
