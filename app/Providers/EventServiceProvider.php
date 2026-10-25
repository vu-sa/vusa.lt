<?php

namespace App\Providers;

use App\Events\CommentPosted;
use App\Events\DutiableChanged;
use App\Events\FileableNameUpdated;
use App\Events\MemberRegistrationCreated;
use App\Events\ReservationResourceCreated;
use App\Events\SearchRelationChanged;
use App\Events\StudentRepRegistrationCreated;
use App\Events\TaskCreated;
use App\Listeners\BlockExternalNotificationsOnStaging;
use App\Listeners\DiagnoseHealth;
use App\Listeners\HandleDutiableChange;
use App\Listeners\HandleTaskCreated;
use App\Listeners\InvalidateRepStatisticsRelation;
use App\Listeners\InvalidateRepStatisticsSettings;
use App\Listeners\NotifyUsersOfComment;
use App\Listeners\PruneRejectedPushSubscription;
use App\Listeners\QueueMailingListSync;
use App\Listeners\QueueNotificationForDigest;
use App\Listeners\QueueReservationReviewSync;
use App\Listeners\RecordDeviceLogin;
use App\Listeners\RefreshImageCacheAfterConversion;
use App\Listeners\ReservationResource\HandleReservationResourceCreated;
use App\Listeners\ReservationResource\HandleReservationResourceStateChanged;
use App\Listeners\ResolveInstitutionActivityRequests;
use App\Listeners\SendMemberRegistrationNotification;
use App\Listeners\SendStudentRepRegistrationNotification;
use App\Listeners\SyncContactSearchIndexes;
use App\Listeners\SyncExOfficioDutiables;
use App\Listeners\SyncInstitutionActivityIndex;
use App\Listeners\SyncRelationSearchIndex;
use App\Listeners\UpdateSharepointFolder;
use App\Models\Cadence;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\FileableFile;
use App\Models\Institution;
use App\Models\InstitutionActivityRequest;
use App\Models\InstitutionCheckIn;
use App\Models\InstitutionSecretary;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\Pivots\DutyDutyType;
use App\Models\Resource;
use App\Models\ResponsibilityGrant;
use App\Models\Role;
use App\Models\RoleType;
use App\Models\Task;
use App\Models\User;
use App\Models\Vote;
use App\Notifications\Subscribers\ApprovalNotificationSubscriber;
use App\Observers\DocumentObserver;
use App\Observers\RepStatisticsObserver;
use App\Observers\RoleTypeObserver;
use App\Observers\UserPermissionObserver;
use App\Tasks\Subscribers\ApprovalTaskSubscriber;
use App\Tasks\Subscribers\InstitutionCheckInTaskSubscriber;
use App\Tasks\Subscribers\MeetingTaskSubscriber;
use App\Tasks\Subscribers\ReservationTaskSubscriber;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationSending;
use NotificationChannels\WebPush\Events\NotificationFailed;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;
use Spatie\LaravelSettings\Events\SettingsSaved;
use Spatie\MediaLibrary\Conversions\Events\ConversionHasBeenCompletedEvent;
use Spatie\ModelStates\Events\StateChanged;
use Spatie\Permission\Models\Permission;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    #[\Override]
    protected $listen = [
        SettingsSaved::class => [InvalidateRepStatisticsSettings::class],
        SearchRelationChanged::class => [QueueMailingListSync::class, InvalidateRepStatisticsRelation::class, QueueReservationReviewSync::class],
        SocialiteWasCalled::class => [
            MicrosoftExtendSocialite::class.'@handle',
        ],
        CommentPosted::class => [
            NotifyUsersOfComment::class,
        ],
        FileableNameUpdated::class => [
            UpdateSharepointFolder::class,
        ],
        StateChanged::class => [
            // Note: Task-related handling moved to ReservationTaskSubscriber
            HandleReservationResourceStateChanged::class,
        ],
        DutiableChanged::class => [
            QueueReservationReviewSync::class,
            HandleDutiableChange::class,
            SyncExOfficioDutiables::class,
            SyncContactSearchIndexes::class,
            QueueMailingListSync::class,
        ],
        ReservationResourceCreated::class => [
            HandleReservationResourceCreated::class,
        ],
        MemberRegistrationCreated::class => [
            SendMemberRegistrationNotification::class,
        ],
        StudentRepRegistrationCreated::class => [
            SendStudentRepRegistrationNotification::class,
        ],
        TaskCreated::class => [
            HandleTaskCreated::class,
        ],
        // Note: Approval task handling moved to ApprovalTaskSubscriber
        // Notification digest queuing
        NotificationSending::class => [
            BlockExternalNotificationsOnStaging::class,
            QueueNotificationForDigest::class,
        ],
        Login::class => [
            RecordDeviceLogin::class,
        ],
        DiagnosingHealth::class => [
            DiagnoseHealth::class,
        ],
        NotificationFailed::class => [
            PruneRejectedPushSubscription::class,
        ],
        ConversionHasBeenCompletedEvent::class => [
            RefreshImageCacheAfterConversion::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    #[\Override]
    protected $subscribe = [
        // Task subscribers
        ReservationTaskSubscriber::class,
        ApprovalTaskSubscriber::class,
        MeetingTaskSubscriber::class,
        InstitutionCheckInTaskSubscriber::class,
        ResolveInstitutionActivityRequests::class,
        // Notification subscribers
        ApprovalNotificationSubscriber::class,
        // Search index subscribers
        SyncInstitutionActivityIndex::class,
        SyncRelationSearchIndex::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    #[\Override]
    public function boot()
    {
        foreach ([Cadence::class, Institution::class, InstitutionType::class, Duty::class, Role::class, ResponsibilityGrant::class, Meeting::class, InstitutionCheckIn::class, InstitutionActivityRequest::class, Task::class, AgendaItem::class, Vote::class, Dutiable::class, InstitutionSecretary::class, Calendar::class, FileableFile::class] as $model) {
            $model::observe(RepStatisticsObserver::class);
        }
        foreach ([Duty::class, User::class, Institution::class, DutyType::class, InstitutionType::class, ResponsibilityGrant::class] as $model) {
            $model::observe(QueueMailingListSync::class);
        }
        Document::observe(DocumentObserver::class);
        foreach ([Duty::class, User::class, Institution::class, DutyType::class, ResponsibilityGrant::class, Role::class, Resource::class, DutyDutyType::class] as $model) {
            $model::observe(QueueReservationReviewSync::class);
        }
        RoleType::observe(RoleTypeObserver::class);
        // Permission cache invalidation for users, roles, duties
        // Clears permission cache, Atstovavimas cache, and Typesense scoped keys
        User::observe(UserPermissionObserver::class);
        Role::observe(UserPermissionObserver::class);
        Duty::observe(UserPermissionObserver::class);
        Permission::observe(UserPermissionObserver::class);
    }
}
