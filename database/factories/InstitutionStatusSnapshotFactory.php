<?php

namespace Database\Factories;

use App\Models\InstitutionStatusSnapshot;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionStatusSnapshot>
 */
class InstitutionStatusSnapshotFactory extends Factory
{
    protected $model = InstitutionStatusSnapshot::class;

    public function definition(): array
    {
        return [
            'taken_on' => $this->faker->unique()->dateTimeBetween('-1 year', '-1 day')->format('Y-m-d'),
            'tenant_id' => Tenant::factory(),
            'all' => 10,
            'needs_attention' => 3,
            'overdue' => 2,
            'approaching' => 1,
            'no_activity' => 0,
            'recent_check_in' => 1,
            'current' => 6,
            'meetings_held' => 0,
            'meetings_registered' => 0,
            'check_ins_registered' => 0,
            'entered_attention' => 0,
            'left_attention' => 0,
            'is_reconstructed' => false,
        ];
    }
}
