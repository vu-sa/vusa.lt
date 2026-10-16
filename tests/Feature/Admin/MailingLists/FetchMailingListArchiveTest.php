<?php

use App\Actions\FetchMailingListArchive;
use App\Models\MailingList;
use App\Models\Tenant;
use App\Services\Mailman\MailmanException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

pest()->use(RefreshDatabase::class);

const ARCHIVES = 'https://konf.test/archives';

beforeEach(function (): void {
    config([
        'services.mailman.enabled' => true,
        'services.mailman.archives_url' => ARCHIVES,
        'services.mailman.archives_username' => 'vusa-app-archive',
    ]);
    Http::preventStrayRequests();

    $this->list = MailingList::factory()->create(['tenant_id' => Tenant::main()->id, 'address' => 'akad.etika@konf.vusa.lt']);
});

function archivedEmail(string $hash, string $date): array
{
    return [
        'message_id_hash' => $hash,
        'thread' => ARCHIVES."/api/list/akad.etika@konf.vusa.lt/thread/THREAD{$hash}/",
        'sender' => ['address' => 'akadem (a) vusa.lt'],
        'sender_name' => 'Akademinis koordinatorius',
        'subject' => "Laiškas {$hash}",
        'date' => $date,
    ];
}

test('copies new posts with their text and attachments, stopping at the first stored one', function (): void {
    $this->list->update(['archive_head_hash' => 'OLD', 'archive_backfilled_at' => now()]);
    $this->list->messages()->create(['message_id_hash' => 'OLD', 'subject' => 'Senas', 'sent_at' => '2026-01-01 10:00:00']);

    Http::fake([
        ARCHIVES.'/api/list/akad.etika@konf.vusa.lt/' => Http::response(['name' => 'akad.etika@konf.vusa.lt']),
        ARCHIVES.'/api/list/akad.etika@konf.vusa.lt/emails/*' => Http::response([
            'count' => 3,
            'next' => null,
            'results' => [
                archivedEmail('NEW', '2026-10-01T09:00:00Z'),
                archivedEmail('OLD', '2026-01-01T10:00:00+02:00'),
                archivedEmail('OLDER', '2025-12-01T10:00:00+02:00'),
            ],
        ]),
        ARCHIVES.'/api/list/akad.etika@konf.vusa.lt/email/NEW/' => Http::response([
            'content' => "Sveiki,\nregistracija atidaryta.",
            'attachments' => [['name' => 'mokymai.pdf', 'size' => 2048, 'download' => ARCHIVES.'/list/akad.etika@konf.vusa.lt/message/NEW/attachment/2/mokymai.pdf']],
        ]),
    ]);

    expect(app(FetchMailingListArchive::class)->execute($this->list))->toBe(1);

    $message = $this->list->messages()->where('message_id_hash', 'NEW')->sole();
    expect($message)
        ->sender_email->toBe('akadem@vusa.lt')
        ->thread_id->toBe('THREADNEW')
        ->body_text->toBe("Sveiki,\nregistracija atidaryta.")
        ->and($message->sent_at->format('Y-m-d H:i:s'))->toBe('2026-10-01 12:00:00')
        ->and($message->attachments[0]['name'])->toBe('mokymai.pdf')
        ->and($this->list->fresh()->last_post_at->format('Y-m-d H:i:s'))->toBe('2026-10-01 12:00:00');
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/email/OLDER/'));
});

test('a list nobody has posted to yet has no archive and still backfills full history once messages appear', function (): void {
    Http::fake([
        ARCHIVES.'/api/list/akad.etika@konf.vusa.lt/' => Http::response(['detail' => 'Not found.'], 404),
    ]);

    $fetch = app(FetchMailingListArchive::class);
    expect($fetch->execute($this->list))->toBe(0)
        ->and($this->list->fresh()->archive_fetched_at)->not->toBeNull()
        ->and($this->list->fresh()->archive_backfilled_at)->toBeNull();
    Http::assertNotSent(fn (Request $request) => str_contains($request->url(), '/emails/'));

    $emails = array_map(fn ($n) => archivedEmail('HASH'.$n, '2026-10-01T12:00:00Z'), range(60, 1));
    fakeArchivePages($emails);

    expect($fetch->execute($this->list))->toBe(60)
        ->and($this->list->messages()->count())->toBe(60)
        ->and($this->list->fresh()->archive_backfilled_at)->not->toBeNull();
});

/** @param list<array<string, mixed>> $emails */
function fakeArchivePages(array $emails, ?string $failHash = null): void
{
    Http::swap(new Factory);
    Http::preventStrayRequests();
    Http::fake([
        ARCHIVES.'/api/list/akad.etika@konf.vusa.lt/' => Http::response(['name' => 'akad.etika@konf.vusa.lt']),
        ARCHIVES.'/api/list/akad.etika@konf.vusa.lt/emails/*' => function (Request $request) use ($emails) {
            $offset = (int) ($request['offset'] ?? 0);
            $page = array_slice($emails, $offset, 50);

            return Http::response(['count' => count($emails), 'results' => $page, 'next' => $offset + count($page) < count($emails) ? 'next' : null]);
        },
        ARCHIVES.'/api/list/akad.etika@konf.vusa.lt/email/*' => fn (Request $request) => $failHash !== null && str_contains($request->url(), '/'.$failHash.'/')
            ? Http::response([], 503) : Http::response(['content' => 'Body', 'attachments' => []]),
    ]);
}

test('history beyond five hundred messages resumes and remains idempotent while new posts arrive', function (): void {
    $emails = array_map(fn ($n) => archivedEmail('HASH'.$n, '2026-10-01T12:00:00Z'), range(501, 1));
    fakeArchivePages($emails);
    $fetch = app(FetchMailingListArchive::class);
    expect($fetch->execute($this->list))->toBe(500)
        ->and($this->list->fresh()->archive_backfilled_at)->toBeNull();

    array_unshift($emails, archivedEmail('NEWEST', '2026-10-16T12:00:00Z'));
    fakeArchivePages($emails);
    expect($fetch->execute($this->list))->toBe(2)
        ->and($this->list->messages()->count())->toBe(502)
        ->and($this->list->fresh()->archive_backfilled_at)->not->toBeNull()
        ->and($this->list->fresh()->posts_last_year)->toBe(502)
        ->and($fetch->execute($this->list))->toBe(0);
});

test('a failed message fetch does not advance the head past missing posts', function (): void {
    $this->list->update(['archive_head_hash' => 'OLD', 'archive_backfilled_at' => now()]);
    $this->list->messages()->create(['message_id_hash' => 'OLD']);
    $emails = [archivedEmail('NEWEST', '2026-10-16'), archivedEmail('FAILED', '2026-10-15'), archivedEmail('OLD', '2026-10-01')];
    fakeArchivePages($emails, 'FAILED');
    $fetch = app(FetchMailingListArchive::class);
    expect(fn () => $fetch->execute($this->list))->toThrow(MailmanException::class);
    expect($this->list->fresh()->archive_head_hash)->toBe('OLD');

    fakeArchivePages($emails);
    expect($fetch->execute($this->list))->toBe(1)
        ->and($this->list->messages()->count())->toBe(3)
        ->and($this->list->fresh()->archive_head_hash)->toBe('NEWEST');
});

test('malformed archive pages do not mark the history complete', function (): void {
    Http::fake([
        ARCHIVES.'/api/list/akad.etika@konf.vusa.lt/' => Http::response(['name' => 'akad.etika@konf.vusa.lt']),
        ARCHIVES.'/api/list/akad.etika@konf.vusa.lt/emails/*' => Http::response(['results' => []]),
    ]);
    expect(fn () => app(FetchMailingListArchive::class)->execute($this->list))->toThrow(MailmanException::class);
    expect($this->list->fresh()->archive_backfilled_at)->toBeNull()
        ->and($this->list->fresh()->archive_fetched_at)->toBeNull();
});

test('browser archive links use the public host instead of the internal API host', function (): void {
    config(['services.mailman.archives_public_url' => 'https://konf.vusa.lt/public-archives']);
    fakeArchivePages([archivedEmail('PUBLIC', '2026-10-16')]);
    app(FetchMailingListArchive::class)->execute($this->list);

    expect($this->list->messages()->sole()->url)->toBe('https://konf.vusa.lt/public-archives/list/akad.etika@konf.vusa.lt/message/PUBLIC/');
});
