<?php

namespace Database\Factories;

use App\Enums\KnowledgeSectionKind;
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

    public function collection(?string $date = null): static
    {
        return $this->state(['kind' => KnowledgeSectionKind::Collection, 'date' => $date]);
    }
}
