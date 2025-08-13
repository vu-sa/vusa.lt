<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institution_check_in_verifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('check_in_id')->constrained('institution_check_ins')->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained('users');
            $table->timestamps();

            $table->unique(['check_in_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_check_in_verifications');
    }
};
