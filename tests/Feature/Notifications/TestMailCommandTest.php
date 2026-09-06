<?php

use App\Mail\NotificationDigest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportException;

pest()->use(RefreshDatabase::class);

describe('mail:test', function (): void {
    test('delivers inline instead of queueing', function (): void {
        Mail::fake();

        $this->artisan('mail:test', ['email' => 'someone@example.com'])
            ->assertSuccessful();

        // The command exists to prove the transport works. A queued message is
        // delivered by a worker, so a broken transport would report success here.
        Mail::assertNothingQueued();
        Mail::assertSentCount(1);
    });

    test('delivers the digest template inline, even though NotificationDigest is ShouldQueue', function (): void {
        Mail::fake();

        $this->actingAs(User::factory()->create())
            ->artisan('mail:test', ['email' => 'someone@example.com', '--digest' => true])
            ->assertSuccessful();

        Mail::assertNothingQueued();
        Mail::assertSent(NotificationDigest::class, fn ($mail) => $mail->hasTo('someone@example.com'));
    });

    test('reports a transport failure and exits non-zero', function (): void {
        Mail::shouldReceive('to')->andReturnSelf();
        Mail::shouldReceive('sendNow')->andThrow(new TransportException('535 Authentication failed'));

        $this->artisan('mail:test', ['email' => 'someone@example.com'])
            ->expectsOutputToContain('535 Authentication failed')
            ->assertFailed();
    });
});
