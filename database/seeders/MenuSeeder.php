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
                    'name' => 'VU SA padaliniai',
                    'url' => 'kontaktai/kategorija/padaliniai',
                ],
                [
                    'parent_id' => 2,
                    'order' => 2,
                    'name' => 'Kontaktų paieška',
                    'url' => 'kontaktai/paieska',
                ],
            ],
        );
    }
}
