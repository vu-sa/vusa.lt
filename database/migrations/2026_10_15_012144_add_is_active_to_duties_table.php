<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('duties', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('places_to_occupy');
        });
    }

    public function down(): void
    {
        Schema::table('duties', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }
};
