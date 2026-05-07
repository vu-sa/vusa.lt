<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CategoriesSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(TenantSeeder::class);
        $this->call(TypeSeeder::class);
        $this->call(DeleteAndSeedPermissions::class);
        $this->call(RoleStudentRepresentativeSeeder::class);
        $this->call(RoleStudentRepresentativeCoordinatorSeeder::class);
        $this->call(RoleCommunicationCoordinatorSeeder::class);
        $this->call(RoleGlobalCommunicationCoordinatorSeeder::class);
        $this->call(RoleResourceManagerSeeder::class);
    }
}
