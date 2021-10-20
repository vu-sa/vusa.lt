<?php

namespace Database\Seeders;

use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->faker = Faker::create();
        
        DB::table('contacts')->insert([
            ['groupname' => 'aprasymas', 'infoText' => '<p>' . $this->faker->paragraph(2), 'image' => '/images/placeholders/foto' . rand(1,5) . '.jpg', 'grouptitle' => 'centrinis-biuras']]);
    }
}