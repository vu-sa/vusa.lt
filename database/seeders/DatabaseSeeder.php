<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Doing;
use App\Models\Duty;
use App\Models\Goal;
use App\Models\Institution;
use App\Models\MainPage;
use App\Models\Matter;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Padalinys;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use App\Models\SaziningaiExamObserver;
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
        // $this->call(RegistrationFormsSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(PadaliniaiSeeder::class);
        $this->call(TypeSeeder::class);

        $padaliniai = Padalinys::all();

        Institution::factory(10)
            ->has(Matter::factory(3))
            ->has(Meeting::factory(3)->has(AgendaItem::factory(3)))
            ->recycle($padaliniai)
            ->create();

        $users = User::factory(25)
            ->has(Doing::factory(5))->hasAttached(Duty::factory(3), ['start_date' => now()])
            ->create();

        $this->call(MenuSeeder::class);
        $this->call(DeleteAndSeedPermissions::class);

        Banner::factory(20)->recycle($padaliniai)->create();
        Calendar::factory(50)->recycle($padaliniai)->create();
        News::factory(75)->recycle($padaliniai)->create();
        Page::factory(75)->recycle($padaliniai)->create();

        Resource::factory(50)->has(Reservation::factory()->hasAttached($users->random(3)))->recycle($padaliniai)->create();
        SaziningaiExam::factory(15)->create();
        SaziningaiExamFlow::factory(20)->create();
        SaziningaiExamObserver::factory(10)->create();

        $this->call(RoleStudentRepresentativeSeeder::class);
        $this->call(RoleStudentRepresentativeCoordinatorSeeder::class);

        Goal::factory(10)->recycle($padaliniai)->create();

        foreach ($padaliniai as $padalinys) {
            MainPage::factory(6)
                ->recycle($padalinys)
                ->state(new Sequence(
                    ['lang' => 'lt', 'link' => '/lt/kontaktai/koordinatoriai', 'text' => 'Koordinatoriai'],
                    ['lang' => 'en', 'link' => '/en/kontaktai/koordinatoriai', 'text' => 'Coordinators'],
                    ['lang' => 'lt', 'link' => '/lt/kontaktai/kuratoriai', 'text' => 'Kuratoriai'],
                    ['lang' => 'en', 'link' => '/en/kontaktai/kuratoriai', 'text' => 'Curators'],
                    ['lang' => 'lt', 'link' => '/lt/kontaktai/studentu-atstovai', 'text' => 'Studentų atstovai'],
                    ['lang' => 'en', 'link' => '/en/kontaktai/studentu-atstovai', 'text' => 'Studentų atstovai']
                ))
                ->create();


        }

        MainPage::factory(200)->recycle($padaliniai)->create();

    }
}
