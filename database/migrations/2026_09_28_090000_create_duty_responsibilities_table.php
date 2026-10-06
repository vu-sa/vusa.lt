<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_responsibilities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('duty_id')->constrained()->cascadeOnDelete();
            $table->string('responsibility');
            // Tenants and types have integer keys, institutions ULIDs, so the id is a string.
            $table->string('scope_type');
            $table->string('scope_id', 26);
            $table->timestamps();

            $table->unique(['duty_id', 'responsibility', 'scope_type', 'scope_id'], 'duty_responsibilities_unique');
            $table->index(['responsibility', 'scope_type', 'scope_id'], 'duty_responsibilities_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_responsibilities');
    }
};
