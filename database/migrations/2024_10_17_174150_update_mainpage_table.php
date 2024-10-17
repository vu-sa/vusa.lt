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
        Schema::table('main_page', function (Blueprint $table) {
            $table->dropColumn('image');
            $table->dropColumn('position');
            $table->dropColumn('type');
            $table->string('icon')->nullable()->after('text');
            $table->text('text')->nullable()->change();
            $table->boolean('is_important')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('main_page', function (Blueprint $table) {
            $table->string('image')->nullable()->after('text');
            $table->integer('position')->nullable()->after('image');
            $table->string('type')->nullable()->after('position');
            $table->dropColumn('icon');
            $table->text('text')->nullable(false)->change();
            $table->dropColumn('is_important');
        });
    }
};
