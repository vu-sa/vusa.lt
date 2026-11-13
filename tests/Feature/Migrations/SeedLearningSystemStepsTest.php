<?php

use App\Enums\LearningStage;
use App\Models\LearningAudience;
use App\Models\LearningProgress;
use App\Models\LearningStep;
use App\Services\Learning\SystemSteps\SystemStepRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('system step seed can be rerun without changing curator choices or progress', function (): void {
    $migration = require database_path('migrations/2026_11_09_100100_seed_learning_system_steps.php');
    $step = LearningStep::query()->where('key', 'install_app')->firstOrFail();
    $step->audiences()->update(['stage' => LearningStage::WholeTerm->value]);
    $step->update(['order' => 31, 'is_active' => false]);
    $progress = LearningProgress::factory()->create(['progressable_type' => $step->getMorphClass(), 'progressable_id' => $step->id]);
    $missing = LearningStep::query()->where('key', 'browser_push')->firstOrFail();
    $missing->delete();
    $migration->up();
    $audienceCount = LearningAudience::query()->count();
    $migration->up();
    expect(LearningAudience::query()->count())->toBe($audienceCount)
        ->and(LearningStep::query()->where('is_system', true)->count())->toBe(count(SystemStepRegistry::definitions()));
    $this->assertDatabaseHas('learning_progress', ['id' => $progress->id, 'progressable_type' => $step->getMorphClass(), 'progressable_id' => $step->id]);
    expect($step->fresh()->audiences->sole()->stage)->toBe(LearningStage::WholeTerm)
        ->and($step->fresh()->order)->toBe(31)
        ->and($step->fresh()->is_active)->toBeFalse()
        ->and(LearningStep::query()->where('key', 'browser_push')->count())->toBe(1);
});

test('steps reps tick themselves are seeded as regular tasks, some with a button', function (): void {
    $notifications = LearningStep::query()->where('key', 'notification_settings')->sole();

    expect($notifications->is_system)->toBeFalse()
        ->and($notifications->getTranslation('title', 'lt'))->toBe('Pasirink, kaip gauti pranešimus')
        ->and($notifications->getTranslation('action_label', 'en'))->toBe('Set notifications')
        ->and($notifications->getTranslation('action_url', 'lt'))->toBe('/mano/profile/notifications')
        ->and($notifications->audiences()->sole()->stage)->toBe(LearningStage::BeforeStart);
    $handover = LearningStep::query()->where('key', 'write_handover')->sole();
    expect($handover->is_system)->toBeFalse()
        ->and($handover->getTranslations('action_url'))->toBeEmpty()
        ->and($handover->audiences->pluck('stage')->unique()->all())->toBe([LearningStage::TermEnd])
        ->and(LearningStep::query()->whereIn('key', ['follow_institution', 'read_handover'])->exists())->toBeFalse();
});
