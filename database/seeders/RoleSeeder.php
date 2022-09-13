<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->insert([
            ['description' => 'Administratoriai gali valdyti visas puslapio funkcijas, matyti visus padalinius ir redaguoti jų informaciją. Administratoriai taip pat gali redaguoti kitų vartotojų roles.', 'alias' => 'admin', 'name' => 'Administratorius'],
            ['description' => 'Padalinių administratoriai dažniausiai yra komunikacijos koordinatoriai padaliniuose ir gali valdyti savo padalinio naujienas, puslapius, vartotojus (žmonės), pareigybes ir institucijas.', 'alias' => 'padaliniai-admin', 'name' => 'padaliniai-admin'],
            ['description' => 'Kalendoriaus administratoriai pildo kalendoriaus įvykius.', 'alias' => 'kalendorius', 'name' => 'Kalendoriaus administratoriai'],
            ['description' => '„Sąžiningai“ administratoriai gali koreguoti egzaminų informaciją, kurti ir redaguoti srautus, pasižiūrėti prisiregistravusių kontaktinę informaciją ir pažymėti jų stebėjimo dalyvavimą. Prisiregistruoti prie egzamino kolkas galima tik per viešą registraciją.', 'alias' => 'saziningai', 'name' => '„Sąžiningai“ administratoriai'],
            ['description' => 'Kiti naudotojai yra naudotojų grupė, kuri neturi priskirtos rolės. Šiuo metu tokie vartotojai gali tik pažiūrėti failus ir juos įkelti. Jeigu matote, kad Tau priskirta šį rolė, galėjo įsivelti klaida. Dėl visa ko, susisiekite su it@vusa.lt', 'alias' => 'naudotojai', 'name' => 'Kiti naudotojai']
        ]);
    }
}
