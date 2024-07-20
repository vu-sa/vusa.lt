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
        return [
            'fullname' => $this->faker->company(),
            'shortname' => $this->faker->companySuffix(),
            'alias' => $this->faker->companySuffix(),
            'shortname_vu' => $this->faker->companySuffix(),
        ];
    }
}
