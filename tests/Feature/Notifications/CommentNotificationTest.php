<?php

use App\Enums\MeetingType;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\CommentPostedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * Attach a freshly-created user to the meeting's institution (via a duty), so
 * they belong to the comment audience that the notification pipeline targets.
 */
function audienceMember(Institution $institution): User
{
    $user = User::factory()->create(['notification_preferences' => []]);
    $user->duties()->attach(
        Duty::factory()->for($institution)->create(),
        ['start_date' => now()->subMonth(), 'end_date' => null],
    );

    return $user;
}

beforeEach(function () {
    Notification::fake();
    config(['queue.default' => 'sync']);

    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->institution = Institution::factory()->for($this->tenant)->create();

    $this->meeting = Meeting::create([
        'title' => 'Notification meeting',
        'start_time' => Carbon::now()->addDay()->format('Y-m-d H:i'),
        'type' => MeetingType::InPerson,
    ]);
    $this->meeting->institutions()->attach($this->institution->id);

    $this->agendaItem = AgendaItem::factory()->create(['meeting_id' => $this->meeting->id]);

    $this->author = audienceMember($this->institution);
    $this->rep = audienceMember($this->institution);
    $this->bystander = audienceMember($this->institution);
});

test('a mentioned user is notified', function () {
    $this->actingAs($this->author);
    $body = '<p>Hey <span data-id="'.$this->rep->id.'">@rep</span></p>';

    $this->agendaItem->comment($body);

    Notification::assertSentTo($this->rep, CommentPostedNotification::class);
});

test('an agenda-item root comment notifies the meeting audience (the fixed gap)', function () {
    $this->actingAs($this->author);

    $this->agendaItem->comment('<p>Anyone around?</p>');

    Notification::assertSentTo($this->rep, CommentPostedNotification::class);
    Notification::assertSentTo($this->bystander, CommentPostedNotification::class);
});

test('the comment author is never notified of their own comment', function () {
    $this->actingAs($this->author);

    $this->agendaItem->comment('<p>Anyone around?</p>');

    Notification::assertNotSentTo($this->author, CommentPostedNotification::class);
});

test('a reply notifies thread participants only, not the whole audience', function () {
    // The rep opens the thread (notifies the audience — reset afterwards so we
    // assert only on the reply's recipients).
    $this->actingAs($this->rep);
    $root = $this->agendaItem->comment('<p>Opening question</p>');

    Notification::fake();

    $this->actingAs($this->author);
    $this->agendaItem->comment('<p>My reply</p>', $root->id);

    // The root author (a thread participant) is notified…
    Notification::assertSentTo($this->rep, CommentPostedNotification::class);
    // …but an audience member who never joined the thread is not, and neither is the replier.
    Notification::assertNotSentTo($this->bystander, CommentPostedNotification::class);
    Notification::assertNotSentTo($this->author, CommentPostedNotification::class);
});
