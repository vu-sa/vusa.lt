<?php

use App\Models\Meeting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add the type column (nullable)
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('type')->nullable()->after('description');
        });

        // 2. Migrate data from typeables pivot table
        $meetingTypes = DB::table('types')
            ->where('model_type', Meeting::class)
            ->pluck('slug', 'id');

        // Map old Type slugs to new enum values
        $slugToEnum = [
            'in-person-meeting' => 'in-person',
            'remote-meeting' => 'remote',
            'email-meeting' => 'email',
        ];

        // Get all meeting type associations
        $typeables = DB::table('typeables')
            ->where('typeable_type', Meeting::class)
            ->get();

        foreach ($typeables as $typeable) {
            $slug = $meetingTypes[$typeable->type_id] ?? null;
            $enumValue = $slugToEnum[$slug] ?? null;

            if ($enumValue) {
                DB::table('meetings')
                    ->where('id', $typeable->typeable_id)
                    ->update(['type' => $enumValue]);
            }
        }

        // 3. Clean up orphaned pivot records for meetings
        DB::table('typeables')
            ->where('typeable_type', Meeting::class)
            ->delete();

        // 4. Remove meeting types from types table
        DB::table('types')
            ->where('model_type', Meeting::class)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the meeting types in types table
        $meetingTypes = [
            ['slug' => 'in-person-meeting', 'title' => json_encode(['lt' => 'Gyvas susitikimas', 'en' => 'In-person Meeting']), 'description' => json_encode(['en' => null]), 'model_type' => Meeting::class],
            ['slug' => 'remote-meeting', 'title' => json_encode(['lt' => 'Nuotolinis susitikimas', 'en' => 'Remote Meeting']), 'description' => json_encode(['en' => null]), 'model_type' => Meeting::class],
            ['slug' => 'email-meeting', 'title' => json_encode(['lt' => 'Elektroninis posėdis (el. laišku)', 'en' => 'E-meeting (via email)']), 'description' => json_encode(['en' => null]), 'model_type' => Meeting::class],
        ];

        $slugToEnum = [
            'in-person-meeting' => 'in-person',
            'remote-meeting' => 'remote',
            'email-meeting' => 'email',
        ];

        foreach ($meetingTypes as $type) {
            $typeId = DB::table('types')->insertGetId(array_merge($type, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            // Migrate data back to pivot
            $enumValue = $slugToEnum[$type['slug']];
            $meetings = DB::table('meetings')
                ->where('type', $enumValue)
                ->pluck('id');

            foreach ($meetings as $meetingId) {
                DB::table('typeables')->insert([
                    'type_id' => $typeId,
                    'typeable_type' => Meeting::class,
                    'typeable_id' => $meetingId,
                ]);
            }
        }

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
