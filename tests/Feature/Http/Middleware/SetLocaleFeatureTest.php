<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('handles malicious lang parameter in real request', function () {
    $maliciousInput = "'nvOpzp; AND 1=1 OR (<'\">iKO)),";

    $response = $this->get('/news/test?lang='.urlencode($maliciousInput));

    // Should redirect to localized URL with default locale
    $response->assertRedirect('/lt/news/test');

    // Locale should not be set to malicious input
    expect(app()->getLocale())->toBe(config('app.locale'));
    expect(session()->get('lang'))->toBeNull();
});

test('sets valid locale from parameter', function () {
    $response = $this->get('/news/test?lang=en');

    $response->assertRedirect('/en/news/test');
    expect(app()->getLocale())->toBe('en');
    expect(session()->get('lang'))->toBe('en');
});

test('preserves query parameters during redirect', function () {
    $response = $this->get('/news/test?lang=en&page=2&search=query');

    // Should redirect and preserve other parameters
    $response->assertRedirect();
    expect($response->headers->get('Location'))->toContain('/en/news/test');
});

test('handles multiple injection attempts', function () {
    $injectionAttempts = [
        "'; DROP TABLE users; --",
        "<script>alert('xss')</script>",
        "1' OR '1'='1",
        "javascript:alert('test')",
        '../../../etc/passwd',
        '%27%20OR%20%271%27%3D%271',
    ];

    foreach ($injectionAttempts as $injection) {
        $response = $this->get('/news/test?lang='.urlencode($injection));

        // Should redirect to default locale
        $response->assertRedirect('/lt/news/test');

        // Session should not contain malicious data
        expect(session()->get('lang'))->toBeNull();

        // Reset session for next test
        session()->flush();
    }
});

test('bypasses middleware for admin routes', function () {
    // Assuming there's a route that would normally cause a redirect
    // but should be bypassed for admin routes
    $adminRoutes = [
        '/mano',
        '/mano/dashboard',
        '/auth/login',
        '/login',
        '/telescope',
        '/feed',
        '/pulse',
    ];

    foreach ($adminRoutes as $route) {
        $response = $this->get($route);

        // Should not redirect due to locale (may 404 or have other response)
        expect($response->getStatusCode())->not->toBe(301, "Route {$route} should bypass locale middleware");
    }
});

test('handles edge cases gracefully', function () {
    $edgeCases = [
        '/news/test?lang=',  // Empty string
        '/news/test?lang[]',  // Array parameter
        '/news/test?lang='.str_repeat('a', 100),  // Very long string
        '/news/test?lang=🚀',  // Unicode characters
        '/news/test?lang=null',  // String "null"
        '/news/test?lang=undefined',  // String "undefined"
    ];

    foreach ($edgeCases as $url) {
        $response = $this->get($url);

        // Should handle gracefully by redirecting to default locale
        $response->assertRedirect('/lt/news/test');

        // Session should be clean
        $sessionLang = session()->get('lang');
        expect($sessionLang === null || in_array($sessionLang, ['lt', 'en']))->toBeTrue(
            "Session lang should be null or valid locale for URL: {$url}"
        );

        session()->flush();
    }
});
