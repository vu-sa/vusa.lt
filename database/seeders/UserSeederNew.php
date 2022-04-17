<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeederNew extends Seeder
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
                'name' => 'Test User',
                'email' => 'test@test.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role_id' => 1
            ]
        ]);
    }
}
