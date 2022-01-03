<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Contact;
use App\Models\MainPage;
use App\Models\News;
use App\Models\Page;
use App\Models\Saziningai;
use App\Models\Saziningai_people;
use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {  
        $this->call(UserSeeder::class);
        User::factory()->count(10)->create();
        
        $this->call(ContactsSeeder::class);
        $this->call(MenuSeeder::class);
        $this->call(NewsCatsSeeder::class);
        $this->call(PadaliniaiSeeder::class);
        $this->call(PageCatsSeeder::class);
        $this->call(UserGroupSeeder::class);
        
        Agenda::factory()->count(10)->create();
        Calendar::factory()->count(10)->create();
        Contact::factory()->count(10)->create();
        MainPage::factory()->count(9)->create();
        News::factory()->count(25)->create();
        Page::factory()->count(25)->create();
        Saziningai::factory()->count(10)->create();
        // Saziningai_people::factory()->count(10)->create();
        Banner::factory()->count(25)->create();
        
    }
}