<?php

namespace Database\Factories;

use App\Models\ProblemCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ProblemCategory>
 */
class ProblemCategoryFactory extends Factory
{
    protected $model = ProblemCategory::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'name' => ['lt' => $name, 'en' => $name],
            'slug' => Str::slug($name),
            'description' => ['lt' => $this->faker->sentence(), 'en' => $this->faker->sentence()],
        ];
    }
}
