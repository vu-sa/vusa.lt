<?php

use App\Services\StagingMediaStore;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

beforeEach(function (): void {
    $this->root = public_path('staging-media/test-'.Str::random(8));
    File::ensureDirectoryExists($this->root);
    config(['filesystems.disks.stagingMedia.root' => $this->root]);
});

afterEach(function (): void {
    File::deleteDirectory($this->root);
});

test('it empties its own folder inside public', function (): void {
    File::ensureDirectoryExists($this->root.'/12/conversions');
    File::put($this->root.'/12/conversions/photo-thumb.webp', 'x');

    expect(app(StagingMediaStore::class)->wipe())->toBeTrue()
        ->and(File::isDirectory($this->root))->toBeTrue()
        ->and(File::allFiles($this->root))->toBeEmpty();
});

test('it refuses a root that resolves outside the public directory', function (): void {
    $outside = sys_get_temp_dir().'/staging-media-outside-'.Str::random(8);
    File::ensureDirectoryExists($outside);
    File::put($outside.'/production.webp', 'x');
    symlink($outside, $this->root.'/link');
    config(['filesystems.disks.stagingMedia.root' => $this->root.'/link']);

    $store = app(StagingMediaStore::class);

    expect($store->rootError())->toContain('inside this application\'s public directory')
        ->and($store->wipe())->toBeFalse()
        ->and(File::exists($outside.'/production.webp'))->toBeTrue();

    File::deleteDirectory($outside);
});

test('a missing root needs no wiping', function (): void {
    config(['filesystems.disks.stagingMedia.root' => $this->root.'/missing']);

    expect(app(StagingMediaStore::class)->wipe())->toBeTrue();
});
