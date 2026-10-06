<?php

use App\Actions\UpdateDuty;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\CallQueuedClosure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Symfony\Component\HttpKernel\Exception\HttpException;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    config(['queue.default' => 'sync']);
});

function updateDutyActionData(Duty $duty, array $overrides = []): array
{
    return array_replace([
        'name' => ['lt' => 'Atnaujintos pareigos', 'en' => 'Updated duty'],
        'institution_id' => $duty->institution_id,
        'places_to_occupy' => 2,
        'contacts_grouping' => 'none',
    ], $overrides);
}

test('duty update persists attributes institution and explicit and type-derived roles', function (): void {
    $duty = Duty::factory()->create();
    $institution = Institution::factory()->create();
    $role = Role::factory()->create();
    $typeRole = Role::factory()->create();
    $type = DutyType::factory()->create();
    $type->roles()->attach($typeRole);

    app(UpdateDuty::class)->execute($duty, updateDutyActionData($duty, [
        'institution_id' => $institution->id,
        'description' => ['lt' => '<p>Aprašymas</p>', 'en' => '<p>Description</p>'],
        'email' => 'duty@example.com',
        'contacts_grouping' => 'tenant',
        'roles' => [$role->id],
        'types' => [$type->id],
    ]));

    $updated = $duty->fresh();
    expect($updated->getTranslations('name'))->toBe(['lt' => 'Atnaujintos pareigos', 'en' => 'Updated duty'])
        ->and($updated->getTranslation('description', 'en'))->toBe('<p>Description</p>')
        ->and($updated->institution_id)->toBe($institution->id)
        ->and($updated->email)->toBe('duty@example.com')
        ->and($updated->places_to_occupy)->toBe(2)
        ->and($updated->contacts_grouping)->toBe('tenant')
        ->and($updated->roles()->pluck('roles.id')->all())->toEqualCanonicalizing([$role->id, $typeRole->id])
        ->and($updated->types()->pluck('duty_types.id')->all())->toBe([$type->id]);
});

test('duty update leaves omitted or null member lists alone and removes an empty list', function (array $overrides, ?string $expectedEndDate): void {
    $this->travelTo('2026-10-03 12:00:00');
    $duty = Duty::factory()->create();
    $row = Dutiable::factory()->forDuty($duty)->active()->create();

    app(UpdateDuty::class)->execute($duty, updateDutyActionData($duty, $overrides));

    expect($row->fresh()->end_date?->toDateString())->toBe($expectedEndDate);
})->with([
    'omitted' => [[], null],
    'null' => [['current_users' => null], null],
    'empty' => [['current_users' => []], '2026-10-02'],
]);

test('duty update clears omitted roles types targets and assignable tenants', function (): void {
    $duty = Duty::factory()->create();
    $duty->assignRole(Role::factory()->create());
    $duty->types()->attach(DutyType::factory()->create());
    $duty->exOfficioTargetDuties()->attach(Duty::factory()->create());
    $duty->assignableTenants()->attach(Tenant::factory()->create());

    app(UpdateDuty::class)->execute($duty, updateDutyActionData($duty));

    expect($duty->roles()->count())->toBe(0)
        ->and($duty->types()->count())->toBe(0)
        ->and($duty->exOfficioTargetDuties()->count())->toBe(0)
        ->and($duty->assignableTenants()->count())->toBe(0);
});

test('super admin role rejection rolls back attributes and membership writes', function (): void {
    $this->travelTo('2026-10-03 12:00:00');
    $duty = Duty::factory()->create();
    $originalName = $duty->getTranslations('name');
    $row = Dutiable::factory()->forDuty($duty)->active()->create();
    $superRole = Role::findByName(config('permission.super_admin_role_name'));

    expect(fn () => app(UpdateDuty::class)->execute($duty, updateDutyActionData($duty, [
        'roles' => [$superRole->id],
        'current_users' => [],
    ])))->toThrow(HttpException::class, __('messages.role.not_assignable_to_duty'))
        ->and($duty->fresh()->getTranslations('name'))->toBe($originalName)
        ->and($row->fresh()->end_date)->toBeNull()
        ->and($duty->roles()->count())->toBe(0);
});

test('assignable tenant removal ends all allocated seats while retained tenants sync manual representatives', function (): void {
    $this->travelTo('2026-10-03 12:00:00');
    $duty = Duty::factory()->create();
    $removedTenant = Tenant::factory()->create();
    $retainedTenant = Tenant::factory()->create();
    $duty->assignableTenants()->attach([$removedTenant->id, $retainedTenant->id]);
    $source = Dutiable::factory()->active()->create();
    $removed = Dutiable::factory()->forDuty($duty)->forTenant($removedTenant)->active()->create(['end_date' => '2026-11-01']);
    $removedDerived = Dutiable::factory()->forDuty($duty)->forTenant($removedTenant)->active()->create(['via_dutiable_id' => $source->id]);
    $retainedDerived = Dutiable::factory()->forDuty($duty)->forTenant($retainedTenant)->active()->create(['via_dutiable_id' => $source->id]);
    $retained = Dutiable::factory()->forDuty($duty)->forTenant($retainedTenant)->active()->create();
    $owning = Dutiable::factory()->forDuty($duty)->active()->create();

    app(UpdateDuty::class)->execute($duty, updateDutyActionData($duty, [
        'assignable_tenants' => [['tenant_id' => $retainedTenant->id, 'quota' => 3, 'user_ids' => [$retained->dutiable_id]]],
    ]));

    expect($removed->fresh()->end_date->toDateString())->toBe('2026-10-02')
        ->and($removedDerived->fresh()->end_date->toDateString())->toBe('2026-10-02')
        ->and($retainedDerived->fresh()->end_date)->toBeNull()
        ->and($retained->fresh()->end_date)->toBeNull()
        ->and($owning->fresh()->end_date)->toBeNull()
        ->and($duty->assignableTenants()->first()->pivot->quota)->toBe(3);
});

test('target backfill waits for the outer transaction to commit', function (): void {
    $duty = Duty::factory()->create();
    $target = Duty::factory()->create();
    $source = Dutiable::factory()->forDuty($duty)->active()->create();

    DB::beginTransaction();
    try {
        app(UpdateDuty::class)->execute($duty, updateDutyActionData($duty, ['ex_officio_target_duty_ids' => [$target->id]]));
        expect(Dutiable::where('via_dutiable_id', $source->id)->count())->toBe(0);
        DB::commit();
    } catch (Throwable $exception) {
        DB::rollBack();
        throw $exception;
    }

    $derived = Dutiable::where('via_dutiable_id', $source->id)->firstOrFail();
    expect($derived->duty_id)->toBe($target->id)
        ->and($derived->dutiable_id)->toBe($source->dutiable_id);
});

test('target unlink backfill waits for commit and keeps derived membership history', function (): void {
    $this->travelTo('2026-10-03 12:00:00');
    $duty = Duty::factory()->create();
    $target = Duty::factory()->create();
    $duty->exOfficioTargetDuties()->attach($target);
    $source = Dutiable::factory()->forDuty($duty)->active()->create();
    $derived = Dutiable::where('via_dutiable_id', $source->id)->firstOrFail();

    DB::beginTransaction();
    try {
        app(UpdateDuty::class)->execute($duty, updateDutyActionData($duty));
        expect($derived->fresh()->end_date)->toBeNull();
        DB::commit();
    } catch (Throwable $exception) {
        DB::rollBack();
        throw $exception;
    }

    expect($derived->fresh()->end_date->toDateString())->toBe('2026-10-03');
});

test('outer rollback discards duty changes and target backfill', function (): void {
    $duty = Duty::factory()->create();
    $originalName = $duty->getTranslations('name');
    $target = Duty::factory()->create();
    $source = Dutiable::factory()->forDuty($duty)->active()->create();

    DB::beginTransaction();
    try {
        app(UpdateDuty::class)->execute($duty, updateDutyActionData($duty, ['ex_officio_target_duty_ids' => [$target->id]]));
    } finally {
        DB::rollBack();
    }
    DB::transaction(fn () => null);

    expect($duty->fresh()->getTranslations('name'))->toBe($originalName)
        ->and($duty->exOfficioTargetDuties()->count())->toBe(0)
        ->and(Dutiable::where('via_dutiable_id', $source->id)->count())->toBe(0);
});

test('unchanged ex officio targets do not queue a backfill', function (): void {
    $duty = Duty::factory()->create();
    $target = Duty::factory()->create();
    $duty->exOfficioTargetDuties()->attach($target);
    Queue::fake([CallQueuedClosure::class]);

    app(UpdateDuty::class)->execute($duty, updateDutyActionData($duty, ['ex_officio_target_duty_ids' => [$target->id]]));

    Queue::assertNotPushed(CallQueuedClosure::class);
    expect($duty->exOfficioTargetDuties()->pluck('duties.id')->all())->toBe([$target->id]);
});
