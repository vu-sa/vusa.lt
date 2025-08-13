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
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('title')->index();
        });

        // Update existing records to set proper order based on creation time
        \App\Models\Pivots\AgendaItem::orderBy('created_at')
            ->get()
            ->groupBy('meeting_id')
            ->each(function ($agendaItems) {
                $agendaItems->each(function ($item, $index) {
                    $item->update(['order' => $index + 1]);
                });
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agenda_items', function (Blueprint $table) {
            $table->dropIndex('agenda_items_order_index');
            $table->dropColumn('order');
        });
    }
};
