<?php

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Role;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

pest()->use(RefreshDatabase::class);

function splitTypesMigration(): object
{
    return require database_path('migrations/2026_10_01_223412_split_institution_and_duty_types.php');
}

beforeEach(function (): void {
    $this->institution = Institution::factory()->create();
    $this->duty = Duty::factory()->create();
    Schema::disableForeignKeyConstraints();
    foreach (['duty_type_role', 'role_can_attach_duty_types', 'duty_duty_type', 'institution_institution_type', 'duty_types', 'institution_types', 'type_assignment_orphans'] as $table) {
        Schema::drop($table);
    }
    Schema::enableForeignKeyConstraints();
    DB::table('types')->delete();
});

test('cutover preserves ids metadata grants and history while archiving missing owners', function (): void {
    $institution = $this->institution;
    $duty = $this->duty;
    $role = Role::query()->first();
    foreach ([500 => 'institution', 501 => 'institution', 600 => 'duty'] as $id => $domain) {
        DB::table('types')->insert([
            'id' => $id, 'model_type' => $domain, 'parent_id' => $id === 501 ? 500 : null,
            'title' => json_encode(['lt' => 'Tipas '.$id, 'en' => 'Type '.$id]),
            'extra_attributes' => json_encode(['custom' => 'preserved']),
            'slug' => 'legacy-'.$id, 'deleted_at' => $id === 501 ? now() : null,
        ]);
    }
    DB::table('typeables')->insert([
        ['type_id' => 500, 'typeable_type' => 'institution', 'typeable_id' => $institution->id],
        ['type_id' => 600, 'typeable_type' => 'duty', 'typeable_id' => $duty->id],
        ['type_id' => 600, 'typeable_type' => 'duty', 'typeable_id' => 'missing-owner'],
    ]);
    DB::table('role_type')->insert(['type_id' => 600, 'role_id' => $role->id]);
    DB::table('role_can_attach_types')->insert(['type_id' => 600, 'role_id' => $role->id]);
    activity()->performedOn(new \App\Models\Type(['id' => 500]))->log('Historical type');

    splitTypesMigration()->up();

    $this->assertDatabaseHas('institution_types', ['id' => 501, 'parent_id' => 500]);
    $this->assertDatabaseHas('duty_types', ['id' => 600, 'extra_attributes' => json_encode(['custom' => 'preserved'])]);
    $this->assertDatabaseHas('institution_institution_type', ['institution_id' => $institution->id, 'institution_type_id' => 500]);
    $this->assertDatabaseHas('duty_duty_type', ['duty_id' => $duty->id, 'duty_type_id' => 600]);
    $this->assertDatabaseHas('duty_type_role', ['role_id' => $role->id, 'duty_type_id' => 600]);
    $this->assertDatabaseHas('role_can_attach_duty_types', ['role_id' => $role->id, 'duty_type_id' => 600]);
    $this->assertDatabaseHas('activity_log', ['subject_type' => 'institution_type', 'subject_id' => 500]);
    expect(DB::table('type_assignment_orphans')->count())->toBe(1)
        ->and(DB::table('duty_duty_type')->count())->toBe(1)
        ->and(DB::table('institution_types')->where('id', 501)->value('deleted_at'))->not->toBeNull();
});

test('cross-domain hierarchy fails before creating destination tables', function (): void {
    DB::table('types')->insert([
        ['id' => 500, 'model_type' => 'institution', 'parent_id' => null],
        ['id' => 600, 'model_type' => 'duty', 'parent_id' => 500],
    ]);
    expect(fn () => splitTypesMigration()->up())->toThrow(RuntimeException::class, 'Cross-domain parent')
        ->and(Schema::hasTable('institution_types'))->toBeFalse();
});
