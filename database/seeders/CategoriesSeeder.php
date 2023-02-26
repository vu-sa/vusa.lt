<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('categories')->insert([
            ['alias' => 'red', 'name' => 'Akademinės naujienos'],
            ['alias' => 'yellow', 'name' => 'Socialinės naujienos'],
            ['alias' => 'grey', 'name' => 'Kita informacija'],
            ['alias' => 'freshmen-camps', 'name' => 'Pirmakursių stovykla'],
        ]);
    }
}
