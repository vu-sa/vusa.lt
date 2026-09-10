<?php

namespace Database\Seeders;

use App\Models\SupportRequestArea;
use App\Models\SupportRequestType;
use App\Models\SupportService;
use Illuminate\Database\Seeder;

class SupportRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $service = SupportService::query()->firstOrCreate(['slug' => 'vusa-lt'], [
            'name' => ['lt' => 'vusa.lt', 'en' => 'vusa.lt'], 'is_active' => true, 'sort_order' => 1,
        ]);

        foreach ([
            ['bug', 'Klaida', 'Bug'], ['suggestion', 'Pasiūlymas', 'Suggestion'], ['question', 'Klausimas', 'Question'],
        ] as $index => [$slug, $lt, $en]) {
            SupportRequestType::query()->firstOrCreate(['slug' => $slug], ['name' => ['lt' => $lt, 'en' => $en], 'is_active' => true, 'sort_order' => $index + 1]);
        }

        foreach ([
            ['public-site', 'Vieša svetainė', 'Public site'], ['admin-system', 'Administravimo sistema', 'Admin system'], ['account-access', 'Paskyra ir prieiga', 'Account and access'], ['content', 'Turinys', 'Content'], ['other', 'Kita', 'Other'],
        ] as $index => [$slug, $lt, $en]) {
            SupportRequestArea::query()->firstOrCreate(['support_service_id' => $service->id, 'slug' => $slug], ['name' => ['lt' => $lt, 'en' => $en], 'is_active' => true, 'sort_order' => $index + 1]);
        }
    }
}
