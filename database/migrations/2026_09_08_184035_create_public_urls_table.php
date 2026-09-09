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
        Schema::create('public_urls', function (Blueprint $table) {
            $table->id();
            $table->morphs('urlable');
            $table->string('locale', 2);
            // Wider than the app's default 125-char cap (AppServiceProvider::defaultStringLength):
            // a Page permalink alone is validated up to 255 chars and News has no cap at all —
            // domain + prefix on top of either routinely exceeds 255.
            $table->string('url', 512)->unique();
            $table->timestamps();

            $table->index(['urlable_type', 'urlable_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_urls');
    }
};
