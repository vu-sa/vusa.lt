<?php

namespace Database\Factories;

use App\Models\Form;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FormField>
 */
class FormFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => Form::factory(),
            'label' => ['lt' => $this->faker->sentence(), 'en' => $this->faker->sentence()],
            'description' => ['lt' => $this->faker->paragraph(), 'en' => $this->faker->paragraph()],
            'type' => $this->faker->randomElement(['string', 'enum', 'boolean', 'date']),
            'subtype' => null,
            'options' => null,
            'is_required' => $this->faker->boolean(),
            'order' => $this->faker->numberBetween(0, 100),
            'default_value' => null,
            'placeholder' => null,
            'use_model_options' => false,
            'options_model' => null,
            'options_model_field' => null,
        ];
    }
}
