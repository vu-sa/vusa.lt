<?php

use App\Actions\ResolveLearningPath;
use App\Enums\LearningLayer;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStage;
use App\Enums\ResponsibilityPurpose;
use App\Models\Document;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\HandoverNote;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\LearningStep;
use App\Models\LearningStepProgress;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function learningRep(?Institution $institution = null, ?string $start = null): array
{
    $user = User::factory()->create(['profile_photo_path' => null]);
    $duty = Duty::factory()->for($institution ?? Institution::factory()->create())->create();
    grantPurpose($duty, ResponsibilityPurpose::StudentRepresentation);
    Dutiable::factory()->forUser($user)->create(['duty_id' => $duty->id, 'start_date' => $start ?? today()->subDays(10), 'end_date' => today()->addYear()]);

    return [$user, $duty];
}

test('a representative receives inherited institution and own tenant layers only', function (): void {
    $this->freezeTime();
    $parent = InstitutionType::factory()->create();
    $type = InstitutionType::factory()->create(['parent_id' => $parent->id]);
    $tenant = Tenant::query()->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create();
    $institution->types()->sync([$type->id]);
    [$user] = learningRep($institution);
    $platform = LearningStep::factory()->create();
    $foundation = LearningStep::factory()->create(['layer' => LearningLayer::Foundation]);
    $inherited = LearningStep::factory()->forInstitutionType($parent)->create();
    $own = LearningStep::factory()->forTenant($tenant)->create();
    $foreign = LearningStep::factory()->forTenant()->create();
    $unrelated = LearningStep::factory()->forInstitutionType()->create();
    $inactive = LearningStep::factory()->create(['is_active' => false]);
    $path = ResolveLearningPath::execute($user);
    $ids = $path->steps->pluck('step.id')->all();

    expect($ids)->toContain($platform->id, $foundation->id, $inherited->id, $own->id)
        ->not->toContain($foreign->id, $unrelated->id, $inactive->id);
    expect($path->currentStage)->toBe(LearningStage::FirstMonths);
});

test('a non representative receives platform and inherited duty type steps', function (): void {
    $this->freezeTime();
    $user = User::factory()->create(['profile_photo_path' => null]);
    $duty = Duty::factory()->create();
    $parent = DutyType::factory()->create();
    $type = DutyType::factory()->create(['parent_id' => $parent->id]);
    $duty->types()->attach($type);
    Dutiable::factory()->forUser($user)->create(['duty_id' => $duty->id, 'start_date' => today(), 'end_date' => null]);
    $platform = LearningStep::factory()->create();
    $ownType = LearningStep::factory()->forDutyType($parent)->create();
    $foundation = LearningStep::factory()->create(['layer' => LearningLayer::Foundation]);
    $path = ResolveLearningPath::execute($user);

    expect($path->steps->pluck('step.id')->all())->toContain($platform->id, $ownType->id)->not->toContain($foundation->id);
    expect($path->currentStage)->toBeNull();
});

test('an incoming representative gets foundation before their term starts', function (): void {
    $this->freezeTime();
    [$user] = learningRep(start: today()->addMonth()->toDateString());
    $step = LearningStep::factory()->create(['layer' => LearningLayer::Foundation]);
    $path = ResolveLearningPath::execute($user);

    expect($path->currentStage)->toBe(LearningStage::BeforeStart);
    expect($path->contains($step->id))->toBeTrue();
});

test('per term progress expires while ordinary progress and skips remain resolved', function (): void {
    $this->freezeTime();
    [$user] = learningRep();
    $term = LearningStep::factory()->create(['per_term' => true]);
    $done = LearningStep::factory()->create();
    $skip = LearningStep::factory()->create();
    foreach ([$term, $done, $skip] as $step) {
        LearningStepProgress::factory()->for($user)->create([
            'learning_step_id' => $step->id, 'completed_at' => today()->subMonth(),
            'status' => $step->is($skip) ? LearningProgressStatus::Skipped : LearningProgressStatus::Done,
        ]);
    }
    $path = ResolveLearningPath::execute($user);
    $states = $path->steps->keyBy('step.id');

    expect($states[$term->id]->state)->toBe('pending');
    expect($states[$done->id]->state)->toBe('done');
    expect($states[$skip->id]->state)->toBe('skipped');
    expect($path->totals()['allDone'])->toBe(2);
});

test('received handover unlocks reading and meeting preparation stays outside progress', function (): void {
    $this->freezeTime();
    [$user, $duty] = learningRep();
    $read = LearningStep::query()->where('key', 'read_handover')->firstOrFail();
    $type = InstitutionType::factory()->create();
    $duty->institution->types()->attach($type);
    $prep = LearningStep::factory()->meetingPrep($type)->create();
    expect(ResolveLearningPath::execute($user)->contains($read->id))->toBeFalse();
    HandoverNote::factory()->for($duty)->create();
    $user->unsetRelation('authorization_duties');
    $path = ResolveLearningPath::execute($user);

    expect($path->contains($read->id))->toBeTrue();
    expect($path->steps->pluck('step.id')->all())->not->toContain($prep->id);
    expect($path->meetingPrep->pluck('step.id')->all())->toContain($prep->id);
});

test('knowledge becomes updated when the SharePoint source changes after completion', function (): void {
    $this->freezeTime();
    $user = User::factory()->create(['profile_photo_path' => null]);
    $step = LearningStep::factory()->create();
    $document = Document::factory()->create(['sharepoint_modified_at' => now()]);
    $step->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    LearningStepProgress::factory()->for($user)->create(['learning_step_id' => $step->id, 'completed_at' => now()->subDay()]);
    $path = ResolveLearningPath::execute($user);

    expect($path->steps->firstWhere('step.id', $step->id)->state)->toBe('updated');
});

test('only handover written during the current term automatically completes writing', function (): void {
    $this->freezeTime();
    [$user, $duty] = learningRep();
    $note = HandoverNote::factory()->for($duty)->for($user, 'author')->create(['created_at' => today()->subMonths(2)]);
    $key = fn () => ResolveLearningPath::execute($user)->steps->first(fn ($item) => $item->step->key === 'write_handover');
    expect($key()->state)->toBe('pending');
    $note->forceFill(['created_at' => now()])->save();
    expect($key()->state)->toBe('done');
    expect($key()->autoCompleted)->toBeTrue();
});

test('a handover written for another duty does not complete writing for the rep seat', function (): void {
    $this->freezeTime();
    [$user] = learningRep();
    HandoverNote::factory()->for(Duty::factory()->create())->for($user, 'author')->create(['created_at' => now()]);
    $item = ResolveLearningPath::execute($user)->steps->first(fn ($item) => $item->step->key === 'write_handover');
    expect($item->state)->toBe('pending');
});

test('detected completion is read time only and a manual tick also completes a system step', function (): void {
    $first = User::factory()->create(['profile_photo_path' => '/avatar.jpg']);
    $second = User::factory()->create(['profile_photo_path' => null]);
    $photo = LearningStep::query()->where('key', 'profile_photo')->firstOrFail();
    LearningStepProgress::factory()->for($second)->create(['learning_step_id' => $photo->id]);
    $paths = ResolveLearningPath::forUsers(collect([$first, $second]));
    $automatic = $paths[$first->id]->steps->firstWhere('step.id', $photo->id);
    $manual = $paths[$second->id]->steps->firstWhere('step.id', $photo->id);

    expect($automatic->state)->toBe('done');
    expect($automatic->autoCompleted)->toBeTrue();
    expect($manual->state)->toBe('done');
    expect($manual->autoCompleted)->toBeFalse();
    $this->assertDatabaseMissing('learning_step_progress', ['user_id' => $first->id, 'learning_step_id' => $photo->id]);
});

test('term stages follow the first months and final forty five day boundaries', function (int $startedDaysAgo, int $endsInDays, LearningStage $stage): void {
    $this->freezeTime();
    [$user, $duty] = learningRep(start: today()->subDays($startedDaysAgo)->toDateString());
    $term = $user->dutiables()->where('duty_id', $duty->id)->firstOrFail();
    $term->update(['end_date' => today()->addDays($endsInDays)]);
    $user->unsetRelation('authorization_duties');
    expect(ResolveLearningPath::execute($user)->currentStage)->toBe($stage);
})->with([
    'sixtieth day' => [60, 200, LearningStage::FirstMonths],
    'short term first months' => [10, 20, LearningStage::FirstMonths],
    'sixty first day' => [61, 200, LearningStage::WholeTerm],
    'forty six days left' => [100, 46, LearningStage::WholeTerm],
    'forty five days left' => [100, 45, LearningStage::TermEnd],
]);
