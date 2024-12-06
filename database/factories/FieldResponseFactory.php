<?php

namespace Database\Factories;

use App\Models\FormField;
use App\Models\Registration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FieldResponse>
 */
class FieldResponseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_id' => Registration::factory(),
            'form_field_id' => FormField::factory(),
            'response' => ['value' => $this->faker->sentence()],
        ];
    }
}
