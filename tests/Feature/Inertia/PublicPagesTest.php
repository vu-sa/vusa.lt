<?php

namespace Tests\Feature\Inertia;

use App\Models\Institution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\CoversNothing;
// use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

#[CoversNothing]
class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    // check if public inertia response returns default props
    public function test_home_page_gets_default_public_props(): void
    {
        $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/HomePage')
                ->has('app', fn (Assert $page) => $page
                    ->has('env')
                    ->has('locale')
                    ->has('path')
                    ->has('url')
                )
                ->has('mainNavigation')
                ->has('padaliniai')
                ->has('padalinys', fn (Assert $page) => $page
                    ->has('alias')
                    ->has('banners')
                    ->has('id')
                    ->has('shortname')
                    ->has('type')
                    ->has('subdomain')
                    ->has('links')
                )
            );
    }

    // check if public inertia response doesn't return any auth

    public function test_no_auth_without_authentication(): void
    {
        $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/HomePage')
                ->where('auth', null)
            );
    }

    public function test_can_open_the_home_page(): void
    {
        $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/HomePage')
                ->has('news')
                ->has('calendar')
                ->has('upcomingEvents')
            );
    }

    public function test_can_open_news_archive(): void
    {
        $this->get(route('newsArchive', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/NewsArchive')
                ->has('news', fn (Assert $page) => $page
                    ->has('data')
                    ->etc()
                )
            );
    }

    public function test_can_open_news(): void
    {
        $news = \App\Models\News::query()->inRandomOrder()->first();

        $this->get(route('news', ['subdomain' => 'www', 'lang' => 'lt', 'news' => $news->permalink, 'newsString' => 'naujiena']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/NewsPage')
                ->has('article')
            );
    }

    public function test_can_open_student_representative_page(): void
    {
        $this->get(route('contacts.studentRepresentatives', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Contacts/ShowStudentReps')
                ->has('types')
            );
    }

    public function test_can_open_institution_page(): void
    {
        $institution = Institution::query()->inRandomOrder()->first();

        $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Contacts/ContactInstitutionOrType')
                ->has('institution')
                ->has('contacts')
            );
    }

    public function test_can_open_representation_padaliniai_category(): void
    {
        $this->get(route('contacts.category', ['subdomain' => 'www', 'lang' => 'lt', 'type' => 'padaliniai']))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Public/Contacts/ShowContactCategory')
                ->has('institutions')
            );
    }
}
