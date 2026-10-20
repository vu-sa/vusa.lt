<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One closed day per padalinys: how many bodies were in each activity status, and what changed that day. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_status_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->date('taken_on');
            $table->unsignedInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedSmallInteger('all');
            $table->unsignedSmallInteger('needs_attention');
            $table->unsignedSmallInteger('overdue');
            $table->unsignedSmallInteger('approaching');
            $table->unsignedSmallInteger('no_activity');
            $table->unsignedSmallInteger('recent_check_in');
            $table->unsignedSmallInteger('current');
            $table->unsignedSmallInteger('meetings_held');
            $table->unsignedSmallInteger('meetings_registered');
            $table->unsignedSmallInteger('check_ins_registered');
            $table->unsignedSmallInteger('entered_attention');
            $table->unsignedSmallInteger('left_attention');
            $table->boolean('is_reconstructed')->default(false);
            $table->timestamps();

            $table->unique(['taken_on', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_status_snapshots');
    }
};
