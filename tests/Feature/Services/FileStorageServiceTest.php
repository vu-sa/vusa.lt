<?php

use App\Exceptions\StagingResourceReadOnlyException;
use App\Services\FileStorageService;
use App\Services\FileUploadWriter;
use App\Services\ImageUploadService;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

test('file paths retain relative fragments and Unicode names', function (string $path, string $expected): void {
    expect(app(FileStorageService::class)->normalizeFilePath($path))->toBe($expected);
})->with([
    'root' => ['public/files', 'public/files'],
    'relative' => ['reports/2026', 'public/files/reports/2026'],
    'separators' => ['reports\\2026//', 'public/files/reports/2026'],
    'punctuation' => ['public/files/Tyrimai, [2026] – „vasara“ ©', 'public/files/Tyrimai, [2026] – „vasara“ ©'],
]);

test('invalid paths cannot escape or imitate the file-manager root', function (string $path): void {
    expect(fn () => app(FileStorageService::class)->normalizeFilePath($path))
        ->toThrow(InvalidArgumentException::class);
})->with(['', '.', '..', '../outside', 'public/files/../outside', 'public/files-backup', 'public/news', '/etc/passwd', "public/files/bad\x01", "public/files/bad\xff"]);

test('listing naturally sorts names and exposes working public URLs', function (): void {
    Storage::fake();
    Storage::put('public/files/reports/10. ataskaita.txt', 'ten');
    Storage::put('public/files/reports/2. ataskaita.txt', 'two');
    Storage::put('public/files/reports/photo.PNG', 'image');
    Storage::makeDirectory('public/files/reports/10. folder');
    Storage::makeDirectory('public/files/reports/2. folder');

    $listing = app(FileStorageService::class)->listDirectory('public/files/reports', ['txt']);

    expect(array_column($listing['files'], 'name'))->toBe(['2. ataskaita.txt', '10. ataskaita.txt']);
    expect(array_column($listing['directories'], 'name'))->toBe(['2. folder', '10. folder']);
    expect($listing['path'])->toBe('public/files/reports');
    expect($listing['files'][0]['url'])->toBe('/uploads/files/reports/2. ataskaita.txt');
    expect($listing['files'][0])->toHaveKeys(['path', 'name', 'type', 'size', 'modified', 'mimeType', 'url']);
});

test('batch storage preserves successful files when one image cannot be decoded', function (): void {
    Storage::fake();
    $files = [
        UploadedFile::fake()->create('broken.png', 1, 'image/png'),
        UploadedFile::fake()->createWithContent('report.txt', 'good'),
    ];

    $result = app(FileStorageService::class)->storeMany($files, 'public/files/reports');

    expect(array_column($result['uploaded'], 'name'))->toBe(['report.txt']);
    expect($result['failed'])->toBe([['name' => 'broken.png', 'reason' => __('files.errors.upload_failed')]]);
    Storage::assertMissing('public/files/reports/broken.webp');
    expect(Storage::get('public/files/reports/report.txt'))->toBe('good');
});

test('GIF and SVG batch uploads retain their original bytes', function (): void {
    Storage::fake();
    $files = [
        UploadedFile::fake()->createWithContent('animated.gif', 'GIF89a original animation'),
        UploadedFile::fake()->createWithContent('vector.svg', '<svg xmlns="http://www.w3.org/2000/svg"/>'),
    ];

    $result = app(FileStorageService::class)->storeMany($files, 'public/files/images');

    expect(array_column($result['uploaded'], 'name'))->toBe(['animated.gif', 'vector.svg']);
    expect(Storage::get('public/files/images/animated.gif'))->toBe('GIF89a original animation');
    expect(Storage::get('public/files/images/vector.svg'))->toBe('<svg xmlns="http://www.w3.org/2000/svg"/>');
});

test('converted images and single-image uploads share collision protection', function (): void {
    Storage::fake();
    $this->freezeTime();
    Storage::put('public/files/images/picture.webp', 'original');

    $batch = app(FileStorageService::class)->storeMany([
        UploadedFile::fake()->image('picture.png', 10, 10),
        UploadedFile::fake()->image('picture.jpg', 10, 10),
    ], 'public/files/images');
    $single = app(ImageUploadService::class)->processAndSave(
        UploadedFile::fake()->image('picture.png', 10, 10), 'public/files/images'
    );

    expect(array_column($batch['uploaded'], 'name'))->toBe([
        'picture_'.now()->timestamp.'.webp', 'picture_'.now()->timestamp.'_2.webp',
    ]);
    expect($single['name'])->toBe('picture_'.now()->timestamp.'_3.webp');
    expect(Storage::get('public/files/images/picture.webp'))->toBe('original');
    Storage::assertExists($single['path']);
});

test('a false image write is reported as a batch failure', function (): void {
    $disk = Storage::fake();
    $failingDisk = Mockery::mock(FilesystemAdapter::class);
    $failingDisk->shouldReceive('exists')->once()->andReturnFalse();
    $failingDisk->shouldReceive('put')->once()->andReturnFalse();
    Storage::set(Storage::getDefaultDriver(), $failingDisk);

    $result = app(FileStorageService::class)->storeMany([
        UploadedFile::fake()->image('photo.png', 10, 10),
    ], 'public/files/images');

    expect($result['uploaded'])->toBe([]);
    expect($result['failed'])->toBe([['name' => 'photo.png', 'reason' => __('files.errors.upload_failed')]]);
    $disk->assertMissing('public/files/images/photo.webp');
});

test('lock contention is a localized per-file batch failure', function (): void {
    Storage::fake();
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->once()->andThrow(new LockTimeoutException);
    Cache::shouldReceive('lock')->once()->andReturn($lock);

    $result = app(FileStorageService::class)->storeMany([
        UploadedFile::fake()->create('report.txt', 1, 'text/plain'),
    ], 'public/files/reports');

    expect($result)->toBe(['uploaded' => [], 'failed' => [
        ['name' => 'report.txt', 'reason' => __('files.errors.upload_busy')],
    ]]);
    Storage::assertMissing('public/files/reports/report.txt');
});

test('staging protection refuses the batch before any file is processed', function (): void {
    Storage::fake();
    config(['app.env' => 'staging', 'app.files_read_only' => true]);
    $writer = Mockery::mock(FileUploadWriter::class);
    $writer->shouldNotReceive('write');
    $this->app->instance(FileUploadWriter::class, $writer);

    expect(fn () => app(FileStorageService::class)->storeMany([
        UploadedFile::fake()->create('report.txt', 1, 'text/plain'),
    ], 'public/files/reports'))->toThrow(StagingResourceReadOnlyException::class);
    Storage::assertMissing('public/files/reports/report.txt');
});
