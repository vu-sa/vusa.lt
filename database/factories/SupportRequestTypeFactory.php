<?php

namespace Database\Factories;

use App\Models\SupportRequestType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportRequestType>
 */
class SupportRequestTypeFactory extends Factory
{
    protected $model = SupportRequestType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => [
                'lt' => ucfirst($name).' LT',
                'en' => ucfirst($name).' EN',
            ],
            'slug' => Str::slug($name),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 10),
        ];
    }
}
