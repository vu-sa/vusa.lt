<?php

namespace App\Providers;

use App\Models\Calendar;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Relationshipable;
use App\Models\Role;
use App\Models\RoleType;
use App\Models\Type;
use App\Models\Typeable;
use App\Models\User;
use App\Observers\CalendarObserver;
use App\Observers\InstitutionObserver;
use App\Observers\RelationshipableObserver;
use App\Observers\RoleTypeObserver;
use App\Observers\TypeableObserver;
use App\Observers\TypeObserver;
use App\Observers\UserPermissionObserver;
use App\Tasks\Subscribers\ApprovalTaskSubscriber;
use App\Tasks\Subscribers\MeetingTaskSubscriber;
use App\Tasks\Subscribers\ReservationTaskSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Permission;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            \SocialiteProviders\Microsoft\MicrosoftExtendSocialite::class.'@handle',
        ],
        \App\Events\CommentPosted::class => [
            \App\Listeners\NotifyUsersOfComment::class,
        ],
        \App\Events\FileableNameUpdated::class => [
            \App\Listeners\UpdateSharepointFolder::class,
        ],
        \Spatie\ModelStates\Events\StateChanged::class => [
            // Note: Task-related handling moved to ReservationTaskSubscriber
            \App\Listeners\ReservationResource\HandleReservationResourceStateChanged::class,
        ],
        \App\Events\DutiableChanged::class => [
            \App\Listeners\HandleDutiableChange::class,
        ],
        \App\Events\ReservationResourceCreated::class => [
            \App\Listeners\ReservationResource\HandleReservationResourceCreated::class,
        ],
        \App\Events\MemberRegistrationCreated::class => [
            \App\Listeners\SendMemberRegistrationNotification::class,
        ],
        \App\Events\StudentRepRegistrationCreated::class => [
            \App\Listeners\SendStudentRepRegistrationNotification::class,
        ],
        \App\Events\TaskCreated::class => [
            \App\Listeners\HandleTaskCreated::class,
        ],
        // Note: Approval task handling moved to ApprovalTaskSubscriber
        // Notification digest queuing
        \Illuminate\Notifications\Events\NotificationSending::class => [
            \App\Listeners\QueueNotificationForDigest::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array<int, class-string>
     */
    protected $subscribe = [
        ReservationTaskSubscriber::class,
        ApprovalTaskSubscriber::class,
        MeetingTaskSubscriber::class,
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
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
