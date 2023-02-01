<?php

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Doing;
use App\Models\User;
use App\Notifications\ModelCommented;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

it('asserts that a comment notification is sent when an user comments on a doing', function () {

    $doing = Doing::factory()->has(User::factory()->count(1))->create();

    // get user from doing
    $user = $doing->users->first();

    $comment = Comment::factory()->for($user)->for($doing, 'commentable')->create();

    Notification::fake();

    CommentPosted::dispatch($comment);

    Notification::assertSentTo(
        [$user],
        ModelCommented::class
    );
});

