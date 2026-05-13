<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_tenant', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('duty_id')->constrained('duties')->cascadeOnDelete();
            $table->unsignedInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unsignedInteger('quota')->nullable();
            $table->timestamps();

            $table->unique(['duty_id', 'tenant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_tenant');
    }
};
