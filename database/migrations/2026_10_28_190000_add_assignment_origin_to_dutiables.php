<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dutiables', function (Blueprint $table): void {
            $table->string('assignment_origin', 20)->default('unknown');
        });
        DB::table('dutiables')->whereNotNull('via_dutiable_id')->update([
            'assignment_origin' => 'ex_officio', 'updated_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('dutiables', fn (Blueprint $table) => $table->dropColumn('assignment_origin'));
    }
};
