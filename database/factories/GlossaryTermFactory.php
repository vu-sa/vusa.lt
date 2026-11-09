<?php

namespace Database\Factories;

use App\Models\GlossaryTerm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<GlossaryTerm> */
class GlossaryTermFactory extends Factory
{
    protected $model = GlossaryTerm::class;

    public function definition(): array
    {
        return ['term' => ['lt' => fake()->word(), 'en' => fake()->word()], 'definition' => ['lt' => fake()->paragraph(), 'en' => fake()->paragraph()]];
    }
}
