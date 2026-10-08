<?php

use App\Actions\Media\AddImageMedia;
use App\Http\Middleware\HandleInertiaRequests;
use App\Jobs\Media\AlignExistingMediaJob;
use App\Jobs\Media\BackfillLegacyImagesJob;
use App\Jobs\Media\ReindexImageSearchablesJob;
use App\Listeners\RefreshImageCacheAfterConversion;
use App\Models\Banner;
use App\Models\Calendar;
use App\Models\News;
use App\Models\Tenant;
use App\Services\ContentResolution\ContentPartResolver;
use App\Services\Media\LegacyImageBackfill;
use App\Services\Media\LegacyImageImporter;
use App\Services\Media\LegacyImageResolver;
use App\Services\Media\LegacyImageSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('spatieMediaLibrary');
    $this->tenant = Tenant::query()->first();
    $this->uploads = sys_get_temp_dir().'/legacy-backfill-'.Str::random(8);
    File::ensureDirectoryExists($this->uploads.'/news');
    File::put($this->uploads.'/news/rudens-sventė.jpg', UploadedFile::fake()->image('a.jpg', 1200, 800)->getContent());
    app()->instance(LegacyImageResolver::class, new LegacyImageResolver($this->uploads, $this->uploads));
});

afterEach(function (): void {
    File::deleteDirectory($this->uploads);
});

test('dispatching queues one chain: align existing media, import rows without media, then reindex', function (): void {
    Bus::fake();
    $calendar = Calendar::factory()->create();
    app(AddImageMedia::class)->execute($calendar, UploadedFile::fake()->image('e.jpg'), 'images');
    $withImage = News::factory()->for($this->tenant)->create(['image' => '/uploads/news/rudens-sventė.jpg']);
    News::factory()->for($this->tenant)->create(['image' => null]);

    app(LegacyImageBackfill::class)->dispatch();

    // The seeded admin's faker photo URL is queued too; the resolver sorts it out when the job runs.
    Bus::assertChained([
        AlignExistingMediaJob::class,
        fn (BackfillLegacyImagesJob $job) => $job->target === 'news.image' && $job->ids === [$withImage->id],
        fn (BackfillLegacyImagesJob $job) => $job->target === 'users.profile_photo',
        ReindexImageSearchablesJob::class,
    ]);
});

test('the import copies the file, records where it came from and leaves the article untouched', function (): void {
    $news = News::factory()->for($this->tenant)->create(['image' => '/uploads/news/rudens-sventė.jpg', 'image_author' => 'Jonas']);
    $updatedAt = $news->fresh()->updated_at;
    $this->travel(1)->hour();

    (new BackfillLegacyImagesJob('news.image', [$news->id]))->handle(app(LegacyImageImporter::class));

    $news->refresh();
    $media = $news->getFirstMedia('image');

    expect($media->getCustomProperty('legacy_source'))->toBe('/uploads/news/rudens-sventė.jpg')
        ->and($media->getCustomProperty('author'))->toBe('Jonas')
        ->and($media->getCustomProperty('width'))->toBe(1200)
        ->and($news->updated_at->equalTo($updatedAt))->toBeTrue()
        ->and($news->image)->toContain("/{$media->id}/")
        ->and(File::exists($this->uploads.'/news/rudens-sventė.jpg'))->toBeTrue();
});

test('running the import again does not duplicate images', function (): void {
    $news = News::factory()->for($this->tenant)->create(['image' => '/uploads/news/rudens-sventė.jpg']);

    (new BackfillLegacyImagesJob('news.image', [$news->id]))->handle(app(LegacyImageImporter::class));
    (new BackfillLegacyImagesJob('news.image', [$news->id]))->handle(app(LegacyImageImporter::class));

    expect($news->refresh()->getMedia('image'))->toHaveCount(1);
});

test('records sharing one legacy file each get their own copy', function (): void {
    $banners = Banner::factory()->for($this->tenant)->count(2)->create(['image_url' => 'https://vusa.lt/uploads/news/rudens-sventė.jpg']);

    (new BackfillLegacyImagesJob('banners.image', $banners->modelKeys()))->handle(app(LegacyImageImporter::class));

    $ids = $banners->map(fn (Banner $banner) => $banner->refresh()->getFirstMedia('image')->id);

    expect($ids->unique())->toHaveCount(2);
});

test('the status separates what is left to import from what can never be imported', function (): void {
    News::factory()->for($this->tenant)->create(['image' => '/uploads/news/rudens-sventė.jpg']);
    News::factory()->for($this->tenant)->create(['image' => '058543019bc51198ea1dc255580d215be99f2297.jpeg']);
    News::factory()->for($this->tenant)->create(['image' => '/uploads/news/gone.jpg']);

    $status = app(LegacyImageBackfill::class)->status('news.image')['news.image'];

    expect($status['with_value'])->toBe(3)
        ->and($status['with_media'])->toBe(0)
        ->and($status['without_media'])->toBe([
            LegacyImageSource::RESOLVED => 1,
            LegacyImageSource::PLACEHOLDER => 1,
            LegacyImageSource::MISSING => 1,
        ]);

    $this->artisan('media:backfill-legacy-images', ['--sync' => true, '--target' => 'news.image'])->assertSuccessful();
    $this->artisan('media:backfill-legacy-images', ['--status' => true, '--target' => 'news.image'])
        ->expectsOutputToContain('0 pending')
        ->assertSuccessful();
});

test('on staging only a sample is imported each night', function (): void {
    Bus::fake();
    config(['app.env' => 'staging']);
    News::factory()->for($this->tenant)->count(3)->create(['image' => '/uploads/news/rudens-sventė.jpg']);
    $newest = News::query()->orderByDesc('id')->first();

    $backfill = new class extends LegacyImageBackfill
    {
        public const int STAGING_SAMPLE = 1;
    };
    $backfill->dispatch('news.image');

    Bus::assertChained([
        fn (BackfillLegacyImagesJob $job) => $job->ids === [$newest->id],
        ReindexImageSearchablesJob::class,
    ]);
});

test('import restores worker settings even when progress reporting fails', function (): void {
    $news = News::factory()->for($this->tenant)->create(['image' => '/uploads/news/gone.jpg']);
    config(['media-library.queue_conversions_by_default' => true]);
    RefreshImageCacheAfterConversion::$muted = true;
    try {
        expect(fn () => (new BackfillLegacyImagesJob('news.image', [$news->id]))->handle(app(LegacyImageImporter::class), function (): void {
            throw new RuntimeException('progress failed');
        }))->toThrow(RuntimeException::class, 'progress failed');
        expect(config('media-library.queue_conversions_by_default'))->toBeTrue()
            ->and(RefreshImageCacheAfterConversion::$muted)->toBeTrue();
    } finally {
        RefreshImageCacheAfterConversion::$muted = false;
    }
});

test('synchronous imports invalidate caches that contain image urls', function (): void {
    News::factory()->for($this->tenant)->create(['image' => '/uploads/news/rudens-sventė.jpg']);
    $cache = Cache::class;
    $cache::forever(HandleInertiaRequests::TENANTS_CACHE_KEY, 'stale');
    $cache::tags(['banners'])->forever('image-backfill-test', 'stale');
    $cache::tags([ContentPartResolver::CACHE_TAG])->forever('image-backfill-test', 'stale');
    app(LegacyImageBackfill::class)->runNow('news.image');
    expect($cache::get(HandleInertiaRequests::TENANTS_CACHE_KEY))->toBeNull()
        ->and($cache::tags(['banners'])->get('image-backfill-test'))->toBeNull()
        ->and($cache::tags([ContentPartResolver::CACHE_TAG])->get('image-backfill-test'))->toBeNull();
});

test('verbose status shows the source and result of every checked row', function (string $flag): void {
    $news = News::factory()->for($this->tenant)->create(['image' => '/uploads/news/gone.jpg']);
    $this->artisan('media:backfill-legacy-images', ['--status' => true, '--target' => 'news.image', $flag => true])
        ->expectsOutputToContain('Checking news.image: 1 row(s).')
        ->expectsOutputToContain("news.image [1/1] #{$news->id} /uploads/news/gone.jpg: missing")
        ->doesntExpectOutputToContain('Checking users.profile_photo')
        ->assertSuccessful();
})->with(['-v', '--verbose']);

test('verbose synchronous imports respect the row limit and report missing sources', function (): void {
    $news = News::factory()->for($this->tenant)->create(['image' => '/uploads/news/gone.jpg']);
    News::factory()->for($this->tenant)->create(['image' => '/uploads/news/another.jpg']);
    $this->artisan('media:backfill-legacy-images', ['--sync' => true, '--limit' => 1, '--target' => 'news.image', '--verbose' => true])
        ->expectsOutputToContain('Checking news.image: 1 row(s).')
        ->expectsOutputToContain("[1/1] #{$news->id} /uploads/news/gone.jpg: missing")
        ->doesntExpectOutputToContain('another.jpg')
        ->assertSuccessful();
});

test('verbose queued imports report target counts without inspecting files', function (): void {
    Bus::fake();
    News::factory()->for($this->tenant)->create(['image' => '/uploads/news/gone.jpg']);
    $this->artisan('media:backfill-legacy-images', ['--target' => 'news.image', '--verbose' => true])
        ->expectsOutputToContain('news.image: 1 row(s), 1 import chunk(s).')
        ->doesntExpectOutputToContain('gone.jpg')
        ->assertSuccessful();
});

test('normal and quiet backfill output omit per-file progress', function (array $flags): void {
    News::factory()->for($this->tenant)->create(['image' => '/uploads/news/gone.jpg']);
    $this->artisan('media:backfill-legacy-images', ['--status' => true, '--target' => 'news.image', ...$flags])
        ->doesntExpectOutputToContain('Checking news.image')
        ->doesntExpectOutputToContain('gone.jpg')
        ->assertSuccessful();
})->with([[[]], [['--verbose' => true, '--quiet' => true]]]);

test('an empty target still reports the start of a verbose check', function (): void {
    $this->artisan('media:backfill-legacy-images', ['--status' => true, '--target' => 'news.image', '--verbose' => true])
        ->expectsOutputToContain('Checking news.image: 0 row(s).')
        ->assertSuccessful();
});

test('synchronous progress reports each import before the next row is processed', function (): void {
    $first = News::factory()->for($this->tenant)->create(['image' => '/uploads/news/rudens-sventė.jpg']);
    $second = News::factory()->for($this->tenant)->create(['image' => '/uploads/news/gone.jpg']);
    $events = [];
    app(LegacyImageBackfill::class)->runNow('news.image', null, function ($target, $checked, $total, $id, $value, $outcome) use ($first, $second, &$events): void {
        $events[] = [$checked, $total, $id, $value, $outcome];
        if ($checked === 1) {
            expect($first->fresh()->getFirstMedia('image'))->not->toBeNull()
                ->and($second->fresh()->getFirstMedia('image'))->toBeNull();
        }
    });
    expect($events)->toBe([
        [0, 2, null, null, null],
        [1, 2, $first->id, '/uploads/news/rudens-sventė.jpg', LegacyImageSource::RESOLVED],
        [2, 2, $second->id, '/uploads/news/gone.jpg', LegacyImageSource::MISSING],
    ]);
});

test('verbose import failures are reported while subsequent rows are still checked', function (): void {
    $first = News::factory()->for($this->tenant)->create(['image' => '/uploads/news/failed.jpg']);
    $second = News::factory()->for($this->tenant)->create(['image' => '/uploads/news/gone.jpg']);
    $importer = Mockery::mock(LegacyImageImporter::class);
    $importer->shouldReceive('import')->once()->withArgs(fn ($owner, $collection) => $owner->id === $first->id && $collection === 'image')->andThrow(new RuntimeException('unreadable image'));
    $importer->shouldReceive('import')->once()->withArgs(fn ($owner, $collection) => $owner->id === $second->id && $collection === 'image')->andReturn(['outcome' => LegacyImageSource::MISSING, 'media' => null]);
    app()->instance(LegacyImageImporter::class, $importer);
    $this->artisan('media:backfill-legacy-images', ['--sync' => true, '--target' => 'news.image', '--verbose' => true])
        ->expectsOutputToContain("[1/2] #{$first->id} /uploads/news/failed.jpg: failed")
        ->expectsOutputToContain("[2/2] #{$second->id} /uploads/news/gone.jpg: missing")
        ->assertSuccessful();
});
