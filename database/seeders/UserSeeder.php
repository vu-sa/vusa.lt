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
        DB::table('users')->insert([[
            'username' => 'admin',
            'realname' => 'Administrator',
            'email' => 'test@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'gid' => 1
        ],
        [
        'username' => 'padalinys',
        'realname' => 'Padalinys (VU SA IF)',
        'email' => 'if@test.com',
        'email_verified_at' => now(),
        'password' => Hash::make('password'),
        'gid' => 4
        ],
        [
        'username' => 'saziningai',
        'realname' => 'Saziningai',
        'email' => 'if@test.com',
        'email_verified_at' => now(),
        'password' => Hash::make('saziningai'),
        'gid' => 19
        ]]);
    }
}