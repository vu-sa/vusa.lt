<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceLink>
 */
class WorkspaceLinkFactory extends Factory
{
    protected $model = WorkspaceLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'linkable_type' => Meeting::class,
            'linkable_id' => Meeting::factory(),
            'added_by' => User::factory(),
        ];
    }
}
