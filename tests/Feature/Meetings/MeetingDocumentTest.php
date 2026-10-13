<?php

use App\Actions\Documents\UpdateDocumentStatus;
use App\Enums\DocumentStatus;
use App\Jobs\SyncDocumentFromSharePointJob;
use App\Models\Document;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;

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

test('a document linked to another meeting is not taken over by linking it here', function (): void {
    $otherMeeting = Meeting::factory()->create();
    $document = Document::factory()->for($this->institution)->create(['meeting_id' => $otherMeeting->id]);

    asUser($this->admin)
        ->post(route('meetings.documents.store', $this->meeting), ['document_id' => $document->id])
        ->assertSessionHasErrors('document_id');

    expect($document->fresh()->meeting_id)->toBe($otherMeeting->id);
});

test('a link that lost a race to another meeting is refused rather than moving the document', function (): void {
    $document = Document::factory()->for($this->institution)->create();
    // The request validated an unlinked document; another request linked it before this one took the lock.
    $stale = $document->replicate();
    $stale->id = $document->id;
    $stale->exists = true;
    $document->update(['meeting_id' => Meeting::factory()->create()->id]);

    expect(fn () => UpdateDocumentStatus::linkToMeeting($stale, $this->meeting, $this->admin))
        ->toThrow(ValidationException::class)
        ->and($document->fresh()->meeting_id)->not->toBe($this->meeting->id);
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

test('a user without meeting update rights cannot link documents', function (): void {
    $user = makeUser($this->tenant);
    $document = Document::factory()->for($this->institution)->create();

    asUser($user)
        ->post(route('meetings.documents.store', $this->meeting), ['document_id' => $document->id])
        ->assertStatus(403);

    expect($document->fresh()->meeting_id)->toBeNull();
});

test('the meeting page labels each linked document by language and date', function (): void {
    Document::factory()->for($this->institution)->create([
        'meeting_id' => $this->meeting->id,
        'title' => 'VU SA Tarybos protokolas',
        'language' => 'Lietuvių',
        'document_date' => '2026-07-23',
    ]);

    asUser($this->admin)
        ->get(route('meetings.show', $this->meeting))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            // `language_code` is derived, so it only reaches the panel if it is appended.
            ->loadDeferredProps('meetingPanels', fn (Assert $page) => $page
                ->where('documents.0.language_code', 'lt')
                ->where('documents.0.document_date', '2026-07-23')
            ));
});

test('a document of unrecorded language carries no language claim', function (): void {
    Document::factory()->for($this->institution)->create([
        'meeting_id' => $this->meeting->id,
        'language' => null,
    ]);

    asUser($this->admin)
        ->get(route('meetings.show', $this->meeting))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps('meetingPanels', fn (Assert $page) => $page
                ->where('documents.0.language_code', 'unknown')
            ));
});

test('linking a pending SharePoint file publishes it', function (): void {
    Queue::fake();
    $document = Document::factory()->pending()->for($this->institution)->create();

    asUser($this->admin)
        ->post(route('meetings.documents.store', $this->meeting), ['document_id' => $document->id])
        ->assertRedirect();

    expect($document->fresh()->meeting_id)->toBe($this->meeting->id)
        ->and($document->fresh()->status)->toBe(DocumentStatus::Published);

    Queue::assertPushed(SyncDocumentFromSharePointJob::class, fn ($job) => $job->document->is($document) && $job->force);
});

test('linking a hidden document keeps it hidden', function (): void {
    Queue::fake();
    $document = Document::factory()->inactive()->for($this->institution)->create();

    asUser($this->admin)
        ->post(route('meetings.documents.store', $this->meeting), ['document_id' => $document->id])
        ->assertRedirect();

    expect($document->fresh()->status)->toBe(DocumentStatus::Hidden);
    Queue::assertNotPushed(SyncDocumentFromSharePointJob::class);
});

test('the meeting page offers pending files of its padalinys, meeting-day files first', function (): void {
    $older = Document::factory()->pending()->for($this->institution)->create(['document_date' => '2020-01-01', 'sharepoint_modified_at' => now()]);
    $sameDay = Document::factory()->pending()->for($this->institution)->create(['document_date' => $this->meeting->start_time->toDateString(), 'sharepoint_modified_at' => now()->subWeek()]);
    Document::factory()->pending()->for(Institution::factory()->for(Tenant::factory())->create())->create();
    Document::factory()->pending()->for($this->institution)->create(['meeting_id' => Meeting::factory()->create()->id]);

    asUser($this->admin)
        ->get(route('meetings.show', $this->meeting))
        ->assertInertia(fn (Assert $page) => $page
            ->missing('pendingDocuments')
            ->loadDeferredProps('meetingPanels', fn (Assert $page) => $page
                ->has('pendingDocuments', 2)
                ->where('pendingDocuments.0.id', $sameDay->id)
                ->where('pendingDocuments.1.id', $older->id)
            ));
});

test('pending files past the suggestions are found by search, without ones linked elsewhere', function (): void {
    Document::factory()->pending()->for($this->institution)->count(8)->create(['title' => 'Kitas failas']);
    $wanted = Document::factory()->pending()->for($this->institution)->create(['title' => 'Senato protokolas']);
    Document::factory()->pending()->for($this->institution)->create(['title' => 'Senato nutarimas', 'meeting_id' => Meeting::factory()->create()->id]);

    asUser($this->admin)
        ->getJson(route('api.v1.admin.meetings.pendingDocuments', ['meeting' => $this->meeting, 'search' => 'Senato']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $wanted->id);
});

test('only someone who may edit the meeting can search its pending files', function (): void {
    asUser(makeUser($this->tenant))
        ->getJson(route('api.v1.admin.meetings.pendingDocuments', ['meeting' => $this->meeting]))
        ->assertForbidden();
});

test('a linked document that is not published reaches only those who may manage documents', function (): void {
    $coordinator = makeTenantUserWithRole('Studentų atstovų koordinatorius', $this->tenant);
    $published = Document::factory()->for($this->institution)->create(['meeting_id' => $this->meeting->id]);
    Document::factory()->inactive()->for($this->institution)->create(['meeting_id' => $this->meeting->id]);

    asUser($coordinator)
        ->get(route('meetings.show', $this->meeting))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->loadDeferredProps('meetingPanels', fn (Assert $page) => $page
                ->has('documents', 1)
                ->where('documents.0.id', $published->id)
            ));
});
