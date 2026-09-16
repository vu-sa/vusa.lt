<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        $types = [
            ['atstovavimas', 'Atstovavimas', 'Representation'],
            ['posedis', 'Posėdis', 'Meeting'],
            ['susirinkimas', 'Susirinkimas', 'Assembly'],
            ['mokymai', 'Mokymai', 'Training'],
            ['konferencija', 'Konferencija', 'Conference'],
            ['rinkimai', 'Rinkimai', 'Elections'],
            ['stovykla', 'Stovykla', 'Camp'],
            ['terminas', 'Terminas', 'Deadline'],
        ];

        foreach ($types as $index => [$slug, $lt, $en]) {
            DB::table('event_types')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => json_encode(['lt' => $lt, 'en' => $en]),
                    'is_active' => true,
                    'sort_order' => $index + 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('event_types')->whereIn('slug', [
            'atstovavimas', 'posedis', 'susirinkimas', 'mokymai',
            'konferencija', 'rinkimai', 'stovykla', 'terminas',
        ])->delete();
    }
};
