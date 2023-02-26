<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Notifications\ModelCommented;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class NotifyUsersOfComment implements ShouldQueue
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
     * @return void
     */
    public function handle(CommentPosted $event)
    {
        $commentable = $event->comment->commentable;

        // let's assume for now, that the subject will always be an user
        $user = $event->comment->user;

        $subject = [
            'modelClass' => class_basename(get_class($user)),
            'name' => $user->name,
            'image' => $user->profile_photo_path,
        ];

        $objectClassName = class_basename(get_class($commentable));

        $routeName = Str::of($objectClassName)->lcfirst()->plural().'.show';

        $object = [
            'modelClass' => $objectClassName,
            'name' => optional($commentable)->name ?: optional($commentable)->title ?: null,
            'url' => route($routeName, $commentable->id),
        ];

        // TODO: not always title, sometimes name, sometimes something else
        $text = "<p><strong>{$user->name}</strong> paliko komentarą įraše: {$commentable->title}</p>";

        // TODO: send notification to all users that have access to the commentable, e.g. file doesn't work
        Notification::send($commentable->users?->unique(), new ModelCommented($text, $object, $subject));
    }
}
