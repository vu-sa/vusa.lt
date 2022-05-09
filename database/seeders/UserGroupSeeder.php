<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users_groups')->insert([
            ['descr' => 'Administratoriai', 'alias' => 'admin'],
            ['descr' => 'Komunikacija', 'alias' => 'komunikacija'],
            ['descr' => 'Kalendorius', 'alias' => 'kalendorius'],
            ['descr' => 'VU SA IF', 'alias' => 'vusaif'],
            ['descr' => 'VU SA Other', 'alias' => 'vusaother']
        ]);
    }
}
