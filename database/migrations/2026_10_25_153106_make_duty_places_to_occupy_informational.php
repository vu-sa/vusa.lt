<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('duties', function (Blueprint $table): void {
            $table->unsignedInteger('places_to_occupy')->nullable()->default(null)
                ->comment('Manually maintained informational seat count')->change();
        });

        DB::table('duties')->whereNotNull('places_to_occupy')->update([
            'places_to_occupy' => null,
            'updated_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        // Cleared reference numbers cannot be reconstructed.
        Schema::table('duties', function (Blueprint $table): void {
            $table->unsignedInteger('places_to_occupy')->nullable()->default(1)
                ->comment('Full number of positions to occupy for this duty')->change();
        });
    }
};
