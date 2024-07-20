<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tenants')->insert([
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Istorijos fakultete', 'shortname' => 'VU SA IF', 'shortname_vu' => 'VU IF', 'alias' => 'if', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Fizikos fakultete', 'shortname' => 'VU SA FF', 'shortname_vu' => 'VU FF', 'alias' => 'ff', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Chemijos ir geomokslų fakultete', 'shortname' => 'VU SA CHGF', 'shortname_vu' => 'VU CHGF', 'alias' => 'chgf', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Ekonomikos ir verslo administravimo fakultete', 'shortname' => 'VU SA EVAF', 'shortname_vu' => 'VU EVAF', 'alias' => 'evaf', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Filologijos fakultete', 'shortname' => 'VU SA FilF', 'shortname_vu' => 'VU FlF', 'alias' => 'filf', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Filosofijos fakultete', 'shortname' => 'VU SA FsF', 'shortname_vu' => 'VU FsF', 'alias' => 'fsf', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Gyvybės mokslų centre', 'shortname' => 'VU SA GMC', 'shortname_vu' => 'VU GMC', 'alias' => 'gmc', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Komunikacijos fakultete', 'shortname' => 'VU SA KF', 'shortname_vu' => 'VU KF', 'alias' => 'kf', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Kauno fakultete', 'shortname' => 'VU SA KnF', 'shortname_vu' => 'VU KnF', 'alias' => 'knf', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Medicinos fakultete', 'shortname' => 'VU SA MF', 'shortname_vu' => 'VU MF', 'alias' => 'mf', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Matematikos ir informatikos fakultete', 'shortname_vu' => 'VU MIF', 'shortname' => 'VU SA MIF', 'alias' => 'mif', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Teisės fakultete', 'shortname' => 'VU SA TF', 'shortname_vu' => 'VU TF', 'alias' => 'tf', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Tarptautinių santykių ir politikos mokslų institute', 'shortname_vu' => 'VU TSPMI', 'shortname' => 'VU SA TSPMI', 'alias' => 'tspmi', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Verslo mokykloje', 'shortname' => 'VU SA VM', 'shortname_vu' => 'VU VM', 'alias' => 'vm', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Šiaulių akademijoje', 'shortname' => 'VU SA ŠA', 'shortname_vu' => 'VU ŠA', 'alias' => 'sa', 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė', 'shortname' => 'VU SA', 'shortname_vu' => 'VU', 'alias' => 'vusa', 'type' => 'pagrindinis'],
        ]);
    }
}
