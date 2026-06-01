<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Holds the private, real-time collaborative "Atstovų pastabos" document for an
     * agenda item. One row per agenda item: the encoded Y.js CRDT snapshot plus a
     * rendered HTML snapshot for instant read-only display.
     */
    public function up(): void
    {
        Schema::create('agenda_item_notes', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('agenda_item_id', 26)->unique();
            // base64-encoded Y.js document snapshot (Y.encodeStateAsUpdate); stored as
            // text to avoid MySQL's 64KB BLOB limit and binary-escaping concerns.
            $table->longText('yjs_state')->nullable();
            $table->longText('notes_html')->nullable();
            $table->char('updated_by', 26)->nullable();
            $table->timestamps();

            $table->foreign('agenda_item_id')->references('id')->on('agenda_items')->cascadeOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agenda_item_notes');
    }
};
