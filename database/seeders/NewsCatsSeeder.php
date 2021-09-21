<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsCatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('news_cats')->insert([
            ['name' => 'Akademinės naujienos'],
            ['name' => 'Socialinės naujienos'],
            ['name' => 'Kita informacija']]);
    }
}