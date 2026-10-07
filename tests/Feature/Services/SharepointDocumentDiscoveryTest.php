<?php

use App\Enums\DocumentStatus;
use App\Exceptions\SharepointDeltaExpiredException;
use App\Exceptions\SharepointThrottledException;
use App\Jobs\DiscoverSharepointDocumentsJob;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Models\Institution;
use App\Models\SharepointFolder;
use App\Services\Documents\DiscoveryResult;
use App\Services\Documents\SharepointDocumentDiscovery;
use App\Services\SharepointGraphService;
use App\Services\SystemMonitorService;
use App\Settings\DocumentDiscoverySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Microsoft\Graph\Generated\Models\Deleted;
use Microsoft\Graph\Generated\Models\DriveItem;
use Microsoft\Graph\Generated\Models\File;
use Microsoft\Graph\Generated\Models\Folder;
use Microsoft\Graph\Generated\Models\ItemReference;
use Microsoft\Graph\Generated\Models\Root;
use Microsoft\Graph\Generated\Models\SharepointIds;

pest()->use(RefreshDatabase::class);

const DISCOVERY_SITE = 'site-1';
const DISCOVERY_LIST = 'list-1';

beforeEach(function (): void {
    // Past the first-ever run, so new files arrive as pending; the baseline has its own tests.
    $settings = app(DocumentDiscoverySettings::class);
    $settings->last_full_run_at = '2026-10-01T00:00:00+03:00';
    $settings->save();
});

afterEach(function (): void {
    Mockery::close();
});

function discoveryFile(string $id, string $uniqueId, string $name = 'Protokolas.pdf', string $modified = '2026-10-01T10:00:00Z', string $folder = 'Dokumentų sistema/Protokolai'): DriveItem
{
    $ids = new SharepointIds;
    $ids->setListItemUniqueId($uniqueId);
    $ids->setSiteId(DISCOVERY_SITE);
    $ids->setListId(DISCOVERY_LIST);

    // Like Graph's delta feed: the parent's id, but no path.
    $parent = new ItemReference;
    $parent->setId('folder:'.$folder);

    $item = new DriveItem;
    $item->setId($id);
    $item->setName($name);
    $item->setFile(new File);
    $item->setSharepointIds($ids);
    $item->setParentReference($parent);
    $item->setWebUrl("https://vusa.sharepoint.com/{$name}");
    $item->setLastModifiedDateTime(new DateTime($modified));

    return $item;
}

function discoveryFolder(string $id, string $name, ?string $parentId): DriveItem
{
    $item = new DriveItem;
    $item->setId($id);
    $item->setName($name);

    if ($parentId === null) {
        $item->setRoot(new Root);
    } else {
        $item->setFolder(new Folder);
        $parent = new ItemReference;
        $parent->setId($parentId);
        $item->setParentReference($parent);
    }

    return $item;
}

function discoveryDeleted(string $id): DriveItem
{
    $item = new DriveItem;
    $item->setId($id);
    $item->setDeleted(new Deleted);

    return $item;
}

/**
 * @param  list<array{items: list<DriveItem>, nextLink: ?string, deltaLink: ?string}>  $pages
 * @param  array<string, array{eTag: ?string, fields: array<string, mixed>}>  $listItems
 * @param  list<string>  $transient  drive item ids whose lookup fails for a reason that may pass
 */
function discoveryWithGraph(array $pages, array $listItems = [], array $transient = []): SharepointDocumentDiscovery
{
    $graph = Mockery::mock(SharepointGraphService::class);
    $graph->shouldReceive('getDriveDeltaPage')->andReturnValues($pages);
    $graph->shouldReceive('getListItemsForDriveItems')
        ->andReturnUsing(fn (array $ids): array => [
            'items' => array_intersect_key($listItems, array_flip($ids)),
            'transient' => array_values(array_intersect($transient, $ids)),
        ]);

    $discovery = Mockery::mock(SharepointDocumentDiscovery::class, [app(DocumentDiscoverySettings::class)])
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();
    $discovery->shouldReceive('makeGraphService')->andReturn($graph);

    return $discovery;
}

/**
 * The root and every folder above the files, as a listing carries them, followed by the items.
 *
 * @param  list<DriveItem>  $items
 */
function discoveryPage(array $items, string $deltaLink = 'delta-2'): array
{
    $folders = ['root' => discoveryFolder('root', 'root', null)];

    foreach ($items as $item) {
        $parentId = $item->getParentReference()?->getId();

        if ($parentId === null || ! str_starts_with($parentId, 'folder:')) {
            continue;
        }

        $path = '';
        foreach (explode('/', substr($parentId, strlen('folder:'))) as $segment) {
            $parent = $path === '' ? 'root' : 'folder:'.$path;
            $path = ltrim($path.'/'.$segment, '/');
            $folders['folder:'.$path] ??= discoveryFolder('folder:'.$path, $segment, $parent);
        }
    }

    return ['items' => [...array_values($folders), ...$items], 'nextLink' => null, 'deltaLink' => $deltaLink];
}

test('a new file arrives as pending, without a public link, with its metadata', function (): void {
    $institution = Institution::factory()->create(['name' => ['lt' => 'VU SA MIF', 'en' => 'VU SR MIF']]);

    $result = discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'UNIQUE-1')])], [
        'drive-1' => ['eTag' => '"e1"', 'fields' => [
            'Title' => 'Parlamento protokolas',
            'Padalinys' => ['Label' => 'VU SA MIF'],
            'Turinys' => ['Label' => 'Protokolai'],
            'Date' => '2026-09-13T21:00:00Z',
            'Language' => 'Lietuvių',
        ]],
    ])->run();

    $document = Document::query()->sole();

    expect($result->created)->toBe(1)
        ->and($document->status)->toBe(DocumentStatus::Pending)
        ->and($document->anonymous_url)->toBeNull()
        ->and($document->sharepoint_id)->toBe('unique-1')
        ->and($document->title)->toBe('Parlamento protokolas')
        ->and($document->institution_id)->toBe($institution->id)
        ->and($document->sharepoint_path)->toBe('Dokumentų sistema/Protokolai')
        ->and($document->metadataProblems())->toBe([])
        ->and($document->shouldBeSearchable())->toBeFalse()
        ->and(app(DocumentDiscoverySettings::class)->delta_link)->toBe('delta-2');
});

test('a renamed folder moves the stored paths of everything under it, though Graph sends only the folder', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-1';
    $settings->save();
    SharepointFolder::query()->insert([
        ['drive_item_id' => 'root', 'parent_id' => null, 'name' => ''],
        ['drive_item_id' => 'f-ds', 'parent_id' => 'root', 'name' => 'Dokumentų sistema'],
        ['drive_item_id' => 'f-prot', 'parent_id' => 'f-ds', 'name' => 'Protokolai'],
        ['drive_item_id' => 'f-2026', 'parent_id' => 'f-prot', 'name' => '2026'],
    ]);
    $inside = Document::factory()->create(['sharepoint_path' => 'Dokumentų sistema/Protokolai/2026']);
    $sibling = Document::factory()->create(['sharepoint_path' => 'Dokumentų sistema/Protokolai senieji']);

    discoveryWithGraph([['items' => [discoveryFolder('f-prot', 'Nutarimai', 'f-ds')], 'nextLink' => null, 'deltaLink' => 'delta-3']])->run();

    expect($inside->refresh()->sharepoint_path)->toBe('Dokumentų sistema/Nutarimai/2026')
        ->and($sibling->refresh()->sharepoint_path)->toBe('Dokumentų sistema/Protokolai senieji')
        ->and(SharepointFolder::query()->find('f-prot')->name)->toBe('Nutarimai');
});

test('Graph\'s own path is only a fallback when the folder tree cannot place a file', function (): void {
    $item = discoveryFile('drive-1', 'unique-1');
    $parent = new ItemReference;
    $parent->setId('unknown-folder');
    $parent->setPath('/drives/b!archive/root:/Dokumentų sistema/Kita');
    $item->setParentReference($parent);

    discoveryWithGraph([discoveryPage([$item])], ['drive-1' => ['eTag' => '"e"', 'fields' => []]])->run();

    expect(Document::query()->sole()->sharepoint_path)->toBe('Dokumentų sistema/Kita');
});

test('an imported document is matched by its list item id and keeps its publication', function (): void {
    $document = Document::factory()->create(['sharepoint_id' => 'unique-1', 'title' => 'Senas', 'anonymous_url' => 'https://share/x']);

    $result = discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'UNIQUE-1')])], [
        'drive-1' => ['eTag' => '"e2"', 'fields' => ['Title' => 'Naujas']],
    ])->run();

    $document->refresh();

    expect($result->created)->toBe(0)
        ->and($result->updated)->toBe(1)
        ->and(Document::query()->count())->toBe(1)
        ->and($document->status)->toBe(DocumentStatus::Published)
        ->and($document->anonymous_url)->toBe('https://share/x')
        ->and($document->title)->toBe('Naujas')
        ->and($document->sharepoint_drive_item_id)->toBe('drive-1');
});

test('files outside the document system folder are not listed, but imported ones keep syncing', function (): void {
    $imported = Document::factory()->create(['sharepoint_id' => 'unique-2', 'title' => 'Senas']);

    $result = discoveryWithGraph([discoveryPage([
        discoveryFile('drive-1', 'unique-1', folder: 'Planavimai/2026'),
        discoveryFile('drive-2', 'unique-2', folder: 'Archyvas'),
    ])], [
        'drive-1' => ['eTag' => '"e"', 'fields' => []],
        'drive-2' => ['eTag' => '"e"', 'fields' => ['Title' => 'Naujas']],
    ])->run(full: true);

    expect($result->created)->toBe(0)
        ->and(Document::query()->pluck('id')->all())->toBe([$imported->id])
        ->and($imported->refresh()->title)->toBe('Naujas')
        ->and($imported->removed_from_sharepoint_at)->toBeNull();
});

test('the first run ever files existing documents as hidden, so only later files wait', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->last_full_run_at = null;
    $settings->save();

    $first = discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'unique-1')])], [
        'drive-1' => ['eTag' => '"e"', 'fields' => []],
    ])->run();

    $later = discoveryWithGraph([discoveryPage([discoveryFile('drive-2', 'unique-2')], 'delta-3')], [
        'drive-2' => ['eTag' => '"e"', 'fields' => []],
    ])->run();

    expect($first->baseline)->toBeTrue()
        ->and($later->baseline)->toBeFalse()
        ->and(Document::query()->where('sharepoint_id', 'unique-1')->value('status'))->toBe(DocumentStatus::Hidden)
        ->and(Document::query()->where('sharepoint_id', 'unique-2')->value('status'))->toBe(DocumentStatus::Pending);
});

test('a forced full listing after the first run still files new documents as pending', function (): void {
    $result = discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'unique-1')])], [
        'drive-1' => ['eTag' => '"e"', 'fields' => []],
    ])->run(full: true);

    expect($result->baseline)->toBeFalse()
        ->and(Document::query()->sole()->status)->toBe(DocumentStatus::Pending);
});

test('a limited run adds a sample, removes nothing and keeps the feed position', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-1';
    $settings->save();
    $gone = Document::factory()->create(['sharepoint_drive_item_id' => 'drive-gone']);

    $result = discoveryWithGraph([discoveryPage([
        discoveryFile('drive-1', 'unique-1'),
        discoveryFile('drive-2', 'unique-2'),
        discoveryFile('drive-3', 'unique-3'),
        discoveryDeleted('drive-gone'),
    ])], array_fill_keys(['drive-1', 'drive-2', 'drive-3'], ['eTag' => '"e"', 'fields' => []]))->run(limit: 2);

    expect($result->limited)->toBeTrue()
        ->and($result->created)->toBe(2)
        ->and($gone->refresh()->removed_from_sharepoint_at)->toBeNull()
        ->and(app(DocumentDiscoverySettings::class)->delta_link)->toBe('delta-1');
});

test('only document file types become documents', function (): void {
    $result = discoveryWithGraph([discoveryPage([
        discoveryFile('drive-1', 'unique-1', 'Protokolas.PDF'),
        discoveryFile('drive-2', 'unique-2', 'Nuotrauka.jpg'),
        discoveryFile('drive-3', 'unique-3', 'Svetainė.url'),
        discoveryFile('drive-4', 'unique-4', 'Vaizdo įrašas.mp4'),
    ])], array_fill_keys(['drive-1', 'drive-2', 'drive-3', 'drive-4'], ['eTag' => '"e"', 'fields' => []]))->run();

    expect($result->created)->toBe(2)
        ->and(Document::query()->orderBy('name')->pluck('name')->all())->toBe(['Protokolas.PDF', 'Svetainė.url']);
});

test('an unchanged file needs no metadata lookup', function (): void {
    Document::factory()->create([
        'sharepoint_id' => 'unique-1',
        'sharepoint_drive_item_id' => 'drive-1',
        'name' => 'Protokolas.pdf',
        'sharepoint_path' => 'Dokumentų sistema/Protokolai',
        'sharepoint_web_url' => 'https://vusa.sharepoint.com/Protokolas.pdf',
        'sharepoint_modified_at' => Carbon::parse('2026-10-01T10:00:00Z')->setTimezone(config('app.timezone')),
    ]);

    $result = discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'unique-1')])])->run();

    expect($result->updated)->toBe(0)->and($result->failed)->toBe(0);
});

test('a file moved to another folder gets its new path, though moving leaves its modification time alone', function (): void {
    $document = Document::factory()->create([
        'sharepoint_id' => 'unique-1',
        'sharepoint_drive_item_id' => 'drive-1',
        'name' => 'Protokolas.pdf',
        'sharepoint_path' => 'Dokumentų sistema/Protokolai',
        'sharepoint_modified_at' => Carbon::parse('2026-10-01T10:00:00Z')->setTimezone(config('app.timezone')),
    ]);

    // No list item lookup is offered: a move must not need one.
    $result = discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'unique-1', folder: 'Dokumentų sistema/Nutarimai')])])->run();

    expect($result->updated)->toBe(1)
        ->and($result->failed)->toBe(0)
        ->and($document->refresh()->sharepoint_path)->toBe('Dokumentų sistema/Nutarimai');
});

test('a file at the drive root is stored without a folder instead of keeping its old one', function (): void {
    $document = Document::factory()->create([
        'sharepoint_id' => 'unique-1',
        'sharepoint_drive_item_id' => 'drive-1',
        'sharepoint_path' => 'Dokumentų sistema/Protokolai',
        'sharepoint_modified_at' => Carbon::parse('2026-10-01T10:00:00Z')->setTimezone(config('app.timezone')),
    ]);
    $item = discoveryFile('drive-1', 'unique-1');
    $parent = new ItemReference;
    $parent->setId('root');
    $item->setParentReference($parent);

    discoveryWithGraph([['items' => [discoveryFolder('root', 'root', null), $item], 'nextLink' => null, 'deltaLink' => 'delta-2']])->run();

    expect($document->refresh()->sharepoint_path)->toBeNull();
});

test('renaming a folder and a folder inside it maps each file once, keeping both new names', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-1';
    $settings->save();
    SharepointFolder::query()->insert([
        ['drive_item_id' => 'root', 'parent_id' => null, 'name' => ''],
        ['drive_item_id' => 'f-a', 'parent_id' => 'root', 'name' => 'A'],
        ['drive_item_id' => 'f-b', 'parent_id' => 'f-a', 'name' => 'B'],
        ['drive_item_id' => 'f-x', 'parent_id' => 'root', 'name' => 'X'],
    ]);
    $nested = Document::factory()->create(['sharepoint_path' => 'A/B']);
    $direct = Document::factory()->create(['sharepoint_path' => 'A']);
    $x = Document::factory()->create(['sharepoint_path' => 'X']);

    // A → C with A/B → C/D, and X takes A's old name: a chained move that must not move A's files twice.
    discoveryWithGraph([['items' => [
        discoveryFolder('f-a', 'C', 'root'),
        discoveryFolder('f-b', 'D', 'f-a'),
        discoveryFolder('f-x', 'A', 'root'),
    ], 'nextLink' => null, 'deltaLink' => 'delta-3']])->run();

    expect($nested->refresh()->sharepoint_path)->toBe('C/D')
        ->and($direct->refresh()->sharepoint_path)->toBe('C')
        ->and($x->refresh()->sharepoint_path)->toBe('A');
});

test('a refused mass removal changes no stored folder or path', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-1';
    $settings->save();
    SharepointFolder::query()->insert([
        ['drive_item_id' => 'root', 'parent_id' => null, 'name' => ''],
        ['drive_item_id' => 'f-a', 'parent_id' => 'root', 'name' => 'A'],
    ]);
    $moved = Document::factory()->create(['sharepoint_path' => 'A']);
    $documents = Document::factory()->count(SharepointDocumentDiscovery::MAX_REMOVALS + 1)->sequence(
        fn ($sequence) => ['sharepoint_drive_item_id' => "drive-{$sequence->index}"]
    )->create();

    $result = discoveryWithGraph([['items' => [
        discoveryFolder('f-a', 'B', 'root'),
        ...$documents->map(fn (Document $document) => discoveryDeleted($document->sharepoint_drive_item_id))->all(),
    ], 'nextLink' => null, 'deltaLink' => 'delta-3']])->run();

    expect($result->abortReason)->toBe('mass_removal')
        ->and($moved->refresh()->sharepoint_path)->toBe('A')
        ->and(SharepointFolder::query()->find('f-a')->name)->toBe('A');
});

test('a file whose data cannot be stored fails alone and holds the checkpoint back', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-1';
    $settings->save();
    // Stands in for whatever a malformed list item trips over while being stored.
    Document::saving(function (Document $document): void {
        throw_if($document->name === 'Blogas.pdf', new InvalidArgumentException('malformed'));
    });

    $result = discoveryWithGraph([discoveryPage([
        discoveryFile('drive-1', 'unique-1', 'Geras.pdf'),
        discoveryFile('drive-2', 'unique-2', 'Blogas.pdf'),
    ])], [
        'drive-1' => ['eTag' => '"e"', 'fields' => []],
        'drive-2' => ['eTag' => '"e"', 'fields' => []],
    ])->run();

    expect($result->failed)->toBe(1)
        ->and(Document::query()->pluck('name')->all())->toBe(['Geras.pdf'])
        ->and(app(DocumentDiscoverySettings::class)->delta_link)->toBe('delta-1');
});

test('an unreadable date leaves the date empty rather than failing the file', function (): void {
    discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'unique-1')])], [
        'drive-1' => ['eTag' => '"e"', 'fields' => ['Date' => 'ne data']],
    ])->run();

    expect(Document::query()->sole()->document_date)->toBeNull();
});

test('a changed or restored published file has its public link checked again', function (): void {
    Queue::fake([SyncDocumentFromSharePointJob::class]);
    $restored = Document::factory()->removedFromSharepoint()->create(['sharepoint_id' => 'unique-1', 'sharepoint_drive_item_id' => 'drive-1']);
    $pending = Document::factory()->pending()->create(['sharepoint_id' => 'unique-2', 'sharepoint_drive_item_id' => 'drive-old']);

    discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'unique-1'), discoveryFile('drive-2', 'unique-2')])], [
        'drive-1' => ['eTag' => '"e"', 'fields' => []],
        'drive-2' => ['eTag' => '"e"', 'fields' => []],
    ])->run();

    Queue::assertPushed(SyncDocumentFromSharePointJob::class, fn ($job) => $job->document->is($restored));
    Queue::assertNotPushed(SyncDocumentFromSharePointJob::class, fn ($job) => $job->document->is($pending));
});

test('a padalinys created after its files arrived is matched without the files changing', function (): void {
    $document = Document::factory()->pending()->create([
        'sharepoint_id' => 'unique-1',
        'institution_id' => null,
        'sharepoint_institution_label' => 'VU SA Naujas',
    ]);
    $institution = Institution::factory()->create(['name' => ['lt' => 'VU SA Naujas', 'en' => 'VU SR New']]);

    $result = discoveryWithGraph([discoveryPage([])])->run();

    expect($result->rematched)->toBe(1)
        ->and($document->refresh()->institution_id)->toBe($institution->id);
});

test('a file deleted in SharePoint is marked removed and leaves the public site', function (): void {
    $document = Document::factory()->create(['sharepoint_drive_item_id' => 'drive-1']);
    $bystander = Document::factory()->create(['sharepoint_drive_item_id' => 'drive-2']);

    $result = discoveryWithGraph([discoveryPage([discoveryDeleted('drive-1')])])->run();

    expect($result->removed)->toBe(1)
        ->and($document->refresh()->removed_from_sharepoint_at)->not->toBeNull()
        ->and($document->status)->toBe(DocumentStatus::Published)
        ->and($document->shouldBeSearchable())->toBeFalse()
        ->and(Document::query()->published()->pluck('id')->all())->toBe([$bystander->id]);
});

test('a file restored from the recycle bin comes back with its status', function (): void {
    $document = Document::factory()->removedFromSharepoint()->create(['sharepoint_id' => 'unique-1', 'sharepoint_drive_item_id' => 'drive-1']);

    $result = discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'unique-1')])], [
        'drive-1' => ['eTag' => '"e3"', 'fields' => []],
    ])->run();

    expect($result->restored)->toBe(1)
        ->and($document->refresh()->removed_from_sharepoint_at)->toBeNull()
        ->and($document->isPublished())->toBeTrue();
});

test('a full listing marks documents the archive no longer contains as removed', function (): void {
    $kept = Document::factory()->create(['sharepoint_id' => 'unique-1', 'sharepoint_list_id' => DISCOVERY_LIST]);
    $gone = Document::factory()->create(['sharepoint_id' => 'unique-2', 'sharepoint_list_id' => DISCOVERY_LIST]);
    $otherLibrary = Document::factory()->create(['sharepoint_id' => 'unique-3', 'sharepoint_list_id' => 'another-list']);

    discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'unique-1')])], [
        'drive-1' => ['eTag' => '"e"', 'fields' => []],
    ])->run(full: true);

    expect($kept->refresh()->removed_from_sharepoint_at)->toBeNull()
        ->and($gone->refresh()->removed_from_sharepoint_at)->not->toBeNull()
        ->and($otherLibrary->refresh()->removed_from_sharepoint_at)->toBeNull();
});

test('a run that would remove too many documents is refused and keeps the old delta link', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-1';
    $settings->save();

    $documents = Document::factory()->count(SharepointDocumentDiscovery::MAX_REMOVALS + 1)->sequence(
        fn ($sequence) => ['sharepoint_drive_item_id' => "drive-{$sequence->index}"]
    )->create();

    $result = discoveryWithGraph([discoveryPage($documents->map(fn (Document $document) => discoveryDeleted($document->sharepoint_drive_item_id))->all())])->run();

    expect($result->abortReason)->toBe('mass_removal')
        ->and(Document::query()->whereNotNull('removed_from_sharepoint_at')->count())->toBe(0)
        ->and(app(DocumentDiscoverySettings::class)->delta_link)->toBe('delta-1');
});

test('a failed metadata lookup keeps the old delta link so the next run retries', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-1';
    $settings->save();

    $result = discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'unique-1')])], [])->run();

    expect($result->failed)->toBe(1)
        ->and(Document::query()->count())->toBe(0)
        ->and(app(DocumentDiscoverySettings::class)->delta_link)->toBe('delta-1');
});

test('an expired delta link falls back to a full listing', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-old';
    $settings->save();

    $graph = Mockery::mock(SharepointGraphService::class);
    $graph->shouldReceive('getDriveDeltaPage')->with('delta-old')->andThrow(new SharepointDeltaExpiredException);
    $graph->shouldReceive('getDriveDeltaPage')->with(null)->andReturn(discoveryPage([discoveryFile('drive-1', 'unique-1')], 'delta-new'));
    $graph->shouldReceive('getListItemsForDriveItems')->andReturn(['items' => ['drive-1' => ['eTag' => '"e"', 'fields' => []]], 'transient' => []]);

    $discovery = Mockery::mock(SharepointDocumentDiscovery::class, [$settings])->makePartial()->shouldAllowMockingProtectedMethods();
    $discovery->shouldReceive('makeGraphService')->andReturn($graph);

    $result = $discovery->run();

    expect($result->full)->toBeTrue()
        ->and($result->created)->toBe(1)
        ->and(app(DocumentDiscoverySettings::class)->delta_link)->toBe('delta-new')
        ->and(app(DocumentDiscoverySettings::class)->last_full_run_at)->not->toBeNull();
});

test('a dry run writes nothing but reports unmatched padalinys labels', function (): void {
    $result = discoveryWithGraph([discoveryPage([discoveryFile('drive-1', 'unique-1')])], [
        'drive-1' => ['eTag' => '"e"', 'fields' => ['Padalinys' => ['Label' => 'Nežinomas padalinys']]],
    ])->run(dryRun: true);

    expect($result->created)->toBe(1)
        ->and(array_keys($result->unmatchedLabels))->toBe(['Nežinomas padalinys'])
        ->and(Document::query()->count())->toBe(0)
        ->and(app(DocumentDiscoverySettings::class)->delta_link)->toBeNull();
});

test('the system status flags a discovery that stopped finishing', function (?string $lastRun, string $expected): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->last_run_at = $lastRun;
    $settings->save();

    expect(app(SystemMonitorService::class)->getDocumentDiscoveryStatus()['status'])->toBe($expected);
})->with([
    'recent' => [fn () => now()->subMinutes(20)->toIso8601String(), 'healthy'],
    'a few hours ago' => [fn () => now()->subHours(3)->toIso8601String(), 'warning'],
    'over a day ago' => [fn () => now()->subDays(2)->toIso8601String(), 'error'],
    'never' => [null, 'warning'],
]);

test('a file that keeps failing stops holding the checkpoint back after three runs', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-1';
    $settings->save();
    $page = fn () => [discoveryPage([discoveryFile('drive-1', 'unique-1')], 'delta-next')];

    $first = discoveryWithGraph($page())->run();
    $second = discoveryWithGraph($page())->run();

    expect($first->givenUp)->toBe(0)
        ->and($second->givenUp)->toBe(0)
        ->and(app(DocumentDiscoverySettings::class)->delta_link)->toBe('delta-1');

    $third = discoveryWithGraph($page())->run();

    expect($third->givenUp)->toBe(1)
        ->and(app(DocumentDiscoverySettings::class)->delta_link)->toBe('delta-next');
});

test('a file failing only transiently keeps holding the checkpoint back and is never given up', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-1';
    $settings->save();
    $page = fn () => [discoveryPage([discoveryFile('drive-1', 'unique-1')], 'delta-next')];

    foreach (range(1, 4) as $_) {
        $result = discoveryWithGraph($page(), transient: ['drive-1'])->run();

        expect($result->givenUp)->toBe(0);
    }

    expect(app(DocumentDiscoverySettings::class)->delta_link)->toBe('delta-1')
        ->and(app(DocumentDiscoverySettings::class)->skipped_files)->toBe(0);
});

test('a long throttle pauses discovery without moving the checkpoint', function (): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->delta_link = 'delta-1';
    $settings->save();

    $graph = Mockery::mock(SharepointGraphService::class);
    $graph->shouldReceive('getDriveDeltaPage')->once()->andReturn(discoveryPage([discoveryFile('drive-1', 'unique-1')], 'delta-next'));
    $graph->shouldReceive('getListItemsForDriveItems')->once()->andThrow(new SharepointThrottledException(600));

    $discovery = Mockery::mock(SharepointDocumentDiscovery::class, [$settings])->makePartial()->shouldAllowMockingProtectedMethods();
    $discovery->shouldReceive('makeGraphService')->andReturn($graph);

    expect($discovery->run()->abortReason)->toBe('throttled')
        ->and($discovery->run()->abortReason)->toBe('throttled')
        ->and(app(DocumentDiscoverySettings::class)->delta_link)->toBe('delta-1');
});

test('the scheduled job turns a run into a full listing once a week', function (?string $lastFull, bool $expectFull): void {
    $settings = app(DocumentDiscoverySettings::class);
    $settings->last_full_run_at = $lastFull;
    $settings->save();

    $discovery = Mockery::mock(SharepointDocumentDiscovery::class);
    $discovery->shouldReceive('run')->once()->with($expectFull)->andReturn(new DiscoveryResult);

    (new DiscoverSharepointDocumentsJob)->handle($discovery, app(DocumentDiscoverySettings::class));
})->with([
    'recent full listing' => [fn () => now()->subDays(2)->toIso8601String(), false],
    'over a week ago' => [fn () => now()->subDays(8)->toIso8601String(), true],
]);
