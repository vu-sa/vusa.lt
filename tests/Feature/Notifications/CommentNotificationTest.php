<?php

use App\Events\UserComments;
use App\Models\Comment;
use App\Models\Doing;
use App\Notifications\CommentNotification;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('asserts that a comment notification is sent when an user comments on a doing', function () {

    $user = User::factory();
    $doing = Doing::factory()->create();

    $comment = Comment::factory()->for($user)->for($doing, 'commentable')->create();

    Notification::fake();

    UserComments::dispatch($user, $doing, 'route');

    Notification::assertSentTo(
        $user,
        CommentNotification::class,
        function ($notification, $channels) use ($comment) {
            return $notification->comment->id === $comment->id;
        }
    );

    // remove created models after test
    // $comment->delete();
    // $doing->delete();
    // $user->delete();
});

