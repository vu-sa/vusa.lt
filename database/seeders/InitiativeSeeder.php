<?php

namespace Database\Seeders;

use App\Models\Initiative;
use App\Models\Type;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
