<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Banner;
use App\Models\Calendar;
use App\Models\Contact;
use App\Models\Duty;
use App\Models\MainPage;
use App\Models\News;
use App\Models\Page;
use App\Models\Saziningai;
use App\Models\Saziningai_people;
use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use App\Models\SaziningaiExamObserver;
use Illuminate\Database\Seeder;
use App\Models\User;

class OldDatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {  
        $this->call(CategoriesSeeder::class);
        $this->call(DutyInstitutionTypesSeeder::class);
        $this->call(DutyTypesSeeder::class);
        $this->call(RegistrationFormsSeeder::class);
        $this->call(UserSeeder::class);
        $this->call(PadaliniaiSeeder::class);
        
        User::factory()->has(Duty::factory()->count(3))->count(10)->create();
        
        $this->call(MenuSeeder::class);
        
        Calendar::factory()->count(10)->create();
        MainPage::factory()->count(9)->create();
        News::factory()->count(25)->create();
        Page::factory()->count(25)->create();
        SaziningaiExam::factory()->count(10)->create();
        SaziningaiExamFlow::factory()->count(10)->create();
        SaziningaiExamObserver::factory()->count(10)->create();
        
    }
}