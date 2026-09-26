<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->outsider = makeUser($this->tenant);
});

describe('403 explanation (U8)', function (): void {
    test('a direct visit to a forbidden admin page renders the explanation with status 403', function (): void {
        asUser($this->outsider)->get(route('news.index'))
            ->assertForbidden()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/AccessDenied')
                ->where('permission', 'news.read')
                ->where('action', __('forbidden.actions.read'))
                ->where('resource', trans_choice('entities.news.model', 5)));
    });

    test('an Inertia visit keeps the redirect with an error flash instead of the page', function (): void {
        asUser($this->outsider)->get(route('news.index'), [
            'X-Inertia' => 'true',
            'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        ])
            ->assertRedirect()
            ->assertSessionHas('error');
    });
});
