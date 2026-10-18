<?php

namespace Database\Factories;

use App\Models\RepMetricSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepMetricSnapshot>
 */
class RepMetricSnapshotFactory extends Factory
{
    protected $model = RepMetricSnapshot::class;

    public function definition(): array
    {
        return [
            'taken_on' => $this->faker->unique()->dateTimeBetween('-1 year')->format('Y-m-d'),
            'window_days' => 30,
            'metrics' => [
                'active_reps' => ['value' => 40.0, 'part' => 4, 'whole' => 10],
            ],
            'is_baseline' => false,
        ];
    }
}
