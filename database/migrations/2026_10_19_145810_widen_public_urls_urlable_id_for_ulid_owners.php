<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Institutions have ULID keys, so their retired contact URLs need a string owner id.
     */
    public function up(): void
    {
        Schema::table('public_urls', function (Blueprint $table) {
            $table->string('urlable_id', 36)->change();
        });
    }

    public function down(): void
    {
        // A ULID cannot be cast back to an integer.
        DB::table('public_urls')->where('urlable_type', 'institution')->delete();

        Schema::table('public_urls', function (Blueprint $table) {
            $table->unsignedBigInteger('urlable_id')->change();
        });
    }
};
