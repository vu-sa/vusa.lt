<?php

use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\SharepointFile;
use App\Models\Tenant;
use App\Support\MorphMap;
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
            'fileable_type' => MorphMap::alias(Meeting::class),
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
            'fileable_type' => MorphMap::alias(Meeting::class),
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

/**
 * These three endpoints address SharePoint drive items by their opaque Graph id, so there is
 * no local model to authorize against — they are gated on the SharepointFile capability the
 * way createFolder already is. Authorization must happen before any Graph call is attempted.
 */
describe('drive item permission endpoints', function (): void {
    test('createPublicPermission returns 403 for unauthorized user', function (): void {
        asUser($this->user)
            ->post(route('sharepoint.createPublicPermission', ['id' => 'some-drive-item-id']))
            ->assertStatus(403);
    });

    test('getDriveItemPublicLink returns 403 for unauthorized user', function (): void {
        asUser($this->user)
            ->get(route('sharepoint.getDriveItemPublicLink', ['id' => 'some-drive-item-id']))
            ->assertStatus(403);
    });

    test('getTypesDriveItems returns 403 when the user cannot view the fileable', function (): void {
        asUser($this->user)
            ->get(route('sharepoint.getTypesDriveItems', [
                'type' => 'Institution',
                'id' => $this->institution->id,
            ]))
            ->assertStatus(403);
    });
});
