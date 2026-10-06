<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_activity_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // Everything sent together (one coordinator send, one periodicity task) shares it.
            $table->ulid('send_id')->index();
            $table->foreignUlid('institution_id')->constrained('institutions')->cascadeOnDelete();
            $table->foreignUlid('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUlid('task_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->date('period_start');
            $table->text('note')->nullable();
            $table->string('answer')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignUlid('meeting_id')->nullable()->constrained('meetings')->nullOnDelete();
            $table->foreignUlid('check_in_id')->nullable()->constrained('institution_check_ins')->nullOnDelete();
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->index(['institution_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_activity_requests');
    }
};
