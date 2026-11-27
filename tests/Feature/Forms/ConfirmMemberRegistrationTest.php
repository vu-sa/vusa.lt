<?php

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
    $this->user = User::factory()->create(['name' => 'Jonas Jonaitis']);
    $this->duty = Duty::factory()
        ->for($this->institution)
        ->hasAttached($this->user, ['start_date' => now()->subDay(), 'end_date' => now()->addDay()])
        ->create([
            'email' => 'duty@example.com',
        ]);
});

describe('ConfirmMemberRegistration Mail', function (): void {

    it('sets contact name from duty user when available', function (): void {
        $mail = new ConfirmMemberRegistration(
            'Jonas Petraitis',
            $this->institution,
            $this->duty
        );

        expect($mail->contactName)->toBe('Jono Jonaičio');
    });

    it('falls back to duty email when no user is assigned', function (): void {
        // Create duty without attached users
        $dutyWithoutUser = Duty::factory()
            ->for($this->institution)
            ->create(['email' => 'fallback@example.com']);

        $mail = new ConfirmMemberRegistration(
            'Jonas Petraitis',
            $this->institution,
            $dutyWithoutUser
        );

        expect($mail->contactName)->toBe('fallback@example.com');
    });

    it('builds email with correct subject and reply-to', function (): void {
        $mail = new ConfirmMemberRegistration(
            'Jonas Petraitis',
            $this->institution,
            $this->duty
        );

        $builtMail = $mail->build();

        expect($builtMail->subject)->toStartWith('Gavome tavo registraciją: ')
            ->not->toContain('📝')
            ->toContain($this->institution->maybe_short_name)
            ->and(mb_strlen($builtMail->subject))->toBeLessThanOrEqual(60)
            ->and($builtMail->replyTo[0]['address'])->toBe($this->duty->email)
            ->and($builtMail->markdown)->toBe('emails.memberRegistration.confirm');
    });

});
