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
                'realname' => 'admin',
                'username' => 'test@test.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'gid' => 1
            ],
            [
                'realname' => 'padalinys',
                'username' => 'if@test.comb',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'gid' => 4
            ],
            [
                'realname' => 'saziningai',
                'username' => 'if@test.coma',
                'email_verified_at' => now(),
                'password' => Hash::make('saziningai'),
                'gid' => 19
            ]
        ]);
    }
}
