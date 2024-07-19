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

        Institution::factory(50)
            ->has(Matter::factory(3))
            ->has(Meeting::factory(3)->has(AgendaItem::factory(3)))
            ->recycle($padaliniai)
            ->withType()
            ->create();

        Institution::factory()
            ->state(['name' => 'Centrinis biuras', 'alias' => 'centrinis-biuras'])
            ->withType()
            ->recycle(Padalinys::where('alias', 'vusa')->get())
            ->has(Duty::factory(10)->withType()->hasAttached(User::factory(), ['start_date' => now()->subDay()]))
            ->create();

        $users = User::factory(25)
            ->has(Doing::factory(5)->withType())->hasAttached(Duty::factory(3)->withType(), ['start_date' => now()->subDay()])
            ->create();

        $this->call(MenuSeeder::class);
        $this->call(DeleteAndSeedPermissions::class);

        Banner::factory(20)->recycle($padaliniai)->create();
        Calendar::factory(50)->recycle($padaliniai)->create();
        News::factory(75)->recycle($padaliniai)->create();
        Page::factory(75)->recycle($padaliniai)->create();

        Resource::factory(50)->has(Reservation::factory()->hasAttached($users->random(3)))->recycle($padaliniai)->create();

        $this->call(RoleStudentRepresentativeSeeder::class);
        $this->call(RoleStudentRepresentativeCoordinatorSeeder::class);
        $this->call(RoleCommunicationCoordinatorSeeder::class);

        Goal::factory(10)->recycle($padaliniai)->create();

        foreach ($padaliniai as $padalinys) {
            MainPage::factory(6)
                ->recycle($padalinys)
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

        MainPage::factory(200)->recycle($padaliniai)->create();
    }
}
