<?php

namespace Tests\Feature\Notifications;

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Doing;
use App\Models\User;
use App\Notifications\ModelCommented;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\TestCase;

#[CoversNothing]
class CommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_asserts_that_a_comment_notification_is_sent_when_an_user_comments_on_a_doing()
    {
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
    }
}
