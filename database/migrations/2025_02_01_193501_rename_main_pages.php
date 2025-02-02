<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::rename('main_page', 'quick_links');

        // Rename permissions
        $permissions = [
            'mainPages.create.padalinys' => 'quickLinks.create.padalinys',
            'mainPages.read.padalinys' => 'quickLinks.read.padalinys',
            'mainPages.update.padalinys' => 'quickLinks.update.padalinys',
            'mainPages.delete.padalinys' => 'quickLinks.delete.padalinys',
        ];

        foreach ($permissions as $oldName => $newName) {
            DB::table('permissions')->where('name', $oldName)->update(['name' => $newName]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('quick_links', 'main_page');

        // Rename permissions
        $permissions = [
            'quickLinks.create.padalinys' => 'mainPages.create.padalinys',
            'quickLinks.read.padalinys' => 'mainPages.read.padalinys',
            'quickLinks.update.padalinys' => 'mainPages.update.padalinys',
            'quickLinks.delete.padalinys' => 'mainPages.delete.padalinys',
        ];

        foreach ($permissions as $oldName => $newName) {
            DB::table('permissions')->where('name', $oldName)->update(['name' => $newName]);
        }
    }
};
