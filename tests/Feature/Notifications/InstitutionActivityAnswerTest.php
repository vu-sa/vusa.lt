<?php

use App\Actions\ActivityRequests\AnswerInstitutionActivityRequest;
use App\Actions\ActivityRequests\GetInstitutionActivityRequestHistory;
use App\Actions\RecordMeeting;
use App\Enums\InstitutionActivityAnswer;
use App\Mail\NotificationDigest;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\Meeting;
use App\Models\NotificationDigestQueue;
use App\Models\Pivots\AgendaItem;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InstitutionActivityNotification;
use App\Notifications\InstitutionActivityNotMineNotification;
use App\Support\MorphMap;
use App\Tasks\Enums\ActionType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->institution = Institution::factory()->for(Tenant::query()->firstOrFail())->create();
    $this->recipient = User::factory()->create();
    $this->activityRequest = InstitutionActivityRequest::factory()->create([
        'institution_id' => $this->institution->id,
        'recipient_id' => $this->recipient->id,
        'period_start' => today()->subMonth(),
    ]);
});

function activityNotification(InstitutionActivityRequest ...$requests): InstitutionActivityNotification
{
    return new InstitutionActivityNotification(new Collection($requests));
}

describe('the email asks and links to a confirmation page (U21)', function (): void {
    test('one institution gets yes and no buttons that open its answer page with the answer chosen', function (): void {
        $notification = activityNotification($this->activityRequest);

        expect($notification->title($this->recipient))->toBe('VU SA · '.__('activity_requests.campaigns.activity_confirmation'))
            ->and($notification->primaryAction()['url'])->toContain('/atsakymas/'.$this->activityRequest->id, 'answer=met', 'signature=')
            ->and($notification->secondaryAction()['url'])->toContain('answer=not_met', 'signature=');
    });

    test('several institutions arrive in one email with their own answer links', function (): void {
        $second = InstitutionActivityRequest::factory()->create([
            'send_id' => $this->activityRequest->send_id,
            'recipient_id' => $this->recipient->id,
        ]);

        $notification = activityNotification($this->activityRequest, $second);
        $mail = $notification->toMail($this->recipient);
        $text = (string) new Markdown(view(), config('mail.markdown'))->renderText($mail->markdown, $mail->data());

        expect($notification->title($this->recipient))->toBe('VU SA · '.__('activity_requests.campaigns.activity_confirmation'))
            ->and($notification->secondaryAction())->toBeNull()
            ->and($text)->toContain($this->institution->name, $second->institution->name, '/atsakymas/'.$second->id);
    });

    test('the body explains the activity question and that sign-in is unnecessary', function (): void {
        $this->activityRequest->update(['task_id' => Task::factory()->create(['metadata' => ['effective_days_since_activity' => 41]])->id]);

        expect(activityNotification($this->activityRequest->fresh())->body($this->recipient))->toBe(__('activity_requests.email.activity_confirmation'));
    });
});

describe('opening a link', function (): void {
    test('shows the question but records nothing, so mail scanners cannot answer', function (): void {
        $this->get($this->activityRequest->answerUrl(InstitutionActivityAnswer::NotMet))
            ->assertOk()
            ->assertSee($this->institution->name);

        expect($this->activityRequest->fresh()->answered_at)->toBeNull()
            ->and(InstitutionCheckIn::query()->count())->toBe(0);
    });

    test('refuses a link whose signature was tampered with or has expired', function (): void {
        $this->get(route('activityAnswers.show', $this->activityRequest))->assertForbidden();

        $this->travel(InstitutionActivityRequest::LINK_LIFETIME_DAYS + 1)->days();

        $this->get($this->activityRequest->answerUrl())->assertForbidden();
    });

    test('escapes the coordinator note', function (): void {
        $this->activityRequest->update([
            'requested_by_id' => User::factory()->create()->id,
            'note' => '<script>alert("x")</script>',
        ]);

        $this->get($this->activityRequest->answerUrl())
            ->assertOk()
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee('&lt;script&gt;', false);
    });
});

describe('answering', function (): void {
    test('no files a check-in as the recipient from the period start to today and closes the reminder task', function (): void {
        $task = Task::factory()->create([
            'taskable_type' => MorphMap::alias(Institution::class),
            'taskable_id' => $this->institution->id,
            'action_type' => ActionType::PeriodicityGap,
        ]);

        $this->post($this->activityRequest->submitUrl(), ['answer' => 'not_met'])
            ->assertRedirect($this->activityRequest->answerUrl());

        $checkIn = InstitutionCheckIn::query()->sole();

        expect($checkIn)
            ->user_id->toBe($this->recipient->id)
            ->institution_id->toBe($this->institution->id)
            ->and($checkIn->start_date->toDateString())->toBe(today()->subMonth()->toDateString())
            ->and($checkIn->end_date->toDateString())->toBe(today()->toDateString())
            ->and($this->activityRequest->fresh())
            ->answer->toBe(InstitutionActivityAnswer::NotMet)
            ->check_in_id->toBe($checkIn->id)
            ->and($task->fresh()->completed_at)->not->toBeNull();
    });

    test('yes records a meeting of the institution at the given date and time', function (): void {
        $date = today()->subWeek();

        $this->post($this->activityRequest->submitUrl(), [
            'answer' => 'met',
            'meeting_date' => $date->toDateString(),
            'meeting_type' => 'in-person',
            'meeting_time' => '15:30',
        ])->assertRedirect();

        $meeting = Meeting::query()->sole();

        expect($meeting->start_time->format('Y-m-d H:i'))->toBe($date->format('Y-m-d').' 15:30')
            ->and($meeting->institutions->pluck('id')->all())->toBe([$this->institution->id])
            ->and($this->activityRequest->fresh()->meeting_id)->toBe($meeting->id);
    });

    test('a decision by email needs no time and takes the 23:59 deadline marker', function (): void {
        $this->post($this->activityRequest->submitUrl(), [
            'answer' => 'met',
            'meeting_date' => today()->subDay()->toDateString(),
            'meeting_type' => 'email',
        ])->assertSessionHasNoErrors();

        expect(Meeting::query()->sole()->start_time->format('H:i'))->toBe('23:59');
    });

    test('a meeting must fall inside the period asked about and have a time', function (): void {
        $this->post($this->activityRequest->submitUrl(), [
            'answer' => 'met',
            'meeting_date' => today()->subMonths(2)->toDateString(),
            'meeting_type' => 'in-person',
        ])->assertSessionHasErrors(['meetings.0.date', 'meetings.0.time']);

        expect(Meeting::query()->count())->toBe(0);
    });

    test('the answer cannot be posted without the signed form address', function (): void {
        $this->post(route('activityAnswers.store', $this->activityRequest), ['answer' => 'not_met'])->assertForbidden();

        expect(InstitutionCheckIn::query()->count())->toBe(0);
    });

    test('recording activity closes every other open question about the institution, so no link records it twice', function (): void {
        $colleague = InstitutionActivityRequest::factory()->create([
            'send_id' => $this->activityRequest->send_id,
            'institution_id' => $this->institution->id,
        ]);

        $this->post($this->activityRequest->submitUrl(), ['answer' => 'not_met']);
        $this->post($colleague->submitUrl(), ['answer' => 'not_met'])->assertRedirect($colleague->answerUrl());

        expect(InstitutionCheckIn::query()->count())->toBe(1)
            ->and($colleague->fresh()->resolved_at)->not->toBeNull();
    });

    test('a meeting recorded in Mano VU SA also closes the open questions', function (): void {
        app(RecordMeeting::class)->execute($this->institution, now()->subDay());

        expect($this->activityRequest->fresh()->isOpen())->toBeFalse();
    });

    test('"not mine" tells whoever asked and leaves the question open for the others', function (): void {
        Notification::fake();
        $coordinator = User::factory()->create();
        $this->activityRequest->update(['requested_by_id' => $coordinator->id]);
        $colleague = InstitutionActivityRequest::factory()->create(['institution_id' => $this->institution->id]);

        $this->post($this->activityRequest->submitUrl(), ['answer' => 'not_mine'])->assertRedirect();

        Notification::assertSentTo($coordinator, InstitutionActivityNotMineNotification::class);
        expect($this->activityRequest->fresh()->answer)->toBe(InstitutionActivityAnswer::NotMine)
            ->and($colleague->fresh()->isOpen())->toBeTrue()
            ->and(InstitutionCheckIn::query()->count())->toBe(0);
    });
});

test('new requests keep the fixed end date across delayed replies', function (): void {
    $this->activityRequest->update(['period_end' => today()->subWeek(), 'locale' => 'en']);
    $end = $this->activityRequest->period_end->toDateString();
    $this->get($this->activityRequest->answerUrl())->assertOk()->assertSee($end)->assertSee('Period:');
    $this->post($this->activityRequest->submitUrl(), ['answer' => 'not_met'])->assertSessionHasNoErrors();
    expect(InstitutionCheckIn::query()->sole()->end_date->toDateString())->toBe($end);
});

test('multiple meetings save together and a stale repeated reply creates no duplicates', function (): void {
    $this->activityRequest->update(['campaign_type' => 'missing_meetings', 'period_end' => today()]);
    $payload = ['answer' => 'met', 'meetings' => [
        ['date' => today()->subWeek()->toDateString(), 'type' => 'remote', 'time' => '09:30'],
        ['date' => today()->subDay()->toDateString(), 'type' => 'email'],
    ]];
    $stale = $this->activityRequest->fresh();
    $this->post($this->activityRequest->submitUrl(), $payload)->assertSessionHasNoErrors();
    app(AnswerInstitutionActivityRequest::class)->execute($stale, InstitutionActivityAnswer::Met, meetings: $payload['meetings']);
    expect(Meeting::query()->count())->toBe(2)->and($this->activityRequest->fresh()->meetings)->toHaveCount(2)
        ->and($this->activityRequest->fresh()->answer)->toBe(InstitutionActivityAnswer::Met);
});

test('validation preserves all meeting rows selected answer and field errors', function (): void {
    $rows = [['date' => today()->subDay()->toDateString(), 'type' => 'remote', 'time' => ''], ['date' => today()->toDateString(), 'type' => 'email']];
    $this->from($this->activityRequest->answerUrl())->post($this->activityRequest->submitUrl(), ['answer' => 'met', 'meetings' => $rows])
        ->assertSessionHasErrors('meetings.0.time')->assertSessionHasInput('meetings', $rows)->assertSessionHasInput('answer', 'met');
    expect(Meeting::query()->count())->toBe(0)->and($this->activityRequest->fresh()->answered_at)->toBeNull();
});

test('completeness records only the uncovered gap after the latest meeting', function (): void {
    $this->activityRequest->update(['campaign_type' => 'missing_meetings', 'period_end' => today()]);
    $meeting = app(RecordMeeting::class)->execute($this->institution, now()->subWeek());
    AgendaItem::factory()->create(['meeting_id' => $meeting->id, 'type' => 'informational']);
    expect($this->activityRequest->fresh()->isOpen())->toBeTrue();
    $this->post($this->activityRequest->submitUrl(), ['answer' => 'complete'])->assertSessionHasNoErrors();
    expect(InstitutionCheckIn::query()->sole()->start_date->toDateString())->toBe(today()->subWeek()->addDay()->toDateString())
        ->and($this->activityRequest->fresh()->answer)->toBe(InstitutionActivityAnswer::Complete);
});

test('record completion replies name incomplete meetings and cannot confirm unfinished agendas', function (): void {
    $this->activityRequest->update(['campaign_type' => 'missing_meetings', 'period_end' => today()]);
    $meeting = app(RecordMeeting::class)->execute($this->institution, now()->subWeek());
    $notification = activityNotification($this->activityRequest);
    $mail = $notification->toMail($this->recipient);
    $this->get($this->activityRequest->answerUrl())->assertOk()
        ->assertSee(__('activity_requests.incomplete_meetings'))->assertSee(route('meetings.show', $meeting))
        ->assertSee(__('activity_requests.add_missing_meeting'))
        ->assertDontSee(__('notifications.action_register_meeting'))->assertDontSee(__('notifications.action_report_activity'));
    expect($notification->primaryAction()['label'])->toBe(__('activity_requests.edit_records'))
        ->and((string) $mail->render())->toContain(__('activity_requests.edit_records'), route('meetings.show', $meeting))
        ->not->toContain(__('notifications.action_register_meeting'), __('notifications.action_report_activity'));
    $this->post($this->activityRequest->submitUrl(), ['answer' => 'complete'])->assertSessionHasErrors('answer');
    expect($this->activityRequest->fresh()->isOpen())->toBeTrue()->and(InstitutionCheckIn::query()->count())->toBe(0);
});

test('digests preserve each institution signed actions and incomplete meeting links in HTML and text', function (): void {
    $second = InstitutionActivityRequest::factory()->create(['recipient_id' => $this->recipient->id, 'period_end' => today()]);
    foreach (['activity_confirmation', 'missing_meetings'] as $campaign) {
        $this->activityRequest->update(['campaign_type' => $campaign, 'period_end' => today()]);
        $second->update(['campaign_type' => $campaign]);
        $meetingLinks = [];
        if ($campaign === 'missing_meetings') {
            foreach ([$this->activityRequest, $second] as $request) {
                $meeting = app(RecordMeeting::class)->execute($request->institution, now()->subWeek());
                $meetingLinks[] = route('meetings.show', $meeting);
            }
        }
        $notification = activityNotification($this->activityRequest, $second);
        $item = $notification->toDigestItem($this->recipient);
        $digest = new NotificationDigest($this->recipient, ['meeting' => [$item]]);
        $text = (string) new Markdown(view(), config('mail.markdown'))->renderText($digest->content()->markdown, $digest->content()->with);
        foreach ([$digest->render(), $text] as $content) {
            foreach ($meetingLinks as $link) {
                expect((string) $content)->toContain($link);
            }
            foreach ([$this->activityRequest, $second] as $request) {
                expect(html_entity_decode((string) $content))->toContain($request->answerUrl(InstitutionActivityAnswer::NotMine), $request->answerUrl($campaign === 'missing_meetings' ? InstitutionActivityAnswer::Complete : InstitutionActivityAnswer::NotMet));
            }
            expect((string) $content)->toContain($campaign === 'missing_meetings' ? __('activity_requests.edit_records') : __('notifications.action_register_meeting'));
        }
    }
});

test('no cannot contradict a meeting added without the request resolution event', function (): void {
    $meeting = Meeting::factory()->create(['start_time' => now()->subDay()]);
    $meeting->institutions()->attach($this->institution);
    $this->post($this->activityRequest->submitUrl(), ['answer' => 'not_met'])->assertSessionHasErrors('answer');
    expect(InstitutionCheckIn::query()->count())->toBe(0)->and($this->activityRequest->fresh()->answered_at)->toBeNull();
});

test('confirming a period never creates overlapping check-ins', function (): void {
    InstitutionCheckIn::factory()->create(['institution_id' => $this->institution->id, 'start_date' => today()->subWeeks(2), 'end_date' => today()->subWeek()]);
    $this->post($this->activityRequest->submitUrl(), ['answer' => 'not_met'])->assertSessionHasNoErrors();
    $periods = InstitutionCheckIn::query()->orderBy('start_date')->get();
    expect($periods)->toHaveCount(3)->and($periods[0]->end_date->lt($periods[1]->start_date))->toBeTrue()
        ->and($periods[1]->end_date->lt($periods[2]->start_date))->toBeTrue();
});

test('completeness resolves only colleagues whose same-campaign periods are covered', function (): void {
    $this->activityRequest->update(['campaign_type' => 'missing_meetings', 'period_end' => today()]);
    $covered = InstitutionActivityRequest::factory()->create(['institution_id' => $this->institution->id, 'campaign_type' => 'missing_meetings', 'period_start' => today()->subWeek(), 'period_end' => today()]);
    $wider = InstitutionActivityRequest::factory()->create(['institution_id' => $this->institution->id, 'campaign_type' => 'missing_meetings', 'period_start' => today()->subYear(), 'period_end' => today()]);
    $this->post($this->activityRequest->submitUrl(), ['answer' => 'complete'])->assertSessionHasNoErrors();
    expect($covered->fresh()->resolved_by_request_id)->toBe($this->activityRequest->id)->and($wider->fresh()->isOpen())->toBeTrue();
});

test('an unrelated historical meeting cannot resolve a current request or periodicity task', function (): void {
    $task = Task::factory()->create(['taskable_type' => MorphMap::alias(Institution::class), 'taskable_id' => $this->institution->id, 'action_type' => ActionType::PeriodicityGap]);
    app(RecordMeeting::class)->execute($this->institution, now()->subYears(2));
    expect($this->activityRequest->fresh()->isOpen())->toBeTrue()->and($task->fresh()->completed_at)->toBeNull();
});

test('repeated not-mine replies notify the coordinator only once', function (): void {
    Notification::fake();
    $coordinator = User::factory()->create();
    $this->activityRequest->update(['requested_by_id' => $coordinator->id]);
    $stale = $this->activityRequest->fresh();
    $answer = app(AnswerInstitutionActivityRequest::class);
    $answer->execute($this->activityRequest, InstitutionActivityAnswer::NotMine);
    $answer->execute($stale, InstitutionActivityAnswer::NotMine);
    Notification::assertSentToTimes($coordinator, InstitutionActivityNotMineNotification::class, 1);
});

test('queued notifications suppress obsolete questions and deleted institutions', function (): void {
    $notification = activityNotification($this->activityRequest);
    $this->activityRequest->update(['resolved_at' => now()]);
    expect($notification->shouldSend($this->recipient, 'mail'))->toBeFalse();
    $this->institution->delete();
    $this->get($this->activityRequest->answerUrl())->assertOk()->assertSee(__('activity_requests.unavailable_title'))->assertDontSee($this->institution->name);
});

test('digest delivery removes answered requests before sending', function (): void {
    $this->travelTo(now()->setTime(12, 0));
    Mail::fake();
    $this->recipient->update(['notification_preferences' => ['types' => ['institution_activity' => ['email' => 'digest']]]]);
    NotificationDigestQueue::create([
        'user_id' => $this->recipient->id, 'notification_id' => (string) Str::uuid(),
        'notification_class' => InstitutionActivityNotification::class, 'category' => 'task',
        'data' => activityNotification($this->activityRequest)->toDigestItem($this->recipient),
    ])->forceFill(['created_at' => now()->subDay()])->save();
    $this->activityRequest->update(['answered_at' => now(), 'answer' => 'not_mine']);
    $this->artisan('notifications:send-digests')->assertSuccessful();
    Mail::assertNothingSent();
    expect(NotificationDigestQueue::query()->count())->toBe(0);
});

test('queued immediate mail rechecks current notification preferences', function (): void {
    $notification = activityNotification($this->activityRequest);
    $this->recipient->update(['notification_preferences' => ['types' => ['institution_activity' => ['email' => 'off']]]]);
    expect($notification->shouldSend($this->recipient, 'mail'))->toBeFalse()
        ->and($notification->shouldSend($this->recipient, 'database'))->toBeTrue();
});

test('history handles soft-deleted result meetings without exposing broken links', function (): void {
    $meeting = app(RecordMeeting::class)->execute($this->institution, now()->subDay());
    $meeting->delete();
    $history = app(GetInstitutionActivityRequestHistory::class)->execute($this->institution, $this->recipient);
    expect($history['data'][0]['campaigns']['activity_confirmation'][0]['meetings'])->toBe([]);
});

describe('several representatives of one institution', function (): void {
    beforeEach(function (): void {
        $this->colleague = InstitutionActivityRequest::factory()->create([
            'send_id' => $this->activityRequest->send_id,
            'institution_id' => $this->institution->id,
            'period_start' => today()->subMonth(),
        ]);
    });

    test('a colleague sees who recorded the meeting and when it was', function (): void {
        $date = today()->subWeek();
        $this->post($this->activityRequest->submitUrl(), ['answer' => 'met', 'meetings' => [['date' => $date->toDateString(), 'type' => 'in-person', 'time' => '15:30']]]);

        expect($this->colleague->fresh()->resolved_by_request_id)->toBe($this->activityRequest->id);
        $this->get($this->colleague->answerUrl())->assertOk()
            ->assertSee($this->recipient->name)
            ->assertSee(__('activity_requests.history.meeting', ['date' => $date->toDateString()]))
            ->assertSee(__('activity_requests.another_meeting'))
            ->assertDontSee(__('activity_requests.confirm_not_met'));
        expect(app(GetInstitutionActivityRequestHistory::class)->execute($this->institution, $this->colleague->recipient)['data'][0]['campaigns']['activity_confirmation'][0]['resolved_by'])
            ->toBe($this->recipient->name);
    });

    test('a colleague can still add a meeting nobody recorded, but cannot deny one', function (): void {
        $first = ['date' => today()->subWeek()->toDateString(), 'type' => 'in-person', 'time' => '15:30'];
        $this->post($this->activityRequest->submitUrl(), ['answer' => 'met', 'meetings' => [$first]]);

        $this->post($this->colleague->submitUrl(), ['answer' => 'not_met'])->assertRedirect($this->colleague->answerUrl());
        expect(InstitutionCheckIn::query()->count())->toBe(0);

        $this->post($this->colleague->submitUrl(), ['answer' => 'met', 'meetings' => [$first, ['date' => today()->subDays(2)->toDateString(), 'type' => 'email']]])
            ->assertSessionHasNoErrors();

        expect(Meeting::query()->count())->toBe(2)
            ->and($this->colleague->fresh())->answer->toBe(InstitutionActivityAnswer::Met)->resolved_at->toBeNull();
    });

    test('a colleague whose term started earlier is asked only about the part nobody confirmed', function (): void {
        $this->colleague->update(['period_start' => today()->subMonths(2)]);

        $this->post($this->activityRequest->submitUrl(), ['answer' => 'not_met']);

        expect($this->colleague->fresh()->isOpen())->toBeTrue();
        $this->get($this->colleague->answerUrl())->assertOk()->assertSee(__('activity_requests.uncovered', [
            'periods' => today()->subMonths(2)->toDateString().' – '.today()->subMonth()->subDay()->toDateString(),
        ]));
    });
});
