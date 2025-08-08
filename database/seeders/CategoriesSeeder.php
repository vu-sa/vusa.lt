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
            ['alias' => 'red', 'name' => json_encode(['lt' => 'Akademinės naujienos', 'en' => 'Academic news'])],
            ['alias' => 'yellow', 'name' => json_encode(['lt' => 'Socialinės naujienos', 'en' => 'Social news'])],
            ['alias' => 'grey', 'name' => json_encode(['lt' => 'Kita informacija', 'en' => 'Other information'])],
            ['alias' => 'freshmen-camps', 'name' => json_encode(['lt' => 'Pirmakursių stovykla', 'en' => 'Freshmen camps'])],
        ]);
    }
}
