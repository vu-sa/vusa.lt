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
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Istorijos fakultete', 'shortname' => 'VU SA IF', 'alias' => 'vusaif'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Fizikos fakultete', 'shortname' => 'VU SA FF', 'alias' => 'vusaff'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Chemijos ir geomokslų fakultete', 'shortname' => 'VU SA CHGF', 'alias' => 'vusachgf'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Ekonomikos ir verslo administravimo fakultete', 'shortname' => 'VU SA EVAF', 'alias' => 'vusaevaf'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Filologijos fakultete', 'shortname' => 'VU SA FilF', 'alias' => 'vusafilf'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Filosofijos fakultete', 'shortname' => 'VU SA FsF', 'alias' => 'vusafsf'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Gyvybės mokslų centre', 'shortname' => 'VU SA GMC', 'alias' => 'vusagmc'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Komunikacijos fakultete', 'shortname' => 'VU SA KF', 'alias' => 'vusakf'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Kauno fakultete', 'shortname' => 'VU SA KnF', 'alias' => 'vusaknf'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Medicinos fakultete', 'shortname' => 'VU SA MF', 'alias' => 'vusamf'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Matematikos ir informatikos fakultete', 'shortname' => 'VU SA MIF', 'alias' => 'vusamif'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Teisės fakultete', 'shortname' => 'VU SA TF', 'alias' => 'vusatf'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Tarptautinių santykių ir politikos mokslų institute', 'shortname' => 'VU SA TSPMI', 'alias' => 'vusatspmi'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Verslo mokykloje', 'shortname' => 'VU SA VM', 'alias' => 'vusavm'],
            ['fullname' => 'Vilniaus universiteto Studentų atstovybė Šiaulių akademijoje', 'shortname' => 'VU SA ŠA', 'alias' => 'vusasa'],
        ]);
    }
}