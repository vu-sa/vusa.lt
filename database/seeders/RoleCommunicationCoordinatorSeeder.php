<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Type;
use Illuminate\Database\Seeder;

class RoleCommunicationCoordinatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $role = new Role;

        $role->name = 'Communication Coordinator';
        $role->guard_name = 'web';

        $role->save();

        $role->syncPermissions([
            'news.create.padalinys',
            'news.read.padalinys',
            'news.update.padalinys',
            'news.delete.padalinys',
            'pages.create.padalinys',
            'pages.read.padalinys',
            'pages.update.padalinys',
            'pages.delete.padalinys',
            'banners.create.padalinys',
            'banners.read.padalinys',
            'banners.update.padalinys',
            'banners.delete.padalinys',
            'calendars.create.padalinys',
            'calendars.read.padalinys',
            'calendars.update.padalinys',
            'calendars.delete.padalinys',
            'quickLinks.create.padalinys',
            'quickLinks.read.padalinys',
            'quickLinks.update.padalinys',
            'quickLinks.delete.padalinys',
            'users.create.padalinys',
            'users.read.padalinys',
            'users.update.padalinys',
            'institutions.create.padalinys',
            'institutions.read.padalinys',
            'institutions.update.padalinys',
            'files.create.padalinys',
            'files.read.padalinys',
            'files.update.padalinys',
            'files.delete.padalinys',
            'duties.create.padalinys',
            'duties.read.padalinys',
            'duties.update.padalinys',
            'duties.delete.padalinys',
        ]);

        $role->attachable_types()->attach(Type::query()->where('slug', 'pirmininkas')->firstOrFail());

        $role->attachable_types()->attach(Type::query()->where('slug', 'koordinatoriai')->firstOrFail());

        $role->attachable_types()->attach(Type::query()->where('slug', 'kuratoriai')->firstOrFail());
    }
}
