<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('calendar', function (Blueprint $table): void {
            // Nullable so legacy rows can stay unset and be found by the backfill
            // command's review queue — StoreCalendarRequest/UpdateCalendarRequest make
            // it required going forward, at the application layer.
            $table->foreignId('event_type_id')->nullable()->after('category_id')
                ->constrained('event_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('calendar', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('event_type_id');
        });
    }
};
