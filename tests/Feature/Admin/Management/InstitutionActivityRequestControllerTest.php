<?php

use App\Actions\ActivityRequests\GetInstitutionActivityRequestHistory;
use App\Actions\ActivityRequests\SendInstitutionActivityRequests;
use App\Actions\RecordMeeting;
use App\Enums\ActivityRequestSkipReason;
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
use App\Services\InstitutionActivityStatusService;
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
            ->assertJsonPath('data.0.skip_reason', ActivityRequestSkipReason::NotAllowed->value)
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
    test('distinguishes missing representative duty types from missing current holders', function (bool $typed): void {
        $type = DutyType::query()->where('slug', 'studentu-atstovai')->firstOrFail();
        $duty = Duty::factory()->for($this->otherInstitution)->create();
        if ($typed) {
            $duty->types()->attach($type);
        } else {
            $this->rep->duties()->attach($duty, ['start_date' => today()->subMonth(), 'end_date' => null]);
        }

        asUser($this->coordinator)->postJson(route('api.v1.admin.activityRequests.preview'), [
            'campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->otherInstitution->id],
        ])->assertOk()
            ->assertJsonPath('data.0.recipients', [])
            ->assertJsonPath('data.0.skip_reason', $typed ? 'no_recipients' : 'no_representative_duties');

        Notification::assertNothingSent();
    })->with(['typed vacant duty' => true, 'untyped occupied duty' => false]);

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
            ->and($byInstitution[$this->otherInstitution->id]['skip_reason'])->toBe(ActivityRequestSkipReason::NoRepresentativeDuties->value)
            ->and($byInstitution[$covered->id]['skip_reason'])->toBe(ActivityRequestSkipReason::CheckInActive->value);
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
    $recipients = collect([...$response->json('data.0.recipients'), ...$response->json('data.0.excluded_recipients')])->keyBy('id');
    expect($recipients[$this->rep->id]['period_start'])->toBe(today()->subMonths(2)->toDateString())
        ->and($recipients[$newRep->id]['period_start'])->toBe(today()->subWeek()->toDateString())
        ->and($recipients[$newRep->id]['period_end'])->toBe(today()->toDateString())
        ->and($recipients[$newRep->id]['skip_reason'])->toBe('recent_activity');
});

test('without a current cadence the period starts at the assignment, and former representatives are excluded', function (): void {
    Cadence::factory()->create(['institution_id' => $this->institution->id, 'start_date' => today()->subYears(3), 'end_date' => today()->subYears(2)]);
    $former = activityRequestRep($this->institution);
    $former->duties()->updateExistingPivot($former->duties->first()->id, ['end_date' => today()->subDay()]);
    $plan = app(SendInstitutionActivityRequests::class)->plan([$this->institution], $this->coordinator);
    expect($plan[0]->recipients->pluck('id')->all())->toBe([$this->rep->id])
        ->and($plan[0]->recipientPeriods[$this->rep->id]['period_start'])->toBe(today()->subYear()->toDateString());
});

test('secretaries replace representatives and use the institution override rather than the global cadence', function (): void {
    Cadence::factory()->create(['start_date' => today()->subMonths(3), 'end_date' => today()->addMonths(3)]);
    $cadence = Cadence::factory()->create(['institution_id' => $this->institution->id, 'start_date' => today()->subMonths(2), 'end_date' => today()->addMonth()]);
    $secretary = User::factory()->create();
    InstitutionSecretary::create(['institution_id' => $this->institution->id, 'cadence_id' => $cadence->id, 'user_id' => $secretary->id]);
    $plan = app(SendInstitutionActivityRequests::class)->plan([$this->institution], $this->coordinator);
    expect($plan[0]->recipients->pluck('id')->all())->toBe([$secretary->id])
        ->and($plan[0]->recipientPeriods[$secretary->id]['period_start'])->toBe($cadence->start_date->toDateString());
});

test('activity confirmation asks from the day after the last meeting, record completion from the term start', function (): void {
    $this->freezeTime();
    app(RecordMeeting::class)->execute($this->institution, now()->subDays(40));
    $late = activityRequestRep($this->institution);
    $late->duties()->updateExistingPivot($late->duties->first()->id, ['start_date' => today()->subWeek()]);
    $sender = app(SendInstitutionActivityRequests::class);
    $activity = $sender->plan([$this->institution], $this->coordinator);
    $missing = $sender->plan([$this->institution], $this->coordinator, InstitutionActivityCampaign::MissingMeetings);
    expect($activity[0]->recipientPeriods[$this->rep->id]['period_start'])->toBe(today()->subDays(40)->addDay()->toDateString())
        ->and($activity[0]->recipientPeriods[$late->id]['period_start'])->toBe(today()->subWeek()->toDateString())
        ->and($missing[0]->recipients->pluck('id')->all())->toBe([$this->rep->id])
        ->and($missing[0]->recipientPeriods[$this->rep->id]['period_start'])->toBe(today()->subYear()->toDateString())
        ->and($missing[0]->excludedRecipients[0]['skip_reason'])->toBe('no_recorded_meetings');
});

test('activity confirmation asks from the day after the last report, and not at all while one runs', function (): void {
    $this->freezeTime();
    InstitutionCheckIn::factory()->for($this->institution)->for($this->rep)->for($this->tenant)
        ->create(['start_date' => today()->subMonths(3), 'end_date' => today()->subDays(40)]);
    $plan = app(SendInstitutionActivityRequests::class)->plan([$this->institution], $this->coordinator);
    expect($plan[0]->recipientPeriods[$this->rep->id]['period_start'])->toBe(today()->subDays(40)->addDay()->toDateString());

    InstitutionCheckIn::factory()->for($this->institution)->for($this->rep)->for($this->tenant)
        ->create(['start_date' => today()->subDays(6), 'end_date' => today()->addWeek()]);
    $plan = app(SendInstitutionActivityRequests::class)->plan([$this->institution->fresh()], $this->coordinator);
    expect($plan[0]->recipients)->toBeEmpty()
        ->and($plan[0]->skipReason)->toBe(ActivityRequestSkipReason::CheckInActive);
});

test('preferences explain delivery and actual immediate queued email count', function (): void {
    $this->rep->update(['notification_preferences' => ['types' => ['institution_activity' => ['email' => 'off']]]]);
    $plan = app(SendInstitutionActivityRequests::class)->plan([$this->institution], $this->coordinator);
    expect($plan[0]->recipientPeriods[$this->rep->id]['delivery_mode'])->toBe('off');
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
    $this->travel(40)->days();
    $newRep = activityRequestRep($this->institution);
    $newRep->duties()->updateExistingPivot($newRep->duties->first()->id, ['start_date' => today()]);
    $result = $sender->send([$this->institution], $this->coordinator, pairs: [$this->institution->id.':'.$newRep->id]);
    expect($result['queued_requests'])->toBe(1)->and(InstitutionActivityRequest::query()->where('recipient_id', $newRep->id)->count())->toBe(1);
});

test('automatic task retries reuse requests and ask one campaign, records first when they are incomplete', function (): void {
    $task = Task::factory()->create();
    app(RecordMeeting::class)->execute($this->institution, now()->subDays(40));
    $sender = app(SendInstitutionActivityRequests::class);
    $sender->forTasks([['task' => $task, 'institution' => $this->institution, 'recipients' => collect([$this->rep])]]);
    $sender->forTasks([['task' => $task, 'institution' => $this->institution, 'recipients' => collect([$this->rep])]]);
    expect(InstitutionActivityRequest::query()->where('task_id', $task->id)->get())->toHaveCount(1)
        ->and(InstitutionActivityRequest::query()->where('task_id', $task->id)->sole()->campaign_type)->toBe(InstitutionActivityCampaign::MissingMeetings);
    Notification::assertSentToTimes($this->rep, InstitutionActivityNotification::class, 1);
});

test('a coordinator may ask again within a month, while an automatic task waits', function (): void {
    $earlier = InstitutionActivityRequest::factory()->create([
        'institution_id' => $this->institution->id, 'recipient_id' => $this->rep->id, 'requested_by_id' => $this->coordinator->id,
        'period_start' => today()->subDays(60), 'period_end' => today()->subDays(20), 'expires_at' => today()->subDay(),
    ]);
    $earlier->forceFill(['created_at' => now()->subDays(20)])->save();
    $sender = app(SendInstitutionActivityRequests::class);

    $sender->forTasks([['task' => Task::factory()->create(), 'institution' => $this->institution, 'recipients' => collect([$this->rep])]]);
    expect(InstitutionActivityRequest::query()->count())->toBe(1);

    expect($sender->send([$this->institution], $this->coordinator)['queued_requests'])->toBe(1);
});

test('an automatic task asks whether a meeting happened when no records are missing', function (): void {
    $task = Task::factory()->create();
    app(SendInstitutionActivityRequests::class)->forTasks([['task' => $task, 'institution' => $this->institution, 'recipients' => collect([$this->rep])]]);
    expect(InstitutionActivityRequest::query()->where('task_id', $task->id)->sole()->campaign_type)->toBe(InstitutionActivityCampaign::ActivityConfirmation);
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
        $cadence = Cadence::factory()->create(['institution_id' => $this->institution->id, 'start_date' => today()->subMonths(2), 'end_date' => today()->addMonth()]);
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

test('manual activity preview and creation require the meeting interval to be exceeded', function (int $days, ?string $reason): void {
    $this->travelTo('2026-10-18 12:00:00');
    $this->institution->update(['meeting_periodicity_days' => 30]);
    app(RecordMeeting::class)->execute($this->institution, now()->subDays($days));
    $payload = ['campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id]];

    asUser($this->coordinator)->postJson(route('api.v1.admin.activityRequests.preview'), $payload)
        ->assertOk()->assertJsonPath('data.0.skip_reason', $reason);
    asUser($this->coordinator)->post(route('institutions.activity-requests.store'), $payload)->assertRedirect();

    expect(InstitutionActivityRequest::query()->count())->toBe($reason === null ? 1 : 0);
    if ($reason === null) {
        Notification::assertSentTo($this->rep, InstitutionActivityNotification::class);
    } else {
        Notification::assertNotSentTo($this->rep, InstitutionActivityNotification::class);
    }
})->with(['FF two days after meeting' => [2, 'recent_activity'], 'approaching' => [24, 'recent_activity'], 'exact interval' => [30, 'recent_activity'], 'overdue' => [31, null]]);

test('manual activity questions can be sent during check-in grace while automatic questions wait', function (): void {
    $this->travelTo('2026-11-20 12:00:00');
    $this->institution->update(['meeting_periodicity_days' => 30]);
    InstitutionCheckIn::factory()->for($this->institution)->create(['start_date' => '2026-09-01', 'end_date' => '2026-11-17']);
    $payload = ['campaign_type' => 'activity_confirmation', 'institution_ids' => [$this->institution->id]];

    asUser($this->coordinator)->postJson(route('api.v1.admin.activityRequests.preview'), $payload)
        ->assertOk()
        ->assertJsonPath('data.0.skip_reason', null)
        ->assertJsonPath('data.0.activity_status.status', 'recent_check_in')
        ->assertJsonPath('data.0.activity_status.last_completed_check_in_at', '2026-11-17')
        ->assertJsonPath('data.0.recipients.0.period_start', '2026-11-18');

    $sender = app(SendInstitutionActivityRequests::class);
    expect($sender->plan([$this->institution], null)[0]->skipReason)->toBe(ActivityRequestSkipReason::RecentActivity);
    asUser($this->coordinator)->post(route('institutions.activity-requests.store'), $payload)->assertRedirect();
    Notification::assertSentTo($this->rep, InstitutionActivityNotification::class);
    expect(InstitutionActivityRequest::query()->sole()->requested_by_id)->toBe($this->coordinator->id);
    expect($sender->send([$this->institution], $this->coordinator)['queued_requests'])->toBe(0);
});

test('activity requests exclude upcoming meetings and inactive institutions', function (string $condition, string $reason): void {
    app(RecordMeeting::class)->execute($this->institution, now()->subDays(40));
    if ($condition === 'upcoming') {
        app(RecordMeeting::class)->execute($this->institution, now()->addWeek());
    } else {
        $this->institution->update(['is_active' => false]);
    }
    $sender = app(SendInstitutionActivityRequests::class);
    $result = $sender->send([$this->institution], $this->coordinator);
    expect($result['plan'][0]->skipReason->value)->toBe($reason)
        ->and($result['queued_requests'])->toBe(0)
        ->and(InstitutionActivityRequest::query()->count())->toBe(0);
    $sender->forTasks([['task' => Task::factory()->create(), 'institution' => $this->institution->fresh(), 'recipients' => collect([$this->rep])]]);
    Notification::assertNotSentTo($this->rep, InstitutionActivityNotification::class);
})->with(['scheduled' => ['upcoming', 'upcoming_meeting'], 'inactive' => ['inactive', 'inactive_institution']]);

test('no-history requests wait for each recipients term interval and leave the dashboard without activity', function (): void {
    $this->travelTo('2026-12-02 12:00:00');
    $this->institution->update(['meeting_periodicity_days' => 30]);
    Cadence::factory()->create(['institution_id' => $this->institution->id, 'start_date' => '2026-11-01', 'end_date' => '2027-08-31']);
    $this->rep->duties()->updateExistingPivot($this->rep->duties->first()->id, ['start_date' => '2026-11-01']);
    $newRep = activityRequestRep($this->institution);
    $newRep->duties()->updateExistingPivot($newRep->duties->first()->id, ['start_date' => '2026-11-15']);

    $result = app(SendInstitutionActivityRequests::class)->send([$this->institution], $this->coordinator);
    expect($result['queued_requests'])->toBe(1)
        ->and($result['plan'][0]->excludedRecipients[0]['id'])->toBe($newRep->id)
        ->and($result['plan'][0]->excludedRecipients[0]['skip_reason'])->toBe('recent_activity')
        ->and(InstitutionActivityRequest::query()->sole()->period_start->toDateString())->toBe('2026-11-01')
        ->and(app(InstitutionActivityStatusService::class)->resolve($this->institution)->status->value)->toBe('no_activity');
    Notification::assertSentTo($this->rep, InstitutionActivityNotification::class);
    Notification::assertNotSentTo($newRep, InstitutionActivityNotification::class);
});

test('an open request blocks a next-day send until expiry', function (): void {
    $sender = app(SendInstitutionActivityRequests::class);
    $sender->send([$this->institution], $this->coordinator);
    $this->travel(1)->days();
    $result = $sender->send([$this->institution], $this->coordinator);
    expect($result['queued_requests'])->toBe(0)->and($result['plan'][0]->skipReason)->toBe(ActivityRequestSkipReason::AlreadyAsked);
    InstitutionActivityRequest::query()->sole()->update(['expires_at' => now()->subSecond()]);
    expect($sender->send([$this->institution], $this->coordinator)['queued_requests'])->toBe(1)
        ->and(InstitutionActivityRequest::query()->count())->toBe(2);
    Notification::assertSentToTimes($this->rep, InstitutionActivityNotification::class, 2);
});

test('future-only check-ins do not hide an uncovered overdue gap', function (): void {
    app(RecordMeeting::class)->execute($this->institution, now()->subDays(40));
    InstitutionCheckIn::factory()->for($this->institution)->create(['start_date' => today()->addWeek(), 'end_date' => today()->addWeeks(2)]);
    $result = app(SendInstitutionActivityRequests::class)->send([$this->institution], $this->coordinator);
    expect($result['queued_requests'])->toBe(1)
        ->and(InstitutionActivityRequest::query()->sole()->period_start->toDateString())->toBe(today()->subDays(39)->toDateString());
    Notification::assertSentTo($this->rep, InstitutionActivityNotification::class);
});

test('an active check-in blocks activity questions but allows completing older records', function (): void {
    app(RecordMeeting::class)->execute($this->institution, now()->subDays(2));
    InstitutionCheckIn::factory()->for($this->institution)->create(['start_date' => today()->subDay(), 'end_date' => today()->addWeek()]);
    $sender = app(SendInstitutionActivityRequests::class);
    expect($sender->send([$this->institution], $this->coordinator)['plan'][0]->skipReason)->toBe(ActivityRequestSkipReason::CheckInActive);
    expect($sender->send([$this->institution], $this->coordinator, campaign: InstitutionActivityCampaign::MissingMeetings)['queued_requests'])->toBe(1)
        ->and(InstitutionActivityRequest::query()->sole()->campaign_type)->toBe(InstitutionActivityCampaign::MissingMeetings);
    Notification::assertSentToTimes($this->rep, InstitutionActivityNotification::class, 1);
});

test('sending recalculates eligibility after a successful preview', function (): void {
    $sender = app(SendInstitutionActivityRequests::class);
    expect($sender->plan([$this->institution], $this->coordinator)[0]->recipients)->toHaveCount(1);
    app(RecordMeeting::class)->execute($this->institution, now()->addWeek());

    $result = $sender->send([$this->institution], $this->coordinator);
    expect($result['queued_requests'])->toBe(0)
        ->and($result['plan'][0]->skipReason)->toBe(ActivityRequestSkipReason::UpcomingMeeting)
        ->and(InstitutionActivityRequest::query()->count())->toBe(0);
    Notification::assertNotSentTo($this->rep, InstitutionActivityNotification::class);
});

test('no-history activity requests exclude vacation days from the recipients waiting interval', function (): void {
    $this->travelTo('2027-09-01 12:00:00');
    $this->institution->update(['meeting_periodicity_days' => 30]);
    Cadence::factory()->create(['institution_id' => $this->institution->id, 'start_date' => '2027-06-20', 'end_date' => '2028-06-19']);
    $this->rep->duties()->updateExistingPivot($this->rep->duties->first()->id, ['start_date' => '2027-06-20']);

    $result = app(SendInstitutionActivityRequests::class)->send([$this->institution], $this->coordinator);
    expect($result['queued_requests'])->toBe(0)
        ->and($result['plan'][0]->skipReason)->toBe(ActivityRequestSkipReason::RecentActivity)
        ->and(InstitutionActivityRequest::query()->count())->toBe(0);
    Notification::assertNotSentTo($this->rep, InstitutionActivityNotification::class);
});
