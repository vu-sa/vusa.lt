<?php

use App\Enums\MeetingType;
use App\Events\CommentBroadcast;
use App\Http\Resources\CommentResource;
use App\Models\Duty;
use App\Models\Form;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CommentRecipientResolver;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->coordinator = makeTenantUserWithRole('Komunikacijos koordinatorius', $this->tenant);

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

describe('index', function (): void {
    test('returns root comments with nested replies', function (): void {
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

    test('filters by resolved state', function (): void {
        $this->actingAs($this->coordinator);
        $open = $this->agendaItem->comment('<p>Open</p>');
        $done = $this->agendaItem->comment('<p>Done</p>');
        $done->resolve($this->coordinator);

        asUser($this->coordinator)->getJson($this->indexUrl.'?resolved=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $open->id);

        asUser($this->coordinator)->getJson($this->indexUrl.'?resolved=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $done->id);
    });

    test('an outsider is forbidden (403)', function (): void {
        asUser($this->outsider)->getJson($this->indexUrl)->assertStatus(403);
    });

    test('an unknown commentable type 404s', function (): void {
        asUser($this->coordinator)
            ->getJson(route('api.v1.admin.comments.index', ['commentableType' => 'banana', 'commentableId' => $this->agendaItem->id]))
            ->assertStatus(404);
    });
});

describe('store', function (): void {
    test('a view-only participant can post a comment and it broadcasts', function (): void {
        Event::fake([CommentBroadcast::class]);

        asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Hello from viewer</p>'])
            ->assertCreated()
            ->assertJsonPath('data.body', '<p>Hello from viewer</p>')
            ->assertJsonPath('data.can.update', true)
            ->assertJsonPath('data.can.delete', true);

        Event::assertDispatched(CommentBroadcast::class, fn ($e) => $e->action === 'created'
            && $e->channelName === "comments.agendaItem.{$this->agendaItem->id}");
    });

    test('a reply computes thread_root_id', function (): void {
        $this->actingAs($this->coordinator);
        $root = $this->agendaItem->comment('<p>Root</p>');

        asUser($this->coordinator)->postJson($this->storeUrl, ['body' => '<p>Reply</p>', 'parent_id' => $root->id])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $root->id)
            ->assertJsonPath('data.thread_root_id', $root->id);
    });

    test('a poll is created with server-assigned option ids', function (): void {
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

    test('a poll without at least two options is rejected (422)', function (): void {
        asUser($this->coordinator)->postJson($this->storeUrl, [
            'body' => '<p>x</p>',
            'kind' => 'poll',
            'metadata' => ['poll' => ['options' => [['label' => 'Only one']]]],
        ])->assertStatus(422)->assertJsonValidationErrors(['metadata.poll.options']);
    });

    test('a reply cannot be a poll (422)', function (): void {
        $this->actingAs($this->coordinator);
        $root = $this->agendaItem->comment('<p>Root</p>');

        asUser($this->coordinator)->postJson($this->storeUrl, [
            'body' => '<p>x</p>',
            'kind' => 'poll',
            'parent_id' => $root->id,
            'metadata' => ['poll' => ['options' => [['label' => 'Yes'], ['label' => 'No']]]],
        ])->assertStatus(422)->assertJsonValidationErrors(['parent_id']);
    });

    test('an outsider cannot post (403)', function (): void {
        asUser($this->outsider)->postJson($this->storeUrl, ['body' => '<p>x</p>'])->assertStatus(403);
        expect($this->agendaItem->comments()->count())->toBe(0);
    });
});

describe('update & delete', function (): void {
    test('the author can edit, marking it edited', function (): void {
        $this->actingAs($this->viewer);
        $comment = $this->agendaItem->comment('<p>Original</p>');

        asUser($this->viewer)->patchJson(route('api.v1.admin.comments.update', $comment), ['body' => '<p>Edited</p>'])
            ->assertOk()->assertJsonPath('data.body', '<p>Edited</p>');

        expect($comment->fresh()->edited_at)->not->toBeNull();
    });

    test('a non-author cannot edit (403)', function (): void {
        $this->actingAs($this->viewer);
        $comment = $this->agendaItem->comment('<p>Mine</p>');

        asUser($this->coordinator)->patchJson(route('api.v1.admin.comments.update', $comment), ['body' => '<p>hijack</p>'])
            ->assertStatus(403);
    });

    test('a moderator (parent update) can delete another user comment', function (): void {
        $this->actingAs($this->viewer);
        $comment = $this->agendaItem->comment('<p>From viewer</p>');

        asUser($this->coordinator)->deleteJson(route('api.v1.admin.comments.destroy', $comment))
            ->assertOk();

        expect($comment->fresh()->isErased())->toBeTrue();
    });

    test('deleting erases the words and the author but leaves a placeholder that keeps its replies', function (): void {
        Event::fake([CommentBroadcast::class]);
        $this->actingAs($this->viewer);
        $root = $this->agendaItem->comment('<p>Kada kitas posėdis? @Jonas</p>');
        $root->forceFill(['mentioned_user_ids' => [$this->coordinator->id]])->save();
        $root->reactions()->create(['user_id' => $this->coordinator->id, 'emoji' => '👍']);
        $this->actingAs($this->coordinator);
        $reply = $this->agendaItem->comment('<p>Rugsėjo 30 d.</p>', $root->id);

        asUser($this->viewer)->deleteJson(route('api.v1.admin.comments.destroy', $root))
            ->assertOk()
            ->assertJsonPath('data.is_erased', true)
            ->assertJsonPath('data.body', '')
            ->assertJsonPath('data.user.id', null)
            ->assertJsonPath('data.can.delete', false);

        $erased = $root->fresh();
        expect($erased->user_id)->toBeNull()
            ->and($erased->mentioned_user_ids)->toBeNull()
            ->and($erased->reactions()->count())->toBe(0)
            ->and($reply->fresh()->parent_id)->toBe($root->id);

        asUser($this->coordinator)->getJson($this->indexUrl)
            ->assertJsonPath('data.0.is_erased', true)
            ->assertJsonPath('data.0.replies.0.body', '<p>Rugsėjo 30 d.</p>');

        Event::assertDispatched(CommentBroadcast::class, fn (CommentBroadcast $event): bool => $event->action === 'updated');
    });

    test('an erased comment cannot be edited, reacted to or deleted again', function (): void {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>Ištrinsiu</p>');
        $comment->erase();

        asUser($this->coordinator)->patchJson(route('api.v1.admin.comments.update', $comment), ['body' => '<p>Vėl</p>'])->assertForbidden();
        asUser($this->coordinator)->deleteJson(route('api.v1.admin.comments.destroy', $comment))->assertForbidden();
    });

    test('an erased comment no longer counts towards the discussion', function (): void {
        $this->actingAs($this->coordinator);
        $this->agendaItem->comment('<p>Lieka</p>');
        $this->agendaItem->comment('<p>Ištrintas</p>')->erase();

        asUser($this->coordinator)->get(route('agendaItems.show', $this->agendaItem))
            ->assertInertia(fn ($page) => $page->where('siblingAgendaItems', fn ($siblings) => collect($siblings)->firstWhere('id', $this->agendaItem->id)['comments_count'] === 1));
    });

    test('a non-author non-moderator cannot delete (403)', function (): void {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>From coordinator</p>');

        asUser($this->viewer)->deleteJson(route('api.v1.admin.comments.destroy', $comment))
            ->assertStatus(403);
    });
});

describe('resolve', function (): void {
    test('a view-audience user can resolve and unresolve', function (): void {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>Question?</p>');

        asUser($this->viewer)->postJson(route('api.v1.admin.comments.resolve', $comment))
            ->assertOk()->assertJsonPath('data.is_resolved', true);

        asUser($this->viewer)->deleteJson(route('api.v1.admin.comments.resolve', $comment))
            ->assertOk()->assertJsonPath('data.is_resolved', false);
    });
});

describe('reactions', function (): void {
    test('toggling adds then removes a reaction', function (): void {
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

    test('an invalid emoji is rejected', function (): void {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>React</p>');

        asUser($this->coordinator)->putJson(route('api.v1.admin.comments.reactions.toggle', $comment), ['emoji' => '💀'])
            ->assertStatus(422)->assertJsonValidationErrors(['emoji']);
    });
});

describe('mentionables', function (): void {
    test('returns users who can view the parent', function (): void {
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

describe('resource serialization', function (): void {
    test('a comment without loaded replies serializes cleanly (broadcast payload path)', function (): void {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>Root</p>')->load('reactions.user:id,name');

        // Mirrors how the broadcast payload is built (resolve() + json_encode),
        // which previously blew up on the unloaded `replies` relation.
        $array = new CommentResource($comment)->resolve(request());

        expect($array)->not->toHaveKey('replies')
            ->and(fn () => json_encode($array, JSON_THROW_ON_ERROR))->not->toThrow(Throwable::class);
    });
});

describe('feed', function (): void {
    test('returns comments that mention the user', function (): void {
        $this->actingAs($this->coordinator);
        $body = '<p>Ping <span data-id="'.$this->viewer->id.'">@viewer</span></p>';
        $this->agendaItem->comment($body);

        asUser($this->viewer)->getJson(route('api.v1.admin.comments.feed'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.commentable_type', 'agendaItem');
    });
});

describe('reservation', function (): void {
    beforeEach(function (): void {
        $this->reservation = Reservation::factory()->create();
        $this->reservationUser = User::factory()->hasAttached($this->reservation)->create();
        $this->reservationOutsider = makeUser($this->tenant);

        $this->reservationIndexUrl = route('api.v1.admin.comments.index', ['commentableType' => 'reservation', 'commentableId' => $this->reservation->id]);
        $this->reservationStoreUrl = route('api.v1.admin.comments.store', ['commentableType' => 'reservation', 'commentableId' => $this->reservation->id]);
    });

    test('returns root comments with nested replies', function (): void {
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

    test('a reservation user can post a comment and it broadcasts', function (): void {
        Event::fake([CommentBroadcast::class]);

        asUser($this->reservationUser)->postJson($this->reservationStoreUrl, ['body' => '<p>Hello from reservation user</p>'])
            ->assertCreated()
            ->assertJsonPath('data.body', '<p>Hello from reservation user</p>')
            ->assertJsonPath('data.can.update', true)
            ->assertJsonPath('data.can.delete', true);

        Event::assertDispatched(CommentBroadcast::class, fn ($e) => $e->action === 'created'
            && $e->channelName === "comments.reservation.{$this->reservation->id}");
    });

    test('an outsider is forbidden (403)', function (): void {
        asUser($this->reservationOutsider)->getJson($this->reservationIndexUrl)->assertStatus(403);
        asUser($this->reservationOutsider)->postJson($this->reservationStoreUrl, ['body' => '<p>x</p>'])->assertStatus(403);
    });

    test('mentionables returns reservation users', function (): void {
        $otherUser = User::factory()->hasAttached($this->reservation)->create(['name' => 'Reservation Member']);

        asUser($this->reservationUser)
            ->getJson(route('api.v1.admin.comments.mentionables', ['commentableType' => 'reservation', 'commentableId' => $this->reservation->id]))
            ->assertOk()
            ->assertJsonFragment(['id' => (string) $otherUser->id, 'name' => 'Reservation Member']);
    });
});

describe('support request', function (): void {
    test('a user can comment on a public support request', function (): void {
        Event::fake([CommentBroadcast::class]);

        $supportRequest = SupportRequest::factory()->create(['visibility' => 'public']);
        $commenter = makeUser($this->tenant);
        $storeUrl = route('api.v1.admin.comments.store', [
            'commentableType' => 'supportRequest',
            'commentableId' => $supportRequest->id,
        ]);

        asUser($commenter)->postJson($storeUrl, ['body' => '<p>Public request comment</p>'])
            ->assertCreated()
            ->assertJsonPath('data.body', '<p>Public request comment</p>');

        Event::assertDispatched(CommentBroadcast::class, fn ($event) => $event->action === 'created'
            && $event->channelName === "comments.supportRequest.{$supportRequest->id}");
    });

    test('mentionables include the reporter, assignee, involved people and role members', function (): void {
        $creator = makeUser($this->tenant);
        $assignee = makeUser($this->tenant);
        $involved = makeUser($this->tenant);
        $roleMember = makeUser($this->tenant);
        $role = Role::create(['name' => 'Support Mentions', 'guard_name' => 'web']);
        $roleMember->assignRole($role);

        $supportRequest = SupportRequest::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
            'visibility' => 'roles',
        ]);
        $supportRequest->roles()->attach($role);
        $supportRequest->involvedUsers()->attach($involved);

        $ids = asUser($involved)
            ->getJson(route('api.v1.admin.comments.mentionables', ['commentableType' => 'supportRequest', 'commentableId' => $supportRequest->id]))
            ->assertOk()
            ->collect('data')
            ->pluck('id');

        expect($ids->sort()->values()->all())
            ->toBe(collect([$creator->id, $assignee->id, $involved->id, $roleMember->id])->sort()->values()->all());
    });

    test('a root comment reaches the reporter, assignee and involved people but not role members', function (): void {
        $creator = makeUser($this->tenant);
        $assignee = makeUser($this->tenant);
        $involved = makeUser($this->tenant);
        $roleMember = makeUser($this->tenant);
        $role = Role::create(['name' => 'Support Audience', 'guard_name' => 'web']);
        $roleMember->assignRole($role);

        $supportRequest = SupportRequest::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
            'visibility' => 'roles',
        ]);
        $supportRequest->roles()->attach($role);
        $supportRequest->involvedUsers()->attach($involved);

        $this->actingAs($involved);
        $comment = $supportRequest->comment('<p>Man irgi neveikia</p>');

        $audience = app(CommentRecipientResolver::class)->audience($comment)->pluck('id');

        expect($audience->sort()->values()->all())
            ->toBe(collect([$creator->id, $assignee->id])->sort()->values()->all());
    });
});

/**
 * Duty and form records mount the same Veikla panel as every other record; before these
 * were allowlisted, every visit to either page answered 404 with a "Commentable not found" toast.
 */
describe('duty', function (): void {
    beforeEach(function (): void {
        $this->duty = Duty::factory()->for($this->institution)->create();
        $this->dutyIndexUrl = route('api.v1.admin.comments.index', ['commentableType' => 'duty', 'commentableId' => $this->duty->id]);
        $this->dutyStoreUrl = route('api.v1.admin.comments.store', ['commentableType' => 'duty', 'commentableId' => $this->duty->id]);
    });

    test('a coordinator reads and posts on a duty in their padalinys', function (): void {
        asUser($this->coordinator)->getJson($this->dutyIndexUrl)->assertOk()->assertJsonCount(0, 'data');

        asUser($this->coordinator)->postJson($this->dutyStoreUrl, ['body' => '<p>Vietos rudeniui</p>'])
            ->assertCreated()
            ->assertJsonPath('data.body', '<p>Vietos rudeniui</p>');
    });

    test('an outsider is forbidden (403)', function (): void {
        asUser($this->outsider)->getJson($this->dutyIndexUrl)->assertStatus(403);
        asUser($this->outsider)->postJson($this->dutyStoreUrl, ['body' => '<p>x</p>'])->assertStatus(403);
    });

    /** `$duty->users` is every person who ever held the seat; only current holders are offered. */
    test('mentionables are the current holders, never former ones', function (): void {
        $current = User::factory()->create(['name' => 'Dabartinė narė']);
        $current->duties()->attach($this->duty, ['start_date' => now()->subMonth(), 'end_date' => null]);

        $former = User::factory()->create(['name' => 'Buvęs narys']);
        $former->duties()->attach($this->duty, ['start_date' => now()->subYears(3), 'end_date' => now()->subYears(2)]);

        asUser($this->coordinator)
            ->getJson(route('api.v1.admin.comments.mentionables', ['commentableType' => 'duty', 'commentableId' => $this->duty->id]))
            ->assertOk()
            ->assertJsonFragment(['id' => (string) $current->id])
            ->assertJsonMissing(['id' => (string) $former->id]);
    });
});

describe('form', function (): void {
    beforeEach(function (): void {
        $this->form = Form::factory()->for($this->tenant)->create();
        $this->formIndexUrl = route('api.v1.admin.comments.index', ['commentableType' => 'form', 'commentableId' => $this->form->id]);
        $this->formStoreUrl = route('api.v1.admin.comments.store', ['commentableType' => 'form', 'commentableId' => $this->form->id]);
    });

    test('a coordinator reads and posts on a form in their padalinys', function (): void {
        asUser($this->coordinator)->getJson($this->formIndexUrl)->assertOk();

        asUser($this->coordinator)->postJson($this->formStoreUrl, ['body' => '<p>Pataisyk klausimą</p>'])
            ->assertCreated();
    });

    test('an outsider is forbidden (403)', function (): void {
        asUser($this->outsider)->getJson($this->formIndexUrl)->assertStatus(403);
    });
});
