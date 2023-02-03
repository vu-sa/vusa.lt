<?php

use Inertia\Testing\AssertableInertia as Assert;

// check if public inertia response returns default props

it('gets default public props', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->has('alias')
            ->has('app', fn (Assert $page) => $page
                ->has('env')
                ->has('locale')
                ->has('url')
            )
            ->has('mainNavigation')
            ->has('padaliniai')
        );
});

// check if public inertia response doesn't return any auth

it('doesn\'t return user immediately in public pages', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->where('auth', null)
        );
});

it('can see the home page', function () {
    $this->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->has('banners')
            ->has('news')
            ->has('calendar')
            ->has('mainPage')
            ->has('banners')
        );
});

it('can see the news page', function () {
    $this->get(route('news'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('article')
            ->has('otherLangNews')
        );
});