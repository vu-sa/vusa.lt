<?php

use App\Http\Controllers\Api\Admin\SharepointApiController;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeUser($this->tenant);
    $this->admin->assignRole('Super Admin');
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->meeting = Meeting::factory()->create([
        'start_time' => now()->addDay(),
    ]);
    $this->meeting->institutions()->attach($this->institution);
});

describe('attachFileableFilesToDriveItems', function () {
    test('returns drive items unchanged when no fileable context provided', function () {
        $controller = new SharepointApiController;
        $reflection = new ReflectionMethod($controller, 'attachFileableFilesToDriveItems');
        $reflection->setAccessible(true);

        $driveItems = collect([
            ['id' => 'drive-item-1', 'name' => 'File 1'],
            ['id' => 'drive-item-2', 'name' => 'File 2'],
        ]);

        $result = $reflection->invoke($controller, $driveItems, null, null);

        expect($result)->toHaveCount(2);
        expect($result->first()['fileableFile'] ?? null)->toBeNull();
    });

    test('attaches fileableFile when fileable context is provided and records exist', function () {
        $controller = new SharepointApiController;
        $reflection = new ReflectionMethod($controller, 'attachFileableFilesToDriveItems');
        $reflection->setAccessible(true);

        $fileableFile = FileableFile::factory()->create([
            'fileable_type' => Meeting::class,
            'fileable_id' => $this->meeting->id,
            'sharepoint_id' => 'drive-item-1',
            'name' => 'Test File.pdf',
        ]);

        $driveItems = collect([
            ['id' => 'drive-item-1', 'name' => 'File 1'],
            ['id' => 'drive-item-2', 'name' => 'File 2'],
        ]);

        $result = $reflection->invoke($controller, $driveItems, 'Meeting', $this->meeting->id);

        expect($result)->toHaveCount(2);

        $firstItem = $result->firstWhere('id', 'drive-item-1');
        $secondItem = $result->firstWhere('id', 'drive-item-2');

        expect($firstItem['fileableFile'])->not->toBeNull();
        expect($firstItem['fileableFile']->id)->toBe($fileableFile->id);

        expect($secondItem['fileableFile'])->toBeNull();
    });

    test('ignores invalid fileable_type', function () {
        $controller = new SharepointApiController;
        $reflection = new ReflectionMethod($controller, 'attachFileableFilesToDriveItems');
        $reflection->setAccessible(true);

        $driveItems = collect([
            ['id' => 'drive-item-1', 'name' => 'File 1'],
        ]);

        $result = $reflection->invoke($controller, $driveItems, 'InvalidType', 'some-id');

        expect($result->first()['fileableFile'] ?? null)->toBeNull();
    });

    test('does not attach externally deleted fileableFiles', function () {
        $controller = new SharepointApiController;
        $reflection = new ReflectionMethod($controller, 'attachFileableFilesToDriveItems');
        $reflection->setAccessible(true);

        FileableFile::factory()->create([
            'fileable_type' => Meeting::class,
            'fileable_id' => $this->meeting->id,
            'sharepoint_id' => 'drive-item-1',
            'name' => 'Deleted File.pdf',
            'deleted_externally_at' => now(),
        ]);

        $driveItems = collect([
            ['id' => 'drive-item-1', 'name' => 'Deleted File.pdf'],
        ]);

        $result = $reflection->invoke($controller, $driveItems, 'Meeting', $this->meeting->id);

        expect($result->first()['fileableFile'])->toBeNull();
    });

    test('returns empty collection when drive items have no ids', function () {
        $controller = new SharepointApiController;
        $reflection = new ReflectionMethod($controller, 'attachFileableFilesToDriveItems');
        $reflection->setAccessible(true);

        $driveItems = collect([
            ['name' => 'File without id'],
        ]);

        $result = $reflection->invoke($controller, $driveItems, 'Meeting', $this->meeting->id);

        expect($result)->toHaveCount(1);
        expect($result->first()['fileableFile'] ?? null)->toBeNull();
    });
});

describe('fileableFiles endpoint', function () {
    test('returns FileableFile records for a fileable', function () {
        FileableFile::factory()->count(3)->create([
            'fileable_type' => Meeting::class,
            'fileable_id' => $this->meeting->id,
        ]);

        $response = asUser($this->admin)->getJson(
            route('api.v1.admin.fileables.files', ['type' => 'Meeting', 'id' => $this->meeting->id])
        );

        $response->assertStatus(200);
        $data = $response->json('data');
        expect($data)->toHaveCount(3);
    });

    test('rejects invalid fileable type', function () {
        $response = asUser($this->admin)->getJson(
            route('api.v1.admin.fileables.files', ['type' => 'InvalidType', 'id' => 'some-id'])
        );

        $response->assertStatus(400);
    });
});
