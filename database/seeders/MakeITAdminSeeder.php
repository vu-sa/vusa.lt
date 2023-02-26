<?php

namespace Database\Seeders;

use App\Models\Duty;
use App\Models\User;
use Illuminate\Database\Seeder;

class MakeITAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Duty::with('users')->where('email', 'it@vusa.lt')->get()->users->assignRole(config('permission.super_admin_role_name'));
        User::where('email', 'it@vusa.lt')->first()->assignRole(config('permission.super_admin_role_name'));
    }
}
