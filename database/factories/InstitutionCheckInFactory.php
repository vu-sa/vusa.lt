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
        $mode = $this->faker->randomElement(['blackout', 'heads_up']);
        $duration = $mode === 'blackout' ? $this->faker->randomElement([7,14,28,60]) : $this->faker->numberBetween(1,7);

        return [
            'institution_id' => Institution::factory(),
            'user_id' => User::factory(),
            'tenant_id' => function (array $attrs) {
                return Institution::find($attrs['institution_id'])->tenant_id;
            },
            'until_date' => now()->copy()->addDays($duration)->toDateString(),
            'checked_at' => now()->toDateTimeString(),
            'confidence' => $this->faker->randomElement(['low','medium','high']),
            'note' => $this->faker->boolean(40) ? $this->faker->sentence() : null,
            'visibility' => 'public',
            'state' => \App\States\InstitutionCheckIns\Active::class,
            'mode' => $mode,
            // verified_count removed; compute from verifications
        ];
    }

    public function blackout(): self
    {
        return $this->state(fn () => ['mode' => 'blackout']);
    }

    public function headsUp(): self
    {
        return $this->state(fn () => ['mode' => 'heads_up']);
    }
}
