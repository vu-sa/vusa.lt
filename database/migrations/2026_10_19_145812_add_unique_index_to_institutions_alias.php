<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('institutions')
            ->select('alias')
            ->groupBy('alias')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('alias');

        throw_if($duplicates->isNotEmpty(), RuntimeException::class, 'Duplicate institution aliases remain: '.$duplicates->implode(', '));

        Schema::table('institutions', function (Blueprint $table) {
            $table->unique('alias');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropUnique(['alias']);
        });
    }
};
