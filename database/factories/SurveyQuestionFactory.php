<?php

namespace Database\Factories;

use App\Enums\SurveyQuestionType;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Database\Factories\Concerns\HasTranslatableFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SurveyQuestion>
 */
class SurveyQuestionFactory extends Factory
{
    use HasTranslatableFactory;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'survey_id' => Survey::factory(),
            'survey_question_template_id' => null,
            'group_name' => $this->translatableWords(2),
            'title' => 'Q'.$this->faker->unique()->numberBetween(1, 9999),
            'type' => SurveyQuestionType::LongText,
            'question' => $this->translatable(),
            'help' => $this->translatable(),
            'options' => null,
            'is_required' => false,
            'order' => 0,
        ];
    }

    public function ofType(SurveyQuestionType $type): static
    {
        return $this->state(fn (): array => [
            'type' => $type,
            'options' => $type->hasOptions() ? $this->sampleOptions() : null,
        ]);
    }

    /**
     * @return list<array{code: string, label: array{lt: string, en: string}}>
     */
    private function sampleOptions(): array
    {
        return [
            ['code' => 'A1', 'label' => ['lt' => 'Taip', 'en' => 'Yes']],
            ['code' => 'A2', 'label' => ['lt' => 'Ne', 'en' => 'No']],
        ];
    }
}
