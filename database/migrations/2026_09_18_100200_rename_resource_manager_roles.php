<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('roles')
            ->whereIn('name', ['Resource Manager', 'Resursų administratorius'])
            ->update(['name' => 'Išteklių administratorius']);

        DB::table('roles')
            ->where('name', 'Centrinio biuro resursų administratorius')
            ->update(['name' => 'Centrinio biuro išteklių administratorius']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')
            ->where('name', 'Išteklių administratorius')
            ->update(['name' => 'Resursų administratorius']);

        DB::table('roles')
            ->where('name', 'Centrinio biuro išteklių administratorius')
            ->update(['name' => 'Centrinio biuro resursų administratorius']);
    }
};
