<?php

use App\Helpers\GenitivizeHelper;
use App\Mail\ConfirmMemberRegistration;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->user = User::factory()->create();
    $this->duty = Duty::factory()
        ->for($this->institution)
        ->hasAttached($this->user, ['start_date' => now()->subDay(), 'end_date' => now()->addDay()])
        ->create([
            'email' => 'duty@example.com',
        ]);
});

describe('ConfirmMemberRegistration Mail', function (): void {
    it('can be constructed with valid parameters', function (): void {
        $mail = new ConfirmMemberRegistration(
            'Jonas Jonaitis',
            $this->institution,
            $this->duty
        );

        expect($mail->name)->toBe('Jonas Jonaitis')
            ->and($mail->institution)->toBe($this->institution)
            ->and($mail->dutyContact)->toBe($this->duty);
    });

    it('sets contact name from duty user when available', function (): void {
        $mail = new ConfirmMemberRegistration(
            'Jonas Jonaitis',
            $this->institution,
            $this->duty
        );

        $expectedContactName = GenitivizeHelper::genitivizeEveryWord($this->user->name);
        expect($mail->contactName)->toBe($expectedContactName);
    });

    it('falls back to duty email when no user is assigned', function (): void {
        // Create duty without attached users
        $dutyWithoutUser = Duty::factory()
            ->for($this->institution)
            ->create(['email' => 'fallback@example.com']);

        $mail = new ConfirmMemberRegistration(
            'Jonas Jonaitis',
            $this->institution,
            $dutyWithoutUser
        );

        expect($mail->contactName)->toBe('fallback@example.com');
    });

    it('builds email with correct subject and reply-to', function (): void {
        $mail = new ConfirmMemberRegistration(
            'Jonas Jonaitis',
            $this->institution,
            $this->duty
        );

        $builtMail = $mail->build();

        expect($builtMail->subject)->toContain('📝')
            ->toContain($this->institution->maybe_short_name)
            ->and($builtMail->replyTo[0]['address'])->toBe($this->duty->email)
            ->and($builtMail->markdown)->toBe('emails.memberRegistration.confirm');
    });

    it('throws TypeError when dutyContact is null', function (): void {
        // This test documents the current behavior that causes the error
        // In PHP 8+, passing null to a typed parameter throws TypeError
        // We need to suppress the static analysis error since we're intentionally testing invalid input
        /** @phpstan-ignore-next-line */
        expect(fn () => new ConfirmMemberRegistration(
            'Jonas Jonaitis',
            $this->institution,
            null
        ))->toThrow(TypeError::class);
    });
});
