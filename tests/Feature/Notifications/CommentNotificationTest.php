<?php

namespace Tests\Feature\Notifications;

use App\Events\CommentPosted;
use App\Models\Comment;
use App\Models\Doing;
use App\Models\Duty;
use App\Models\User;
use App\Notifications\ModelCommented;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\CoversNothing;
use Tests\TestCase;

#[CoversNothing]
class CommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    // TODO: actually test the notification
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

    // Test if comment notification is sent to duty email if user has current_duty with email vusa.lt
    // For doings it's not as applicable as for reservations and other models
    public function test_asserts_that_a_comment_notification_is_sent_to_duty_email_if_user_has_current_duty_with_email_vusa_lt()
    {
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
    }
}
