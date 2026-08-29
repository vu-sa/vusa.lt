<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Attach a nutarimas / protokolas to the meeting that produced it.
 *
 * `documents.institution_id` + `document_date` already say which body and which day; this names
 * the meeting itself, so the record and the agenda stop being two disconnected halves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->char('meeting_id', 26)->nullable()->after('institution_id');
            $table->foreign('meeting_id')->references('id')->on('meetings')->nullOnDelete();
            $table->index('meeting_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['meeting_id']);
            $table->dropIndex(['meeting_id']);
            $table->dropColumn('meeting_id');
        });
    }
};
