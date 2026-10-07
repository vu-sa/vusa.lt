<?php

use App\Actions\ActivityRequests\GetInstitutionActivityRequestHistory;
use App\Actions\ActivityRequests\SendInstitutionActivityRequests;
use App\Actions\RecordMeeting;
use App\Enums\InstitutionActivityCampaign;
use App\Models\Cadence;
use App\Models\Duty;
use App\Models\DutyResponsibility;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionSecretary;
use App\Models\Pivots\AgendaItem;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use App\Services\AdminNavigation\AdminNavigationCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

pest()->use(RefreshDatabase::class);

/** A current student representative of $institution. */
function activityRequestRep(Institution $institution, ?User $user = null): User
{
    $repType = DutyType::query()->where('slug', 'studentu-atstovai')->first()
        ?? DutyType::factory()->create(['slug' => 'studentu-atstovai']);
    $duty = Duty::factory()->for($institution)->hasAttached($repType, [], 'types')->create();

    $user ??= User::factory()->create();
    $user->duties()->attach($duty, ['start_date' => now()->subYear(), 'end_date' => null]);

    return $user;
}

beforeEach(function (): void {
    Notification::fake();

    $this->tenant = Tenant::query()->firstOrFail();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->otherInstitution = Institution::factory()->for($this->tenant)->create();

    $this->coordinator = makeUser($this->tenant);
    $duty = $this->coordinator->duties()->firstOrFail();
    DutyResponsibility::factory()->for($duty)->forInstitution($this->institution)->create();
    DutyResponsibility::factory()->for($duty)->forInstitution($this->otherInstitution)->create();

    $this->rep = activityRequestRep($this->institution);
});

describe('unauthorized access', function (): void {
    test('a representative cannot ask about their own institution', function (): void {
        asUser($this->rep)
            ->post(route('institutions.activity-requests.store'), ['campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id]])
            ->assertForbidden();

        expect(InstitutionActivityRequest::query()->count())->toBe(0);
    });
});

describe('tenant isolation', function (): void {
    test('an institution the coordinator does not coordinate is skipped, and nobody there is named or emailed', function (): void {
        $foreign = Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail())->create();
        $foreignRep = activityRequestRep($foreign);

        asUser($this->coordinator)
            ->postJson(route('api.v1.admin.activityRequests.preview'), ['campaign_type' => 'activity_confirmation', 'institution_ids' => [$foreign->id]])
            ->assertOk()
            ->assertJsonPath('data.0.skip_reason', SendInstitutionActivityRequests::SKIP_NOT_ALLOWED)
            ->assertJsonPath('data.0.recipients', []);

        asUser($this->coordinator)
            ->post(route('institutions.activity-requests.store'), ['campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id, $foreign->id]])
            ->assertRedirect();

        Notification::assertNotSentTo($foreignRep, InstitutionActivityNotification::class);
        Notification::assertSentTo($this->rep, InstitutionActivityNotification::class);

        asUser($this->coordinator)
            ->getJson(route('api.v1.admin.activityRequests.candidates'))
            ->assertOk()
            ->assertJsonMissing(['id' => $foreign->id])
            ->assertJsonFragment(['id' => $this->institution->id]);
    });
});

describe('authorized access', function (): void {
    test('each representative gets one email covering all of their institutions, with the note', function (): void {
        activityRequestRep($this->otherInstitution, $this->rep);

        asUser($this->coordinator)
            ->post(route('institutions.activity-requests.store'), [
                'campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id, $this->otherInstitution->id],
                'note' => 'Prašau atsakyti iki penktadienio',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentToTimes($this->rep, InstitutionActivityNotification::class, 1);
        expect(InstitutionActivityRequest::query()->where('recipient_id', $this->rep->id)->get())
            ->toHaveCount(2)
            ->each(fn ($request) => $request
                ->requested_by_id->toBe($this->coordinator->id)
                ->note->toBe('Prašau atsakyti iki penktadienio'));
    });

    test('the note is limited to 500 characters', function (): void {
        asUser($this->coordinator)
            ->post(route('institutions.activity-requests.store'), [
                'campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id],
                'note' => str_repeat('a', 501),
            ])
            ->assertSessionHasErrors('note');
    });
});

test('on staging the email reaches only the coordinator who sent it, never the representative', function (): void {
    Notification::swap(new ChannelManager(app()));
    config(['app.env' => 'staging', 'mail.mailers.smtp' => ['transport' => 'array']]);
    app('mail.manager')->forgetMailers();

    // Through the action rather than HTTP: staging's basic-auth middleware is not what is under test.
    app(SendInstitutionActivityRequests::class)->send([$this->institution], $this->coordinator);

    $sent = app('mail.manager')->mailer('smtp')->getSymfonyTransport()->messages();

    expect($sent)->toHaveCount(1)
        ->and(collect($sent->first()->getOriginalMessage()->getTo())->map->getAddress()->all())->toBe([$this->coordinator->email])
        ->and($sent->first()->getOriginalMessage()->getSubject())->toStartWith('[Staging] ')
        ->and(app('mail.manager')->mailer('array')->getSymfonyTransport()->messages())->toBeEmpty();
});

describe('who is asked about what', function (): void {
    test('the preview names the recipients and why an institution is left out', function (): void {
        $covered = Institution::factory()->for($this->tenant)->create();
        DutyResponsibility::factory()->for($this->coordinator->duties()->firstOrFail())->forInstitution($covered)->create();
        activityRequestRep($covered);
        InstitutionCheckIn::factory()->for($covered)->create(['start_date' => today()->subYears(2), 'end_date' => today()->addWeek()]);

        $response = asUser($this->coordinator)->postJson(route('api.v1.admin.activityRequests.preview'), [
            'campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id, $this->otherInstitution->id, $covered->id],
        ])->assertOk();

        $byInstitution = collect($response->json('data'))->keyBy('institution.id');

        expect($byInstitution[$this->institution->id]['skip_reason'])->toBeNull()
            ->and($byInstitution[$this->institution->id]['recipients'][0]['name'])->toBe($this->rep->name)
            ->and($byInstitution[$this->otherInstitution->id]['skip_reason'])->toBe(SendInstitutionActivityRequests::SKIP_NO_RECIPIENTS)
            ->and($byInstitution[$covered->id]['skip_reason'])->toBe(SendInstitutionActivityRequests::SKIP_CHECK_IN_ACTIVE);
    });

    test('an institution with an open question is not asked again', function (): void {
        InstitutionActivityRequest::factory()->create(['institution_id' => $this->institution->id, 'recipient_id' => $this->rep->id, 'period_start' => today()->subYears(2)]);

        asUser($this->coordinator)->post(route('institutions.activity-requests.store'), ['campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id]]);

        Notification::assertNothingSent();
    });

    test('a coordinator who also represents the institution can include themselves', function (): void {
        activityRequestRep($this->institution, $this->coordinator);

        asUser($this->coordinator)->post(route('institutions.activity-requests.store'), ['campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id]]);

        Notification::assertSentTo($this->coordinator, InstitutionActivityNotification::class);
        Notification::assertSentTo($this->rep, InstitutionActivityNotification::class);
    });
});

test('campaign type is required and unsupported campaigns are rejected', function (): void {
    asUser($this->coordinator)->postJson(route('api.v1.admin.activityRequests.preview'), ['institution_ids' => [$this->institution->id]])->assertUnprocessable()->assertJsonValidationErrors('campaign_type');
    asUser($this->coordinator)->postJson(route('api.v1.admin.activityRequests.preview'), ['institution_ids' => [$this->institution->id], 'campaign_type' => 'unknown'])->assertUnprocessable()->assertJsonValidationErrors('campaign_type');
});

test('period starts at the current cadence clamped to each representative assignment', function (): void {
    $this->freezeTime();
    Cadence::factory()->create(['institution_id' => $this->institution->id, 'start_date' => today()->subMonths(2), 'end_date' => today()->addMonth()]);
    $newRep = activityRequestRep($this->institution);
    $newRep->duties()->updateExistingPivot($newRep->duties->first()->id, ['start_date' => today()->subWeek(), 'end_date' => today()]);
    $response = asUser($this->coordinator)->postJson(route('api.v1.admin.activityRequests.preview'), ['institution_ids' => [$this->institution->id], 'campaign_type' => 'activity_confirmation'])->assertOk();
    $recipients = collect($response->json('data.0.recipients'))->keyBy('id');
    expect($recipients[$this->rep->id]['period_start'])->toBe(today()->subMonths(2)->toDateString())
        ->and($recipients[$newRep->id]['period_start'])->toBe(today()->subWeek()->toDateString())
        ->and($recipients[$newRep->id]['period_end'])->toBe(today()->toDateString());
});

test('without a current cadence the period starts at the assignment, and former representatives are excluded', function (): void {
    Cadence::factory()->create(['institution_id' => $this->institution->id, 'start_date' => today()->subYears(3), 'end_date' => today()->subYears(2)]);
    $former = activityRequestRep($this->institution);
    $former->duties()->updateExistingPivot($former->duties->first()->id, ['end_date' => today()->subDay()]);
    $plan = app(SendInstitutionActivityRequests::class)->plan([$this->institution], $this->coordinator);
    expect($plan[0]['recipients']->pluck('id')->all())->toBe([$this->rep->id])
        ->and($plan[0]['recipient_periods'][$this->rep->id]['period_start'])->toBe(today()->subYear()->toDateString());
});

test('secretaries replace representatives and use the institution override rather than the global cadence', function (): void {
    Cadence::factory()->create(['start_date' => today()->subMonths(3), 'end_date' => today()->addMonths(3)]);
    $cadence = Cadence::factory()->create(['institution_id' => $this->institution->id, 'start_date' => today()->subMonth(), 'end_date' => today()->addMonth()]);
    $secretary = User::factory()->create();
    InstitutionSecretary::create(['institution_id' => $this->institution->id, 'cadence_id' => $cadence->id, 'user_id' => $secretary->id]);
    $plan = app(SendInstitutionActivityRequests::class)->plan([$this->institution], $this->coordinator);
    expect($plan[0]['recipients']->pluck('id')->all())->toBe([$secretary->id])
        ->and($plan[0]['recipient_periods'][$secretary->id]['period_start'])->toBe($cadence->start_date->toDateString());
});

test('existing meetings exclude only activity confirmation and within each recipient period', function (): void {
    app(RecordMeeting::class)->execute($this->institution, now()->subMonth());
    $late = activityRequestRep($this->institution);
    $late->duties()->updateExistingPivot($late->duties->first()->id, ['start_date' => today()->subWeek()]);
    $sender = app(SendInstitutionActivityRequests::class);
    $activity = $sender->plan([$this->institution], $this->coordinator);
    $missing = $sender->plan([$this->institution], $this->coordinator, InstitutionActivityCampaign::MissingMeetings);
    expect($activity[0]['recipients']->pluck('id')->all())->toBe([$late->id])
        ->and($activity[0]['excluded_recipients'][0]['skip_reason'])->toBe('meeting_recorded')
        ->and($missing[0]['recipients']->pluck('id')->all())->toBe([$this->rep->id])
        ->and($missing[0]['excluded_recipients'][0]['skip_reason'])->toBe('no_recorded_meetings');
});

test('preferences explain delivery and actual immediate queued email count', function (): void {
    $this->rep->update(['notification_preferences' => ['types' => ['institution_activity' => ['email' => 'off']]]]);
    $plan = app(SendInstitutionActivityRequests::class)->plan([$this->institution], $this->coordinator);
    expect($plan[0]['recipient_periods'][$this->rep->id]['delivery_mode'])->toBe('off');
    $result = app(SendInstitutionActivityRequests::class)->send([$this->institution], $this->coordinator);
    expect($result['queued_emails'])->toBe(0)->and($result['queued_recipients'])->toBe(1)->and($result['queued_requests'])->toBe(1);
    Notification::assertSentTo($this->rep, InstitutionActivityNotification::class);
});

test('duplicate suppression is scoped to recipient campaign and covered period', function (): void {
    $sender = app(SendInstitutionActivityRequests::class);
    $sender->send([$this->institution], $this->coordinator);
    $result = $sender->send([$this->institution], $this->coordinator);
    expect($result['queued_requests'])->toBe(0);
    app(RecordMeeting::class)->execute($this->institution, now()->subDay());
    $result = $sender->send([$this->institution], $this->coordinator, campaign: InstitutionActivityCampaign::MissingMeetings);
    expect($result['queued_requests'])->toBe(1);
    $newRep = activityRequestRep($this->institution);
    $newRep->duties()->updateExistingPivot($newRep->duties->first()->id, ['start_date' => today()]);
    $result = $sender->send([$this->institution], $this->coordinator);
    expect($result['queued_requests'])->toBe(1)->and(InstitutionActivityRequest::query()->where('recipient_id', $newRep->id)->count())->toBe(1);
});

test('automatic task retries reuse requests and choose campaigns using in-period meetings', function (): void {
    $task = Task::factory()->create();
    app(RecordMeeting::class)->execute($this->institution, now()->subWeek());
    $sender = app(SendInstitutionActivityRequests::class);
    $sender->forTask($task, $this->institution, collect([$this->rep]));
    $sender->forTask($task, $this->institution, collect([$this->rep]));
    expect(InstitutionActivityRequest::query()->where('task_id', $task->id)->get())->toHaveCount(1)
        ->and(InstitutionActivityRequest::query()->where('task_id', $task->id)->sole()->campaign_type)->toBe(InstitutionActivityCampaign::MissingMeetings);
    Notification::assertSentToTimes($this->rep, InstitutionActivityNotification::class, 1);
});

test('history is deferred and contains no signed answer links', function (): void {
    InstitutionActivityRequest::factory()->create(['institution_id' => $this->institution->id, 'recipient_id' => $this->rep->id]);
    asUser($this->coordinator)->get(route('institutions.show', $this->institution))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('canViewActivityRequests', true)->missing('activityRequests')
            ->loadDeferredProps('activityRequests', fn ($reload) => $reload->has('activityRequests.data', 1)));
    $history = app(GetInstitutionActivityRequestHistory::class)->execute($this->institution, $this->coordinator);
    expect(json_encode($history))->not->toContain('signature=', '/atsakymas/');
});

test('history exposes only the recipients own requests and no data to other readers', function (): void {
    InstitutionActivityRequest::factory()->create(['institution_id' => $this->institution->id, 'recipient_id' => $this->rep->id]);
    InstitutionActivityRequest::factory()->create(['institution_id' => $this->institution->id]);
    $history = app(GetInstitutionActivityRequestHistory::class);
    expect($history->execute($this->institution, $this->rep)['data'])->toHaveCount(1)
        ->and($history->execute($this->institution, User::factory()->create())['data'])->toBe([])
        ->and($history->execute($this->institution, $this->coordinator)['data'])->toHaveCount(2);
});

test('history groups recipients by sending batch and paginates twenty batches', function (): void {
    $requests = InstitutionActivityRequest::factory()->count(21)->create(['institution_id' => $this->institution->id, 'recipient_id' => $this->rep->id]);
    InstitutionActivityRequest::factory()->create(['institution_id' => $this->institution->id, 'recipient_id' => $this->rep->id, 'send_id' => $requests->last()->send_id]);
    $history = app(GetInstitutionActivityRequestHistory::class);
    $first = $history->execute($this->institution, $this->coordinator);
    expect($first['data'])->toHaveCount(20)->and($first['next_page'])->toBe(2)
        ->and($first['data'][0]['campaigns']['activity_confirmation'])->toHaveCount(2)
        ->and($history->execute($this->institution, $this->coordinator, 2)['data'])->toHaveCount(1);
});

test('the campaign entry is catalogued for authorized coordinators and super admins but not representatives', function (): void {
    $catalog = app(AdminNavigationCatalog::class);
    foreach ([$this->coordinator, makeAdminUser($this->tenant)] as $actor) {
        $actions = collect($catalog->for($actor)['workspaces'])->flatMap(fn ($workspace) => $workspace['createActions']);
        expect($actions->firstWhere('key', 'ask_activity')['target']['screen'])->toBe('activity.campaign');
    }
    $actions = collect($catalog->for($this->rep)['workspaces'])->flatMap(fn ($workspace) => $workspace['createActions']);
    expect($actions->contains('key', 'ask_activity'))->toBeFalse();
});

test('twenty-one institution previews batch recipients cadence and existence lookups', function (): void {
    $admin = makeAdminUser($this->tenant);
    $institutions = Institution::factory()->count(21)->for($this->tenant)->create();
    foreach ($institutions as $institution) {
        activityRequestRep($institution, $this->rep);
    }
    DB::enableQueryLog();
    DB::flushQueryLog();
    $response = asUser($admin)->postJson(route('api.v1.admin.activityRequests.preview'), ['institution_ids' => $institutions->pluck('id')->all(), 'campaign_type' => 'activity_confirmation']);
    $queries = count(DB::getQueryLog());
    DB::disableQueryLog();
    $response->assertOk()->assertJsonCount(21, 'data');
    expect($queries)->toBeLessThan(60);
});

test('the sole representative is still named when sending their own campaign', function (): void {
    activityRequestRep($this->otherInstitution, $this->coordinator);
    asUser($this->coordinator)->postJson(route('api.v1.admin.activityRequests.preview'), [
        'institution_ids' => [$this->otherInstitution->id], 'campaign_type' => 'activity_confirmation',
    ])->assertOk()->assertJsonPath('data.0.skip_reason', null)->assertJsonPath('data.0.recipients.0.id', $this->coordinator->id);
});

test('record completion targets missing agendas and excludes empty or completed periods', function (): void {
    activityRequestRep($this->otherInstitution);
    $emptyAgenda = app(RecordMeeting::class)->execute($this->institution, now()->subWeek());
    $incompleteAgenda = app(RecordMeeting::class)->execute($this->institution, now()->subDays(3));
    $item = AgendaItem::factory()->create(['meeting_id' => $incompleteAgenda->id, 'type' => 'voting']);
    $complete = app(RecordMeeting::class)->execute($this->institution, now()->subDay());
    AgendaItem::factory()->create(['meeting_id' => $complete->id, 'type' => 'informational']);
    $response = asUser($this->coordinator)->postJson(route('api.v1.admin.activityRequests.preview'), [
        'institution_ids' => [$this->institution->id, $this->otherInstitution->id], 'campaign_type' => 'missing_meetings',
    ])->assertOk();
    $entries = collect($response->json('data'))->keyBy('institution.id');
    expect(collect($entries[$this->institution->id]['recipients'][0]['incomplete_meetings'])->pluck('id')->sort()->values()->all())
        ->toBe(collect([$emptyAgenda->id, $incompleteAgenda->id])->sort()->values()->all())
        ->and($entries[$this->otherInstitution->id]['skip_reason'])->toBe('no_recorded_meetings');
    AgendaItem::factory()->create(['meeting_id' => $emptyAgenda->id, 'type' => 'informational']);
    $item->update(['type' => 'informational']);
    asUser($this->coordinator)->postJson(route('api.v1.admin.activityRequests.preview'), [
        'institution_ids' => [$this->institution->id], 'campaign_type' => 'missing_meetings',
    ])->assertOk()->assertJsonPath('data.0.skip_reason', 'records_complete');
});

describe('picking people', function (): void {
    test('a picked representative is asked even when the term has a named secretary, and nobody else is', function (): void {
        $cadence = Cadence::factory()->create(['institution_id' => $this->institution->id, 'start_date' => today()->subMonth(), 'end_date' => today()->addMonth()]);
        $secretary = User::factory()->create();
        InstitutionSecretary::create(['institution_id' => $this->institution->id, 'cadence_id' => $cadence->id, 'user_id' => $secretary->id]);
        $other = activityRequestRep($this->institution);

        asUser($this->coordinator)->post(route('institutions.activity-requests.store'), [
            'campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id],
            'recipients' => [['institution_id' => $this->institution->id, 'user_id' => $this->rep->id]],
        ])->assertRedirect()->assertSessionHas('success');

        expect(InstitutionActivityRequest::query()->pluck('recipient_id')->all())->toBe([$this->rep->id]);
        Notification::assertNotSentTo([$secretary, $other], InstitutionActivityNotification::class);
    });

    test('unticked rows are not asked, and a pair naming someone outside the institution asks nobody', function (): void {
        $outsider = User::factory()->create();
        activityRequestRep($this->otherInstitution, $this->rep);

        asUser($this->coordinator)->post(route('institutions.activity-requests.store'), [
            'campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id, $this->otherInstitution->id],
            'recipients' => [
                ['institution_id' => $this->otherInstitution->id, 'user_id' => $this->rep->id],
                ['institution_id' => $this->institution->id, 'user_id' => $outsider->id],
            ],
        ])->assertRedirect();

        expect(InstitutionActivityRequest::query()->get(['institution_id', 'recipient_id'])->map->only(['institution_id', 'recipient_id'])->all())
            ->toBe([['institution_id' => $this->otherInstitution->id, 'recipient_id' => $this->rep->id]]);
    });

    test('a picked pair must belong to an institution in the request', function (): void {
        asUser($this->coordinator)->post(route('institutions.activity-requests.store'), [
            'campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id],
            'recipients' => [['institution_id' => $this->otherInstitution->id, 'user_id' => $this->rep->id]],
        ])->assertSessionHasErrors('recipients.0.institution_id');
    });

    test('the people list names reachable representatives and secretaries with their institutions only', function (): void {
        activityRequestRep($this->otherInstitution, $this->rep);
        $foreignRep = activityRequestRep(Institution::factory()->for(Tenant::query()->whereKeyNot($this->tenant->id)->firstOrFail())->create());

        $people = asUser($this->coordinator)->getJson(route('api.v1.admin.activityRequests.people'))->assertOk()->json('data');

        expect(collect($people)->pluck('id')->all())->toContain($this->rep->id)->not->toContain($foreignRep->id)
            ->and(collect(collect($people)->firstWhere('id', $this->rep->id)['institutions'])->pluck('id')->sort()->values()->all())
            ->toBe(collect([$this->institution->id, $this->otherInstitution->id])->sort()->values()->all());
    });

    test('a representative cannot list people', function (): void {
        asUser($this->rep)->getJson(route('api.v1.admin.activityRequests.people'))->assertForbidden();
    });
});
