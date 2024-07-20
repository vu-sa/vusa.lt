<?php

use App\Models\Institution;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('home page gets default public props', function () {
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
            ->has('tenants')
            ->has('tenant', fn (Assert $page) => $page
                ->has('alias')
                ->has('banners')
                ->has('id')
                ->has('shortname')
                ->has('type')
                ->has('subdomain')
                ->has('links')
            )
        );
});

test('no auth without authentication', function () {
    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->where('auth', null)
        );
});

test('can open the home page', function () {
    $this->get(route('home', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/HomePage')
            ->has('news')
            ->has('calendar')
            ->has('upcomingEvents')
        );
});

test('can open news archive', function () {
    $this->get(route('newsArchive', ['subdomain' => 'www', 'lang' => 'lt', 'newsString' => 'naujienos']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsArchive')
            ->has('news', fn (Assert $page) => $page
                ->has('data')
                ->etc()
            )
        );
});

test('can open news', function () {
    $news = \App\Models\News::query()->inRandomOrder()->first();

    $this->get(route('news', ['subdomain' => 'www', 'lang' => 'lt', 'news' => $news->permalink, 'newsString' => 'naujiena']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/NewsPage')
            ->has('article')
        );
});

test('can open student representative page', function () {
    $this->get(route('contacts.studentRepresentatives', ['subdomain' => 'www', 'lang' => 'lt']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowStudentReps')
            ->has('types')
        );
});

test('can open institution page', function () {
    $institution = Institution::query()->inRandomOrder()->first();

    $this->get(route('contacts.institution', ['subdomain' => 'www', 'lang' => 'lt', 'institution' => $institution->id]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ContactInstitutionOrType')
            ->has('institution')
            ->has('contacts')
        );
});

test('can open representation padaliniai category', function () {
    $this->get(route('contacts.category', ['subdomain' => 'www', 'lang' => 'lt', 'type' => 'padaliniai']))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Public/Contacts/ShowContactCategory')
            ->has('institutions')
        );
});

test('can leave feedback', function () {

    Mail::fake();

    // assert that route exists
    $response = $this->post(route('feedback.send'), [
        'feedback' => 'Test feedback',
        'href' => 'https://vusa.lt',
        'selectedText' => 'Test selected text',
    ]);

    $response->assertRedirect();

    // assert that mail was sent
    Mail::assertQueued(\App\Mail\FeedbackMail::class);
});
