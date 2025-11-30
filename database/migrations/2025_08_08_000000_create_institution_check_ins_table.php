<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institution_check_ins', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // tenants.id is unsignedInteger (renamed from legacy `padaliniai`), so match the type
            $table->unsignedInteger('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants');
            $table->foreignUlid('institution_id')->constrained('institutions');
            $table->foreignUlid('user_id')->constrained('users');
            $table->date('start_date');
            $table->date('end_date');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'end_date']);
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_check_ins');
    }
};
