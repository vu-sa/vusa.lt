<?php

namespace Database\Factories;

use App\Models\EventType;
use Database\Factories\Concerns\HasTranslatableFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<EventType>
 */
class EventTypeFactory extends Factory
{
    use HasTranslatableFactory;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $nameEn = fake('en_US')->unique()->words(2, true);

        return [
            'name' => $this->translatable(ucwords(fake('lt_LT')->words(2, true)), ucwords($nameEn)),
            'description' => $this->translatable(),
            'slug' => Str::slug($nameEn),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
