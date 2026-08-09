<?php

use App\Enums\ApprovalDecision;
use App\Enums\SurveyStatus;
use App\Jobs\PublishSurveyToLimeSurveyJob;
use App\Models\ApprovalFlow;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

/**
 * TestSeeder does not run ApprovalFlowSeeder, so the flow this feature depends on has to
 * be created explicitly — which also documents exactly what the feature needs to work.
 */
function seedSurveyApprovalFlow(string $permission = 'surveys.update.*'): ApprovalFlow
{
    return ApprovalFlow::updateOrCreate(
        ['name' => 'survey_default', 'flowable_type' => Survey::class, 'flowable_id' => null],
        [
            'steps' => [['order' => 1, 'name' => 'Survey Approval', 'required_count' => 1, 'permission' => $permission]],
            'is_sequential' => true,
            'escalation_days' => 5,
        ],
    );
}

function makeUserWithPermission(Tenant $tenant, string $permission): User
{
    $user = makeUser($tenant);

    $role = Role::firstOrCreate(['name' => "Test Role {$permission}", 'guard_name' => 'web']);
    $role->syncPermissions(Permission::query()->where('name', $permission)->get());

    $user->duties()->first()->assignRole($role);

    return $user;
}

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->survey = Survey::factory()->create(['tenant_id' => $this->tenant->id]);
    SurveyQuestion::factory()->create(['survey_id' => $this->survey->id, 'title' => 'Q1']);

    $this->approvalService = app(ApprovalService::class);
});

describe('requesting approval', function (): void {
    test('moves a draft to pending approval', function (): void {
        seedSurveyApprovalFlow();
        $author = makeUserWithPermission($this->tenant, 'surveys.update.padalinys');

        asUser($author)->post(route('surveys.requestApproval', $this->survey))->assertRedirect();

        expect($this->survey->fresh()->status)->toBe(SurveyStatus::PendingApproval);
    });

    test('refuses a survey with no questions', function (): void {
        seedSurveyApprovalFlow();
        $empty = Survey::factory()->create(['tenant_id' => $this->tenant->id]);
        $author = makeUserWithPermission($this->tenant, 'surveys.update.padalinys');

        asUser($author)->post(route('surveys.requestApproval', $empty))
            ->assertRedirect()
            ->assertSessionHas('error');

        expect($empty->fresh()->status)->toBe(SurveyStatus::Draft);
    });

    test('refuses when no approval flow is configured', function (): void {
        $author = makeUserWithPermission($this->tenant, 'surveys.update.padalinys');

        asUser($author)->post(route('surveys.requestApproval', $this->survey))
            ->assertRedirect()
            ->assertSessionHas('error');

        expect($this->survey->fresh()->status)->toBe(SurveyStatus::Draft);
    });
});

describe('approving', function (): void {
    beforeEach(function (): void {
        seedSurveyApprovalFlow();
        $this->approver = makeUserWithPermission($this->tenant, 'surveys.update.*');
        $this->survey->forceFill(['status' => SurveyStatus::PendingApproval])->save();
    });

    test('marks the survey approved and queues the publish job', function (): void {
        Queue::fake();

        $this->approvalService->approve($this->survey, $this->approver, ApprovalDecision::Approved);

        expect($this->survey->fresh()->status)->toBe(SurveyStatus::Approved);
        expect($this->survey->fresh()->sync_status)->toBe('pending');

        Queue::assertPushed(PublishSurveyToLimeSurveyJob::class);
    });

    test('a rejection stops the survey without touching LimeSurvey', function (): void {
        Queue::fake();

        $this->approvalService->approve($this->survey, $this->approver, ApprovalDecision::Rejected);

        expect($this->survey->fresh()->status)->toBe(SurveyStatus::Rejected);

        Queue::assertNotPushed(PublishSurveyToLimeSurveyJob::class);
    });

    test('a user without the step permission cannot approve', function (): void {
        Queue::fake();

        $outsider = makeUserWithPermission($this->tenant, 'surveys.read.padalinys');

        expect(fn () => $this->approvalService->approve($this->survey, $outsider, ApprovalDecision::Approved))
            ->toThrow(InvalidArgumentException::class);

        expect($this->survey->fresh()->status)->toBe(SurveyStatus::PendingApproval);
        Queue::assertNotPushed(PublishSurveyToLimeSurveyJob::class);
    });

    test('a draft cannot be approved — the decision is not allowed in that state', function (): void {
        $draft = Survey::factory()->create(['tenant_id' => $this->tenant->id]);

        expect(fn () => $this->approvalService->approve($draft, $this->approver, ApprovalDecision::Approved))
            ->toThrow(InvalidArgumentException::class);
    });

    test('a rejected survey becomes editable again', function (): void {
        $this->approvalService->approve($this->survey, $this->approver, ApprovalDecision::Rejected);

        expect($this->survey->fresh()->isEditable())->toBeTrue();
    });
});
