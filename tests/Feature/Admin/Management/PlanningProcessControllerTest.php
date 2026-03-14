<?php

use App\Enums\ApprovalDecision;
use App\Models\Approval;
use App\Models\PlanningProcess;
use App\Models\PlanningStageDeadline;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();

    $this->user = makeUser($this->tenant);
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    $this->superAdmin = makeAdminUser();

    $this->planningProcess = PlanningProcess::factory()->create([
        'tenant_id' => $this->tenant->id,
        'academic_year_start' => 2026,
    ]);

    $this->otherProcess = PlanningProcess::factory()->create([
        'tenant_id' => $this->otherTenant->id,
        'academic_year_start' => 2026,
    ]);
});

describe('unauthorized access', function () {
    test('cannot index planning processes', function () {
        asUser($this->user)->get(route('planavimai.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function () {
        asUser($this->user)->get(route('planavimai.create'))
            ->assertStatus(403);
    });

    test('cannot store planning process', function () {
        asUser($this->user)->post(route('planavimai.store'), [
            'tenant_id' => $this->tenant->id,
            'academic_year_start' => 2025,
        ])->assertStatus(403);
    });

    test('cannot delete planning process', function () {
        asUser($this->user)->delete(route('planavimai.destroy', $this->planningProcess))
            ->assertStatus(403);
    });
});

describe('authorized access', function () {
    test('super admin can index planning processes', function () {
        asUser($this->superAdmin)->get(route('planavimai.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlanningProcesses/IndexPlanningProcess')
                ->has('data')
                ->has('meta')
            );
    });

    test('super admin can access create page', function () {
        asUser($this->superAdmin)->get(route('planavimai.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlanningProcesses/CreatePlanningProcess')
                ->has('tenants')
            );
    });

    test('super admin can store planning process', function () {
        $newTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();

        asUser($this->superAdmin)->post(route('planavimai.store'), [
            'tenant_id' => $newTenant->id,
            'academic_year_start' => 2030,
        ])->assertRedirect(route('planavimai.index'));

        expect(PlanningProcess::where('tenant_id', $newTenant->id)->where('academic_year_start', 2030)->exists())
            ->toBeTrue();
    });

    test('super admin can view planning process', function () {
        asUser($this->superAdmin)->get(route('planavimai.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlanningProcesses/ShowPlanningProcess')
                ->has('planningProcess')
            );
    });

    test('super admin can update planning process', function () {
        asUser($this->superAdmin)->patch(route('planavimai.update', $this->planningProcess), [
            'expectations_text' => 'Updated expectations',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->expectations_text)->toBe('Updated expectations');
    });

    test('super admin can delete planning process', function () {
        asUser($this->superAdmin)->delete(route('planavimai.destroy', $this->planningProcess))
            ->assertRedirect(route('planavimai.index'));

        expect(PlanningProcess::withTrashed()->find($this->planningProcess->id)->deleted_at)
            ->not->toBeNull();
    });
});

describe('moderator access', function () {
    beforeEach(function () {
        $this->moderator = makeUser($this->otherTenant);
        $this->planningProcess->update(['moderator_user_id' => $this->moderator->id]);
    });

    test('moderator can view their assigned process', function () {
        asUser($this->moderator)->get(route('planavimai.show', $this->planningProcess))
            ->assertStatus(200);
    });

    test('moderator can update their assigned process', function () {
        asUser($this->moderator)->patch(route('planavimai.update', $this->planningProcess), [
            'goal_text' => 'Moderator updated goal',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->goal_text)->toBe('Moderator updated goal');
    });

    test('moderator cannot approve their own goal', function () {
        $this->planningProcess->update([
            'current_stage' => 2,
            'expectations_submitted_at' => now(),
        ]);

        asUser($this->moderator)->patch(route('planavimai.updateGoal', $this->planningProcess), [
            'goal_text' => 'Our goal',
            'goal_approved_at' => now()->toISOString(),
        ])->assertStatus(403);

        expect($this->planningProcess->fresh()->goal_approved_at)->toBeNull();
    });

    test('moderator cannot approve documents on their own process', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
        ]);

        asUser($this->moderator)->patch(route('planavimai.approveDocument', $this->planningProcess), [
            'collection' => 'tip_document',
        ])->assertStatus(403);

        expect($this->planningProcess->fresh()->tip_approved_at)->toBeNull();
    });

    test('moderator cannot advance stage on their own process', function () {
        $this->planningProcess->update([
            'expectations_text' => 'Some expectations',
            'expectations_submitted_at' => now(),
        ]);

        asUser($this->moderator)->patch(route('planavimai.advanceStage', $this->planningProcess))
            ->assertStatus(403);

        expect($this->planningProcess->fresh()->current_stage)->toBe(1);
    });

    test('moderator can update goal text without approving', function () {
        $this->planningProcess->update(['current_stage' => 2, 'expectations_submitted_at' => now()]);

        asUser($this->moderator)->patch(route('planavimai.updateGoal', $this->planningProcess), [
            'goal_text' => 'Updated by moderator',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->goal_text)->toBe('Updated by moderator');
    });
});

describe('stage advancement', function () {
    test('cannot advance stage if current stage is not complete', function () {
        asUser($this->superAdmin)->patch(route('planavimai.advanceStage', $this->planningProcess))
            ->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(1);
    });

    test('can advance stage when current stage is complete', function () {
        $this->planningProcess->update([
            'expectations_text' => 'Some expectations',
            'expectations_submitted_at' => now(),
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.advanceStage', $this->planningProcess))
            ->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(2);
    });
});

describe('auto stage advancement', function () {
    test('submitting expectations auto-advances from stage 1 to stage 2', function () {
        expect($this->planningProcess->current_stage)->toBe(1);

        asUser($this->superAdmin)->patch(route('planavimai.update', $this->planningProcess), [
            'expectations_text' => 'My expectations',
            'expectations_submitted_at' => now()->toISOString(),
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(2);
    });

    test('approving goal auto-advances from stage 2 to stage 3', function () {
        $this->planningProcess->update([
            'current_stage' => 2,
            'expectations_submitted_at' => now(),
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.updateGoal', $this->planningProcess), [
            'goal_text' => 'Our annual goal',
            'goal_approved_at' => now()->toISOString(),
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(3);
    });

    test('approving both documents auto-advances from stage 3 to stage 4', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
        ]);

        // Approve TIP - should not advance yet (MVP still missing)
        asUser($this->superAdmin)->patch(route('planavimai.approveDocument', $this->planningProcess), [
            'collection' => 'tip_document',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(3);

        // Approve MVP - now both are approved, should advance
        asUser($this->superAdmin)->patch(route('planavimai.approveDocument', $this->planningProcess), [
            'collection' => 'mvp_document',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(4);
    });

    test('submitting reflection auto-advances from stage 5 and locks process', function () {
        $this->planningProcess->update([
            'current_stage' => 5,
            'expectations_submitted_at' => now(),
            'goal_approved_at' => now(),
            'tip_approved_at' => now(),
            'mvp_approved_at' => now(),
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.update', $this->planningProcess), [
            'reflection_text' => 'Year-end reflection',
            'reflection_submitted_at' => now()->toISOString(),
        ])->assertRedirect();

        $fresh = $this->planningProcess->fresh();
        expect($fresh->current_stage)->toBe(6);
        expect($fresh->locked_at)->not->toBeNull();
    });

    test('does not auto-advance when stage completion criteria not met', function () {
        // Update expectations text but don't submit (no expectations_submitted_at)
        asUser($this->superAdmin)->patch(route('planavimai.update', $this->planningProcess), [
            'expectations_text' => 'Draft expectations',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(1);
    });
});

describe('tenant isolation', function () {
    test('admin cannot update process from different tenant', function () {
        $originalText = $this->otherProcess->expectations_text;

        asUser($this->admin)->patch(route('planavimai.update', $this->otherProcess), [
            'expectations_text' => 'Injected text',
        ])->assertStatus(403);

        expect($this->otherProcess->fresh()->expectations_text)->toBe($originalText);
    });
});

describe('model relationships', function () {
    test('planning process has correct relationships', function () {
        $process = PlanningProcess::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_start' => 2025,
        ]);

        expect($process->tenant)->not->toBeNull();
        expect($process->tenant->id)->toBe($this->tenant->id);
    });

    test('planning process stage helper methods work correctly', function () {
        $process = PlanningProcess::factory()->create([
            'tenant_id' => $this->tenant->id,
            'academic_year_start' => 2024,
        ]);

        expect($process->isStageComplete(1))->toBeFalse();
        expect($process->canAdvanceToStage(2))->toBeFalse();

        $process->update([
            'expectations_submitted_at' => now(),
        ]);

        expect($process->fresh()->isStageComplete(1))->toBeTrue();
        expect($process->fresh()->canAdvanceToStage(2))->toBeTrue();
    });
});

describe('deadlines in show page', function () {
    test('show page includes deadlines for matching academic year', function () {
        PlanningStageDeadline::factory()->create([
            'academic_year_start' => 2026,
            'stage' => 1,
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-09-30',
        ]);
        PlanningStageDeadline::factory()->create([
            'academic_year_start' => 2026,
            'stage' => 2,
            'starts_at' => '2026-10-01',
            'ends_at' => '2026-10-31',
        ]);

        asUser($this->superAdmin)->get(route('planavimai.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlanningProcesses/ShowPlanningProcess')
                ->has('deadlines', 2)
                ->where('deadlines.0.stage', 1)
                ->where('deadlines.1.stage', 2)
            );
    });

    test('show page returns empty deadlines when none exist', function () {
        asUser($this->superAdmin)->get(route('planavimai.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('deadlines', 0)
            );
    });

    test('show page only returns deadlines for matching academic year', function () {
        PlanningStageDeadline::factory()->create([
            'academic_year_start' => 2026,
            'stage' => 1,
        ]);
        PlanningStageDeadline::factory()->create([
            'academic_year_start' => 2025,
            'stage' => 1,
        ]);

        asUser($this->superAdmin)->get(route('planavimai.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('deadlines', 1)
                ->where('deadlines.0.academic_year_start', 2026)
            );
    });
});

describe('document rejection', function () {
    test('coordinator can reject a document with feedback', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.rejectDocument', $this->planningProcess), [
            'collection' => 'tip_document',
            'notes' => 'The document needs more detail in section 2.',
        ])->assertRedirect();

        // Approval record created with rejection
        $approval = Approval::where('approvable_type', PlanningProcess::class)
            ->where('approvable_id', $this->planningProcess->id)
            ->where('context', 'tip_document')
            ->latest()
            ->first();

        expect($approval)->not->toBeNull();
        expect($approval->decision)->toBe(ApprovalDecision::Rejected);
        expect($approval->notes)->toBe('The document needs more detail in section 2.');
    });

    test('rejecting a document clears approval timestamps', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
            'tip_approved_at' => now(),
            'tip_approved_by' => $this->superAdmin->id,
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.rejectDocument', $this->planningProcess), [
            'collection' => 'tip_document',
            'notes' => 'Needs revision.',
        ])->assertRedirect();

        $fresh = $this->planningProcess->fresh();
        expect($fresh->tip_approved_at)->toBeNull();
        expect($fresh->tip_approved_by)->toBeNull();
    });

    test('rejection requires notes', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.rejectDocument', $this->planningProcess), [
            'collection' => 'tip_document',
            'notes' => '',
        ])->assertSessionHasErrors('notes');
    });

    test('moderator cannot reject documents on their own process', function () {
        $moderator = makeUser($this->otherTenant);
        $this->planningProcess->update([
            'moderator_user_id' => $moderator->id,
            'current_stage' => 3,
            'goal_approved_at' => now(),
        ]);

        asUser($moderator)->patch(route('planavimai.rejectDocument', $this->planningProcess), [
            'collection' => 'tip_document',
            'notes' => 'Reject this.',
        ])->assertStatus(403);
    });
});

describe('goal rejection', function () {
    test('coordinator can reject a goal with feedback', function () {
        $this->planningProcess->update([
            'current_stage' => 2,
            'expectations_submitted_at' => now(),
            'goal_text' => 'Our annual goal',
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.rejectGoal', $this->planningProcess), [
            'notes' => 'Goal is too vague, please be more specific.',
        ])->assertRedirect();

        $approval = Approval::where('approvable_type', PlanningProcess::class)
            ->where('approvable_id', $this->planningProcess->id)
            ->where('context', 'goal')
            ->latest()
            ->first();

        expect($approval)->not->toBeNull();
        expect($approval->decision)->toBe(ApprovalDecision::Rejected);
        expect($approval->notes)->toBe('Goal is too vague, please be more specific.');
        expect($this->planningProcess->fresh()->goal_approved_at)->toBeNull();
    });

    test('goal rejection requires notes', function () {
        $this->planningProcess->update([
            'current_stage' => 2,
            'expectations_submitted_at' => now(),
            'goal_text' => 'Some goal',
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.rejectGoal', $this->planningProcess), [
            'notes' => '',
        ])->assertSessionHasErrors('notes');
    });
});

describe('approval records', function () {
    test('approving a document creates an approval record', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.approveDocument', $this->planningProcess), [
            'collection' => 'tip_document',
        ])->assertRedirect();

        $approval = Approval::where('approvable_type', PlanningProcess::class)
            ->where('approvable_id', $this->planningProcess->id)
            ->where('context', 'tip_document')
            ->latest()
            ->first();

        expect($approval)->not->toBeNull();
        expect($approval->decision)->toBe(ApprovalDecision::Approved);
    });

    test('approving a goal creates an approval record', function () {
        $this->planningProcess->update([
            'current_stage' => 2,
            'expectations_submitted_at' => now(),
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.updateGoal', $this->planningProcess), [
            'goal_text' => 'Our annual goal',
            'goal_approved_at' => now()->toISOString(),
        ])->assertRedirect();

        $approval = Approval::where('approvable_type', PlanningProcess::class)
            ->where('approvable_id', $this->planningProcess->id)
            ->where('context', 'goal')
            ->latest()
            ->first();

        expect($approval)->not->toBeNull();
        expect($approval->decision)->toBe(ApprovalDecision::Approved);
    });

    test('show page includes approval history and field changes', function () {
        // Create an approval record
        Approval::create([
            'approvable_type' => PlanningProcess::class,
            'approvable_id' => $this->planningProcess->id,
            'user_id' => $this->superAdmin->id,
            'decision' => ApprovalDecision::Rejected,
            'step' => 1,
            'context' => 'tip_document',
            'notes' => 'Needs revision',
        ]);

        asUser($this->superAdmin)->get(route('planavimai.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('approvalHistory')
                ->has('fieldChanges')
                ->has('tipDocuments')
                ->has('mvpDocuments')
                ->has('canApprove')
            );
    });

    test('rejection then re-approval flow works correctly', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
        ]);

        // Reject TIP
        asUser($this->superAdmin)->patch(route('planavimai.rejectDocument', $this->planningProcess), [
            'collection' => 'tip_document',
            'notes' => 'Needs more detail.',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->tip_approved_at)->toBeNull();

        // Approve TIP after revision
        asUser($this->superAdmin)->patch(route('planavimai.approveDocument', $this->planningProcess), [
            'collection' => 'tip_document',
        ])->assertRedirect();

        $fresh = $this->planningProcess->fresh();
        expect($fresh->tip_approved_at)->not->toBeNull();

        // Should have 2 approval records (1 reject + 1 approve)
        $approvalCount = Approval::where('approvable_type', PlanningProcess::class)
            ->where('approvable_id', $this->planningProcess->id)
            ->where('context', 'tip_document')
            ->count();

        expect($approvalCount)->toBe(2);
    });
});

describe('reapproval on change', function () {
    test('changing goal text after approval clears approval', function () {
        $this->planningProcess->update([
            'current_stage' => 2,
            'expectations_submitted_at' => now(),
            'goal_text' => 'Original goal',
            'goal_approved_at' => now(),
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.updateGoal', $this->planningProcess), [
            'goal_text' => 'Modified goal',
        ])->assertRedirect();

        $fresh = $this->planningProcess->fresh();
        expect($fresh->goal_text)->toBe('Modified goal');
        expect($fresh->goal_approved_at)->toBeNull();
    });

    test('saving same goal text does not clear approval', function () {
        $this->planningProcess->update([
            'current_stage' => 2,
            'expectations_submitted_at' => now(),
            'goal_text' => 'Original goal',
            'goal_approved_at' => now(),
        ]);

        asUser($this->superAdmin)->patch(route('planavimai.updateGoal', $this->planningProcess), [
            'goal_text' => 'Original goal',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->goal_approved_at)->not->toBeNull();
    });

    test('uploading new document after approval clears approval', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
            'tip_approved_at' => now(),
            'tip_approved_by' => $this->superAdmin->id,
        ]);

        $file = UploadedFile::fake()->createWithContent('new_tip.pdf', '%PDF-1.4 test content');

        asUser($this->superAdmin)->post(route('planavimai.uploadDocument', $this->planningProcess), [
            'collection' => 'tip_document',
            'document' => $file,
        ])->assertRedirect();

        $fresh = $this->planningProcess->fresh();
        expect($fresh->tip_approved_at)->toBeNull();
        expect($fresh->tip_approved_by)->toBeNull();
        expect($fresh->tip_approved_media_id)->toBeNull();
    });

    test('uploading document to unapproved collection does not affect anything', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
        ]);

        $file = UploadedFile::fake()->createWithContent('tip.pdf', '%PDF-1.4 test content');

        asUser($this->superAdmin)->post(route('planavimai.uploadDocument', $this->planningProcess), [
            'collection' => 'tip_document',
            'document' => $file,
        ])->assertRedirect();

        // tip_approved_at was already null, should stay null
        expect($this->planningProcess->fresh()->tip_approved_at)->toBeNull();
    });
});
