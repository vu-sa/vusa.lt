<?php

use App\Enums\LearningAudienceType;
use App\Enums\ResponsibilityPurpose;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (SystemStepRegistry::definitions() as $key => $definition) {
            $id = (string) Str::ulid();
            $inserted = DB::table('learning_steps')->insertOrIgnore([
                'id' => $id, 'key' => $key, 'is_system' => true,
                'kind' => 'action',
                'per_term' => $definition['per_term'], 'order' => 0, 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            if ($inserted) {
                $sources = $definition['audience'] === LearningAudienceType::Everyone
                    ? [null] : [ResponsibilityPurpose::StudentRepresentation->value, ResponsibilityPurpose::StudentRepCoordination->value];
                foreach ($sources as $source) {
                    DB::table('learning_step_audiences')->insert([
                        'id' => (string) Str::ulid(), 'learning_step_id' => $id,
                        'type' => $source === null ? 'everyone' : 'purpose', 'source_id' => $source,
                        'importance' => $definition['importance']->value, 'stage' => $definition['stage']->value,
                        'order' => 0, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Authored progress must survive a rollback of the seed.
    }
};
