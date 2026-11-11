<?php

namespace Database\Factories;

use App\Models\KnowledgeSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KnowledgeSection> */
class KnowledgeSectionFactory extends Factory
{
    protected $model = KnowledgeSection::class;

    public function definition(): array
    {
        return ['title' => ['lt' => 'Įvadiniai mokymai', 'en' => 'Introductory training'],
            'description' => ['lt' => 'Mokymų dokumentai', 'en' => 'Training documents'], 'order' => 0];
    }
}
