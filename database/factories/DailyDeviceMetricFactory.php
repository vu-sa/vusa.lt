<?php

namespace Database\Factories;

use App\Models\DailyDeviceMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyDeviceMetric>
 */
class DailyDeviceMetricFactory extends Factory
{
    protected $model = DailyDeviceMetric::class;

    public function definition(): array
    {
        return [
            'date' => $this->faker->unique()->date(),
            'phone_logins' => $this->faker->numberBetween(0, 50),
            'tablet_logins' => $this->faker->numberBetween(0, 20),
            'desktop_logins' => $this->faker->numberBetween(10, 100),
            'pwa_launches' => $this->faker->numberBetween(0, 15),
        ];
    }
}
