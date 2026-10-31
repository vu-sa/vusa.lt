<?php

namespace App\Services\MailPreview;

use App\Enums\InstitutionActivityCampaign;
use App\Enums\MeetingType;
use App\Enums\SupportRequestStatus;
use App\Enums\SupportRequestVisibility;
use App\Mail;
use App\Models;
use App\Notifications;
use Illuminate\Database\Eloquent\Collection;

class MailPreviewSamples
{
    public const string ID = '01J00000000000000000000000';

    public function user(): Models\User
    {
        return (new Models\User)->forceFill([
            'id' => self::ID, 'name' => 'Vardenis Pavardenis', 'email' => 'vardenis@example.org',
            'notification_preferences' => ['emails' => []],
        ])->setRelation('current_duties', new Collection);
    }

    public function institution(): Models\Institution
    {
        return (new Models\Institution)->forceFill([
            'id' => self::ID, 'name' => ['lt' => 'Pavyzdinė taryba', 'en' => 'Sample council'],
            'short_name' => ['lt' => 'Taryba', 'en' => 'Council'],
        ]);
    }

    public function notifications(): array
    {
        $user = $this->user();
        $institution = $this->institution();
        $task = (new Models\Task)->forceFill(['id' => self::ID, 'name' => 'Parengti ataskaitą', 'due_date' => '2026-10-15'])
            ->setRelation('taskable', $institution);
        $meeting = (new Models\Meeting)->forceFill(['id' => self::ID, 'start_time' => '2026-11-05 15:00:00', 'type' => MeetingType::InPerson])
            ->setRelation('institutions', new Collection([$institution]))->setRelation('agendaItems', new Collection);
        $duty = $this->duty($institution, $user);
        $request = (new Models\InstitutionActivityRequest)->forceFill([
            'id' => self::ID, 'period_start' => '2026-10-01', 'period_end' => '2026-10-31',
            'expires_at' => '2026-12-01', 'campaign_type' => InstitutionActivityCampaign::ActivityConfirmation,
            'locale' => app()->getLocale(), 'note' => 'Pavyzdinis klausimas apie tarybos veiklą.',
        ])->setRelation('institution', $institution)->setRelation('recipient', $user)
            ->setRelation('requestedBy', $user)->setRelation('task', $task);
        $support = (new Models\SupportRequest)->forceFill([
            'id' => self::ID, 'title' => 'Pavyzdinė pagalbos užklausa', 'visibility' => SupportRequestVisibility::Private,
        ])->setRelation('creator', $user)->setRelation('type', (new Models\SupportRequestType)->forceFill([
            'name' => ['lt' => 'Pagalba', 'en' => 'Help'],
        ]));
        $actor = ['modelClass' => 'User', 'name' => $user->name];
        $object = ['modelClass' => 'Meeting', 'name' => $institution->name, 'url' => 'https://example.org/meeting', 'id' => self::ID];
        $changes = $this->reservationChanges();

        return [
            new Notifications\TaskAssignedNotification($task, $user),
            new Notifications\TaskReminderNotification($task, 2),
            new Notifications\TaskOverdueNotification(collect([$task, clone $task])),
            new Notifications\TaskAutoCompletedNotification($task, 'Sprendimas priimtas', $user),
            new Notifications\MeetingCreatedNotification($meeting),
            new Notifications\MeetingAgendaCompletedNotification($meeting, $user),
            (new Notifications\MeetingCreatedNotification($meeting))->viaFollow(),
            new Notifications\MeetingReminderNotification($meeting, 2),
            new Notifications\InstitutionActivityNotification(new Collection([$request])),
            new Notifications\InstitutionActivityNotMineNotification($request),
            new Notifications\ApprovalRequestedNotification($changes[0]['resource']->reservation),
            new Notifications\ReservationStatusChangedNotification([$changes[0]], $user),
            new Notifications\AssignedToResourceNotification($actor, $object),
            new Notifications\ReservationDraftItemTakenNotification(['Projektorius'], '2026-11-05'),
            new Notifications\CommentPostedNotification('Pavyzdinis komentaras.', $object, $actor),
            new Notifications\CommentPostedNotification('Pavyzdinis paminėjimas.', $object, $actor, true),
            new Notifications\DutyExpiringNotification($duty, (new Models\Pivots\Dutiable)->forceFill(['end_date' => '2026-11-30']), 30),
            new Notifications\AccessChangedNotification([[
                'kind' => 'started', 'dutyName' => 'Atstovas', 'institutionName' => $institution->name,
                'date' => '2026-10-01', 'effectiveOn' => '2026-10-01', 'isExOfficio' => false,
            ]], '2026-10-01'),
            new Notifications\MemberRegistrationNotification(1, $user->name, $institution, $user->email, self::ID),
            new Notifications\StudentRepRegistrationNotification(self::ID, $user->name, $institution, self::ID),
            new Notifications\SupportRequestCreatedNotification($support),
            new Notifications\SupportRequestAssignedNotification($support, $user),
            new Notifications\SupportRequestInvolvedNotification($support, $user),
            new Notifications\SupportRequestStatusChangedNotification($support, SupportRequestStatus::New, SupportRequestStatus::InProgress, $user),
            new Notifications\WelcomeNotification,
        ];
    }

    public function reservationChanges(): array
    {
        $reservation = (new Models\Reservation)->forceFill(['id' => self::ID, 'name' => 'Pavyzdinė rezervacija']);

        return collect(['Projektorius', 'Ekranas'])->map(fn (string $name, int $index): array => [
            'resource' => (new Models\Pivots\ReservationResource)->forceFill([
                'id' => $index + 1, 'reservation_id' => self::ID,
                'start_time' => '2026-11-05 15:00:00', 'end_time' => '2026-11-06 15:00:00',
            ])->setRelation('reservation', $reservation)->setRelation('resource', (new Models\Resource)->forceFill(['name' => $name])),
            'oldState' => 'created', 'newState' => 'reserved',
        ])->all();
    }

    public function mailables(): array
    {
        $user = $this->user();
        $institution = $this->institution();

        return [
            'member-confirmation' => new Mail\ConfirmMemberRegistration($user->name, $institution, $this->duty($institution, $user)),
            'student-rep-confirmation' => new Mail\ConfirmStudentRepRegistration($user->name, $institution, $user),
            'feedback' => new Mail\FeedbackMail('Pavyzdinis atsiliepimas.', $user, 'https://example.org', 'Pavyzdinis tekstas'),
        ];
    }

    private function duty(Models\Institution $institution, Models\User $user): Models\Duty
    {
        return (new Models\Duty)->forceFill([
            'id' => self::ID, 'name' => ['lt' => 'Atstovas', 'en' => 'Representative'], 'email' => 'atstovas@example.org',
        ])->setRelation('institution', $institution)->setRelation('current_users', new Collection([$user]));
    }

    public function digestItems(string $scenario): array
    {
        $notifications = collect($this->notifications())->keyBy(fn ($notification) => $notification->type()->value);
        $item = fn (string $type): array => $notifications[$type]->toDigestItem($this->user());
        $meeting = $item('meeting_created');

        return match ($scenario) {
            'single' => ['meeting' => [$meeting]],
            'grouped' => ['meeting' => [$meeting, $meeting, $meeting, $item('meeting_agenda_completed')]],
            'overflow' => ['meeting' => array_fill(0, 8, $meeting)],
            'overdue' => ['task' => [$item('task_overdue')]],
            'legacy' => ['meeting' => [array_diff_key($meeting, array_flip(['type', 'digestLine', 'digestGroupTitle']))]],
        };
    }
}
