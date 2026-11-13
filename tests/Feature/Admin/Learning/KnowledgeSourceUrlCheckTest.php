<?php

use App\Jobs\CheckKnowledgeSourceUrlJob;
use App\Jobs\CheckKnowledgeSourceUrlsJob;
use App\Models\Document;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeItemSource;
use App\Services\Documents\ThumbnailStore;
use App\Services\Learning\HostResolver;
use App\Services\Learning\SafeUrlFetcher;
use App\Services\Learning\SourceUrlChecker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Http::preventStrayRequests();
    $this->app->instance(HostResolver::class, new class extends HostResolver
    {
        public function resolve(string $host): array
        {
            return ['93.184.216.34'];
        }
    });
});

function sourceUrlForCheck(array $attributes = []): KnowledgeItemSource
{
    return KnowledgeItemSource::factory()->create(['url' => 'https://public.test/page', ...$attributes]);
}

test('successful and protected responses reset failures', function (int $status): void {
    $this->freezeSecond();
    $source = sourceUrlForCheck(['check_failures' => 2]);
    Http::fake(['https://public.test/page' => Http::response('', $status, ['Content-Type' => 'text/plain'])]);

    app(SourceUrlChecker::class)->check($source);

    $this->assertDatabaseHas('knowledge_item_sources', ['id' => $source->id, 'check_status' => $status, 'check_failures' => 0, 'thumbnail_path' => null]);
    expect($source->checked_at?->equalTo(now()))->toBeTrue();
    if ($status === 403 || $status === 401) {
        Http::assertSentCount(1);
    }
})->with([200, 401, 403]);

test('three failed checks mark a URL broken', function (): void {
    $source = sourceUrlForCheck();
    Http::fake(['https://public.test/page' => Http::response('', 404)]);
    $checker = app(SourceUrlChecker::class);

    $checker->check($source);
    expect($source->isBroken())->toBeFalse();
    $checker->check($source);
    expect($source->isBroken())->toBeFalse();
    $checker->check($source);

    expect($source->fresh()->isBroken())->toBeTrue();
});

test('network failures increment and saturate the failure count', function (int $initial, int $expected): void {
    $source = sourceUrlForCheck(['check_failures' => $initial]);
    Http::fake(['https://public.test/page' => Http::failedConnection()]);

    app(SourceUrlChecker::class)->check($source);

    $this->assertDatabaseHas('knowledge_item_sources', ['id' => $source->id, 'check_status' => 0, 'check_failures' => $expected]);
})->with([[0, 1], [255, 255]]);

test('unsupported HEAD falls back to GET', function (int $status): void {
    $source = sourceUrlForCheck(['check_failures' => 2]);
    Http::fake(['https://public.test/page' => Http::sequence()->push('', $status)->push('', 200, ['Content-Type' => 'text/plain'])]);

    app(SourceUrlChecker::class)->check($source);

    expect($source->check_status)->toBe(200)->and($source->check_failures)->toBe(0);
    Http::assertSentCount(2);
})->with([405, 501]);

test('HTML preview images are resolved against the final page URL and stored as WebP', function (string $meta): void {
    Storage::fake();
    $source = sourceUrlForCheck();
    $png = Image::createImage(2, 2)->encodeUsingFileExtension('png')->toString();
    Http::fake([
        'https://public.test/page' => Http::response('', 302, ['Location' => '/guide/page']),
        'https://public.test/guide/page' => Http::response('<html><head><meta '.$meta.' content="../cover.png"></head></html>', 200, ['Content-Type' => 'text/html', 'ETag' => 'v1']),
        'https://public.test/cover.png' => Http::response($png, 200, ['Content-Type' => 'image/png']),
    ]);

    app(SourceUrlChecker::class)->check($source);

    Storage::assertExists('source-thumbnails/'.$source->id.'.webp');
    expect($source->thumbnail_version)->toBe('v1')->and($source->content_version)->toBe('v1')->and($source->check_failures)->toBe(0);
})->with(['property="og:image"', 'name="twitter:image"']);

test('preview errors preserve the previous thumbnail and successful link status', function (): void {
    Storage::fake();
    $source = sourceUrlForCheck(['thumbnail_path' => 'source-thumbnails/old.webp', 'thumbnail_version' => 'old']);
    Storage::put('source-thumbnails/old.webp', 'old-image');
    Http::fake([
        'https://public.test/page' => Http::response('<meta property="og:image" content="/bad.png">', 200, ['Content-Type' => 'text/html', 'ETag' => 'new']),
        'https://public.test/bad.png' => Http::response('%PDF-not-an-image', 200, ['Content-Type' => 'image/png']),
    ]);

    app(SourceUrlChecker::class)->check($source);

    expect($source->thumbnail_version)->toBe('old')->and($source->check_failures)->toBe(0);
    expect(Storage::get('source-thumbnails/old.webp'))->toBe('old-image');
});

test('unchanged or unversioned existing previews are not downloaded again', function (?string $version): void {
    $source = sourceUrlForCheck(['thumbnail_path' => 'source-thumbnails/old.webp', 'thumbnail_version' => $version ?? 'image-hash']);
    Http::fake(['https://public.test/page' => Http::response('', 200, array_filter(['Content-Type' => 'text/html', 'ETag' => $version]))]);

    app(SourceUrlChecker::class)->check($source);

    Http::assertSentCount(1);
})->with(['v1', null]);

test('PDF previews use pdftoppm and remove all temporary files', function (): void {
    Storage::fake();
    $source = sourceUrlForCheck();
    $png = Image::createImage(2, 2)->encodeUsingFileExtension('png')->toString();
    $temporary = [];
    Process::fake(['*pdftoppm*' => function (PendingProcess $process) use ($png, &$temporary) {
        $command = $process->command;
        $pdf = $command[count($command) - 2];
        $output = $command[count($command) - 1].'-01.png';
        $temporary = [$pdf, $output];
        expect(file_get_contents($pdf))->toStartWith('%PDF-');
        file_put_contents($output, $png);

        return Process::result();
    }]);
    Http::fake(['https://public.test/page' => Http::response('%PDF-external', 200, ['Content-Type' => 'application/pdf', 'ETag' => 'pdf-v1'])]);
    $checker = Mockery::mock(SourceUrlChecker::class, [app(SafeUrlFetcher::class), app(ThumbnailStore::class)])
        ->makePartial()->shouldAllowMockingProtectedMethods();
    $checker->shouldReceive('pdftoppm')->andReturn('/usr/bin/pdftoppm');

    $checker->check($source);

    Storage::assertExists('source-thumbnails/'.$source->id.'.webp');
    expect($source->thumbnail_version)->toBe('pdf-v1')->and($temporary)->toHaveCount(2);
    foreach ($temporary as $file) {
        expect(file_exists($file))->toBeFalse();
    }
    Process::assertRan(fn (PendingProcess $process): bool => $process->timeout === 30 && in_array('-png', $process->command, true));
});

test('a missing PDF renderer skips the preview without failing the link', function (): void {
    Storage::fake();
    Process::fake();
    $source = sourceUrlForCheck();
    Http::fake(['https://public.test/page' => Http::response('%PDF-external', 200, ['Content-Type' => 'application/pdf'])]);
    $checker = Mockery::mock(SourceUrlChecker::class, [app(SafeUrlFetcher::class), app(ThumbnailStore::class)])
        ->makePartial()->shouldAllowMockingProtectedMethods();
    $checker->shouldReceive('pdftoppm')->andReturnNull();

    $checker->check($source);

    expect($source->check_failures)->toBe(0)->and($source->thumbnail_path)->toBeNull();
    Process::assertNothingRan();
});

test('daily dispatch includes only URL sources of active items and spaces their jobs', function (): void {
    $this->freezeSecond();
    Queue::fake([CheckKnowledgeSourceUrlJob::class]);
    $first = sourceUrlForCheck();
    $second = sourceUrlForCheck();
    KnowledgeItemSource::factory()->for(KnowledgeItem::factory()->create(['is_active' => false]), 'item')->create();
    KnowledgeItemSource::factory()->create(['document_id' => Document::factory()->create()->id, 'url' => null]);

    (new CheckKnowledgeSourceUrlsJob(5))->handle();

    Queue::assertPushed(CheckKnowledgeSourceUrlJob::class, 2);
    Queue::assertPushed(CheckKnowledgeSourceUrlJob::class, fn ($job): bool => $job->source->is($first) && $job->delay->equalTo(now()));
    Queue::assertPushed(CheckKnowledgeSourceUrlJob::class, fn ($job): bool => $job->source->is($second) && $job->delay->equalTo(now()->addSeconds(5)));
});

test('document sources are never fetched by the URL checker', function (): void {
    $source = KnowledgeItemSource::factory()->create(['document_id' => Document::factory()->create()->id, 'url' => null]);
    Http::fake(['https://public.test/*' => Http::response()]);

    app(SourceUrlChecker::class)->check($source);

    Http::assertNothingSent();
    expect($source->fresh()->checked_at)->toBeNull();
});

test('a failed PDF render cleans up and keeps the old preview', function (): void {
    Storage::fake();
    $source = sourceUrlForCheck(['thumbnail_path' => 'source-thumbnails/old.webp', 'thumbnail_version' => 'old']);
    Storage::put('source-thumbnails/old.webp', 'old-image');
    $temporary = [];
    Process::fake(['*pdftoppm*' => function (PendingProcess $process) use (&$temporary) {
        $command = $process->command;
        $temporary = [$command[count($command) - 2], $command[count($command) - 1].'-1.png'];
        file_put_contents($temporary[1], 'incomplete');

        return Process::result(exitCode: 1);
    }]);
    Http::fake(['https://public.test/page' => Http::response('%PDF-external', 200, ['Content-Type' => 'application/pdf', 'ETag' => 'new'])]);
    $checker = Mockery::mock(SourceUrlChecker::class, [app(SafeUrlFetcher::class), app(ThumbnailStore::class)])
        ->makePartial()->shouldAllowMockingProtectedMethods();
    $checker->shouldReceive('pdftoppm')->andReturn('/usr/bin/pdftoppm');

    $checker->check($source);

    expect($source->thumbnail_version)->toBe('old')->and($source->check_failures)->toBe(0);
    expect(Storage::get('source-thumbnails/old.webp'))->toBe('old-image');
    expect($temporary)->toHaveCount(2);
    foreach ($temporary as $file) {
        expect(file_exists($file))->toBeFalse();
    }
});

test('unversioned previews use an image digest', function (): void {
    Storage::fake();
    $source = sourceUrlForCheck();
    $png = Image::createImage(2, 2)->encodeUsingFileExtension('png')->toString();
    Http::fake([
        'https://public.test/page' => Http::response('<meta property="og:image" content="/cover.png">', 200, ['Content-Type' => 'text/html']),
        'https://public.test/cover.png' => Http::response($png, 200, ['Content-Type' => 'image/png']),
    ]);

    app(SourceUrlChecker::class)->check($source);

    expect($source->thumbnail_version)->toBe(sha1($png))->and($source->content_version)->toBeNull();
    Storage::assertExists('source-thumbnails/'.$source->id.'.webp');
});

test('the inline command limits checks to one item and reports totals', function (): void {
    $source = sourceUrlForCheck(['check_failures' => 2]);
    $other = sourceUrlForCheck();
    Http::fake(['https://public.test/page' => Http::response('', 404)]);

    $this->artisan('knowledge:check-source-urls', ['--item' => $source->knowledge_item_id])
        ->expectsOutputToContain('Checked 1 links, 1 not working, 0 previews.')
        ->assertSuccessful();

    expect($source->fresh()->isBroken())->toBeTrue()->and($other->fresh()->checked_at)->toBeNull();
    Http::assertSentCount(1);
});
