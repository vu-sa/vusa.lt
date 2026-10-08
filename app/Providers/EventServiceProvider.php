<?php

namespace App\Providers;

use App\Events\CommentPosted;
use App\Events\DutiableChanged;
use App\Events\FileableNameUpdated;
use App\Events\MemberRegistrationCreated;
use App\Events\ReservationResourceCreated;
use App\Events\StudentRepRegistrationCreated;
use App\Events\TaskCreated;
use App\Listeners\BlockExternalNotificationsOnStaging;
use App\Listeners\HandleDutiableChange;
use App\Listeners\HandleTaskCreated;
use App\Listeners\NotifyUsersOfComment;
use App\Listeners\PruneRejectedPushSubscription;
use App\Listeners\QueueNotificationForDigest;
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
use App\Models\Document;
use App\Models\Duty;
use App\Models\Role;
use App\Models\RoleType;
use App\Models\User;
use App\Notifications\Subscribers\ApprovalNotificationSubscriber;
use App\Observers\DocumentObserver;
use App\Observers\RoleTypeObserver;
use App\Observers\UserPermissionObserver;
use App\Tasks\Subscribers\ApprovalTaskSubscriber;
use App\Tasks\Subscribers\InstitutionCheckInTaskSubscriber;
use App\Tasks\Subscribers\MeetingTaskSubscriber;
use App\Tasks\Subscribers\ReservationTaskSubscriber;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationSending;
use NotificationChannels\WebPush\Events\NotificationFailed;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;
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
            HandleDutiableChange::class,
            SyncExOfficioDutiables::class,
            SyncContactSearchIndexes::class,
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
        Document::observe(DocumentObserver::class);
        RoleType::observe(RoleTypeObserver::class);
        // Permission cache invalidation for users, roles, duties
        // Clears permission cache, Atstovavimas cache, and Typesense scoped keys
        User::observe(UserPermissionObserver::class);
        Role::observe(UserPermissionObserver::class);
        Duty::observe(UserPermissionObserver::class);
        Permission::observe(UserPermissionObserver::class);
    }
}
