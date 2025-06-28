<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Category;
use App\Models\Doing;
use App\Models\Duty;
use App\Models\Goal;
use App\Models\Institution;
use App\Models\Matter;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Pivots\Dutiable;
use App\Models\QuickLink;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\StudyProgram;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(CategoriesSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(TenantSeeder::class);
        $this->call(TypeSeeder::class);
        $this->call(MemberRegistrationFormSeeder::class);

        $tenants = Tenant::all();
        $categories = Category::all();

        // Create study programs for each tenant
        $studyPrograms = StudyProgram::factory(30)->recycle($tenants)->create();

        Institution::factory(50)
            ->has(Matter::factory(3))
            ->has(Meeting::factory(3)->has(AgendaItem::factory(3)))
            ->recycle($tenants)
            ->withType()
            ->create();

        Institution::factory()
            ->state(['name' => 'Centrinis biuras', 'alias' => 'centrinis-biuras'])
            ->withType()
            ->recycle(Tenant::where('alias', 'vusa')->get())
            ->has(Duty::factory(10)->withType()->hasAttached(
                User::factory()->recycle($studyPrograms), 
                ['start_date' => now()->subDay()]
            ))
            ->create();

        $users = User::factory(25)
            ->recycle($studyPrograms)
            ->has(Doing::factory(5)->withType())
            ->hasAttached(Duty::factory(3)->withType(), ['start_date' => now()->subDay()])
            ->create();

        // After all dutiables are created, randomly assign study programs to 60% of them
        $this->assignStudyProgramsToDutiables($studyPrograms);

        $this->call(MenuSeeder::class);
        $this->call(DeleteAndSeedPermissions::class);

        Banner::factory(20)->recycle($tenants)->create();
        Calendar::factory(50)->recycle($tenants)->recycle($categories)->create();
        News::factory(75)->recycle($tenants)->create();
        Page::factory(75)->recycle($tenants)->create();

        Resource::factory(50)->has(Reservation::factory()->hasAttached($users->random(3)))->recycle($tenants)->create();

        $this->call(RoleStudentRepresentativeSeeder::class);
        $this->call(RoleStudentRepresentativeCoordinatorSeeder::class);
        $this->call(RoleCommunicationCoordinatorSeeder::class);
        $this->call(RoleResourceManagerSeeder::class);

        Goal::factory(10)->recycle($tenants)->create();

        foreach ($tenants as $tenant) {
            QuickLink::factory(6)
                ->recycle($tenant)
                ->state(new Sequence(['lang' => 'lt'], ['lang' => 'en']))
                ->state(new Sequence(
                    ['link' => '/lt/kontaktai/koordinatoriai', 'text' => 'Koordinatoriai'],
                    ['link' => '/en/kontaktai/koordinatoriai', 'text' => 'Coordinators'],
                    ['link' => '/lt/kontaktai/kuratoriai', 'text' => 'Kuratoriai'],
                    ['link' => '/en/kontaktai/kuratoriai', 'text' => 'Curators'],
                    ['link' => '/lt/kontaktai/studentu-atstovai', 'text' => 'Studentų atstovai'],
                    ['link' => '/en/kontaktai/studentu-atstovai', 'text' => 'Student representatives'],
                ))->create();
        }

        QuickLink::factory(200)->recycle($tenants)->create();
    }

    /**
     * Assign study programs to approximately 60% of dutiables
     */
    private function assignStudyProgramsToDutiables($studyPrograms)
    {
        Dutiable::chunk(50, function ($dutiables) use ($studyPrograms) {
            foreach ($dutiables as $dutiable) {
                // 60% chance to assign a study program
                if (fake()->boolean(60)) {
                    $dutiable->study_program_id = $studyPrograms->random()->id;
                    $dutiable->save();
                }
            }
        });
    }
}
