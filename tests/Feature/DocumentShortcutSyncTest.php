<?php

use App\Models\Document;
use App\Services\DocumentSharepointSyncService;
use App\Services\SharepointGraphService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Microsoft\Graph\Generated\Models\DriveItem;
use Microsoft\Graph\Generated\Models\FieldValueSet;
use Microsoft\Graph\Generated\Models\Permission;
use Microsoft\Graph\Generated\Models\SharingLink;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Completely disable Scout indexing for tests to avoid extra job queuing
    config(['scout.driver' => null]);
    config(['scout.queue' => false]);
    config(['scout.after_commit' => false]);
});

afterEach(function () {
    Mockery::close();
});

/**
 * Build a partial mock of the sync service with its Graph service factory
 * stubbed out, so no live SharePoint connection is ever attempted.
 */
function mockSyncServiceWithGraph(SharepointGraphService $graph): DocumentSharepointSyncService
{
    $service = Mockery::mock(DocumentSharepointSyncService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $service->shouldReceive('makeGraphService')->andReturn($graph);

    return $service;
}

function makeFieldValueSet(array $additionalData): FieldValueSet
{
    $fieldValueSet = new FieldValueSet;
    $fieldValueSet->setAdditionalData($additionalData);

    return $fieldValueSet;
}

function makeDriveItem(string $id, ?string $name = null): DriveItem
{
    $driveItem = new DriveItem;
    $driveItem->setId($id);
    if ($name !== null) {
        $driveItem->setName($name);
    }

    return $driveItem;
}

function makePermission(string $id, string $webUrl): Permission
{
    $link = new SharingLink;
    $link->setWebUrl($webUrl);

    $permission = new Permission;
    $permission->setId($id);
    $permission->setLink($link);

    return $permission;
}

describe('.url shortcut resolution during sync', function () {
    test('resolves and persists the link_url for a .url shortcut document', function () {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'eTag' => 'old-etag',
            'anonymous_url' => null,
            'link_url' => null,
        ]);

        $driveItem = makeDriveItem('drive-1', 'ataskaita2023.vusa.lt.url');

        $graph = Mockery::mock(SharepointGraphService::class);
        $graph->shouldReceive('getListItem')->once()->andReturn(makeFieldValueSet([
            '@odata.etag' => 'new-etag',
            'Name' => 'ataskaita2023.vusa.lt.url',
            'Title' => 'VU SA Veiklos ataskaita 2022-2023 m. m',
        ]));
        $graph->shouldReceive('getDriveItemByListItem')->once()->andReturn($driveItem);
        $graph->shouldReceive('getDriveItemContent')->once()->with('drive-1')
            ->andReturn("[InternetShortcut]\r\nURL=https://ataskaita2023.vusa.lt\r\n");
        $graph->shouldReceive('getDriveItemPublicLink')->once()->andReturn(null);
        $graph->shouldReceive('createPublicPermission')->once()
            ->andReturn(makePermission('perm-1', 'https://sharepoint.example.com/shortcut/123'));

        $service = mockSyncServiceWithGraph($graph);
        $result = $service->sync($document);

        expect($result)->not->toBeNull();
        expect($result->link_url)->toBe('https://ataskaita2023.vusa.lt');
        expect($result->anonymous_url)->toBe('https://sharepoint.example.com/shortcut/123');
        expect($result->sync_status)->toBe('success');
    });

    test('does not attempt to resolve a shortcut target for a normal document', function () {
        $document = Document::factory()->create([
            'name' => 'protokolas.pdf',
            'eTag' => 'old-etag',
            'anonymous_url' => 'https://sharepoint.example.com/document/1',
            'link_url' => null,
        ]);

        $driveItem = makeDriveItem('drive-2', 'protokolas.pdf');

        $graph = Mockery::mock(SharepointGraphService::class);
        $graph->shouldReceive('getListItem')->once()->andReturn(makeFieldValueSet([
            '@odata.etag' => 'new-etag',
            'Name' => 'protokolas.pdf',
        ]));
        $graph->shouldReceive('getDriveItemByListItem')->once()->andReturn($driveItem);
        $graph->shouldNotReceive('getDriveItemContent');
        $graph->shouldReceive('getDriveItemPublicLink')->once()
            ->andReturn(makePermission('perm-2', 'https://sharepoint.example.com/document/1'));

        $service = mockSyncServiceWithGraph($graph);
        $result = $service->sync($document);

        expect($result)->not->toBeNull();
        expect($result->link_url)->toBeNull();
        expect($result->sync_status)->toBe('success');
    });

    test('leaves link_url unchanged and sync succeeds when shortcut content is unparseable', function () {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'eTag' => 'old-etag',
            'anonymous_url' => null,
            'link_url' => null,
        ]);

        $driveItem = makeDriveItem('drive-3', 'ataskaita2023.vusa.lt.url');

        $graph = Mockery::mock(SharepointGraphService::class);
        $graph->shouldReceive('getListItem')->once()->andReturn(makeFieldValueSet([
            '@odata.etag' => 'new-etag',
            'Name' => 'ataskaita2023.vusa.lt.url',
        ]));
        $graph->shouldReceive('getDriveItemByListItem')->once()->andReturn($driveItem);
        $graph->shouldReceive('getDriveItemContent')->once()->andReturn('not a valid shortcut file');
        $graph->shouldReceive('getDriveItemPublicLink')->once()
            ->andReturn(makePermission('perm-3', 'https://sharepoint.example.com/shortcut/3'));

        $service = mockSyncServiceWithGraph($graph);
        $result = $service->sync($document);

        expect($result)->not->toBeNull();
        expect($result->link_url)->toBeNull();
        expect($result->sync_status)->toBe('success');
    });

    test('sync still succeeds and keeps the previous link_url when content fetch throws', function () {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'eTag' => 'old-etag',
            'anonymous_url' => 'https://sharepoint.example.com/shortcut/4',
            'link_url' => null,
        ]);

        $driveItem = makeDriveItem('drive-4', 'ataskaita2023.vusa.lt.url');

        $graph = Mockery::mock(SharepointGraphService::class);
        $graph->shouldReceive('getListItem')->once()->andReturn(makeFieldValueSet([
            '@odata.etag' => 'new-etag',
            'Name' => 'ataskaita2023.vusa.lt.url',
        ]));
        $graph->shouldReceive('getDriveItemByListItem')->once()->andReturn($driveItem);
        $graph->shouldReceive('getDriveItemContent')->once()->andThrow(new Exception('Graph request failed'));
        $graph->shouldReceive('getDriveItemPublicLink')->once()
            ->andReturn(makePermission('perm-4', 'https://sharepoint.example.com/shortcut/4'));

        $service = mockSyncServiceWithGraph($graph);
        $result = $service->sync($document);

        expect($result)->not->toBeNull();
        expect($result->link_url)->toBeNull();
        expect($result->sync_status)->toBe('success');
    });

    test('clears link_url when a document is renamed away from .url upstream', function () {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'eTag' => 'old-etag',
            'anonymous_url' => 'https://sharepoint.example.com/shortcut/5',
            'link_url' => 'https://ataskaita2023.vusa.lt',
        ]);

        $driveItem = makeDriveItem('drive-5', 'ataskaita2023.pdf');

        $graph = Mockery::mock(SharepointGraphService::class);
        $graph->shouldReceive('getListItem')->once()->andReturn(makeFieldValueSet([
            '@odata.etag' => 'new-etag',
            'Name' => 'ataskaita2023.pdf',
        ]));
        $graph->shouldReceive('getDriveItemByListItem')->once()->andReturn($driveItem);
        $graph->shouldNotReceive('getDriveItemContent');
        $graph->shouldReceive('getDriveItemPublicLink')->once()
            ->andReturn(makePermission('perm-5', 'https://sharepoint.example.com/shortcut/5'));

        $service = mockSyncServiceWithGraph($graph);
        $result = $service->sync($document);

        expect($result)->not->toBeNull();
        expect($result->name)->toBe('ataskaita2023.pdf');
        expect($result->link_url)->toBeNull();
    });

    test('does not short-circuit on matching eTag when the shortcut target is still unresolved', function () {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'eTag' => 'same-etag',
            'anonymous_url' => 'https://sharepoint.example.com/shortcut/6',
            'link_url' => null,
        ]);

        $driveItem = makeDriveItem('drive-6', 'ataskaita2023.vusa.lt.url');

        $graph = Mockery::mock(SharepointGraphService::class);
        $graph->shouldReceive('getListItem')->once()->andReturn(makeFieldValueSet([
            '@odata.etag' => 'same-etag',
            'Name' => 'ataskaita2023.vusa.lt.url',
        ]));
        // A full pass must run despite the eTag match, since link_url is still null.
        $graph->shouldReceive('getDriveItemByListItem')->once()->andReturn($driveItem);
        $graph->shouldReceive('getDriveItemContent')->once()
            ->andReturn("[InternetShortcut]\r\nURL=https://ataskaita2023.vusa.lt\r\n");
        $graph->shouldReceive('getDriveItemPublicLink')->once()
            ->andReturn(makePermission('perm-6', 'https://sharepoint.example.com/shortcut/6'));

        $service = mockSyncServiceWithGraph($graph);
        $result = $service->sync($document);

        expect($result)->not->toBeNull();
        expect($result->link_url)->toBe('https://ataskaita2023.vusa.lt');
    });

    test('short-circuits on matching eTag once the shortcut target is already resolved', function () {
        $document = Document::factory()->create([
            'name' => 'ataskaita2023.vusa.lt.url',
            'eTag' => 'same-etag',
            'anonymous_url' => 'https://sharepoint.example.com/shortcut/7',
            'link_url' => 'https://ataskaita2023.vusa.lt',
        ]);

        $graph = Mockery::mock(SharepointGraphService::class);
        $graph->shouldReceive('getListItem')->once()->andReturn(makeFieldValueSet([
            '@odata.etag' => 'same-etag',
        ]));
        $graph->shouldNotReceive('getDriveItemByListItem');
        $graph->shouldNotReceive('getDriveItemContent');
        $graph->shouldNotReceive('getDriveItemPublicLink');

        $service = mockSyncServiceWithGraph($graph);
        $result = $service->sync($document);

        // Short-circuit path returns null (no fatal failure, nothing to update).
        expect($result)->toBeNull();

        $document->refresh();
        expect($document->link_url)->toBe('https://ataskaita2023.vusa.lt');
        expect($document->sync_status)->toBe('success');
    });
});
