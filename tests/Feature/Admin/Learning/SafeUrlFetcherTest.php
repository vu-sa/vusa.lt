<?php

use App\Services\Learning\HostResolver;
use App\Services\Learning\SafeUrlFetcher;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Http::preventStrayRequests();
    $this->app->instance(HostResolver::class, new class extends HostResolver
    {
        public function resolve(string $host): array
        {
            return match ($host) {
                'private.test' => ['10.0.0.5'],
                'mixed.test' => ['93.184.216.34', 'fc00::1'],
                'empty.test' => [],
                default => ['93.184.216.34'],
            };
        }
    });
});

test('unsafe URLs are refused without sending a request', function (string $url): void {
    Http::fake(['https://public.test/*' => Http::response('ok')]);

    $result = app(SafeUrlFetcher::class)->get($url, 100);

    expect($result->status)->toBe(0)
        ->and($result->reason)->not->toBeNull();
    Http::assertNothingSent();
})->with([
    'file:///etc/passwd', 'ftp://public.test/file', 'https://public.test:8080/',
    'http://10.0.0.5/', 'http://127.0.0.1/', 'http://169.254.169.254/',
    'http://[::1]/', 'http://[fe80::1]/', 'http://[fc00::1]/', 'http://0.0.0.1/',
    'http://[::ffff:127.0.0.1]/', 'http://[::ffff:a00:5]/', 'http://[::ffff:169.254.169.254]/',
    'https://private.test/', 'https://mixed.test/', 'https://empty.test/',
]);

test('a redirect cannot reach a private host', function (): void {
    Http::fake(['https://public.test/start' => Http::response('', 302, ['Location' => 'http://private.test/secret'])]);

    $result = app(SafeUrlFetcher::class)->get('https://public.test/start', 100);

    expect($result->status)->toBe(0);
    Http::assertSentCount(1);
});

test('three relative redirects are followed but a fourth is refused', function (int $redirects, int $status): void {
    Http::fake(['https://public.test/*' => function ($request) use ($redirects) {
        $page = (int) basename($request->url());

        return $page < $redirects ? Http::response('', 302, ['Location' => (string) ($page + 1)]) : Http::response('ok');
    }]);

    $result = app(SafeUrlFetcher::class)->get('https://public.test/0', 100);

    expect($result->status)->toBe($status);
    Http::assertSentCount(4);
})->with([[3, 200], [4, 0]]);

test('an oversized body fails instead of returning partial content', function (): void {
    Http::fake(['https://public.test/page' => Http::response(str_repeat('a', 101))]);

    $result = app(SafeUrlFetcher::class)->get('https://public.test/page', 100);

    expect($result->status)->toBe(0)->and($result->body)->toBeNull();
});

test('a public host succeeds with normalized headers and a pinned transport', function (): void {
    $options = [];
    Http::fake(['https://public.test/page' => function ($request, $requestOptions) use (&$options) {
        $options = $requestOptions;

        return Http::response('hello', 200, ['Content-Type' => 'Text/HTML; charset=utf-8', 'ETag' => 'v1', 'Last-Modified' => 'yesterday']);
    }]);

    $result = app(SafeUrlFetcher::class)->get('https://public.test/page', 100);

    expect($result->status)->toBe(200)->and($result->body)->toBe('hello')
        ->and($result->contentType)->toBe('text/html')->and($result->version)->toBe('v1')
        ->and($options['curl'][CURLOPT_RESOLVE])->toBe(['public.test:443:93.184.216.34'])
        ->and($options['allow_redirects'])->toBeFalse()->and($options['proxy'])->toBe('');
});

test('network exceptions become a failure result', function (): void {
    Http::fake(['https://public.test/page' => Http::failedConnection()]);

    $result = app(SafeUrlFetcher::class)->head('https://public.test/page');

    expect($result->status)->toBe(0)->and($result->reason)->not->toBeNull();
});

test('the transport aborts downloads as soon as their byte cap is exceeded', function (): void {
    Http::fake(['https://public.test/page' => function ($request, $options) {
        $options['progress'](1000, 101, 0, 0);

        return Http::response('should not finish');
    }]);

    $result = app(SafeUrlFetcher::class)->get('https://public.test/page', 100);

    expect($result->status)->toBe(0)->and($result->body)->toBeNull()->and($result->reason)->toContain('byte cap');
});

test('a public IP literal skips DNS resolution', function (): void {
    $resolver = Mockery::mock(HostResolver::class);
    $resolver->shouldNotReceive('resolve');
    $this->app->instance(HostResolver::class, $resolver);
    Http::fake(['https://93.184.216.34/page' => Http::response('ok')]);

    $result = app(SafeUrlFetcher::class)->get('https://93.184.216.34/page', 2);

    expect($result->status)->toBe(200)->and($result->body)->toBe('ok');
});

test('a public IPv6 address is pinned without changing the hostname', function (): void {
    $resolver = Mockery::mock(HostResolver::class);
    $resolver->shouldReceive('resolve')->with('ipv6.test')->once()->andReturn(['2606:4700:4700::1111']);
    $this->app->instance(HostResolver::class, $resolver);
    $pin = [];
    Http::fake(['https://ipv6.test/page' => function ($request, $options) use (&$pin) {
        $pin = $options['curl'][CURLOPT_RESOLVE];

        return Http::response('ok');
    }]);

    $result = app(SafeUrlFetcher::class)->get('https://ipv6.test/page', 2);

    expect($result->status)->toBe(200)->and($pin)->toBe(['ipv6.test:443:[2606:4700:4700::1111]']);
});
