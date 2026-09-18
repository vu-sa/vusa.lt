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
        Schema::table('approvals', function (Blueprint $table) {
            $table->timestamp('reverted_at')->nullable()->after('notes');
            $table->foreignUlid('reverted_by_id')
                ->nullable()
                ->after('reverted_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('reversion_notes')->nullable()->after('reverted_by_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approvals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reverted_by_id');
            $table->dropColumn(['reverted_at', 'reversion_notes']);
        });
    }
};
