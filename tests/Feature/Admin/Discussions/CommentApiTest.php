<?php

use App\Actions\Media\AddImageMedia;
use App\Actions\ResolveCommentAuthorDuty;
use App\Enums\CommentKind;
use App\Enums\MeetingType;
use App\Enums\SystemDutyType;
use App\Enums\TenantType;
use App\Events\CommentBroadcast;
use App\Events\CommentPosted;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Form;
use App\Models\Institution;
use App\Models\InstitutionLink;
use App\Models\KnowledgeItem;
use App\Models\Meeting;
use App\Models\PendingUpload;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\Problem;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\SupportRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\CommentRecipientResolver;
use App\Services\SystemDutyTypes;
use App\Settings\DutyTypeSettings;
use App\Support\Commentables;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

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
    // Sees the meeting through a linked institution; members of the meeting's own institution edit it.
    $linkedInstitution = Institution::factory()->for($this->tenant)->create();
    InstitutionLink::factory()->create(['source_institution_id' => $linkedInstitution->id, 'target_institution_id' => $this->institution->id]);
    $duty = Duty::factory()->for($linkedInstitution)->create();
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
        $open = $this->agendaItem->comment('<p>Open</p>', attributes: ['kind' => CommentKind::Topic, 'title' => 'Open']);
        $done = $this->agendaItem->comment('<p>Done</p>', attributes: ['kind' => CommentKind::Topic, 'title' => 'Done']);
        $done->resolve($this->coordinator);

        asUser($this->coordinator)->getJson($this->indexUrl.'?resolved=0')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $open->id);

        asUser($this->coordinator)->getJson($this->indexUrl.'?resolved=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $done->id);
    });

    test('empty and invalid resolved filters leave all threads visible', function (string $filter): void {
        $this->actingAs($this->coordinator);
        $open = $this->agendaItem->comment('<p>Open</p>', attributes: ['kind' => CommentKind::Topic, 'title' => 'Open']);
        $done = $this->agendaItem->comment('<p>Done</p>', attributes: ['kind' => CommentKind::Topic, 'title' => 'Done']);
        $done->resolve($this->coordinator);

        asUser($this->coordinator)->getJson($this->indexUrl.$filter)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $open->id])
            ->assertJsonFragment(['id' => $done->id]);
    })->with(['empty' => '?resolved=', 'invalid' => '?resolved=invalid', 'array' => '?resolved[]=x']);

    test('an outsider is forbidden before validating the resolved filter', function (): void {
        asUser($this->outsider)->getJson($this->indexUrl.'?resolved[]=x')->assertForbidden();
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

    test('the author can edit within 15 minutes but not after, and can still delete', function (): void {
        $this->actingAs($this->viewer);
        $comment = $this->agendaItem->comment('<p>Original</p>');

        $this->travel(14)->minutes();
        asUser($this->viewer)->patchJson(route('api.v1.admin.comments.update', $comment), ['body' => '<p>Typo fixed</p>'])->assertOk();

        $this->travel(2)->minutes();
        asUser($this->viewer)->patchJson(route('api.v1.admin.comments.update', $comment), ['body' => '<p>Too late</p>'])->assertForbidden();
        asUser($this->viewer)->getJson($this->indexUrl)->assertJsonPath('data.0.can.update', false)->assertJsonPath('data.0.can.delete', true);

        asUser($this->viewer)->deleteJson(route('api.v1.admin.comments.destroy', $comment))->assertSuccessful();
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

        asUserWithInertia($this->coordinator)->get(route('agendaItems.show', $this->agendaItem))
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
    test('a topic author can close and reopen their topic', function (): void {
        $this->actingAs($this->viewer);
        $comment = $this->agendaItem->comment('<p>Question?</p>', attributes: ['kind' => CommentKind::Topic, 'title' => 'Question']);

        asUser($this->viewer)->postJson(route('api.v1.admin.comments.resolve', $comment))
            ->assertOk()->assertJsonPath('data.is_resolved', true)->assertJsonPath('data.is_closed', true);

        asUser($this->viewer)->deleteJson(route('api.v1.admin.comments.resolve', $comment))
            ->assertOk()->assertJsonPath('data.is_resolved', false)->assertJsonPath('data.is_closed', false);

        expect($comment->fresh()->resolved_at)->toBeNull();
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

describe('reply destinations', function (): void {
    test('root comments and nested replies round trip on every discussion record', function (string $type): void {
        Event::fake([CommentBroadcast::class]);
        $commentable = match ($type) {
            'knowledgeItem' => KnowledgeItem::factory()->create(),
            'meeting' => $this->meeting,
            'agendaItem' => $this->agendaItem,
            'institution' => $this->institution,
            'duty' => $this->coordinator->duties()->firstOrFail(),
            'form' => Form::factory()->for($this->tenant)->create(),
            'problem' => Problem::factory()->for($this->tenant)->create(['created_by' => $this->coordinator->id]),
            'reservation' => Reservation::factory()->create(),
            'supportRequest' => SupportRequest::factory()->create(['created_by' => $this->coordinator->id]),
        };
        if ($commentable instanceof Reservation) {
            $commentable->users()->attach($this->coordinator);
        }
        $params = ['commentableType' => $type, 'commentableId' => $commentable->getKey()];
        $url = route('api.v1.admin.comments.store', $params);
        $rootId = asUser($this->coordinator)->postJson($url, ['body' => '<p>Root</p>'])
            ->assertCreated()->json('data.id');

        $replyId = asUser($this->coordinator)->postJson($url, ['body' => '<p>Reply</p>', 'parent_id' => $rootId])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $rootId)
            ->assertJsonPath('data.thread_root_id', $rootId)
            ->json('data.id');
        asUser($this->coordinator)->postJson($url, ['body' => '<p>Nested reply</p>', 'parent_id' => $replyId])
            ->assertCreated()
            ->assertJsonPath('data.parent_id', $replyId)
            ->assertJsonPath('data.thread_root_id', $rootId);

        asUser($this->coordinator)->getJson(route('api.v1.admin.comments.index', $params))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(2, 'data.0.replies');
        expect($commentable->comments()->count())->toBe(3);
        Event::assertDispatchedTimes(CommentBroadcast::class, 3);
    })->with(array_keys(Commentables::TYPES));

    test('a reply cannot use a parent from another record (403)', function (string $destination): void {
        Event::fake([CommentBroadcast::class]);
        $this->actingAs($this->coordinator);
        $otherRecord = match ($destination) {
            'same type' => AgendaItem::factory()->create(['meeting_id' => $this->meeting->id]),
            'different type' => $this->meeting,
            'different tenant' => Problem::factory()->create([
                'tenant_id' => Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail()->id,
            ]),
        };
        $parent = $otherRecord->comment('<p>Another record</p>');

        asUser($this->coordinator)->postJson($this->storeUrl, [
            'body' => '<p>Misplaced reply</p>', 'parent_id' => $parent->id,
        ])->assertForbidden();

        expect($this->agendaItem->comments()->count())->toBe(0)
            ->and($parent->replies()->count())->toBe(0);
        Event::assertNotDispatched(CommentBroadcast::class);
    })->with(['same type', 'different type', 'different tenant']);

    test('a record excludes legacy replies belonging to another record', function (): void {
        $this->actingAs($this->coordinator);
        $root = $this->agendaItem->comment('<p>Root</p>');
        Comment::factory()->create([
            'user_id' => $this->coordinator->id,
            'commentable_type' => $this->meeting->getMorphClass(),
            'commentable_id' => $this->meeting->id,
            'parent_id' => $root->id,
            'thread_root_id' => $root->id,
            'body' => '<p>Another record</p>',
        ]);

        asUser($this->coordinator)->getJson($this->indexUrl)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonCount(0, 'data.0.replies')
            ->assertJsonMissing(['body' => '<p>Another record</p>']);
    });

});

describe('topics and capabilities', function (): void {
    test('creates a titled topic', function (): void {
        $response = asUser($this->viewer)->postJson($this->storeUrl, [
            'kind' => 'topic', 'title' => 'Next meeting', 'body' => '<p>When?</p>',
        ])->assertCreated()
            ->assertJsonPath('data.kind', 'topic')
            ->assertJsonPath('data.title', 'Next meeting')
            ->assertJsonPath('data.is_closed', false)
            ->assertJsonPath('data.can.resolve', true);

        $this->assertDatabaseHas('comments', ['id' => $response->json('data.id'), 'title' => 'Next meeting', 'kind' => 'topic']);
    });

    test('validates topic titles and forbids titles on other kinds', function (array $payload): void {
        asUser($this->viewer)->postJson($this->storeUrl, $payload + ['body' => '<p>Text</p>'])
            ->assertUnprocessable()->assertJsonValidationErrors('title');

        expect($this->agendaItem->comments()->count())->toBe(0);
    })->with([
        'missing' => [['kind' => 'topic']],
        'empty' => [['kind' => 'topic', 'title' => '']],
        'too long' => [['kind' => 'topic', 'title' => str_repeat('a', 161)]],
        'not a string' => [['kind' => 'topic', 'title' => ['Title']]],
        'plain comment' => [['kind' => 'comment', 'title' => 'Title']],
        'poll' => [['kind' => 'poll', 'title' => 'Title', 'metadata' => ['poll' => ['options' => [['label' => 'Yes'], ['label' => 'No']]]]]],
    ]);

    test('support requests reject topics and polls', function (string $kind): void {
        $support = SupportRequest::factory()->create(['visibility' => 'public']);
        $payload = ['kind' => $kind, 'body' => '<p>Text</p>'];
        if ($kind === 'topic') {
            $payload['title'] = 'Title';
        } else {
            $payload['metadata'] = ['poll' => ['options' => [['label' => 'Yes'], ['label' => 'No']]]];
        }

        asUser($this->viewer)->postJson(route('api.v1.admin.comments.store', [
            'commentableType' => 'supportRequest', 'commentableId' => $support->id,
        ]), $payload)->assertUnprocessable()->assertJsonValidationErrors('kind');

        expect($support->comments()->count())->toBe(0);
    })->with(['topic', 'poll']);

    test('a topic cannot be a reply', function (): void {
        $root = Comment::factory()->for($this->agendaItem, 'commentable')->create();

        asUser($this->viewer)->postJson($this->storeUrl, [
            'kind' => 'topic', 'title' => 'Title', 'body' => '<p>Text</p>', 'parent_id' => $root->id,
        ])->assertUnprocessable()->assertJsonValidationErrors('parent_id');

        expect($root->replies()->count())->toBe(0);
    });

    test('a parent updater can close and reopen another authors topic', function (): void {
        $topic = Comment::factory()->topic()->for($this->agendaItem, 'commentable')->for($this->viewer)->create();

        asUser($this->coordinator)->postJson(route('api.v1.admin.comments.resolve', $topic))
            ->assertOk()->assertJsonPath('data.is_closed', true)->assertJsonPath('data.can.resolve', true);
        expect($topic->fresh()->resolved_by)->toBe($this->coordinator->id);

        asUser($this->coordinator)->deleteJson(route('api.v1.admin.comments.resolve', $topic))
            ->assertOk()->assertJsonPath('data.is_closed', false);
        expect($topic->fresh()->resolved_at)->toBeNull();
    });

    test('another viewer cannot close or reopen a topic', function (): void {
        $topic = Comment::factory()->topic()->for($this->agendaItem, 'commentable')->for($this->coordinator)->create();

        asUser($this->viewer)->getJson($this->indexUrl)->assertOk()->assertJsonPath('data.0.can.resolve', false);
        asUser($this->viewer)->postJson(route('api.v1.admin.comments.resolve', $topic))->assertForbidden();
        expect($topic->fresh()->resolved_at)->toBeNull();

        $topic->resolve($this->coordinator);
        asUser($this->viewer)->deleteJson(route('api.v1.admin.comments.resolve', $topic))->assertForbidden();
        expect($topic->fresh()->isResolved())->toBeTrue();
    });

    test('plain comments polls and erased topics cannot be closed', function (string $state): void {
        $comment = Comment::factory()->for($this->agendaItem, 'commentable')->for($this->coordinator)->create([
            'kind' => $state === 'erased' ? CommentKind::Topic : CommentKind::from($state),
            'title' => $state === 'erased' ? 'Title' : null,
        ]);
        if ($state === 'erased') {
            $comment->erase();
        }

        asUser($this->coordinator)->postJson(route('api.v1.admin.comments.resolve', $comment))->assertForbidden();
        asUser($this->coordinator)->deleteJson(route('api.v1.admin.comments.resolve', $comment))->assertForbidden();
        asUser($this->coordinator)->getJson($this->indexUrl)->assertOk()->assertJsonPath('data.0.can.resolve', false);
        expect($comment->fresh()->resolved_at)->toBeNull();
    })->with(['comment', 'poll', 'erased']);

    test('index exposes capabilities and current author duty choices', function (): void {
        $option = $this->viewer->dutiables()->with('duty.institution')->firstOrFail();
        $this->viewer->update(['pronouns' => ['lt' => 'ji', 'en' => 'she']]);
        $root = Comment::factory()->topic()->for($this->agendaItem, 'commentable')->for($this->viewer)->create(['author_dutiable_id' => $option->id]);
        Comment::factory()->for($this->agendaItem, 'commentable')->for($this->viewer)->create([
            'parent_id' => $root->id, 'thread_root_id' => $root->id, 'author_dutiable_id' => $option->id,
        ]);

        asUser($this->viewer)->getJson($this->indexUrl)->assertOk()
            ->assertJsonPath('meta.capabilities', ['layout' => 'comments', 'topics' => true, 'polls' => true, 'images' => true])
            ->assertJsonCount(1, 'meta.me.duties')
            ->assertJsonPath('meta.me.default_dutiable_id', $option->id)
            ->assertJsonPath('meta.me.duties.0.dutiable_id', $option->id)
            ->assertJsonPath('data.0.author_duty.dutiable_id', $option->id)
            ->assertJsonPath('data.0.replies.0.author_duty.dutiable_id', $option->id)
            ->assertJsonPath('data.0.user.pronouns', ['lt' => 'ji', 'en' => 'she'])
            ->assertJsonPath('data.0.replies.0.user.pronouns', ['lt' => 'ji', 'en' => 'she'])
            ->assertJsonStructure(['meta' => ['capabilities', 'me' => ['duties' => [['dutiable_id', 'name', 'institution', 'use_original_duty_name']], 'default_dutiable_id']]]);
    });

    test('index exposes alias specific capabilities even without duties', function (string $alias, string $layout, bool $topics): void {
        $user = User::factory()->create();
        $record = $alias === 'supportRequest'
            ? SupportRequest::factory()->create(['visibility' => 'public'])
            : KnowledgeItem::factory()->create(['is_active' => true]);

        asUser($user)->getJson(route('api.v1.admin.comments.index', ['commentableType' => $alias, 'commentableId' => $record->id]))
            ->assertOk()->assertJsonPath('meta', [
                'capabilities' => ['layout' => $layout, 'topics' => $topics, 'polls' => $topics, 'images' => true],
                'me' => ['duties' => [], 'default_dutiable_id' => null, 'can_moderate' => false],
            ]);
    })->with([['supportRequest', 'conversation', false], ['knowledgeItem', 'topics', true]]);
});

test('index tells a moderator, and only a moderator, that they moderate this record', function (): void {
    asUser($this->coordinator)->getJson($this->indexUrl)->assertOk()->assertJsonPath('meta.me.can_moderate', true);
    asUser($this->viewer)->getJson($this->indexUrl)->assertOk()->assertJsonPath('meta.me.can_moderate', false);
});

describe('closed topics', function (): void {
    test('refuse replies to the topic and to its replies, and accept them again once reopened', function (): void {
        $topic = Comment::factory()->topic()->for($this->agendaItem, 'commentable')->for($this->viewer)->create();
        $reply = Comment::factory()->for($this->agendaItem, 'commentable')->for($this->viewer)
            ->create(['parent_id' => $topic->id, 'thread_root_id' => $topic->id]);
        $topic->resolve($this->viewer);

        foreach ([$topic, $reply] as $parent) {
            asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Late</p>', 'parent_id' => $parent->id])
                ->assertUnprocessable()->assertJsonValidationErrors('parent_id');
        }
        expect($topic->threadReplies()->count())->toBe(1);

        $topic->unresolve();
        asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Again</p>', 'parent_id' => $topic->id])->assertCreated();
    });
});

describe('author duty', function (): void {
    test('rejects a foreign or future duty assignment', function (string $state): void {
        $this->freezeTime();
        $option = Dutiable::factory()->forUser($state === 'foreign' ? $this->coordinator : $this->viewer)
            ->forDuty(Duty::factory()->for($this->institution)->create())->create([
                'start_date' => $state === 'future' ? today()->addDay() : today()->subMonth(),
            ]);

        asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Text</p>', 'dutiable_id' => $option->id])
            ->assertUnprocessable()->assertJsonValidationErrors('dutiable_id');

        expect($this->agendaItem->comments()->count())->toBe(0);
    })->with(['foreign', 'future']);

    test('an assignment whose duty was deleted is not offered', function (): void {
        $gone = Dutiable::factory()->forUser($this->viewer)->forDuty(Duty::factory()->for($this->institution)->create())
            ->create(['start_date' => today()->subMonth(), 'end_date' => null]);
        $gone->duty->delete();

        expect(app(ResolveCommentAuthorDuty::class)->options($this->viewer->fresh())->pluck('id'))->not->toContain($gone->id);
    });

    test('a former duty is offered with its period and can be chosen, but is never the default', function (): void {
        $this->freezeTime();
        $former = Dutiable::factory()->forUser($this->viewer)->forDuty(Duty::factory()->for($this->institution)->create())
            ->create(['start_date' => today()->subYear(), 'end_date' => today()->subMonth()]);
        $current = $this->viewer->dutiables()->whereKeyNot($former->id)->firstOrFail();

        asUser($this->viewer)->getJson($this->indexUrl)->assertOk()
            ->assertJsonPath('meta.me.duties.0.dutiable_id', $current->id)
            ->assertJsonPath('meta.me.duties.1.dutiable_id', $former->id)
            ->assertJsonPath('meta.me.duties.1.is_current', false)
            ->assertJsonPath('meta.me.duties.1.start_date', today()->subYear()->toDateString())
            ->assertJsonPath('meta.me.duties.1.end_date', today()->subMonth()->toDateString())
            ->assertJsonPath('meta.me.default_dutiable_id', $current->id);

        asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Text</p>', 'dutiable_id' => $former->id])
            ->assertCreated()->assertJsonPath('data.author_duty.dutiable_id', $former->id)
            ->assertJsonPath('data.author_duty.is_current', false);
    });

    test('an explicit current duty is stored for every kind and merges the preference quietly', function (string $kind): void {
        $option = $this->viewer->dutiables()->firstOrFail();
        $this->viewer->forceFill(['ui_preferences' => ['theme' => 'dark']])->saveQuietly();
        $activityCount = $this->viewer->activitiesAsSubject()->count();
        $payload = ['body' => '<p>Text</p>', 'kind' => $kind, 'dutiable_id' => $option->id];
        if ($kind === 'topic') {
            $payload['title'] = 'Title';
        } elseif ($kind === 'poll') {
            $payload['metadata'] = ['poll' => ['options' => [['label' => 'Yes'], ['label' => 'No']]]];
        } elseif ($kind === 'reply') {
            $payload['kind'] = 'comment';
            $payload['parent_id'] = Comment::factory()->topic()->for($this->agendaItem, 'commentable')->create()->id;
        }

        $response = asUser($this->viewer)->postJson($this->storeUrl, $payload)
            ->assertCreated()->assertJsonPath('data.author_duty.dutiable_id', $option->id);

        $this->assertDatabaseHas('comments', ['id' => $response->json('data.id'), 'author_dutiable_id' => $option->id]);
        expect($this->viewer->fresh()->ui_preferences)->toMatchArray(['theme' => 'dark', 'comment_dutiable_id' => $option->id]);
        expect($this->viewer->activitiesAsSubject()->count())->toBe($activityCount);
    })->with(['comment', 'topic', 'poll', 'reply']);

    test('a duty tied to the record wins over the last choice, which wins over picker order', function (): void {
        $this->freezeTime();
        $user = User::factory()->create();
        $matching = Dutiable::factory()->forUser($user)->forDuty(Duty::factory()->for($this->institution)->create())
            ->create(['start_date' => today()->subMonth(), 'end_date' => today()]);
        $other = Dutiable::factory()->forUser($user)->forDuty(Duty::factory()->for(Institution::factory()->for($this->tenant))->create())
            ->create(['start_date' => today(), 'end_date' => null]);
        $resolver = app(ResolveCommentAuthorDuty::class);
        $user->forceFill(['ui_preferences' => ['comment_dutiable_id' => $other->id]])->saveQuietly();

        foreach ([$this->institution, $matching->duty, $this->meeting, $this->agendaItem] as $record) {
            expect($resolver->default($user, $record))->toBe($matching->id);
        }
        expect($resolver->default($user, new SupportRequest))->toBe($other->id);

        $user->forceFill(['ui_preferences' => ['comment_dutiable_id' => 'stale']])->saveQuietly();
        expect($resolver->default($user, new SupportRequest))->toBe($other->id);

        $matching->update(['end_date' => today()->subDay()]);
        $other->update(['end_date' => today()->subDay()]);
        expect($resolver->default($user, $this->agendaItem))->toBeNull();
    });

    test('the picker lists a coordinator duty first, then by institution prominence, then former duties', function (): void {
        $coordinatorType = DutyType::factory()->create();
        app(DutyTypeSettings::class)->fill([SystemDutyType::Coordinator->settingKey() => $coordinatorType->id])->save();
        app(SystemDutyTypes::class)->flush();
        $user = User::factory()->create();
        $assign = fn (Institution $institution, array $dates = []) => Dutiable::factory()->forUser($user)
            ->forDuty(Duty::factory()->for($institution)->create())
            ->create(array_merge(['start_date' => today()->subMonth(), 'end_date' => null], $dates));

        $main = Tenant::factory()->create(['type' => TenantType::Pagrindinis]);
        $vusa = Institution::factory()->for($main)->create();
        $main->update(['primary_institution_id' => $vusa->id]);
        $centralOffice = Institution::factory()->for($main)->create();
        $centralOffice->forceFill(['alias' => 'centrinis-biuras'])->saveQuietly();

        $ordinary = $assign(Institution::factory()->for($this->tenant)->create(), ['start_date' => today()]);
        $office = $assign($centralOffice);
        $atVusa = $assign($vusa);
        $former = $assign($vusa, ['start_date' => today()->subYear(), 'end_date' => today()->subMonth()]);
        $coordinator = $assign(Institution::factory()->for($this->tenant)->create());
        $coordinator->duty->types()->attach($coordinatorType);

        $resolver = app(ResolveCommentAuthorDuty::class);

        expect($resolver->options($user->fresh())->pluck('id')->all())
            ->toBe([$coordinator->id, $atVusa->id, $office->id, $ordinary->id, $former->id])
            ->and($resolver->default($user->fresh(), new SupportRequest))->toBe($coordinator->id)
            ->and($resolver->default($user->fresh(), $ordinary->duty->institution))->toBe($ordinary->id);
    });

    test('post without a selection uses the default without changing preferences', function (): void {
        $option = $this->viewer->dutiables()->firstOrFail();
        $this->viewer->forceFill(['ui_preferences' => ['theme' => 'dark']])->saveQuietly();

        asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Text</p>'])
            ->assertCreated()->assertJsonPath('data.author_duty.dutiable_id', $option->id);

        expect($this->viewer->fresh()->ui_preferences)->toMatchArray(['theme' => 'dark'])->not->toHaveKey('comment_dutiable_id');
    });
});

describe('comment images', function (): void {
    test('own pending images attach in order keep their files and are erased with the comment', function (): void {
        Storage::fake('spatieMediaLibrary');
        $pending = PendingUpload::create(['user_id' => $this->viewer->id]);
        $first = app(AddImageMedia::class)->execute($pending, UploadedFile::fake()->image('first.png'), PendingUpload::COLLECTION);
        $secondPending = PendingUpload::create(['user_id' => $this->viewer->id]);
        $second = app(AddImageMedia::class)->execute($secondPending, UploadedFile::fake()->image('second.png'), PendingUpload::COLLECTION);
        $paths = [$first->getPathRelativeToRoot(), $second->getPathRelativeToRoot()];

        $response = asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Images</p>', 'image_ids' => [$second->id, $first->id]])
            ->assertCreated()->assertJsonCount(2, 'data.images')
            ->assertJsonPath('data.images.0.id', $second->id)->assertJsonPath('data.images.1.id', $first->id)
            ->assertJsonStructure(['data' => ['images' => [['id', 'url', 'thumb', 'srcset', 'width', 'height', 'focal_point', 'alt', 'author']]]]);

        $comment = Comment::findOrFail($response->json('data.id'));
        expect($comment->getMedia(Comment::IMAGES)->pluck('id')->all())->toBe([$second->id, $first->id]);
        $this->assertModelMissing($pending);
        $this->assertModelMissing($secondPending);
        Storage::disk('spatieMediaLibrary')->assertExists($paths);
        asUser($this->viewer)->getJson($this->indexUrl)->assertOk()->assertJsonCount(2, 'data.0.images');

        asUser($this->viewer)->deleteJson(route('api.v1.admin.comments.destroy', $comment))
            ->assertOk()->assertJsonPath('data.images', []);

        expect($comment->fresh()->media()->count())->toBe(0);
        Storage::disk('spatieMediaLibrary')->assertMissing($paths);
    });

    test('foreign pending images return 403 and create nothing or send notifications', function (): void {
        Storage::fake('spatieMediaLibrary');
        Event::fake([CommentPosted::class, CommentBroadcast::class]);
        $pending = PendingUpload::create(['user_id' => $this->coordinator->id]);
        $media = app(AddImageMedia::class)->execute($pending, UploadedFile::fake()->image('foreign.png'), PendingUpload::COLLECTION);

        asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Text</p>', 'image_ids' => [$media->id]])->assertForbidden();

        expect($this->agendaItem->comments()->count())->toBe(0);
        expect($media->fresh()->model_id)->toBe($pending->id);
        $this->assertModelExists($pending);
        Event::assertNotDispatched(CommentPosted::class);
        Event::assertNotDispatched(CommentBroadcast::class);
    });

    test('image ids must be integers and at most four', function (array $ids): void {
        asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Text</p>', 'image_ids' => $ids])
            ->assertUnprocessable()->assertJsonValidationErrors(count($ids) > 4 ? 'image_ids' : 'image_ids.0');
        expect($this->agendaItem->comments()->count())->toBe(0);
    })->with([[[1, 2, 3, 4, 5]], [['invalid']]]);
});

test('author duty labels use the current locale, the full institution name and its tenant', function (): void {
    app()->setLocale('en');
    $this->withSession(['lang' => 'en']);
    $institution = Institution::factory()->for($this->tenant)->create([
        'name' => ['lt' => 'Institucija', 'en' => 'Institution'],
        'short_name' => ['lt' => 'INST', 'en' => ''],
    ]);
    $duty = Duty::factory()->for($institution)->create(['name' => ['lt' => 'Atstovė', 'en' => 'Representative']]);
    $assignment = Dutiable::factory()->forUser($this->viewer)->forDuty($duty)->create([
        'start_date' => today()->subDay(), 'end_date' => null, 'use_original_duty_name' => true,
    ]);

    asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Text</p>', 'dutiable_id' => $assignment->id])
        ->assertCreated()->assertJsonPath('data.author_duty', [
            'dutiable_id' => $assignment->id, 'name' => 'Representative',
            'institution' => 'Institution', 'tenant' => $this->tenant->shortname, 'use_original_duty_name' => true,
            'start_date' => today()->subDay()->toDateString(), 'end_date' => null, 'is_current' => true,
        ]);
});

test('reply images appear under their thread and attached images cannot be claimed again', function (): void {
    Storage::fake('spatieMediaLibrary');
    $root = Comment::factory()->topic()->for($this->agendaItem, 'commentable')->for($this->viewer)->create();
    $pending = PendingUpload::create(['user_id' => $this->viewer->id]);
    $media = app(AddImageMedia::class)->execute($pending, UploadedFile::fake()->image('reply.png'), PendingUpload::COLLECTION);

    $reply = asUser($this->viewer)->postJson($this->storeUrl, [
        'body' => '<p>Reply</p>', 'parent_id' => $root->id, 'image_ids' => [$media->id],
    ])->assertCreated()->assertJsonPath('data.images.0.id', $media->id)->json('data.id');

    asUser($this->viewer)->getJson($this->indexUrl)->assertOk()
        ->assertJsonPath('data.0.replies.0.id', $reply)->assertJsonPath('data.0.replies.0.images.0.id', $media->id);
    asUser($this->viewer)->postJson($this->storeUrl, ['body' => '<p>Reuse</p>', 'image_ids' => [$media->id]])->assertForbidden();
    expect($this->agendaItem->comments()->count())->toBe(2);
    expect($media->fresh()->model_id)->toBe($reply);
});

test('erasing a topic also clears its title and author duty', function (): void {
    $assignment = $this->viewer->dutiables()->firstOrFail();
    $topic = Comment::factory()->topic()->for($this->agendaItem, 'commentable')->for($this->viewer)
        ->create(['author_dutiable_id' => $assignment->id]);
    $topic->resolve($this->viewer);

    asUser($this->viewer)->deleteJson(route('api.v1.admin.comments.destroy', $topic))->assertOk()
        ->assertJsonPath('data.title', null)->assertJsonPath('data.author_duty', null)->assertJsonPath('data.is_closed', false);

    expect($topic->fresh()->author_dutiable_id)->toBeNull();
    expect($topic->fresh()->resolved_by)->toBeNull();
});
