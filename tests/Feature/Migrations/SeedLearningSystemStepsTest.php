<?php

use App\Enums\LearningStage;
use App\Models\LearningStep;
use App\Models\LearningStepProgress;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('system step seed can be rerun without changing curator choices or progress', function (): void {
    $migration = require database_path('migrations/2026_11_09_100100_seed_learning_system_steps.php');
    $step = LearningStep::query()->where('key', 'check_access')->firstOrFail();
    $step->update(['stage' => LearningStage::WholeTerm, 'order' => 31, 'is_active' => false]);
    $progress = LearningStepProgress::factory()->create(['learning_step_id' => $step->id]);
    $missing = LearningStep::query()->where('key', 'profile_photo')->firstOrFail();
    $missing->delete();
    $migration->up();
    $migration->up();

    expect(LearningStep::query()->where('is_system', true)->count())->toBe(count(SystemStepRegistry::definitions()));
    $this->assertDatabaseHas('learning_step_progress', ['id' => $progress->id, 'learning_step_id' => $step->id]);
    expect($step->fresh()->stage)->toBe(LearningStage::WholeTerm);
    expect($step->fresh()->order)->toBe(31);
    expect($step->fresh()->is_active)->toBeFalse();
    expect(LearningStep::query()->where('key', 'profile_photo')->count())->toBe(1);
});
