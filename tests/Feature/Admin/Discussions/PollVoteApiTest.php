<?php

use App\Enums\CommentKind;
use App\Enums\MeetingType;
use App\Events\CommentBroadcast;
use App\Models\Comment;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

/**
 * Create a poll comment on the agenda item with two stable option ids.
 *
 * @param  array<string, mixed>  $poll  Extra poll metadata (e.g. allow_multiple, closes_at).
 */
function makePoll(AgendaItem $agendaItem, User $author, array $poll = []): Comment
{
    test()->actingAs($author);

    return $agendaItem->comment('<p>Pick one</p>', null, [
        'kind' => CommentKind::Poll,
        'metadata' => ['poll' => array_merge([
            'options' => [['id' => 'a', 'label' => 'Yes'], ['id' => 'b', 'label' => 'No']],
            'allow_multiple' => false,
        ], $poll)],
    ]);
}

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    $this->institution = Institution::factory()->for($this->tenant)->create();

    $this->meeting = Meeting::create([
        'title' => 'Poll meeting',
        'start_time' => Carbon::now()->addDay()->format('Y-m-d H:i'),
        'type' => MeetingType::InPerson,
    ]);
    $this->meeting->institutions()->attach($this->institution->id);
    $this->agendaItem = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id]);

    $duty = Duty::factory()->for($this->institution)->create();
    $this->viewer = User::factory()->create();
    $this->viewer->duties()->attach($duty, ['start_date' => now()->subDay(), 'end_date' => null]);

    $this->outsider = makeUser(
        Tenant::query()->where('id', '!=', $this->tenant->id)->inRandomOrder()->first() ?? $this->tenant
    );

    $this->voteUrl = fn (Comment $poll) => route('api.v1.admin.comments.poll.votes.toggle', $poll);
});

describe('single-choice voting', function () {
    test('a vote is recorded, switched, then retracted by toggling', function () {
        $poll = makePoll($this->agendaItem, $this->coordinator);
        $url = ($this->voteUrl)($poll);

        // Cast a vote for option "a".
        asUser($this->viewer)->putJson($url, ['option_id' => 'a'])
            ->assertOk()
            ->assertJsonPath('data.poll.total_votes', 1)
            ->assertJsonPath('data.poll.my_option_ids', ['a']);

        // Switching to "b" replaces it (single-choice).
        asUser($this->viewer)->putJson($url, ['option_id' => 'b'])
            ->assertOk()
            ->assertJsonPath('data.poll.total_votes', 1)
            ->assertJsonPath('data.poll.my_option_ids', ['b']);

        expect($poll->pollVotes()->where('user_id', $this->viewer->id)->count())->toBe(1);

        // Toggling the current option off retracts the vote.
        asUser($this->viewer)->putJson($url, ['option_id' => 'b'])
            ->assertOk()
            ->assertJsonPath('data.poll.total_votes', 0)
            ->assertJsonPath('data.poll.my_option_ids', []);
    });

    test('tallies expose the voters behind each option', function () {
        $poll = makePoll($this->agendaItem, $this->coordinator);

        asUser($this->viewer)->putJson(($this->voteUrl)($poll), ['option_id' => 'a'])
            ->assertOk()
            ->assertJsonPath('data.poll.tallies.0.option_id', 'a')
            ->assertJsonPath('data.poll.tallies.0.count', 1)
            ->assertJsonPath('data.poll.tallies.0.voters.0.id', (string) $this->viewer->id);
    });
});

describe('multiple-choice voting', function () {
    test('options toggle independently', function () {
        $poll = makePoll($this->agendaItem, $this->coordinator, ['allow_multiple' => true]);
        $url = ($this->voteUrl)($poll);

        asUser($this->viewer)->putJson($url, ['option_id' => 'a'])->assertOk();
        asUser($this->viewer)->putJson($url, ['option_id' => 'b'])
            ->assertOk()
            ->assertJsonPath('data.poll.my_option_ids', ['a', 'b'])
            ->assertJsonPath('data.poll.total_votes', 1);

        asUser($this->viewer)->putJson($url, ['option_id' => 'a'])
            ->assertOk()
            ->assertJsonPath('data.poll.my_option_ids', ['b']);
    });
});

describe('guards', function () {
    test('voting on a closed poll is rejected (422)', function () {
        $poll = makePoll($this->agendaItem, $this->coordinator, ['closes_at' => now()->subHour()->toISOString()]);

        asUser($this->viewer)->putJson(($this->voteUrl)($poll), ['option_id' => 'a'])
            ->assertStatus(422);
    });

    test('voting on a non-poll comment is rejected (422)', function () {
        $this->actingAs($this->coordinator);
        $comment = $this->agendaItem->comment('<p>Just a comment</p>');

        asUser($this->viewer)->putJson(($this->voteUrl)($comment), ['option_id' => 'a'])
            ->assertStatus(422);
    });

    test('an unknown option is rejected (422)', function () {
        $poll = makePoll($this->agendaItem, $this->coordinator);

        asUser($this->viewer)->putJson(($this->voteUrl)($poll), ['option_id' => 'nope'])
            ->assertStatus(422)->assertJsonValidationErrors(['option_id']);
    });

    test('an outsider cannot vote (403)', function () {
        $poll = makePoll($this->agendaItem, $this->coordinator);

        asUser($this->outsider)->putJson(($this->voteUrl)($poll), ['option_id' => 'a'])
            ->assertStatus(403);
    });
});

test('a vote broadcasts a poll action', function () {
    Event::fake([CommentBroadcast::class]);
    $poll = makePoll($this->agendaItem, $this->coordinator);

    asUser($this->viewer)->putJson(($this->voteUrl)($poll), ['option_id' => 'a'])->assertOk();

    Event::assertDispatched(CommentBroadcast::class, fn ($e) => $e->action === 'poll'
        && $e->channelName === "comments.agendaItem.{$this->agendaItem->id}");
});
