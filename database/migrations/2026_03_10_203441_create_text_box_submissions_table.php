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
        Schema::create('text_box_submissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('content_part_id')->constrained('content_parts')->cascadeOnDelete();
            $table->text('text');
            $table->char('user_id', 26)->nullable();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->index('content_part_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('text_box_submissions');
    }
};
