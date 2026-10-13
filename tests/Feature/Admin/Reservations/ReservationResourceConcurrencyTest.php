<?php

use App\Actions\EnsureReservationCapacity;
use App\Enums\ApprovalDecision;
use App\Models\Pivots\ReservationResource;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Tenant;
use App\Services\ApprovalService;
use Database\Seeders\TestSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    if (! getenv('RUN_MYSQL_CONCURRENCY_TESTS')) {
        $this->markTestSkipped('Set RUN_MYSQL_CONCURRENCY_TESTS=1 to exercise MySQL row locks.');
    }

    $this->originalConnection = config('database.default');
    $this->lockDatabase = 'vusa_lock_test_'.bin2hex(random_bytes(6));
    $connection = [...config('database.connections.mysql'), 'url' => null, 'database' => null];
    config(['database.connections.mysql_lock_admin' => $connection]);
    Schema::connection('mysql_lock_admin')->createDatabase($this->lockDatabase);

    foreach (['mysql_lock_owner', 'mysql_lock_contender'] as $name) {
        config(["database.connections.{$name}" => [...$connection, 'database' => $this->lockDatabase]]);
    }
    DB::setDefaultConnection('mysql_lock_owner');
    config(['webpush.database_connection' => 'mysql_lock_owner']);
    $this->artisan('migrate', ['--database' => 'mysql_lock_owner', '--schema-path' => database_path('schema/mysql-schema.sql'), '--force' => true])->assertExitCode(0);
    $this->seed(TestSeeder::class);
    DB::connection('mysql_lock_contender')->statement('SET SESSION innodb_lock_wait_timeout = 1');

    $this->tenant = Tenant::query()->firstOrFail();
    $this->owner = makeUser($this->tenant);
    $this->manager = makeTenantUserWithRole('Išteklių administratorius', $this->tenant);
    $this->resource = Resource::factory()->for($this->tenant)->create(['capacity' => 1, 'is_reservable' => true]);
    $this->reservation = Reservation::factory()->create([
        'start_time' => now()->addDays(2), 'end_time' => now()->addDays(2)->addHour(),
    ]);
    $this->reservation->users()->attach($this->owner);
});

afterEach(function (): void {
    if (! isset($this->lockDatabase)) {
        return;
    }
    foreach (['mysql_lock_owner', 'mysql_lock_contender'] as $name) {
        $connection = DB::connection($name);
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }
        DB::purge($name);
    }
    DB::setDefaultConnection($this->originalConnection);
    Schema::connection('mysql_lock_admin')->dropDatabaseIfExists($this->lockDatabase);
    DB::purge('mysql_lock_admin');
});

test('a competing capacity claim waits for the first booking and then sees its allocation', function (): void {
    $start = $this->reservation->start_time;
    $end = $this->reservation->end_time;
    $lines = [['id' => $this->resource->id, 'quantity' => 1]];
    $owner = DB::connection('mysql_lock_owner');
    $owner->beginTransaction();
    EnsureReservationCapacity::execute($lines, $start, $end);
    ReservationResource::create([
        'reservation_id' => $this->reservation->id, 'resource_id' => $this->resource->id,
        'quantity' => 1, 'start_time' => $start, 'end_time' => $end,
    ]);

    DB::setDefaultConnection('mysql_lock_contender');
    try {
        DB::transaction(fn () => EnsureReservationCapacity::execute($lines, $start, $end));
        test()->fail('A competing claim must wait for the resource lock.');
    } catch (QueryException $exception) {
        expect($exception->errorInfo[1])->toBe(1205);
    }

    $owner->commit();
    expect(fn () => DB::transaction(fn () => EnsureReservationCapacity::execute($lines, $start, $end)))
        ->toThrow(ValidationException::class)
        ->and((int) ReservationResource::where('resource_id', $this->resource->id)->sum('quantity'))->toBe(1);
});

test('an approval waits for an edit and rechecks the resource manager after it commits', function (): void {
    $pivot = ReservationResource::create([
        'reservation_id' => $this->reservation->id, 'resource_id' => $this->resource->id,
        'quantity' => 1, 'start_time' => $this->reservation->start_time, 'end_time' => $this->reservation->end_time,
    ]);
    $otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->firstOrFail();
    $otherResource = Resource::factory()->for($otherTenant)->create(['capacity' => 1, 'is_reservable' => true]);
    $owner = DB::connection('mysql_lock_owner');
    $owner->beginTransaction();
    $locked = ReservationResource::query()->lockForUpdate()->findOrFail($pivot->id);
    $locked->update(['resource_id' => $otherResource->id]);

    DB::setDefaultConnection('mysql_lock_contender');
    try {
        app(ApprovalService::class)->approve($pivot, $this->manager, ApprovalDecision::Approved);
        test()->fail('Approval must wait for the edited pivot lock.');
    } catch (QueryException $exception) {
        expect($exception->errorInfo[1])->toBe(1205);
    }

    $owner->commit();
    expect(fn () => app(ApprovalService::class)->approve($pivot, $this->manager, ApprovalDecision::Approved))
        ->toThrow(InvalidArgumentException::class)
        ->and($pivot->fresh()->resource_id)->toBe($otherResource->id)
        ->and($pivot->approvals()->count())->toBe(0);
});
