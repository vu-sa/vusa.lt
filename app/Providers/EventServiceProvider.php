<?php

namespace App\Providers;

use App\Events\CommentPosted;
use App\Events\DutiableChanged;
use App\Events\FileableNameUpdated;
use App\Events\MemberRegistrationCreated;
use App\Events\ReservationResourceCreated;
use App\Events\StudentRepRegistrationCreated;
use App\Events\TaskCreated;
use App\Listeners\HandleDutiableChange;
use App\Listeners\HandleTaskCreated;
use App\Listeners\NotifyUsersOfComment;
use App\Listeners\QueueNotificationForDigest;
use App\Listeners\ReservationResource\HandleReservationResourceCreated;
use App\Listeners\ReservationResource\HandleReservationResourceStateChanged;
use App\Listeners\SendMemberRegistrationNotification;
use App\Listeners\SendStudentRepRegistrationNotification;
use App\Listeners\UpdateSharepointFolder;
use App\Models\Calendar;
use App\Models\Document;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Relationshipable;
use App\Models\Role;
use App\Models\RoleType;
use App\Models\Type;
use App\Models\Typeable;
use App\Models\User;
use App\Notifications\Subscribers\ApprovalNotificationSubscriber;
use App\Observers\CalendarObserver;
use App\Observers\DocumentObserver;
use App\Observers\InstitutionObserver;
use App\Observers\RelationshipableObserver;
use App\Observers\RoleTypeObserver;
use App\Observers\TypeableObserver;
use App\Observers\TypeObserver;
use App\Observers\UserPermissionObserver;
use App\Tasks\Subscribers\ApprovalTaskSubscriber;
use App\Tasks\Subscribers\InstitutionCheckInTaskSubscriber;
use App\Tasks\Subscribers\MeetingTaskSubscriber;
use App\Tasks\Subscribers\ReservationTaskSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Notifications\Events\NotificationSending;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Microsoft\MicrosoftExtendSocialite;
use Spatie\ModelStates\Events\StateChanged;
use Spatie\Permission\Models\Permission;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
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
            QueueNotificationForDigest::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        // Task subscribers
        ReservationTaskSubscriber::class,
        ApprovalTaskSubscriber::class,
        MeetingTaskSubscriber::class,
        InstitutionCheckInTaskSubscriber::class,
        // Notification subscribers
        ApprovalNotificationSubscriber::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Document::observe(DocumentObserver::class);
        Calendar::observe(CalendarObserver::class);
        RoleType::observe(RoleTypeObserver::class);
        Type::observe(TypeObserver::class);
        Typeable::observe(TypeableObserver::class);
        Institution::observe(InstitutionObserver::class);
        Relationshipable::observe(RelationshipableObserver::class);
        // Permission cache invalidation for users, roles, duties
        // Clears permission cache, Atstovavimas cache, and Typesense scoped keys
        User::observe(UserPermissionObserver::class);
        Role::observe(UserPermissionObserver::class);
        Duty::observe(UserPermissionObserver::class);
        Permission::observe(UserPermissionObserver::class);
    }
}
