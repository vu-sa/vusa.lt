<?php

namespace App\Listeners;

use App\Enums\CommentKind;
use App\Events\CommentPosted;
use App\Models\Pivots\ReservationResource;
use App\Notifications\CommentPostedNotification;
use App\Services\CommentRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class NotifyUsersOfComment implements ShouldQueue
{
    public function __construct(protected CommentRecipientResolver $recipients) {}

    public function handle(CommentPosted $event): void
    {
        $comment = $event->comment;
        $commentable = $comment->commentable;

        if (! $commentable) {
            return;
        }

        $user = $comment->user;

        $subject = [
            'modelClass' => class_basename($user::class),
            'name' => $user->name,
            'image' => $user->profile_photo_path,
        ];

        $objectClassName = class_basename($commentable::class);
        $objectName = $commentable->name ?? $commentable->title ?? null;

        if ($objectClassName === class_basename(ReservationResource::class)) {
            $objectName = $commentable->reservation->name;
        }

        $routeName = Str::of($objectClassName)->lcfirst()->plural().'.show';

        $object = [
            'modelClass' => $objectClassName,
            'name' => $objectName,
            'url' => route($routeName, $commentable->id),
            'id' => $commentable->getKey(),
        ];

        $mentioned = $this->recipients->mentioned($comment);

        if ($mentioned->isNotEmpty()) {
            Notification::send($mentioned, new CommentPostedNotification(
                $comment->body, $object, $subject, isMention: true, isPoll: $comment->kind === CommentKind::Poll,
            ));
        }

        $audience = $this->recipients->audience($comment);

        if ($audience->isNotEmpty()) {
            Notification::send($audience, new CommentPostedNotification(
                $comment->body, $object, $subject, isPoll: $comment->kind === CommentKind::Poll,
            ));
        }
    }
}
