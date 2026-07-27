<?php

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Pivots\AgendaItem;
use App\Models\Tag;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * `Meeting` and `Tag` both cascaded their children away in an unguarded `deleting`
 * hook, so a *soft* delete destroyed data restore could not bring back. These tests
 * pin down the distinction the guards introduce: soft delete preserves everything,
 * force delete clears exactly what the restricting foreign keys require.
 */
describe('meeting', function () {
    test('soft delete preserves the agenda, its votes and its notes', function () {
        $meeting = Meeting::factory()->create();
        $agendaItem = AgendaItem::factory()->for($meeting)->create();
        $vote = Vote::factory()->for($agendaItem, 'agendaItem')->create();

        $meeting->delete();

        $this->assertSoftDeleted('meetings', ['id' => $meeting->id]);
        $this->assertDatabaseHas('agenda_items', ['id' => $agendaItem->id]);
        $this->assertDatabaseHas('votes', ['id' => $vote->id]);
    });

    test('restore returns the agenda intact', function () {
        $meeting = Meeting::factory()->create();
        AgendaItem::factory()->for($meeting)->count(3)->create();

        $meeting->delete();
        $meeting->restore();

        expect($meeting->fresh()->agendaItems)->toHaveCount(3);
    });

    test('force delete removes the agenda and its cascaded votes', function () {
        $meeting = Meeting::factory()->create();
        $agendaItem = AgendaItem::factory()->for($meeting)->create();
        $vote = Vote::factory()->for($agendaItem, 'agendaItem')->create();

        $meeting->delete();
        $meeting->forceDelete();

        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id]);
        $this->assertDatabaseMissing('agenda_items', ['id' => $agendaItem->id]);
        $this->assertDatabaseMissing('votes', ['id' => $vote->id]);
    });

    test('force delete succeeds for a meeting that has institutions', function () {
        // institution_meeting.meeting_id is RESTRICT, so before the hook detached them
        // this failed for every meeting that had one — which is nearly all of them.
        $meeting = Meeting::factory()->create();
        $meeting->institutions()->attach(Institution::factory()->create());

        $meeting->delete();
        $meeting->forceDelete();

        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id]);
        $this->assertDatabaseMissing('institution_meeting', ['meeting_id' => $meeting->id]);
    });
});

describe('tag', function () {
    test('soft delete keeps the tag attached to its news', function () {
        $tag = Tag::factory()->create();
        $news = News::factory()->create();
        $tag->news()->attach($news);

        $tag->delete();

        $this->assertSoftDeleted('tags', ['id' => $tag->id]);
        $this->assertDatabaseHas('posts_tags', ['tag_id' => $tag->id, 'news_id' => $news->id]);
    });

    test('restore returns the news links', function () {
        $tag = Tag::factory()->create();
        $tag->news()->attach(News::factory()->count(2)->create());

        $tag->delete();
        $tag->restore();

        expect($tag->fresh()->news)->toHaveCount(2);
    });

    test('force delete detaches the news so the restricting key does not block it', function () {
        $tag = Tag::factory()->create();
        $news = News::factory()->create();
        $tag->news()->attach($news);

        $tag->delete();
        $tag->forceDelete();

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseMissing('posts_tags', ['tag_id' => $tag->id]);
        // The article itself is untouched.
        $this->assertDatabaseHas('news', ['id' => $news->id]);
    });
});
