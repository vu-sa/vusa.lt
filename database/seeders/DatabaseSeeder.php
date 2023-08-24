<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Doing;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\MainPage;
use App\Models\Matter;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Registration;
use App\Models\RegistrationForm;
use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use App\Models\SaziningaiExamObserver;
use App\Models\User;
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

        Institution::factory(10)
            ->has(Matter::factory(3))
            ->has(Meeting::factory(3)->has(AgendaItem::factory(3)))
            ->create();

        $users = User::factory(25)
            ->has(Doing::factory(5))->hasAttached(Duty::factory(3), ['start_date' => now()])
            ->create();

        $this->call(MenuSeeder::class);
        $this->call(DeleteAndSeedPermissions::class);

        Banner::factory(20)->create();
        Calendar::factory(50)->create();
        MainPage::factory(50)->create();
        News::factory(75)->create();
        Page::factory(75)->create();

        Resource::factory(50)->has(Reservation::factory()->hasAttached($users->random(3)))->create();
        SaziningaiExam::factory(15)->create();
        SaziningaiExamFlow::factory(20)->create();
        SaziningaiExamObserver::factory(10)->create();

        $this->call(RoleStudentRepresentativeSeeder::class);
        $this->call(RoleStudentRepresentativeCoordinatorSeeder::class);
    }
}
