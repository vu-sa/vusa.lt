<?php

use App\Actions\Documents\UpdateDocumentStatus;
use App\Enums\DocumentStatus;
use App\Helpers\ShortUrlHelper;
use App\Jobs\DiscoverSharepointDocumentsJob;
use App\Jobs\RevokeSharepointPermissionJob;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Tenant;
use Database\Seeders\RoleDocumentManagerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake();

    $this->tenant = Tenant::query()->where('id', '!=', Tenant::main()->id)->first();
    $this->otherTenant = Tenant::query()->whereNotIn('id', [$this->tenant->id, Tenant::main()->id])->first();
    $this->regularUser = makeUser($this->tenant);
    $this->manager = makeTenantUserWithRole(RoleDocumentManagerSeeder::NAME, $this->tenant);
    $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
    $this->pending = Document::factory()->pending()->create(['institution_id' => $this->institution->id]);
});

describe('unauthorized access', function (): void {
    test('a member without document rights cannot publish', function (): void {
        asUser($this->regularUser)
            ->post(route('documents.status'), ['document_ids' => [$this->pending->id], 'status' => 'published'])
            ->assertForbidden();

        expect($this->pending->refresh()->status)->toBe(DocumentStatus::Pending);
    });

    test('a member without document rights cannot list unpublished files', function (): void {
        asUser($this->regularUser)->getJson(route('api.v1.admin.documents.folder', ['flat' => 1, 'show' => 'pending']))->assertForbidden();
    });

    test('a member without document rights cannot trigger a SharePoint check', function (): void {
        asUser($this->regularUser)->post(route('documents.discover'))->assertForbidden();

        Queue::assertNotPushed(DiscoverSharepointDocumentsJob::class);
    });

    test('a pending document cannot be opened by someone who could only read it', function (): void {
        asUser($this->regularUser)->get(route('documents.show', $this->pending))->assertForbidden();
    });
});

describe('authorized access', function (): void {
    test('publishing queues the public link', function (): void {
        asUser($this->manager)
            ->post(route('documents.status'), ['document_ids' => [$this->pending->id], 'status' => 'published'])
            ->assertRedirect();

        $this->pending->refresh();

        expect($this->pending->status)->toBe(DocumentStatus::Published)
            ->and($this->pending->published_by)->toBe($this->manager->id)
            ->and($this->pending->published_at)->not->toBeNull();

        Queue::assertPushed(SyncDocumentFromSharePointJob::class, fn ($job) => $job->document->is($this->pending) && $job->force);
    });

    test('hiding a published document revokes its link', function (): void {
        $published = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'anonymous_url' => 'https://share/x',
            'sharepoint_permission_id' => 'perm-1',
        ]);

        asUser($this->manager)
            ->post(route('documents.status'), ['document_ids' => [$published->id], 'status' => 'hidden'])
            ->assertRedirect();

        expect($published->refresh()->status)->toBe(DocumentStatus::Hidden)
            ->and($published->anonymous_url)->toBeNull();

        Queue::assertPushed(RevokeSharepointPermissionJob::class);
    });

    test('hiding a file that was never shown does not say it was taken off vusa.lt', function (): void {
        asUser($this->manager)->postJson(route('api.v1.admin.documents.status'), ['document_ids' => [$this->pending->id], 'status' => 'hidden'])
            ->assertOk()
            ->assertJsonPath('message', __('messages.document.hidden_unpublished', ['count' => 1]));
    });

    test('hiding revokes a link stored after the request loaded the document', function (): void {
        $published = Document::factory()->create(['institution_id' => $this->institution->id, 'anonymous_url' => null, 'sharepoint_permission_id' => null]);
        // Loaded before the link exists, as a request does while the sync job is still creating it.
        $loadedEarlier = Document::query()->whereKey($published->id)->get();
        Document::query()->whereKey($published->id)->update(['anonymous_url' => 'https://share/new', 'sharepoint_permission_id' => 'perm-new']);

        UpdateDocumentStatus::execute($loadedEarlier, DocumentStatus::Hidden, $this->manager);

        expect($published->refresh()->status)->toBe(DocumentStatus::Hidden)
            ->and($published->anonymous_url)->toBeNull()
            ->and($published->sharepoint_permission_id)->toBeNull();

        Queue::assertPushed(RevokeSharepointPermissionJob::class, fn ($job) => $job->sharepointPermissionId === 'perm-new');
    });

    test('the index shows how many files wait in each view', function (): void {
        Document::factory()->pending()->removedFromSharepoint()->create(['institution_id' => $this->institution->id]);

        asUser($this->manager)->get(route('documents.index'))
            ->assertInertia(fn ($page) => $page
                ->where('discovery.counts', ['pending' => 1, 'removed' => 1])
            );
    });

    test('a manager can trigger a SharePoint check', function (): void {
        asUser($this->manager)->post(route('documents.discover'))->assertRedirect();

        Queue::assertPushed(DiscoverSharepointDocumentsJob::class);
    });

    test('the pending list names metadata problems', function (): void {
        $this->pending->update(['content_type' => null]);

        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => '', 'flat' => 1, 'show' => 'pending']))
            ->assertOk()
            ->assertJsonCount(1, 'data.files')
            ->assertJsonPath('data.files.0.id', $this->pending->id)
            ->assertJsonPath('data.files.0.problems', ['content_type']);
    });

    test('links to the retired separate views land on the matching filters', function (string $query, array $expected): void {
        asUser($this->manager)->get(route('documents.index').'?'.$query)
            ->assertRedirect(route('documents.index', $expected));
    })->with([
        'removed queue' => ['queue=removed', ['status' => 'removed', 'layout' => 'list']],
        'folder view' => ['browse=folders', ['layout' => 'folders']],
    ]);
});

describe('tenant isolation', function (): void {
    test('a manager sees other padaliniai\' published files read-only, but not their unpublished ones', function (): void {
        $foreignInstitution = Institution::factory()->create(['tenant_id' => $this->otherTenant->id]);
        Document::factory()->pending()->create(['institution_id' => $foreignInstitution->id]);
        Document::factory()->pending()->create(['institution_id' => null]);
        $foreignPublished = Document::factory()->create(['institution_id' => $foreignInstitution->id, 'anonymous_url' => 'https://share/x']);

        $files = collect(asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => '', 'flat' => 1]))->assertOk()->json('data.files'))->keyBy('id');

        expect($files->keys()->all())->toEqualCanonicalizing([$this->pending->id, $foreignPublished->id])
            ->and($files[$foreignPublished->id]['can']['update'])->toBeFalse()
            ->and($files[$this->pending->id]['can']['update'])->toBeTrue();
    });

    test('a manager cannot publish another padalinys\' file, even alongside their own', function (): void {
        $foreign = Document::factory()->pending()->create(['institution_id' => Institution::factory()->create(['tenant_id' => $this->otherTenant->id])->id]);

        asUser($this->manager)
            ->post(route('documents.status'), ['document_ids' => [$this->pending->id, $foreign->id], 'status' => 'published'])
            ->assertForbidden();

        expect($this->pending->refresh()->status)->toBe(DocumentStatus::Pending)
            ->and($foreign->refresh()->status)->toBe(DocumentStatus::Pending);
    });

    test('a file without a padalinys is left to managers with all-tenant scope', function (): void {
        $orphan = Document::factory()->pending()->create(['institution_id' => null]);

        asUser($this->manager)
            ->post(route('documents.status'), ['document_ids' => [$orphan->id], 'status' => 'published'])
            ->assertForbidden();

        asUser(makeAdminUser($this->tenant))
            ->post(route('documents.status'), ['document_ids' => [$orphan->id], 'status' => 'published'])
            ->assertRedirect();

        expect($orphan->refresh()->status)->toBe(DocumentStatus::Published);
    });

    test('files without a padalinys appear in the pending view of all-tenant managers only', function (): void {
        $orphan = Document::factory()->pending()->create(['institution_id' => null]);

        asUser(makeAdminUser($this->tenant))->getJson(route('api.v1.admin.documents.folder', ['path' => '', 'flat' => 1, 'show' => 'pending']))
            ->assertOk()
            ->assertJsonCount(2, 'data.files')
            ->assertJsonFragment(['id' => $orphan->id]);

        expect($orphan->metadataProblems())->toContain('institution');
    });
});

describe('public visibility', function (): void {
    test('only a published document resolves its short link', function (DocumentStatus $status, bool $removed, int $expected): void {
        $document = Document::factory()->create([
            'status' => $status,
            'anonymous_url' => 'https://share/x',
            'removed_from_sharepoint_at' => $removed ? now() : null,
        ]);

        $this->get('/d/'.ShortUrlHelper::encode($document->id))->assertStatus($expected);
    })->with([
        'published' => [DocumentStatus::Published, false, 302],
        'pending' => [DocumentStatus::Pending, false, 404],
        'hidden' => [DocumentStatus::Hidden, false, 404],
        'removed from SharePoint' => [DocumentStatus::Published, true, 404],
    ]);

    test('the public filter lists only content types of published documents', function (): void {
        $this->pending->update(['content_type' => 'Tik paslėptų rūšis']);
        Document::factory()->create(['content_type' => 'Vieša rūšis', 'anonymous_url' => 'https://share/x']);

        $this->get(route('documents', ['subdomain' => 'www', 'lang' => 'lt']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('allContentTypes', fn ($types) => collect($types)->contains('Vieša rūšis') && ! collect($types)->contains('Tik paslėptų rūšis')));
    });

    test('only a published document with a link is indexed', function (array $attributes, bool $indexed): void {
        $document = Document::factory()->create($attributes);

        // The engine skips an empty record, which is what keeps a stale queued MakeSearchable harmless.
        expect($document->shouldBeSearchable())->toBe($indexed)
            ->and($document->toSearchableArray() !== [])->toBe($indexed);
    })->with([
        'published with a link' => [['anonymous_url' => 'https://share/x'], true],
        'published, link not created yet' => [['anonymous_url' => null], false],
        'pending' => [['status' => DocumentStatus::Pending, 'anonymous_url' => 'https://share/x'], false],
        'hidden' => [['status' => DocumentStatus::Hidden, 'anonymous_url' => 'https://share/x'], false],
        'removed from SharePoint' => [['removed_from_sharepoint_at' => now(), 'anonymous_url' => 'https://share/x'], false],
    ]);

    test('no write path or reindex sends an unpublished document to the index', function (): void {
        $sent = [];
        $engine = Mockery::spy(NullEngine::class);
        $engine->shouldReceive('update')->andReturnUsing(function ($models) use (&$sent): void {
            foreach ($models as $model) {
                if ($model->toSearchableArray() !== []) {
                    $sent[] = $model->id;
                }
            }
        });
        $manager = app(EngineManager::class);
        $manager->extend('typesense', fn () => $engine);
        $manager->forgetEngines();

        $published = Document::factory()->create(['anonymous_url' => 'https://share/x']);
        $hidden = Document::factory()->create(['anonymous_url' => 'https://share/y', 'sharepoint_permission_id' => 'perm-1']);
        UpdateDocumentStatus::execute(Document::query()->whereKey($hidden->id)->get(), DocumentStatus::Hidden, $this->manager);
        $removed = Document::factory()->create(['anonymous_url' => 'https://share/z']);
        $removed->update(['removed_from_sharepoint_at' => now()]);
        Document::factory()->pending()->create();

        $sent = [];
        Document::query()->searchable();

        expect($sent)->toBe([$published->id]);
        $engine->shouldHaveReceived('delete')->withArgs(fn ($models) => $models->contains('id', $hidden->id));
        $engine->shouldHaveReceived('delete')->withArgs(fn ($models) => $models->contains('id', $removed->id));
    });
});

describe('folder view', function (): void {
    beforeEach(function (): void {
        $this->base = 'Dokumentų sistema/00. Lietuvių kalba/01. VU SA Padaliniai/MIF';
        $this->pending->update(['sharepoint_path' => $this->base.'/Protokolai', 'name' => 'b.pdf']);
        Document::factory()->create(['institution_id' => $this->institution->id, 'sharepoint_path' => $this->base.'/Protokolai', 'name' => 'a.pdf']);
        Document::factory()->inactive()->create(['institution_id' => $this->institution->id, 'sharepoint_path' => $this->base.'/10. Ataskaitos/2026', 'name' => 'c.pdf']);
        Document::factory()->create(['institution_id' => $this->institution->id, 'sharepoint_path' => $this->base, 'name' => 'Nuostatai.pdf']);
    });

    test('opens in the folder that holds the manager\'s files, folders first with counts', function (): void {
        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder'))
            ->assertOk()
            ->assertJsonPath('data.path', $this->base)
            ->assertJsonPath('data.breadcrumbs.3', ['name' => 'MIF', 'path' => $this->base])
            ->assertJsonPath('data.folders', [
                ['name' => '10. Ataskaitos', 'path' => $this->base.'/10. Ataskaitos', 'counts' => ['published' => 0, 'pending' => 0, 'hidden' => 1]],
                ['name' => 'Protokolai', 'path' => $this->base.'/Protokolai', 'counts' => ['published' => 1, 'pending' => 1, 'hidden' => 0]],
            ])
            ->assertJsonPath('data.files.0.name', 'Nuostatai.pdf');
    });

    test('a large folder is listed page by page, each file once', function (): void {
        Document::factory()->count(100)->sequence(fn ($sequence) => ['name' => sprintf('z-%03d.pdf', $sequence->index)])
            ->create(['institution_id' => $this->institution->id, 'sharepoint_path' => $this->base.'/Protokolai']);
        $folder = ['path' => $this->base.'/Protokolai'];

        $first = asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', $folder))->assertOk();
        $second = asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', [...$folder, 'offset' => $first->json('data.next_offset')]))->assertOk();

        expect($first->json('data.files'))->toHaveCount(100)
            ->and($first->json('data.next_offset'))->toBe(100)
            ->and($second->json('data.next_offset'))->toBeNull()
            ->and(collect([...$first->json('data.files'), ...$second->json('data.files')])->pluck('id')->unique())->toHaveCount(102);
    });

    test('a row says whether the user may change it', function (): void {
        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base.'/Protokolai']))
            ->assertOk()
            ->assertJsonPath('data.files.0.can.update', true);
    });

    test('lists a folder\'s files with their state', function (): void {
        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base.'/Protokolai']))
            ->assertOk()
            ->assertJsonPath('data.folders', [])
            ->assertJsonPath('data.files.0.name', 'a.pdf')
            ->assertJsonPath('data.files.0.status', 'published')
            ->assertJsonPath('data.files.1.status', 'pending');
    });

    test('"pending" keeps just pending files and the folders that lead to them', function (): void {
        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base, 'show' => 'pending']))
            ->assertOk()
            ->assertJsonCount(1, 'data.folders')
            ->assertJsonPath('data.folders.0.name', 'Protokolai')
            ->assertJsonCount(0, 'data.files');
    });

    test('"hidden" keeps just the files not shown, and the folder offers its content types', function (): void {
        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base, 'show' => 'hidden']))
            ->assertOk()
            ->assertJsonPath('data.folders.0.name', '10. Ataskaitos')
            ->assertJsonCount(1, 'data.folders')
            ->assertJsonPath('data.content_types', Document::query()->whereNotNull('content_type')->distinct()->orderBy('content_type')->pluck('content_type')->all());
    });

    test('the content type filter narrows files and folders', function (): void {
        $this->pending->update(['content_type' => 'Protokolai']);

        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base, 'content_type' => 'Protokolai']))
            ->assertOk()
            ->assertJsonPath('data.folders.0.name', 'Protokolai')
            ->assertJsonPath('data.folders.0.counts.pending', 1);
    });

    test('search finds files anywhere below the folder', function (): void {
        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base, 'search' => 'c.pdf']))
            ->assertOk()
            ->assertJsonCount(1, 'data.files')
            ->assertJsonPath('data.files.0.sharepoint_path', $this->base.'/10. Ataskaitos/2026');
    });

    test('another padalinys\' unpublished files never show up, even at the top', function (): void {
        Document::factory()->pending()->create([
            'institution_id' => Institution::factory()->create(['tenant_id' => $this->otherTenant->id])->id,
            'sharepoint_path' => 'Dokumentų sistema/00. Lietuvių kalba/01. VU SA Padaliniai/KITAS',
        ]);

        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => 'Dokumentų sistema/00. Lietuvių kalba/01. VU SA Padaliniai']))
            ->assertOk()
            ->assertJsonCount(1, 'data.folders')
            ->assertJsonPath('data.folders.0.name', 'MIF');
    });

    test('the page gives the database view to managers only', function (): void {
        asUser($this->manager)->get(route('documents.index'))
            ->assertInertia(fn ($page) => $page->whereNot('discovery', null));

        asUser($this->regularUser)->get(route('documents.index', ['browse' => 'folders']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('discovery', null));
    });

    test('the list shows every file below the folder, the latest change first', function (): void {
        Document::factory()->create(['institution_id' => $this->institution->id, 'sharepoint_path' => $this->base.'/Protokolai', 'name' => 'newest.pdf', 'sharepoint_modified_at' => now()->addDay()]);

        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base, 'flat' => 1]))
            ->assertOk()
            ->assertJsonPath('data.folders', [])
            ->assertJsonCount(5, 'data.files')
            ->assertJsonPath('data.files.0.name', 'newest.pdf');
    });

    test('a refresh can ask for everything already shown at once', function (): void {
        $response = asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base, 'flat' => 1, 'limit' => 2]))->assertOk();

        expect($response->json('data.files'))->toHaveCount(2)
            ->and($response->json('data.next_offset'))->toBe(2);
    });

    test('files removed from SharePoint are listed from the whole archive, whatever folder is open', function (): void {
        $removed = Document::factory()->removedFromSharepoint()->create(['institution_id' => $this->institution->id, 'sharepoint_path' => 'Dokumentų sistema/Kitur']);

        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base, 'show' => 'removed']))
            ->assertOk()
            ->assertJsonPath('data.files.0.id', $removed->id);
    });

    test('files removed from SharePoint are listed only under their own filter', function (): void {
        $removed = Document::factory()->pending()->removedFromSharepoint()->create(['institution_id' => $this->institution->id, 'sharepoint_path' => $this->base]);

        $all = asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base, 'flat' => 1]))->json('data.files');
        asUser($this->manager)->getJson(route('api.v1.admin.documents.folder', ['path' => $this->base, 'show' => 'removed']))
            ->assertOk()
            ->assertJsonPath('data.folders', [])
            ->assertJsonCount(1, 'data.files')
            ->assertJsonPath('data.files.0.id', $removed->id);

        expect(collect($all)->pluck('id'))->not->toContain($removed->id);
    });

    test('the folder view shows a file through the API and gets the updated row back', function (): void {
        asUser($this->manager)->postJson(route('api.v1.admin.documents.status'), ['document_ids' => [$this->pending->id], 'status' => 'published'])
            ->assertOk()
            ->assertJsonPath('data.0.id', $this->pending->id)
            ->assertJsonPath('data.0.status', 'published');

        Queue::assertPushed(SyncDocumentFromSharePointJob::class);
    });

    test('the API refuses another padalinys\' file and members without rights', function (): void {
        $foreign = Document::factory()->pending()->create(['institution_id' => Institution::factory()->create(['tenant_id' => $this->otherTenant->id])->id]);

        asUser($this->manager)->postJson(route('api.v1.admin.documents.status'), ['document_ids' => [$foreign->id], 'status' => 'published'])->assertForbidden();
        asUser($this->regularUser)->postJson(route('api.v1.admin.documents.status'), ['document_ids' => [$this->pending->id], 'status' => 'published'])->assertForbidden();

        expect($foreign->refresh()->status)->toBe(DocumentStatus::Pending);
    });

    test('a member without document rights cannot browse folders', function (): void {
        asUser($this->regularUser)->getJson(route('api.v1.admin.documents.folder'))->assertForbidden();
    });
});

describe('deleting files removed from SharePoint', function (): void {
    test('a manager deletes their padalinys\' removed files', function (): void {
        $removed = Document::factory()->removedFromSharepoint()->create(['institution_id' => $this->institution->id]);

        asUser($this->manager)->deleteJson(route('api.v1.admin.documents.destroyRemoved'), ['document_ids' => [$removed->id]])
            ->assertOk()
            ->assertJsonPath('data.ids', [$removed->id]);

        expect(Document::query()->find($removed->id))->toBeNull();
    });

    test('a file still in SharePoint is never deleted', function (): void {
        asUser($this->manager)->deleteJson(route('api.v1.admin.documents.destroyRemoved'), ['document_ids' => [$this->pending->id]])
            ->assertUnprocessable();

        expect($this->pending->fresh())->not->toBeNull();
    });

    test('another padalinys\' removed file and members without rights are refused', function (): void {
        $foreign = Document::factory()->removedFromSharepoint()->create(['institution_id' => Institution::factory()->create(['tenant_id' => $this->otherTenant->id])->id]);
        $own = Document::factory()->removedFromSharepoint()->create(['institution_id' => $this->institution->id]);

        asUser($this->manager)->deleteJson(route('api.v1.admin.documents.destroyRemoved'), ['document_ids' => [$foreign->id]])->assertForbidden();
        asUser($this->regularUser)->deleteJson(route('api.v1.admin.documents.destroyRemoved'), ['document_ids' => [$own->id]])->assertForbidden();

        expect($foreign->fresh())->not->toBeNull()->and($own->fresh())->not->toBeNull();
    });
});
