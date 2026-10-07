<?php

use App\Services\FileUploadWriter;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

test('same-second collisions preserve every file and its readable name', function (): void {
    Storage::fake();
    $this->freezeTime();
    $writer = app(FileUploadWriter::class);

    $first = $writer->write('public/files/reports', 'ataskaita.pdf', 'first');
    $second = $writer->write('public/files/reports', 'ataskaita.pdf', 'second');
    $third = $writer->write('public/files/reports', 'ataskaita.pdf', 'third');

    expect($first['name'])->toBe('ataskaita.pdf');
    expect($second['name'])->toBe('ataskaita_'.now()->timestamp.'.pdf');
    expect($third['name'])->toBe('ataskaita_'.now()->timestamp.'_2.pdf');
    expect(Storage::get($first['path']))->toBe('first');
    expect(Storage::get($second['path']))->toBe('second');
    expect(Storage::get($third['path']))->toBe('third');
    expect($first['url'])->toBe('/uploads/files/reports/ataskaita.pdf');
});

test('filenames without an extension keep readable collision suffixes', function (): void {
    Storage::fake();
    $this->freezeTime();
    $writer = app(FileUploadWriter::class);
    $writer->write('public/files/reports', 'README', 'original');

    $stored = $writer->write('public/files/reports', 'README', 'new');

    expect($stored['name'])->toBe('README_'.now()->timestamp);
    expect(Storage::get('public/files/reports/README'))->toBe('original');
});

test('unsafe upload filenames cannot write outside the destination', function (string $filename): void {
    $disk = Storage::fake();

    expect(fn () => app(FileUploadWriter::class)->write('public/files/reports', $filename, 'payload'))
        ->toThrow(InvalidArgumentException::class);
    $disk->assertDirectoryEmpty('/');
})->with(['../escape.txt', 'sub/file.txt', 'sub\\file.txt', '.', '..', "bad\x00.txt"]);

test('failed writes throw and release the directory lock', function (bool $uploadedFile, bool $throws): void {
    $disk = Storage::fake();
    $failingDisk = Mockery::mock(FilesystemAdapter::class);
    $failingDisk->shouldReceive('exists')->once()->andReturnFalse();
    $write = $failingDisk->shouldReceive($uploadedFile ? 'putFileAs' : 'put')->once();
    if ($throws) {
        $write->andThrow(new RuntimeException('Disk unavailable'));
    } else {
        $write->andReturnFalse();
    }
    Storage::set(Storage::getDefaultDriver(), $failingDisk);
    $contents = $uploadedFile ? UploadedFile::fake()->create('report.txt', 1, 'text/plain') : 'contents';
    $key = 'file-upload:'.hash('sha256', Storage::getDefaultDriver().'|public/files/reports');

    expect(fn () => app(FileUploadWriter::class)->write('public/files/reports', 'report.txt', $contents))
        ->toThrow(RuntimeException::class);

    $lock = Cache::lock($key, 120);
    expect($lock->get())->toBeTrue();
    $lock->release();
    $disk->assertMissing('public/files/reports/report.txt');
})->with([
    'encoded false write' => [false, false],
    'uploaded false write' => [true, false],
    'encoded exception' => [false, true],
    'uploaded exception' => [true, true],
]);

test('a lock timeout does not write a file', function (): void {
    $disk = Storage::fake();
    $lock = Mockery::mock(Lock::class);
    $lock->shouldReceive('block')->with(5, Mockery::type(Closure::class))
        ->once()->andThrow(new LockTimeoutException);
    Cache::shouldReceive('lock')->with('file-upload:'.hash('sha256', Storage::getDefaultDriver().'|public/files/reports'), 120)
        ->once()->andReturn($lock);

    expect(fn () => app(FileUploadWriter::class)->write('public/files/reports', 'report.txt', 'contents'))
        ->toThrow(LockTimeoutException::class);
    $disk->assertMissing('public/files/reports/report.txt');
});
