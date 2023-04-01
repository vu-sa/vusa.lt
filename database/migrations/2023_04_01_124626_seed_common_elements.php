<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // check if categories table is empty
        if (Schema::hasTable('categories') && DB::table('categories')->count() === 0) {
            // seed categories table with default categories
            DB::table('categories')->insert([
                ['alias' => 'red', 'name' => 'Akademinės naujienos'],
                ['alias' => 'yellow', 'name' => 'Socialinės naujienos'],
                ['alias' => 'grey', 'name' => 'Kita informacija'],
                ['alias' => 'freshmen-camps', 'name' => 'Pirmakursių stovykla'],
            ]);
        }

        // check if registration forms table is empty
        if (Schema::hasTable('registration_forms') && DB::table('registration_forms')->count() === 0) {
            // seed registration forms table with empty forms, to not cause errors
            DB::table('registration_forms')->insert([
                ['data' => '{}'], ['data' => '{}'],
            ]);
        }

        // check if padaliniai table is empty
        if (Schema::hasTable('padaliniai') && DB::table('padaliniai')->count() === 0) {
            // seed padaliniai table with default padaliniai
            DB::table('padaliniai')->insert([
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Istorijos fakultete', 'shortname' => 'VU SA IF', 'shortname_vu' => 'VU IF', 'alias' => 'if', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Fizikos fakultete', 'shortname' => 'VU SA FF', 'shortname_vu' => 'VU FF', 'alias' => 'ff', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Chemijos ir geomokslų fakultete', 'shortname' => 'VU SA CHGF', 'shortname_vu' => 'VU CHGF', 'alias' => 'chgf', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Ekonomikos ir verslo administravimo fakultete', 'shortname' => 'VU SA EVAF', 'shortname_vu' => 'VU EVAF', 'alias' => 'evaf', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Filologijos fakultete', 'shortname' => 'VU SA FilF', 'shortname_vu' => 'VU FlF', 'alias' => 'filf', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Filosofijos fakultete', 'shortname' => 'VU SA FsF', 'shortname_vu' => 'VU FsF', 'alias' => 'fsf', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Gyvybės mokslų centre', 'shortname' => 'VU SA GMC', 'shortname_vu' => 'VU GMC', 'alias' => 'gmc', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Komunikacijos fakultete', 'shortname' => 'VU SA KF', 'shortname_vu' => 'VU KF', 'alias' => 'kf', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Kauno fakultete', 'shortname' => 'VU SA KnF', 'shortname_vu' => 'VU KnF', 'alias' => 'knf', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Medicinos fakultete', 'shortname' => 'VU SA MF', 'shortname_vu' => 'VU MF', 'alias' => 'mf', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Matematikos ir informatikos fakultete', 'shortname_vu' => 'VU MIF', 'shortname' => 'VU SA MIF', 'alias' => 'mif', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Teisės fakultete', 'shortname' => 'VU SA TF', 'shortname_vu' => 'VU TF', 'alias' => 'tf', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Tarptautinių santykių ir politikos mokslų institute', 'shortname_vu' => 'VU TSPMI', 'shortname' => 'VU SA TSPMI', 'alias' => 'tspmi', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Verslo mokykloje', 'shortname' => 'VU SA VM', 'shortname_vu' => 'VU VM', 'alias' => 'vm', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė Šiaulių akademijoje', 'shortname' => 'VU SA ŠA', 'shortname_vu' => 'VU ŠA', 'alias' => 'sa', 'en' => 0, 'type' => 'padalinys'],
                ['fullname' => 'Vilniaus universiteto Studentų atstovybė', 'shortname' => 'VU SA', 'shortname_vu' => 'VU', 'alias' => 'vusa', 'en' => 0, 'type' => 'pagrindinis'],
            ]);
        }

        // check if navigation table is empty
        if (Schema::hasTable('navigation') && DB::table('navigation')->count() === 0) {
            // seed navigation table with default navigation
            DB::table('navigation')->insert(
                [
                    [
                        'parent_id' => 0,
                        'order' => 0,
                        'name' => 'VU SA nuorodos',
                        'url' => '#',
                    ], [
                        'parent_id' => 0,
                        'order' => 1,
                        'name' => 'Kontaktai',
                        'url' => '#',
                    ], [
                        'parent_id' => 0,
                        'order' => 2,
                        'name' => 'Administravimas',
                        'url' => route('login'),
                    ],  [
                        'parent_id' => 1,
                        'order' => 0,
                        'name' => 'Naujienos',
                        'url' => 'naujiena/archyvas',
                    ], [
                        'parent_id' => 1,
                        'order' => 1,
                        'name' => 'Narių registracija',
                        'url' => 'nariu-registracija',
                    ],  [
                        'parent_id' => 1,
                        'order' => 4,
                        'name' => 'Sąžiningai: registracija',
                        'url' => 'saziningai-registracija',
                    ],
                    [
                        'parent_id' => 1,
                        'order' => 5,
                        'name' => 'Sąžiningai: stebėjimas',
                        'url' => 'saziningai-uzregistruoti-egzaminai',
                    ],
                    [
                        'parent_id' => 2,
                        'order' => 0,
                        'name' => 'Pagrindiniai kontaktai',
                        'url' => 'kontaktai',
                    ],
                    [
                        'parent_id' => 2,
                        'order' => 1,
                        'name' => 'Kontaktų paieška',
                        'url' => 'kontaktai/paieska',
                    ],
                ],
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
