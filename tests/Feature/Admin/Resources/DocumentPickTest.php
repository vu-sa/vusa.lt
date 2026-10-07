<?php

use App\Enums\DocumentStatus;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Services\Documents\SharepointDocumentDiscovery;
use App\Services\SharepointGraphService;
use App\Settings\DocumentDiscoverySettings;
use Database\Seeders\RoleDocumentManagerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Microsoft\Graph\Generated\Models\DriveItem;
use Microsoft\Graph\Generated\Models\File;
use Microsoft\Graph\Generated\Models\ItemReference;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake();

    $this->tenant = Tenant::query()->where('id', '!=', Tenant::main()->id)->first();
    $this->manager = makeTenantUserWithRole(RoleDocumentManagerSeeder::NAME, $this->tenant);
    $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id, 'name' => ['lt' => 'MIF padalinys', 'en' => 'MIF unit']]);
});

/**
 * What SharePoint's file picker reports for the file with this list item id.
 *
 * @return array{site_id: string, list_id: string, list_item_unique_id: string}
 */
function pickedFile(string $uniqueId): array
{
    return ['site_id' => 'site-1', 'list_id' => 'list-1', 'list_item_unique_id' => $uniqueId];
}

/**
 * Discovery with a Graph stub that knows one file, at `$folder` of the archive drive.
 */
function bindPickDiscovery(string $name = 'Protokolas.pdf', string $folder = 'Dokumentų sistema/Protokolai', ?string $driveId = null, string $padalinys = 'MIF padalinys'): void
{
    $parent = new ItemReference;
    $parent->setId('folder-1');
    $parent->setPath('/drives/archive/root:/'.$folder);
    $parent->setDriveId($driveId ?? config('filesystems.sharepoint.archive_drive_id'));

    $driveItem = new DriveItem;
    $driveItem->setId('drive-new');
    $driveItem->setName($name);
    $driveItem->setFile(new File);
    $driveItem->setParentReference($parent);
    $driveItem->setLastModifiedDateTime(new DateTime('2026-10-01T10:00:00Z'));

    $graph = Mockery::mock(SharepointGraphService::class);
    $graph->shouldReceive('getDriveItemByListItem')->andReturn($driveItem);
    $graph->shouldReceive('getListItemsForDriveItems')->andReturn(['items' => ['drive-new' => ['eTag' => '"e"', 'fields' => [
        'Title' => 'Naujas protokolas',
        'Padalinys' => ['Label' => $padalinys],
    ]]], 'transient' => []]);

    $discovery = Mockery::mock(SharepointDocumentDiscovery::class, [app(DocumentDiscoverySettings::class)])->makePartial()->shouldAllowMockingProtectedMethods();
    $discovery->shouldReceive('makeGraphService')->andReturn($graph);
    app()->instance(SharepointDocumentDiscovery::class, $discovery);
}

describe('documents page', function (): void {
    test('a picked file already known is published', function (): void {
        $document = Document::factory()->pending()->create(['institution_id' => $this->institution->id, 'sharepoint_id' => 'abc-123']);

        asUser($this->manager)->post(route('documents.pick'), ['documents' => [pickedFile('{ABC-123}')]])->assertRedirect();

        expect($document->fresh()->status)->toBe(DocumentStatus::Published);
        Queue::assertPushed(SyncDocumentFromSharePointJob::class);
    });

    test('a picked file discovery has not reached yet is imported and published', function (): void {
        bindPickDiscovery();

        asUser($this->manager)->post(route('documents.pick'), ['documents' => [pickedFile('new-1')]])->assertRedirect();

        expect(Document::query()->where('sharepoint_id', 'new-1')->first())
            ->status->toBe(DocumentStatus::Published)
            ->title->toBe('Naujas protokolas')
            ->institution_id->toBe($this->institution->id)
            ->sharepoint_path->toBe('Dokumentų sistema/Protokolai');
    });

    test('a file outside the document archive is refused', function (string $folder, ?string $driveId): void {
        bindPickDiscovery(folder: $folder, driveId: $driveId);

        asUser($this->manager)->post(route('documents.pick'), ['documents' => [pickedFile('new-1')]])->assertSessionHasErrors('documents');

        expect(Document::query()->where('sharepoint_id', 'new-1')->exists())->toBeFalse();
    })->with([
        'another folder' => ['Asmeniniai', null],
        'another drive' => ['Dokumentų sistema/Protokolai', 'other-drive'],
    ]);

    test('another padalinys\' file is refused, and so is a member without document rights', function (): void {
        $foreign = Document::factory()->pending()->create([
            'institution_id' => Institution::factory()->create(['tenant_id' => Tenant::query()->whereNotIn('id', [$this->tenant->id])->first()->id])->id,
            'sharepoint_id' => 'foreign-1',
        ]);

        asUser($this->manager)->post(route('documents.pick'), ['documents' => [pickedFile('foreign-1')]])->assertForbidden();
        asUser(makeUser($this->tenant))->post(route('documents.pick'), ['documents' => [pickedFile('foreign-1')]])->assertForbidden();

        expect($foreign->fresh()->status)->toBe(DocumentStatus::Pending);
    });
});

describe('meeting page', function (): void {
    beforeEach(function (): void {
        $this->admin = makeAdminUser($this->tenant);
        $this->meeting = Meeting::factory()->create();
        $this->meeting->institutions()->attach($this->institution);
    });

    test('a picked file is linked to the meeting and published', function (): void {
        bindPickDiscovery();

        asUser($this->admin)->post(route('meetings.documents.storeFromSharepoint', $this->meeting), ['documents' => [pickedFile('new-1')]])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        expect(Document::query()->where('sharepoint_id', 'new-1')->first())
            ->meeting_id->toBe($this->meeting->id)
            ->status->toBe(DocumentStatus::Published);
    });

    test('a picked file of no padalinys of the meeting is not linked', function (): void {
        bindPickDiscovery(padalinys: 'Nežinomas');

        asUser($this->admin)->post(route('meetings.documents.storeFromSharepoint', $this->meeting), ['documents' => [pickedFile('new-1')]])
            ->assertSessionHasErrors('documents');

        expect(Document::query()->where('sharepoint_id', 'new-1')->value('meeting_id'))->toBeNull();
    });

    test('someone who may not edit the meeting cannot link through the picker', function (): void {
        asUser($this->manager)->post(route('meetings.documents.storeFromSharepoint', $this->meeting), ['documents' => [pickedFile('new-1')]])
            ->assertForbidden();
    });
});
