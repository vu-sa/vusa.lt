<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PageCatsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('page_cats')->insert([
            ['name' => 'Akademinė informacija'],
            ['name' => 'Socialinė informacija'],
            ['name' => 'Kita informacija']]);
    }
}