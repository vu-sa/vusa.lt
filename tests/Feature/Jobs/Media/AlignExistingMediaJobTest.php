<?php

use App\Actions\Media\AddImageMedia;
use App\Jobs\Media\AlignExistingMediaJob;
use App\Models\Calendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

pest()->use(RefreshDatabase::class);

/**
 * A calendar main image as it looks before the shared conversions: focal point in the column,
 * no dimensions, an unused `webp` conversion and responsive images on disk.
 */
function legacyCalendarImage(): Media
{
    $calendar = Calendar::factory()->create();
    $calendar->forceFill(['main_image_focal_point' => '30% 70%'])->saveQuietly();

    $media = app(AddImageMedia::class)->execute($calendar, UploadedFile::fake()->image('event.jpg', 1000, 500), 'main_image');
    $media->forgetCustomProperty('width')->forgetCustomProperty('height');
    $media->responsive_images = ['media_library_original' => ['urls' => ['event___media_library_original_400_200.webp'], 'base64svg' => '']];
    $media->save();

    $paths = PathGeneratorFactory::create($media);
    Storage::disk($media->disk)->put($paths->getPathForConversions($media).'event-webp.webp', 'old');
    Storage::disk($media->disk)->put($paths->getPathForResponsiveImages($media).'event___media_library_original_400_200.webp', 'old');

    return $media;
}

test('existing media gets its focal point, dimensions and only the shared conversions', function (): void {
    Storage::fake('spatieMediaLibrary');
    $media = legacyCalendarImage();
    $paths = PathGeneratorFactory::create($media);

    config(['media-library.queue_conversions_by_default' => true]);
    Bus::fake();
    (new AlignExistingMediaJob([$media->id]))->handle(app(FileManipulator::class));
    Bus::assertNotDispatched(PerformConversionsJob::class);
    expect(config('media-library.queue_conversions_by_default'))->toBeTrue();

    $media->refresh();
    $disk = Storage::disk('spatieMediaLibrary');

    expect($media->getCustomProperty('focal_point'))->toBe('30% 70%')
        ->and($media->getCustomProperty('width'))->toBe(1000)
        ->and($media->responsive_images)->toBe([])
        ->and(array_keys(array_filter($media->generated_conversions)))->toEqualCanonicalizing(['thumb', 'medium', 'large']);
    $disk->assertMissing($paths->getPathForConversions($media).'event-webp.webp');
    $disk->assertMissing($paths->getPathForResponsiveImages($media).'event___media_library_original_400_200.webp');
});

test('on staging the focal point moves but production\'s files are not touched', function (): void {
    Storage::fake('spatieMediaLibrary');
    $media = legacyCalendarImage();
    $paths = PathGeneratorFactory::create($media);
    config(['app.env' => 'staging', 'app.files_read_only' => true]);

    (new AlignExistingMediaJob([$media->id]))->handle(app(FileManipulator::class));

    expect($media->refresh()->getCustomProperty('focal_point'))->toBe('30% 70%');
    Storage::disk('spatieMediaLibrary')->assertExists($paths->getPathForConversions($media).'event-webp.webp');
    Storage::disk('spatieMediaLibrary')->assertExists($paths->getPathForResponsiveImages($media).'event___media_library_original_400_200.webp');
});

test('failed conversion generation preserves the old derived files and restores queue settings', function (): void {
    Storage::fake('spatieMediaLibrary');
    $media = legacyCalendarImage();
    $paths = PathGeneratorFactory::create($media);
    config(['media-library.queue_conversions_by_default' => true]);
    $manipulator = Mockery::mock(FileManipulator::class);
    $manipulator->shouldReceive('createDerivedFiles')->once()->andThrow(new RuntimeException('conversion failed'));
    (new AlignExistingMediaJob([$media->id]))->handle($manipulator);
    expect(config('media-library.queue_conversions_by_default'))->toBeTrue()
        ->and($media->refresh()->responsive_images)->not->toBeEmpty();
    Storage::disk('spatieMediaLibrary')->assertExists($paths->getPathForConversions($media).'event-webp.webp');
    Storage::disk('spatieMediaLibrary')->assertExists($paths->getPathForResponsiveImages($media).'event___media_library_original_400_200.webp');
});
