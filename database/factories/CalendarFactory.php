<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class CalendarFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Calendar::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $date = fake()->dateTimeBetween('-1 years', '+1 years');
        // end_date is randomly after 0-3 days
        $end_date = fake()->dateTimeBetween(Carbon::parse($date)->addHour(), Carbon::parse($date)->addDays(3));

        return [
            'title' => ['lt' => fake()->sentence, 'en' => fake()->sentence],
            'description' => ['lt' => fake()->paragraph, 'en' => fake()->paragraph],
            'location' => ['lt' => fake()->city, 'en' => fake()->city],
            'organizer' => ['lt' => fake()->name, 'en' => fake()->name],
            'date' => $date,
            'end_date' => $end_date,
            'category' => Arr::random(['red', 'yellow', 'grey']),
            'cto_url' => ['lt' => fake()->url, 'en' => fake()->url],
            'is_international' => fake()->boolean,
            'is_draft' => fake()->boolean,
            'is_all_day' => fake()->boolean,
            'tenant_id' => Tenant::factory(),
        ];
    }
}
