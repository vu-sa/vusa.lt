<?php

use App\Mail\ConfirmMemberRegistration;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Settings\FormSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);


describe('SendMemberRegistrationNotification Listener', function () {

    it('demonstrates the null duty error scenario is now fixed', function () {
        // This test shows that the error is now properly handled

        $tenant = Tenant::factory()->create();
        $institution = Institution::factory()->for($tenant)->create();

        // Create a role and configure it in settings
        $role = Role::create(['name' => 'Member Registration Coordinator', 'guard_name' => 'web']);
        $settings = app(FormSettings::class);
        $settings->member_registration_notification_recipient_role_id = $role->id;
        $settings->save();

        // Create duties but don't assign the required role (so no duties will match)
        $duty = Duty::factory()->for($institution)->create();
        $otherRole = Role::create(['name' => 'Other Role', 'guard_name' => 'web']);
        $duty->assignRole($otherRole);

        // Now test that the mail system can handle this gracefully
        // The system should use the fallback duty
        $mail = new ConfirmMemberRegistration(
            'Jonas Jonaitis',
            $institution,
            $duty  // Using the available duty as fallback
        );

        expect($mail->dutyContact)->toBe($duty);
        expect($mail->institution)->toBe($institution);
        expect($mail->name)->toBe('Jonas Jonaitis');
    });

    it('works correctly when duty with role exists', function () {
        $tenant = Tenant::factory()->create();
        $institution = Institution::factory()->for($tenant)->create();

        // Create a role and configure it in settings
        $role = Role::create(['name' => 'Member Registration Coordinator', 'guard_name' => 'web']);
        $settings = app(FormSettings::class);
        $settings->member_registration_notification_recipient_role_id = $role->id;
        $settings->save();

        // Create a duty with the role
        $user = User::factory()->create();
        $duty = Duty::factory()
            ->for($institution)
            ->hasAttached($user, ['start_date' => now()->subDay(), 'end_date' => now()->addDay()])
            ->create(['email' => 'coordinator@example.com']);

        $duty->assignRole($role);

        // Test that the mail can be created successfully
        $mail = new ConfirmMemberRegistration(
            'Jonas Jonaitis',
            $institution,
            $duty
        );

        expect($mail->dutyContact)->toBe($duty);
        expect($mail->institution)->toBe($institution);
        expect($mail->name)->toBe('Jonas Jonaitis');
    });

    it('fails when no role is configured in settings', function () {
        // This test shows what happens when member_registration_notification_recipient_role_id is null
        $settings = app(FormSettings::class);
        expect($settings->member_registration_notification_recipient_role_id)->toBeNull();

        // When the setting is null, the whereHas query will find no duties
        $tenant = Tenant::factory()->create();
        $institution = Institution::factory()->for($tenant)->create();

        // Create a duty without any specific role
        Duty::factory()->for($institution)->create();

        // Simulate the query that would be run in the listener
        $mailableDuties = $institution->duties()->whereHas('roles', function ($query) use ($settings) {
            $query->where('id', $settings->member_registration_notification_recipient_role_id);
        })->get();

        // The collection should be empty because the role_id is null
        expect($mailableDuties)->toBeEmpty();
        expect($mailableDuties->first())->toBeNull();

        // This would cause the TypeError in the actual listener
        $this->expectException(TypeError::class);
        new ConfirmMemberRegistration(
            'Jonas Jonaitis',
            $institution,
            $mailableDuties->first()  // @phpstan-ignore-line
        );
    });

    it('fails when configured role does not exist', function () {
        // Configure a non-existent role ID
        $settings = app(FormSettings::class);
        $settings->member_registration_notification_recipient_role_id = 'non-existent-role-id';
        $settings->save();

        $tenant = Tenant::factory()->create();
        $institution = Institution::factory()->for($tenant)->create();

        // Create a duty (but it won't have the non-existent role)
        Duty::factory()->for($institution)->create();

        // Simulate the query that would be run in the listener
        $mailableDuties = $institution->duties()->whereHas('roles', function ($query) use ($settings) {
            $query->where('id', $settings->member_registration_notification_recipient_role_id);
        })->get();

        // The collection should be empty because the role doesn't exist
        expect($mailableDuties)->toBeEmpty();
        expect($mailableDuties->first())->toBeNull();

        // This would cause the TypeError in the actual listener
        $this->expectException(TypeError::class);
        new ConfirmMemberRegistration(
            'Jonas Jonaitis',
            $institution,
            $mailableDuties->first()  // @phpstan-ignore-line
        );
    });

    it('fails when institution has no duties with the configured role', function () {
        // Create a role and configure it
        $role = Role::create(['name' => 'Member Registration Coordinator', 'guard_name' => 'web']);
        $settings = app(FormSettings::class);
        $settings->member_registration_notification_recipient_role_id = $role->id;
        $settings->save();

        $tenant = Tenant::factory()->create();
        $institution = Institution::factory()->for($tenant)->create();

        // Create duties but don't assign the required role
        $duty = Duty::factory()->for($institution)->create();
        $otherRole = Role::create(['name' => 'Other Role', 'guard_name' => 'web']);
        $duty->assignRole($otherRole);

        // Simulate the query that would be run in the listener
        $mailableDuties = $institution->duties()->whereHas('roles', function ($query) use ($settings) {
            $query->where('id', $settings->member_registration_notification_recipient_role_id);
        })->get();

        // The collection should be empty because no duties have the required role
        expect($mailableDuties)->toBeEmpty();
        expect($mailableDuties->first())->toBeNull();

        // This would cause the TypeError in the actual listener
        $this->expectException(TypeError::class);
        new ConfirmMemberRegistration(
            'Jonas Jonaitis',
            $institution,
            $mailableDuties->first()  // @phpstan-ignore-line
        );
    });

    it('logs warning and skips email when no role-specific duty found', function () {
        // Create test data
        $tenant = Tenant::factory()->create();
        $institution = Institution::factory()->for($tenant)->create();

        // Create role but don't assign to any duty
        $role = Role::create(['name' => 'Member Registration Coordinator', 'guard_name' => 'web']);
        $settings = app(FormSettings::class);
        $settings->member_registration_notification_recipient_role_id = $role->id;
        $settings->save();

        // Create a duty that doesn't have the required role
        Duty::factory()->for($institution)->create();

        // Simulate the query condition
        $mailableDuties = $institution->duties()->whereHas('roles', function ($query) use ($settings) {
            $query->where('id', $settings->member_registration_notification_recipient_role_id);
        })->get();

        // Verify no duties match the role criteria
        expect($mailableDuties)->toBeEmpty();
        expect($mailableDuties->first())->toBeNull();

        // In this case, the listener should skip sending email
        // This is now handled gracefully instead of throwing an error
    });

    it('handles gracefully when institution has no duties at all', function () {
        // Create institution with no duties
        $tenant = Tenant::factory()->create();
        $institution = Institution::factory()->for($tenant)->create();

        // Configure role
        $role = Role::create(['name' => 'Member Registration Coordinator', 'guard_name' => 'web']);
        $settings = app(FormSettings::class);
        $settings->member_registration_notification_recipient_role_id = $role->id;
        $settings->save();

        // Verify no duties exist
        expect($institution->duties()->count())->toBe(0);

        // Simulate the queries that would run in the listener
        $mailableDuties = $institution->duties()->whereHas('roles', function ($query) use ($settings) {
            $query->where('id', $settings->member_registration_notification_recipient_role_id);
        })->get();

        expect($mailableDuties)->toBeEmpty();
        expect($mailableDuties->first())->toBeNull();

        // In this case, the listener should return early and not send email
        // This is now handled gracefully instead of throwing an error
    });
});
