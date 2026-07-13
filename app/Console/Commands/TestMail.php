<?php

namespace App\Console\Commands;

use App\Mail\NotificationDigest;
use App\Models\User;
use App\Notifications\TestPushNotification;
use Illuminate\Console\Command;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Diagnose the configured mail transport by sending a message synchronously.
 *
 * Sending is done with sendNow() so that transport errors (bad credentials,
 * unreachable host, TLS failures) surface here instead of inside a queue worker.
 */
class TestMail extends Command
{
    protected $signature = 'mail:test
                            {email? : Recipient address (defaults to MAIL_FROM_ADDRESS)}
                            {--digest : Send the notification digest template instead of a plain message}';

    protected $description = 'Send a test email through the configured mailer and report transport errors';

    public function handle(): int
    {
        $this->info('📧 Mail Configuration');
        $this->newLine();

        $this->showConfiguration();

        $recipient = $this->argument('email') ?? config('mail.from.address');

        if (! is_string($recipient) || $recipient === '') {
            $this->error('No recipient given and MAIL_FROM_ADDRESS is not set.');

            return self::FAILURE;
        }

        $mailable = $this->option('digest')
            ? $this->buildDigestMailable()
            : $this->buildPlainMailable();

        $this->newLine();
        $this->info("Sending to {$recipient}...");

        try {
            Mail::to($recipient)->sendNow($mailable);
        } catch (TransportExceptionInterface $e) {
            $this->newLine();
            $this->error('❌ The mail transport rejected the message.');
            $this->newLine();
            $this->line($e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info("✅ Sent successfully to {$recipient}.");

        return self::SUCCESS;
    }

    /**
     * Print the resolved mail configuration, masking the password.
     */
    private function showConfiguration(): void
    {
        $mailer = config('mail.default');

        $rows = [
            ['Mailer', $mailer],
            ['Host', config('mail.mailers.smtp.host')],
            ['Port', config('mail.mailers.smtp.port')],
            ['Encryption', config('mail.mailers.smtp.encryption') ?: 'none'],
            ['Username', config('mail.mailers.smtp.username') ?: '(not set)'],
            ['Password', $this->maskSecret(config('mail.mailers.smtp.password'))],
            ['Verify peer', config('mail.mailers.smtp.verify_peer') ? 'yes' : 'no (TLS certificates are not validated)'],
            ['From', config('mail.from.address').' ('.config('mail.from.name').')'],
        ];

        $this->table(['Setting', 'Value'], $rows);

        if ($mailer === 'log') {
            $this->warn('⚠️  The log mailer is active — nothing will actually be delivered.');
        }
    }

    /**
     * Mask a secret so it can be shown without leaking it.
     */
    private function maskSecret(mixed $secret): string
    {
        if (! is_string($secret) || $secret === '') {
            return '(not set)';
        }

        $length = mb_strlen($secret);

        if ($length <= 2) {
            return str_repeat('*', $length)." ({$length} chars)";
        }

        return mb_substr($secret, 0, 1)
            .str_repeat('*', $length - 2)
            .mb_substr($secret, -1)
            ." ({$length} chars)";
    }

    /**
     * Build a digest mailable with a sample item, exercising the real template.
     */
    private function buildDigestMailable(): Mailable
    {
        $user = Auth::user() ?? User::query()->first();

        if (! $user instanceof User) {
            $this->warn('No user found to render the digest for — falling back to a plain message.');

            return $this->buildPlainMailable();
        }

        $notification = new TestPushNotification;

        return new NotificationDigest($user, [
            $notification->category()->value => [$notification->toDigestItem($user)],
        ]);
    }

    private function buildPlainMailable(): Mailable
    {
        return new class extends Mailable
        {
            public function envelope(): Envelope
            {
                return new Envelope(subject: '✅ vusa.lt — mail:test');
            }

            public function content(): Content
            {
                return new Content(
                    htmlString: '<p>This is a test message from <strong>vusa.lt</strong>.</p>'
                        .'<p>If you are reading this, the configured mail transport works.</p>'
                        .'<p>Sent at '.now()->toDateTimeString().'.</p>',
                );
            }
        };
    }
}
