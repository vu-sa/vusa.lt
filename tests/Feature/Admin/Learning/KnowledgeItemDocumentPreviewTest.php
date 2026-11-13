<?php

use App\Enums\DocumentStatus;
use App\Jobs\RefreshDocumentThumbnailJob;
use App\Models\Document;
use App\Models\KnowledgeItem;
use App\Models\Permission;
use App\Models\Tenant;
use App\Services\Documents\DocumentPreviewService;
use App\Services\SharepointGraphService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;
use Microsoft\Graph\Generated\Models\DriveItem;
use Microsoft\Graph\Generated\Models\Thumbnail;
use Microsoft\Graph\Generated\Models\ThumbnailSet;

pest()->use(RefreshDatabase::class);

function knowledgeItemWithDocument(array $documentAttributes): array
{
    $document = Document::factory()->create($documentAttributes);
    $item = KnowledgeItem::factory()->create();
    $item->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);

    return [$item, $document];
}

/** @param  list<string>  $thumbnailUrls */
function previewServiceWithDriveItem(array $thumbnailUrls): DocumentPreviewService
{
    $driveItem = new DriveItem;
    $driveItem->setId('drive-item');
    $driveItem->setThumbnails(array_map(function (string $url): ThumbnailSet {
        $large = new Thumbnail;
        $large->setUrl($url);
        $set = new ThumbnailSet;
        $set->setLarge($large);

        return $set;
    }, $thumbnailUrls));

    $graph = Mockery::mock(SharepointGraphService::class);
    $graph->shouldReceive('getDriveItemByListItem')->andReturn($driveItem);

    $service = Mockery::mock(DocumentPreviewService::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $service->shouldReceive('graphFor')->andReturn($graph);

    return $service;
}

beforeEach(function (): void {
    Storage::fake();
    $this->member = makeUser(Tenant::query()->firstOrFail());
});

describe('thumbnail route', function (): void {
    test('a reader gets the thumbnail of a hidden document', function (): void {
        [$item] = knowledgeItemWithDocument(['status' => DocumentStatus::Hidden, 'anonymous_url' => null, 'thumbnail_path' => 'document-thumbnails/1.webp', 'thumbnail_etag' => 'etag']);
        Storage::put('document-thumbnails/1.webp', 'webp-bytes');

        asUser($this->member)->get(route('knowledgeItems.thumbnail', $item))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');
    });

    test('guests are sent to log in', function (): void {
        [$item] = knowledgeItemWithDocument(['thumbnail_path' => 'document-thumbnails/1.webp']);

        $this->get(route('knowledgeItems.thumbnail', $item))->assertRedirect();
    });

    test('items without a document thumbnail return 404', function (): void {
        [$withoutThumbnail] = knowledgeItemWithDocument(['thumbnail_path' => null]);
        $urlOnly = KnowledgeItem::factory()->create();
        $urlOnly->sources()->create(['locale' => 'lt', 'url' => 'https://example.test/guide']);

        asUser($this->member)->get(route('knowledgeItems.thumbnail', $withoutThumbnail))->assertNotFound();
        asUser($this->member)->get(route('knowledgeItems.thumbnail', $urlOnly))->assertNotFound();
    });
});

describe('open route', function (): void {
    test('a reader opens a hidden document through a temporary SharePoint link without making it public', function (): void {
        [$item, $document] = knowledgeItemWithDocument(['status' => DocumentStatus::Hidden, 'anonymous_url' => null]);
        $this->mock(DocumentPreviewService::class)->shouldReceive('temporaryUrl')->once()->andReturn('https://sharepoint.test/preview');

        asUser($this->member)->get(route('knowledgeItems.openSource', $item))->assertRedirect('https://sharepoint.test/preview');
        expect($document->fresh()->anonymous_url)->toBeNull();
    });

    test('a published document opens through its public link', function (): void {
        [$item, $document] = knowledgeItemWithDocument(['status' => DocumentStatus::Published, 'anonymous_url' => 'https://example.test/shared']);
        $this->mock(DocumentPreviewService::class)->shouldNotReceive('temporaryUrl');

        asUser($this->member)->get(route('knowledgeItems.openSource', $item))->assertRedirect(route('documents.show', $document->id));
    });

    test('an unavailable SharePoint link returns with an error', function (): void {
        [$item] = knowledgeItemWithDocument(['status' => DocumentStatus::Hidden, 'anonymous_url' => null]);
        $this->mock(DocumentPreviewService::class)->shouldReceive('temporaryUrl')->andReturnNull();

        asUser($this->member)->from(route('knowledgeItems.show', $item))->get(route('knowledgeItems.openSource', $item))
            ->assertRedirect(route('knowledgeItems.show', $item))
            ->assertSessionHas('error');
    });

    test('url-only items have no document to open', function (): void {
        $urlOnly = KnowledgeItem::factory()->create();
        $urlOnly->sources()->create(['locale' => 'lt', 'url' => 'https://example.test/guide']);

        asUser($this->member)->get(route('knowledgeItems.openSource', $urlOnly))->assertNotFound();
    });

    test('guests are sent to log in', function (): void {
        [$item] = knowledgeItemWithDocument(['status' => DocumentStatus::Hidden, 'anonymous_url' => null]);
        $this->mock(DocumentPreviewService::class)->shouldNotReceive('temporaryUrl');

        $this->get(route('knowledgeItems.openSource', $item))->assertRedirect(route('login'));
    });
});

describe('thumbnail refresh', function (): void {
    test('linking a document queues its thumbnail', function (): void {
        Queue::fake();
        foreach (['learningSteps.create.*', 'learningSteps.update.*', 'documents.read.*'] as $permission) {
            $this->member->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $document = Document::factory()->create(['eTag' => 'etag-1']);

        asUser($this->member)->post(route('knowledgeItems.store'), [
            'title' => ['lt' => 'Atstovo žinios', 'en' => 'Representative knowledge'],
            'summary' => ['lt' => 'Ką atstovas turi žinoti.'],
            'description' => ['lt' => '<p>Žinok savo teises.</p>', 'en' => '<p>Know your rights.</p>'],
            'audiences' => [['type' => 'everyone', 'tenant_id' => null, 'importance' => 'recommended']],
            'sources' => ['lt' => ['document_id' => $document->id]],
            'order' => 1, 'is_active' => true,
        ])->assertRedirect();

        Queue::assertPushed(RefreshDocumentThumbnailJob::class, fn (RefreshDocumentThumbnailJob $job): bool => $job->document->is($document));
    });

    test('the first-page thumbnail is stored as WebP for the current file version', function (): void {
        Http::fake(['thumbnails.test/*' => Http::response(Image::createImage(800, 1100)->encodeUsingFileExtension('png')->toString(), 200, ['Content-Type' => 'image/png'])]);
        $document = Document::factory()->create(['status' => DocumentStatus::Hidden, 'eTag' => 'etag-2']);
        $service = previewServiceWithDriveItem(['https://thumbnails.test/large']);

        $service->refreshThumbnail($document);

        expect($document->fresh()->thumbnail_path)->toBe("document-thumbnails/{$document->id}.webp")
            ->and($document->fresh()->thumbnail_etag)->toBe('etag-2')
            ->and(Image::decodeBinary(Storage::get("document-thumbnails/{$document->id}.webp"))->width())->toBe(600)
            ->and($service->needsThumbnail($document->fresh()))->toBeFalse();
    });

    test('a file without a thumbnail is remembered until it changes', function (): void {
        $document = Document::factory()->create(['eTag' => 'etag-3']);

        previewServiceWithDriveItem([])->refreshThumbnail($document);

        expect($document->fresh()->thumbnail_path)->toBeNull()
            ->and($document->fresh()->thumbnail_etag)->toBe('etag-3');
    });

    test('a failed download leaves the document to retry later', function (): void {
        Http::fake(['thumbnails.test/*' => Http::response('nope', 500)]);
        $document = Document::factory()->create(['eTag' => 'etag-4']);

        previewServiceWithDriveItem(['https://thumbnails.test/large'])->refreshThumbnail($document);

        expect($document->fresh()->thumbnail_etag)->toBeNull();
    });
});
