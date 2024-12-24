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
        Schema::table('documents', function (Blueprint $table) {
            $table->string('name', 500)->change();
            $table->string('title', 500)->change();
            $table->date('effective_date')->nullable();
            $table->date('expiration_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('name', 200)->change();
            $table->string('title', 200)->change();
            $table->dropColumn('effective_date');
            $table->dropColumn('expiration_date');
        });
    }
};
