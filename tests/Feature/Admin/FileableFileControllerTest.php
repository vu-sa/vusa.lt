<?php

use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Services\SharepointGraphService;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Microsoft\Graph\Generated\Models\DriveItem;
use Microsoft\Graph\Generated\Models\ODataErrors\ODataError;
use Microsoft\Graph\Generated\Models\Permission;
use Microsoft\Graph\Generated\Models\SharingLink;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeAdminUser($this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->meeting = Meeting::factory()->create();
    $this->meeting->institutions()->attach($this->institution);

    $this->graph = Mockery::mock(SharepointGraphService::class);
    $this->graph->siteId = 'site-id';
    app()->bind(SharepointGraphService::class, fn () => $this->graph);
});

function fakeDriveItem(string $id, string $name): DriveItem
{
    $item = new DriveItem;
    $item->setId($id);
    $item->setName($name);

    return $item;
}

function fakeAnonymousLink(string $url): Permission
{
    $link = new SharingLink;
    $link->setWebUrl($url);

    $permission = new Permission;
    $permission->setLink($link);

    return $permission;
}

function graphNotFound(): ODataError
{
    $error = new ODataError('itemNotFound');
    $error->setResponseStatusCode(404);

    return $error;
}

function meetingFile(Meeting $meeting, array $attributes = []): FileableFile
{
    return FileableFile::factory()->create([
        'fileable_type' => MorphMap::alias(Meeting::class),
        'fileable_id' => $meeting->id,
        'sharepoint_id' => 'drive-item-1',
        'name' => 'Protokolas.pdf',
        'public_link' => null,
        ...$attributes,
    ]);
}

describe('store', function (): void {
    test('uploads several files at once and records the name SharePoint gave each', function (): void {
        $this->graph->shouldReceive('uploadDriveItem')->twice()->andReturn(
            fakeDriveItem('item-a', '2026-09-12 protokolas (1).pdf'),
            fakeDriveItem('item-b', 'Ataskaita.docx'),
        );

        asUser($this->admin)
            ->post(route('fileableFiles.store', ['type' => 'Meeting', 'id' => $this->meeting->id]), [
                'files' => [
                    ['file' => UploadedFile::fake()->create('scan.pdf', 10, 'application/pdf'), 'type' => 'Protokolai', 'date' => '2026-09-12', 'name' => '2026-09-12 protokolas'],
                    ['file' => UploadedFile::fake()->create('Ataskaita.docx', 10), 'type' => 'Ataskaitos', 'date' => '2026-09-12'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $files = $this->meeting->fileableFiles()->orderBy('sharepoint_id')->get();

        expect($files)->toHaveCount(2)
            ->and($files[0]->name)->toBe('2026-09-12 protokolas (1).pdf')
            ->and($files[0]->file_type)->toBe('Protokolai')
            ->and($files[1]->file_type)->toBe('Ataskaitos')
            ->and($this->meeting->fresh()->has_protocol)->toBeTrue();
    });

    test('a failed file does not stop the others and is reported back for a retry', function (): void {
        $this->graph->shouldReceive('uploadDriveItem')->twice()->andReturnUsing(
            fn () => throw new RuntimeException('Graph timeout'),
            fn () => fakeDriveItem('item-b', 'Ataskaita.pdf'),
        );

        asUser($this->admin)
            ->post(route('fileableFiles.store', ['type' => 'Meeting', 'id' => $this->meeting->id]), [
                'files' => [
                    ['file' => UploadedFile::fake()->create('Protokolas.pdf', 10, 'application/pdf'), 'type' => 'Protokolai', 'date' => '2026-09-12'],
                    ['file' => UploadedFile::fake()->create('Ataskaita.pdf', 10, 'application/pdf'), 'type' => 'Ataskaitos', 'date' => '2026-09-12'],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('error')
            ->assertSessionHas('data', ['failed_file_indexes' => [0]]);

        expect($this->meeting->fileableFiles()->pluck('file_type')->all())->toBe(['Ataskaitos']);
    });

    test('a coordinator uploads to a meeting of their own padalinys', function (): void {
        $this->graph->shouldReceive('uploadDriveItem')->once()->andReturn(fakeDriveItem('item-a', 'Protokolas.pdf'));

        asUser(makeTenantUserWithRole('Communication Coordinator', $this->tenant))
            ->post(route('fileableFiles.store', ['type' => 'Meeting', 'id' => $this->meeting->id]), [
                'files' => [['file' => UploadedFile::fake()->create('Protokolas.pdf', 10, 'application/pdf'), 'type' => 'Protokolai', 'date' => '2026-09-12']],
            ])
            ->assertSessionHas('success');
    });

    test('rejects a coordinator uploading to another padalinys meeting', function (): void {
        $otherInstitution = Institution::factory()->for(Tenant::factory()->create())->create();
        $otherMeeting = Meeting::factory()->create();
        $otherMeeting->institutions()->attach($otherInstitution);

        asUser(makeTenantUserWithRole('Communication Coordinator', $this->tenant))
            ->post(route('fileableFiles.store', ['type' => 'Meeting', 'id' => $otherMeeting->id]), [
                'files' => [['file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'), 'type' => 'Protokolai', 'date' => '2026-09-12']],
            ])
            ->assertStatus(403);
    });

    test('rejects a file type SharePoint does not know', function (): void {
        asUser($this->admin)
            ->post(route('fileableFiles.store', ['type' => 'Meeting', 'id' => $this->meeting->id]), [
                'files' => [['file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'), 'type' => 'Laiškai', 'date' => '2026-09-12']],
            ])
            ->assertSessionHasErrors('files.0.type');
    });

    test('returns 404 for a model that cannot hold files', function (): void {
        asUser($this->admin)
            ->post(route('fileableFiles.store', ['type' => 'User', 'id' => $this->admin->id]), [
                'files' => [['file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf'), 'type' => 'Kita', 'date' => '2026-09-12']],
            ])
            ->assertNotFound();
    });
});

describe('open', function (): void {
    test('goes straight to the stored anonymous link', function (): void {
        $file = meetingFile($this->meeting, ['public_link' => 'https://vustudentuatstovybe.sharepoint.com/:b:/s/x']);

        $this->graph->shouldNotReceive('getDriveItemPublicLink');

        asUser($this->admin)
            ->get(route('fileableFiles.open', $file))
            ->assertRedirect('https://vustudentuatstovybe.sharepoint.com/:b:/s/x');
    });

    test('mints and keeps an anonymous link when the file has none', function (): void {
        $file = meetingFile($this->meeting);

        $this->graph->shouldReceive('getDriveItemPublicLink')->with('drive-item-1')->andReturnNull();
        $this->graph->shouldReceive('createPublicPermission')->once()
            ->with('site-id', 'drive-item-1', false)
            ->andReturn(fakeAnonymousLink('https://sharepoint.test/new-link'));

        asUser($this->admin)
            ->get(route('fileableFiles.open', $file))
            ->assertRedirect('https://sharepoint.test/new-link');

        expect($file->fresh()->public_link)->toBe('https://sharepoint.test/new-link');
    });

    test('marks a file gone from SharePoint instead of failing', function (): void {
        $file = meetingFile($this->meeting);

        $this->graph->shouldReceive('getDriveItemPublicLink')->andThrow(graphNotFound());

        asUser($this->admin)
            ->from(route('meetings.show', $this->meeting))
            ->get(route('fileableFiles.open', $file))
            ->assertRedirect(route('meetings.show', $this->meeting))
            ->assertSessionHas('error');

        expect($file->fresh()->deleted_externally_at)->not->toBeNull();
    });

    test('returns 403 for a user who cannot view the meeting', function (): void {
        $file = meetingFile($this->meeting);

        asUser(makeUser($this->tenant))
            ->get(route('fileableFiles.open', $file))
            ->assertStatus(403);
    });
});

test('publicLink returns the url for copying', function (): void {
    $file = meetingFile($this->meeting, ['public_link' => 'https://sharepoint.test/existing']);

    asUser($this->admin)
        ->post(route('fileableFiles.publicLink', $file))
        ->assertOk()
        ->assertJson(['url' => 'https://sharepoint.test/existing']);
});

test('destroy removes the record when SharePoint already lost the file', function (): void {
    $file = meetingFile($this->meeting);

    $this->graph->shouldReceive('deleteDriveItem')->andThrow(graphNotFound());

    asUser($this->admin)
        ->delete(route('fileableFiles.destroy', $file))
        ->assertRedirect()
        ->assertSessionHas('info', __('messages.sharepoint.file_deleted'));

    expect(FileableFile::query()->find($file->id))->toBeNull();
});
