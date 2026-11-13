<?php

namespace App\Services\Learning;

use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Psr7\UriResolver;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Uri;
use Psr\Http\Message\StreamInterface;

class SafeUrlFetcher
{
    private const int MAX_REDIRECTS = 3;

    private const array REDIRECT_STATUSES = [301, 302, 303, 307, 308];

    public function __construct(private readonly HostResolver $resolver) {}

    public function head(string $url): FetchResult
    {
        return $this->fetch($url, 'HEAD', 0);
    }

    public function get(string $url, int $maxBytes): FetchResult
    {
        return $this->fetch($url, 'GET', $maxBytes);
    }

    public static function resolveUrl(string $base, string $reference): string
    {
        return (string) UriResolver::resolve(new \GuzzleHttp\Psr7\Uri($base), new \GuzzleHttp\Psr7\Uri($reference));
    }

    private function fetch(string $url, string $method, int $maxBytes): FetchResult
    {
        try {
            for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
                $target = $this->target($url);
                if (is_string($target)) {
                    return new FetchResult($url, 0, reason: $target);
                }

                $outcome = $this->send($url, $method, $maxBytes, $target, $hop === self::MAX_REDIRECTS);
                if ($outcome instanceof FetchResult) {
                    return $outcome;
                }
                $url = $outcome;
            }
        } catch (\Throwable $e) {
            return new FetchResult($url, 0, reason: $e->getMessage());
        }

        return new FetchResult($url, 0, reason: 'Redirect limit');
    }

    /**
     * The address to pin the request to, or why the URL is refused.
     *
     * @return array{host: string, port: int, ip: string}|string
     */
    private function target(string $url): array|string
    {
        $uri = Uri::of($url);
        $host = trim((string) $uri->host(), '[]');
        $port = $uri->port() ?? ($uri->scheme() === 'https' ? 443 : 80);
        if (! in_array($uri->scheme(), ['http', 'https'], true) || ! in_array($port, [80, 443], true) || $host === '' || filled($uri->user(true))) {
            return 'Refused URL scheme, port or authority';
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : $this->resolver->resolve($host);
        if ($addresses === [] || array_any($addresses, fn (string $ip): bool => ! $this->isPublic($ip))) {
            return 'Refused unresolved or non-public host';
        }

        return ['host' => $host, 'port' => $port, 'ip' => $addresses[0]];
    }

    /**
     * One request to a checked address: the result, or the next URL when it redirects.
     *
     * @param  array{host: string, port: int, ip: string}  $target
     */
    private function send(string $url, string $method, int $maxBytes, array $target, bool $lastHop): FetchResult|string
    {
        $sink = tmpfile();
        if ($sink === false) {
            return new FetchResult($url, 0, reason: 'Could not create response sink');
        }

        try {
            // Force cURL: Guzzle's streaming handler would ignore CURLOPT_RESOLVE.
            $response = Http::withoutRedirecting()->connectTimeout(5)->timeout(20)->setHandler(new CurlHandler)
                ->withOptions($this->options($sink, $method, $maxBytes, $target))->send($method, $url);

            if (in_array($response->status(), self::REDIRECT_STATUSES, true)) {
                $location = $response->header('Location');

                return $lastHop || ! filled($location)
                    ? new FetchResult($url, 0, reason: 'Redirect limit or missing Location')
                    : self::resolveUrl($url, $location);
            }

            if ($method !== 'GET') {
                return $this->result($url, $response, null);
            }
            $body = $this->readBody($response->toPsrResponse()->getBody(), $maxBytes);

            return $body === null ? new FetchResult($url, 0, reason: 'Response exceeds byte cap') : $this->result($url, $response, $body);
        } finally {
            if (is_resource($sink)) {
                fclose($sink);
            }
        }
    }

    /**
     * @param  resource  $sink
     * @param  array{host: string, port: int, ip: string}  $target
     * @return array<string, mixed>
     */
    private function options($sink, string $method, int $maxBytes, array $target): array
    {
        $bracket = fn (string $value): string => str_contains($value, ':') ? '['.$value.']' : $value;

        return [
            'sink' => $sink,
            'proxy' => '',
            'curl' => [CURLOPT_RESOLVE => ["{$bracket($target['host'])}:{$target['port']}:{$bracket($target['ip'])}"]],
            'progress' => function ($total, $downloaded) use ($method, $maxBytes): void {
                if ($method === 'GET' && $downloaded > $maxBytes) {
                    throw new \RuntimeException('Response exceeds byte cap');
                }
            },
        ];
    }

    private function result(string $url, Response $response, ?string $body): FetchResult
    {
        $type = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
        $version = $response->header('ETag') ?: $response->header('Last-Modified');

        return new FetchResult($url, $response->status(), $type === '' ? null : $type, filled($version) ? mb_substr($version, 0, 255) : null, $body);
    }

    /** The whole body, or null when it is longer than the cap. */
    private function readBody(StreamInterface $stream, int $maxBytes): ?string
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $body = '';
        while (! $stream->eof()) {
            $body .= $stream->read(min(8192, max(1, $maxBytes - strlen($body) + 1)));
            if (strlen($body) > $maxBytes) {
                return null;
            }
        }

        return $body;
    }

    private function isPublic(string $ip): bool
    {
        $binary = @inet_pton($ip);
        if ($binary === false) {
            return false;
        }
        if (strlen($binary) === 16 && substr($binary, 0, 12) === str_repeat("\0", 10)."\xff\xff") {
            return $this->isPublic((string) inet_ntop(substr($binary, 12)));
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }
        $first = ord($binary[0]);
        $second = ord($binary[1]);
        if (strlen($binary) === 4) {
            // 100.64.0.0/10 is carrier-grade NAT, which the reserved-range flag does not cover.
            return ! in_array($first, [0, 127], true) && ! ($first === 169 && $second === 254) && ! ($first === 100 && ($second & 0xC0) === 64) && $first < 224;
        }

        return $binary !== str_repeat("\0", 15)."\1"
            && ! ($first === 0xFE && ($second & 0xC0) === 0x80)
            && ($first & 0xFE) !== 0xFC
            && $first !== 0xFF;
    }
}
