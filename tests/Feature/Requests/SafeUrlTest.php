<?php

use App\Http\Requests;
use App\Http\Requests\Api\Admin\ContentEditorDraftRequest;
use App\Models\Goal;
use App\Models\Institution;
use App\Models\Page;
use App\Models\Tenant;
use App\Models\User;
use App\Rules\SafeUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('accepts supported schemes and relative references', function (string $url): void {
    expect(Validator::make(['url' => $url], ['url' => new SafeUrl])->passes())->toBeTrue();
})->with([
    'https://example.com/path', 'HTTP://example.com', '/lt/kontaktai', '#anchor',
    'kontaktai', '../kontaktai', '?page=2', '//example.com/path',
    'mailto:info@example.com', 'tel:+37012345678', " \thttps://example.com\r\n",
]);

test('rejects unsupported schemes and non-string values', function (mixed $url): void {
    expect(Validator::make(['url' => $url], ['url' => new SafeUrl])->fails())->toBeTrue();
})->with([
    'javascript:void(0)', 'data:text/plain,hello', 'vbscript:MsgBox(1)',
    " \x00JaVa\tsCrIpT:void(0)\r\n", "java\nscript:void(0)", 'ftp://example.com',
    ['url' => ['https://example.com']], 123,
]);

dataset('stored URL request fields', [
    [Requests\UpdateUserSettingsRequest::class, 'facebook_url'],
    [Requests\StoreUserRequest::class, 'facebook_url'],
    [Requests\UpdateUserRequest::class, 'facebook_url'],
    [Requests\StoreNavigationRequest::class, 'url'],
    [Requests\UpdateNavigationRequest::class, 'url'],
    [Requests\StoreBannerRequest::class, 'link_url'],
    [Requests\UpdateBannerRequest::class, 'link_url'],
    [Requests\StoreQuickLinkRequest::class, 'link'],
    [Requests\UpdateQuickLinkRequest::class, 'link'],
    [Requests\StoreInstitutionRequest::class, 'website'],
    [Requests\StoreInstitutionRequest::class, 'facebook_url'],
    [Requests\UpdateInstitutionRequest::class, 'instagram_url'],
    [Requests\StoreCalendarRequest::class, 'cto_url.lt'],
    [Requests\UpdateCalendarRequest::class, 'cto_url.en'],
    [Requests\StoreCalendarRequest::class, 'facebook_url'],
    [Requests\StoreSupportRequestRequest::class, 'context_url'],
    [Requests\UpdateSupportRequestRequest::class, 'context_url'],
    [Requests\UpdateUIPreferencesRequest::class, 'pinned_pages.*.url'],
    [Requests\Api\Admin\TrackRecentPageRequest::class, 'url'],
    [Requests\SendFeedbackRequest::class, 'href'],
    [Requests\Goals\StepRequest::class, 'url'],
]);

test('validates stored URL fields before accepting them', function (string $requestClass, string $field): void {
    $request = new $requestClass;
    $request->setUserResolver(fn () => $this->user);
    $route = new Route('PATCH', '/', fn () => null);
    $route->bind($request);
    $route->setParameter('user', $this->user);
    $route->setParameter('goal', new Goal);
    $route->setParameter('institution', Institution::factory()->make(['id' => '01J00000000000000000000000']));
    $request->setRouteResolver(fn () => $route);
    $rules = $request->rules()[$field];

    foreach (['javascript:void(0)' => false, 'https://example.com' => true, '/lt/kontaktai' => true] as $value => $accepted) {
        $data = [];
        Arr::set($data, str_replace('*', '0', $field), $value);

        $expected = $requestClass === Requests\Goals\StepRequest::class && str_starts_with($value, '/') ? false : $accepted;

        expect(Validator::make($data, [$field => $rules])->passes())->toBe($expected);
    }
})->with('stored URL request fields');

test('keeps the saved profile link when its replacement is refused', function (): void {
    $this->user->update(['facebook_url' => 'https://example.com/profile']);

    $this->patch(route('profile.update'), ['facebook_url' => 'javascript:void(0)'])
        ->assertSessionHasErrors('facebook_url');

    expect($this->user->fresh()->facebook_url)->toBe('https://example.com/profile');
});

dataset('content URL paths', [
    'buttons.0.link', '0.buttons.0.link', '0.columns.0.content.value.href',
    'links.0.url', 'url',
    'content.0.marks.0.attrs.href',
]);

test('validates nested content URLs in pages news homepages and drafts', function (string $path): void {
    foreach ([Requests\StorePageRequest::class, Requests\UpdatePageRequest::class, Requests\NewsRequest::class, Requests\UpdateContentRequest::class, ContentEditorDraftRequest::class] as $requestClass) {
        $prefix = match ($requestClass) {
            Requests\UpdateContentRequest::class => 'parts',
            ContentEditorDraftRequest::class => 'snapshot.content.parts',
            default => 'content.parts',
        };

        foreach (['javascript:void(0)' => false, 'https://example.com' => true, '#details' => true] as $url => $accepted) {
            $data = ['revision' => 1];
            Arr::set($data, $prefix.'.0', ['type' => 'hero', 'json_content' => []]);
            $field = $prefix.'.0.json_content.'.$path;
            Arr::set($data, $field, $url);
            $request = $requestClass::create('/', 'PUT', $data);
            $request->setUserResolver(fn () => $this->user);
            $route = new Route('PUT', '/', fn () => null);
            $route->bind($request);
            $route->setParameter('page', Page::factory()->make(['id' => 1, 'tenant_id' => Tenant::query()->value('id'), 'lang' => 'lt']));
            $request->setRouteResolver(fn () => $route);
            $rules = array_filter($request->rules(), fn ($key) => str_starts_with($key, $prefix.'.'), ARRAY_FILTER_USE_KEY);

            expect(Validator::make($data, $rules)->errors()->has($field))->toBe(! $accepted);
        }
    }
})->with('content URL paths');

test('saves supported profile links', function (string $url): void {
    $this->patch(route('profile.update'), ['facebook_url' => $url])->assertSessionHasNoErrors();

    expect($this->user->fresh()->facebook_url)->toBe($url);
})->with(['https://example.com/profile', '/lt/kontaktai']);

test('uses the existing URL validation message', function (): void {
    $validator = Validator::make(['url' => 'javascript:void(0)'], ['url' => new SafeUrl]);

    expect($validator->errors()->first('url'))->toBe(__('validation.url', ['attribute' => 'url']));
});
