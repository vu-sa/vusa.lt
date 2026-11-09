<?php

use App\Enums\LearningLayer;
use App\Models\Document;
use App\Models\GlossaryTerm;
use App\Models\InstitutionType;
use App\Models\LearningStep;
use App\Models\Tenant;
use Database\Seeders\LearningPathPilotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('seeds the pilot once, links archive documents by title and keeps curator edits on rerun', function (): void {
    $spk = InstitutionType::query()->where('slug', 'spk')->first() ?? InstitutionType::factory()->create(['slug' => 'spk']);
    $statute = Document::factory()->create(['title' => 'VU SA Įstatai (nuo 2025 m.)']);
    $tenant = Tenant::query()->where('type', 'padalinys')->firstOrFail();
    $unitRegulations = Document::factory()->create(['title' => "{$tenant->shortname} nuostatai"]);

    $this->seed(LearningPathPilotSeeder::class);

    $step = LearningStep::query()->where('key', 'pilot_vusa_statute')->sole();
    expect($step->sourceFor('lt')->document_id)->toBe($statute->id);
    expect(LearningStep::query()->where('institution_type_id', $spk->id)->count())->toBe(9);
    $unitStep = LearningStep::query()->where('key', "pilot_unit_regulations_{$tenant->alias}")->sole();
    expect($unitStep->layer)->toBe(LearningLayer::Tenant);
    expect($unitStep->sourceFor('lt')->document_id)->toBe($unitRegulations->id);
    expect(LearningStep::query()->where('key', 'pilot_spk_regulations')->sole()->glossaryTerms)->toHaveCount(5);

    $step->update(['order' => 99, 'title' => ['lt' => 'Kuratoriaus pavadinimas']]);
    $counts = [LearningStep::query()->count(), GlossaryTerm::query()->count()];
    $this->seed(LearningPathPilotSeeder::class);

    expect([LearningStep::query()->count(), GlossaryTerm::query()->count()])->toBe($counts);
    expect($step->fresh()->order)->toBe(99);
    expect($step->fresh()->getTranslation('title', 'lt'))->toBe('Kuratoriaus pavadinimas');
});
