<?php

use App\Models\PlanningProcess;
use App\Models\PlanningStageDeadline;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        asUser($this->user)->get(route('planningProcesses.index'))
            ->assertStatus(403);
    });

    test('cannot access create page', function () {
        asUser($this->user)->get(route('planningProcesses.create'))
            ->assertStatus(403);
    });

    test('cannot store planning process', function () {
        asUser($this->user)->post(route('planningProcesses.store'), [
            'tenant_id' => $this->tenant->id,
            'academic_year_start' => 2025,
        ])->assertStatus(403);
    });

    test('cannot delete planning process', function () {
        asUser($this->user)->delete(route('planningProcesses.destroy', $this->planningProcess))
            ->assertStatus(403);
    });
});

describe('authorized access', function () {
    test('super admin can index planning processes', function () {
        asUser($this->superAdmin)->get(route('planningProcesses.index'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlanningProcesses/IndexPlanningProcess')
                ->has('data')
                ->has('meta')
            );
    });

    test('super admin can access create page', function () {
        asUser($this->superAdmin)->get(route('planningProcesses.create'))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlanningProcesses/CreatePlanningProcess')
                ->has('tenants')
            );
    });

    test('super admin can store planning process', function () {
        $newTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();

        asUser($this->superAdmin)->post(route('planningProcesses.store'), [
            'tenant_id' => $newTenant->id,
            'academic_year_start' => 2030,
        ])->assertRedirect(route('planningProcesses.index'));

        expect(PlanningProcess::where('tenant_id', $newTenant->id)->where('academic_year_start', 2030)->exists())
            ->toBeTrue();
    });

    test('super admin can view planning process', function () {
        asUser($this->superAdmin)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlanningProcesses/ShowPlanningProcess')
                ->has('planningProcess')
            );
    });

    test('super admin can update planning process', function () {
        asUser($this->superAdmin)->patch(route('planningProcesses.update', $this->planningProcess), [
            'expectations_text' => 'Updated expectations',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->expectations_text)->toBe('Updated expectations');
    });

    test('super admin can delete planning process', function () {
        asUser($this->superAdmin)->delete(route('planningProcesses.destroy', $this->planningProcess))
            ->assertRedirect(route('planningProcesses.index'));

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
        asUser($this->moderator)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200);
    });

    test('moderator can update their assigned process', function () {
        asUser($this->moderator)->patch(route('planningProcesses.update', $this->planningProcess), [
            'goal_text' => 'Moderator updated goal',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->goal_text)->toBe('Moderator updated goal');
    });

    test('moderator cannot approve their own goal', function () {
        $this->planningProcess->update([
            'current_stage' => 2,
            'expectations_submitted_at' => now(),
        ]);

        asUser($this->moderator)->patch(route('planningProcesses.updateGoal', $this->planningProcess), [
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

        asUser($this->moderator)->patch(route('planningProcesses.approveDocument', $this->planningProcess), [
            'collection' => 'tip_document',
        ])->assertStatus(403);

        expect($this->planningProcess->fresh()->tip_approved_at)->toBeNull();
    });

    test('moderator cannot advance stage on their own process', function () {
        $this->planningProcess->update([
            'expectations_text' => 'Some expectations',
            'expectations_submitted_at' => now(),
        ]);

        asUser($this->moderator)->patch(route('planningProcesses.advanceStage', $this->planningProcess))
            ->assertStatus(403);

        expect($this->planningProcess->fresh()->current_stage)->toBe(1);
    });

    test('moderator can update goal text without approving', function () {
        $this->planningProcess->update(['current_stage' => 2, 'expectations_submitted_at' => now()]);

        asUser($this->moderator)->patch(route('planningProcesses.updateGoal', $this->planningProcess), [
            'goal_text' => 'Updated by moderator',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->goal_text)->toBe('Updated by moderator');
    });
});

describe('stage advancement', function () {
    test('cannot advance stage if current stage is not complete', function () {
        asUser($this->superAdmin)->patch(route('planningProcesses.advanceStage', $this->planningProcess))
            ->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(1);
    });

    test('can advance stage when current stage is complete', function () {
        $this->planningProcess->update([
            'expectations_text' => 'Some expectations',
            'expectations_submitted_at' => now(),
        ]);

        asUser($this->superAdmin)->patch(route('planningProcesses.advanceStage', $this->planningProcess))
            ->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(2);
    });
});

describe('auto stage advancement', function () {
    test('submitting expectations auto-advances from stage 1 to stage 2', function () {
        expect($this->planningProcess->current_stage)->toBe(1);

        asUser($this->superAdmin)->patch(route('planningProcesses.update', $this->planningProcess), [
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

        asUser($this->superAdmin)->patch(route('planningProcesses.updateGoal', $this->planningProcess), [
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
        asUser($this->superAdmin)->patch(route('planningProcesses.approveDocument', $this->planningProcess), [
            'collection' => 'tip_document',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(3);

        // Approve MVP - now both are approved, should advance
        asUser($this->superAdmin)->patch(route('planningProcesses.approveDocument', $this->planningProcess), [
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

        asUser($this->superAdmin)->patch(route('planningProcesses.update', $this->planningProcess), [
            'reflection_text' => 'Year-end reflection',
            'reflection_submitted_at' => now()->toISOString(),
        ])->assertRedirect();

        $fresh = $this->planningProcess->fresh();
        expect($fresh->current_stage)->toBe(6);
        expect($fresh->locked_at)->not->toBeNull();
    });

    test('does not auto-advance when stage completion criteria not met', function () {
        // Update expectations text but don't submit (no expectations_submitted_at)
        asUser($this->superAdmin)->patch(route('planningProcesses.update', $this->planningProcess), [
            'expectations_text' => 'Draft expectations',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->current_stage)->toBe(1);
    });
});

describe('tenant isolation', function () {
    test('admin cannot update process from different tenant', function () {
        $originalText = $this->otherProcess->expectations_text;

        asUser($this->admin)->patch(route('planningProcesses.update', $this->otherProcess), [
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

        asUser($this->superAdmin)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlanningProcesses/ShowPlanningProcess')
                ->has('deadlines', 2)
                ->where('deadlines.0.stage', 1)
                ->where('deadlines.1.stage', 2)
            );
    });

    test('show page returns empty deadlines when none exist', function () {
        asUser($this->superAdmin)->get(route('planningProcesses.show', $this->planningProcess))
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

        asUser($this->superAdmin)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('deadlines', 1)
                ->where('deadlines.0.academic_year_start', 2026)
            );
    });
});
