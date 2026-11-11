<?php

namespace Database\Factories;

use App\Models\KnowledgeItem;
use App\Models\KnowledgeItemSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<KnowledgeItemSource> */
class KnowledgeItemSourceFactory extends Factory
{
    protected $model = KnowledgeItemSource::class;

    public function definition(): array
    {
        return ['knowledge_item_id' => KnowledgeItem::factory(), 'locale' => 'lt', 'url' => fake()->url(), 'document_id' => null];
    }
}
