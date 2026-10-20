<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

pest()->use(RefreshDatabase::class);

test('it reports orphan permissions with who still holds them, and writes nothing', function (): void {
    $orphan = Permission::create(['name' => 'saziningaiExams.read.padalinys', 'guard_name' => 'web']);
    Role::factory()->create(['name' => 'Senoji rolė'])->givePermissionTo($orphan);
    Permission::query()->where('name', 'eventTypes.read.*')->delete();

    expect(Artisan::call('permissions:audit', ['--json' => true]))->toBe(0);

    $report = json_decode(Artisan::output(), true);

    expect($report['missing'])->toContain('eventTypes.read.*')
        ->and(collect($report['orphans'])->firstWhere('name', 'saziningaiExams.read.padalinys')['held_by'])->toBe(['Senoji rolė'])
        ->and(Permission::query()->where('name', 'saziningaiExams.read.padalinys')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', 'eventTypes.read.*')->exists())->toBeFalse();
});
