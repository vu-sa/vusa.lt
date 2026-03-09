<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

beforeEach(function () {
    $this->middleware = new SetLocale;
});

test('sets locale from valid lang parameter', function () {
    $request = Request::create('/test?lang=en');

    $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect(app()->getLocale())->toBe('en');
    expect(session()->get('lang'))->toBe('en');
});

test('ignores invalid locale from lang parameter', function () {
    $originalLocale = app()->getLocale();
    $request = Request::create('/test?lang=invalid');

    $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect(app()->getLocale())->toBe($originalLocale);
    expect(session()->get('lang'))->toBeNull();
});

test('sanitizes malicious lang parameter', function () {
    $originalLocale = app()->getLocale();
    $maliciousInput = "'nvOpzp; AND 1=1 OR (<'\">iKO)),";
    $request = Request::create('/test?lang='.urlencode($maliciousInput));

    $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect(app()->getLocale())->toBe($originalLocale);
    expect(session()->get('lang'))->toBeNull();
});

test('handles sql injection attempts', function () {
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

        $this->middleware->handle($request, function ($req) {
            return new Response('test');
        });

        expect(app()->getLocale())->toBe($originalLocale);
        expect(session()->get('lang'))->toBeNull();
    }
});

test('handles xss attempts', function () {
    $originalLocale = app()->getLocale();
    $xssAttempts = [
        "<script>alert('xss')</script>",
        "javascript:alert('xss')",
        "<img src=x onerror=alert('xss')>",
        "';alert('xss');//",
    ];

    foreach ($xssAttempts as $xss) {
        $request = Request::create('/test?lang='.urlencode($xss));

        $this->middleware->handle($request, function ($req) {
            return new Response('test');
        });

        expect(app()->getLocale())->toBe($originalLocale);
        expect(session()->get('lang'))->toBeNull();
    }
});

test('handles non string lang parameter', function () {
    $originalLocale = app()->getLocale();
    $request = Request::create('/test');
    $request->merge(['lang' => ['array' => 'value']]);

    $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect(app()->getLocale())->toBe($originalLocale);
    expect(session()->get('lang'))->toBeNull();
});

test('handles extremely long lang parameter', function () {
    $originalLocale = app()->getLocale();
    $longString = str_repeat('a', 1000);
    $request = Request::create('/test?lang='.$longString);

    $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect(app()->getLocale())->toBe($originalLocale);
    expect(session()->get('lang'))->toBeNull();
});

test('uses locale from session when no parameter', function () {
    session()->put('lang', 'en');
    $request = Request::create('/test');

    $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect(app()->getLocale())->toBe('en');
});

test('ignores invalid locale from session', function () {
    $originalLocale = app()->getLocale();
    session()->put('lang', 'invalid');
    $request = Request::create('/test');

    $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect(app()->getLocale())->toBe($originalLocale);
});

test('uses default locale when no valid locale available', function () {
    $request = Request::create('/test');

    $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect(app()->getLocale())->toBe(config('app.locale'));
});

test('bypasses locale processing for admin routes', function () {
    $request = Request::create('/mano/dashboard');

    $response = $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getContent())->toBe('test');
});

test('bypasses locale processing for auth routes', function () {
    $bypassRoutes = ['mano', 'auth', 'feedback', 'login', 'telescope', '_impersonate', 'feed', 'livewire', 'registration', 'broadcasting'];

    foreach ($bypassRoutes as $route) {
        $request = Request::create("/{$route}/test");

        $response = $this->middleware->handle($request, function ($req) {
            return new Response('test');
        });

        expect($response)->toBeInstanceOf(Response::class);
        expect($response->getContent())->toBe('test');
    }
});

test('redirects to locale when no locale segment', function () {
    app()->setLocale('lt');
    $request = Request::create('/news/test');

    $response = $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect($response->getStatusCode())->toBe(301);
    expect($response->headers->get('Location'))->toEndWith('/lt/news/test');
});

test('allows valid locale segments', function () {
    $request = Request::create('/en/news/test');

    $response = $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect($response)->toBeInstanceOf(Response::class);
    expect($response->getContent())->toBe('test');
});

test('redirects invalid locale segments', function () {
    app()->setLocale('lt');
    $request = Request::create('/invalid/news/test');

    $response = $this->middleware->handle($request, function ($req) {
        return new Response('test');
    });

    expect($response->getStatusCode())->toBe(301);
    expect($response->headers->get('Location'))->toEndWith('/lt/invalid/news/test');
});

test('sanitize locale removes special characters', function () {
    $reflection = new \ReflectionClass($this->middleware);
    $method = $reflection->getMethod('sanitizeLocale');
    $method->setAccessible(true);

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

test('sanitize locale handles non string input', function () {
    $reflection = new \ReflectionClass($this->middleware);
    $method = $reflection->getMethod('sanitizeLocale');
    $method->setAccessible(true);

    $nonStringInputs = [null, 123, [], (object) [], true, false];

    foreach ($nonStringInputs as $input) {
        $result = $method->invoke($this->middleware, $input);
        expect($result)->toBeNull('Failed for input type: '.gettype($input));
    }
});

test('is valid locale only accepts configured locales', function () {
    $reflection = new \ReflectionClass($this->middleware);
    $method = $reflection->getMethod('isValidLocale');
    $method->setAccessible(true);

    expect($method->invoke($this->middleware, 'en'))->toBeTrue();
    expect($method->invoke($this->middleware, 'lt'))->toBeTrue();
    expect($method->invoke($this->middleware, 'fr'))->toBeFalse();
    expect($method->invoke($this->middleware, 'invalid'))->toBeFalse();
    expect($method->invoke($this->middleware, ''))->toBeFalse();
    expect($method->invoke($this->middleware, null))->toBeFalse();
});
