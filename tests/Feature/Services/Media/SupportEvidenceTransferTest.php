<?php

use App\Exceptions\StagingResourceReadOnlyException;
use App\Models\SupportRequest;
use App\Services\Media\SupportEvidenceTransfer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Storage::fake('spatieMediaLibrary');
    Storage::fake('supportEvidence');
    $owner = SupportRequest::factory()->create();
    $owner->delete();
    $this->media = $owner->media()->create([
        'collection_name' => 'evidence',
        'name' => 'Screenshot',
        'file_name' => 'screenshot.png',
        'mime_type' => 'image/png',
        'disk' => 'spatieMediaLibrary',
        'conversions_disk' => 'spatieMediaLibrary',
        'size' => 8,
        'manipulations' => [],
        'custom_properties' => [],
        'generated_conversions' => ['thumb' => true],
        'responsive_images' => ['media_library_original' => ['urls' => ['screenshot___media_library_original_10_10.png']]],
    ]);
    $this->paths = [
        $this->media->getPathRelativeToRoot(),
        $this->media->id.'/previous-screenshot.png',
        $this->media->id.'/conversions/screenshot-thumb.webp',
        $this->media->id.'/responsive-images/screenshot___media_library_original_10_10.png',
    ];
    foreach ($this->paths as $path) {
        Storage::disk('spatieMediaLibrary')->put($path, 'contents:'.$path);
    }
});

test('the command copies originals and derived files while keeping ids and public copies until cleanup', function (): void {
    $this->artisan('media:privatize-evidence', ['--sync' => true])->assertSuccessful();

    $media = $this->media->fresh();
    expect($media->disk)->toBe('supportEvidence')
        ->and($media->conversions_disk)->toBe('supportEvidence')
        ->and($media->generated_conversions)->toBe(['thumb' => true])
        ->and(Media::query()->count())->toBe(1);
    foreach ($this->paths as $path) {
        Storage::disk('spatieMediaLibrary')->assertExists($path);
        expect(Storage::disk('supportEvidence')->get($path))->toBe('contents:'.$path);
    }

    $this->artisan('media:privatize-evidence', ['--sync' => true, '--cleanup' => true, '--force' => true])->assertSuccessful();
    $this->artisan('media:privatize-evidence', ['--sync' => true, '--cleanup' => true, '--force' => true])->assertSuccessful();

    foreach ($this->paths as $path) {
        Storage::disk('spatieMediaLibrary')->assertMissing($path);
        Storage::disk('supportEvidence')->assertExists($path);
    }
    expect(app(SupportEvidenceTransfer::class)->hasPublicCopies($media->fresh()))->toBeFalse();
});

test('a missing original leaves the media on its existing disk', function (): void {
    Storage::disk('spatieMediaLibrary')->delete($this->paths[0]);

    expect(fn () => app(SupportEvidenceTransfer::class)->transfer($this->media->id))->toThrow(RuntimeException::class)
        ->and($this->media->fresh()->disk)->toBe('spatieMediaLibrary');
});

test('cleanup retains public files when the private copy has changed', function (): void {
    $transfer = app(SupportEvidenceTransfer::class);
    $transfer->transfer($this->media->id);
    Storage::disk('supportEvidence')->put($this->paths[0], 'changed');

    expect(fn () => $transfer->transfer($this->media->id, cleanup: true))->toThrow(RuntimeException::class);
    Storage::disk('spatieMediaLibrary')->assertExists($this->paths);
});

test('staging copies evidence into its own private disk without deleting shared files', function (): void {
    config(['app.env' => 'staging', 'app.files_read_only' => true]);
    $transfer = app(SupportEvidenceTransfer::class);
    $transfer->transfer($this->media->id);

    expect(fn () => $transfer->transfer($this->media->id, cleanup: true))
        ->toThrow(StagingResourceReadOnlyException::class);
    Storage::disk('spatieMediaLibrary')->assertExists($this->paths);
    Storage::disk('supportEvidence')->assertExists($this->paths);
});

test('cleanup previews public files and leaves them when declined', function (bool $alreadyCopied): void {
    if ($alreadyCopied) {
        app(SupportEvidenceTransfer::class)->transfer($this->media->id);
    }

    $command = $this->artisan('media:privatize-evidence', ['--sync' => true, '--cleanup' => true])
        ->expectsConfirmation('Delete 4 public evidence file(s)?', 'no');
    foreach ($this->paths as $path) {
        $command->expectsOutputToContain($path);
    }
    $command->assertSuccessful()->execute();

    Storage::disk('spatieMediaLibrary')->assertExists($this->paths);
    expect($this->media->fresh()->disk)->toBe($alreadyCopied ? 'supportEvidence' : 'spatieMediaLibrary');
    if (! $alreadyCopied) {
        Storage::disk('supportEvidence')->assertDirectoryEmpty('/');
    }
})->with([false, true]);

test('confirmed cleanup removes the listed public files', function (): void {
    $command = $this->artisan('media:privatize-evidence', ['--sync' => true, '--cleanup' => true])
        ->expectsConfirmation('Delete 4 public evidence file(s)?', 'yes');
    foreach ($this->paths as $path) {
        $command->expectsOutputToContain($path);
    }
    $command->assertSuccessful()->execute();

    Storage::disk('spatieMediaLibrary')->assertMissing($this->paths);
    Storage::disk('supportEvidence')->assertExists($this->paths);
    expect(app(SupportEvidenceTransfer::class)->hasPublicCopies($this->media->fresh()))->toBeFalse();
});

test('force alone keeps public copies', function (): void {
    $this->artisan('media:privatize-evidence', ['--sync' => true, '--force' => true])->assertSuccessful();

    Storage::disk('spatieMediaLibrary')->assertExists($this->paths);
    Storage::disk('supportEvidence')->assertExists($this->paths);
    expect(app(SupportEvidenceTransfer::class)->hasPublicCopies($this->media->fresh()))->toBeTrue();
});

test('noninteractive cleanup requires explicit force', function (): void {
    $this->artisan('media:privatize-evidence', ['--sync' => true, '--cleanup' => true, '--no-interaction' => true])
        ->expectsOutputToContain('Public-copy cleanup requires confirmation or --force.')
        ->assertFailed();

    Storage::disk('spatieMediaLibrary')->assertExists($this->paths);
    Storage::disk('supportEvidence')->assertDirectoryEmpty('/');
    expect($this->media->fresh()->disk)->toBe('spatieMediaLibrary');
});
