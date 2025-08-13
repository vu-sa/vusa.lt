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
            $table->date('until_date');
            $table->dateTime('checked_at')->useCurrent();
            $table->string('confidence')->default('medium');
            $table->text('note')->nullable();
            $table->string('visibility')->default('institution');
            $table->string('state');
            $table->foreignUlid('invalidated_by_meeting_id')->nullable()->constrained('meetings');
            // removed: verified_count; derive from verifications table
            $table->string('mode')->default('blackout'); // blackout | heads_up
            $table->foreignUlid('disputed_by_user_id')->nullable()->constrained('users');
            $table->dateTime('disputed_at')->nullable();
            $table->foreignUlid('suppressed_by_user_id')->nullable()->constrained('users');
            $table->string('suppressed_reason')->nullable();
            $table->dateTime('suppressed_at')->nullable();
            $table->timestamps();

            $table->index(['institution_id', 'state']);
            $table->index('until_date');
            $table->index('mode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_check_ins');
    }
};
