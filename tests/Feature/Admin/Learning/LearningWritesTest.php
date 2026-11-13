<?php

use App\Actions\MergeDuties;
use App\Enums\LearningImportance;
use App\Enums\LearningStage;
use App\Models\Duty;
use App\Models\KnowledgeItem;
use App\Models\KnowledgeSection;
use App\Models\LearningAudience;
use App\Models\LearningStep;
use App\Models\Permission;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

test('system updates ignore authored fields and preserve their identity', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::query()->where('key', 'install_app')->firstOrFail();
    asUser($user)->patch(route('learningSteps.update', $step), [
        'audiences' => [['id' => $step->audiences->first()->id, 'stage' => 'whole_term', 'order' => 2]], 'order' => 6, 'is_active' => false,
        'title' => ['lt' => 'Injected'], 'key' => 'other', 'is_system' => false,
    ])->assertRedirect();
    $step->refresh();
    expect($step->key)->toBe('install_app')
        ->and($step->is_system)->toBeTrue()
        ->and($step->audiences->first()->stage)->toBe(LearningStage::WholeTerm)
        ->and($step->order)->toBe(6)
        ->and($step->is_active)->toBeFalse()
        ->and($step->getTranslations('title'))->toBeEmpty();
});

test('step writes reject incompatible targets and ambiguous sources', function (array $changes, string $error): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $payload = ['kind' => 'action', 'audiences' => [['type' => 'everyone', 'stage' => 'before_start', 'importance' => 'recommended']], 'title' => ['lt' => 'Žingsnis', 'en' => 'Step']];
    asUser($user)->postJson(route('learningSteps.store'), array_replace($payload, $changes))->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    'missing type' => [['audiences' => [['type' => 'institution_type', 'stage' => 'before_start', 'importance' => 'recommended']]], 'audiences.0.source_id'],
    'meeting prep stage' => [['kind' => 'meeting_prep'], 'audiences.0.stage'],
    'retired knowledge kind' => [['kind' => 'knowledge'], 'kind'],
    'missing title' => [['title' => ['en' => 'Step']], 'title.lt'],
    'empty source' => [['sources' => ['lt' => []]], 'sources'],
    'unsafe URL' => [['sources' => ['lt' => ['url' => 'javascript:alert(1)']]], 'sources'],
    'unsafe button link' => [['action_url' => ['lt' => 'javascript:alert(1)']], 'action_url.lt'],
    'protocol-relative button link' => [['action_url' => ['lt' => '//evil.test']], 'action_url.lt'],
]);

test('a step description keeps rich content such as a video but drops scripts', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $video = '<div data-youtube-video=""><iframe src="https://www.youtube-nocookie.com/embed/abc123"></iframe></div>';
    asUser($user)->post(route('learningSteps.store'), [
        'kind' => 'action', 'audiences' => [['type' => 'everyone', 'stage' => 'before_start', 'importance' => 'recommended']], 'title' => ['lt' => 'Žingsnis'],
        'description' => ['lt' => '<p><strong>Žiūrėk</strong></p>'.$video.'<script>alert(1)</script>'],
    ])->assertRedirect();

    $description = LearningStep::query()->latest('id')->firstOrFail()->getTranslation('description', 'lt');
    expect($description)->toContain('<strong>Žiūrėk</strong>', 'youtube-nocookie.com/embed/abc123')
        ->not->toContain('<script');
});

test('a task keeps its button only while it stays a task', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $payload = ['kind' => 'action', 'audiences' => [['type' => 'everyone', 'stage' => 'before_start', 'importance' => 'recommended']], 'title' => ['lt' => 'Žingsnis', 'en' => 'Step'],
        'action_label' => ['lt' => 'Atverti paskyrą', 'en' => 'Open account'], 'action_url' => ['lt' => '/mano/profile']];
    asUser($user)->post(route('learningSteps.store'), $payload)->assertRedirect();
    $step = LearningStep::query()->latest('id')->firstOrFail();
    expect($step->getTranslation('action_label', 'en'))->toBe('Open account')
        ->and($step->getTranslation('action_url', 'lt'))->toBe('/mano/profile');

    asUser($user)->patch(route('learningSteps.update', $step), [...$payload, 'kind' => 'meeting_prep', 'audiences' => [['type' => 'institution', 'source_id' => $user->authorization_duties()->firstOrFail()->institution->id, 'importance' => 'recommended']]])->assertRedirect();
    expect($step->fresh()->getTranslations('action_label'))->toBeEmpty()
        ->and($step->fresh()->getTranslations('action_url'))->toBeEmpty();
});

test('an article body keeps wiki links and their target id', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('knowledgeItems.create.*', 'web'));
    $target = KnowledgeItem::factory()->withoutAudiences()->create();
    asUser($user)->post(route('knowledgeItems.store'), [
        'title' => ['lt' => 'Senatas'], 'summary' => ['lt' => 'Akademinis organas.'],
        'description' => ['lt' => '<p>Žr. <a href="/mano/knowledge/items/'.$target->id.'" data-knowledge-id="'.$target->id.'" onclick="x()">Taryba</a></p>'],
    ])->assertRedirect();

    $article = KnowledgeItem::query()->where('created_by', $user->id)->sole();
    expect($article->getTranslation('description', 'lt'))->toContain('data-knowledge-id="'.$target->id.'"')->not->toContain('onclick')
        ->and($article->outgoingLinks()->sole()->is($target))->toBeTrue();
});

test('replacing audiences removes the previous target', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::factory()->forTenant(Tenant::query()->firstOrFail())->create();
    $old = $step->audiences->sole()->id;
    asUser($user)->patch(route('learningSteps.update', $step), [
        'kind' => 'action', 'title' => ['lt' => 'Veiksmas', 'en' => 'Action'],
        'audiences' => [['type' => 'everyone', 'stage' => 'whole_term', 'importance' => 'recommended']],
    ])->assertRedirect();
    $this->assertDatabaseMissing('learning_audiences', ['id' => $old]);
    expect($step->fresh()->audiences->sole()->tenant_id)->toBeNull();
});

test('members cannot mutate the library or the wiki', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $step = LearningStep::factory()->create();
    $article = KnowledgeItem::factory()->withoutAudiences()->create();
    asUser($user)->patch(route('learningSteps.update', $step), [])->assertForbidden();
    asUser($user)->delete(route('learningSteps.destroy', $step))->assertForbidden();
    asUser($user)->post(route('knowledgeItems.store'), [])->assertForbidden();
    asUser($user)->delete(route('knowledgeItems.destroy', $article))->assertForbidden();
});

test('a tenant curator cannot move an editable step into another unit', function (): void {
    $tenant = Tenant::query()->firstOrFail();
    $user = makeUser($tenant);
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.padalinys', 'web'));
    $step = LearningStep::factory()->forTenant($tenant)->create();
    $other = Tenant::factory()->create();
    asUser($user)->patch(route('learningSteps.update', $step), [
        'kind' => 'action', 'title' => ['lt' => 'Žingsnis', 'en' => 'Step'],
        'audiences' => [['type' => 'everyone', 'tenant_id' => $other->id, 'stage' => 'before_start', 'importance' => 'recommended']],
    ])->assertForbidden();
    expect($step->fresh()->audiences->sole()->tenant_id)->toBe($tenant->id);
});

test('an audience id from another step is refused and the transaction leaves both steps intact', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::factory()->create();
    $other = LearningStep::factory()->create();
    $foreign = $other->audiences->sole();
    asUser($user)->patch(route('learningSteps.update', $step), [
        'kind' => 'action', 'title' => ['lt' => 'Pakeista', 'en' => 'Changed'],
        'audiences' => [['id' => $foreign->id, 'type' => 'everyone', 'stage' => 'before_start', 'importance' => 'essential']],
    ])->assertForbidden();

    expect($step->fresh()->getTranslations('title'))->toBe($step->getTranslations('title'))
        ->and($foreign->fresh()->audienceable_id)->toBe($other->id)
        ->and($foreign->fresh()->importance)->toBe(LearningImportance::Recommended);
});

test('system audiences preserve identity importance and omitted rows', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.update.*', 'web'));
    $step = LearningStep::query()->where('key', 'meet_coordinator')->sole();
    $audience = $step->audiences->first();
    asUser($user)->patch(route('learningSteps.update', $step), [
        'audiences' => [['id' => $audience->id, 'stage' => 'whole_term', 'order' => 7]],
    ])->assertRedirect();
    expect($step->fresh()->audiences)->toHaveCount(2)
        ->and($audience->fresh()->stage)->toBe(LearningStage::WholeTerm)
        ->and($audience->fresh()->importance)->toBe(LearningImportance::Essential);
    asUser($user)->patchJson(route('learningSteps.update', $step), [
        'audiences' => [['id' => $audience->id, 'type' => 'everyone', 'importance' => 'optional']],
    ])->assertUnprocessable();
});

test('authored audience validation rejects empty targeting system purposes and missing stages', function (array $audiences, string $error): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    asUser($user)->postJson(route('learningSteps.store'), [
        'kind' => 'action', 'title' => ['lt' => 'Veiksmas', 'en' => 'Action'], 'audiences' => $audiences,
    ])->assertUnprocessable()->assertJsonValidationErrors($error);
})->with([
    'empty audiences' => [[], 'audiences'],
    'unknown purpose' => [[['type' => 'purpose', 'source_id' => 'nonexistent', 'importance' => 'essential', 'stage' => 'before_start']], 'audiences.0.source_id'],
    'missing stage' => [[['type' => 'everyone', 'importance' => 'recommended']], 'audiences.0.stage'],
]);

test('steps reject knowledge sections', function (): void {
    $user = makeUser(Tenant::query()->firstOrFail());
    $user->givePermissionTo(Permission::findOrCreate('learningSteps.create.*', 'web'));
    $section = KnowledgeSection::factory()->create();
    asUser($user)->postJson(route('learningSteps.store'), [
        'kind' => 'action', 'title' => ['lt' => 'Veiksmas', 'en' => 'Action'], 'knowledge_section_id' => $section->id,
        'audiences' => [['type' => 'everyone', 'importance' => 'recommended', 'stage' => 'before_start']],
    ])->assertUnprocessable()->assertJsonValidationErrors('knowledge_section_id');
});

test('merging duties preserves learning audiences targeting the merged seat', function (): void {
    $kept = Duty::factory()->create();
    $source = Duty::factory()->create();
    $audience = LearningAudience::factory()->duty($source)->create();
    MergeDuties::execute($kept, collect([$source]));
    expect($audience->fresh()->source_id)->toBe($kept->id);
});
