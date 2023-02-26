<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DutyTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('duties_types')->insert(
            [
                ['name' => 'Pirmininkas', 'alias' => 'pirmininkas'],
                ['name' => 'Prezidentas', 'alias' => 'prezidentas'],
                ['name' => 'Koordinatorius', 'alias' => 'koordinatoriai'],
                ['name' => 'Narys', 'alias' => 'narys'],
                ['name' => 'Kuratorius', 'alias' => 'kuratoriai'],
                ['name' => 'Vadovas', 'alias' => 'vadovas'],
                ['name' => 'Studentų atstovas', 'alias' => 'studentu-atstovai'],
            ]
        );
    }
}
