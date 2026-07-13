<?php

use App\Enums\MeetingType;
use App\Events\CommentBroadcast;
use App\Http\Resources\CommentResource;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->institution = Institution::factory()->for($this->tenant)->create();

    $this->meeting = Meeting::create([
        'title' => 'API discussion meeting',
        'start_time' => Carbon::now()->addDay()->format('Y-m-d H:i'),
        'type' => MeetingType::InPerson,
    ]);
    $this->meeting->institutions()->attach($this->institution->id);

    $this->agendaItem = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id]);

    // View-only meeting participant (duty in the institution, no role).
    $duty = Duty::factory()->for($this->institution)->create();
    $this->viewer = User::factory()->create();
    $this->viewer->duties()->attach($duty, ['start_date' => now()->subDay(), 'end_date' => null]);

    $this->outsider = makeUser(
        Tenant::query()->where('id', '!=', $this->tenant->id)->inRandomOrder()->first() ?? $this->tenant
    );

    $this->indexUrl = route('api.v1.admin.comments.index', ['commentableType' => 'agendaItem', 'commentableId' => $this->agendaItem->id]);
    $this->storeUrl = route('api.v1.admin.comments.store', ['commentableType' => 'agendaItem', 'commentableId' => $this->agendaItem->id]);
});

describe('index', function () {
    test('returns root comments with nested replies', function () {
        $this->actingAs($this->coordinator);
        $root = $this->agendaItem->comment('<p>Root</p>');
        $this->agendaItem->comment('<p>Reply</p>', $root->id);

        $response = asUser($this->coordinator)->getJson($this->indexUrl);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(1, 'data.0.replies')
            ->assertJsonPath('data.0.body', '<p>Root</p>')
            ->assertJsonPath('data.0.replies.0.body', '<p>Reply</p>');
    });

    test('filters by resolved state', function () {
        $this->actingAs($this->coordinator);
        $open = $this->agendaItem->comment('<p>Open</p>');
        $done = $this->agendaItem->comment('<p>Done</p>');
        $done->resolve($this->coordinator);

        asUser($this->coordinator)->getJson($this->indexUrl.'?resolved=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $open->id);

        asUser($this->coordinator)->getJson($this->indexUrl.'?resolved=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $done->id);
    });

    test('an outsider is forbidden (403)', function () {
        asUser($this->outsider)->getJson($this->indexUrl)->assertStatus(403);
    });

    test('an unknown commentable type 404s', function () {
        asUser($this->coordinator)
            ->getJson(route('api.v1.admin.comments.index', ['commentableType' => 'banana', 'commentableId' => $this->agendaItem->id]))
            ->assertStatus(404);
    });
});

describe('store', function () {
    test('a view-only participant can post a comment and it broadcasts', function () {
        Event::fake([CommentBroadcast::class]);

        asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Hello from viewer</p>'])
            ->assertCreated()
            ->assertJsonPath('data.body', '<p>Hello from viewer</p>')
            ->assertJsonPath('data.can.update', true)
            ->assertJsonPath('data.can.delete', true);

        Event::assertDispatched(CommentBroadcast::class, fn ($e) => $e->action === 'created'
            && $e->channelName === "comments.agendaItem.{$this->agendaItem->id}");
    });

    test('a reply computes thread_root_id', function () {
        $this->actingAs($this->coordinator);
        $root = $this->agendaItem->comment('<p>Root</p>');

        asUser($this->coordinator)->postJson($this->storeUrl, ['body' => '<p>Reply</p>', 'parent_id' => $root->id])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $root->id)
            ->assertJsonPath('data.thread_root_id', $root->id);
    });

    test('a poll is created with server-assigned option ids', function () {
        $response = asUser($this->coordinator)->postJson($this->storeUrl, [
            'body' => '<p>Approve the budget?</p>',
            'kind' => 'poll',
            'metadata' => ['poll' => ['options' => [['label' => 'Yes'], ['label' => 'No']]]],
        ])
            ->assertCreated()
            ->assertJsonPath('data.kind', 'poll')
            ->assertJsonPath('data.poll.allow_multiple', false)
            ->assertJsonCount(2, 'data.poll.options');

        // Labels are preserved; ids are generated server-side (clients send labels only).
        expect($response->json('data.poll.options.0.label'))->toBe('Yes');
        expect($response->json('data.poll.options.0.id'))->toBeString()->not->toBe('Yes');
    });

    test('a poll without at least two options is rejected (422)', function () {
        asUser($this->coordinator)->postJson($this->storeUrl, [
            'body' => '<p>x</p>',
            'kind' => 'poll',
            'metadata' => ['poll' => ['options' => [['label' => 'Only one']]]],
        ])->assertStatus(422)->assertJsonValidationErrors(['metadata.poll.options']);
    });

    test('a reply cannot be a poll (422)', function () {
        $this->actingAs($this->coordinator);
        $root = $this->agendaItem->comment('<p>Root</p>');

        asUser($this->coordinator)->postJson($this->storeUrl, [
            'body' => '<p>x</p>',
            'kind' => 'poll',
            'parent_id' => $root->id,
            'metadata' => ['poll' => ['options' => [['label' => 'Yes'], ['label' => 'No']]]],
        ])->assertStatus(422)->assertJsonValidationErrors(['parent_id']);
    });

    test('an outsider cannot post (403)', function () {
        asUser($this->outsider)->postJson($this->storeUrl, ['body' => '<p>x</p>'])->assertStatus(403);
        expect($this->agendaItem->comments()->count())->toBe(0);
    });
});

describe('update & delete', function () {
    test('the author can edit, marking it edited', function () {
        $this->actingAs($this->viewer);
        $comment = $this->agendaItem->comment('<p>Original</p>');

        asUser($this->viewer)->patchJson(route('api.v1.admin.comments.update', $comment), ['body' => '<p>Edited</p>'])
            ->assertOk()->assertJsonPath('data.body', '<p>Edited</p>');

        expect($comment->fresh()->edited_at)->not->toBeNull();
    });

    test('a non-author cannot edit (403)', function () {
        $this->actingAs($this->viewer);
        $comment = $this->agendaItem->comment('<p>Mine</p>');

        asUser($this->coordinator)->patchJson(route('api.v1.admin.comments.update', $comment), ['body' => '<p>hijack</p>'])
            ->assertStatus(403);
    });

    test('a moderator (parent update) can delete another user comment', function () {
        $this->actingAs($this->viewer);
        $comment = $this->agendaItem->comment('<p>From viewer</p>');

        asUser($this->coordinator)->deleteJson(route('api.v1.admin.comments.destroy', $comment))
            ->assertOk();

        expect($comment->fresh()->trashed())->toBeTrue();
    });

    test('a non-author non-moderator cannot delete (403)', function () {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>From coordinator</p>');

        asUser($this->viewer)->deleteJson(route('api.v1.admin.comments.destroy', $comment))
            ->assertStatus(403);
    });
});

describe('resolve', function () {
    test('a view-audience user can resolve and unresolve', function () {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>Question?</p>');

        asUser($this->viewer)->postJson(route('api.v1.admin.comments.resolve', $comment))
            ->assertOk()->assertJsonPath('data.is_resolved', true);

        asUser($this->viewer)->deleteJson(route('api.v1.admin.comments.resolve', $comment))
            ->assertOk()->assertJsonPath('data.is_resolved', false);
    });
});

describe('reactions', function () {
    test('toggling adds then removes a reaction', function () {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>React</p>');
        $url = route('api.v1.admin.comments.reactions.toggle', $comment);

        asUser($this->viewer)->putJson($url, ['emoji' => '👍'])
            ->assertOk()
            ->assertJsonPath('data.reactions.0.emoji', '👍')
            ->assertJsonPath('data.reactions.0.count', 1)
            ->assertJsonPath('data.reactions.0.reacted_by_me', true);

        asUser($this->viewer)->putJson($url, ['emoji' => '👍'])->assertOk();
        expect($comment->reactions()->count())->toBe(0);
    });

    test('an invalid emoji is rejected', function () {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>React</p>');

        asUser($this->coordinator)->putJson(route('api.v1.admin.comments.reactions.toggle', $comment), ['emoji' => '💀'])
            ->assertStatus(422)->assertJsonValidationErrors(['emoji']);
    });
});

describe('mentionables', function () {
    test('returns users who can view the parent', function () {
        $rep = User::factory()->create(['name' => 'Rep Person']);
        $rep->duties()->attach(
            Duty::factory()->for($this->institution)->create(),
            ['start_date' => now()->subMonth(), 'end_date' => null]
        );

        asUser($this->coordinator)
            ->getJson(route('api.v1.admin.comments.mentionables', ['commentableType' => 'agendaItem', 'commentableId' => $this->agendaItem->id]))
            ->assertOk()
            ->assertJsonFragment(['id' => (string) $rep->id, 'name' => 'Rep Person']);
    });
});

describe('resource serialization', function () {
    test('a comment without loaded replies serializes cleanly (broadcast payload path)', function () {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>Root</p>')->load('reactions.user:id,name');

        // Mirrors how the broadcast payload is built (resolve() + json_encode),
        // which previously blew up on the unloaded `replies` relation.
        $array = (new CommentResource($comment))->resolve(request());

        expect($array)->not->toHaveKey('replies');
        expect(fn () => json_encode($array, JSON_THROW_ON_ERROR))->not->toThrow(Throwable::class);
    });
});

describe('feed', function () {
    test('returns comments that mention the user', function () {
        $this->actingAs($this->coordinator);
        $body = '<p>Ping <span data-id="'.$this->viewer->id.'">@viewer</span></p>';
        $this->agendaItem->comment($body);

        asUser($this->viewer)->getJson(route('api.v1.admin.comments.feed'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.commentable_type', 'agendaItem');
    });
});

describe('reservation', function () {
    beforeEach(function () {
        $this->reservation = Reservation::factory()->create();
        $this->reservationUser = User::factory()->hasAttached($this->reservation)->create();
        $this->reservationOutsider = makeUser($this->tenant);

        $this->reservationIndexUrl = route('api.v1.admin.comments.index', ['commentableType' => 'reservation', 'commentableId' => $this->reservation->id]);
        $this->reservationStoreUrl = route('api.v1.admin.comments.store', ['commentableType' => 'reservation', 'commentableId' => $this->reservation->id]);
    });

    test('returns root comments with nested replies', function () {
        $this->actingAs($this->reservationUser);
        $root = $this->reservation->comment('<p>Root</p>');
        $this->reservation->comment('<p>Reply</p>', $root->id);

        asUser($this->reservationUser)->getJson($this->reservationIndexUrl)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(1, 'data.0.replies')
            ->assertJsonPath('data.0.body', '<p>Root</p>')
            ->assertJsonPath('data.0.replies.0.body', '<p>Reply</p>');
    });

    test('a reservation user can post a comment and it broadcasts', function () {
        Event::fake([CommentBroadcast::class]);

        asUser($this->reservationUser)->postJson($this->reservationStoreUrl, ['body' => '<p>Hello from reservation user</p>'])
            ->assertCreated()
            ->assertJsonPath('data.body', '<p>Hello from reservation user</p>')
            ->assertJsonPath('data.can.update', true)
            ->assertJsonPath('data.can.delete', true);

        Event::assertDispatched(CommentBroadcast::class, fn ($e) => $e->action === 'created'
            && $e->channelName === "comments.reservation.{$this->reservation->id}");
    });

    test('an outsider is forbidden (403)', function () {
        asUser($this->reservationOutsider)->getJson($this->reservationIndexUrl)->assertStatus(403);
        asUser($this->reservationOutsider)->postJson($this->reservationStoreUrl, ['body' => '<p>x</p>'])->assertStatus(403);
    });

    test('mentionables returns reservation users', function () {
        $otherUser = User::factory()->hasAttached($this->reservation)->create(['name' => 'Reservation Member']);

        asUser($this->reservationUser)
            ->getJson(route('api.v1.admin.comments.mentionables', ['commentableType' => 'reservation', 'commentableId' => $this->reservation->id]))
            ->assertOk()
            ->assertJsonFragment(['id' => (string) $otherUser->id, 'name' => 'Reservation Member']);
    });
});
