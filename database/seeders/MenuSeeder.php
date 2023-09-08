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
                    'url' => route('login', ['lang' => 'lt', 'subdomain' => 'www']),
                ],  [
                    'parent_id' => 1,
                    'order' => 0,
                    'name' => 'Naujienos',
                    'url' => route('newsArchive', ['lang' => 'lt', 'subdomain' => 'www']),
                ], [
                    'parent_id' => 1,
                    'order' => 1,
                    'name' => 'Narių registracija',
                    'url' => route('memberRegistration', ['lang' => 'lt']),
                ],  [
                    'parent_id' => 1,
                    'order' => 4,
                    'name' => 'Sąžiningai: registracija',
                    'url' => route('saziningaiExamRegistration', ['lang' => 'lt']),
                ],
                [
                    'parent_id' => 1,
                    'order' => 5,
                    'name' => 'Sąžiningai: stebėjimas',
                    'url' => route('saziningaiExams.registered', ['lang' => 'lt']),
                ],
                [
                    'parent_id' => 2,
                    'order' => 0,
                    'name' => 'Kontaktų paieška',
                    'url' => route('contacts', ['lang' => 'lt', 'subdomain' => 'www']),
                ],
                [
                    'parent_id' => 2,
                    'order' => 1,
                    'name' => 'VU SA padaliniai',
                    'url' => route('contacts.category', ['lang' => 'lt', 'subdomain' => 'www', 'type' => 'padaliniai']),
                ],
            ],
        );
    }
}
