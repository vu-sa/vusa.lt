<?php

namespace App\Services\Mailman;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Uri;

/**
 * Read-only access to HyperKitty's archive API. Private archives are served only to a
 * superuser or a subscribed member, so this authenticates as a dedicated service superuser.
 */
class HyperkittyClient
{
    private const int TIMEOUT = 20;

    private const int PAGE_SIZE = 50;

    public function isConfigured(): bool
    {
        return (bool) config('services.mailman.enabled')
            && filled(config('services.mailman.archives_url'))
            && filled(config('services.mailman.archives_username'));
    }

    /** Wrong credentials fail even on this public endpoint, so it doubles as a login check. */
    public function ping(): void
    {
        $page = $this->get('api/lists/', ['limit' => 1])->json();
        if (! is_array($page) || ! is_int($page['count'] ?? null) || ! is_array($page['results'] ?? null)) {
            throw new MailmanException('HyperKitty returned an invalid health response.');
        }
    }

    /** @return array{results: list<array<string, mixed>>, count: int, next: string|null} */
    public function emailPage(string $address, int $offset = 0): array
    {
        $page = $this->get("api/list/{$address}/emails/", ['limit' => self::PAGE_SIZE, 'offset' => $offset])->json();
        if (! is_array($page) || ! is_int($page['count'] ?? null) || $page['count'] < 0
            || ! is_array($page['results'] ?? null) || ! array_is_list($page['results'])
            || ! array_key_exists('next', $page) || ($page['next'] !== null && ! is_string($page['next']))) {
            throw new MailmanException('HyperKitty returned an invalid archive page.');
        }
        foreach ($page['results'] as $email) {
            if (! is_array($email) || ! is_string($email['message_id_hash'] ?? null) || $email['message_id_hash'] === '') {
                throw new MailmanException('HyperKitty returned an invalid archived message.');
            }
        }
        if (($page['results'] === [] && $page['next'] !== null)
            || ($page['next'] === null && $offset + count($page['results']) < $page['count'])) {
            throw new MailmanException('HyperKitty returned an incomplete archive page.');
        }

        return $page;
    }

    /**
     * @return array{body_text: string|null, attachments: list<array{name: string, size: int|null, url: string|null}>}
     */
    public function emailDetail(string $address, string $messageIdHash): array
    {
        $email = $this->get("api/list/{$address}/email/{$messageIdHash}/")->json();

        if (! is_array($email) || ! array_key_exists('content', $email)
            || (isset($email['attachments']) && ! is_array($email['attachments']))) {
            throw new MailmanException('HyperKitty returned an invalid archived message detail.');
        }

        return [
            'body_text' => isset($email['content']) ? (string) $email['content'] : null,
            'attachments' => array_map(fn (array $attachment) => [
                'name' => (string) ($attachment['name'] ?? ''),
                'size' => isset($attachment['size']) ? (int) $attachment['size'] : null,
                'url' => isset($attachment['download']) ? $this->publicUrl((string) $attachment['download']) : null,
            ], $email['attachments'] ?? []),
        ];
    }

    /**
     * HyperKitty only knows a list once something was archived to it; before that its emails
     * endpoint answers 500 rather than an empty page.
     */
    public function hasArchive(string $address): bool
    {
        $response = $this->request()->get($this->baseUrl()."/api/list/{$address}/");

        if ($response->status() === 404) {
            return false;
        }

        if (! $response->successful()) {
            throw new MailmanException(sprintf('HyperKitty GET api/list/%s/ failed with %d', $address, $response->status()));
        }

        return true;
    }

    public function messageUrl(string $address, string $messageIdHash): string
    {
        return $this->publicUrl($this->baseUrl()."/list/{$address}/message/{$messageIdHash}/");
    }

    private function publicUrl(string $url): string
    {
        $api = Uri::of($this->baseUrl());
        $public = Uri::of(config('services.mailman.archives_public_url') ?: $this->baseUrl());
        $path = Uri::of($url)->path();
        $relativePath = str_starts_with($path, $api->path().'/') ? substr($path, strlen($api->path())) : $path;

        return (string) $public->withPath(trim($public->path(), '/').'/'.ltrim($relativePath, '/').(str_ends_with($url, '/') ? '/' : ''));
    }

    /** HyperKitty obfuscates sender addresses as "name (a) domain". */
    public static function senderEmail(?string $obfuscated): ?string
    {
        return $obfuscated === null ? null : strtolower(str_replace(' (a) ', '@', $obfuscated));
    }

    public static function threadId(?string $threadUrl): ?string
    {
        return $threadUrl === null ? null : Str::of(rtrim($threadUrl, '/'))->afterLast('/')->toString();
    }

    /**
     * @param  array<string, mixed>  $query
     */
    private function get(string $path, array $query = []): Response
    {
        $response = $this->request()->get($this->baseUrl().'/'.$path, $query);

        if (! $response->successful()) {
            throw new MailmanException(sprintf('HyperKitty GET %s failed with %d', $path, $response->status()));
        }

        return $response;
    }

    private function request(): PendingRequest
    {
        if (! $this->isConfigured()) {
            throw new MailmanException('HyperKitty is not configured.');
        }

        return Http::withBasicAuth((string) config('services.mailman.archives_username'), (string) config('services.mailman.archives_password'))
            ->acceptJson()
            ->withoutRedirecting()
            ->connectTimeout(5)
            ->timeout(self::TIMEOUT);
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('services.mailman.archives_url'), '/');
    }
}
