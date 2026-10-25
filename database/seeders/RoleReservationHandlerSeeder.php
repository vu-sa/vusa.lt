<?php

namespace Database\Seeders;

use App\Enums\ResponsibilityPurpose;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleReservationHandlerSeeder extends Seeder
{
    public const string NAME = 'rezervaciju-administravimas';

    public function run(): void
    {
        if (Role::forPurpose(ResponsibilityPurpose::ReservationHandling) !== null) {
            return;
        }

        Role::create([
            'name' => Role::generateName(self::NAME),
            'guard_name' => 'web',
            'title' => ['lt' => 'Rezervacijų administravimas', 'en' => 'Reservation handling'],
            'purpose' => ResponsibilityPurpose::ReservationHandling,
        ]);
    }
}
