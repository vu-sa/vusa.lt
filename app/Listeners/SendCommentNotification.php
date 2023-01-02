<?php

namespace App\Listeners;

use App\Models\Doing;
use App\Notifications\CommentSubmitted;
use App\Events\UserComments;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Notification;

class SendCommentNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\UserComments  $event
     * @return void
     */
    public function handle(UserComments $event)
    {
        // check if model is App\Models\Doing
        if ($event->modelCommentedOn instanceof Doing) {
            // get all doing's institution users
            // TODO: fix for multiple questions
            $users = $event->modelCommentedOn->questions->first()->institution->duties->pluck('users')->flatten()->unique('id');

            // send notification to user
            Notification::send($users, new CommentSubmitted($event->commenter, $event->modelCommentedOn, $event->route));
        }
    }
}
