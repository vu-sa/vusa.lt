<?php

use App\Models\Document;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeAdminUser($this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();

    $this->meeting = Meeting::factory()->create();
    $this->meeting->institutions()->attach($this->institution);
});

test('a document of the meeting institution can be linked', function (): void {
    $document = Document::factory()->for($this->institution)->create();

    asUser($this->admin)
        ->post(route('meetings.documents.store', $this->meeting), ['document_id' => $document->id])
        ->assertRedirect();

    expect($document->fresh()->meeting_id)->toBe($this->meeting->id)
        ->and($this->meeting->fresh()->documents)->toHaveCount(1);
});

test('a document of a sibling institution in the same tenant can be linked', function (): void {
    // Internal bodies (Parlamentas, Taryba) have their paperwork filed under the central
    // institution of the same tenant, not under the body itself.
    $centralInstitution = Institution::factory()->for($this->tenant)->create();
    $document = Document::factory()->for($centralInstitution)->create();

    asUser($this->admin)
        ->post(route('meetings.documents.store', $this->meeting), ['document_id' => $document->id])
        ->assertRedirect();

    expect($document->fresh()->meeting_id)->toBe($this->meeting->id);
});

test('a document belonging to another tenant cannot be linked', function (): void {
    $otherTenant = Tenant::factory()->create();
    $otherInstitution = Institution::factory()->for($otherTenant)->create();
    $document = Document::factory()->for($otherInstitution)->create();

    asUser($this->admin)
        ->post(route('meetings.documents.store', $this->meeting), ['document_id' => $document->id])
        ->assertStatus(403);

    expect($document->fresh()->meeting_id)->toBeNull();
});

test('a document linked to another meeting cannot be unlinked through this one', function (): void {
    $otherMeeting = Meeting::factory()->create();
    $document = Document::factory()->for($this->institution)->create(['meeting_id' => $otherMeeting->id]);

    asUser($this->admin)
        ->delete(route('meetings.documents.destroy', [$this->meeting, $document]))
        ->assertStatus(403);

    expect($document->fresh()->meeting_id)->toBe($otherMeeting->id);
});

test('unlinking clears the link but keeps the document', function (): void {
    $document = Document::factory()->for($this->institution)->create(['meeting_id' => $this->meeting->id]);

    asUser($this->admin)
        ->delete(route('meetings.documents.destroy', [$this->meeting, $document]))
        ->assertRedirect();

    expect($document->fresh())->not->toBeNull()
        ->and($document->fresh()->meeting_id)->toBeNull();
});

test('picking a SharePoint file that is already registered files it under the meeting', function (): void {
    // Registered already, so batchProcessDocuments filters it out — the controller adopts it
    // instead of leaving the editor with a silent no-op.
    $existing = Document::factory()->for($this->institution)->create([
        'sharepoint_id' => 'sp-existing-1',
        'sharepoint_site_id' => 'site-1',
        'sharepoint_list_id' => 'list-1',
        'meeting_id' => null,
    ]);

    asUser($this->admin)
        ->post(route('meetings.documents.storeFromSharepoint', $this->meeting), [
            'documents' => [[
                'name' => 'Protokolas.pdf',
                'site_id' => 'site-1',
                'list_id' => 'list-1',
                'list_item_unique_id' => 'sp-existing-1',
            ]],
        ])
        ->assertRedirect();

    expect($existing->fresh()->meeting_id)->toBe($this->meeting->id);
});

test('a user without meeting update rights cannot upload documents into it', function (): void {
    $user = makeUser($this->tenant);

    asUser($user)
        ->post(route('meetings.documents.storeFromSharepoint', $this->meeting), [
            'documents' => [[
                'name' => 'Protokolas.pdf',
                'site_id' => 'site-1',
                'list_id' => 'list-1',
                'list_item_unique_id' => 'sp-1',
            ]],
        ])
        ->assertStatus(403);
});

test('a user without meeting update rights cannot link documents', function (): void {
    $user = makeUser($this->tenant);
    $document = Document::factory()->for($this->institution)->create();

    asUser($user)
        ->post(route('meetings.documents.store', $this->meeting), ['document_id' => $document->id])
        ->assertStatus(403);

    expect($document->fresh()->meeting_id)->toBeNull();
});
