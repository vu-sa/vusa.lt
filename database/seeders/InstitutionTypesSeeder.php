<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstitutionTypesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('duties_institutions_types')->insert(
            [
                ['name' => 'Programos, klubai, projektai', 'alias' => 'pkp'],
                ['name' => 'Studentų atstovų organas', 'alias' => 'studentu-atstovu-organas'],
                ['name' => 'VU SA padalinys', 'alias' => 'vu-sa-padaliniai'],
            ]
        );
    }
}
