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
        Schema::table('documents', function (Blueprint $table) {
            $table->string('sync_status')->default('pending')->after('checked_at')->comment('Status of SharePoint sync: pending, syncing, success, failed');
            $table->text('sync_error_message')->nullable()->after('sync_status')->comment('Error message from failed sync attempts');
            $table->unsignedTinyInteger('sync_attempts')->default(0)->after('sync_error_message')->comment('Number of sync attempts made');
            $table->timestamp('last_sync_attempt_at')->nullable()->after('sync_attempts')->comment('Timestamp of last sync attempt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['sync_status', 'sync_error_message', 'sync_attempts', 'last_sync_attempt_at']);
        });
    }
};
