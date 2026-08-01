<?php

use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\SharepointFile;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeUser($this->tenant);
    $this->admin->assignRole('Super Admin');
    $this->user = makeUser($this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->meeting = Meeting::factory()->create([
        'start_time' => now()->addDay(),
    ]);
    $this->meeting->institutions()->attach($this->institution);
});

describe('destroyFileableFile', function (): void {
    test('marks fileableFile as externally deleted when sharepoint api fails', function (): void {
        $fileableFile = FileableFile::factory()->create([
            'fileable_type' => Meeting::class,
            'fileable_id' => $this->meeting->id,
            'sharepoint_id' => 'test-sharepoint-id',
            'name' => 'Test File.pdf',
        ]);

        // With fake credentials, the Graph API call will fail and the controller
        // should catch the exception and mark the file as externally deleted.
        $response = asUser($this->admin)->delete(
            route('fileableFiles.destroy', $fileableFile->id)
        );

        $response->assertRedirect();

        $fileableFile->refresh();
        expect($fileableFile->deleted_externally_at)->not->toBeNull();
    });

    test('returns 403 for unauthorized user', function (): void {
        $fileableFile = FileableFile::factory()->create([
            'fileable_type' => Meeting::class,
            'fileable_id' => $this->meeting->id,
            'sharepoint_id' => 'test-sharepoint-id',
            'name' => 'Test File.pdf',
        ]);

        $response = asUser($this->user)->delete(
            route('fileableFiles.destroy', $fileableFile->id)
        );

        $response->assertStatus(403);
    });
});

describe('destroy (legacy sharepointFile)', function (): void {
    test('returns 403 for unauthorized user', function (): void {
        $sharepointFile = SharepointFile::factory()->create([
            'sharepoint_id' => 'legacy-sp-id',
        ]);

        $response = asUser($this->user)->delete(
            route('sharepointFiles.destroy', $sharepointFile->id)
        );

        $response->assertStatus(403);
    });
});
