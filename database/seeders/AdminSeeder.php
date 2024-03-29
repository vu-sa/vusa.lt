<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Factory::create();

        User::create([
            'name' => 'Test User',
            'email' => 'test@test.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'profile_photo_path' => $faker->imageUrl(640, 480, 'people', true),
        ]);

        Role::firstOrCreate(['name' => config('permission.super_admin_role_name'), 'guard_name' => 'web']);

        User::where('email', 'test@test.com')->first()->assignRole(config('permission.super_admin_role_name'));
    }
}
