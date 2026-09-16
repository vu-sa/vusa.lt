<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        $eventTypes = [
            ['atstovavimas', 'Atstovavimas', 'Representation'],
            ['posedis', 'Posėdis', 'Meeting'],
            ['susirinkimas', 'Susirinkimas', 'Assembly'],
            ['mokymai', 'Mokymai', 'Training'],
            ['konferencija', 'Konferencija', 'Conference'],
            ['rinkimai', 'Rinkimai', 'Elections'],
            ['stovykla', 'Stovykla', 'Camp'],
            ['terminas', 'Terminas', 'Deadline'],
        ];

        $rows = [];

        foreach ($eventTypes as $index => [$slug, $nameLt, $nameEn]) {
            $rows[] = [
                'slug' => $slug,
                'name' => json_encode(['lt' => $nameLt, 'en' => $nameEn]),
                'is_active' => true,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];
        }

        DB::table('event_types')->upsert(
            $rows,
            ['slug'],
            ['name', 'is_active', 'sort_order', 'updated_at', 'deleted_at'],
        );

        Cache::forget('all-event-types-for-inertia');
    }
}
