<?php

namespace Database\Factories;

use App\Enums\SurveyQuestionType;
use App\Models\SurveyQuestionTemplate;
use Database\Factories\Concerns\HasTranslatableFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestionTemplate>
 */
class SurveyQuestionTemplateFactory extends Factory
{
    use HasTranslatableFactory;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Global by default — the common case for a shared question bank.
            'tenant_id' => null,
            'group_name' => $this->translatableWords(2),
            'title' => 'T'.$this->faker->unique()->numberBetween(1, 9999),
            'type' => SurveyQuestionType::FivePoint,
            'question' => $this->translatable(),
            'help' => $this->translatable(),
            'options' => null,
            'is_required' => true,
            'order' => 0,
            'is_active' => true,
        ];
    }
}
