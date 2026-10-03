<?php

namespace App\Enums;

/**
 * Every notification Mano VU SA sends, as the user sees it on Pranešimų nustatymai: the unit a
 * preference is stored against. A notification class may map to more than one type (a comment is a
 * mention or thread activity), and each type's defaults reproduce the urgency-based channel policy.
 *
 * @typescript
 */
enum NotificationType: string
{
    case TaskAssigned = 'task_assigned';
    case TaskReminder = 'task_reminder';
    case TaskOverdue = 'task_overdue';
    case TaskAutoCompleted = 'task_auto_completed';

    case MeetingReminder = 'meeting_reminder';
    case InstitutionActivity = 'institution_activity';
    case MeetingCreated = 'meeting_created';
    case MeetingAgendaCompleted = 'meeting_agenda_completed';
    case FollowedInstitutionActivity = 'followed_institution_activity';

    case ApprovalRequested = 'approval_requested';
    case ReservationStatusChanged = 'reservation_status_changed';
    case AssignedToResource = 'assigned_to_resource';
    case ReservationDraftItemTaken = 'reservation_draft_item_taken';

    case CommentMention = 'comment_mention';
    case CommentActivity = 'comment_activity';

    case DutyExpiring = 'duty_expiring';
    case AccessChanged = 'access_changed';

    case MemberRegistration = 'member_registration';
    case StudentRepRegistration = 'student_rep_registration';

    case SupportRequestStatusChanged = 'support_request_status_changed';

    case Welcome = 'welcome';
    case TestPush = 'test_push';

    public function section(): NotificationCategory
    {
        return match ($this) {
            self::TaskAssigned, self::TaskReminder, self::TaskOverdue, self::TaskAutoCompleted => NotificationCategory::Task,
            self::MeetingReminder, self::InstitutionActivity, self::MeetingCreated,
            self::MeetingAgendaCompleted, self::FollowedInstitutionActivity => NotificationCategory::Meeting,
            self::ApprovalRequested, self::ReservationStatusChanged,
            self::AssignedToResource, self::ReservationDraftItemTaken => NotificationCategory::Reservation,
            self::CommentMention, self::CommentActivity => NotificationCategory::Comment,
            self::DutyExpiring, self::AccessChanged => NotificationCategory::Duty,
            self::MemberRegistration, self::StudentRepRegistration => NotificationCategory::Registration,
            self::SupportRequestStatusChanged, self::Welcome, self::TestPush => NotificationCategory::System,
        };
    }

    public function urgency(): NotificationUrgency
    {
        return match ($this) {
            self::TaskReminder, self::TaskOverdue, self::MeetingReminder, self::InstitutionActivity,
            self::ApprovalRequested, self::AssignedToResource, self::CommentMention, self::DutyExpiring,
            self::MemberRegistration, self::StudentRepRegistration,
            // The only notice a requester gets of an approval, rejection or pickup to make.
            self::ReservationStatusChanged => NotificationUrgency::Act,
            self::TaskAutoCompleted => NotificationUrgency::Record,
            self::Welcome, self::TestPush => NotificationUrgency::Onboarding,
            default => NotificationUrgency::Know,
        };
    }

    /**
     * Whether the user can change this type's channels; the rest are system messages.
     */
    public function isConfigurable(): bool
    {
        return $this !== self::Welcome && $this !== self::TestPush;
    }

    public function defaultEmail(): EmailDelivery
    {
        return match ($this->urgency()) {
            NotificationUrgency::Act => EmailDelivery::Immediate,
            NotificationUrgency::Know => EmailDelivery::Digest,
            // A record asks nothing of the reader, so the bell is enough (rule 14).
            NotificationUrgency::Record, NotificationUrgency::Onboarding => EmailDelivery::Off,
        };
    }

    /**
     * Registrations go to the role's inbox, which the holder cannot opt out of for the role.
     */
    public function lockedEmail(): ?EmailDelivery
    {
        return match ($this) {
            self::MemberRegistration, self::StudentRepRegistration => EmailDelivery::Immediate,
            default => null,
        };
    }

    public function defaultPush(): bool
    {
        return match ($this) {
            // Not TaskOverdue: a weekly summary is not worth interrupting for, the email carries it.
            self::TaskReminder, self::MeetingReminder, self::ApprovalRequested,
            self::CommentMention, self::FollowedInstitutionActivity => true,
            default => false,
        };
    }

    public function labelKey(): string
    {
        return 'notifications.types.'.$this->value.'.label';
    }

    public function descriptionKey(): string
    {
        return 'notifications.types.'.$this->value.'.description';
    }

    /**
     * The configurable types in page order, grouped by section.
     *
     * @return array<int, self>
     */
    public static function configurable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => $type->isConfigurable()));
    }
}
