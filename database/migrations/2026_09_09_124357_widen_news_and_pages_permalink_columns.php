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
        Schema::table('news', function (Blueprint $table) {
            $table->string('permalink', 255)->nullable()->change();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('permalink', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('news', function (Blueprint $table) {
            $table->string('permalink', 150)->nullable()->change();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->string('permalink', 200)->nullable()->change();
        });
    }
};
