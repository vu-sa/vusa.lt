<?php

use App\Actions\ResolveLearningPath;
use App\Enums\LearningAudienceType;
use App\Enums\LearningImportance;
use App\Enums\LearningProgressStatus;
use App\Enums\LearningStage;
use App\Enums\LearningStepKind;
use App\Enums\ResponsibilityPurpose;
use App\Models\Document;
use App\Models\Duty;
use App\Models\DutyType;
use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\KnowledgeItem;
use App\Models\LearningAudience;
use App\Models\LearningProgress;
use App\Models\LearningStep;
use App\Models\Pivots\Dutiable;
use App\Models\Tenant;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

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
    $ids = $path->steps->pluck('item.id')->all();

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

    expect($path->steps->pluck('item.id')->all())->toContain($platform->id, $ownType->id)->not->toContain($foundation->id);
    expect($path->currentStage)->toBeNull();
});

test('an incoming representative gets foundation before their term starts', function (): void {
    $this->freezeTime();
    [$user] = learningRep(start: today()->addMonth()->toDateString());
    $step = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->audience(['type' => LearningAudienceType::Purpose, 'source_id' => ResponsibilityPurpose::StudentRepresentation->value])->create();
    $path = ResolveLearningPath::execute($user);

    expect($path->currentStage)->toBe(LearningStage::BeforeStart);
    expect($path->contains($step))->toBeTrue();
});

test('completion and skips persist across term changes', function (): void {
    $this->freezeTime();
    [$user] = learningRep();
    $term = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create([]);
    $done = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create();
    $skip = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create();
    foreach ([$term, $done, $skip] as $step) {
        LearningProgress::factory()->for($user)->create([
            'progressable_type' => $step->getMorphClass(), 'progressable_id' => $step->id, 'completed_at' => today()->subMonths(6),
            'status' => $step->is($skip) ? LearningProgressStatus::Skipped : LearningProgressStatus::Done,
        ]);
    }
    $path = ResolveLearningPath::execute($user);
    $states = $path->steps->keyBy('item.id');

    expect($states[$term->id]->state)->toBe('done');
    expect($states[$done->id]->state)->toBe('done');
    expect($states[$skip->id]->state)->toBe('skipped');
    expect($path->totals()['allDone'])->toBe(3);
});

test('an incoming rep keeps completion made before the term starts', function (): void {
    $this->freezeTime();
    [$user] = learningRep(start: today()->addMonth()->toDateString());
    $step = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->create([]);
    LearningProgress::factory()->for($user)->create(['progressable_type' => $step->getMorphClass(), 'progressable_id' => $step->id, 'completed_at' => now(), 'status' => LearningProgressStatus::Done]);
    expect(ResolveLearningPath::execute($user)->steps->firstWhere('item.id', $step->id)->state)->toBe('done');
});

test('meeting preparation stays outside progress', function (): void {
    $this->freezeTime();
    [$user, $duty] = learningRep();
    $type = InstitutionType::factory()->create();
    $duty->institution->types()->attach($type);
    $prep = LearningStep::factory()->state(['kind' => LearningStepKind::Action])->meetingPrep($type)->create();
    $path = ResolveLearningPath::execute($user);

    expect($path->steps->pluck('item.id')->all())->not->toContain($prep->id);
    expect($path->meetingPrep->pluck('item.id')->all())->toContain($prep->id);
});

test('knowledge becomes updated when the SharePoint source changes after completion', function (): void {
    $this->freezeTime();
    $user = User::factory()->create(['profile_photo_path' => null]);
    $step = KnowledgeItem::factory()->create();
    $document = Document::factory()->create(['sharepoint_modified_at' => now()]);
    $step->sources()->create(['locale' => 'lt', 'document_id' => $document->id]);
    LearningProgress::factory()->for($user)->create(['progressable_type' => $step->getMorphClass(), 'progressable_id' => $step->id, 'completed_at' => now()->subDay()]);
    $path = ResolveLearningPath::execute($user);

    expect($path->knowledge->firstWhere('item.id', $step->id)->state)->toBe('updated');
});

test('detected completion is read time only and a manual tick also completes a system step', function (): void {
    $first = User::factory()->create();
    $second = User::factory()->create();
    DB::table('push_subscriptions')->insert(['subscribable_type' => MorphMap::alias(User::class), 'subscribable_id' => $first->id, 'endpoint' => 'https://push.example.test/one']);
    $notification = LearningStep::query()->where('key', 'browser_push')->firstOrFail();
    LearningProgress::factory()->for($second)->create(['progressable_type' => $notification->getMorphClass(), 'progressable_id' => $notification->id]);
    $paths = ResolveLearningPath::forUsers(collect([$first, $second]));
    $automatic = $paths[$first->id]->steps->firstWhere('item.id', $notification->id);
    $manual = $paths[$second->id]->steps->firstWhere('item.id', $notification->id);

    expect($automatic->state)->toBe('done');
    expect($automatic->autoCompleted)->toBeTrue();
    expect($manual->state)->toBe('done');
    expect($manual->autoCompleted)->toBeFalse();
    $this->assertDatabaseMissing('learning_progress', ['user_id' => $first->id, 'progressable_type' => $notification->getMorphClass(), 'progressable_id' => $notification->id]);
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

test('the highest importance audience wins its stage and every matching note is returned', function (): void {
    [$user, $duty] = learningRep();
    $step = LearningStep::factory()->withoutAudiences()->create(['kind' => LearningStepKind::Action]);
    LearningAudience::factory()->for($step, 'audienceable')->everyone()->create([
        'importance' => 'recommended', 'stage' => 'before_start', 'description' => ['lt' => 'Visiems nariams', 'en' => 'For all members'],
    ]);
    $winner = LearningAudience::factory()->for($step, 'audienceable')->duty($duty)->create([
        'importance' => 'essential', 'stage' => 'whole_term', 'description' => ['lt' => 'Tavo pareigoms', 'en' => 'For your duty'],
    ]);
    $item = ResolveLearningPath::execute($user)->steps->firstWhere('item.id', $step->id);

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
        $winner = LearningAudience::factory()->for($step, 'audienceable')->create(['stage' => $stage, 'order' => $order]);
    }
    expect(ResolveLearningPath::execute($user)->steps->firstWhere('item.id', $step->id)->audienceId)->toBe($winner->id);
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
    LearningAudience::factory()->for($matching, 'audienceable')->institutionType($type)->create(['tenant_id' => $own->id]);
    $foreign = LearningStep::factory()->withoutAudiences()->create();
    LearningAudience::factory()->for($foreign, 'audienceable')->institutionType($type)->create(['tenant_id' => $other->id]);
    $path = ResolveLearningPath::execute($user);

    expect($path->contains($matching))->toBeTrue();
    expect($path->contains($foreign))->toBeFalse();
});

test('responsibility ids and system purposes match the holders of the role', function (): void {
    [$holder, $duty] = learningRep();
    $role = $duty->roles()->where('purpose', ResponsibilityPurpose::StudentRepresentation->value)->sole();
    $byRole = LearningStep::factory()->withoutAudiences()->create();
    LearningAudience::factory()->for($byRole, 'audienceable')->responsibility($role)->create();
    $byPurpose = LearningStep::factory()->withoutAudiences()->create();
    LearningAudience::factory()->for($byPurpose, 'audienceable')->purpose()->create();
    $outsider = User::factory()->create();
    $paths = ResolveLearningPath::forUsers(collect([$holder, $outsider]));

    expect($paths[$holder->id]->contains($byRole))->toBeTrue();
    expect($paths[$holder->id]->contains($byPurpose))->toBeTrue();
    expect($paths[$outsider->id]->contains($byRole))->toBeFalse();
    expect($paths[$outsider->id]->contains($byPurpose))->toBeFalse();
});

test('retired system tasks are absent from the path', function (): void {
    $user = User::factory()->create(['profile_photo_path' => '/avatar.jpg']);
    $path = ResolveLearningPath::execute($user);

    expect($path->steps->pluck('item.key'))->not->toContain('profile_photo', 'meet_institution_contacts', 'follow_institution', 'read_handover')
        ->toContain('install_app');
    $this->assertDatabaseMissing('learning_steps', ['key' => 'follow_institution']);
    $this->assertDatabaseMissing('learning_steps', ['key' => 'read_handover']);
    $this->assertDatabaseMissing('learning_steps', ['key' => 'profile_photo']);
    $this->assertDatabaseMissing('learning_steps', ['key' => 'meet_institution_contacts']);
});
