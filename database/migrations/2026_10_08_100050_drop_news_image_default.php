<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The old default was a stock photo, so every article that never chose an image showed it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news', function (Blueprint $table): void {
            $table->string('image', 200)->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('news', function (Blueprint $table): void {
            $table->string('image', 200)->nullable()->default('058543019bc51198ea1dc255580d215be99f2297.jpeg')->change();
        });
    }
};
