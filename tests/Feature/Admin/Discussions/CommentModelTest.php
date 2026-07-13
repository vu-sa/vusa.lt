<?php

use App\Enums\MeetingType;
use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->author = makeUser($this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();

    $this->meeting = Meeting::create([
        'title' => 'Discussion test meeting',
        'start_time' => Carbon::now()->addDay()->format('Y-m-d H:i'),
        'type' => MeetingType::InPerson,
    ]);
    $this->meeting->institutions()->attach($this->institution->id);

    $this->agendaItem = AgendaItem::factory()->create([
        'meeting_id' => $this->meeting->id,
    ]);
});

describe('threaded creation via HasComments::comment', function () {
    test('root, reply, and nested reply compute thread_root_id', function () {
        $this->actingAs($this->author);

        $root = $this->agendaItem->comment('<p>Root</p>');
        expect($root->parent_id)->toBeNull();
        expect($root->thread_root_id)->toBeNull();
        expect($root->user_id)->toBe($this->author->id);
        expect($root->commentable_type)->toBe(AgendaItem::class);
        expect($root->commentable_id)->toBe($this->agendaItem->id);

        $reply = $this->agendaItem->comment('<p>Reply</p>', $root->id);
        expect($reply->parent_id)->toBe($root->id);
        expect($reply->thread_root_id)->toBe($root->id);

        // A reply to the reply still points at the original root.
        $nested = $this->agendaItem->comment('<p>Nested</p>', $reply->id);
        expect($nested->parent_id)->toBe($reply->id);
        expect($nested->thread_root_id)->toBe($root->id);
    });

    test('creating a comment dispatches CommentPosted', function () {
        Event::fake([CommentPosted::class]);
        $this->actingAs($this->author);

        $this->agendaItem->comment('<p>Hi</p>');

        Event::assertDispatched(CommentPosted::class);
    });

    test('mentions are extracted from the body on create', function () {
        $this->actingAs($this->author);
        $mentioned = User::factory()->create();

        $body = '<p>Hey <span data-type="mention" data-id="'.$mentioned->id.'">@Someone</span></p>';
        $comment = $this->agendaItem->comment($body);

        expect($comment->mentioned_user_ids)->toBe([$mentioned->id]);
    });

    test('rootComments returns only thread roots', function () {
        $this->actingAs($this->author);

        $root = $this->agendaItem->comment('<p>Root</p>');
        $this->agendaItem->comment('<p>Reply</p>', $root->id);

        expect($this->agendaItem->comments()->count())->toBe(2);
        expect($this->agendaItem->rootComments()->count())->toBe(1);
    });
});

describe('extractMentions', function () {
    test('parses unique data-id values', function () {
        $html = '<p><span data-id="aaa">@A</span> and <span data-id="bbb">@B</span> and <span data-id="aaa">@A</span></p>';
        expect(Comment::extractMentions($html))->toBe(['aaa', 'bbb']);
    });

    test('returns empty array when there are no mentions', function () {
        expect(Comment::extractMentions('<p>plain</p>'))->toBe([]);
    });
});

describe('body sanitization', function () {
    test('strips script tags on create', function () {
        $this->actingAs($this->author);

        $comment = $this->agendaItem->comment('<p>Hello</p><script>alert(1)</script>');

        expect($comment->body)->not->toContain('<script')
            ->and($comment->body)->toContain('Hello');
    });

    test('strips event handler attributes and disallowed tags', function () {
        $this->actingAs($this->author);

        $comment = $this->agendaItem->comment('<p><img src=x onerror="alert(1)">Hi</p>');

        expect($comment->body)->not->toContain('onerror')
            ->and($comment->body)->not->toContain('<img')
            ->and($comment->body)->toContain('Hi');
    });

    test('keeps allowed formatting and mention markup', function () {
        $this->actingAs($this->author);
        $mentioned = User::factory()->create();

        $body = '<p><strong>Bold</strong> <em>italic</em> '
            .'<span data-type="mention" data-id="'.$mentioned->id.'">@Someone</span></p>';
        $comment = $this->agendaItem->comment($body);

        expect($comment->body)->toContain('<strong>')
            ->and($comment->body)->toContain('<em>')
            ->and($comment->body)->toContain('data-id="'.$mentioned->id.'"')
            ->and($comment->mentioned_user_ids)->toBe([$mentioned->id]);
    });

    test('neutralizes javascript link schemes', function () {
        $this->actingAs($this->author);

        $comment = $this->agendaItem->comment('<p><a href="javascript:alert(1)">click</a></p>');

        expect($comment->body)->not->toContain('javascript:');
    });

    test('sanitizes on direct body assignment', function () {
        $this->actingAs($this->author);
        $comment = $this->agendaItem->comment('<p>original</p>');

        $comment->body = '<p>ok</p><script>alert(1)</script>';

        expect($comment->body)->not->toContain('<script')
            ->and($comment->body)->toContain('ok');
    });
});

describe('resolve helpers', function () {
    test('resolve and unresolve toggle the resolved state', function () {
        $this->actingAs($this->author);
        $resolver = makeUser($this->tenant);

        $comment = $this->agendaItem->comment('<p>Question?</p>');
        expect($comment->isResolved())->toBeFalse();

        $comment->resolve($resolver);
        expect($comment->fresh()->isResolved())->toBeTrue();
        expect($comment->fresh()->resolved_by)->toBe($resolver->id);

        $comment->unresolve();
        expect($comment->fresh()->isResolved())->toBeFalse();
        expect($comment->fresh()->resolved_by)->toBeNull();
    });
});

describe('reactions', function () {
    test('a comment has many reactions and enforces one emoji per user', function () {
        $this->actingAs($this->author);
        $comment = $this->agendaItem->comment('<p>React to me</p>');
        $user = makeUser($this->tenant);

        CommentReaction::create(['comment_id' => $comment->id, 'user_id' => $user->id, 'emoji' => '👍']);

        expect($comment->reactions()->count())->toBe(1);

        // The unique (comment_id, user_id, emoji) index blocks a duplicate.
        expect(fn () => CommentReaction::create([
            'comment_id' => $comment->id, 'user_id' => $user->id, 'emoji' => '👍',
        ]))->toThrow(QueryException::class);
    });
});
