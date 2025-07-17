<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true) . '.pdf',
            'title' => $this->faker->sentence(4),
            'sharepoint_id' => $this->faker->uuid,
            'eTag' => $this->faker->sha256,
            'document_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'institution_id' => Institution::factory(),
            'content_type' => $this->faker->randomElement(['Document', 'Policy', 'Meeting Minutes', 'Report']),
            'language' => $this->faker->randomElement(['lt', 'en']),
            'summary' => $this->faker->paragraph(),
            'anonymous_url' => $this->faker->url,
            'is_active' => true,
            'sharepoint_site_id' => $this->faker->uuid,
            'sharepoint_list_id' => $this->faker->uuid,
            'effective_date' => $this->faker->optional()->dateTimeBetween('-6 months', '+6 months')?->format('Y-m-d'),
            'expiration_date' => $this->faker->optional()->dateTimeBetween('+1 month', '+2 years')?->format('Y-m-d'),
        ];
    }

    /**
     * Create a public document
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive document
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
