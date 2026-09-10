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

        DB::table('support_services')->updateOrInsert(
            ['slug' => 'vusa-lt'],
            [
                'name' => json_encode(['lt' => 'vusa.lt', 'en' => 'vusa.lt']),
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $types = [
            ['bug', 'Klaida', 'Bug'],
            ['suggestion', 'Pasiūlymas', 'Suggestion'],
            ['question', 'Klausimas', 'Question'],
        ];

        foreach ($types as $index => [$slug, $lt, $en]) {
            DB::table('support_request_types')->updateOrInsert(
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

        $serviceId = DB::table('support_services')->where('slug', 'vusa-lt')->value('id');

        $areas = [
            ['public-site', 'Vieša svetainė', 'Public site'],
            ['admin-system', 'Administravimo sistema', 'Admin system'],
            ['account-access', 'Paskyra ir prieiga', 'Account and access'],
            ['content', 'Turinys', 'Content'],
            ['other', 'Kita', 'Other'],
        ];

        foreach ($areas as $index => [$slug, $lt, $en]) {
            DB::table('support_request_areas')->updateOrInsert(
                ['support_service_id' => $serviceId, 'slug' => $slug],
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
        $serviceId = DB::table('support_services')->where('slug', 'vusa-lt')->value('id');

        if ($serviceId) {
            DB::table('support_request_areas')->where('support_service_id', $serviceId)->delete();
        }

        DB::table('support_request_types')->whereIn('slug', ['bug', 'suggestion', 'question'])->delete();
        DB::table('support_services')->where('slug', 'vusa-lt')->delete();
    }
};
