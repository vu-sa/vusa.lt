<?php

use App\Enums\MailingListSyncStatus;
use App\Models\MailingList;
use App\Services\SystemMonitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'services.mailman.enabled' => true,
        'services.mailman.api_url' => 'https://konf.test/mailman-api/3.1',
        'services.mailman.username' => 'vusa-app',
        'services.mailman.archives_url' => 'https://konf.test/archives',
        'services.mailman.archives_username' => 'vusa-app-archive',
    ]);
    Http::preventStrayRequests();
});

test('reports disabled, not broken, where Mailman is not enabled', function (): void {
    config(['services.mailman.enabled' => false]);

    expect(app(SystemMonitorService::class)->getMailmanStatus()['status'])->toBe('disabled');
});

test('is healthy when both logins work and no managed list is stuck', function (): void {
    MailingList::factory()->create(['last_sync_status' => MailingListSyncStatus::Failed]);
    Http::fake([
        'https://konf.test/mailman-api/3.1/lists*' => Http::response(['entries' => [], 'total_size' => 56]),
        'https://konf.test/archives/api/lists/*' => Http::response(['count' => 56, 'results' => []]),
    ]);

    expect(app(SystemMonitorService::class)->getMailmanStatus())
        ->status->toBe('healthy')
        ->lists_needing_attention->toBe(0)
        ->api->toBe('ok')
        ->archive->toBe('ok');
});

test('is an error when the API rejects the login', function (): void {
    Http::fake([
        'https://konf.test/mailman-api/3.1/lists*' => Http::response(['title' => '401 Unauthorized'], 401),
        'https://konf.test/archives/api/lists/*' => Http::response(['count' => 0, 'results' => []]),
    ]);

    $status = app(SystemMonitorService::class)->getMailmanStatus();

    expect($status['status'])->toBe('error')
        ->and($status['error'])->toContain('401');
});

test('needs attention when the archive login fails or a managed list is stuck', function (): void {
    Http::fake([
        'https://konf.test/mailman-api/3.1/lists*' => Http::response(['entries' => [], 'total_size' => 56]),
        'https://konf.test/archives/api/lists/*' => Http::response(['detail' => 'Invalid username/password.'], 403),
    ]);

    expect(app(SystemMonitorService::class)->getMailmanStatus()['status'])->toBe('warning');

    Http::fake([
        'https://konf.test/mailman-api/3.1/lists*' => Http::response(['entries' => [], 'total_size' => 56]),
        'https://konf.test/archives/api/lists/*' => Http::response(['count' => 56, 'results' => []]),
    ]);
    cache()->forget('system.mailman_status');
    MailingList::factory()->managed()->create(['last_sync_status' => MailingListSyncStatus::NeedsReview]);

    expect(app(SystemMonitorService::class)->getMailmanStatus())
        ->status->toBe('warning')
        ->lists_needing_attention->toBe(1);
});

test('an HTTP success with a malformed health payload is not healthy', function (): void {
    Http::fake([
        'https://konf.test/mailman-api/3.1/lists*' => Http::response('<html>Sign in</html>'),
        'https://konf.test/archives/api/lists/*' => Http::response(['results' => []]),
    ]);

    $status = app(SystemMonitorService::class)->getMailmanStatus();
    expect($status['status'])->toBe('error')
        ->and($status['api'])->not->toBe('ok')
        ->and($status['archive'])->not->toBe('ok');
});
