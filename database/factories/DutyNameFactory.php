<?php

namespace Database\Factories;

use App\Models\Duty;
use App\Models\DutyName;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DutyName>
 */
class DutyNameFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'duty_id' => Duty::factory(),
            'name' => ['lt' => 'Koordinatorius', 'en' => 'Coordinator'],
            'used_until' => now()->subYear()->toDateString(),
        ];
    }
}
