<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_activity_requests', function (Blueprint $table): void {
            $table->string('campaign_type')->default('activity_confirmation');
            $table->date('period_end')->nullable();
            $table->string('locale', 5)->nullable();
            $table->string('resolution_source')->nullable();
            $table->foreignUlid('resolved_by_request_id')->nullable()->constrained('institution_activity_requests')->nullOnDelete();
            $table->index(['institution_id', 'recipient_id', 'campaign_type'], 'activity_request_audience_index');
        });
        Schema::create('institution_activity_request_results', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('request_id')->constrained('institution_activity_requests')->cascadeOnDelete();
            $table->foreignUlid('meeting_id')->nullable()->constrained('meetings')->nullOnDelete();
            $table->foreignUlid('check_in_id')->nullable()->constrained('institution_check_ins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_activity_request_results');
        Schema::table('institution_activity_requests', function (Blueprint $table): void {
            $table->dropForeign(['resolved_by_request_id']);
            $table->dropIndex('activity_request_audience_index');
            $table->dropColumn(['campaign_type', 'period_end', 'locale', 'resolution_source', 'resolved_by_request_id']);
        });
    }
};
