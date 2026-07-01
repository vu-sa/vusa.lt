<?php

namespace App\Listeners;

use App\Enums\CommentKind;
use App\Events\CommentPosted;
use App\Models\Pivots\ReservationResource;
use App\Models\User;
use App\Notifications\CommentPostedNotification;
use App\Services\CommentRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
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
            'modelClass' => class_basename(get_class($user)),
            'name' => $user->name,
            'image' => $user->profile_photo_path,
        ];

        $objectClassName = class_basename(get_class($commentable));
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
            Notification::send($mentioned, new CommentPostedNotification($text, $object, $subject));
        }

        // The rest of the audience (reps for a root comment, thread participants
        // for a reply) get the standard comment / poll notification.
        $audience = $this->recipients->audience($comment);

        if ($audience->isNotEmpty()) {
            $action = $comment->kind === CommentKind::Poll
                ? 'notifications.started_poll_on'
                : 'notifications.left_comment_on';

            $text = $this->text($user->name, $objectName, $action);

            Notification::send($this->withDuties($audience), new CommentPostedNotification($text, $object, $subject));
        }
    }

    /**
     * Build the notification body fragment ("<b>X</b> {action} <b>Y</b>").
     */
    protected function text(string $authorName, ?string $objectName, string $actionKey): string
    {
        return "<p><strong>{$authorName}</strong> ".__($actionKey)." <strong>{$objectName}</strong></p>";
    }

    /**
     * Append the recipients' duties so duty inboxes (name@vusa.lt) also receive
     * the mail notification — preserving the pre-existing behaviour.
     *
     * @param  Collection<int, User>  $users
     * @return Collection<int, mixed>
     */
    protected function withDuties(Collection $users): Collection
    {
        return $users->merge(
            $users->load('duties')
                ->pluck('duties')
                ->flatten()
                ->unique('id')
                ->values()
        );
    }
}
