<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            [
                'name' => 'admin',
                'email' => 'test@test.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'gid' => 1
            ],
            [
                'name' => 'padalinys',
                'email' => 'if@test.comb',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'gid' => 4
            ],
            [
                'name' => 'saziningai',
                'email' => 'if@test.coma',
                'email_verified_at' => now(),
                'password' => Hash::make('saziningai'),
                'gid' => 19
            ]
        ]);
    }
}
