<?php

use App\Actions\Media\AddImageMedia;
use App\Jobs\Media\BackfillLegacyImagesJob;
use App\Jobs\Media\UpgradeImageMedia;
use App\Models\Calendar;
use App\Models\Institution;
use App\Models\News;
use App\Models\Page;
use App\Models\PendingUpload;
use App\Models\Tenant;
use App\Services\ContentResolution\ContentPartResolver;
use App\Services\ImageUploadService;
use App\Services\Media\ImageMigration;
use App\Services\Media\LegacyImageResolver;
use App\Support\Media\ImageConversions;
use App\Support\Media\ImageData;
use Illuminate\Bus\PendingBatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;
use Illuminate\Support\Testing\Fakes\BatchRepositoryFake;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake();
    Storage::fake('spatieMediaLibrary');
    Queue::fake([PerformConversionsJob::class]);
});

function upgradeMedia(array $ids, ?FileManipulator $files = null): void
{
    (new UpgradeImageMedia($ids))->handle(app(ImageUploadService::class), $files ?? app(FileManipulator::class), app(LegacyImageResolver::class));
}

test('large photographic masters retain enough pixels for high density layouts', function (int $width, int $height, int $expectedWidth, int $expectedHeight): void {
    $image = UploadedFile::fake()->image('photo.jpg', $width, $height);
    $result = app(ImageUploadService::class)->normalize($image);
    $size = getimagesizefromstring($result['contents']);
    expect([$result['width'], $result['height']])->toBe([$expectedWidth, $expectedHeight])
        ->and([$size[0], $size[1]])->toBe([$expectedWidth, $expectedHeight]);
})->with([[4000, 2000, 3840, 1920], [2000, 4000, 1920, 3840], [300, 200, 300, 200]]);

test('upgrading existing media retains old URLs and generates truthful responsive candidates', function (): void {
    $owner = Page::factory()->create();
    $media = app(AddImageMedia::class)->execute($owner, UploadedFile::fake()->image('photo.jpg', 1800, 900), 'featured_image');
    $media->forgetCustomProperty('image_policy')->save();
    $oldPath = $media->getPathRelativeToRoot();
    upgradeMedia([$media->id]);
    $media->refresh();

    expect($media->getCustomProperty('image_policy'))->toBe(ImageConversions::VERSION)
        ->and($media->hasGeneratedConversion('wide'))->toBeTrue();
    Storage::disk('spatieMediaLibrary')->assertExists($oldPath);
    $data = ImageData::fromMedia($media);
    expect($data['srcset'])->toContain('400w', '900w', '1600w', '1800w')->not->toContain('2400w');
    foreach (ImageConversions::WIDTHS as $name => $width) {
        expect(getimagesize($media->getPath($name))[0])->toBe(min($width, 1800));
    }
    $name = $media->file_name;
    upgradeMedia([$media->id]);
    expect($media->fresh()->file_name)->toBe($name);
});

test('a failed upgrade leaves the original media and file usable', function (): void {
    $owner = Page::factory()->create();
    $media = app(AddImageMedia::class)->execute($owner, UploadedFile::fake()->image('photo.jpg', 800, 400), 'featured_image');
    $media->forgetCustomProperty('image_policy')->save();
    $name = $media->file_name;
    $path = $media->getPathRelativeToRoot();
    $files = Mockery::mock(FileManipulator::class);
    $files->shouldReceive('createDerivedFiles')->once()->andThrow(new RuntimeException('Conversion failed'));

    upgradeMedia([$media->id], $files);
    expect($media->fresh()->file_name)->toBe($name)
        ->and($media->fresh()->getCustomProperty('image_policy'))->toBeNull();
    Storage::disk('spatieMediaLibrary')->assertExists($path);
});

test('staging upgrades use an isolated disk and preserve production media bytes', function (): void {
    $owner = Page::factory()->create();
    $media = app(AddImageMedia::class)->execute($owner, UploadedFile::fake()->image('photo.jpg', 800, 400), 'featured_image');
    $path = $media->getPathRelativeToRoot();
    $bytes = Storage::disk('spatieMediaLibrary')->get($path);
    Storage::fake('stagingMedia');
    config(['app.env' => 'staging', 'app.files_read_only' => true, 'media-library.disk_name' => 'stagingMedia']);
    app()->instance('env', 'staging');

    upgradeMedia([$media->id]);
    expect($media->fresh()->disk)->toBe('stagingMedia')
        ->and(Storage::disk('spatieMediaLibrary')->get($path))->toBe($bytes);
    Storage::disk('stagingMedia')->assertExists($media->fresh()->getPathRelativeToRoot());
});

test('a rerun leaves already upgraded media alone', function (): void {
    $media = app(AddImageMedia::class)->execute(Page::factory()->create(), UploadedFile::fake()->image('photo.jpg', 1200, 800), 'featured_image');
    upgradeMedia([$media->id]);
    $files = Mockery::mock(FileManipulator::class);
    $files->shouldNotReceive('createDerivedFiles');

    upgradeMedia([$media->id], $files);

    expect(UpgradeImageMedia::isCurrent($media->fresh()))->toBeTrue();
});

test('one failing media does not stop the rest of its job', function (): void {
    $owner = Page::factory()->create();
    $failing = app(AddImageMedia::class)->execute($owner, UploadedFile::fake()->image('a.jpg', 800, 400), 'featured_image');
    $other = app(AddImageMedia::class)->execute(News::factory()->create(), UploadedFile::fake()->image('b.jpg', 800, 400), 'image');
    $real = app(FileManipulator::class);
    $files = Mockery::mock(FileManipulator::class);
    $files->shouldReceive('createDerivedFiles')->andReturnUsing(function (Media $media, array $only) use ($failing, $real): void {
        throw_if($media->id === $failing->id, new RuntimeException('Conversion failed'));
        $real->createDerivedFiles($media, $only);
    });

    upgradeMedia([$failing->id, $other->id], $files);

    expect(UpgradeImageMedia::isCurrent($failing->fresh()))->toBeFalse()
        ->and(UpgradeImageMedia::isCurrent($other->fresh()))->toBeTrue();
});

test('media of a soft-deleted owner is upgraded too', function (): void {
    $owner = Page::factory()->create();
    $media = app(AddImageMedia::class)->execute($owner, UploadedFile::fake()->image('photo.jpg', 800, 400), 'featured_image');
    $owner->delete();

    upgradeMedia([$media->id]);

    expect(UpgradeImageMedia::isCurrent($media->fresh()))->toBeTrue();
});

test('cleanup deletes the replaced master and its conversions, keeping the current files', function (): void {
    $media = app(AddImageMedia::class)->execute(Page::factory()->create(), UploadedFile::fake()->image('photo.jpg', 1800, 900), 'featured_image');
    $media->forgetCustomProperty('image_policy')->save();
    (app(FileManipulator::class))->createDerivedFiles($media->fresh(), ['thumb']);
    $media->refresh();
    $oldMaster = $media->getPathRelativeToRoot();
    $oldThumb = $media->getPathRelativeToRoot('thumb');

    upgradeMedia([$media->id]);
    $media->refresh();
    $this->artisan('media:migrate-images', ['--status' => true])->expectsOutputToContain('Superseded files to clean up');
    expect(app(ImageMigration::class)->supersededMedia()->count())->toBe(1);
    $this->artisan('media:migrate-images', ['--cleanup' => true])
        ->expectsOutputToContain('Removed replaced files for 1 media.')
        ->assertSuccessful();

    $disk = Storage::disk('spatieMediaLibrary');
    $disk->assertMissing([$oldMaster, $oldThumb]);
    $disk->assertExists([$media->getPathRelativeToRoot(), $media->getPathRelativeToRoot('wide')]);
    expect($media->fresh()->getCustomProperty('superseded_file'))->toBeNull();
});

test('only artwork is encoded losslessly', function (string $collection, bool $transparent, bool $expected): void {
    $owner = Institution::factory()->create();
    $image = imagecreatetruecolor(600, 400);
    if ($transparent) {
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));
    }
    ob_start();
    imagepng($image);
    $png = (string) ob_get_clean();

    $media = app(AddImageMedia::class)->execute($owner, $png, $collection);

    expect($media->getCustomProperty('lossless'))->toBe($expected);
})->with([
    'opaque PNG photo' => ['image', false, false],
    'transparent PNG' => ['image', true, true],
    'logo' => ['logo', false, true],
]);

test('a finished upload republishes its owner once, after the last conversion', function (): void {
    $owner = Page::factory()->create();
    $media = app(AddImageMedia::class)->execute($owner, UploadedFile::fake()->image('photo.jpg', 1200, 800), 'featured_image');
    $this->travel(5)->minutes();
    $updatedAt = $owner->fresh()->updated_at;
    Cache::tags([ContentPartResolver::CACHE_TAG])->put('resolved', 'stale');

    $media->markAsConversionGenerated('thumb');
    event(new ConversionHasBeenCompletedEvent($media, Conversion::create('thumb')));
    expect($owner->fresh()->updated_at->equalTo($updatedAt))->toBeTrue()
        ->and(Cache::tags([ContentPartResolver::CACHE_TAG])->get('resolved'))->toBe('stale');

    foreach (array_keys(ImageConversions::WIDTHS) as $name) {
        $media->markAsConversionGenerated($name);
    }
    event(new ConversionHasBeenCompletedEvent($media, Conversion::create('wide')));

    expect($owner->fresh()->updated_at->greaterThan($updatedAt))->toBeTrue()
        ->and(Cache::tags([ContentPartResolver::CACHE_TAG])->get('resolved'))->toBeNull();
});

test('the command queues owned media as one batch and reports what is pending', function (): void {
    $media = app(AddImageMedia::class)->execute(Page::factory()->create(), UploadedFile::fake()->image('photo.jpg', 800, 400), 'featured_image');
    $pending = PendingUpload::create(['user_id' => makeUser(Tenant::query()->first())->id]);
    app(AddImageMedia::class)->execute($pending, UploadedFile::fake()->image('draft.jpg', 800, 400), PendingUpload::COLLECTION);
    Bus::fake();

    $this->artisan('media:migrate-images', ['--status' => true])->expectsOutputToContain('1 media to upgrade')->assertFailed();
    $this->artisan('media:migrate-images', ['--detach' => true])->assertSuccessful();

    Bus::assertBatched(fn (PendingBatch $batch): bool => $batch->jobs->contains(fn ($job) => $job instanceof UpgradeImageMedia && $job->ids === [$media->id])
        && ! $batch->jobs->contains(fn ($job) => $job instanceof UpgradeImageMedia && $job->ids !== [$media->id]));
});

test('by default the command follows the batch until its jobs have run', function (): void {
    app(AddImageMedia::class)->execute(Page::factory()->create(), UploadedFile::fake()->image('photo.jpg', 800, 400), 'featured_image');
    $batches = new BatchRepositoryFake;
    Bus::fake([], $batches);
    Sleep::fake();
    Sleep::whenFakingSleep(function () use ($batches): void {
        foreach ($batches->get(10, null) as $batch) {
            $batch->pendingJobs = 0;
            $batch->finishedAt = now()->toImmutable();
        }
    });

    $this->artisan('media:migrate-images')
        ->expectsOutputToContain('stopping this view (Ctrl+C) does not stop the jobs')
        ->expectsOutputToContain('Done. Check what is left with')
        ->assertSuccessful();

    Sleep::assertSleptTimes(1);
});

test('a limited run upgrades the newest media first', function (): void {
    $owner = Page::factory()->create();
    $older = app(AddImageMedia::class)->execute($owner, UploadedFile::fake()->image('a.jpg', 800, 400), 'featured_image');
    $newer = app(AddImageMedia::class)->execute(News::factory()->create(), UploadedFile::fake()->image('b.jpg', 800, 400), 'image');

    $this->artisan('media:migrate-images', ['--sync' => true, '--limit' => 1])->assertSuccessful();

    expect(UpgradeImageMedia::isCurrent($newer->fresh()))->toBeTrue()
        ->and(UpgradeImageMedia::isCurrent($older->fresh()))->toBeFalse();
});

/**
 * A calendar main image as it looks before the shared conversions: focal point in the column,
 * no dimensions, an unused `webp` conversion and responsive images on disk.
 */
function legacyCalendarImage(): Media
{
    $calendar = Calendar::factory()->create();
    $calendar->forceFill(['main_image_focal_point' => '30% 70%'])->saveQuietly();

    $media = app(AddImageMedia::class)->execute($calendar, UploadedFile::fake()->image('event.jpg', 1000, 500), 'main_image');
    $media->forgetCustomProperty('width')->forgetCustomProperty('height')->forgetCustomProperty('image_policy');
    $media->responsive_images = ['media_library_original' => ['urls' => ['event___media_library_original_400_200.webp'], 'base64svg' => '']];
    $media->save();

    $paths = PathGeneratorFactory::create($media);
    Storage::disk($media->disk)->put($paths->getPathForConversions($media).'event-webp.webp', 'old');
    Storage::disk($media->disk)->put($paths->getPathForResponsiveImages($media).'event___media_library_original_400_200.webp', 'old');

    return $media;
}

test('older media gets its focal point, dimensions and only the shared conversions, old files removed at cleanup', function (): void {
    $media = legacyCalendarImage();
    $paths = PathGeneratorFactory::create($media);
    $disk = Storage::disk('spatieMediaLibrary');

    upgradeMedia([$media->id]);
    $media->refresh();

    expect($media->getCustomProperty('focal_point'))->toBe('30% 70%')
        ->and($media->getCustomProperty('width'))->toBe(1000)
        ->and($media->responsive_images)->toBe([])
        ->and(array_keys(array_filter($media->generated_conversions)))->toEqualCanonicalizing(array_keys(ImageConversions::WIDTHS));
    $disk->assertExists($paths->getPathForConversions($media).'event-webp.webp');

    expect(app(ImageMigration::class)->cleanup($media))->toBeTrue();
    $disk->assertMissing([
        $paths->getPathForConversions($media).'event-webp.webp',
        $paths->getPathForResponsiveImages($media).'event___media_library_original_400_200.webp',
    ]);
});

test('on staging the focal point moves but production\'s files are not touched', function (): void {
    $media = legacyCalendarImage();
    $paths = PathGeneratorFactory::create($media);
    Storage::fake('stagingMedia');
    config(['app.env' => 'staging', 'app.files_read_only' => true, 'media-library.disk_name' => 'stagingMedia']);
    app()->instance('env', 'staging');

    upgradeMedia([$media->id]);

    expect($media->refresh()->getCustomProperty('focal_point'))->toBe('30% 70%')
        ->and(app(ImageMigration::class)->cleanup($media))->toBeFalse();
    Storage::disk('spatieMediaLibrary')->assertExists($paths->getPathForConversions($media).'event-webp.webp');
});

test('a real dispatch accepts every job into the batch', function (): void {
    app(AddImageMedia::class)->execute(Page::factory()->create(), UploadedFile::fake()->image('photo.jpg', 800, 400), 'featured_image');
    News::factory()->create(['image' => '/uploads/news/old.jpg']);
    // Only the queue is faked: PendingBatch's own checks (e.g. the Batchable trait) still run.
    Queue::fake();

    $this->artisan('media:migrate-images', ['--detach' => true])->assertSuccessful();

    Queue::assertPushed(UpgradeImageMedia::class, fn (UpgradeImageMedia $job): bool => $job->batchId !== null);
    Queue::assertPushed(BackfillLegacyImagesJob::class, fn (BackfillLegacyImagesJob $job): bool => $job->batchId !== null);
});

test('cancelled media batches leave uploads and legacy images untouched', function (): void {
    $media = app(AddImageMedia::class)->execute(Page::factory()->create(), UploadedFile::fake()->image('cancelled.jpg'), 'featured_image');
    $media->forgetCustomProperty('image_policy')->save();
    $before = $media->fresh()->getAttributes();
    $news = News::factory()->create(['image' => '/uploads/news/cancelled.jpg']);

    foreach ([new UpgradeImageMedia([$media->id]), new BackfillLegacyImagesJob('news', [$news->id])] as $job) {
        [$job, $batch] = $job->withFakeBatch();
        $batch->cancel();
        app()->call([$job, 'handle']);
    }

    expect($media->fresh()->getAttributes())->toBe($before)
        ->and($news->fresh()->getMedia('image'))->toBeEmpty();
});

test('image references and cached conversion URLs need no version query string', function (): void {
    $owner = Institution::factory()->create();
    $media = app(AddImageMedia::class)->execute($owner, UploadedFile::fake()->image(str_repeat('long-name-', 12).'.jpg'), 'image');
    upgradeMedia([$media->id]);
    $owner->refreshImageCache('image');
    $data = $owner->fresh()->imageData('image');

    expect($data['url'])->not->toContain('?')
        ->and($data['thumb'])->not->toContain('?')
        ->and($data['srcset'])->not->toContain('?')
        ->and(strlen($owner->fresh()->image_url))->toBeLessThanOrEqual(125);
});
