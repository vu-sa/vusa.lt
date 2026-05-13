<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dutiables', function (Blueprint $table) {
            $table->ulid('via_dutiable_id')->nullable()->after('id');
            $table->foreign('via_dutiable_id')->references('id')->on('dutiables')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dutiables', function (Blueprint $table) {
            $table->dropForeign(['via_dutiable_id']);
            $table->dropColumn('via_dutiable_id');
        });
    }
};
