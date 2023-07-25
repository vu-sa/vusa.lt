<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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
            // \SocialiteProviders\Google\GoogleExtendSocialite::class.'@handle',
        ],
        \App\Events\CommentPosted::class => [
            \App\Listeners\NotifyUsersOfComment::class,
        ],
        \App\Events\FileableNameUpdated::class => [
            \App\Listeners\UpdateSharepointFolder::class,
        ],
        \Spatie\ModelStates\Events\StateChanged::class => [
            \App\Listeners\HandleDoingStateChange::class,
            \App\Listeners\HandleReservationResourceCreated::class,
        ],
        \App\Events\DutiableChanged::class => [
            \App\Listeners\HandleDutiableChange::class,
        ],
        \App\Events\ReservationResourceCreated::class => [
            \App\Listeners\HandleReservationResourceCreated::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
