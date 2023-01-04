<?php

namespace Database\Seeders;

use App\Models\InstitutionMatter;
use Illuminate\Database\Seeder;

class InstitutionMatterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        InstitutionMatter::factory()->count(10)->create();
    }
}
