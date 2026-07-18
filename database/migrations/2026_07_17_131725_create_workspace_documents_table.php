<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Named real-time collaborative documents inside a workspace. Mirrors
     * agenda_item_notes: the encoded Y.js CRDT snapshot is the source of truth,
     * plus a rendered HTML snapshot for instant read-only display. Archiving a
     * document is a soft delete.
     */
    public function up(): void
    {
        Schema::create('workspace_documents', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('workspace_id', 26);
            $table->string('title');
            // base64-encoded Y.js document snapshot (Y.encodeStateAsUpdate); stored as
            // text to avoid MySQL's 64KB BLOB limit and binary-escaping concerns.
            $table->longText('yjs_state')->nullable();
            $table->longText('content_html')->nullable();
            $table->char('updated_by', 26)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('workspace_id')->references('id')->on('workspaces')->cascadeOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_documents');
    }
};
