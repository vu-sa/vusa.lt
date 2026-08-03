<?php

use App\Models\AgendaItemNote;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Vote;
use App\Services\ActivityRootResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

pest()->use(RefreshDatabase::class);

test('a Vote activity roots to its Meeting', function (): void {
    $meeting = Meeting::factory()->create();
    $agendaItem = AgendaItem::factory()->for($meeting, 'meeting')->create();
    $vote = Vote::factory()->for($agendaItem, 'agendaItem')->create();

    $activity = Activity::where('subject_type', Vote::class)->where('subject_id', $vote->id)->latest('id')->first();

    expect($activity->root_subject_type)->toBe(Meeting::class)
        ->and($activity->root_subject_id)->toBe($meeting->id);
});

test('an AgendaItemNote activity roots to its Meeting', function (): void {
    $meeting = Meeting::factory()->create();
    $agendaItem = AgendaItem::factory()->for($meeting, 'meeting')->create();
    $note = AgendaItemNote::factory()->for($agendaItem, 'agendaItem')->create();

    $activity = Activity::where('subject_type', AgendaItemNote::class)->where('subject_id', $note->id)->latest('id')->first();

    expect($activity->root_subject_type)->toBe(Meeting::class)
        ->and($activity->root_subject_id)->toBe($meeting->id);
});

test('a Duty activity roots to its Institution', function (): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution, 'institution')->create();

    $activity = Activity::where('subject_type', Duty::class)->where('subject_id', $duty->id)->latest('id')->first();

    expect($activity->root_subject_type)->toBe(Institution::class)
        ->and($activity->root_subject_id)->toBe($institution->id);
});

test('a Meeting activity roots to itself', function (): void {
    $meeting = Meeting::factory()->create();

    $activity = Activity::where('subject_type', Meeting::class)->where('subject_id', $meeting->id)->latest('id')->first();

    expect($activity->root_subject_type)->toBe(Meeting::class)
        ->and($activity->root_subject_id)->toBe($meeting->id);
});

test('a Duty whose Institution was soft-deleted resolves to itself instead of throwing', function (): void {
    $institution = Institution::factory()->create();
    $duty = Duty::factory()->for($institution, 'institution')->create();

    $institution->delete();

    // A fresh resolver instance, not the container singleton: the singleton
    // already memoized this Duty's root (to the institution) when it was
    // created above, and that memo is intentionally never invalidated by a
    // later state change -- this test is about the resolver's own fallback
    // logic given a parent that can no longer be loaded, not about cache
    // invalidation.
    [$type, $id] = (new ActivityRootResolver)->resolve(Duty::find($duty->id));

    expect($type)->toBe(Duty::class)->and($id)->toBe($duty->id);
});

test('resolving the root of an agenda item whose meeting was soft-deleted does not throw', function (): void {
    $meeting = Meeting::factory()->create();
    $agendaItem = AgendaItem::factory()->for($meeting, 'meeting')->create();

    $meeting->delete();

    [$type, $id] = (new ActivityRootResolver)->resolve(AgendaItem::find($agendaItem->id));

    // The meeting can no longer be loaded (soft-deleted parent), so the
    // resolver falls back to the agenda item as its own root rather than
    // throwing -- the path exercised when a Meeting cascade-deletes its
    // agenda items and votes.
    expect($type)->toBe(AgendaItem::class)->and($id)->toBe($agendaItem->id);
});

test('a ContentPart activity roots to its owning News', function (): void {
    $news = News::factory()->create();
    $part = $news->content->parts->first();

    $activity = Activity::where('subject_type', ContentPart::class)->where('subject_id', $part->id)->latest('id')->first();

    expect($activity->root_subject_type)->toBe(News::class)
        ->and($activity->root_subject_id)->toBe((string) $news->id);
});

test('a ContentPart activity roots to its owning Page', function (): void {
    $page = Page::factory()->create();
    $part = $page->content->parts->first();

    $activity = Activity::where('subject_type', ContentPart::class)->where('subject_id', $part->id)->latest('id')->first();

    expect($activity->root_subject_type)->toBe(Page::class)
        ->and($activity->root_subject_id)->toBe((string) $page->id);
});

test('a ContentPart activity roots to its owning Tenant', function (): void {
    $tenant = Tenant::factory()->create();
    $content = Content::factory()->create();
    $tenant->content()->associate($content)->save();

    $part = $content->parts()->create([
        'type' => 'tiptap',
        'json_content' => [],
        'order' => 0,
    ]);

    $activity = Activity::where('subject_type', ContentPart::class)->where('subject_id', $part->id)->latest('id')->first();

    expect($activity->root_subject_type)->toBe(Tenant::class)
        ->and($activity->root_subject_id)->toBe((string) $tenant->id);
});

test('a ContentPart on a soft-deleted News still rolls up to it instead of self-rooting', function (): void {
    $news = News::factory()->create();
    $part = $news->content->parts->first();

    $news->delete();

    // A fresh resolver, same reasoning as the Duty/Institution case above --
    // this is about Content::news()'s withTrashed(), not memoization.
    [$type, $id] = (new ActivityRootResolver)->resolve(ContentPart::find($part->id));

    expect($type)->toBe(News::class)->and($id)->toBe((string) $news->id);
});

test('an orphan Content (no News/Page/Tenant owner) self-roots without throwing', function (): void {
    $content = Content::factory()->create();
    $part = $content->parts()->create([
        'type' => 'tiptap',
        'json_content' => [],
        'order' => 0,
    ]);

    [$type, $id] = (new ActivityRootResolver)->resolve($part);

    expect($type)->toBe(Content::class)->and($id)->toBe((string) $content->id);
});

test('resolving the owner for several content parts of one Content costs at most two queries total', function (): void {
    $content = Content::factory()->create();
    News::factory()->create(['content_id' => $content->id]);
    $partIds = ContentPart::factory()->for($content, 'content')->count(5)->create()->pluck('id');

    // Load fresh, relation-less instances before measuring, so the query log
    // below only counts what the resolver itself does.
    $parts = ContentPart::find($partIds);
    $resolver = new ActivityRootResolver;

    DB::enableQueryLog();
    DB::flushQueryLog();

    foreach ($parts as $part) {
        $resolver->resolve($part);
    }

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // One query to load the (shared) Content, one to find its News owner --
    // both cached on the resolver instance, so parts 2-5 cost nothing further.
    expect($queryCount)->toBeLessThanOrEqual(2);
});

test('resolving the root for several votes on one agenda item costs at most two queries total', function (): void {
    $meeting = Meeting::factory()->create();
    $agendaItem = AgendaItem::factory()->for($meeting, 'meeting')->create();
    $voteIds = Vote::factory()->for($agendaItem, 'agendaItem')->count(5)->create()->pluck('id');

    // Load fresh, relation-less instances before measuring, so the query log
    // below only counts what the resolver itself does.
    $votes = Vote::find($voteIds);
    $resolver = new ActivityRootResolver;

    DB::enableQueryLog();
    DB::flushQueryLog();

    foreach ($votes as $vote) {
        $resolver->resolve($vote);
    }

    $queryCount = count(DB::getQueryLog());
    DB::disableQueryLog();

    // One query to load the (shared) agenda item, one to load its meeting --
    // both cached on the resolver instance, so votes 2-5 cost nothing further.
    expect($queryCount)->toBeLessThanOrEqual(2);
});
