<?php

namespace Tests\Feature\Inertia;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
// use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    // check if public inertia response returns default props
    public function test_gets_default_public_props(): void {

        $this->get(route('home', ['padalinys' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/HomePage')
                ->has('alias')
                ->has('app', fn (Assert $page) => $page
                    ->has('env')
                    ->has('locale')
                    ->has('path')
                    ->has('url')
                )
                ->has('mainNavigation')
                ->has('padaliniai')
            );
    }

    // check if public inertia response doesn't return any auth

    public function test_does_not_return_user_immediately_in_public_pages(): void {
        $this->get(route('home', ['padalinys' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/HomePage')
                ->where('auth', null)
            );
    }

    public function test_can_see_the_home_page (): void {
        $this->get(route('home', ['padalinys' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/HomePage')
                ->has('banners')
                ->has('news')
                ->has('calendar')
                ->has('mainPage')
                ->has('banners')
            );
    }
}
