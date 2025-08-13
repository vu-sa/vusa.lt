<?php

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use App\States\InstitutionCheckIns\Active;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('author can withdraw own check-in; non-author cannot', function () {
    $author = makeTenantUser();
    $other = makeTenantUser();

    $checkIn = InstitutionCheckIn::factory()->create([
        'user_id' => $author->id,
        'state' => Active::class,
        'until_date' => Carbon::now()->addDays(3),
    ]);

    asUserWithInertia($author)
        ->post(route('check-ins.withdraw', $checkIn))
        ->assertStatus(302);

    // Use plain auth (no Inertia header) to receive a 403 instead of Handler's 302 redirect
    asUser($other)
        ->post(route('check-ins.withdraw', $checkIn))
        ->assertStatus(403);
});

it('member can confirm; non-member forbidden', function () {
    $member = makeTenantUser();
    $nonMember = User::factory()->create();
    $checkIn = InstitutionCheckIn::factory()->create([
        'state' => Active::class,
        'until_date' => Carbon::now()->addDays(3),
    ]);

    asUserWithInertia($member)
        ->post(route('check-ins.confirm', $checkIn))
        ->assertStatus(302);

    // Use plain auth (no Inertia header) to receive a 403 instead of Handler's 302 redirect
    asUser($nonMember)
        ->post(route('check-ins.confirm', $checkIn))
        ->assertStatus(403);
});

it('admin can suppress/unsuppress', function () {
    $admin = makeAdminUser();
    $checkIn = InstitutionCheckIn::factory()->create([
        'state' => Active::class,
        'until_date' => Carbon::now()->addDays(4),
    ]);

    asUserWithInertia($admin)->post(route('check-ins.suppress', $checkIn), ['reason' => 'policy'])->assertStatus(302);
    asUserWithInertia($admin)->post(route('check-ins.unsuppress', $checkIn))->assertStatus(302);
});
