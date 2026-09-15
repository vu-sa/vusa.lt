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

        DB::table('event_types')->updateOrInsert(
            ['slug' => 'susirinkimas'],
            [
                'name' => json_encode(['lt' => 'Susirinkimas', 'en' => 'Assembly']),
                'is_active' => true,
                'sort_order' => 3,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ]
        );

        // Return any assignments of the retired catch-all type to the untyped review queue.
        DB::table('event_types')->where('slug', 'renginys')->delete();

        $posedisId = DB::table('event_types')->where('slug', 'posedis')->value('id');
        $susirinkimasId = DB::table('event_types')->where('slug', 'susirinkimas')->value('id');

        if ($posedisId !== null && $susirinkimasId !== null) {
            DB::table('calendar')
                ->where('event_type_id', $posedisId)
                ->where('title->lt', 'like', '%susirinkim%')
                ->update(['event_type_id' => $susirinkimasId]);
        }

        foreach ([
            'atstovavimas',
            'posedis',
            'susirinkimas',
            'mokymai',
            'konferencija',
            'rinkimai',
            'stovykla',
            'terminas',
        ] as $index => $slug) {
            DB::table('event_types')->where('slug', $slug)->update([
                'sort_order' => $index + 1,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * The retired type and any former assignments cannot be reconstructed.
     */
    public function down(): void {}
};
