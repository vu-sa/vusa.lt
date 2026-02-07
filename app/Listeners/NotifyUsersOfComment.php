<?php

namespace App\Listeners;

use App\Events\CommentPosted;
use App\Models\Pivots\ReservationResource;
use App\Notifications\CommentPostedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class NotifyUsersOfComment implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(CommentPosted $event): void
    {
        $commentable = $event->comment->commentable;

        // NOTE: in some cases, $commentable can be null, so we need to check if it's null
        if (! $commentable) {
            return;
        }

        // Note: Previously, comments could have a 'decision' field for status changes.
        // Decisions are now handled through the approvals table (see 2026_01_15_160000 migration).
        // Comments are now purely for discussion/notes.

        // let's assume for now, that the subject will always be a user
        $user = $event->comment->user;

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

        // Build the comment text
        $text = "<p><strong>{$user->name}</strong> ".__('notifications.left_comment_on')." <strong>{$objectName}</strong></p>";

        $notifiables = $commentable->users?->unique();

        // If notifiable users have duties with emails, also send notification to those emails
        $notifiables = $notifiables?->merge(
            $commentable->users?->unique()
                ->load('duties')
                ->pluck('duties')
                ->flatten()
                ->unique('id')
                ->values()
        ) ?? collect();

        Notification::send($notifiables, new CommentPostedNotification($text, $object, $subject));
    }
}
