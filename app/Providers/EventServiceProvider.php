<?php

namespace App\Providers;

use App\Events\CommentPosted;
use App\Events\FileableNameUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\NotifyUsersOfComment;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \SocialiteProviders\Manager\SocialiteWasCalled::class => [
            \SocialiteProviders\Microsoft\MicrosoftExtendSocialite::class . '@handle',
        ],
        CommentPosted::class => [
            NotifyUsersOfComment::class,
        ],
        FileableNameUpdated::class => [
            \App\Listeners\UpdateSharepointFolder::class,
        ],
        \Spatie\ModelStates\Events\StateChanged::class => [
            \App\Listeners\HandleDoingStateChange::class,
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
