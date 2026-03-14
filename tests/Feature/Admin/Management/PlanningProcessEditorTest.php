<?php

use App\Models\PlanningProcess;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->otherTenant = Tenant::query()->where('id', '!=', $this->tenant->id)->first();

    $this->coordinator = makeTenantUserWithRole('Communication Coordinator', $this->tenant);
    $this->moderator = makeUser($this->tenant);
    $this->editor = makeUser($this->tenant);
    $this->tenantUser = makeUser($this->tenant);
    $this->outsideUser = makeUser($this->otherTenant);

    $this->planningProcess = PlanningProcess::factory()->create([
        'tenant_id' => $this->tenant->id,
        'academic_year_start' => 2026,
        'moderator_user_id' => $this->moderator->id,
    ]);

    // Add editor to the planning process
    $this->planningProcess->editors()->attach($this->editor->id);
});

describe('editor view access', function () {
    test('editor can view the planning process', function () {
        asUser($this->editor)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200);
    });

    test('same-tenant user can view the planning process (read-only)', function () {
        asUser($this->tenantUser)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200);
    });

    test('outside-tenant user cannot view the planning process', function () {
        asUser($this->outsideUser)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(403);
    });
});

describe('editor update access', function () {
    test('editor can update the planning process', function () {
        asUser($this->editor)->patch(route('planningProcesses.update', $this->planningProcess), [
            'goal_text' => 'Editor updated goal',
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->goal_text)->toBe('Editor updated goal');
    });

    test('same-tenant user cannot update the planning process', function () {
        asUser($this->tenantUser)->patch(route('planningProcesses.update', $this->planningProcess), [
            'goal_text' => 'Unauthorized update',
        ])->assertStatus(403);
    });
});

describe('editor cannot approve', function () {
    test('editor cannot approve goals', function () {
        $this->planningProcess->update([
            'current_stage' => 2,
            'expectations_submitted_at' => now(),
        ]);

        asUser($this->editor)->patch(route('planningProcesses.updateGoal', $this->planningProcess), [
            'goal_text' => 'Goal text',
            'goal_approved_at' => now()->toISOString(),
        ])->assertStatus(403);

        expect($this->planningProcess->fresh()->goal_approved_at)->toBeNull();
    });

    test('editor cannot approve documents', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
        ]);

        asUser($this->editor)->patch(route('planningProcesses.approveDocument', $this->planningProcess), [
            'collection' => 'tip_document',
        ])->assertStatus(403);
    });

    test('editor cannot reject documents', function () {
        $this->planningProcess->update([
            'current_stage' => 3,
            'goal_approved_at' => now(),
        ]);

        asUser($this->editor)->patch(route('planningProcesses.rejectDocument', $this->planningProcess), [
            'collection' => 'tip_document',
            'notes' => 'Reject',
        ])->assertStatus(403);
    });
});

describe('editor management', function () {
    test('coordinator can add an editor', function () {
        $newEditor = makeUser($this->tenant);

        asUser($this->coordinator)->post(route('planningProcesses.addEditor', $this->planningProcess), [
            'user_id' => $newEditor->id,
        ])->assertRedirect();

        expect($this->planningProcess->editors()->where('user_id', $newEditor->id)->exists())->toBeTrue();
    });

    test('moderator can add an editor', function () {
        $newEditor = makeUser($this->tenant);

        asUser($this->moderator)->post(route('planningProcesses.addEditor', $this->planningProcess), [
            'user_id' => $newEditor->id,
        ])->assertRedirect();

        expect($this->planningProcess->editors()->where('user_id', $newEditor->id)->exists())->toBeTrue();
    });

    test('coordinator can remove an editor', function () {
        asUser($this->coordinator)->delete(route('planningProcesses.removeEditor', $this->planningProcess), [
            'user_id' => $this->editor->id,
        ])->assertRedirect();

        expect($this->planningProcess->editors()->where('user_id', $this->editor->id)->exists())->toBeFalse();
    });

    test('moderator can remove an editor', function () {
        asUser($this->moderator)->delete(route('planningProcesses.removeEditor', $this->planningProcess), [
            'user_id' => $this->editor->id,
        ])->assertRedirect();

        expect($this->planningProcess->editors()->where('user_id', $this->editor->id)->exists())->toBeFalse();
    });

    test('editor cannot add other editors', function () {
        $newEditor = makeUser($this->tenant);

        asUser($this->editor)->post(route('planningProcesses.addEditor', $this->planningProcess), [
            'user_id' => $newEditor->id,
        ])->assertStatus(403);
    });

    test('regular tenant user cannot add editors', function () {
        $newEditor = makeUser($this->tenant);

        asUser($this->tenantUser)->post(route('planningProcesses.addEditor', $this->planningProcess), [
            'user_id' => $newEditor->id,
        ])->assertStatus(403);
    });

    test('cannot add the moderator as an editor', function () {
        asUser($this->coordinator)->post(route('planningProcesses.addEditor', $this->planningProcess), [
            'user_id' => $this->moderator->id,
        ])->assertRedirect()
            ->assertSessionHas('error');
    });

    test('cannot manage editors on a locked process', function () {
        $this->planningProcess->update(['locked_at' => now()]);
        $newEditor = makeUser($this->tenant);

        asUser($this->coordinator)->post(route('planningProcesses.addEditor', $this->planningProcess), [
            'user_id' => $newEditor->id,
        ])->assertStatus(403);
    });
});

describe('expectations visibility', function () {
    test('editor can view expectations', function () {
        $this->planningProcess->update(['expectations_text' => 'Secret expectations']);

        asUser($this->editor)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewExpectations', true)
                ->where('planningProcess.expectations_text', 'Secret expectations')
            );
    });

    test('moderator cannot view expectations', function () {
        $this->planningProcess->update(['expectations_text' => 'Secret expectations']);

        asUser($this->moderator)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewExpectations', false)
                ->where('planningProcess.expectations_text', null)
            );
    });

    test('same-tenant user cannot view expectations', function () {
        $this->planningProcess->update(['expectations_text' => 'Secret expectations']);

        asUser($this->tenantUser)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewExpectations', false)
                ->where('planningProcess.expectations_text', null)
            );
    });

    test('coordinator can view expectations', function () {
        $this->planningProcess->update(['expectations_text' => 'Secret expectations']);

        asUser($this->coordinator)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewExpectations', true)
                ->where('planningProcess.expectations_text', 'Secret expectations')
            );
    });
});

describe('moderator assignment restrictions', function () {
    test('coordinator can assign moderator', function () {
        $newModerator = makeUser($this->tenant);

        asUser($this->coordinator)->patch(route('planningProcesses.assignModerator', $this->planningProcess), [
            'moderator_user_id' => $newModerator->id,
        ])->assertRedirect();

        expect($this->planningProcess->fresh()->moderator_user_id)->toBe($newModerator->id);
    });

    test('moderator cannot assign moderator', function () {
        $newModerator = makeUser($this->tenant);

        asUser($this->moderator)->patch(route('planningProcesses.assignModerator', $this->planningProcess), [
            'moderator_user_id' => $newModerator->id,
        ])->assertStatus(403);

        expect($this->planningProcess->fresh()->moderator_user_id)->toBe($this->moderator->id);
    });

    test('editor cannot assign moderator', function () {
        $newModerator = makeUser($this->tenant);

        asUser($this->editor)->patch(route('planningProcesses.assignModerator', $this->planningProcess), [
            'moderator_user_id' => $newModerator->id,
        ])->assertStatus(403);

        expect($this->planningProcess->fresh()->moderator_user_id)->toBe($this->moderator->id);
    });
});

describe('template management restrictions', function () {
    test('moderator cannot upload template', function () {
        asUser($this->moderator)->post(route('planningProcesses.uploadTemplate', $this->planningProcess), [
            'collection' => 'tip_template',
            'template' => UploadedFile::fake()->create('template.pdf', 100, 'application/pdf'),
        ])->assertStatus(403);
    });

    test('editor cannot upload template', function () {
        asUser($this->editor)->post(route('planningProcesses.uploadTemplate', $this->planningProcess), [
            'collection' => 'tip_template',
            'template' => UploadedFile::fake()->create('template.pdf', 100, 'application/pdf'),
        ])->assertStatus(403);
    });

    test('moderator cannot delete template', function () {
        asUser($this->moderator)->delete(route('planningProcesses.deleteTemplate', $this->planningProcess), [
            'collection' => 'tip_template',
        ])->assertStatus(403);
    });

    test('editor cannot delete template', function () {
        asUser($this->editor)->delete(route('planningProcesses.deleteTemplate', $this->planningProcess), [
            'collection' => 'tip_template',
        ])->assertStatus(403);
    });
});

describe('field change history visibility', function () {
    test('coordinator can see field changes', function () {
        asUser($this->coordinator)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewFieldChanges', true)
            );
    });

    test('editor can see field changes', function () {
        asUser($this->editor)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewFieldChanges', true)
            );
    });

    test('moderator can see field changes', function () {
        asUser($this->moderator)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewFieldChanges', true)
            );
    });

    test('same-tenant user cannot see field changes', function () {
        asUser($this->tenantUser)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->where('canViewFieldChanges', false)
            );
    });
});

describe('show page props', function () {
    test('show page includes editors and management props', function () {
        asUser($this->coordinator)->get(route('planningProcesses.show', $this->planningProcess))
            ->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->has('editors', 1)
                ->has('canManageEditors')
                ->has('canAssignModerator')
                ->has('canManageTemplates')
                ->has('canViewFieldChanges')
                ->has('isModerator')
            );
    });
});
