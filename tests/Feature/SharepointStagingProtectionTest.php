<?php

use App\Exceptions\StagingResourceReadOnlyException;
use App\Models\Document;
use App\Services\SharepointGraphService;
use Illuminate\Http\UploadedFile;

beforeEach(function (): void {
    $this->originalEnvironment = config('app.env');
    $this->originalSharepointReadOnly = config('app.sharepoint_read_only');

    config([
        'app.env' => 'staging',
        'app.sharepoint_read_only' => true,
    ]);
});

afterEach(function (): void {
    config([
        'app.env' => $this->originalEnvironment,
        'app.sharepoint_read_only' => $this->originalSharepointReadOnly,
    ]);
});

test('every direct SharePoint mutator refuses to run in staging', function (): void {
    $service = (new ReflectionClass(SharepointGraphService::class))->newInstanceWithoutConstructor();
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

    foreach ($operations as $operation) {
        expect($operation)->toThrow(StagingResourceReadOnlyException::class);
    }
});

test('a read only batch import preserves an existing local public link when SharePoint has none', function (): void {
    $document = new Document;
    $document->sharepoint_id = 'item';
    $document->title = 'Document';
    $document->anonymous_url = 'https://example.sharepoint.com/:b:/existing';
    $document->sharepoint_permission_id = 'permission';

    $service = (new ReflectionClass(SharepointGraphService::class))->newInstanceWithoutConstructor();
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

    $service = (new ReflectionClass(SharepointGraphService::class))->newInstanceWithoutConstructor();
    $method = new ReflectionMethod(SharepointGraphService::class, 'applyImportedPublicLink');
    $method->invoke($service, $document, null);

    expect($document->anonymous_url)->toBeNull()
        ->and($document->sharepoint_permission_id)->toBeNull()
        ->and($document->sync_status)->toBe('failed');
});
