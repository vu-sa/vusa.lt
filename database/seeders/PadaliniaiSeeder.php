<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PadaliniaiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('padaliniai')->insert([
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Istorijos fakultete', 'shortname' => 'VU SA IF', 'shortname_vu' => 'VU IF', 'alias' => 'if', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Fizikos fakultete', 'shortname' => 'VU SA FF', 'shortname_vu' => 'VU FF', 'alias' => 'ff', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Chemijos ir geomokslų fakultete', 'shortname' => 'VU SA CHGF', 'shortname_vu' => 'VU CHGF','alias' => 'chgf', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Ekonomikos ir verslo administravimo fakultete', 'shortname' => 'VU SA EVAF','shortname_vu' => 'VU EVAF', 'alias' => 'evaf', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Filologijos fakultete', 'shortname' => 'VU SA FilF','shortname_vu' => 'VU FlF', 'alias' => 'filf', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Filosofijos fakultete', 'shortname' => 'VU SA FsF','shortname_vu' => 'VU FsF', 'alias' => 'fsf', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Gyvybės mokslų centre', 'shortname' => 'VU SA GMC','shortname_vu' => 'VU GMC', 'alias' => 'gmc', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Komunikacijos fakultete', 'shortname' => 'VU SA KF','shortname_vu' => 'VU KF', 'alias' => 'kf', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Kauno fakultete', 'shortname' => 'VU SA KnF','shortname_vu' => 'VU KnF', 'alias' => 'knf', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Medicinos fakultete', 'shortname' => 'VU SA MF','shortname_vu' => 'VU MF', 'alias' => 'mf', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Matematikos ir informatikos fakultete','shortname_vu' => 'VU MIF', 'shortname' => 'VU SA MIF', 'alias' => 'mif', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Teisės fakultete', 'shortname' => 'VU SA TF','shortname_vu' => 'VU TF', 'alias' => 'tf', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Tarptautinių santykių ir politikos mokslų institute','shortname_vu' => 'VU TSPMI', 'shortname' => 'VU SA TSPMI', 'alias' => 'tspmi', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Verslo mokykloje', 'shortname' => 'VU SA VM','shortname_vu' => 'VU VM', 'alias' => 'vm', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Šiaulių akademijoje', 'shortname' => 'VU SA ŠA', 'shortname_vu' => 'VU ŠA','alias' => 'sa', 'en' => 0, 'type' => 'padalinys'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė', 'shortname' => 'VU SA','shortname_vu' => 'VU', 'alias' => 'vusa', 'en' => 0, 'type' => 'pagrindinis'],
        ]);
    }
}
