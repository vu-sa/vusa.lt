<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_names', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('duty_id')->constrained('duties')->cascadeOnDelete();
            $table->json('name');
            $table->date('used_until');
            $table->timestamps();

            $table->index(['duty_id', 'used_until']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_names');
    }
};
