<?php

use App\Actions\ResolveLearningPath;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Cadence;
use App\Models\Document;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\HandoverNote;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\LearningStep;
use App\Models\LearningStepAudience;
use App\Models\LearningStepProgress;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Learning\SystemSteps\WriteHandoverDetector;
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

test('a representative receives inherited institution and own tenant audiences only', function (): void {
    $this->freezeTime();
    $parent = InstitutionType::factory()->create();
    $type = InstitutionType::factory()->create(['parent_id' => $parent->id]);
    $tenant = Tenant::query()->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create();
    $institution->types()->sync([$type->id]);
    [$user] = learningRep($institution);
    $platform = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create();
    $foundation = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->audience(['type' => LearningAudienceType::Purpose, 'source_id' => ResponsibilityPurpose::StudentRepresentation->value])->create();
    $inherited = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->forInstitutionType($parent)->create();
    $own = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->forTenant($tenant)->create();
    $foreign = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->forTenant()->create();
    $unrelated = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->forInstitutionType()->create();
    $inactive = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create(['is_active' => false]);
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
    $platform = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create();
    $ownType = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->forDutyType($parent)->create();
    $foundation = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->audience(['type' => LearningAudienceType::Purpose, 'source_id' => ResponsibilityPurpose::StudentRepresentation->value])->create();
    $path = ResolveLearningPath::execute($user);

    expect($path->steps->pluck('step.id')->all())->toContain($platform->id, $ownType->id)->not->toContain($foundation->id);
    expect($path->currentStage)->toBeNull();
});

test('an incoming representative gets foundation before their term starts', function (): void {
    $this->freezeTime();
    [$user] = learningRep(start: today()->addMonth()->toDateString());
    $step = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->audience(['type' => LearningAudienceType::Purpose, 'source_id' => ResponsibilityPurpose::StudentRepresentation->value])->create();
    $path = ResolveLearningPath::execute($user);

    expect($path->currentStage)->toBe(LearningStage::BeforeStart);
    expect($path->contains($step->id))->toBeTrue();
});

test('per term progress expires while ordinary progress and skips remain resolved', function (): void {
    $this->freezeTime();
    [$user] = learningRep();
    $term = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create(['per_term' => true]);
    $done = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create();
    $skip = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create();
    foreach ([$term, $done, $skip] as $step) {
        LearningStepProgress::factory()->for($user)->create([
            'learning_step_id' => $step->id, 'completed_at' => today()->subMonths(6),
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

test('an incoming rep keeps per term progress made before the term starts', function (): void {
    $this->freezeTime();
    [$user] = learningRep(start: today()->addMonth()->toDateString());
    $step = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create(['per_term' => true]);
    LearningStepProgress::factory()->for($user)->create(['learning_step_id' => $step->id, 'completed_at' => now(), 'status' => LearningProgressStatus::Done]);
    expect(ResolveLearningPath::execute($user)->steps->firstWhere('step.id', $step->id)->state)->toBe('done');
});

test('received handover unlocks reading and meeting preparation stays outside progress', function (): void {
    $this->freezeTime();
    [$user, $duty] = learningRep();
    $read = LearningStep::query()->where('key', 'read_handover')->firstOrFail();
    $type = InstitutionType::factory()->create();
    $duty->institution->types()->attach($type);
    $prep = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->meetingPrep($type)->create();
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

    expect($path->documents->firstWhere('step.id', $step->id)->state)->toBe('updated');
});

test('only handover written during the current term automatically completes writing', function (): void {
    $this->freezeTime();
    [$user, $duty] = learningRep();
    $note = HandoverNote::factory()->for($duty)->for($user, 'author')->create(['created_at' => today()->subMonths(6)]);
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
    $first = User::factory()->create(['notification_preferences' => ['digest' => 'daily']]);
    $second = User::factory()->create(['notification_preferences' => null]);
    $notification = LearningStep::query()->where('key', 'notification_settings')->firstOrFail();
    LearningStepProgress::factory()->for($second)->create(['learning_step_id' => $notification->id]);
    $paths = ResolveLearningPath::forUsers(collect([$first, $second]));
    $automatic = $paths[$first->id]->steps->firstWhere('step.id', $notification->id);
    $manual = $paths[$second->id]->steps->firstWhere('step.id', $notification->id);

    expect($automatic->state)->toBe('done');
    expect($automatic->autoCompleted)->toBeTrue();
    expect($manual->state)->toBe('done');
    expect($manual->autoCompleted)->toBeFalse();
    $this->assertDatabaseMissing('learning_step_progress', ['user_id' => $first->id, 'learning_step_id' => $notification->id]);
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

test('handover completion requires this rep duties cadence even when another cadence note was just edited', function (): void {
    $this->freezeTime();
    [$user, $duty] = learningRep();
    $old = Cadence::factory()->create(['institution_id' => $duty->institution_id, 'start_date' => today()->subYears(2), 'end_date' => today()->subYear()]);
    $current = Cadence::factory()->create(['institution_id' => $duty->institution_id, 'start_date' => today()->subMonth(), 'end_date' => today()->addYear()]);
    HandoverNote::factory()->for($duty)->for($user, 'author')->create(['cadence_id' => $old->id]);
    $item = fn () => ResolveLearningPath::execute($user)->steps->first(fn ($step) => $step->step->key === 'write_handover');
    expect($item()->state)->toBe('pending');
    expect(app(WriteHandoverDetector::class)->doneFor(collect([$user->id])))->toBeEmpty();
    $note = HandoverNote::factory()->for($duty)->for($user, 'author')->create(['cadence_id' => $current->id]);
    expect($item()->autoCompleted)->toBeTrue();
    expect(app(WriteHandoverDetector::class)->doneFor(collect([$user->id]))->all())->toBe([$user->id]);
    $note->delete();
    expect($item()->state)->toBe('pending');
});

test('a current cadence handover in the ninety day lead in counts for an incoming rep', function (int $daysBeforeStart, bool $completed): void {
    $this->freezeTime();
    [$user, $duty] = learningRep(start: today()->addMonth()->toDateString());
    $start = $user->dutiables()->where('duty_id', $duty->id)->sole()->start_date;
    $cadence = Cadence::factory()->create(['institution_id' => $duty->institution_id, 'start_date' => $start, 'end_date' => $start->copy()->addYear()]);
    HandoverNote::factory()->for($duty)->for($user, 'author')->create(['cadence_id' => $cadence->id, 'created_at' => $start->copy()->subDays($daysBeforeStart)]);
    $item = ResolveLearningPath::execute($user)->steps->first(fn ($step) => $step->step->key === 'write_handover');
    expect($item->autoCompleted)->toBe($completed);
    expect(app(WriteHandoverDetector::class)->doneFor(collect([$user->id]))->contains($user->id))->toBe($completed);
})->with(['ninety days' => [90, true], 'older' => [91, false]]);

test('the highest importance audience wins its stage and every matching note is returned', function (): void {
    [$user, $duty] = learningRep();
    $step = LearningStep::factory()->withoutAudiences()->create(['kind' => LearningStepKind::Action]);
    LearningStepAudience::factory()->for($step, 'step')->everyone()->create([
        'importance' => 'recommended', 'stage' => 'before_start', 'description' => ['lt' => 'Visiems nariams', 'en' => 'For all members'],
    ]);
    $winner = LearningStepAudience::factory()->for($step, 'step')->duty($duty)->create([
        'importance' => 'essential', 'stage' => 'whole_term', 'description' => ['lt' => 'Tavo pareigoms', 'en' => 'For your duty'],
    ]);
    $item = ResolveLearningPath::execute($user)->steps->firstWhere('step.id', $step->id);

    expect($item->importance)->toBe(LearningImportance::Essential);
    expect($item->stage)->toBe(LearningStage::WholeTerm);
    expect($item->audienceId)->toBe($winner->id);
    expect(array_column($item->audienceNotes, 'description'))->toBe(['Visiems nariams', 'Tavo pareigoms']);
    expect($item->audienceLabel)->toBe($winner->label('lt'));
});

test('equal importance chooses the earliest stage then lowest audience order', function (): void {
    [$user] = learningRep();
    $step = LearningStep::factory()->withoutAudiences()->create();
    foreach ([['whole_term', 0], ['before_start', 5], ['before_start', 1]] as [$stage, $order]) {
        $winner = LearningStepAudience::factory()->for($step, 'step')->create(['stage' => $stage, 'order' => $order]);
    }
    expect(ResolveLearningPath::execute($user)->documents->firstWhere('step.id', $step->id)->audienceId)->toBe($winner->id);
});

test('tenant filtering requires the matching fact to come from a duty in that tenant', function (): void {
    $own = Tenant::query()->firstOrFail();
    $other = Tenant::factory()->create();
    $type = InstitutionType::factory()->create();
    $institution = Institution::factory()->for($own)->create();
    $institution->types()->attach($type);
    [$user] = learningRep($institution);
    $unrelated = Duty::factory()->for(Institution::factory()->for($other))->create();
    Dutiable::factory()->forUser($user)->forDuty($unrelated)->create(['end_date' => null]);
    $matching = LearningStep::factory()->withoutAudiences()->create();
    LearningStepAudience::factory()->for($matching, 'step')->institutionType($type)->create(['tenant_id' => $own->id]);
    $foreign = LearningStep::factory()->withoutAudiences()->create();
    LearningStepAudience::factory()->for($foreign, 'step')->institutionType($type)->create(['tenant_id' => $other->id]);
    $path = ResolveLearningPath::execute($user);

    expect($path->contains($matching->id))->toBeTrue();
    expect($path->contains($foreign->id))->toBeFalse();
});

test('responsibility ids and system purposes match the holders of the role', function (): void {
    [$holder, $duty] = learningRep();
    $role = $duty->roles()->where('purpose', ResponsibilityPurpose::StudentRepresentation->value)->sole();
    $byRole = LearningStep::factory()->withoutAudiences()->create();
    LearningStepAudience::factory()->for($byRole, 'step')->responsibility($role)->create();
    $byPurpose = LearningStep::factory()->withoutAudiences()->create();
    LearningStepAudience::factory()->for($byPurpose, 'step')->purpose()->create();
    $outsider = User::factory()->create();
    $paths = ResolveLearningPath::forUsers(collect([$holder, $outsider]));

    expect($paths[$holder->id]->contains($byRole->id))->toBeTrue();
    expect($paths[$holder->id]->contains($byPurpose->id))->toBeTrue();
    expect($paths[$outsider->id]->contains($byRole->id))->toBeFalse();
    expect($paths[$outsider->id]->contains($byPurpose->id))->toBeFalse();
});

test('retired system tasks are absent from the path', function (): void {
    $user = User::factory()->create(['profile_photo_path' => '/avatar.jpg']);
    $path = ResolveLearningPath::execute($user);

    expect($path->steps->pluck('step.key'))->not->toContain('profile_photo', 'meet_institution_contacts')
        ->toContain('review_contacts');
    $this->assertDatabaseMissing('learning_steps', ['key' => 'profile_photo']);
    $this->assertDatabaseMissing('learning_steps', ['key' => 'meet_institution_contacts']);
});
