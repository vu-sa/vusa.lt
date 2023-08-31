<?php

namespace Database\Factories;

use App\Models\Type;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class TypeableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
    */
    public function definition(): array
    {
        return [
            'type_id' => Type::factory(),
            'typeable_type' => fn (array $attributes) => Type::find($attributes['type_id'])->model_type,
            'typeable_id' => function (array $attributes) {
                return $attributes['typeable_type']::factory();
            },
        ];
    }
}
