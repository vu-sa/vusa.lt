<?php

use App\Enums\LearningAudienceType;
use App\Models\Document;
use App\Models\GlossaryTerm;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\LearningStep;
use App\Models\Tenant;
use Database\Seeders\LearningPathPilotSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('seeds the pilot once, links archive documents by title and replaces audiences on rerun', function (): void {
    $spk = InstitutionType::query()->where('slug', 'spk')->first() ?? InstitutionType::factory()->create(['slug' => 'spk']);
    $statute = Document::factory()->create(['title' => 'VU SA Įstatai (nuo 2025 m.)']);
    $tenant = Tenant::query()->where('type', 'padalinys')->firstOrFail();
    $unitRegulations = Document::factory()->create(['title' => "{$tenant->shortname} nuostatai"]);

    $this->seed(LearningPathPilotSeeder::class);

    $step = KnowledgeItem::query()->where('key', 'pilot_vusa_statute')->sole();
    expect($step->sourceFor('lt')->document_id)->toBe($statute->id);
    $forSpk = fn ($query) => $query->where('type', 'institution_type')->where('source_id', (string) $spk->id);
    expect(LearningStep::query()->whereHas('audiences', $forSpk)->count())->toBe(7)
        ->and(KnowledgeItem::query()->whereHas('audiences', $forSpk)->count())->toBe(2);
    $unitStep = KnowledgeItem::query()->where('key', "pilot_unit_regulations_{$tenant->alias}")->sole();
    expect($unitStep->audiences->sole()->type)->toBe(LearningAudienceType::Everyone);
    expect($unitStep->audiences->sole()->tenant_id)->toBe($tenant->id);
    expect($step->section->getTranslation('title', 'en'))->toBe('Introductory training');
    expect($step->audiences)->toHaveCount(2);
    expect($unitStep->sourceFor('lt')->document_id)->toBe($unitRegulations->id);
    expect(KnowledgeItem::query()->where('key', 'pilot_spk_regulations')->sole()->glossaryTerms)->toHaveCount(5);

    $step->update(['order' => 99, 'title' => ['lt' => 'Kuratoriaus pavadinimas']]);
    $counts = [LearningStep::query()->count(), KnowledgeItem::query()->count(), GlossaryTerm::query()->count()];
    $this->seed(LearningPathPilotSeeder::class);

    expect([LearningStep::query()->count(), KnowledgeItem::query()->count(), GlossaryTerm::query()->count()])->toBe($counts);
    expect($step->fresh()->order)->toBe(10);
    expect($step->fresh()->getTranslation('title', 'lt'))->toBe('VU SA įstatai');
    expect($step->fresh()->audiences)->toHaveCount(2);
});
