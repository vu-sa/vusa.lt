<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\DutyType;
use Illuminate\Database\Seeder;

class RoleGlobalCommunicationCoordinatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = Role::firstOrCreate([
            'name' => 'Centrinio biuro komunikacijos koordinatorius',
            'guard_name' => 'web',
        ]);

        $role->syncPermissions([
            // Global tag management
            'tags.create.*',
            'tags.read.*',
            'tags.update.*',
            'tags.delete.*',
            // Global navigation management (note: navigations, not navigation)
            'navigations.create.*',
            'navigations.read.*',
            'navigations.update.*',
            'navigations.delete.*',
            // Global event type management
            'eventTypes.create.*',
            'eventTypes.read.*',
            'eventTypes.update.*',
            'eventTypes.delete.*',
            // Content, institutions and people in every padalinys, as in production
            'banners.create.*',
            'banners.read.*',
            'banners.update.*',
            'banners.delete.*',
            'calendars.create.*',
            'calendars.read.*',
            'calendars.update.*',
            'calendars.delete.*',
            'duties.create.*',
            'duties.read.*',
            'duties.update.*',
            'duties.delete.*',
            'files.create.*',
            'files.read.*',
            'files.update.*',
            'files.delete.*',
            'institutions.create.*',
            'institutions.read.*',
            'institutions.update.*',
            'institutions.delete.*',
            'news.create.*',
            'news.read.*',
            'news.update.*',
            'news.delete.*',
            'pages.create.*',
            'pages.read.*',
            'pages.update.*',
            'pages.delete.*',
            'quickLinks.create.*',
            'quickLinks.read.*',
            'quickLinks.update.*',
            'quickLinks.delete.*',
            'users.create.*',
            'users.read.*',
            'users.update.*',
            'users.delete.*',
            'problems.create.*',
            'problems.update.*',
        ]);

        // This role can be attached to high-level coordination types
        $role->attachable_types()->attach(DutyType::query()->where('slug', 'pirmininkas')->firstOrFail());
    }
}
