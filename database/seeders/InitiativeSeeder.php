<?php

namespace Database\Seeders;

use App\Models\Initiative;
use Illuminate\Database\Seeder;

class InitiativeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Initiative::factory()->count(10)->create();
    }
}
