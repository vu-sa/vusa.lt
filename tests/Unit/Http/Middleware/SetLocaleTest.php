<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function (): void {
    $this->middleware = new SetLocale;
});

test('sets locale from valid lang parameter', function (): void {
    $request = Request::create('/test?lang=en');

    $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect(app()->getLocale())->toBe('en')
        ->and(session()->get('lang'))->toBe('en');
});

test('ignores invalid locale from lang parameter', function (): void {
    $originalLocale = app()->getLocale();
    $request = Request::create('/test?lang=invalid');

    $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect(app()->getLocale())->toBe($originalLocale)
        ->and(session()->get('lang'))->toBeNull();
});

test('sanitizes malicious lang parameter', function (): void {
    $originalLocale = app()->getLocale();
    $maliciousInput = "'nvOpzp; AND 1=1 OR (<'\">iKO)),";
    $request = Request::create('/test?lang='.urlencode($maliciousInput));

    $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect(app()->getLocale())->toBe($originalLocale)
        ->and(session()->get('lang'))->toBeNull();
});

test('handles sql injection attempts', function (): void {
    $originalLocale = app()->getLocale();
    $sqlInjections = [
        "'; DROP TABLE users; --",
        "1' OR '1'='1",
        "admin'--",
        "' OR 1=1#",
        "' UNION SELECT * FROM users--",
    ];

    foreach ($sqlInjections as $injection) {
        $request = Request::create('/test?lang='.urlencode($injection));

        $this->middleware->handle($request, fn ($req) => new Response('test'));

        expect(app()->getLocale())->toBe($originalLocale)
            ->and(session()->get('lang'))->toBeNull();
    }
});

test('handles xss attempts', function (): void {
    $originalLocale = app()->getLocale();
    $xssAttempts = [
        "<script>alert('xss')</script>",
        "javascript:alert('xss')",
        "<img src=x onerror=alert('xss')>",
        "';alert('xss');//",
    ];

    foreach ($xssAttempts as $xss) {
        $request = Request::create('/test?lang='.urlencode($xss));

        $this->middleware->handle($request, fn ($req) => new Response('test'));

        expect(app()->getLocale())->toBe($originalLocale)
            ->and(session()->get('lang'))->toBeNull();
    }
});

test('handles non string lang parameter', function (): void {
    $originalLocale = app()->getLocale();
    $request = Request::create('/test');
    $request->merge(['lang' => ['array' => 'value']]);

    $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect(app()->getLocale())->toBe($originalLocale)
        ->and(session()->get('lang'))->toBeNull();
});

test('handles extremely long lang parameter', function (): void {
    $originalLocale = app()->getLocale();
    $longString = str_repeat('a', 1000);
    $request = Request::create('/test?lang='.$longString);

    $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect(app()->getLocale())->toBe($originalLocale)
        ->and(session()->get('lang'))->toBeNull();
});

test('uses locale from session when no parameter', function (): void {
    session()->put('lang', 'en');
    $request = Request::create('/test');

    $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect(app()->getLocale())->toBe('en');
});

test('ignores invalid locale from session', function (): void {
    $originalLocale = app()->getLocale();
    session()->put('lang', 'invalid');
    $request = Request::create('/test');

    $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect(app()->getLocale())->toBe($originalLocale);
});

test('uses default locale when no valid locale available', function (): void {
    $request = Request::create('/test');

    $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect(app()->getLocale())->toBe(config('app.locale'));
});

test('bypasses locale processing for admin routes', function (): void {
    $request = Request::create('/mano/dashboard');

    $response = $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getContent())->toBe('test');
});

test('bypasses locale processing for auth routes', function (): void {
    $bypassRoutes = ['mano', 'auth', 'feedback', 'login', 'telescope', 'feed', 'livewire', 'registration', 'broadcasting'];

    foreach ($bypassRoutes as $route) {
        $request = Request::create("/{$route}/test");

        $response = $this->middleware->handle($request, fn ($req) => new Response('test'));

        expect($response)->toBeInstanceOf(Response::class)
            ->and($response->getContent())->toBe('test');
    }
});

test('redirects to locale when no locale segment', function (): void {
    app()->setLocale('lt');
    $request = Request::create('/news/test');

    $response = $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect($response->getStatusCode())->toBe(301)
        ->and($response->headers->get('Location'))->toEndWith('/lt/news/test');
});

test('allows valid locale segments', function (): void {
    $request = Request::create('/en/news/test');

    $response = $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect($response)->toBeInstanceOf(Response::class)
        ->and($response->getContent())->toBe('test');
});

test('redirects invalid locale segments', function (): void {
    app()->setLocale('lt');
    $request = Request::create('/invalid/news/test');

    $response = $this->middleware->handle($request, fn ($req) => new Response('test'));

    expect($response->getStatusCode())->toBe(301)
        ->and($response->headers->get('Location'))->toEndWith('/lt/invalid/news/test');
});

test('sanitize locale removes special characters', function (): void {
    $reflection = new ReflectionClass($this->middleware);
    $method = $reflection->getMethod('sanitizeLocale');

    $testCases = [
        'en123!@#' => 'en',
        'lt-LT' => 'ltLT',
        'en_US' => 'enUS',
        'fr.FR' => 'frFR',
        "'nvOpzp; AND 1=1 OR (<'\">iKO))," => null, // Should be null due to length limit
        '' => '',
        'a' => 'a',
        str_repeat('a', 15) => null, // Too long
        'en' => 'en',
        'lt' => 'lt',
    ];

    foreach ($testCases as $input => $expected) {
        $result = $method->invoke($this->middleware, $input);
        expect($result)->toBe($expected, "Failed for input: {$input}");
    }
});

test('sanitize locale handles non string input', function (): void {
    $reflection = new ReflectionClass($this->middleware);
    $method = $reflection->getMethod('sanitizeLocale');

    $nonStringInputs = [null, 123, [], (object) [], true, false];

    foreach ($nonStringInputs as $input) {
        $result = $method->invoke($this->middleware, $input);
        expect($result)->toBeNull('Failed for input type: '.gettype($input));
    }
});

test('is valid locale only accepts configured locales', function (): void {
    $reflection = new ReflectionClass($this->middleware);
    $method = $reflection->getMethod('isValidLocale');

    expect($method->invoke($this->middleware, 'en'))->toBeTrue()
        ->and($method->invoke($this->middleware, 'lt'))->toBeTrue()
        ->and($method->invoke($this->middleware, 'fr'))->toBeFalse()
        ->and($method->invoke($this->middleware, 'invalid'))->toBeFalse()
        ->and($method->invoke($this->middleware, ''))->toBeFalse()
        ->and($method->invoke($this->middleware, null))->toBeFalse();
});
