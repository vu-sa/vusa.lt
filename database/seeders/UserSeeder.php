<?php

namespace Database\Seeders;

use Faker\Factory;
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
        $faker = Factory::create();
        
        DB::table('users')->insert([
            [
                'name' => 'Test User',
                'email' => 'test@test.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'role_id' => 1,
                'profile_photo_path' => $faker->imageUrl(640, 480, 'people', true),
            ]
        ]);
    }
}
