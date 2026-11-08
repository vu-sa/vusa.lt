<?php

use App\Models\Cadence;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\InstitutionSecretary;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Support\AuthorityCacheExpiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

test('authority boundaries are queried once per request or job while each cache keeps its own lifetime', function (): void {
    $this->travelTo('2026-11-06 12:00:00');
    $user = User::factory()->create();
    DB::enableQueryLog();

    $longExpiry = AuthorityCacheExpiry::for($user, 3600);
    $shortExpiry = AuthorityCacheExpiry::for($user, 600);

    expect($longExpiry->toDateTimeString())->toBe('2026-11-06 13:00:00')
        ->and($shortExpiry->toDateTimeString())->toBe('2026-11-06 12:10:00')
        ->and(DB::getQueryLog())->toHaveCount(2);

    app()->forgetScopedInstances();
    AuthorityCacheExpiry::for($user, 3600);

    expect(DB::getQueryLog())->toHaveCount(4);
});

test('moving an institution clears non-ended holders access after commit', function (bool $scheduled): void {
    $tenant = Tenant::query()->first();
    $holder = makeTenantUserWithRole('Komunikacijos koordinatorius', $tenant);
    if ($scheduled) {
        $holder->duties()->first()->pivot->update(['start_date' => today()->addMonth()]);
    }
    $institution = $holder->duties()->first()->institution;
    $destination = Tenant::query()->whereKeyNot($tenant->id)->first();
    $authorizer = app(ModelAuthorizer::class);
    expect($authorizer->tenants($holder, 'news.update.padalinys')->modelKeys())->toBe([$tenant->id]);
    $unrelated = User::factory()->create();
    Cache::put('create-permissions-'.$holder->id, ['cached'], 1800);
    Cache::put('create-permissions-'.$unrelated->id, ['cached'], 1800);

    DB::transaction(function () use ($institution, $destination, $holder): void {
        $institution->update(['tenant_id' => $destination->id]);

        expect(Cache::has('auth:duties:'.$holder->id))->toBeTrue()
            ->and(Cache::has('create-permissions-'.$holder->id))->toBeTrue();
    });

    expect(Cache::has('auth:duties:'.$holder->id))->toBeFalse()
        ->and(Cache::has('create-permissions-'.$holder->id))->toBeFalse()
        ->and(Cache::has('create-permissions-'.$unrelated->id))->toBeTrue()
        ->and($authorizer->tenants($holder, 'news.update.padalinys')->modelKeys())->toBe([$destination->id]);
})->with(['current holder' => false, 'scheduled holder' => true]);

test('cadence changes clear its secretaries access after commit', function (string $change): void {
    $institution = Institution::factory()->for(Tenant::query()->first())->create();
    $cadence = Cadence::factory()->create([
        'institution_id' => $institution->id, 'start_date' => '2026-07-01', 'end_date' => '2027-06-30',
    ]);
    $secretary = User::factory()->create();
    InstitutionSecretary::create(['institution_id' => $institution->id, 'cadence_id' => $cadence->id, 'user_id' => $secretary->id]);
    $other = User::factory()->create();
    Cache::put('auth:duties:'.$secretary->id, ['cached'], 1800);
    Cache::put('index-permissions-'.$secretary->id, ['cached'], 1800);
    Cache::put('index-permissions-'.$other->id, ['cached'], 1800);

    DB::transaction(function () use ($cadence, $secretary, $change): void {
        match ($change) {
            'start' => $cadence->update(['start_date' => '2026-08-01']),
            'end' => $cadence->update(['end_date' => '2027-05-31']),
            'delete' => $cadence->delete(),
        };

        expect(Cache::has('index-permissions-'.$secretary->id))->toBeTrue();
    });

    expect(Cache::has('auth:duties:'.$secretary->id))->toBeFalse()
        ->and(Cache::has('index-permissions-'.$secretary->id))->toBeFalse()
        ->and(Cache::has('index-permissions-'.$other->id))->toBeTrue();
    if ($change === 'delete') {
        expect(InstitutionSecretary::query()->where('cadence_id', $cadence->id)->exists())->toBeFalse();
    }
})->with(['start', 'end', 'delete']);

test('rolling back a cadence change keeps the secretaries cached access', function (): void {
    $institution = Institution::factory()->for(Tenant::query()->first())->create();
    $cadence = Cadence::factory()->forYear(2026)->create(['institution_id' => $institution->id]);
    $secretary = User::factory()->create();
    InstitutionSecretary::create(['institution_id' => $institution->id, 'cadence_id' => $cadence->id, 'user_id' => $secretary->id]);
    Cache::put('index-permissions-'.$secretary->id, ['cached'], 1800);

    expect(fn () => DB::transaction(function () use ($cadence): void {
        $cadence->delete();
        throw new RuntimeException('Cancel cadence change.');
    }))->toThrow(RuntimeException::class, 'Cancel cadence change.')
        ->and(Cache::has('index-permissions-'.$secretary->id))->toBeTrue()
        ->and(Cadence::query()->find($cadence->id))->not->toBeNull()
        ->and(InstitutionSecretary::query()->where('cadence_id', $cadence->id)->where('user_id', $secretary->id)->exists())->toBeTrue();
});

test('authority cache expires after the last day of an active secretary cadence', function (): void {
    $this->travelTo('2026-11-06 23:50:00');
    $institution = Institution::factory()->for(Tenant::query()->first())->create();
    $cadence = Cadence::factory()->create([
        'institution_id' => $institution->id, 'start_date' => '2026-07-01', 'end_date' => '2026-11-06',
    ]);
    $secretary = User::factory()->create();
    InstitutionSecretary::create(['institution_id' => $institution->id, 'cadence_id' => $cadence->id, 'user_id' => $secretary->id]);

    expect(AuthorityCacheExpiry::for($secretary, 3600)->toDateTimeString())->toBe('2026-11-07 00:00:00');
});

test('authority cache keeps the earlier duty boundary', function (): void {
    $this->travelTo('2026-11-06 23:50:00');
    $institution = Institution::factory()->for(Tenant::query()->first())->create();
    $cadence = Cadence::factory()->create([
        'institution_id' => $institution->id, 'start_date' => '2026-07-01', 'end_date' => '2026-11-07',
    ]);
    $secretary = User::factory()->create();
    InstitutionSecretary::create(['institution_id' => $institution->id, 'cadence_id' => $cadence->id, 'user_id' => $secretary->id]);
    Dutiable::factory()->forDuty(Duty::factory()->for($institution)->create())->forUser($secretary)->create([
        'start_date' => '2026-07-01', 'end_date' => '2026-11-06',
    ]);

    expect(AuthorityCacheExpiry::for($secretary, 3600)->toDateTimeString())->toBe('2026-11-07 00:00:00');
});

test('inactive secretary cadences leave the normal cache lifetime unchanged', function (string $start, string $end): void {
    $this->travelTo('2026-11-06 23:50:00');
    $institution = Institution::factory()->for(Tenant::query()->first())->create();
    $cadence = Cadence::factory()->create(['institution_id' => $institution->id, 'start_date' => $start, 'end_date' => $end]);
    $secretary = User::factory()->create();
    InstitutionSecretary::create(['institution_id' => $institution->id, 'cadence_id' => $cadence->id, 'user_id' => $secretary->id]);

    expect(AuthorityCacheExpiry::for($secretary, 3600)->toDateTimeString())->toBe('2026-11-07 00:50:00');
})->with([
    'ended' => ['2026-07-01', '2026-11-05'],
    'upcoming' => ['2026-11-07', '2027-06-30'],
]);
