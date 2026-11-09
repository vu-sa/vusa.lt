<?php

use App\Services\Learning\SystemSteps\SystemStepRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (SystemStepRegistry::definitions() as $key => $definition) {
            DB::table('learning_steps')->insertOrIgnore([
                'id' => (string) Str::ulid(), 'key' => $key, 'is_system' => true,
                'kind' => 'action', 'layer' => $definition['layer']->value,
                'stage' => $definition['stage']->value, 'importance' => $definition['importance']->value,
                'per_term' => $definition['per_term'], 'order' => 0, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Authored progress must survive a rollback of the seed.
    }
};
