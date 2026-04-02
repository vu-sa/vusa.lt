<?php

namespace Database\Seeders;

use App\Models\ProblemCategory;
use Illuminate\Database\Seeder;

class ProblemCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => [
                    'lt' => 'Komunikacija',
                    'en' => 'Communication',
                ],
                'slug' => 'communication',
                'description' => [
                    'lt' => 'Problemos susijusios su komunikacija, informacijos sklaida ar bendravimu',
                    'en' => 'Problems related to communication, information dissemination or interaction',
                ],
            ],
            [
                'name' => [
                    'lt' => 'Procesai',
                    'en' => 'Processes',
                ],
                'slug' => 'processes',
                'description' => [
                    'lt' => 'Problemos susijusios su organizaciniais procesais ir procedūromis',
                    'en' => 'Problems related to organizational processes and procedures',
                ],
            ],
            [
                'name' => [
                    'lt' => 'Techninės problemos',
                    'en' => 'Technical Issues',
                ],
                'slug' => 'technical',
                'description' => [
                    'lt' => 'Techninės problemos, IT infrastruktūra, įranga',
                    'en' => 'Technical problems, IT infrastructure, equipment',
                ],
            ],
            [
                'name' => [
                    'lt' => 'Administracinės problemos',
                    'en' => 'Administrative Issues',
                ],
                'slug' => 'administrative',
                'description' => [
                    'lt' => 'Administracinės problemos, dokumentacija, biurokratija',
                    'en' => 'Administrative problems, documentation, bureaucracy',
                ],
            ],
            [
                'name' => [
                    'lt' => 'Žmogiškieji ištekliai',
                    'en' => 'Human Resources',
                ],
                'slug' => 'human-resources',
                'description' => [
                    'lt' => 'Problemos susijusios su komandos nariais, savanoriais, motyvavimu',
                    'en' => 'Problems related to team members, volunteers, motivation',
                ],
            ],
            [
                'name' => [
                    'lt' => 'Kita',
                    'en' => 'Other',
                ],
                'slug' => 'other',
                'description' => [
                    'lt' => 'Kitos problemos, nepriskiriamos kitoms kategorijoms',
                    'en' => 'Other problems not assigned to other categories',
                ],
            ],
        ];

        foreach ($categories as $category) {
            ProblemCategory::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
