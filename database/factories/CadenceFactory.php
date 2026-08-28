<?php

namespace Database\Factories;

use App\Models\Cadence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cadence>
 */
class CadenceFactory extends Factory
{
    protected $model = Cadence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = $this->faker->numberBetween(2020, 2027);

        return [
            'institution_id' => null,
            'start_date' => sprintf('%d-07-01', $startYear),
            'end_date' => sprintf('%d-06-30', $startYear + 1),
        ];
    }

    public function forYear(int $startYear): static
    {
        return $this->state(fn () => [
            'start_date' => sprintf('%d-07-01', $startYear),
            'end_date' => sprintf('%d-06-30', $startYear + 1),
        ]);
    }
}
