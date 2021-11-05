<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('menu_new')->insert(
            [
                [
                    'pid' => 0,
                    'order' => 0,
                    'text' => 'VU SA nuorodos',
                    'url' => '/#',
                    'creator' => 1
                ], [
                    'pid' => 0,
                    'order' => 1,
                    'text' => 'Kontaktai',
                    'url' => '/#',
                    'creator' => 1
                ], [
                    'pid' => 0,
                    'order' => 2,
                    'text' => 'Padaliniai',
                    'url' => '/#',
                    'creator' => 1
                ], [
                    'pid' => 0,
                    'order' => 3,
                    'text' => 'Kita',
                    'url' => '/#',
                    'creator' => 1
                ], [
                    'pid' => 1,
                    'order' => 0,
                    'text' => 'Naujienos',
                    'url' => '/naujiena/archyvas',
                    'creator' => 1
                ], [
                    'pid' => 1,
                    'order' => 1,
                    'text' => 'Paieška',
                    'url' => '/paieska',
                    'creator' => 1
                ], [
                    'pid' => 1,
                    'order' => 2,
                    'text' => 'Renginiai',
                    'url' => '/renginiai',
                    'creator' => 1
                ], [
                    'pid' => 1,
                    'order' => 3,
                    'text' => 'Darbotvarkė',
                    'url' => '/darbotvarke',
                    'creator' => 1
                ], [
                    'pid' => 1,
                    'order' => 4,
                    'text' => 'Sąžiningai: registracija',
                    'url' => '/saziningai-registracija',
                    'creator' => 1
                ],
                [
                    'pid' => 1,
                    'order' => 5,
                    'text' => 'Sąžiningai: stebėjimas',
                    'url' => '/registracija-stebejimui',
                    'creator' => 1
                ],
                [
                    'pid' => 2,
                    'order' => 0,
                    'text' => 'Centrinis biuras',
                    'url' => '/kontaktai/centrinis-biuras',
                    'creator' => 1
                ],
                [

                    'pid' => 3,
                    'order' => 0,
                    'text' => 'Istorijos fakultete',
                    'url' => 'http://if.vusa.testas:8000',
                    'creator' => 1
                ]
            ],
        );
    }
}
