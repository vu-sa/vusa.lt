<?php

namespace Database\Factories;

use App\Models\SupportRequestArea;
use App\Models\SupportService;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SupportRequestArea>
 */
class SupportRequestAreaFactory extends Factory
{
    protected $model = SupportRequestArea::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'support_service_id' => SupportService::factory(),
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
