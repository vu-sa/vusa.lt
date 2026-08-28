<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Let an agenda item carry a slot, not just a moment.
 *
 * `start_time` alone says when a question is taken up; longer posėdžiai want to publish how long
 * each one runs, so people can turn up for the part that concerns them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agenda_items', function (Blueprint $table): void {
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('agenda_items', function (Blueprint $table): void {
            $table->dropColumn('end_time');
        });
    }
};
