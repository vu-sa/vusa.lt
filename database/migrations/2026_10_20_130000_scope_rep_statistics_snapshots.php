<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rep_metric_snapshots', function (Blueprint $table): void {
            $table->dropUnique(['taken_on']);
            $table->unsignedInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->string('scope_key', 40)->default('global');
            $table->unsignedSmallInteger('definition_version')->default(1);
            $table->boolean('is_reconstructed')->default(false);
            $table->timestamp('measured_at')->nullable();
            $table->json('contributors')->nullable();
            $table->json('supplementary')->nullable();
            $table->unique(['taken_on', 'scope_key', 'window_days', 'definition_version'], 'rep_metrics_scope_day_unique');
        });

        Schema::table('institution_status_snapshots', function (Blueprint $table): void {
            $table->json('institution_statuses')->nullable();
            $table->json('meeting_ids')->nullable();
            $table->unsignedSmallInteger('population_added')->default(0);
            $table->unsignedSmallInteger('population_removed')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('institution_status_snapshots', function (Blueprint $table): void {
            $table->dropColumn(['institution_statuses', 'meeting_ids', 'population_added', 'population_removed']);
        });

        Schema::table('rep_metric_snapshots', function (Blueprint $table): void {
            $table->dropUnique('rep_metrics_scope_day_unique');
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id', 'scope_key', 'definition_version', 'is_reconstructed', 'measured_at', 'contributors', 'supplementary']);
        });
    }
};
