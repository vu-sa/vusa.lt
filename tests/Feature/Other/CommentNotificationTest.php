<?php

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Doing;
use App\Models\Duty;
use App\Models\User;
use App\Notifications\ModelCommented;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('asserts that a comment notification is sent when an user comments on a doing', function () {
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

test('asserts that a comment notification is sent to duty email if user has current duty with email vusa lt', function () {
    $user = User::factory()->hasAttached(
        Duty::factory()->count(1)->state(
            fn () => ['email' => 'example@vusa.lt']
        ),
        ['start_date' => now()]
    )->create();

    $doing = Doing::factory()->hasAttached($user)->create();

    // get user from doing
    $comment = Comment::factory()->for($user)->for($doing, 'commentable')->create();

    Notification::fake();

    CommentPosted::dispatch($comment);

    Notification::assertSentTo(
        [$user->current_duties()->first()],
        ModelCommented::class
    );
});
