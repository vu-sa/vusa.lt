<?php

namespace App\Providers;

use App\Models\Calendar;
use App\Models\Duty;
use App\Models\Role;
use App\Models\RoleType;
use App\Models\Typeable;
use App\Models\User;
use App\Observers\CalendarObserver;
use App\Observers\RoleTypeObserver;
use App\Observers\TypeableObserver;
use App\Observers\UserPermissionObserver;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Spatie\Permission\Models\Permission;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
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
            \App\Listeners\ReservationResource\HandleReservationResourceReserved::class,
            \App\Listeners\ReservationResource\HandleReservationResourceLent::class,
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
        /* \App\Events\TaskCreated::class => [ */
        /*    \App\Listeners\HandleTaskCreated::class, */
        /* ], */
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
        Typeable::observe(TypeableObserver::class);
        // TODO: properly implement this and the PermissionService
        // User::observe(UserPermissionObserver::class);
        // Role::observe(UserPermissionObserver::class);
        // Duty::observe(UserPermissionObserver::class);
        Permission::observe(UserPermissionObserver::class);
    }
}
