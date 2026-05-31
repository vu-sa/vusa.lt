<?php

use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\SharepointFile;
use App\Models\Tenant;
use App\Services\SharepointGraphService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::factory()->create(['shortname' => 'test-tenant']);
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->meeting = Meeting::factory()->create([
        'start_time' => now()->addDay(),
    ]);
    $this->meeting->institutions()->attach($this->institution);

    // Create service with test parameters — no network calls are made
    // because parseDriveItems only queries the local database.
    $this->service = new SharepointGraphService(
        siteId: 'test-site',
        driveId: 'test-drive',
        listId: null
    );
});

describe('parseDriveItems', function () {
    test('attaches sharepointFile records to matching drive items', function () {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('parseDriveItems');
        $method->setAccessible(true);

        $sharepointFile = SharepointFile::factory()->create([
            'sharepoint_id' => 'drive-item-1',
        ]);

        $mockDriveItems = collect([
            [
                'id' => 'drive-item-1',
                'name' => 'Test File.pdf',
                'size' => 1024,
                'file' => ['mimeType' => 'application/pdf'],
                'createdDateTime' => '2024-01-15T10:00:00Z',
                'lastModifiedDateTime' => '2024-01-15T12:00:00Z',
                'webUrl' => 'https://sharepoint.test/file.pdf',
                'listItem' => ['fields' => null],
                'permissions' => null,
                'thumbnails' => [],
            ],
            [
                'id' => 'drive-item-2',
                'name' => 'Unmatched File.docx',
                'size' => 2048,
                'file' => ['mimeType' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                'createdDateTime' => '2024-01-15T10:00:00Z',
                'lastModifiedDateTime' => '2024-01-15T12:00:00Z',
                'webUrl' => 'https://sharepoint.test/file2.docx',
                'listItem' => ['fields' => null],
                'permissions' => null,
                'thumbnails' => [],
            ],
        ]);

        $result = $method->invoke($this->service, $mockDriveItems);

        expect($result)->toHaveCount(2);

        $firstItem = $result->firstWhere('id', 'drive-item-1');
        $secondItem = $result->firstWhere('id', 'drive-item-2');

        expect($firstItem['sharepointFile'])->not->toBeNull();
        expect($firstItem['sharepointFile']->id)->toBe($sharepointFile->id);

        expect($secondItem['sharepointFile'])->toBeNull();
    });

    test('does not include fileableFile key (that is handled by controller)', function () {
        $reflection = new ReflectionClass($this->service);
        $method = $reflection->getMethod('parseDriveItems');
        $method->setAccessible(true);

        FileableFile::factory()->create([
            'fileable_type' => Meeting::class,
            'fileable_id' => $this->meeting->id,
            'sharepoint_id' => 'drive-item-1',
            'name' => 'Test File.pdf',
        ]);

        $mockDriveItems = collect([
            [
                'id' => 'drive-item-1',
                'name' => 'Test File.pdf',
                'size' => 1024,
                'file' => ['mimeType' => 'application/pdf'],
                'createdDateTime' => '2024-01-15T10:00:00Z',
                'lastModifiedDateTime' => '2024-01-15T12:00:00Z',
                'webUrl' => 'https://sharepoint.test/file.pdf',
                'listItem' => ['fields' => null],
                'permissions' => null,
                'thumbnails' => [],
            ],
        ]);

        $result = $method->invoke($this->service, $mockDriveItems);

        expect($result)->toHaveCount(1);
        $item = $result->first();

        // parseDriveItems is intentionally agnostic to fileable context;
        // fileableFile attachment is the controller's responsibility.
        expect($item)->not->toHaveKey('fileableFile');
    });
});
