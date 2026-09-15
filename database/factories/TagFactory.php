<?php

namespace Database\Factories;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nameLt = fake('lt_LT')->words(2, true);
        // alias now carries a DB-level unique constraint — unique() keeps parallel factory
        // calls from colliding on the same slug.
        $nameEn = fake()->unique()->words(2, true);
        $descriptionLt = fake('lt_LT')->sentence();
        $descriptionEn = fake('en_US')->sentence();

        return [
            'name' => [
                'lt' => ucwords($nameLt),
                'en' => ucwords($nameEn),
            ],
            'description' => [
                'lt' => $descriptionLt,
                'en' => $descriptionEn,
            ],
            'alias' => Str::slug($nameEn),
            'is_topic' => false,
            'sort_order' => 0,
        ];
    }

    public function topic(): static
    {
        return $this->state(fn (array $attributes): array => ['is_topic' => true]);
    }
}
