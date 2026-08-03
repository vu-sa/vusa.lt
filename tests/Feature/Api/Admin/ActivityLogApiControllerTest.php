<?php

use App\Models\Content;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tiptap\Editor;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->meeting = Meeting::factory()->create();
    $this->meeting->institutions()->attach($this->institution->id);
});

test('the meeting feed contains a vote change and marks it as not-root', function (): void {
    $agendaItem = AgendaItem::factory()->for($this->meeting, 'meeting')->create();
    $vote = Vote::factory()->for($agendaItem, 'agendaItem')->create();

    $vote->update(['decision' => 'positive']);

    $response = asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'meeting', 'subjectId' => $this->meeting->id]))
        ->assertStatus(200)
        ->json('data');

    $voteEntry = collect($response)->first(fn ($entry) => $entry['subject']['type'] === 'vote' && $entry['event'] === 'updated');

    expect($voteEntry)->not->toBeNull()
        ->and($voteEntry['subject']['id'])->toBe($vote->id)
        ->and($voteEntry['subject']['is_root'])->toBeFalse();
});

test('scope=self excludes descendant activities from the meeting feed', function (): void {
    $agendaItem = AgendaItem::factory()->for($this->meeting, 'meeting')->create();
    $vote = Vote::factory()->for($agendaItem, 'agendaItem')->create();
    $vote->update(['decision' => 'positive']);

    $data = asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', [
            'subjectType' => 'meeting',
            'subjectId' => $this->meeting->id,
            'scope' => 'self',
        ]))
        ->assertStatus(200)
        ->json('data');

    expect(collect($data)->pluck('subject.type'))->not->toContain('vote');
});

test('unauthenticated requests are rejected', function (): void {
    $this->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'meeting', 'subjectId' => $this->meeting->id]))
        ->assertStatus(401);
});

test('a user without view access on the subject gets a 403 JSON response', function (): void {
    asUser($this->user)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'meeting', 'subjectId' => $this->meeting->id]))
        ->assertStatus(403);
});

test('an unknown subject type 404s without instantiating anything', function (): void {
    asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'foo', 'subjectId' => $this->meeting->id]))
        ->assertStatus(404);
});

test('a subject type not in the allowlist 404s even though the model exists', function (): void {
    asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'comment', 'subjectId' => '01H0000000000000000000000']))
        ->assertStatus(404);
});

test('cursor pagination returns non-overlapping pages and signals has_more correctly', function (): void {
    $problem = Problem::factory()->create(['tenant_id' => $this->tenant->id]);

    foreach (range(1, 12) as $i) {
        $problem->update(['status' => $i % 2 === 0 ? 'in_progress' : 'open']);
    }

    $first = asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', [
            'subjectType' => 'problem',
            'subjectId' => $problem->id,
            'per_page' => 5,
        ]))
        ->assertStatus(200);

    $firstIds = collect($first->json('data'))->pluck('id');
    expect($firstIds)->toHaveCount(5)
        ->and($first->json('meta.cursor.has_more'))->toBeTrue();

    $nextCursor = $first->json('meta.cursor.next');
    expect($nextCursor)->not->toBeNull();

    $second = asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', [
            'subjectType' => 'problem',
            'subjectId' => $problem->id,
            'per_page' => 5,
            'cursor' => $nextCursor,
        ]))
        ->assertStatus(200);

    $secondIds = collect($second->json('data'))->pluck('id');

    expect($secondIds)->toHaveCount(5)
        ->and($firstIds->intersect($secondIds))->toBeEmpty();
});

test('enum, relation, and rich changes are formatted with the right shape', function (): void {
    $responsibleUser = User::factory()->create(['name' => 'Jonas Jonaitis']);
    $problem = Problem::factory()->create([
        'tenant_id' => $this->tenant->id,
        'status' => 'open',
        'responsible_user_id' => null,
    ]);

    $problem->update([
        'status' => 'resolved',
        'responsible_user_id' => $responsibleUser->id,
        'description' => ['lt' => '<p>Nauja santrauka</p>', 'en' => '<p>New summary</p>'],
    ]);

    $data = asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'problem', 'subjectId' => $problem->id]))
        ->assertStatus(200)
        ->json('data');

    $updated = collect($data)->first(fn ($entry) => $entry['event'] === 'updated');
    $changes = collect($updated['changes']);

    $statusChange = $changes->firstWhere('key', 'status');
    expect($statusChange['type'])->toBe('enum')
        ->and($statusChange['new_display'])->not->toBeNull()
        ->and($statusChange['new_display'])->not->toBe('resolved');

    $userChange = $changes->firstWhere('key', 'responsible_user_id');
    expect($userChange['type'])->toBe('relation')
        ->and($userChange['new_display'])->toBe('Jonas Jonaitis');

    // description is translatable, logged as a raw {"lt":..,"en":..} JSON
    // string (see Problem::getActivitylogOptions()) and split into one row
    // per locale (see ActivityChangeFormatter::localeMapChanges()) -- both
    // locales changed here, so both rows appear.
    $descriptionChangeLt = $changes->firstWhere('key', 'description.lt');
    expect($descriptionChangeLt['type'])->toBe('diff')
        ->and($descriptionChangeLt['label'])->toContain('LT')
        ->and($descriptionChangeLt['new_display'])->toContain('Nauja santrauka')
        ->and($descriptionChangeLt['new_display'])->not->toContain('<p>');

    $descriptionChangeEn = $changes->firstWhere('key', 'description.en');
    expect($descriptionChangeEn['type'])->toBe('diff')
        ->and($descriptionChangeEn['label'])->toContain('EN')
        ->and($descriptionChangeEn['new_display'])->toContain('New summary');
});

test('the page feed contains a content-part edit labelled by block type and position, and marks it as not-root', function (): void {
    $page = Page::factory()->create(['tenant_id' => $this->tenant->id]);
    $part = $page->content->parts->first();

    $part->update(['json_content' => (new Editor)->setContent('<p>New body text</p>')->getDocument()]);

    $data = asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'page', 'subjectId' => $page->id]))
        ->assertStatus(200)
        ->json('data');

    $entry = collect($data)->first(fn ($e) => $e['subject']['type'] === 'contentPart' && $e['event'] === 'updated');

    expect($entry)->not->toBeNull()
        ->and($entry['subject']['id'])->toBe((string) $part->id)
        ->and($entry['subject']['is_root'])->toBeFalse()
        ->and($entry['subject']['label'])->toContain('#1');
});

test('subject_type=contentPart filters the page feed to block edits only, and scope=self excludes them', function (): void {
    $page = Page::factory()->create(['tenant_id' => $this->tenant->id]);
    $part = $page->content->parts->first();
    $part->update(['json_content' => (new Editor)->setContent('<p>Edited</p>')->getDocument()]);
    $page->update(['title' => 'Renamed page']);

    $filtered = asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', [
            'subjectType' => 'page',
            'subjectId' => $page->id,
            'subject_type' => 'contentPart',
        ]))
        ->assertStatus(200)
        ->json('data');

    expect(collect($filtered)->pluck('subject.type')->unique()->all())->toBe(['contentPart']);

    $selfScoped = asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', [
            'subjectType' => 'page',
            'subjectId' => $page->id,
            'scope' => 'self',
        ]))
        ->assertStatus(200)
        ->json('data');

    expect(collect($selfScoped)->pluck('subject.type'))->not->toContain('contentPart');
});

test('subjectType=contentPart 404s as a root -- it is descendant-only in the allowlist', function (): void {
    $page = Page::factory()->create(['tenant_id' => $this->tenant->id]);
    $part = $page->content->parts->first();

    asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'contentPart', 'subjectId' => $part->id]))
        ->assertStatus(404);
});

test('a tenant homepage content-part edit is reachable via the tenant root feed, and requires super admin', function (): void {
    $tenant = Tenant::factory()->create();
    $content = Content::factory()->create();
    $tenant->content()->associate($content)->save();
    $part = $content->parts()->create(['type' => 'tiptap', 'json_content' => [], 'order' => 0]);

    $part->update(['json_content' => (new Editor)->setContent('<p>Edited homepage block</p>')->getDocument()]);

    asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'tenant', 'subjectId' => $tenant->id]))
        ->assertStatus(403);

    $superAdmin = makeAdminUser($tenant);

    $data = asUser($superAdmin)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'tenant', 'subjectId' => $tenant->id]))
        ->assertStatus(200)
        ->json('data');

    expect(collect($data)->pluck('subject.type'))->toContain('contentPart');
});

test('a relation_updated activity carries the attached/detached members and rolls up to the parent Institution', function (): void {
    $duty = Duty::factory()->for($this->institution, 'institution')->create();
    $user = User::factory()->create(['name' => 'Jonas Jonaitis']);

    $duty->attachAudited('users', [$user->id => ['start_date' => now()]]);

    $data = asUser($this->admin)
        ->getJson(route('api.v1.admin.activityLog.index', ['subjectType' => 'institution', 'subjectId' => $this->institution->id]))
        ->assertStatus(200)
        ->json('data');

    $entry = collect($data)->first(fn ($e) => $e['event'] === 'relation_updated');

    expect($entry)->not->toBeNull()
        ->and($entry['subject']['type'])->toBe('duty')
        ->and($entry['subject']['is_root'])->toBeFalse()
        ->and($entry['relation_change']['relation'])->toBe('users')
        ->and($entry['relation_change']['attached'][0]['label'])->toBe('Jonas Jonaitis')
        ->and($entry['relation_change']['detached'])->toBe([]);
});
