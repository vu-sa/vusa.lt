<?php

namespace App\Services\MailPreview;

use App\Enums\AgendaItemType;
use App\Enums\InstitutionActivityCampaign;
use App\Enums\MeetingType;
use App\Enums\NotificationType;
use App\Enums\SupportRequestStatus;
use App\Enums\SupportRequestVisibility;
use App\Mail;
use App\Models;
use App\Notifications;
use Closure;
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

    public function institution(string $lt = 'Pavyzdinė taryba', string $en = 'Sample council'): Models\Institution
    {
        return (new Models\Institution)->forceFill([
            'id' => self::ID, 'name' => ['lt' => $lt, 'en' => $en],
            'short_name' => ['lt' => $lt, 'en' => $en],
        ])->setRelation('types', new Collection);
    }

    /** Sample text shown in the previewed locale, so English previews read as English. */
    private function text(string $lt, string $en): string
    {
        return app()->getLocale() === 'en' ? $en : $lt;
    }

    private function task(string $lt, string $en, Models\Institution $institution): Models\Task
    {
        return (new Models\Task)->forceFill(['id' => self::ID, 'name' => $this->text($lt, $en), 'due_date' => '2026-10-15'])
            ->setRelation('taskable', $institution);
    }

    private function meeting(Models\Institution $institution, string $startTime = '2026-11-05 15:00:00'): Models\Meeting
    {
        $agendaItems = collect(range(1, 3))->map(fn (): Models\Pivots\AgendaItem => (new Models\Pivots\AgendaItem)->forceFill(['type' => AgendaItemType::Voting]));

        return (new Models\Meeting)->forceFill(['id' => self::ID, 'start_time' => $startTime, 'type' => MeetingType::InPerson])
            ->setRelation('institutions', new Collection([$institution]))->setRelation('agendaItems', new Collection($agendaItems));
    }

    /** @return array<string, Closure(): Notifications\BaseNotification> */
    public function notificationFactories(): array
    {
        $user = fn () => $this->user();
        $institution = fn () => $this->institution();
        $task = fn () => $this->task('Parengti ataskaitą', 'Prepare the report', $institution());
        $meeting = fn () => $this->meeting($institution());
        $duty = fn () => $this->duty($institution(), $user());
        $request = fn () => (new Models\InstitutionActivityRequest)->forceFill([
            'id' => self::ID, 'period_start' => '2026-10-01', 'period_end' => '2026-10-31',
            'expires_at' => '2026-12-01', 'campaign_type' => InstitutionActivityCampaign::ActivityConfirmation,
            'locale' => app()->getLocale(), 'note' => $this->text('Pavyzdinis klausimas apie tarybos veiklą.', 'A sample question about the council.'),
        ])->setRelation('institution', $institution())->setRelation('recipient', $user())
            ->setRelation('requestedBy', $user())->setRelation('task', $task());
        $support = fn () => (new Models\SupportRequest)->forceFill([
            'id' => self::ID, 'title' => $this->text('Pavyzdinė pagalbos užklausa', 'Sample support request'), 'visibility' => SupportRequestVisibility::Private,
        ])->setRelation('creator', $user())->setRelation('type', (new Models\SupportRequestType)->forceFill([
            'name' => ['lt' => 'Pagalba', 'en' => 'Help'],
        ]));
        $actor = fn () => ['modelClass' => 'User', 'name' => $user()->name];
        $object = fn () => ['modelClass' => 'Meeting', 'name' => $institution()->name, 'url' => 'https://example.org/meeting', 'id' => self::ID];

        return [
            NotificationType::TaskAssigned->value => fn () => new Notifications\TaskAssignedNotification($task(), $user()),
            NotificationType::TaskReminder->value => fn () => new Notifications\TaskReminderNotification($task(), 2),
            NotificationType::TaskOverdue->value => fn () => new Notifications\TaskOverdueNotification(collect([$task(), $this->task('Atnaujinti kontaktus', 'Update the contacts', $institution())])),
            NotificationType::TaskAutoCompleted->value => fn () => new Notifications\TaskAutoCompletedNotification($task(), $this->text('Sprendimas priimtas', 'Decision made'), $user()),
            NotificationType::MeetingCreated->value => fn () => new Notifications\MeetingCreatedNotification($meeting()),
            NotificationType::MeetingAgendaCompleted->value => fn () => new Notifications\MeetingAgendaCompletedNotification($meeting(), $user()),
            NotificationType::FollowedInstitutionActivity->value => fn () => new Notifications\MeetingCreatedNotification($meeting())->viaFollow(),
            NotificationType::MeetingReminder->value => fn () => new Notifications\MeetingReminderNotification($meeting(), 2),
            NotificationType::InstitutionActivity->value => fn () => new Notifications\InstitutionActivityNotification(new Collection([$request()])),
            NotificationType::InstitutionActivityNotMine->value => fn () => new Notifications\InstitutionActivityNotMineNotification($request()),
            NotificationType::ApprovalRequested->value => fn () => new Notifications\ApprovalRequestedNotification($this->reservationChanges()[0]['resource']->reservation),
            NotificationType::ReservationStatusChanged->value => fn () => new Notifications\ReservationStatusChangedNotification([$this->reservationChanges()[0]], $user()),
            NotificationType::AssignedToResource->value => fn () => new Notifications\AssignedToResourceNotification($actor(), $object()),
            NotificationType::ReservationDraftItemTaken->value => fn () => new Notifications\ReservationDraftItemTakenNotification([$this->text('Projektorius', 'Projector')], '2026-11-05'),
            NotificationType::CommentActivity->value => fn () => new Notifications\CommentPostedNotification($this->text('<p>Manau, 3 punktą reikėtų perkelti į kitą posėdį.</p><p>Ar pritariate?</p>', '<p>I think item 3 should move to the next meeting.</p><p>Do you agree?</p>'), $object(), $actor()),
            NotificationType::CommentMention->value => fn () => new Notifications\CommentPostedNotification($this->text('<p>@Ona, ar gali patikslinti siūlomą datą?</p>', '<p>@Ona, could you clarify the proposed date?</p>'), $object(), $actor(), true),
            NotificationType::DutyExpiring->value => fn () => new Notifications\DutyExpiringNotification($duty(), (new Models\Pivots\Dutiable)->forceFill(['end_date' => '2026-11-30']), 30),
            NotificationType::AccessChanged->value => fn () => new Notifications\AccessChangedNotification([[
                'kind' => 'started', 'dutyName' => $this->text('Atstovas', 'Representative'), 'institutionName' => $institution()->name,
                'date' => '2026-10-01', 'effectiveOn' => '2026-10-01', 'isExOfficio' => false,
            ]], '2026-10-01'),
            NotificationType::MemberRegistration->value => fn () => new Notifications\MemberRegistrationNotification(1, $user()->name, $institution(), $user()->email, self::ID),
            NotificationType::StudentRepRegistration->value => fn () => new Notifications\StudentRepRegistrationNotification(self::ID, $user()->name, $institution(), self::ID),
            NotificationType::SupportRequestCreated->value => fn () => new Notifications\SupportRequestCreatedNotification($support()),
            NotificationType::SupportRequestAssigned->value => fn () => new Notifications\SupportRequestAssignedNotification($support(), $user()),
            NotificationType::SupportRequestInvolved->value => fn () => new Notifications\SupportRequestInvolvedNotification($support(), $user()),
            NotificationType::SupportRequestStatusChanged->value => fn () => new Notifications\SupportRequestStatusChangedNotification($support(), SupportRequestStatus::New, SupportRequestStatus::InProgress, $user()),
            NotificationType::Welcome->value => fn () => new Notifications\WelcomeNotification,
        ];
    }

    public function reservationChanges(): array
    {
        $reservation = (new Models\Reservation)->forceFill(['id' => self::ID, 'name' => $this->text('Pavyzdinė rezervacija', 'Sample reservation')]);

        return collect([$this->text('Projektorius', 'Projector'), $this->text('Ekranas', 'Screen')])->map(fn (string $name, int $index): array => [
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
            'feedback' => new Mail\FeedbackMail($this->text('Pavyzdinis atsiliepimas.', 'Sample feedback.'), $user, 'https://example.org', $this->text('Pavyzdinis tekstas', 'Sample text')),
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
        $factories = $this->notificationFactories();
        $item = fn (string $type): array => $factories[$type]()->toDigestItem($this->user());
        $meeting = $item('meeting_created');
        $meetings = collect([
            ['Pavyzdinė taryba', 'Sample council'], ['Studijų programos komitetas', 'Study programme committee'],
            ['Fakulteto taryba', 'Faculty council'], ['Etikos komisija', 'Ethics commission'],
            ['Senatas', 'Senate'], ['Ginčų komisija', 'Disputes commission'],
            ['Stipendijų komisija', 'Scholarship commission'], ['Rektorato taryba', 'Rectorate council'],
        ])->map(fn (array $names, int $index): array => new Notifications\MeetingCreatedNotification(
            $this->meeting($this->institution(...$names), '2026-11-0'.($index + 1).' 15:00:00')
        )->toDigestItem($this->user()))->all();

        return match ($scenario) {
            'single' => ['meeting' => [$meeting]],
            'grouped' => [
                'meeting' => [...array_slice($meetings, 0, 3), $item('meeting_agenda_completed')],
                'task' => [
                    $item('task_assigned'),
                    new Notifications\TaskAssignedNotification($this->task('Atnaujinti kontaktus', 'Update the contacts', $this->institution()), $this->user())->toDigestItem($this->user()),
                ],
            ],
            'overflow' => ['meeting' => $meetings],
            'overdue' => ['task' => [$item('task_overdue')]],
            'legacy' => ['meeting' => [array_diff_key($meeting, array_flip(['type', 'digestLine', 'digestGroupTitle']))]],
            default => throw new \InvalidArgumentException("Unknown digest preview scenario: {$scenario}"),
        };
    }
}
