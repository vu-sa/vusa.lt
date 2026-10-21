<?php

use App\Enums\ResponsibilityPurpose;
use App\Mail\ConfirmMemberRegistration;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use App\Services\ResponsibilityResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

describe('member registration recipients', function (): void {
    beforeEach(function (): void {
        $this->tenant = Tenant::factory()->create();
        $this->institution = Institution::factory()->for($this->tenant)->create();
    });

    it('finds the padalinys duties holding the responsibility, in contact order', function (): void {
        $second = Duty::factory()->for($this->institution)->create(['order' => 2]);
        $first = Duty::factory()->for(Institution::factory()->for($this->tenant))->create(['order' => 1]);
        grantPurpose($second, ResponsibilityPurpose::MemberRegistration, $this->tenant);
        grantPurpose($first, ResponsibilityPurpose::MemberRegistration, $this->tenant);

        $duties = app(ResponsibilityResolver::class)->dutiesForTenant(ResponsibilityPurpose::MemberRegistration, $this->tenant);

        expect($duties->modelKeys())->toBe([$first->id, $second->id]);
    });

    it('ignores duties with other responsibilities or another padalinys in scope', function (): void {
        Duty::factory()->for($this->institution)->create()->assignRole(Role::factory()->create());
        grantPurpose(Duty::factory()->for($this->institution)->create(), ResponsibilityPurpose::MemberRegistration, Tenant::factory()->create());

        expect(app(ResponsibilityResolver::class)->dutiesForTenant(ResponsibilityPurpose::MemberRegistration, $this->tenant))->toBeEmpty();
    });

    it('builds the confirmation mail around the first intake duty', function (): void {
        $duty = Duty::factory()->for($this->institution)->create(['email' => 'priemimas@example.com']);
        grantPurpose($duty, ResponsibilityPurpose::MemberRegistration, $this->tenant);

        $contact = app(ResponsibilityResolver::class)->dutiesForTenant(ResponsibilityPurpose::MemberRegistration, $this->tenant)->first();
        $mail = new ConfirmMemberRegistration('Jonas Jonaitis', $this->institution, $contact);

        expect($mail->dutyContact->is($duty))->toBeTrue();
    });
});
