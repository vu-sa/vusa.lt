<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tag>
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
        $nameEn = fake('en_US')->words(2, true);
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
            'alias' => \Illuminate\Support\Str::slug($nameEn),
        ];
    }
}
