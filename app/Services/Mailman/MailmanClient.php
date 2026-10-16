<?php

namespace App\Services\Mailman;

use App\Models\MailingList;
use App\Support\StagingProtection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Mailman 3 core REST API (konf.vusa.lt), reached through an HTTPS proxy that only admits the
 * production server.
 *
 * Every non-2xx answer throws: a failed roster read must never look like an empty list, or the
 * sync would subscribe everyone again or, worse, compute removals from nothing.
 */
class MailmanClient
{
    private const int TIMEOUT = 15;

    private const int PAGE_SIZE = 500;

    public function isConfigured(): bool
    {
        return (bool) config('services.mailman.enabled')
            && filled(config('services.mailman.api_url'))
            && filled(config('services.mailman.username'));
    }

    public function domain(): string
    {
        return (string) config('services.mailman.domain', 'konf.vusa.lt');
    }

    /** A cheap authenticated read for health checks; returns how many lists Mailman has. */
    public function ping(): int
    {
        $count = $this->send('GET', 'lists', query: ['count' => 1, 'page' => 1])->json('total_size');
        if (! is_int($count) || $count < 0) {
            throw new MailmanException('Mailman returned an invalid health response.');
        }

        return $count;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lists(): array
    {
        $lists = $this->paginate('lists');
        foreach ($lists as $list) {
            if (! is_string($list['fqdn_listname'] ?? null) || ! is_string($list['list_id'] ?? null)) {
                throw new MailmanException('Mailman returned an invalid list.');
            }
        }

        return $lists;
    }

    /**
     * @return array<string, mixed>
     */
    public function listConfig(string $listId): array
    {
        $config = $this->send('GET', "lists/{$listId}/config")->json();
        if (! is_array($config) || ! is_bool($config['send_welcome_message'] ?? null) || ! is_bool($config['send_goodbye_message'] ?? null)) {
            throw new MailmanException('Mailman returned an invalid list configuration.');
        }

        return $config;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function updateConfig(string $listId, array $config): void
    {
        $this->ensureWritable();
        $this->send('PATCH', "lists/{$listId}/config", $config);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function createList(string $address, array $config): string
    {
        $this->ensureWritable();
        $this->send('POST', 'lists', ['fqdn_listname' => $address, 'style_name' => 'private-default']);

        $listId = MailingList::listIdFor($address);
        try {
            $this->updateConfig($listId, $config);
        } catch (\Throwable $exception) {
            try {
                $this->deleteList($listId);
            } catch (\Throwable $cleanupException) {
                report($cleanupException);
            }
            throw $exception;
        }

        return $listId;
    }

    public function deleteList(string $listId): void
    {
        $this->ensureWritable();
        $this->send('DELETE', "lists/{$listId}");
    }

    /**
     * @return list<array{email: string, display_name: string|null, member_id: string}>
     */
    public function roster(string $listId, string $role = 'member'): array
    {
        $entries = $this->paginate("lists/{$listId}/roster/{$role}");
        foreach ($entries as $entry) {
            if (! is_string($entry['email'] ?? null) || ! filter_var(trim($entry['email']), FILTER_VALIDATE_EMAIL)
                || ! is_string($entry['member_id'] ?? null) || $entry['member_id'] === '') {
                throw new MailmanException('Mailman returned an invalid roster member.');
            }
        }

        return array_map(fn (array $entry) => [
            'email' => strtolower(trim($entry['email'])),
            'display_name' => filled($entry['display_name'] ?? null) ? (string) $entry['display_name'] : null,
            'member_id' => (string) $entry['member_id'],
        ], $entries);
    }

    /**
     * Pre-verified, pre-confirmed and pre-approved, so the person gets no confirmation email.
     */
    public function subscribe(string $listId, string $email, ?string $displayName): void
    {
        $this->ensureWritable();
        $this->send('POST', 'members', array_filter([
            'list_id' => $listId,
            'subscriber' => $email,
            'display_name' => $displayName,
            'role' => 'member',
            'pre_verified' => true,
            'pre_confirmed' => true,
            'pre_approved' => true,
        ], fn ($value) => $value !== null));
    }

    public function unsubscribe(string $memberId): void
    {
        $this->ensureWritable();
        $this->send('DELETE', "members/{$memberId}");
    }

    /**
     * Run membership changes with welcome and goodbye emails off, restoring the list's own
     * settings afterwards even when a change fails.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function silently(string $listId, callable $callback): mixed
    {
        $config = $this->listConfig($listId);
        $original = [
            'send_welcome_message' => (bool) ($config['send_welcome_message'] ?? false),
            'send_goodbye_message' => (bool) ($config['send_goodbye_message'] ?? false),
        ];

        try {
            $this->updateConfig($listId, ['send_welcome_message' => false, 'send_goodbye_message' => false]);

            return $callback();
        } finally {
            $this->updateConfig($listId, $original);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function paginate(string $path): array
    {
        $entries = [];
        $page = 1;

        do {
            $body = $this->send('GET', $path, query: ['count' => self::PAGE_SIZE, 'page' => $page])->json();
            if (! is_array($body) || ! is_int($body['total_size'] ?? null) || $body['total_size'] < 0
                || (isset($body['entries']) && (! is_array($body['entries']) || ! array_is_list($body['entries'])))) {
                throw new MailmanException('Mailman returned an invalid collection.');
            }
            $batch = $body['entries'] ?? [];
            foreach ($batch as $entry) {
                if (! is_array($entry)) {
                    throw new MailmanException('Mailman returned an invalid collection entry.');
                }
            }
            $entries = [...$entries, ...$batch];
            $page++;
        } while (count($entries) < $body['total_size'] && $batch !== []);

        if (count($entries) !== $body['total_size']) {
            throw new MailmanException('Mailman returned an incomplete collection.');
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>|null  $data
     * @param  array<string, mixed>  $query
     */
    private function send(string $method, string $path, ?array $data = null, array $query = []): Response
    {
        if (! $this->isConfigured()) {
            throw new MailmanException('Mailman is not configured.');
        }

        $url = rtrim((string) config('services.mailman.api_url'), '/').'/'.$path;
        $options = $query === [] ? [] : ['query' => $query];

        if ($data !== null) {
            $options['json'] = $data;
        }

        $response = $this->request()->send($method, $url, $options);

        if ($method === 'DELETE' && $response->status() === 404) {
            return $response;
        }

        if (! $response->successful()) {
            throw new MailmanException(sprintf('Mailman %s %s failed with %d: %s', $method, $path, $response->status(), mb_substr($response->body(), 0, 300)));
        }

        return $response;
    }

    private function request(): PendingRequest
    {
        return Http::withBasicAuth((string) config('services.mailman.username'), (string) config('services.mailman.password'))
            ->acceptJson()
            ->withoutRedirecting()
            ->connectTimeout(5)
            ->timeout(self::TIMEOUT);
    }

    private function ensureWritable(): void
    {
        StagingProtection::ensureMailmanIsWritable();
    }
}
