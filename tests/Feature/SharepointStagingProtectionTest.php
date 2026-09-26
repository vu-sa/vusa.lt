<?php

use App\Exceptions\StagingResourceReadOnlyException;
use App\Models\Document;
use App\Services\SharepointGraphService;
use App\Support\StagingProtection;
use Illuminate\Http\UploadedFile;

/** Skips the constructor's Graph calls; the guards only need the target site and drive. */
function sharepointServiceWithoutGraph(): SharepointGraphService
{
    $service = new ReflectionClass(SharepointGraphService::class)->newInstanceWithoutConstructor();
    $service->siteId = 'site';
    new ReflectionProperty(SharepointGraphService::class, 'driveId')->setValue($service, 'drive');

    return $service;
}

beforeEach(function (): void {
    $this->originalEnvironment = config('app.env');
    $this->originalSharepointReadOnly = config('app.sharepoint_read_only');
    $this->originalWritableSiteIds = config('filesystems.sharepoint.writable_site_ids');

    config([
        'app.env' => 'staging',
        'app.sharepoint_read_only' => true,
    ]);
});

afterEach(function (): void {
    config([
        'app.env' => $this->originalEnvironment,
        'app.sharepoint_read_only' => $this->originalSharepointReadOnly,
        'filesystems.sharepoint.writable_site_ids' => $this->originalWritableSiteIds,
    ]);
});

test('a writable staging only writes to an allowlisted site outside production drives', function (?string $siteId, ?string $driveId, bool $readOnly): void {
    config([
        'app.sharepoint_read_only' => false,
        'filesystems.sharepoint.writable_site_ids' => ['test-site'],
    ]);

    expect(StagingProtection::sharepointIsReadOnly($siteId, $driveId))->toBe($readOnly);
})->with([
    'test site' => ['test-site', 'test-drive', false],
    'production site' => ['cbb85cec-3f73-4867-8101-446082b58722', 'test-drive', true],
    'production archive drive' => ['test-site', 'b!pMfaXjYdIEy8zqO3LWICz9geSweNHJhMi7VW4z5KDW0k2jqzC_i8TaX9RPnDbkJq', true],
    'unknown target' => [null, null, true],
]);

test('the global read-only flag blocks even the test site', function (): void {
    config(['filesystems.sharepoint.writable_site_ids' => ['test-site']]);

    expect(fn () => StagingProtection::ensureSharepointIsWritable('test-site', 'test-drive'))
        ->toThrow(StagingResourceReadOnlyException::class);
});

test('every direct SharePoint mutator refuses to run in staging', function (): void {
    $service = sharepointServiceWithoutGraph();
    $file = UploadedFile::fake()->create('document.pdf');

    $operations = [
        fn () => $service->updateDriveItemByPath('folder', ['name' => 'renamed']),
        fn () => $service->updateListItem('list', 'item', ['Title' => 'Changed']),
        fn () => $service->createPublicPermission('site', 'item'),
        fn () => $service->deletePermission('item', 'permission'),
        fn () => $service->uploadDriveItem('folder/document.pdf', $file),
        fn () => $service->deleteDriveItem('item'),
        fn () => $service->createFolder('parent/child'),
        fn () => $service->uploadUrlShortcut('folder/link.url', '[InternetShortcut]'),
    ];

    expect($operations)->each->toThrow(StagingResourceReadOnlyException::class);
});

test('a read only batch import preserves an existing local public link when SharePoint has none', function (): void {
    $document = new Document;
    $document->sharepoint_id = 'item';
    $document->title = 'Document';
    $document->anonymous_url = 'https://example.sharepoint.com/:b:/existing';
    $document->sharepoint_permission_id = 'permission';

    $service = sharepointServiceWithoutGraph();
    $method = new ReflectionMethod(SharepointGraphService::class, 'applyImportedPublicLink');
    $method->invoke($service, $document, null);

    expect($document->anonymous_url)->toBe('https://example.sharepoint.com/:b:/existing')
        ->and($document->sharepoint_permission_id)->toBe('permission')
        ->and($document->sync_status)->toBe('failed');
});

test('a production batch import clears an obsolete local public link when SharePoint has none', function (): void {
    config([
        'app.env' => 'production',
        'app.sharepoint_read_only' => false,
    ]);

    $document = new Document;
    $document->sharepoint_id = 'item';
    $document->title = 'Document';
    $document->anonymous_url = 'https://example.sharepoint.com/:b:/obsolete';
    $document->sharepoint_permission_id = 'permission';

    $service = sharepointServiceWithoutGraph();
    $method = new ReflectionMethod(SharepointGraphService::class, 'applyImportedPublicLink');
    $method->invoke($service, $document, null);

    expect($document->anonymous_url)->toBeNull()
        ->and($document->sharepoint_permission_id)->toBeNull()
        ->and($document->sync_status)->toBe('failed');
});
