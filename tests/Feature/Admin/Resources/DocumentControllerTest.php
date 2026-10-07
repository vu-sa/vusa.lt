<?php

use App\Enums\DocumentStatus;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Tenant;
use Database\Seeders\RoleDocumentManagerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // The index action serves data straight from Typesense (Document::search()),
    // which the suite's default NullEngine would leave empty.
    usesTypesense();

    $this->tenant = Tenant::query()->first();
    $this->regularUser = makeUser($this->tenant);
    $this->documentManager = makeUser($this->tenant);
    $this->documentManager->duties()->first()->assignRole(RoleDocumentManagerSeeder::NAME);
    $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('unauthorized access', function (): void {
    test('browses documents without management abilities', function (): void {
        asUser($this->regularUser)->get(route('documents.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Files/IndexDocument')
                ->where('abilities', ['create' => false, 'update' => false, 'updateTenantShortnames' => []])
                ->where('discovery', null)
                ->where('defaultTenantShortnames', [$this->tenant->shortname, Tenant::main()->shortname])
            );
    });

    test('cannot refresh documents', function (): void {
        $document = Document::factory()->create(['institution_id' => $this->institution->id]);

        $response = asUser($this->regularUser)->post(route('documents.refresh', $document));
        expect($response->status())->toBe(403);
    });

});

describe('authorized access', function (): void {
    test('document manager can access documents index', function (): void {
        Document::factory()->count(3)->create(['institution_id' => $this->institution->id]);

        $response = asUser($this->documentManager)->get(route('documents.index'));
        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Files/IndexDocument')
                ->has('importantContentTypes')
                ->where('abilities.create', true)
            );
    });

    test('admin can access documents index', function (): void {
        $admin = makeTenantUserWithRole(RoleDocumentManagerSeeder::NAME, $this->tenant);
        Document::factory()->count(2)->create(['institution_id' => $this->institution->id]);

        $response = asUser($admin)->get(route('documents.index'));
        $response->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Files/IndexDocument')
                ->has('importantContentTypes')
            );
    });

    test('show redirects to anonymous url if available', function (): void {
        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'anonymous_url' => 'https://example.sharepoint.com/:b:/test',
        ]);

        $response = asUser($this->documentManager)->get(route('documents.show', $document));
        $response->assertRedirect('https://example.sharepoint.com/:b:/test');
    });

    test('show redirects to index if anonymous url not available', function (): void {
        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'anonymous_url' => null,
        ]);

        $response = asUser($this->documentManager)->get(route('documents.show', $document));
        $response->assertRedirect(route('documents.index'));
    });

    test('the archive learns which padaliniai\' rows the manager may change', function (): void {
        asUser($this->documentManager)->get(route('documents.index'))
            ->assertInertia(fn ($page) => $page->where('abilities.updateTenantShortnames', [$this->tenant->shortname]));
    });

    test('document manager can queue a full refresh of a document from SharePoint', function (): void {
        Queue::fake();
        $document = Document::factory()->create(['institution_id' => $this->institution->id]);

        asUser($this->documentManager)->post(route('documents.refresh', $document))->assertRedirect();

        Queue::assertPushed(SyncDocumentFromSharePointJob::class, fn ($job) => $job->document->is($document) && $job->force);
    });

    test('cannot manage documents from other tenants as document manager', function (): void {
        $otherTenant = Tenant::factory()->create();
        $otherInstitution = Institution::factory()->create(['tenant_id' => $otherTenant->id]);
        $otherDocument = Document::factory()->create(['institution_id' => $otherInstitution->id]);

        asUser($this->documentManager)->post(route('documents.refresh', $otherDocument))->assertForbidden();
    });

    test('can filter documents by institution', function (): void {
        $anotherInstitution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);

        Document::factory()->count(2)->create(['institution_id' => $this->institution->id]);
        Document::factory()->count(3)->create(['institution_id' => $anotherInstitution->id]);

        $response = asUser($this->documentManager)->get(route('documents.index', [
            'filters' => json_encode([
                'institution_id' => $this->institution->id,
            ]),
        ]));
        $response->assertStatus(200);
    });

    test('admin table search works for document management', function (): void {
        Document::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Special Report.pdf',
        ]);
        Document::factory()->create([
            'institution_id' => $this->institution->id,
            'name' => 'Regular Document.pdf',
        ]);

        // Admin table uses backend search (different from public frontend search)
        $response = asUser($this->documentManager)->get(route('documents.index', [
            'search' => 'Special',
        ]));
        $response->assertStatus(200);
    });
});

describe('relationships', function (): void {
    test('documents are scoped to tenant through institution', function (): void {
        $otherTenant = Tenant::factory()->create();
        $otherInstitution = Institution::factory()->create(['tenant_id' => $otherTenant->id]);

        // Create documents for this tenant
        $ourDocs = Document::factory()->count(2)->create(['institution_id' => $this->institution->id]);

        // Create documents for other tenant
        $otherDocs = Document::factory()->count(3)->create(['institution_id' => $otherInstitution->id]);

        expect($ourDocs->first()->tenant()->first()->id)->toBe($this->tenant->id)
            ->and($otherDocs->first()->tenant()->first()->id)->toBe($otherTenant->id);
    });

    test('document factory creates valid sharepoint document', function (): void {
        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'status' => DocumentStatus::Published,
        ]);

        expect($document->name)->toBeString()
            ->and($document->title)->toBeString()
            ->and($document->sharepoint_id)->toBeString()
            ->and($document->sharepoint_site_id)->toBeString()
            ->and($document->sharepoint_list_id)->toBeString()
            ->and($document->institution_id)->toBe($this->institution->id)
            ->and($document->isPublished())->toBeTrue();
    });

    test('document has tenant relationship through institution', function (): void {
        $document = Document::factory()->create(['institution_id' => $this->institution->id]);

        expect($document->institution)->not->toBeNull()
            ->and($document->institution->tenant_id)->toBe($this->tenant->id);

        // Test the tenant relationship method
        $tenant = $document->tenant()->first();
        expect($tenant->id)->toBe($this->tenant->id);
    });

    test('document has required sharepoint metadata', function (): void {
        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'sharepoint_id' => 'test-sharepoint-id',
            'sharepoint_site_id' => 'test-site-id',
            'sharepoint_list_id' => 'test-list-id',
        ]);

        expect($document->sharepoint_id)->toBe('test-sharepoint-id')
            ->and($document->sharepoint_site_id)->toBe('test-site-id')
            ->and($document->sharepoint_list_id)->toBe('test-list-id');
    });
});
