<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();

    $role = Role::firstOrCreate(['name' => 'Komunikacijos koordinatorius', 'guard_name' => 'web']);
    $role->givePermissionTo([
        'duties.read.padalinys',
        'duties.update.padalinys',
    ]);

    $this->admin = makeUser($this->tenant);
    $this->adminDuty = $this->admin->duties()->first();
    $this->adminDuty->assignRole('Komunikacijos koordinatorius');
});

function dutyUpdatePayload(Duty $duty, array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Duty',
        'institution_id' => $duty->institution_id,
        'places_to_occupy' => 1,
        'contacts_grouping' => 'none',
    ], $overrides);
}

test('removing the role from your own duty is warned and rolled back', function (): void {
    asUserWithInertia($this->admin)
        ->patch(route('duties.update', $this->adminDuty), dutyUpdatePayload($this->adminDuty, [
            'roles' => [],
            'current_users' => [$this->admin->id],
        ]))
        ->assertSessionHas('access_change_warning');

    expect($this->adminDuty->fresh()->roles()->count())->toBeGreaterThan(0);
});

test('editing a duty you do not hold is not guarded', function (): void {
    $otherDuty = Duty::factory()->for(Institution::factory()->for($this->tenant))->create();
    $otherDuty->assignRole('Komunikacijos koordinatorius');

    asUserWithInertia($this->admin)
        ->patch(route('duties.update', $otherDuty), dutyUpdatePayload($otherDuty, [
            'roles' => [],
        ]))
        ->assertSessionMissing('access_change_warning');

    expect($otherDuty->fresh()->roles()->count())->toBe(0);
});

test('self lockout rolls back duty attributes memberships and queued target backfill', function (): void {
    config(['queue.default' => 'sync']);
    $this->travelTo('2026-10-03 12:00:00');
    $target = Duty::factory()->for($this->adminDuty->institution)->create();
    $originalName = $this->adminDuty->getTranslations('name');
    $membership = $this->adminDuty->dutiables()->where('dutiable_id', $this->admin->id)->firstOrFail();

    asUserWithInertia($this->admin)
        ->patch(route('duties.update', $this->adminDuty), dutyUpdatePayload($this->adminDuty, [
            'roles' => [],
            'current_users' => [],
            'ex_officio_target_duty_ids' => [$target->id],
        ]))
        ->assertSessionHas('access_change_warning');

    expect($this->adminDuty->fresh()->getTranslations('name'))->toBe($originalName);
    expect($membership->fresh()->end_date)->toBeNull();
    expect($this->adminDuty->exOfficioTargetDuties()->count())->toBe(0);
    expect(Dutiable::where('duty_id', $target->id)->count())->toBe(0);
});

test('acknowledged duty role loss persists and redirects to the dashboard', function (): void {
    asUserWithInertia($this->admin)
        ->patch(route('duties.update', $this->adminDuty), dutyUpdatePayload($this->adminDuty, [
            'roles' => [],
            'current_users' => [$this->admin->id],
            'acknowledge_access_change' => true,
        ]))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('success', __('access_change.applied'))
        ->assertSessionMissing('access_change_warning');

    expect($this->adminDuty->fresh()->roles()->count())->toBe(0);
    expect($this->adminDuty->fresh()->getTranslation('name', 'lt'))->toBe('Test Duty');
});
