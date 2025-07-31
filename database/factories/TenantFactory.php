<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate a unique shortname by combining a word, timestamp and a random number
        $timestamp = microtime(true);
        $shortname = $this->faker->word().'-'.$timestamp.'-'.$this->faker->randomNumber(3);

        return [
            'fullname' => $this->faker->company(),
            'shortname' => $shortname,
            'alias' => $this->faker->word().'-'.$this->faker->randomNumber(2),
            'shortname_vu' => $this->faker->word().$this->faker->randomNumber(2),
            'type' => 'padalinys',
        ];
    }
}
