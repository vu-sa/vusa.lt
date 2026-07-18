<?php

namespace Database\Factories;

use App\Models\Workspace;
use App\Models\WorkspaceDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceDocument>
 */
class WorkspaceDocumentFactory extends Factory
{
    protected $model = WorkspaceDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => Workspace::factory(),
            'title' => $this->faker->sentence(3),
            'yjs_state' => null,
            'content_html' => '<p>'.$this->faker->sentence().'</p>',
            'updated_by' => null,
        ];
    }
}
