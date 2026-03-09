<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\InstitutionCheckIn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionCheckIn>
 */
class InstitutionCheckInFactory extends Factory
{
    protected $model = InstitutionCheckIn::class;

    public function definition(): array
    {
        $startDate = now()->copy()->addDays($this->faker->numberBetween(0, 7));
        $endDate = $startDate->copy()->addDays($this->faker->numberBetween(7, 60));

        return [
            'institution_id' => Institution::factory(),
            'user_id' => User::factory(),
            'tenant_id' => function (array $attrs) {
                return Institution::find($attrs['institution_id'])->tenant_id;
            },
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'note' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
        ];
    }
}
