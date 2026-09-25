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

    /**
     * Handle the event.
     */
    public function handle(CommentPosted $event): void
    {
        $comment = $event->comment;
        $commentable = $comment->commentable;

        // NOTE: in some cases, $commentable can be null, so we need to check if it's null
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

        // if class name is reservation_resource, then we need to get the name from the reservation
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

        // Mentioned users get a personal notification ("X mentioned you …").
        $mentioned = $this->recipients->mentioned($comment);

        if ($mentioned->isNotEmpty()) {
            $text = $this->text($user->name, $objectName, 'notifications.mentioned_you_in_comment');
            Notification::send($mentioned, new CommentPostedNotification($text, $object, $subject, isMention: true));
        }

        // The rest of the audience (reps for a root comment, thread participants
        // for a reply) get the standard comment / poll notification.
        $audience = $this->recipients->audience($comment);

        if ($audience->isNotEmpty()) {
            $action = $comment->kind === CommentKind::Poll
                ? 'notifications.started_poll_on'
                : 'notifications.left_comment_on';

            $text = $this->text($user->name, $objectName, $action);

            Notification::send($audience, new CommentPostedNotification($text, $object, $subject));
        }
    }

    /**
     * Build the notification body fragment ("<b>X</b> {action} <b>Y</b>").
     */
    protected function text(string $authorName, ?string $objectName, string $actionKey): string
    {
        return "<p><strong>{$authorName}</strong> ".__($actionKey)." <strong>{$objectName}</strong></p>";
    }
}
