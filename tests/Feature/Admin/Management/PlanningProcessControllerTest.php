<?php

use App\Models\PlanningProcess;
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

describe('tenant isolation', function () {
    test('admin cannot update process from different tenant', function () {
        // Use super admin scoped to a tenant to test cross-tenant restriction
        asUser($this->superAdmin)->patch(route('planningProcesses.update', $this->otherProcess), [
            'expectations_text' => 'Injected text',
        ]);

        // Super admin can update any process, so instead test that tenant-scoped admin can't
        asUser($this->admin)->patch(route('planningProcesses.update', $this->otherProcess), [
            'expectations_text' => 'Injected text',
        ])->assertStatus(403);

        expect($this->otherProcess->fresh()->expectations_text)->not->toBe('Injected text');
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
