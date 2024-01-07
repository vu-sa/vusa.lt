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
        Schema::create('role_can_attach_types', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('types')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_can_attach_types');
    }
};
