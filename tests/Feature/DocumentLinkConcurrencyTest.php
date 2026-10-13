<?php

use App\Enums\DocumentStatus;
use App\Jobs\RevokeSharepointPermissionJob;
use App\Models\Document;
use App\Services\DocumentSharepointSyncService;
use App\Services\SharepointGraphService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Microsoft\Graph\Generated\Models\DriveItem;
use Microsoft\Graph\Generated\Models\FieldValueSet;
use Microsoft\Graph\Generated\Models\Permission;
use Microsoft\Graph\Generated\Models\SharingLink;

pest()->use(RefreshDatabase::class);

test('a link refused because the document was hidden meanwhile is never stored, even when deleting it fails', function (): void {
    Queue::fake([RevokeSharepointPermissionJob::class]);

    $document = Document::factory()->create(['status' => DocumentStatus::Published, 'anonymous_url' => null, 'sharepoint_permission_id' => null]);

    $fields = new FieldValueSet;
    $fields->setAdditionalData(['@odata.etag' => 'new-etag', 'Name' => 'protokolas.pdf']);
    $driveItem = new DriveItem;
    $driveItem->setId('drive-1');
    $link = new SharingLink;
    $link->setWebUrl('https://sharepoint.example.com/new-link');
    $permission = new Permission;
    $permission->setId('perm-new');
    $permission->setLink($link);

    $graph = Mockery::mock(SharepointGraphService::class);
    $graph->shouldReceive('getListItem')->andReturn($fields);
    $graph->shouldReceive('getDriveItemByListItem')->andReturn($driveItem);
    $graph->shouldReceive('getDriveItemPublicLink')->andReturn(null);
    $graph->shouldReceive('createPublicPermission')->andReturnUsing(function () use ($document, $permission): Permission {
        // A manager hides the document while the link is being created.
        DB::table('documents')->where('id', $document->id)->update(['status' => DocumentStatus::Hidden->value]);

        return $permission;
    });
    $graph->shouldReceive('deletePermission')->andThrow(new RuntimeException('Graph unavailable'));

    $service = Mockery::mock(DocumentSharepointSyncService::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('makeGraphService')->andReturn($graph);

    $service->sync($document, force: true);

    expect($document->fresh())
        ->anonymous_url->toBeNull()
        ->sharepoint_permission_id->toBeNull()
        ->status->toBe(DocumentStatus::Hidden);

    Queue::assertPushed(RevokeSharepointPermissionJob::class, fn (RevokeSharepointPermissionJob $job): bool => $job->sharepointPermissionId === 'perm-new' && $job->documentId === $document->id);
});

test('a failed sync records its failure without saving the link it was preparing', function (): void {
    $document = Document::factory()->create(['status' => DocumentStatus::Published, 'anonymous_url' => null, 'sync_status' => 'pending']);

    $fields = new FieldValueSet;
    $fields->setAdditionalData(['@odata.etag' => 'new-etag', 'Name' => 'protokolas.pdf']);

    $graph = Mockery::mock(SharepointGraphService::class);
    $graph->shouldReceive('getListItem')->andReturn($fields);
    $graph->shouldReceive('getDriveItemByListItem')->andThrow(new LogicException('Graph exploded'));

    $service = Mockery::mock(DocumentSharepointSyncService::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('makeGraphService')->andReturn($graph);

    expect(fn () => $service->sync($document, force: true))->toThrow(LogicException::class)->and($document->fresh())->sync_status->toBe('failed')->sync_error_message->toBe('Graph exploded')->eTag->not->toBe('new-etag');
});

test('a revocation leaves alone a link the republished document uses again', function (DocumentStatus $status, bool $revokes): void {
    $document = Document::factory()->create(['status' => $status, 'sharepoint_permission_id' => $status === DocumentStatus::Published ? 'perm-1' : null]);

    $driveItem = new DriveItem;
    $driveItem->setId('drive-1');
    $graph = Mockery::mock(SharepointGraphService::class);
    $graph->shouldReceive('getDriveItemByListItem')->andReturn($driveItem);
    $graph->shouldReceive('deletePermission')->times($revokes ? 1 : 0)->with('drive-1', 'perm-1');

    $job = Mockery::mock(RevokeSharepointPermissionJob::class, [
        $document->sharepoint_site_id, $document->sharepoint_list_id, $document->sharepoint_id, 'perm-1', $document->id,
    ])->makePartial()->shouldAllowMockingProtectedMethods();
    $job->shouldReceive('makeGraphService')->andReturn($graph);

    $job->handle();
})->with([
    'republished with the same link' => [DocumentStatus::Published, false],
    'still hidden' => [DocumentStatus::Hidden, true],
]);
