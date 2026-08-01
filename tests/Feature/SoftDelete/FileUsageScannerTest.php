<?php

use App\Models\Banner;
use App\Services\FileUsageScanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

pest()->use(RefreshDatabase::class);

/**
 * The scanner drives an "is safe to delete" badge that admins act on by removing the
 * file from storage. Its queries used to be soft-delete scoped, so a file referenced
 * only by a trashed banner or article was reported unused — deleting it left the record
 * permanently broken the moment it was restored.
 */
beforeEach(function (): void {
    Cache::flush();
    $this->scanner = app(FileUsageScanner::class);
    $this->imagePath = 'uploads/files/soft-delete-scanner-fixture.jpg';
});

test('a file used only by a trashed record is not reported safe to delete', function (): void {
    $banner = Banner::factory()->create(['image_url' => $this->imagePath]);
    $banner->delete();

    $result = $this->scanner->scanFileUsage($this->imagePath);

    expect($result['is_safe_to_delete'])->toBeFalse()
        ->and($result['total_usages'])->toBeGreaterThan(0);
});

test('a file used by a live record is still reported as in use', function (): void {
    Banner::factory()->create(['image_url' => $this->imagePath]);

    $result = $this->scanner->scanFileUsage($this->imagePath);

    expect($result['is_safe_to_delete'])->toBeFalse();
});

test('a genuinely unreferenced file is still reported safe to delete', function (): void {
    $result = $this->scanner->scanFileUsage('uploads/files/nothing-references-this.jpg');

    expect($result['is_safe_to_delete'])->toBeTrue()
        ->and($result['total_usages'])->toBe(0);
});
