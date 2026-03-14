<?php

use App\Models\Comment;
use App\Models\PlanningProcess;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->inRandomOrder()->first();
    $this->superAdmin = makeAdminUser();

    $this->planningProcess = PlanningProcess::factory()->create([
        'tenant_id' => $this->tenant->id,
        'academic_year_start' => 2026,
    ]);
});

describe('stage comments', function () {
    test('can post a comment with a stage', function () {
        asUser($this->superAdmin)->post(
            route('users.comments.store', $this->superAdmin->id),
            [
                'commentable_type' => 'planning_process',
                'commentable_id' => $this->planningProcess->id,
                'comment' => 'Test stage comment',
                'stage' => 2,
            ]
        )->assertRedirect();

        $comment = Comment::where('commentable_id', $this->planningProcess->id)
            ->where('commentable_type', PlanningProcess::class)
            ->first();

        expect($comment)->not->toBeNull();
        expect($comment->stage)->toBe(2);
        expect($comment->comment)->toBe('Test stage comment');
    });

    test('can post a comment without a stage', function () {
        asUser($this->superAdmin)->post(
            route('users.comments.store', $this->superAdmin->id),
            [
                'commentable_type' => 'planning_process',
                'commentable_id' => $this->planningProcess->id,
                'comment' => 'General comment',
            ]
        )->assertRedirect();

        $comment = Comment::where('commentable_id', $this->planningProcess->id)->first();

        expect($comment)->not->toBeNull();
        expect($comment->stage)->toBeNull();
    });

    test('stage validation rejects invalid values', function () {
        asUser($this->superAdmin)->post(
            route('users.comments.store', $this->superAdmin->id),
            [
                'commentable_type' => 'planning_process',
                'commentable_id' => $this->planningProcess->id,
                'comment' => 'Invalid stage',
                'stage' => 6,
            ]
        )->assertSessionHasErrors('stage');
    });

    test('show page returns stage comments grouped by stage', function () {
        $this->actingAs($this->superAdmin);

        // Create comments for different stages
        $this->planningProcess->comment('Stage 1 comment', ['stage' => 1]);
        $this->planningProcess->comment('Stage 3 comment', ['stage' => 3]);
        $this->planningProcess->comment('Another stage 1 comment', ['stage' => 1]);

        asUser($this->superAdmin)->get(
            route('planavimai.show', $this->planningProcess)
        )->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlanningProcesses/ShowPlanningProcess')
                ->has('stageComments.1', 2)
                ->has('stageComments.3', 1)
                ->missing('stageComments.2')
            );
    });

    test('comments without stage are excluded from stageComments', function () {
        $this->actingAs($this->superAdmin);

        $this->planningProcess->comment('General comment');
        $this->planningProcess->comment('Stage 2 comment', ['stage' => 2]);

        asUser($this->superAdmin)->get(
            route('planavimai.show', $this->planningProcess)
        )->assertStatus(200)
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/PlanningProcesses/ShowPlanningProcess')
                ->has('stageComments.2', 1)
                ->missing('stageComments.0')
            );
    });
});
