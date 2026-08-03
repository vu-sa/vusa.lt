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
