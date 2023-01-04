<?php

namespace Database\Seeders;

use App\Models\Doing;
use App\Models\Question;
use App\Models\Goal;
use App\Models\InstitutionMatter;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DoingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // call factory
        Goal::factory()
            ->has(InstitutionMatter::factory()
                ->has(Doing::factory()->count(10))
            ->count(10))
        ->count(3)->create();
    }
}
