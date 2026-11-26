<?php

use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Tenant;
use Database\Seeders\RoleDocumentManagerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->regularUser = makeUser($this->tenant);
    $this->documentManager = makeUser($this->tenant);
    $this->documentManager->duties()->first()->assignRole(RoleDocumentManagerSeeder::NAME);
    $this->institution = Institution::factory()->create(['tenant_id' => $this->tenant->id]);
});

describe('unauthorized access', function (): void {
    test('browses documents without management abilities', function (): void {
        asUserWithInertia($this->regularUser)->get(route('documents.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/Files/IndexDocument')
                ->where('abilities', ['create' => false, 'update' => false, 'updateTenantShortnames' => [], 'updateInstitutionIds' => []])
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
        asUserWithInertia($this->documentManager)->get(route('documents.index'))
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

});

describe('document edit entry point', function (): void {
    test('opens the source SharePoint file for an authorized editor', function (): void {
        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'sharepoint_web_url' => 'https://vusa.sharepoint.com/sites/test/file.docx',
        ]);

        asUser($this->documentManager)->get(route('documents.edit', $document))
            ->assertRedirect('https://vusa.sharepoint.com/sites/test/file.docx');
    });

    test('refuses read-only members and editors from another tenant', function (): void {
        $document = Document::factory()->create(['institution_id' => $this->institution->id]);
        asUser($this->regularUser)->get(route('documents.edit', $document))->assertForbidden();

        $other = Document::factory()->create(['institution_id' => Institution::factory()->create()->id]);
        asUser($this->documentManager)->get(route('documents.edit', $other))->assertForbidden();
    });

    test('returns to the collection when the source file is unavailable', function (): void {
        $document = Document::factory()->create([
            'institution_id' => $this->institution->id,
            'sharepoint_web_url' => null,
        ]);

        asUser($this->documentManager)->get(route('documents.edit', $document))
            ->assertRedirect(route('documents.index'))->assertSessionHas('info', __('messages.document.edit_unavailable'));
    });
});
