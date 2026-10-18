<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

pest()->use(RefreshDatabase::class);

test('stars the names of self-renamed users before dropping the flag', function (): void {
    Schema::table('users', function (Blueprint $table): void {
        $table->boolean('name_was_changed')->default(false);
    });

    $renamed = User::factory()->create(['name' => 'Jonas Jonaitis']);
    $alreadyStarred = User::factory()->create(['name' => 'Ona Onaitė *']);
    $untouched = User::factory()->create(['name' => 'Petras Petraitis']);
    DB::table('users')->whereIn('id', [$renamed->id, $alreadyStarred->id])->update(['name_was_changed' => true]);

    (require base_path('database/migrations/2026_10_18_170919_drop_name_was_changed_from_users_table.php'))->up();

    expect(Schema::hasColumn('users', 'name_was_changed'))->toBeFalse()
        ->and(DB::table('users')->whereIn('id', [$renamed->id, $alreadyStarred->id, $untouched->id])->pluck('name', 'id')->all())
        ->toBe([
            $renamed->id => 'Jonas Jonaitis *',
            $alreadyStarred->id => 'Ona Onaitė *',
            $untouched->id => 'Petras Petraitis',
        ]);
});
